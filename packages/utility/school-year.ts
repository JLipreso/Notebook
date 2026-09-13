// School-year derivation for the PH school calendar (SY runs June–March/April).
// A notebook created in May 2027 belongs to SY 2027-2028 the moment classes
// start, so the cutover month is configurable and defaults to May.

const DEFAULT_CUTOVER_MONTH = 5 // 1-based; May

/** "2026-2027" for a given date (defaults to now). */
export function deriveSchoolYear(date: Date = new Date(), cutoverMonth = DEFAULT_CUTOVER_MONTH): string {
  const year = date.getFullYear()
  const startYear = date.getMonth() + 1 >= cutoverMonth ? year : year - 1
  return `${startYear}-${startYear + 1}`
}

/** "SY 2026-2027" display form. */
export function formatSchoolYear(schoolYear: string): string {
  return `SY ${schoolYear}`
}

export function isValidSchoolYear(value: string): boolean {
  const match = /^(\d{4})-(\d{4})$/.exec(value)
  if (!match) return false
  return Number(match[2]) === Number(match[1]) + 1
}
