import '@tailwindplus/elements'

if ('scrollRestoration' in history) {
	history.scrollRestoration = 'manual'
}

const resetScrollPosition = () => {
	window.scrollTo(0, 0)
	document.documentElement.scrollTop = 0
	document.body.scrollTop = 0

	document.body.setAttribute('data-scroll-x', '0')
	document.body.setAttribute('data-scroll-y', '0')

	document.querySelectorAll('[x-navigate\\:scroll], [wire\\:navigate\\:scroll]').forEach((el) => {
		el.scrollLeft = 0
		el.scrollTop = 0
		el.setAttribute('data-scroll-x', '0')
		el.setAttribute('data-scroll-y', '0')
	})
}

document.addEventListener('click', (event) => {
	const link = event.target.closest('[wire\\:navigate], [x-navigate]')
	if (!link) return
	resetScrollPosition()
})

document.addEventListener('livewire:navigating', resetScrollPosition)
document.addEventListener('livewire:navigated', resetScrollPosition)