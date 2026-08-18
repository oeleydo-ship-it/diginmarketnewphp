import './bootstrap';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

// Header: elevate with a shadow once the page scrolls (CSP forbids inline scripts).
const header = document.querySelector('[data-header]');
if (header) {
    const elevate = () => header.classList.toggle('shadow-sm', window.scrollY > 20);
    window.addEventListener('scroll', elevate, { passive: true });
    elevate();
}

// Tabbed panels (product page: description / reviews / comments / changelog).
document.querySelectorAll('[data-tabs]').forEach((root) => {
    const tabs = root.querySelectorAll('[data-tab]');
    const panels = root.querySelectorAll('[data-tab-panel]');
    const activate = (tab, sync) => {
        tabs.forEach((t) => t.classList.toggle('tab-active', t === tab));
        panels.forEach((p) => p.classList.toggle('hidden', p.dataset.tabPanel !== tab.dataset.tab));
        // data-tabs-sync keeps the active tab in the URL so it survives form-save redirects.
        if (sync && root.hasAttribute('data-tabs-sync')) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tab.dataset.tab);
            history.replaceState({}, '', url);
        }
    };
    tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab, true)));
    if (root.hasAttribute('data-tabs-sync')) {
        const current = new URL(window.location).searchParams.get('tab');
        const initial = [...tabs].find((t) => t.dataset.tab === current);
        if (initial) activate(initial, false);
    }
});

// File inputs marked data-auto-submit upload as soon as files are chosen
// (sellers otherwise miss the section-local submit button).
document.querySelectorAll('input[type="file"][data-auto-submit]').forEach((input) => {
    input.addEventListener('change', () => {
        if (input.files.length) input.form?.submit();
    });
});

// Styled dropzones hide the native input (.sr-only); reflect the chosen file names
// in the [data-file-label] span so sellers get feedback that the pick registered.
document.querySelectorAll('input[type="file"][data-file-input]').forEach((input) => {
    const label = input.closest('label')?.querySelector('[data-file-label]');
    if (!label) return;
    const original = label.textContent;
    input.addEventListener('change', () => {
        const names = Array.from(input.files).map((file) => file.name);
        label.textContent = names.length ? names.join(', ') : original;
        label.classList.toggle('font-semibold', names.length > 0);
        label.classList.toggle('text-primary', names.length > 0);
    });
});

// Confirm dialogs for destructive forms. Inline onsubmit handlers are blocked by the CSP,
// so forms declare data-confirm="message" and this delegated listener enforces it.
document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.dataset.confirm)) event.preventDefault();
});

// Category list: toggle the collapsible create form.
document.querySelectorAll('[data-toggle-create]').forEach((btn) => {
    const panel = document.querySelector('[data-create-panel]');
    if (!panel) return;
    btn.addEventListener('click', () => panel.classList.toggle('hidden'));
});

// Category list: expand / collapse the inline edit row.
document.querySelectorAll('[data-expand-edit]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.expandEdit;
        const panel = document.querySelector(`[data-edit-panel="${id}"]`);
        if (!panel) return;
        const isOpen = !panel.classList.contains('hidden');
        // Close all other open panels first.
        document.querySelectorAll('[data-edit-panel]').forEach((p) => p.classList.add('hidden'));
        if (!isOpen) panel.classList.remove('hidden');
    });
});

document.querySelectorAll('[data-close-edit]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.closeEdit;
        document.querySelector(`[data-edit-panel="${id}"]`)?.classList.add('hidden');
    });
});

// Seller review table: open one inline rejection panel at a time.
document.querySelectorAll('[data-seller-reject-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.sellerRejectToggle;
        const panel = document.querySelector(`[data-seller-reject-panel="${id}"]`);
        if (!panel) return;
        const isOpen = !panel.classList.contains('hidden');
        document.querySelectorAll('[data-seller-reject-panel]').forEach((row) => row.classList.add('hidden'));
        document.querySelectorAll('[data-seller-reject-toggle]').forEach((toggle) => toggle.setAttribute('aria-expanded', 'false'));
        if (!isOpen) {
            panel.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
            panel.querySelector('input[name="reason"]')?.focus();
        }
    });
});

document.querySelectorAll('[data-seller-reject-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.sellerRejectClose;
        document.querySelector(`[data-seller-reject-panel="${id}"]`)?.classList.add('hidden');
        document.querySelector(`[data-seller-reject-toggle="${id}"]`)?.setAttribute('aria-expanded', 'false');
    });
});

// Invoice print button (inline onclick is blocked by the CSP).
document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});

// Cookie consent: either choice stores the decision for a year and hides the banner.
document.querySelectorAll('[data-cookie-choice]').forEach((button) => {
    button.addEventListener('click', () => {
        document.cookie = 'dm_cookie_consent=' + button.dataset.cookieChoice + ';path=/;max-age=31536000;SameSite=Lax';
        document.querySelector('[data-cookie-banner]')?.remove();
    });
});

// Tawk.to live chat: the layout emits a meta tag only when an admin configured a property id.
const tawk = document.querySelector('meta[name="tawk-embed"]');
if (tawk?.content) {
    window.Tawk_API = window.Tawk_API || {};
    window.Tawk_LoadStart = new Date();
    const script = document.createElement('script');
    script.src = 'https://embed.tawk.to/' + tawk.content;
    script.async = true;
    script.charset = 'UTF-8';
    script.setAttribute('crossorigin', '*');
    document.body.appendChild(script);
}

// Google Analytics (gtag.js): the layout emits a measurement-id meta tag and a
// gtag.js <script async> tag when configured. This file performs the config
// without any inline snippets (CSP-friendly).
const gaMeasurementId = document.querySelector('meta[name="google-analytics-measurement-id"]')?.content;
if (gaMeasurementId) {
    window.dataLayer = window.dataLayer || [];
    window.gtag =
        window.gtag ||
        function () {
            window.dataLayer.push(arguments);
        };
    window.gtag('js', new Date());
    window.gtag('config', gaMeasurementId);
}

// Dismissible modals: any [data-modal-dismiss] (backdrop or button) closes the
// nearest [data-modal]; Escape closes any open modal.
document.querySelectorAll('[data-modal]').forEach((modal) => {
    const close = () => modal.remove();
    modal.querySelectorAll('[data-modal-dismiss]').forEach((el) => el.addEventListener('click', close));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
});

// Selects marked data-submit-on-change submit their form immediately (e.g. sort dropdowns).
document.querySelectorAll('[data-submit-on-change]').forEach((el) => {
    el.addEventListener('change', () => el.form?.submit());
});

// Instant search suggestions under header/hero search fields.
document.querySelectorAll('[data-live-search]').forEach((form) => {
    const input = form.querySelector('input[name="q"]');
    const panel = form.querySelector('[data-search-results]');
    const endpoint = form.dataset.suggestUrl;
    if (!input || !panel || !endpoint) return;

    let timer = 0;
    const hide = () => {
        panel.hidden = true;
        panel.replaceChildren();
    };
    const showItems = (items) => {
        panel.replaceChildren();
        if (!items.length) {
            hide();
            return;
        }
        items.forEach((item) => {
            const link = document.createElement('a');
            link.href = item.url;
            link.className = 'flex items-center justify-between gap-3 px-4 py-3 text-sm transition-colors hover:bg-surface-container-low';
            const title = document.createElement('span');
            title.className = 'min-w-0 truncate font-semibold text-on-surface';
            title.textContent = item.title;
            const meta = document.createElement('span');
            meta.className = 'shrink-0 font-mono text-xs text-on-surface-variant';
            meta.textContent = '$' + item.price;
            link.append(title, meta);
            panel.append(link);
        });
        const more = document.createElement('a');
        more.href = form.action + '?q=' + encodeURIComponent(input.value.trim());
        more.className = 'block border-t border-outline-variant px-4 py-2.5 text-center text-xs font-semibold text-primary hover:bg-surface-container-low';
        more.textContent = 'See all results';
        panel.append(more);
        panel.hidden = false;
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) {
            hide();
            return;
        }
        timer = window.setTimeout(async () => {
            try {
                const response = await fetch(endpoint + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                showItems(data.products || []);
            } catch {
                hide();
            }
        }, 200);
    });
    input.addEventListener('blur', () => window.setTimeout(hide, 180));
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') hide();
    });
});

// Product screenshot gallery: thumbs swap the main preview without leaving the page.
document.querySelectorAll('[data-gallery]').forEach((root) => {
    const main = root.querySelector('[data-gallery-main]');
    const thumbs = root.querySelectorAll('[data-gallery-thumb]');
    if (!main || !thumbs.length) return;
    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            const src = thumb.dataset.src;
            if (!src) return;
            const img = document.createElement('img');
            img.src = src;
            img.alt = thumb.querySelector('img')?.alt || '';
            img.className = 'h-full w-full object-cover';
            img.loading = 'lazy';
            main.replaceChildren(img);
            thumbs.forEach((item) => {
                item.classList.toggle('ring-2', item === thumb);
                item.classList.toggle('ring-primary', item === thumb);
            });
        });
    });
});

// Payout method picker: show only the selected method's fields.
document.querySelectorAll('[data-payout-method]').forEach((select) => {
    const form = select.closest('[data-payout-form]') || document;
    const sync = () => {
        form.querySelectorAll('[data-payout-fields]').forEach((group) => {
            group.classList.toggle('hidden', group.dataset.payoutFields !== select.value);
        });
    };
    select.addEventListener('change', sync);
    sync();
});

// Mobile navigation toggle.
const navToggle = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');
if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => navMenu.classList.toggle('hidden'));
}

const adminToggle = document.querySelector('[data-admin-toggle]');
const adminSidebar = document.querySelector('[data-admin-sidebar]');
if (adminToggle && adminSidebar) {
    adminToggle.addEventListener('click', () => {
        adminSidebar.classList.toggle('hidden');
        adminSidebar.classList.toggle('flex');
    });
}

// Dark mode toggle: html.dark swaps the design-token palette (see app.css).
// The Material Symbols font is ligature-based, so the icon is just text.
document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
    const icon = toggle.querySelector('[data-theme-icon]');
    const sync = () => {
        const dark = document.documentElement.classList.contains('dark');
        toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
        if (icon) icon.textContent = dark ? 'light_mode' : 'dark_mode';
    };
    sync();
    toggle.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('dm-theme', dark ? 'dark' : 'light'); } catch { /* private mode */ }
        sync();
    });
});

const richEditorUploadUrl = document.querySelector('meta[name="rich-editor-upload-url"]')?.content;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const uploadRichEditorImage = async (file) => {
    if (!richEditorUploadUrl) throw new Error('Image uploads are unavailable.');

    const form = new FormData();
    form.append('image', file);

    const response = await fetch(richEditorUploadUrl, {
        method: 'POST',
        body: form,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok || !payload.url) {
        throw new Error(payload.message || 'Image upload failed.');
    }

    return payload.url;
};

const promptRichEditorImage = (quill, host) => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp,image/gif';
    input.click();

    input.addEventListener('change', async () => {
        const [file] = Array.from(input.files || []);
        if (!file) return;

        const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
        host.classList.add('is-uploading');

        try {
            const url = await uploadRichEditorImage(file);
            quill.insertEmbed(range.index, 'image', url, 'user');
            quill.setSelection(range.index + 1, 0, 'silent');
            const image = quill.root.querySelector(`img[src="${CSS.escape(url)}"]`);
            if (image) image.setAttribute('alt', '');
        } catch (error) {
            window.alert(error instanceof Error ? error.message : 'Image upload failed.');
        } finally {
            host.classList.remove('is-uploading');
        }
    }, { once: true });
};

// WYSIWYG: textareas marked data-rich-editor stay in the form (name=description/body)
// while Quill is bundled via Vite so it works with the strict CSP.
document.querySelectorAll('textarea[data-rich-editor]').forEach((textarea) => {
    const host = document.createElement('div');
    host.className = 'rich-editor';
    textarea.classList.add('rich-editor-source');
    textarea.parentNode.insertBefore(host, textarea);
    host.append(textarea);

    const surface = document.createElement('div');
    host.append(surface);

    const locked = textarea.disabled;
    const quill = new Quill(surface, {
        theme: 'snow',
        readOnly: locked,
        modules: {
            toolbar: locked ? false : {
                container: [
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ header: 2 }, { header: 3 }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'code', 'code-block'],
                    ['link', 'image'],
                    ['clean'],
                ],
                handlers: {
                    image: () => promptRichEditorImage(quill, host),
                },
            },
        },
    });

    if (textarea.value.trim()) {
        quill.root.innerHTML = textarea.value;
    }

    const sync = () => {
        textarea.value = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
    };

    quill.on('text-change', sync);
    textarea.closest('form')?.addEventListener('submit', sync);
    sync();
});

// Admin bell: if Reverb / Echo is configured, patch new database notifications into
// the existing dropdown so admins see fresh orders / reviews without reloading.
const adminNotifications = document.querySelector('[data-admin-notifications]');

if (adminNotifications && window.Echo) {
    const userId = adminNotifications.dataset.userId;
    const list = adminNotifications.querySelector('[data-admin-notification-list]');
    const badge = adminNotifications.querySelector('[data-admin-notification-badge]');
    const empty = adminNotifications.querySelector('[data-admin-notification-empty]');
    const iconMap = {
        order: 'shopping_cart',
        seller: 'store',
        review: 'rate_review',
        withdrawal: 'payments',
        refund: 'assignment_return',
        dispute: 'gavel',
        subscription: 'workspace_premium',
    };

    const syncBadge = (nextCount) => {
        if (!badge) return;
        const count = Math.max(0, Number(nextCount || 0));
        badge.textContent = count > 9 ? '9+' : String(count);
        badge.classList.toggle('hidden', count === 0);
    };

    if (userId && list) {
        window.Echo.private(`App.Models.User.${userId}`).notification((notification) => {
            empty?.remove();

            const item = document.createElement('a');
            item.href = notification.url || '#';
            item.className = 'flex items-start gap-3 px-4 py-3 transition hover:bg-[#f6f8ff]';
            item.innerHTML = `
                <span class="material-symbols-outlined mt-0.5 text-[20px] text-[#3525cd]">${iconMap[notification.kind] || 'notifications'}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold">${notification.title || 'Notification'}</span>
                    <span class="text-xs text-[#777a8a]">Just now</span>
                </span>
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#3525cd]"></span>
            `;

            list.prepend(item);

            while (list.children.length > 10) {
                list.removeChild(list.lastElementChild);
            }

            syncBadge((Number(badge?.dataset.count || 0) || 0) + 1);
        });
    }
}
