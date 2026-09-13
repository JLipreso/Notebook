#!/usr/bin/env node
// Convert the PSA's quarterly PSGC publication (XLSX) into the CSV that
// PsgcSeeder reads. (2026-09-13-005 Phase 002 / D-030)
//
//   node scripts/psgc-xlsx-to-csv.mjs <path-to.xlsx> [out.csv]
//
// Writes backend/database/seeders/data/psgc-<YYYYQN>.csv by default, taking the
// quarter from the source filename when it can.
//
// Zero dependencies: an .xlsx is a zip of XML, and Node ships zlib. This runs on
// any machine that can run the repo, with no extra install, which is the point —
// the next developer re-runs it on the next quarterly release.

import fs from 'node:fs'
import path from 'node:path'
import zlib from 'node:zlib'

/* ── minimal zip reader ─────────────────────────────────────────────────── */

/** @returns {Map<string, Buffer>} entry name → uncompressed bytes */
function readZip(buffer) {
  const entries = new Map()

  // Walk local file headers (PK\x03\x04). Enough for xlsx, which never uses
  // zip64 or encryption for these files.
  let offset = 0

  while (offset < buffer.length - 4) {
    if (buffer.readUInt32LE(offset) !== 0x04034b50) break

    const method = buffer.readUInt16LE(offset + 8)
    const flags = buffer.readUInt16LE(offset + 6)
    let compressedSize = buffer.readUInt32LE(offset + 18)
    const nameLength = buffer.readUInt16LE(offset + 26)
    const extraLength = buffer.readUInt16LE(offset + 28)
    const name = buffer.toString('utf8', offset + 30, offset + 30 + nameLength)
    const dataStart = offset + 30 + nameLength + extraLength

    // Streamed entries put sizes in a trailing descriptor; find the next header.
    if (flags & 0x08 || compressedSize === 0) {
      let next = dataStart
      while (next < buffer.length - 4 && buffer.readUInt32LE(next) !== 0x08074b50) next++
      compressedSize = next - dataStart
    }

    const data = buffer.subarray(dataStart, dataStart + compressedSize)
    entries.set(name, method === 8 ? zlib.inflateRawSync(data) : data)

    offset = dataStart + compressedSize + (flags & 0x08 ? 16 : 0)
  }

  return entries
}

/* ── xlsx pieces ────────────────────────────────────────────────────────── */

function decodeXmlEntities(value) {
  return value
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&apos;/g, "'")
    .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number(code)))
    .replace(/&#x([0-9a-f]+);/gi, (_, code) => String.fromCharCode(parseInt(code, 16)))
    .replace(/&amp;/g, '&')
}

/** The shared string table: every text cell in the sheet points into this. */
function readSharedStrings(xml) {
  const strings = []

  for (const item of xml.split('<si>').slice(1)) {
    const end = item.indexOf('</si>')
    const body = end === -1 ? item : item.slice(0, end)
    // A string can be split across runs (<r><t>..</t></r><r><t>..</t></r>).
    const parts = [...body.matchAll(/<t[^>]*>([\s\S]*?)<\/t>/g)].map((m) => m[1])
    strings.push(decodeXmlEntities(parts.join('')))
  }

  return strings
}

function columnIndex(ref) {
  const letters = ref.match(/^[A-Z]+/)?.[0] ?? 'A'
  let index = 0
  for (const char of letters) index = index * 26 + (char.charCodeAt(0) - 64)
  return index - 1
}

/** @returns {string[][]} rows of cell text, ragged rows padded */
function readSheet(xml, sharedStrings) {
  const rows = []

  for (const rowXml of xml.split('<row ').slice(1)) {
    const cells = []

    for (const match of rowXml.matchAll(/<c r="([A-Z]+\d+)"([^>]*)>([\s\S]*?)<\/c>/g)) {
      const [, ref, attrs, body] = match
      const type = attrs.match(/t="([^"]+)"/)?.[1]
      let value = ''

      if (type === 's') {
        const index = Number(body.match(/<v>(\d+)<\/v>/)?.[1])
        value = sharedStrings[index] ?? ''
      } else if (type === 'inlineStr') {
        value = decodeXmlEntities(body.match(/<t[^>]*>([\s\S]*?)<\/t>/)?.[1] ?? '')
      } else {
        value = decodeXmlEntities(body.match(/<v>([\s\S]*?)<\/v>/)?.[1] ?? '')
      }

      const at = columnIndex(ref)
      while (cells.length < at) cells.push('')
      cells[at] = value
    }

    rows.push(cells)
  }

  return rows
}

function toCsv(rows) {
  return rows
    .map((row) =>
      row
        .map((cell) => {
          const value = String(cell ?? '')
          return /[",\n\r]/.test(value) ? `"${value.replace(/"/g, '""')}"` : value
        })
        .join(','),
    )
    .join('\n')
}

/* ── main ───────────────────────────────────────────────────────────────── */

const source = process.argv[2]

if (!source) {
  console.error('usage: node scripts/psgc-xlsx-to-csv.mjs <psgc.xlsx> [out.csv]')
  process.exit(1)
}

const zip = readZip(fs.readFileSync(source))

// Find the PSGC masterlist sheet by NAME, not by position — the PSA reorders
// and hides sheets between quarters.
const workbook = zip.get('xl/workbook.xml')?.toString('utf8') ?? ''
const rels = zip.get('xl/_rels/workbook.xml.rels')?.toString('utf8') ?? ''

const sheets = [...workbook.matchAll(/<sheet[^>]*name="([^"]*)"[^>]*r:id="([^"]*)"/g)].map(
  ([, name, rid]) => ({ name, rid }),
)

const target =
  sheets.find((s) => s.name.trim().toUpperCase() === 'PSGC') ??
  sheets.find((s) => /psgc/i.test(s.name))

if (!target) {
  console.error(`No PSGC sheet found. Sheets present: ${sheets.map((s) => s.name).join(', ')}`)
  process.exit(1)
}

const relTarget = rels.match(new RegExp(`Id="${target.rid}"[^>]*Target="([^"]*)"`))?.[1]

if (!relTarget) {
  console.error(`Could not resolve the sheet file for "${target.name}".`)
  process.exit(1)
}

const sheetPath = `xl/${relTarget.replace(/^\/?xl\//, '')}`
const sheetXml = zip.get(sheetPath)?.toString('utf8')

if (!sheetXml) {
  console.error(`Sheet file ${sheetPath} not found inside the workbook.`)
  process.exit(1)
}

const sharedStrings = readSharedStrings(zip.get('xl/sharedStrings.xml')?.toString('utf8') ?? '')
const rows = readSheet(sheetXml, sharedStrings)

// Default output name carries the quarter, e.g. PSGC-2Q-2026-... -> psgc-2026Q2.csv
const quarter = path.basename(source).match(/(\d)Q[-_ ]?(\d{4})/i)
const defaultName = quarter ? `psgc-${quarter[2]}Q${quarter[1]}.csv` : 'psgc-latest.csv'

const out =
  process.argv[3] ??
  path.join(process.cwd(), 'backend', 'database', 'seeders', 'data', defaultName)

fs.mkdirSync(path.dirname(out), { recursive: true })
fs.writeFileSync(out, toCsv(rows), 'utf8')

console.log(`sheet     : ${target.name} (${sheetPath})`)
console.log(`headers   : ${rows[0]?.filter(Boolean).join(' | ')}`)
console.log(`data rows : ${rows.length - 1}`)
console.log(`wrote     : ${out}`)
