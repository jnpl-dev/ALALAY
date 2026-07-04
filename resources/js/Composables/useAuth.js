import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useAuth() {
  const page = usePage()

  const user = computed(() => page.props.auth?.user ?? null)
  const isAuthenticated = computed(() => !!user.value)
  const role = computed(() => user.value?.role ?? null)

  const isAdmin = computed(() => role.value === 'admin')
  const isAicsStaff = computed(() => role.value === 'aics_staff')
  const isMswdo = computed(() => role.value === 'mswdo')
  const isAccountant = computed(() => role.value === 'accountant')
  const isTreasurer = computed(() => role.value === 'treasurer')
  const isMayorsOffice = computed(() => role.value === 'mayors_office')

  const fullName = computed(() => {
    if (!user.value) return ''
    const parts = [user.value.first_name, user.value.last_name].filter(Boolean)
    return parts.join(' ')
  })

  return {
    user,
    isAuthenticated,
    role,
    isAdmin,
    isAicsStaff,
    isMswdo,
    isAccountant,
    isTreasurer,
    isMayorsOffice,
    fullName,
  }
}
