import './bootstrap';
import 'bootstrap';
import editorWorker from 'monaco-editor/esm/vs/editor/editor.worker?worker';
import cssWorker from 'monaco-editor/esm/vs/language/css/css.worker?worker';
import htmlWorker from 'monaco-editor/esm/vs/language/html/html.worker?worker';
import jsonWorker from 'monaco-editor/esm/vs/language/json/json.worker?worker';
import tsWorker from 'monaco-editor/esm/vs/language/typescript/ts.worker?worker';

window.MonacoEnvironment = {
	getWorker(_moduleId, label) {
		if (label === 'json') {
			return new jsonWorker();
		}

		if (label === 'css' || label === 'scss' || label === 'less') {
			return new cssWorker();
		}

		if (label === 'html' || label === 'handlebars' || label === 'razor') {
			return new htmlWorker();
		}

		if (label === 'typescript' || label === 'javascript') {
			return new tsWorker();
		}

		return new editorWorker();
	},
};

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
	const monacoChangeCallbacks = new Map();
	let monacoModule = null;

	if (monacoEditorElements.length > 0) {
		monacoModule = await import('monaco-editor');

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
			const model = monacoModule.editor.createModel(textarea.value || '', language);

			const editor = monacoModule.editor.create(element, {
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
			if (element.id) {
				editorInstances.set(element.id, editor);
			}

			const syncToTextarea = () => {
				textarea.value = editor.getValue();
				const callback = monacoChangeCallbacks.get(targetId) || monacoChangeCallbacks.get(element.id || '');
				if (typeof callback === 'function') {
					callback(editor.getValue());
				}
			};

			editor.onDidChangeModelContent(syncToTextarea);
			syncToTextarea();

			const form = textarea.closest('form');
			if (form) {
				form.addEventListener('submit', syncToTextarea);
			}
		});
	}

	const slideEditorRoot = document.querySelector('[data-slide-editor]');

	if (slideEditorRoot) {
		const slidesList = slideEditorRoot.querySelector('[data-slide-list]');
		const addButton = slideEditorRoot.querySelector('[data-slide-add]');
		const deleteButton = slideEditorRoot.querySelector('[data-slide-delete]');
		const saveAllButton = slideEditorRoot.querySelector('[data-slide-save-all]');
		const importTriggerButton = slideEditorRoot.querySelector('[data-slide-import-trigger]');
		const importInput = slideEditorRoot.querySelector('[data-slide-import-input]');
		const statusEl = slideEditorRoot.querySelector('[data-slide-save-status]');
		const activeLabel = slideEditorRoot.querySelector('[data-slide-active-label]');
		const titleInput = slideEditorRoot.querySelector('[data-slide-title-input]');
		const activeSlideEditor = editorInstances.get('active-slide-editor');
		const activeSlideScriptEditor = editorInstances.get('active-slide-script-editor');
		const csrf = slideEditorRoot.dataset.csrfToken;
		const slidesIndexUrl = slideEditorRoot.dataset.slidesIndexUrl;
		const slidesStoreUrl = slideEditorRoot.dataset.slidesStoreUrl;
		const slidesReorderUrl = slideEditorRoot.dataset.slidesReorderUrl;
		const slidesSaveAllUrl = slideEditorRoot.dataset.slidesSaveAllUrl;
		const slidesImportUrl = slideEditorRoot.dataset.slidesImportUrl;
		const updateUrlTemplate = slideEditorRoot.dataset.slidesUpdateUrlTemplate;
		const deleteUrlTemplate = slideEditorRoot.dataset.slidesDeleteUrlTemplate;

		if (
			activeSlideEditor
			&& activeSlideScriptEditor
			&& slidesList
			&& addButton
			&& deleteButton
			&& saveAllButton
			&& importTriggerButton
			&& importInput
			&& statusEl
			&& activeLabel
			&& titleInput
			&& csrf
			&& slidesIndexUrl
			&& slidesStoreUrl
			&& slidesReorderUrl
			&& slidesSaveAllUrl
			&& slidesImportUrl
			&& updateUrlTemplate
			&& deleteUrlTemplate
		) {
			const state = {
				slides: [],
				activeSlideId: null,
				autosaveTimers: new Map(),
				isProgrammaticEdit: false,
			};

			const setStatus = (message) => {
				statusEl.textContent = message;
			};

			const findSlide = (slideId) => state.slides.find((slide) => slide.id === slideId);

			const slideDisplayTitle = (slide, index) => {
				const trimmedTitle = (slide.title || '').trim();

				return trimmedTitle !== '' ? trimmedTitle : `Slide ${index + 1}`;
			};

			const slideHeaderText = (slide) => {
				const trimmedTitle = (slide.title || '').trim();

				return trimmedTitle !== ''
					? `Slide ${slide.sort_order}: ${trimmedTitle}`
					: `Slide ${slide.sort_order}`;
			};

			const request = async (url, options = {}) => {
				const isFormData = options.body instanceof FormData;
				const headers = {
					'Accept': 'application/json',
					'X-CSRF-TOKEN': csrf,
					...(options.headers || {}),
				};

				if (!isFormData && !Object.keys(headers).some((key) => key.toLowerCase() === 'content-type')) {
					headers['Content-Type'] = 'application/json';
				}

				const response = await fetch(url, {
					...options,
					headers,
				});

				const payload = await response.json().catch(() => ({}));

				if (!response.ok) {
					const error = new Error(payload.message || 'Request failed.');
					error.payload = payload;
					throw error;
				}

				return payload;
			};

			const renderSlides = () => {
				slidesList.innerHTML = '';

				state.slides.forEach((slide, index) => {
					const item = document.createElement('li');
					item.className = 'list-group-item d-flex align-items-center justify-content-between gap-2';
					item.draggable = true;
					item.dataset.slideId = String(slide.id);

					const button = document.createElement('button');
					button.type = 'button';
					button.className = 'btn btn-sm text-start flex-grow-1';
					button.dataset.slideSelect = String(slide.id);
					button.textContent = slideDisplayTitle(slide, index);

					if (slide.id === state.activeSlideId) {
						button.classList.add('btn-primary');
					} else {
						button.classList.add('btn-outline-secondary');
					}

					const handle = document.createElement('span');
					handle.className = 'text-body-secondary';
					handle.textContent = '::';

					item.appendChild(button);
					item.appendChild(handle);
					slidesList.appendChild(item);
				});
			};

			const setActiveSlide = (slideId) => {
				const slide = findSlide(slideId);

				if (!slide) {
					return;
				}

				state.activeSlideId = slide.id;
				state.isProgrammaticEdit = true;
				activeSlideEditor.setValue(slide.content || '');
				activeSlideScriptEditor.setValue(slide.script || '');
				titleInput.value = slide.title || '';
				titleInput.disabled = false;
				state.isProgrammaticEdit = false;
				activeLabel.textContent = slideHeaderText(slide);
				renderSlides();
				setStatus('Saved');
			};

			const loadSlides = async () => {
				setStatus('Loading slides...');

				const payload = await request(slidesIndexUrl, {
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
					},
				});

				state.slides = (payload.slides || []).map((slide, index) => ({
					...slide,
					sort_order: index + 1,
				}));

				if (state.slides.length === 0) {
					setStatus('No slides available.');
					activeLabel.textContent = 'No slide selected';
					titleInput.value = '';
					titleInput.disabled = true;
					activeSlideEditor.setValue('');
					activeSlideScriptEditor.setValue('');
					renderSlides();
					return;
				}

				if (!findSlide(state.activeSlideId)) {
					state.activeSlideId = state.slides[0].id;
				}

				setActiveSlide(state.activeSlideId);
			};

			const saveSlide = async (slideId) => {
				const slide = findSlide(slideId);
				if (!slide) {
					return;
				}

				setStatus(`Saving slide ${slide.sort_order}...`);

				try {
					const payload = await request(updateUrlTemplate.replace('__SLIDE_ID__', String(slide.id)), {
						method: 'PUT',
						body: JSON.stringify({
							title: slide.title || '',
							content: slide.content,
							script: slide.script || '',
						}),
					});

					const saved = payload.slide;
					slide.title = saved.title;
					slide.updated_at = saved.updated_at;
					activeLabel.textContent = slideHeaderText(slide);
					renderSlides();
					setStatus(`Saved slide ${slide.sort_order}`);
				} catch {
					setStatus(`Save failed on slide ${slide.sort_order}. Retrying on next change.`);
				}
			};

			const queueAutosave = (slideId) => {
				const existingTimer = state.autosaveTimers.get(slideId);
				if (existingTimer) {
					window.clearTimeout(existingTimer);
				}

				setStatus('Unsaved changes...');

				const timerId = window.setTimeout(() => {
					void saveSlide(slideId);
					state.autosaveTimers.delete(slideId);
				}, 700);

				state.autosaveTimers.set(slideId, timerId);
			};

			monacoChangeCallbacks.set('active-slide-textarea', (newValue) => {
				if (state.isProgrammaticEdit || state.activeSlideId === null) {
					return;
				}

				const activeSlide = findSlide(state.activeSlideId);
				if (!activeSlide) {
					return;
				}

				activeSlide.content = newValue;
				queueAutosave(activeSlide.id);
			});

			monacoChangeCallbacks.set('active-slide-script-textarea', (newValue) => {
				if (state.isProgrammaticEdit || state.activeSlideId === null) {
					return;
				}

				const activeSlide = findSlide(state.activeSlideId);
				if (!activeSlide) {
					return;
				}

				activeSlide.script = newValue;
				queueAutosave(activeSlide.id);
			});

			addButton.addEventListener('click', async () => {
				setStatus('Adding slide...');

				try {
					const nextSlideNumber = state.slides.length + 1;
					const payload = await request(slidesStoreUrl, {
						method: 'POST',
						body: JSON.stringify({
							title: `Slide ${nextSlideNumber}`,
							content: '# New slide\n\nAdd content here.',
							script: '',
						}),
					});

					state.slides.push(payload.slide);
					state.slides = state.slides.map((slide, index) => ({
						...slide,
						sort_order: index + 1,
					}));
					setActiveSlide(payload.slide.id);
					setStatus('Slide added');
				} catch {
					setStatus('Could not add slide.');
				}
			});

			deleteButton.addEventListener('click', async () => {
				if (state.activeSlideId === null) {
					return;
				}

				const activeSlide = findSlide(state.activeSlideId);
				if (!activeSlide) {
					return;
				}

				if (!window.confirm(`Delete slide ${activeSlide.sort_order}?`)) {
					return;
				}

				try {
					await request(deleteUrlTemplate.replace('__SLIDE_ID__', String(activeSlide.id)), {
						method: 'DELETE',
					});

					state.slides = state.slides.filter((slide) => slide.id !== activeSlide.id);
					state.slides = state.slides.map((slide, index) => ({
						...slide,
						sort_order: index + 1,
					}));

					if (state.slides.length > 0) {
						setActiveSlide(state.slides[0].id);
					} else {
						state.activeSlideId = null;
						titleInput.value = '';
						titleInput.disabled = true;
						activeSlideEditor.setValue('');
						activeSlideScriptEditor.setValue('');
						activeLabel.textContent = 'No slide selected';
						renderSlides();
					}

					setStatus('Slide deleted');
				} catch (error) {
					setStatus(error?.message || 'Could not delete slide.');
				}
			});

			saveAllButton.addEventListener('click', async () => {
				setStatus('Saving all slides...');

				try {
					await request(slidesSaveAllUrl, {
						method: 'POST',
						body: JSON.stringify({
							slides: state.slides.map((slide) => ({
								id: slide.id,
								title: slide.title || '',
								content: slide.content,
								script: slide.script || '',
							})),
						}),
					});

					setStatus('All slides saved');
				} catch {
					setStatus('Save all failed');
				}
			});

			importTriggerButton.addEventListener('click', () => {
				importInput.click();
			});

			importInput.addEventListener('change', async () => {
				const file = importInput.files?.[0];

				if (!file) {
					return;
				}

				if (!window.confirm('Importing markdown replaces all current slides. Continue?')) {
					importInput.value = '';
					return;
				}

				const formData = new FormData();
				formData.append('markdown_file', file);

				setStatus('Importing markdown...');

				try {
					const payload = await request(slidesImportUrl, {
						method: 'POST',
						body: formData,
					});

					state.slides = (payload.slides || []).map((slide, index) => ({
						...slide,
						sort_order: index + 1,
					}));

					if (state.slides.length > 0) {
						setActiveSlide(state.slides[0].id);
					} else {
						state.activeSlideId = null;
						titleInput.value = '';
						titleInput.disabled = true;
						activeSlideEditor.setValue('');
						activeSlideScriptEditor.setValue('');
						activeLabel.textContent = 'No slide selected';
						renderSlides();
						setStatus('Import completed with no slides.');
					}

					if (state.slides.length > 0) {
						setStatus(`Imported ${payload.imported_count || state.slides.length} slide(s)`);
					}
				} catch (error) {
					setStatus(error?.message || 'Import failed');
				} finally {
					importInput.value = '';
				}
			});

			slidesList.addEventListener('click', (event) => {
				const btn = event.target.closest('[data-slide-select]');
				if (!btn) {
					return;
				}

				const slideId = Number(btn.dataset.slideSelect);
				if (Number.isNaN(slideId)) {
					return;
				}

				setActiveSlide(slideId);
			});

			titleInput.addEventListener('input', () => {
				if (state.activeSlideId === null) {
					return;
				}

				const activeSlide = findSlide(state.activeSlideId);
				if (!activeSlide) {
					return;
				}

				activeSlide.title = titleInput.value;
				activeLabel.textContent = slideHeaderText(activeSlide);
				renderSlides();
				queueAutosave(activeSlide.id);
			});

			let draggedSlideId = null;

			slidesList.addEventListener('dragstart', (event) => {
				const row = event.target.closest('[data-slide-id]');
				if (!row) {
					return;
				}

				draggedSlideId = Number(row.dataset.slideId);
				event.dataTransfer.effectAllowed = 'move';
			});

			slidesList.addEventListener('dragover', (event) => {
				event.preventDefault();
				event.dataTransfer.dropEffect = 'move';
			});

			slidesList.addEventListener('drop', async (event) => {
				event.preventDefault();

				const row = event.target.closest('[data-slide-id]');
				if (!row || draggedSlideId === null) {
					return;
				}

				const targetSlideId = Number(row.dataset.slideId);
				if (Number.isNaN(targetSlideId) || targetSlideId === draggedSlideId) {
					return;
				}

				const draggedIndex = state.slides.findIndex((slide) => slide.id === draggedSlideId);
				const targetIndex = state.slides.findIndex((slide) => slide.id === targetSlideId);

				if (draggedIndex < 0 || targetIndex < 0) {
					return;
				}

				const [moved] = state.slides.splice(draggedIndex, 1);
				state.slides.splice(targetIndex, 0, moved);
				state.slides = state.slides.map((slide, index) => ({
					...slide,
					sort_order: index + 1,
				}));
				renderSlides();

				setStatus('Saving order...');

				try {
					await request(slidesReorderUrl, {
						method: 'POST',
						body: JSON.stringify({
							slide_ids: state.slides.map((slide) => slide.id),
						}),
					});

					if (state.activeSlideId !== null) {
						const updatedActiveSlide = findSlide(state.activeSlideId);
						if (updatedActiveSlide) {
							activeLabel.textContent = slideHeaderText(updatedActiveSlide);
						}
					}

					setStatus('Order saved');
				} catch {
					setStatus('Order save failed');
				}
			});

			try {
				await loadSlides();
			} catch {
				setStatus('Could not load slides.');
			}
		}
	}

	const themeFormEl = document.querySelector('[data-theme-form]');

	if (themeFormEl) {
		const themeStatusEl = themeFormEl.querySelector('[data-theme-save-status]');
		const themeCsrf = themeFormEl.dataset.csrfToken;
		const themeUpdateUrl = themeFormEl.dataset.themeUpdateUrl;

		const setThemeStatus = (message) => {
			if (themeStatusEl) {
				themeStatusEl.textContent = message;
			}
		};

		themeFormEl.addEventListener('submit', async (event) => {
			event.preventDefault();

			const nameInput = themeFormEl.querySelector('#name');
			const descriptionInput = themeFormEl.querySelector('#description');
			const cssTextarea = themeFormEl.querySelector('#css');
			const submitBtn = themeFormEl.querySelector('[type="submit"]');

			if (submitBtn) {
				submitBtn.disabled = true;
			}

			setThemeStatus('Saving...');

			try {
				const response = await fetch(themeUpdateUrl, {
					method: 'PUT',
					headers: {
						'Accept': 'application/json',
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': themeCsrf,
					},
					body: JSON.stringify({
						name: nameInput?.value ?? '',
						description: descriptionInput?.value ?? '',
						css: cssTextarea?.value ?? '',
					}),
				});

				if (!response.ok) {
					const payload = await response.json().catch(() => ({}));
					setThemeStatus(payload.message || 'Save failed.');
				} else {
					setThemeStatus('Saved');
				}
			} catch {
				setThemeStatus('Save failed.');
			} finally {
				if (submitBtn) {
					submitBtn.disabled = false;
				}
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
