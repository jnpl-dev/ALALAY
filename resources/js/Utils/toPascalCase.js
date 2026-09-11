/**
 * Converts a snake_case or kebab-case string to Pascal Case.
 * Example: 'submission_complete' → 'Submission Complete'
 *          'turnstile_bypass' → 'Turnstile Bypass'
 *          'rate_limited' → 'Rate Limited'
 */
export function toPascalCase(str) {
  if (!str) return ''
  return str
    .replace(/[-_]+/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase())
    .replace(/\s+/g, ' ')
    .trim()
}
