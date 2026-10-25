(function () {
	const storageKey = 'digital-notice-board-theme';
	const storedTheme = localStorage.getItem(storageKey);
	if (storedTheme === 'dark') document.documentElement.dataset.theme = 'dark';
	else if (storedTheme === 'light') delete document.documentElement.dataset.theme;

	function initializeThemeToggle() {
		const toggle = document.querySelector('.theme-toggle');
		if (!toggle) return;
		function updateLabel() {
			const dark = document.documentElement.dataset.theme === 'dark';
			toggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
			toggle.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
		}
		updateLabel();
		toggle.addEventListener('click', function () {
			const dark = document.documentElement.dataset.theme !== 'dark';
			if (dark) document.documentElement.dataset.theme = 'dark';
			else delete document.documentElement.dataset.theme;
			localStorage.setItem(storageKey, dark ? 'dark' : 'light');
			updateLabel();
		});
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeThemeToggle, { once: true });
	else initializeThemeToggle();
})();