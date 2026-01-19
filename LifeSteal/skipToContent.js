document.addEventListener('DOMContentLoaded', () => {
  const skipLink = document.querySelector('.skip-to-content')
  const main = document.querySelector('#main-content')
  if (!skipLink || !main) return
  skipLink.addEventListener('click', e => {
    e.preventDefault()
    main.scrollIntoView({ behavior: 'smooth' })
    setTimeout(() => {
      main.setAttribute('tabindex', '-1')
      main.focus({ preventScroll: true })
      main.addEventListener('blur', () => main.removeAttribute('tabindex'), { once: true })
      setTimeout(() => {
        if (window.location.hash) {
          history.replaceState(null, '', window.location.pathname + window.location.search)
        }
      }, 150)
    }, 400)
  })
})
