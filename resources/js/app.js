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

	const monacoEditorElements = [...document.querySelectorAll('[data-monaco-editor]')];
	const editorInstances = new Map();

	if (monacoEditorElements.length > 0) {
		const monaco = await import('monaco-editor');

		monacoEditorElements.forEach((element) => {
			if (element.dataset.editorInitialized === 'true') {
				return;
			}

			const targetId = element.dataset.monacoTarget;
			const textarea = targetId ? document.getElementById(targetId) : null;

			if (!(textarea instanceof HTMLTextAreaElement)) {
				return;
			}

			element.dataset.editorInitialized = 'true';

			const language = element.dataset.monacoLanguage || 'plaintext';
			const model = monaco.editor.createModel(textarea.value || '', language);

			const editor = monaco.editor.create(element, {
				model,
				automaticLayout: true,
				minimap: { enabled: false },
				scrollBeyondLastLine: false,
				fontSize: 14,
				lineNumbersMinChars: 3,
				tabSize: 2,
				wordWrap: 'on',
				theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'vs-dark' : 'vs',
			});

			editorInstances.set(targetId, editor);

			const syncToTextarea = () => {
				textarea.value = editor.getValue();
			};

			editor.onDidChangeModelContent(syncToTextarea);
			syncToTextarea();

			const form = textarea.closest('form');
			if (form) {
				form.addEventListener('submit', syncToTextarea);
			}
		});
	}

	const copyButtons = [...document.querySelectorAll('[data-copy-text]')];
	copyButtons.forEach((button) => {
		button.addEventListener('click', async () => {
			const text = button.dataset.copyText || '';
			if (text === '') {
				return;
			}

			try {
				await navigator.clipboard.writeText(text);
				const originalText = button.textContent;
				button.textContent = 'Copied';
				setTimeout(() => {
					button.textContent = originalText;
				}, 1200);
			} catch {
				// Fallback for restricted clipboard contexts.
				window.prompt('Copy this value:', text);
			}
		});
	});

	// Insert-into-editor support for image library sidebar buttons.
	document.addEventListener('click', (event) => {
		const btn = event.target.closest('[data-monaco-insert]');
		if (!btn) return;

		const insertTargetId = btn.dataset.monacoInsertTarget;
		const insertText = btn.dataset.monacoInsertText;

		if (!insertTargetId || !insertText) return;

		const editor = editorInstances.get(insertTargetId);
		if (!editor) return;

		const sel = editor.getSelection() ?? {
			startLineNumber: 1, startColumn: 1,
			endLineNumber: 1, endColumn: 1,
		};
		editor.executeEdits('image-library', [{
			range: sel,
			text: insertText,
			forceMoveMarkers: true,
		}]);
		editor.focus();
	});

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
