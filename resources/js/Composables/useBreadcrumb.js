import { provide, inject, ref } from 'vue'

const BREADCRUMB_KEY = Symbol('breadcrumb')

export function useBreadcrumbProvider() {
  const items = ref([])
  provide(BREADCRUMB_KEY, items)
  return items
}

export function useBreadcrumb(items) {
  const breadcrumbItems = inject(BREADCRUMB_KEY)
  if (breadcrumbItems && items) {
    breadcrumbItems.value = items
  }
  return breadcrumbItems
}
