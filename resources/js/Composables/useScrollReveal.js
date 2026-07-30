import { ref, onMounted, onUnmounted } from 'vue'

export function useScrollReveal(rootMargin = '0px 0px -80px 0px') {
  const observer = ref(null)

  const observe = (el) => {
    if (!el) return
    el.classList.add('animate-reveal')
    observer.value?.observe(el)
  }

  onMounted(() => {
    observer.value = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('revealed')
          } else {
            entry.target.classList.remove('revealed')
          }
        })
      },
      { rootMargin, threshold: 0.1 }
    )

    document.querySelectorAll('.animate-reveal').forEach((el) => {
      observer.value?.observe(el)
    })
  })

  onUnmounted(() => {
    observer.value?.disconnect()
  })

  return { observe }
}
