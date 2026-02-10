import '@tailwindplus/elements'

if ('scrollRestoration' in history) {
	history.scrollRestoration = 'manual'
}

const clearNavigateScrollState = () => {
	document.body.setAttribute('data-scroll-x', '0')
	document.body.setAttribute('data-scroll-y', '0')

	document.querySelectorAll('[x-navigate\\:scroll], [wire\\:navigate\\:scroll]').forEach((el) => {
		el.setAttribute('data-scroll-x', '0')
		el.setAttribute('data-scroll-y', '0')
	})
}

const forceScrollTop = () => {
	window.scrollTo(0, 0)
	document.documentElement.scrollTop = 0
	document.body.scrollTop = 0
}

document.addEventListener('livewire:navigating', clearNavigateScrollState)
document.addEventListener('livewire:navigated', forceScrollTop)