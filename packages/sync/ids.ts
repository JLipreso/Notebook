import { v7 as uuidv7 } from 'uuid'

// UUIDv7 minting (D-013): every synced row's PK is generated ON THE CLIENT
// and the server inserts it as-is. v7 is time-ordered, so indexes stay warm.
export function mintId(): string {
  return uuidv7()
}
