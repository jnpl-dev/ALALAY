import { ref, computed, watch } from 'vue'

const PSGC_BASE = 'https://psgc.gitlab.io/api'

export function usePsgcAddress() {
  const provinces = ref([])
  const cities = ref([])
  const barangays = ref([])

  const selectedProvince = ref(null)
  const selectedCity = ref(null)
  const selectedBarangay = ref(null)
  const street = ref('')

  const loadingProvinces = ref(false)
  const loadingCities = ref(false)
  const loadingBarangays = ref(false)

  const addressString = computed(() => {
    const parts = [street.value, selectedBarangay.value?.name, selectedCity.value?.name, selectedProvince.value?.name]
    return parts.filter(Boolean).join(', ')
  })

  async function fetchProvinces() {
    loadingProvinces.value = true
    try {
      const res = await fetch(`${PSGC_BASE}/provinces/`)
      const data = await res.json()
      provinces.value = data
        .map(p => ({ code: p.code, name: p.name }))
        .sort((a, b) => a.name.localeCompare(b.name))
    } catch {
      provinces.value = []
    } finally {
      loadingProvinces.value = false
    }
  }

  async function fetchCities(provinceCode) {
    loadingCities.value = true
    cities.value = []
    selectedCity.value = null
    selectedBarangay.value = null
    barangays.value = []
    try {
      const res = await fetch(`${PSGC_BASE}/provinces/${provinceCode}/cities-municipalities/`)
      const data = await res.json()
      cities.value = data
        .map(c => ({ code: c.code, name: c.name }))
        .sort((a, b) => a.name.localeCompare(b.name))
    } catch {
      cities.value = []
    } finally {
      loadingCities.value = false
    }
  }

  async function fetchBarangays(cityCode) {
    loadingBarangays.value = true
    barangays.value = []
    selectedBarangay.value = null
    try {
      const res = await fetch(`${PSGC_BASE}/cities-municipalities/${cityCode}/barangays/`)
      const data = await res.json()
      barangays.value = data
        .map(b => ({ code: b.code, name: b.name }))
        .sort((a, b) => a.name.localeCompare(b.name))
    } catch {
      barangays.value = []
    } finally {
      loadingBarangays.value = false
    }
  }

  function setProvince(province) {
    selectedProvince.value = province
    if (province) {
      return fetchCities(province.code)
    } else {
      cities.value = []
      selectedCity.value = null
      selectedBarangay.value = null
      barangays.value = []
    }
  }

  function setCity(city) {
    selectedCity.value = city
    if (city) {
      fetchBarangays(city.code)
    } else {
      barangays.value = []
      selectedBarangay.value = null
    }
  }

  function setBarangay(barangay) {
    selectedBarangay.value = barangay
  }

  function reset() {
    selectedProvince.value = null
    selectedCity.value = null
    selectedBarangay.value = null
    street.value = ''
    provinces.value = []
    cities.value = []
    barangays.value = []
  }

  function setAddressFromString(addr) {
    if (!addr) return
    const parts = addr.split(', ').filter(Boolean)
    street.value = parts[0] || ''
  }

  return {
    provinces,
    cities,
    barangays,
    selectedProvince,
    selectedCity,
    selectedBarangay,
    street,
    loadingProvinces,
    loadingCities,
    loadingBarangays,
    addressString,
    fetchProvinces,
    setProvince,
    setCity,
    setBarangay,
    reset,
    setAddressFromString,
  }
}
