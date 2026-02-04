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
			const sel = quill.getSelection();
			// During some operations (notably paste), Quill may fire `text-change` before
			// the selection is updated. Fall back to end-of-doc so linkification still works.
			const cursor = sel ? sel.index + (sel.length || 0) : Math.max(0, quill.getLength() - 1);

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

			const lookbehind = 800;
			const windowStart = Math.max(0, cursor - lookbehind);
			let text = quill.getText(windowStart, cursor - windowStart);
			if (!text) return;

			// Ignore trailing whitespace for match scanning.
			text = text.replace(/[\s\n\t]+$/g, '');
			if (text === '') return;

			const urlRe = /(https?:\/\/[^\s<>"']+|www\.[^\s<>"']+)/gi;
			let match;
			while ((match = urlRe.exec(text))) {
				const raw = match[0];
				const cleaned = stripTrailingPunctuation(raw);
				if (!cleaned) continue;
				if (!looksLikeRealUrlToken(cleaned)) continue;

				const href = normalizeHref(cleaned);
				if (!href) continue;

				const startIndex = windowStart + match.index;
				const length = cleaned.length;

				// Avoid re-linking if already linked.
				const fmt = quill.getFormat(startIndex, length);
				if (fmt && fmt.link) continue;

				// Use source 'api' so we don't recursively trigger our own handler.
				quill.formatText(startIndex, length, 'link', href, 'api');
			}
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
			// Defer to ensure selection/index is up-to-date for paste/IME.
			setTimeout(() => {
				try { tryAutoLink(delta, { force: true }); } catch { /* ignore */ }
			}, 0);
		});

		// Allow opening links while editing (Quill is contenteditable, so normal click won't navigate).
		quill.root.addEventListener('click', (e) => {
			try {
				if (!(e.ctrlKey || e.metaKey)) return;
				const a = e.target && e.target.closest ? e.target.closest('a') : null;
				const href = a?.getAttribute?.('href');
				if (!href) return;
				e.preventDefault();
				e.stopPropagation();
				window.open(href, '_blank', 'noopener');
			} catch {
				// ignore
			}
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
				help.textContent = 'You can select multiple files.';
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

		let baseFilesForAppend = null;

		function mergeFiles(existing, incoming) {
			const keyOf = (f) => `${f.name}::${f.size}::${f.lastModified}`;
			const seen = new Set();
			const out = [];
			for (const f of [...existing, ...incoming]) {
				const k = keyOf(f);
				if (seen.has(k)) continue;
				seen.add(k);
				out.push(f);
			}
			return out;
		}

		button.addEventListener('click', () => {
			baseFilesForAppend = Array.from(input.files || []);
			input.click();
		});

		input.addEventListener('change', () => {
			const existing = Array.isArray(baseFilesForAppend) ? baseFilesForAppend : [];
			baseFilesForAppend = null;

			// Default browser behavior replaces the previous selection.
			// Rebuild the FileList to append newly selected files.
			try {
				if (existing.length) {
					const selected = Array.from(input.files || []);
					const merged = mergeFiles(existing, selected);
					if (typeof DataTransfer !== 'undefined') {
						const dt = new DataTransfer();
						for (const f of merged) dt.items.add(f);
						input.files = dt.files;
					}
				}
			} catch {
				// If the browser doesn't allow setting FileList, fall back to default behavior.
			}

			render();
		});
		clearBtn.addEventListener('click', () => {
			input.value = '';
			render();
		});

		// Initial render (e.g. back button restoring state)
		render();
	}
}

function initAttachmentCardClicks() {
	const roots = document.querySelectorAll('[data-activity-tabs-root]');
	for (const root of roots) {
		if (root.dataset.attachmentCardClicksInited === '1') continue;
		root.dataset.attachmentCardClicksInited = '1';

		root.addEventListener('click', (e) => {
			try {
				if (e.defaultPrevented) return;
				if (e.button !== 0) return;

				const card = e.target?.closest?.('[data-attachment-card]');
				if (!card || !root.contains(card)) return;

				// Ignore clicks on interactive elements inside the card.
				const interactive = e.target?.closest?.('a,button,summary,details,form,input,textarea,select');
				if (interactive && card.contains(interactive)) return;

				const url = card.getAttribute('data-open-url');
				if (!url) return;

				window.open(url, '_blank', 'noopener');
			} catch {
				// ignore
			}
		});

		root.addEventListener('keydown', (e) => {
			try {
				const card = e.target?.closest?.('[data-attachment-card]');
				if (!card || !root.contains(card)) return;

				if (e.key !== 'Enter' && e.key !== ' ') return;
				e.preventDefault();

				const url = card.getAttribute('data-open-url');
				if (!url) return;
				window.open(url, '_blank', 'noopener');
			} catch {
				// ignore
			}
		});
	}
}

function initCommentComposers() {
	const wrappers = document.querySelectorAll('[data-comment-composer]');
	for (const wrapper of wrappers) {
		if (wrapper.dataset.commentComposerInited === '1') continue;
		wrapper.dataset.commentComposerInited = '1';

		const textarea = wrapper.querySelector('textarea[name="body"]');
		const editor = wrapper.querySelector('[data-comment-editor]');
		if (!textarea || !editor) continue;

		const quill = new Quill(editor, {
			theme: 'snow',
			placeholder: 'Type / for menu',
			modules: {
				toolbar: false,
			},
		});

		textarea.value = textarea.value || '';
		if (textarea.value) {
			try {
				quill.clipboard.dangerouslyPasteHTML(textarea.value);
			} catch {
				// ignore
			}
		}

		function sync() {
			textarea.value = quill.root.innerHTML;
		}
		sync();
		quill.on('text-change', () => sync());

		const form = wrapper.closest('form');
		if (form) {
			form.addEventListener('submit', (e) => {
				sync();
				const plain = (quill.getText() || '').trim();
				const attachmentsInput = wrapper.querySelector('[data-comment-attachments-input]');
				const hasFiles = Boolean(attachmentsInput?.files?.length);
				if (!hasFiles && plain.length === 0) {
					e.preventDefault();
					alert('Please type a comment or attach a file.');
				}
			});
		}

		const formatBar = wrapper.querySelector('[data-comment-formatbar]');
		const emojiPopover = wrapper.querySelector('[data-comment-emoji-popover]');
		const notify = wrapper.querySelector('[data-comment-notify]');
		const attachmentsInput = wrapper.querySelector('[data-comment-attachments-input]');
		const attachmentsPreview = wrapper.querySelector('[data-comment-attachments-preview]');

		function toggle(el) {
			if (!el) return;
			el.classList.toggle('hidden');
		}
		function hide(el) {
			if (!el) return;
			el.classList.add('hidden');
		}
		function show(el) {
			if (!el) return;
			el.classList.remove('hidden');
		}

		function mergeFiles(existing, incoming) {
			const keyOf = (f) => `${f.name}::${f.size}::${f.lastModified}`;
			const seen = new Set();
			const out = [];
			for (const f of [...existing, ...incoming]) {
				const k = keyOf(f);
				if (seen.has(k)) continue;
				seen.add(k);
				out.push(f);
			}
			return out;
		}

		function setFiles(files) {
			if (!attachmentsInput) return;
			try {
				if (typeof DataTransfer === 'undefined') return;
				const dt = new DataTransfer();
				for (const f of files) dt.items.add(f);
				attachmentsInput.files = dt.files;
			} catch {
				// ignore
			}
		}

		function renderAttachments() {
			if (!attachmentsInput || !attachmentsPreview) return;
			attachmentsPreview.innerHTML = '';
			const files = Array.from(attachmentsInput.files || []);
			if (!files.length) {
				hide(attachmentsPreview);
				return;
			}
			show(attachmentsPreview);
			attachmentsPreview.classList.add('space-y-2');

			files.forEach((file) => {
				const row = document.createElement('div');
				row.className = 'flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2';
				const left = document.createElement('div');
				left.className = 'min-w-0';
				const name = document.createElement('div');
				name.className = 'truncate text-sm font-semibold text-slate-900';
				name.textContent = file.name || 'Attachment';
				const sub = document.createElement('div');
				sub.className = 'mt-0.5 text-xs text-slate-500';
				const kb = Math.max(1, Math.round((file.size || 0) / 1024));
				sub.textContent = `${kb} KB`;
				left.appendChild(name);
				left.appendChild(sub);

				const remove = document.createElement('button');
				remove.type = 'button';
				remove.className = 'shrink-0 rounded-md px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50';
				remove.textContent = 'Remove';
				remove.addEventListener('click', () => {
					const next = files.filter((f) => !(f.name === file.name && f.size === file.size && f.lastModified === file.lastModified));
					setFiles(next);
					renderAttachments();
				});

				row.appendChild(left);
				row.appendChild(remove);
				attachmentsPreview.appendChild(row);
			});
		}

		if (attachmentsInput) {
			let baseFilesForAppend = null;
			attachmentsInput.addEventListener('click', () => {
				baseFilesForAppend = Array.from(attachmentsInput.files || []);
			});
			attachmentsInput.addEventListener('change', () => {
				const existing = Array.isArray(baseFilesForAppend) ? baseFilesForAppend : [];
				baseFilesForAppend = null;

				try {
					if (existing.length) {
						const selected = Array.from(attachmentsInput.files || []);
						const merged = mergeFiles(existing, selected);
						setFiles(merged);
					}
				} catch {
					// ignore
				}
				renderAttachments();
			});

			renderAttachments();
		}

		wrapper.querySelectorAll('[data-comment-action]').forEach((btn) => {
			btn.addEventListener('click', () => {
				const action = btn.getAttribute('data-comment-action');
				if (action === 'format') {
					toggle(formatBar);
					hide(emojiPopover);
					quill.focus();
					return;
				}
				if (action === 'emoji') {
					toggle(emojiPopover);
					hide(formatBar);
					quill.focus();
					return;
				}
				if (action === 'mention') {
					const who = window.prompt('Mention who? (type a name)');
					const text = who ? `@${who} ` : '@';
					const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
					quill.insertText(range.index, text, 'user');
					quill.setSelection(range.index + text.length, 0, 'silent');
					quill.focus();
					return;
				}
				if (action === 'follow') {
					btn.classList.toggle('text-indigo-600');
					btn.classList.toggle('bg-indigo-50');
					btn.classList.toggle('ring-1');
					btn.classList.toggle('ring-indigo-200');
					if (notify) {
						if (!notify.dataset.originalText) notify.dataset.originalText = notify.textContent;
						notify.classList.remove('hidden');
						notify.textContent = btn.classList.contains('text-indigo-600')
							? 'You are following this task'
							: notify.dataset.originalText;
					}
					return;
				}
				if (action === 'attach') {
					attachmentsInput?.click();
					return;
				}
				if (action === 'ai') {
					alert('AI assist is not enabled yet.');
					return;
				}
			});
		});

		wrapper.querySelectorAll('[data-comment-emoji]').forEach((btn) => {
			btn.addEventListener('click', () => {
				const em = btn.getAttribute('data-comment-emoji') || btn.textContent || '';
				if (!em) return;
				const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
				quill.insertText(range.index, em, 'user');
				quill.setSelection(range.index + em.length, 0, 'silent');
				hide(emojiPopover);
				quill.focus();
			});
		});

		wrapper.querySelectorAll('[data-comment-format]').forEach((btn) => {
			btn.addEventListener('click', () => {
				const fmt = btn.getAttribute('data-comment-format');
				const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
				if (fmt === 'bold' || fmt === 'italic' || fmt === 'underline') {
					const current = quill.getFormat(range);
					quill.format(fmt, !current[fmt], 'user');
					quill.focus();
					return;
				}
				if (fmt === 'bullet') {
					quill.format('list', 'bullet', 'user');
					quill.focus();
					return;
				}
				if (fmt === 'ordered') {
					quill.format('list', 'ordered', 'user');
					quill.focus();
					return;
				}
				if (fmt === 'link') {
					const hrefRaw = window.prompt('Link URL (https://...)');
					const href = String(hrefRaw || '').trim();
					if (!href) return;
					if (range.length > 0) {
						quill.format('link', href, 'user');
					} else {
						const text = window.prompt('Link text') || href;
						quill.insertText(range.index, text, { link: href }, 'user');
						quill.setSelection(range.index + text.length, 0, 'silent');
					}
					quill.focus();
					return;
				}
			});
		});

		// Close popovers when clicking outside
		document.addEventListener(
			'click',
			(e) => {
				if (!wrapper.contains(e.target)) {
					hide(formatBar);
					hide(emojiPopover);
				}
			},
			{ capture: true }
		);
	}
}

function hydrateDynamicUi() {
	initRichTextEditors();
	initActivityTabs();
	initAttachmentPickers();
	initAttachmentCardClicks();
	initCommentComposers();
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
