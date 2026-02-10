import '@tailwindplus/elements'

if ('scrollRestoration' in history) {
	history.scrollRestoration = 'manual'
}

const NAV_CLASS = 'lw-navigating'

const setNavigating = (active) => {
	document.documentElement.classList.toggle(NAV_CLASS, active)
}

const clearNavigateScrollState = () => {
	queueMicrotask(() => {
		document.body.removeAttribute('data-scroll-x')
		document.body.removeAttribute('data-scroll-y')

		document.querySelectorAll('[x-navigate\\:scroll], [wire\\:navigate\\:scroll]').forEach((el) => {
			el.removeAttribute('data-scroll-x')
			el.removeAttribute('data-scroll-y')
		})
	})
}

const forceScrollTop = () => {
	window.scrollTo(0, 0)
	document.documentElement.scrollTop = 0
	document.body.scrollTop = 0
}

const handleNavigating = () => {
	setNavigating(true)
	clearNavigateScrollState()
}

const handleNavigated = () => {
	forceScrollTop()
	requestAnimationFrame(() => setNavigating(false))
}

document.addEventListener('alpine:navigating', handleNavigating)
document.addEventListener('livewire:navigating', handleNavigating)
document.addEventListener('alpine:navigated', handleNavigated)
document.addEventListener('livewire:navigated', handleNavigated)