import { beforeEach, describe, expect, it, vi } from 'vitest'

// The engine talks to the server through @notebook/services. Mocked here so the
// tests exercise the ENGINE — ordering, cursors, LWW convergence, the lock —
// rather than the network. The server half has its own 24 feature tests.
const pull = vi.fn()
const push = vi.fn()
const upload = vi.fn()

vi.mock('@notebook/services', () => ({
  syncService: {
    pull: (...args: unknown[]) => pull(...args),
    push: (...args: unknown[]) => push(...args),
  },
  attachmentService: {
    upload: (...args: unknown[]) => upload(...args),
  },
}))

const { MemoryAdapter } = await import('./storage/memory.adapter')
const { pullTable } = await import('./pull')
const { pushAll, pushTable, PUSH_ORDER } = await import('./outbox')
const { uploadPending, requeueFailed } = await import('./attachment-uploader')
const { runSync, isSyncing, resetSyncLock } = await import('./run')

function row(id: string, extra: Record<string, unknown> = {}) {
  return {
    id,
    updated_at: '2026-09-14T00:00:00.000Z',
    deleted_at: null,
    client_updated_at: '2026-09-14T00:00:00.000Z',
    ...extra,
  }
}

beforeEach(() => {
  // The lock is module-level and survives between tests — without this, one
  // test parking a run wedges every later runSync.
  resetSyncLock()
  pull.mockReset()
  push.mockReset()
  upload.mockReset()
  pull.mockResolvedValue({ rows: [], next_cursor: null })
  push.mockResolvedValue({ accepted: [], rejected: [] })
})

describe('pull', () => {
  it('writes rows CLEAN so they are not pushed straight back', async () => {
    const storage = new MemoryAdapter()
    pull.mockResolvedValueOnce({ rows: [row('a')], next_cursor: null })

    await pullTable(storage, 'notebooks')

    expect(await storage.get('notebooks', 'a')).toMatchObject({ id: 'a' })
    expect(await storage.dirtyRows('notebooks')).toHaveLength(0)
  })

  it('follows the cursor until a page comes back short', async () => {
    const storage = new MemoryAdapter()

    pull
      .mockResolvedValueOnce({ rows: [row('a'), row('b')], next_cursor: 'cursor-1' })
      .mockResolvedValueOnce({ rows: [row('c')], next_cursor: null })

    const pulled = await pullTable(storage, 'notebooks', 2)

    expect(pulled).toBe(3)
    expect(pull).toHaveBeenCalledTimes(2)
    // The second call resumed from the cursor the first returned.
    expect(pull.mock.calls[1][1]).toBe('cursor-1')
  })

  it('stores the cursor so the next run resumes', async () => {
    const storage = new MemoryAdapter()
    pull.mockResolvedValueOnce({
      rows: [row('a', { updated_at: '2026-09-14T10:00:00.000Z' })],
      next_cursor: null,
    })

    await pullTable(storage, 'notebooks')

    expect(await storage.getCursor('notebooks')).toBe('2026-09-14T10:00:00.000Z')
  })

  it('applies tombstones locally (§2.2)', async () => {
    const storage = new MemoryAdapter()
    pull.mockResolvedValueOnce({
      rows: [row('a', { deleted_at: '2026-09-14T09:00:00.000Z' })],
      next_cursor: null,
    })

    await pullTable(storage, 'notebooks')

    expect(await storage.get('notebooks', 'a')).toMatchObject({
      deleted_at: '2026-09-14T09:00:00.000Z',
    })
  })
})

describe('outbox', () => {
  it('pushes parent tables before children', async () => {
    const storage = new MemoryAdapter()
    await storage.put('page_attachments', row('att'))
    await storage.put('notebook_pages', row('page'))
    await storage.put('notebooks', row('nb'))

    push.mockResolvedValue({ accepted: [], rejected: [] })

    await pushAll(storage)

    const order = push.mock.calls.map((call) => call[0])
    expect(order).toEqual(['notebooks', 'notebook_pages', 'page_attachments'])
    expect(PUSH_ORDER).toEqual(['notebooks', 'notebook_pages', 'page_attachments'])
  })

  it('clears the dirty flag on accepted rows', async () => {
    const storage = new MemoryAdapter()
    await storage.put('notebooks', row('a'))

    push.mockResolvedValueOnce({ accepted: ['a'], rejected: [] })

    await pushTable(storage, 'notebooks')

    expect(await storage.dirtyRows('notebooks')).toHaveLength(0)
  })

  it('applies the server copy when LWW rejects a row (§2.3)', async () => {
    const storage = new MemoryAdapter()
    await storage.put('notebooks', row('a', { title: 'mine, older' }))

    push.mockResolvedValueOnce({
      accepted: [],
      rejected: [
        {
          row: row('a'),
          server_copy: row('a', { title: 'server wins' }),
          reason: 'stale',
        },
      ],
    })

    await pushTable(storage, 'notebooks')

    // The local row converges on the server's copy...
    expect(await storage.get('notebooks', 'a')).toMatchObject({ title: 'server wins' })
    // ...and is no longer dirty, so it is not re-pushed forever.
    expect(await storage.dirtyRows('notebooks')).toHaveLength(0)
  })

  it('stops retrying a forbidden row that has no server copy', async () => {
    const storage = new MemoryAdapter()
    await storage.put('notebooks', row('a'))

    push.mockResolvedValueOnce({
      accepted: [],
      rejected: [{ row: row('a'), server_copy: null, reason: 'forbidden' }],
    })

    await pushTable(storage, 'notebooks')

    expect(await storage.dirtyRows('notebooks')).toHaveLength(0)
  })

  it('batches large outboxes', async () => {
    const storage = new MemoryAdapter()
    for (let i = 0; i < 5; i += 1) await storage.put('notebooks', row(`n${i}`))

    push.mockResolvedValue({ accepted: [], rejected: [] })

    await pushTable(storage, 'notebooks', 2)

    expect(push).toHaveBeenCalledTimes(3)
    expect(push.mock.calls[0][1]).toHaveLength(2)
    expect(push.mock.calls[2][1]).toHaveLength(1)
  })

  it('pushes a local delete as an ordinary row carrying deleted_at (§2.4)', async () => {
    const storage = new MemoryAdapter()
    await storage.put('notebooks', row('a', { deleted_at: '2026-09-14T09:00:00.000Z' }))

    push.mockResolvedValueOnce({ accepted: ['a'], rejected: [] })

    await pushTable(storage, 'notebooks')

    expect(push.mock.calls[0][1][0]).toMatchObject({
      id: 'a',
      deleted_at: '2026-09-14T09:00:00.000Z',
    })
  })
})

describe('attachment uploader', () => {
  const file = new File(['x'], 'photo.jpg', { type: 'image/jpeg' })

  it('uploads pending rows and patches in the file_upload_id (§2.5)', async () => {
    const storage = new MemoryAdapter()
    await storage.putClean('page_attachments', {
      ...row('att'),
      kind: 'image',
      local_ref: 'file:///photo.jpg',
      upload_status: 'pending',
    })

    upload.mockResolvedValueOnce({ data: { id: 'upload-1', url: 'https://x/photo.jpg' } })

    const outcome = await uploadPending(storage, async () => file)

    expect(outcome).toEqual({ uploaded: 1, failed: 0 })
    expect(await storage.get('page_attachments', 'att')).toMatchObject({
      file_upload_id: 'upload-1',
      upload_status: 'uploaded',
    })
    // Marked dirty so the next push carries the id to the server.
    expect(await storage.dirtyRows('page_attachments')).toHaveLength(1)
  })

  it('marks a row failed when the local file is gone', async () => {
    const storage = new MemoryAdapter()
    await storage.putClean('page_attachments', {
      ...row('att'),
      kind: 'image',
      local_ref: 'file:///missing.jpg',
      upload_status: 'pending',
    })

    const outcome = await uploadPending(storage, async () => null)

    expect(outcome).toEqual({ uploaded: 0, failed: 1 })
    expect(await storage.get('page_attachments', 'att')).toMatchObject({
      upload_status: 'failed',
    })
  })

  it('skips rows that are already uploaded or deleted', async () => {
    const storage = new MemoryAdapter()
    await storage.putClean('page_attachments', {
      ...row('done'),
      local_ref: 'file:///a.jpg',
      upload_status: 'uploaded',
    })
    await storage.putClean('page_attachments', {
      ...row('gone'),
      local_ref: 'file:///b.jpg',
      upload_status: 'pending',
      deleted_at: '2026-09-14T09:00:00.000Z',
    })

    const outcome = await uploadPending(storage, async () => file)

    expect(outcome).toEqual({ uploaded: 0, failed: 0 })
    expect(upload).not.toHaveBeenCalled()
  })

  it('requeues failed rows for the next run', async () => {
    const storage = new MemoryAdapter()
    await storage.putClean('page_attachments', {
      ...row('att'),
      local_ref: 'file:///a.jpg',
      upload_status: 'failed',
    })

    expect(await requeueFailed(storage)).toBe(1)
    expect(await storage.get('page_attachments', 'att')).toMatchObject({
      upload_status: 'pending',
    })
  })
})

describe('runSync', () => {
  it('runs push before pull', async () => {
    const storage = new MemoryAdapter()
    const order: string[] = []

    // Without a dirty row the outbox has nothing to send and push() is never
    // called — the assertion would pass vacuously.
    await storage.put('notebooks', row('a'))

    push.mockImplementation(async () => {
      order.push('push')
      return { accepted: [], rejected: [] }
    })
    pull.mockImplementation(async () => {
      order.push('pull')
      return { rows: [], next_cursor: null }
    })

    await runSync({ storage })

    expect(order[0]).toBe('push')
    expect(order).toContain('pull')
  })

  /** The lock that stops foreground + network-regain + manual triple-firing. */
  it('is single flight — a concurrent call joins the run in progress', async () => {
    const storage = new MemoryAdapter()
    await storage.put('notebooks', row('a'))

    // Park on PUSH, not pull: runSync pulls five tables, so a parked pull would
    // need five releases. Resolve the deferred synchronously at creation time —
    // runSync does async work BEFORE reaching push(), so a `release` captured
    // inside the promise body is still null when the assertions run.
    let release!: () => void
    const parked = new Promise((resolve) => {
      release = () => resolve({ accepted: [], rejected: [] })
    })

    push.mockImplementationOnce(() => parked)

    const first = runSync({ storage })
    const second = runSync({ storage })

    try {
      expect(isSyncing()).toBe(true)
      // The SAME promise — the second call joined the run already in flight.
      expect(first).toBe(second)
    } finally {
      // Release even if an assertion throws, or the parked promise never
      // settles and this test times out instead of reporting the real failure.
      release()
    }

    await first
    await second

    expect(isSyncing()).toBe(false)
  }, 10000)

  it('reports an error instead of throwing', async () => {
    const storage = new MemoryAdapter()
    // A dirty row, or the outbox short-circuits and push() never rejects.
    await storage.put('notebooks', row('a'))
    push.mockRejectedValueOnce(new Error('offline'))

    const result = await runSync({ storage })

    expect(result.ran).toBe(true)
    expect(result.error).toBeInstanceOf(Error)
  })

  it('emits progress for each phase', async () => {
    const storage = new MemoryAdapter()
    const phases: string[] = []

    await runSync({ storage, onProgress: (p) => phases.push(p.phase) })

    expect(phases[0]).toBe('pushing')
    expect(phases).toContain('pulling')
    expect(phases.at(-1)).toBe('done')
  })

  it('skips the attachment step when no file reader is supplied (web)', async () => {
    const storage = new MemoryAdapter()
    await storage.putClean('page_attachments', {
      ...row('att'),
      local_ref: 'file:///a.jpg',
      upload_status: 'pending',
    })

    const result = await runSync({ storage })

    expect(result.uploads).toBeUndefined()
    expect(upload).not.toHaveBeenCalled()
  })
})
