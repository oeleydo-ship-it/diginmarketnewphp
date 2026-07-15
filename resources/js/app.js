import './bootstrap';

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
