import './bootstrap';

import './board';

import Quill from 'quill';
import Chart from 'chart.js/auto';

// Used by the admin-only Performance page charts.
window.Chart = Chart;

function initActivityTabs() {
	const roots = document.querySelectorAll('[data-activity-tabs-root]');
	for (const root of roots) {
		if (root.dataset.activityInited === '1') continue;
		root.dataset.activityInited = '1';

		const buttons = root.querySelectorAll('[data-activity-tab]');
		const panels = root.querySelectorAll('[data-activity-panel]');
		if (!buttons.length || !panels.length) continue;

		function setTab(tab) {
			panels.forEach((p) => {
				p.style.display = p.getAttribute('data-activity-panel') === tab ? '' : 'none';
			});
			buttons.forEach((b) => {
				const active = b.getAttribute('data-activity-tab') === tab;
				b.classList.toggle('border-slate-900', active);
				b.classList.toggle('text-slate-900', active);
				b.classList.toggle('border-transparent', !active);
				b.classList.toggle('text-slate-500', !active);
			});
		}

		buttons.forEach((b) => {
			b.addEventListener('click', () => setTab(b.getAttribute('data-activity-tab')));
		});

		// Respect the server-rendered initial state (which panel is visible).
		let initial = 'comments';
		for (const p of panels) {
			if (p.style.display !== 'none') {
				initial = p.getAttribute('data-activity-panel') || initial;
				break;
			}
		}
		setTab(initial);
	}
}

function initRichTextEditors() {
	const wrappers = document.querySelectorAll('[data-richtext]');
	for (const wrapper of wrappers) {
		const textarea = wrapper.querySelector('textarea');
		const editor = wrapper.querySelector('[data-richtext-editor]');
		if (!textarea || !editor) continue;
		if (editor.dataset.initialized === '1') continue;
		editor.dataset.initialized = '1';

		const uploadUrl = wrapper.dataset.uploadUrl;

		const initialHtml = editor.dataset.initial ? JSON.parse(editor.dataset.initial) : '';

		const csrfToken = document
			.querySelector('meta[name="csrf-token"]')
			?.getAttribute('content');

		const quill = new Quill(editor, {
			theme: 'snow',
			modules: {
				toolbar: {
					container: [
						[{ header: [1, 2, 3, false] }],
						['bold', 'italic', 'underline', 'strike'],
						[{ list: 'ordered' }, { list: 'bullet' }],
						['blockquote', 'code-block'],
						['link', 'image', 'attach'],
						['clean'],
					],
					handlers: {
						image: () => handleUploadAndInsert({ quill, uploadUrl, csrfToken, accept: 'image/*' }),
						attach: () => handleUploadAndInsert({ quill, uploadUrl, csrfToken, accept: '*/*' }),
					},
				},
			},
		});

		if (initialHtml) {
			quill.clipboard.dangerouslyPasteHTML(initialHtml);
		}
		textarea.value = initialHtml || '';

		quill.on('text-change', () => {
			textarea.value = quill.root.innerHTML;
		});

		const form = wrapper.closest('form');
		if (form) {
			form.addEventListener('submit', () => {
				textarea.value = quill.root.innerHTML;
			});
		}

		// Drag & drop uploads (Asana-like)
		quill.root.addEventListener('dragover', (e) => {
			e.preventDefault();
		});
		quill.root.addEventListener('drop', async (e) => {
			e.preventDefault();
			const dt = e.dataTransfer;
			if (!dt || !dt.files || dt.files.length === 0) return;

			for (const file of Array.from(dt.files)) {
				await uploadFileAndInsert({ quill, uploadUrl, csrfToken, file });
			}
		});

		// Paste image uploads
		quill.root.addEventListener('paste', async (e) => {
			const clipboard = e.clipboardData;
			if (!clipboard) return;

			const items = Array.from(clipboard.items || []);
			const files = items
				.filter((i) => i.kind === 'file')
				.map((i) => i.getAsFile())
				.filter(Boolean);

			if (!files.length) return;

			// Only intercept when at least one pasted item is an image.
			const hasImage = files.some((f) => typeof f.type === 'string' && f.type.startsWith('image/'));
			if (!hasImage) return;

			e.preventDefault();
			for (const file of files) {
				if (typeof file.type === 'string' && file.type.startsWith('image/')) {
					await uploadFileAndInsert({ quill, uploadUrl, csrfToken, file });
				}
			}
		});
	}
}

function hydrateDynamicUi() {
	initRichTextEditors();
	initActivityTabs();
}

async function handleUploadAndInsert({ quill, uploadUrl, csrfToken, accept }) {
	if (!uploadUrl) {
		alert('Upload URL is not configured.');
		return;
	}
	if (!csrfToken) {
		alert('CSRF token missing. Please refresh.');
		return;
	}

	const input = document.createElement('input');
	input.type = 'file';
	input.accept = accept;
	input.click();

	input.addEventListener(
		'change',
		async () => {
			const file = input.files?.[0];
			if (!file) return;

			await uploadFileAndInsert({ quill, uploadUrl, csrfToken, file });
		},
		{ once: true }
	);
}

async function uploadFileAndInsert({ quill, uploadUrl, csrfToken, file }) {
	if (!uploadUrl) {
		alert('Upload URL is not configured.');
		return;
	}
	if (!csrfToken) {
		alert('CSRF token missing. Please refresh.');
		return;
	}
	if (!file) return;

	const formData = new FormData();
	formData.append('file', file);

	let response;
	try {
		response = await fetch(uploadUrl, {
			method: 'POST',
			headers: {
				'X-CSRF-TOKEN': csrfToken,
				Accept: 'application/json',
			},
			body: formData,
		});
	} catch (error) {
		console.error(error);
		alert('Upload failed.');
		return;
	}

	if (!response.ok) {
		const text = await response.text();
		console.error(text);
		alert('Upload failed.');
		return;
	}

	const result = await response.json();
	const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };

	if (result.type === 'image' && result.url) {
		quill.insertEmbed(range.index, 'image', result.url, 'user');
		quill.setSelection(range.index + 1, 0, 'silent');
		return;
	}

	if (result.type === 'file' && result.url) {
		const name = result.name || file.name || 'Attachment';
		quill.insertText(range.index, name, { link: result.url }, 'user');
		quill.setSelection(range.index + name.length, 0, 'silent');
		return;
	}

	alert('Upload returned an unexpected response.');
}

document.addEventListener('DOMContentLoaded', hydrateDynamicUi);
document.addEventListener('gb:hydrate', hydrateDynamicUi);

// Allow inline scripts (or dynamic fetch UI) to re-run init.
window.__gbHydrate = hydrateDynamicUi;
