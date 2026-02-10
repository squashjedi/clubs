import '@tailwindplus/elements'

if ('scrollRestoration' in history) {
	history.scrollRestoration = 'manual'
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

document.addEventListener('alpine:navigating', clearNavigateScrollState)
document.addEventListener('livewire:navigating', clearNavigateScrollState)
document.addEventListener('livewire:navigated', forceScrollTop)