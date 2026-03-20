import './bootstrap';
import 'bootstrap';

const isSlidesRoute = window.location.pathname.startsWith('/slides');

if (!isSlidesRoute) {
	const storedTheme = localStorage.getItem('theme');
	const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
	const initialTheme = storedTheme || preferredTheme;

	document.documentElement.setAttribute('data-bs-theme', initialTheme);
}

document.addEventListener('DOMContentLoaded', async () => {
	if (!isSlidesRoute) {
		const root = document.documentElement;
		const toggle = document.getElementById('theme-toggle');
		const icon = document.getElementById('theme-icon');
		const label = document.getElementById('theme-label');

		const applyTheme = (theme) => {
			root.setAttribute('data-bs-theme', theme);

			if (icon) {
				icon.className = theme === 'dark' ? 'bi bi-sun me-1' : 'bi bi-moon-stars me-1';
			}

			if (label) {
				label.textContent = theme === 'dark' ? 'Light mode' : 'Dark mode';
			}
		};

		const currentTheme = root.getAttribute('data-bs-theme') || 'light';
		applyTheme(currentTheme);

		if (toggle) {
			toggle.addEventListener('click', () => {
				const activeTheme = root.getAttribute('data-bs-theme') || 'light';
				const nextTheme = activeTheme === 'dark' ? 'light' : 'dark';

				applyTheme(nextTheme);
				localStorage.setItem('theme', nextTheme);
			});
		}
	}

	const editorElements = [...document.querySelectorAll('[data-markdown-editor]')];

	if (editorElements.length > 0) {
		const [{ default: EasyMDE }] = await Promise.all([
			import('easymde'),
			import('easymde/dist/easymde.min.css'),
		]);

		editorElements.forEach((element) => {
			if (element.dataset.editorInitialized === 'true') {
				return;
			}

			element.dataset.editorInitialized = 'true';

			new EasyMDE({
				element,
				spellChecker: false,
				sideBySideFullscreen: false,
				status: ['lines', 'words', 'cursor'],
				minHeight: '360px',
				autofocus: false,
			});
		});
	}

	const codeBlocks = [...document.querySelectorAll('pre code')];

	if (codeBlocks.length > 0) {
		const [{ default: hljs }, { default: markdown }] = await Promise.all([
			import('highlight.js/lib/core'),
			import('highlight.js/lib/languages/markdown'),
			import('highlight.js/styles/github.css'),
		]);

		hljs.registerLanguage('markdown', markdown);

		codeBlocks.forEach((block) => {
			hljs.highlightElement(block);
		});
	}
});
