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

		function stripTrailingPunctuation(s) {
			return String(s || '').replace(/[\]\)\}>,.;:!]+$/g, '');
		}

		function normalizeHref(s) {
			const v = String(s || '').trim();
			if (v === '') return null;
			if (/^https?:\/\//i.test(v)) return v;
			if (/^www\./i.test(v)) return `https://${v}`;
			return null;
		}

		function looksLikeRealUrlToken(token) {
			if (!token) return false;
			if (!/^(https?:\/\/|www\.)\S+$/i.test(token)) return false;
			// Prevent linking very short/incomplete tokens like "https://" or "www."
			if (token.length < 8) return false;
			const withoutScheme = token.replace(/^https?:\/\//i, '');
			const hostish = withoutScheme.split(/[\/?#]/)[0] || '';
			// Require a dot in the host part (basic TLD signal)
			if (!hostish.includes('.')) return false;
			// Avoid linking if it ends with a bare dot
			if (hostish.endsWith('.')) return false;
			return true;
		}

		function tryAutoLink(delta, opts = {}) {
			const sel = quill.getSelection(true);
			if (!sel) return;

			let insertedText = '';
			try {
				for (const op of (delta?.ops || [])) {
					if (typeof op?.insert === 'string') insertedText += op.insert;
				}
			} catch {
				// ignore
			}

			const isPasteLike = insertedText.length > 1;
			const endsWithWhitespace = /[\s\n\t]$/.test(insertedText);
			const shouldLinkify = Boolean(opts.force) || isPasteLike || endsWithWhitespace;
			if (!shouldLinkify) return;
			// If this change didn't insert text and we're not forcing, skip.
			if (!opts.force && insertedText.length === 0) return;

			// Cursor is after the inserted content.
			const cursor = sel.index;
			const lookbehind = 240;
			const start = Math.max(0, cursor - lookbehind);
			let text = quill.getText(start, cursor - start);
			if (!text) return;

			// If we just typed a space/newline, ignore trailing whitespace when extracting the token.
			const trimmedRight = text.replace(/[\s\n\t]+$/g, '');
			if (trimmedRight === '') return;
			const trimmedLen = trimmedRight.length;
			const endIndex = start + trimmedLen;

			// Find last token boundary.
			const lastSpace = Math.max(
				trimmedRight.lastIndexOf(' '),
				trimmedRight.lastIndexOf('\n'),
				trimmedRight.lastIndexOf('\t')
			);
			let token = trimmedRight.slice(lastSpace + 1);
			const tokenStart = endIndex - token.length;
			token = stripTrailingPunctuation(token);
			if (!token) return;

			if (!looksLikeRealUrlToken(token)) return;

			const href = normalizeHref(token);
			if (!href) return;

			// Avoid re-linking if already linked.
			const fmt = quill.getFormat(tokenStart, token.length);
			if (fmt && fmt.link) return;

			quill.formatText(tokenStart, token.length, 'link', href, 'user');
		}

		if (initialHtml) {
			quill.clipboard.dangerouslyPasteHTML(initialHtml);
		}
		textarea.value = initialHtml || '';

		quill.on('text-change', () => {
			textarea.value = quill.root.innerHTML;
		});

		quill.on('text-change', (delta, oldDelta, source) => {
			if (source !== 'user') return;
			tryAutoLink(delta, { force: true });
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

function initAttachmentPickers() {
	const pickers = document.querySelectorAll('[data-attachments-picker]');
	for (const picker of pickers) {
		if (picker.dataset.attachmentsInited === '1') continue;
		picker.dataset.attachmentsInited = '1';

		const input = picker.querySelector('[data-attachments-input]');
		const button = picker.querySelector('[data-attachments-button]');
		const help = picker.querySelector('[data-attachments-help]');
		const preview = picker.querySelector('[data-attachments-preview]');
		const clearBtn = picker.querySelector('[data-attachments-clear]');
		if (!input || !button || !help || !preview || !clearBtn) continue;

		let objectUrls = [];

		function revokeAll() {
			for (const u of objectUrls) {
				try { URL.revokeObjectURL(u); } catch { /* ignore */ }
			}
			objectUrls = [];
		}

		function render() {
			revokeAll();
			preview.innerHTML = '';
			const files = Array.from(input.files || []);
			if (!files.length) {
				help.textContent = 'You can select multiple files (images, PDF, DOC/DOCX).';
				preview.classList.add('hidden');
				clearBtn.classList.add('hidden');
				return;
			}

			help.textContent = `${files.length} file(s) selected`;
			preview.classList.remove('hidden');
			clearBtn.classList.remove('hidden');

			for (const file of files) {
				const name = file.name || 'Attachment';
				const type = String(file.type || '').toLowerCase();
				const ext = (name.split('.').pop() || '').toLowerCase();
				const isImage = type.startsWith('image/') || ['jpg','jpeg','png','gif','webp'].includes(ext);

				let badge = 'FILE';
				let badgeClasses = 'bg-slate-100 text-slate-700';
				let typeLabel = 'Attachment';
				if (isImage) {
					badge = 'IMG';
					typeLabel = 'Image';
				} else if (ext === 'pdf' || type === 'application/pdf') {
					badge = 'PDF';
					typeLabel = 'PDF';
					badgeClasses = 'bg-rose-100 text-rose-700';
				} else if (['doc','docx'].includes(ext) || type.includes('word')) {
					badge = 'DOC';
					typeLabel = 'DOC';
					badgeClasses = 'bg-sky-100 text-sky-700';
				}

				const card = document.createElement('div');
				card.className = 'rounded-xl border border-slate-200 bg-white p-3';

				const row = document.createElement('div');
				row.className = 'flex items-center gap-3';

				const box = document.createElement('div');
				box.className = 'flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white';

				if (isImage) {
					const url = URL.createObjectURL(file);
					objectUrls.push(url);
					const img = document.createElement('img');
					img.src = url;
					img.alt = name;
					img.className = 'h-full w-full object-cover';
					box.appendChild(img);
				} else {
					const pill = document.createElement('div');
					pill.className = `inline-flex items-center rounded-md px-2 py-1 text-xs font-bold ${badgeClasses}`;
					pill.textContent = badge;
					box.appendChild(pill);
				}

				const meta = document.createElement('div');
				meta.className = 'min-w-0';
				const title = document.createElement('div');
				title.className = 'truncate text-sm font-semibold text-slate-900';
				title.textContent = name;
				const sub = document.createElement('div');
				sub.className = 'mt-0.5 truncate text-xs text-slate-500';
				sub.textContent = typeLabel;
				meta.appendChild(title);
				meta.appendChild(sub);

				row.appendChild(box);
				row.appendChild(meta);
				card.appendChild(row);
				preview.appendChild(card);
			}
		}

		button.addEventListener('click', () => input.click());
		input.addEventListener('change', render);
		clearBtn.addEventListener('click', () => {
			input.value = '';
			render();
		});

		// Initial render (e.g. back button restoring state)
		render();
	}
}

function hydrateDynamicUi() {
	initRichTextEditors();
	initActivityTabs();
	initAttachmentPickers();
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
