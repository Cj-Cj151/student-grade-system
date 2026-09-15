/**
 * app.js
 * Shared JavaScript utilities loaded on every page of the system:
 *   - toast notifications
 *   - a small fetch() wrapper that always talks JSON to our PHP APIs
 *   - responsive sidebar toggle
 *   - show/hide password buttons
 *   - a reusable confirmation dialog
 *
 * Page-specific behaviour (search, filters, modals for a particular
 * page) lives in a <script> block at the bottom of that page instead
 * of in here, so each page stays easy to read on its own.
 */

/* ---------------------------------------------------------------------
   Toast notifications
   --------------------------------------------------------------------- */
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3200);
}

/* ---------------------------------------------------------------------
   apiFetch() - talks to our PHP JSON APIs and always resolves to the
   { success, message, data } shape our backend returns.
   --------------------------------------------------------------------- */
async function apiFetch(url, options = {}) {
    const defaultHeaders = { 'Content-Type': 'application/json' };

    try {
        const response = await fetch(url, {
            ...options,
            headers: { ...defaultHeaders, ...(options.headers || {}) },
        });

        let payload;
        try {
            payload = await response.json();
        } catch (parseErr) {
            return { success: false, message: 'Unexpected server response.', data: [] };
        }

        return payload;
    } catch (networkErr) {
        return { success: false, message: 'Network error. Please check your connection and try again.', data: [] };
    }
}

/* ---------------------------------------------------------------------
   Password show/hide toggle
   Usage: <button type="button" class="toggle-password" data-target="password">Show</button>
   --------------------------------------------------------------------- */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.toggle-password');
    if (!btn) return;

    const input = document.getElementById(btn.dataset.target);
    if (!input) return;

    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? 'Hide' : 'Show';
});

/* ---------------------------------------------------------------------
   Responsive sidebar toggle (mobile / tablet)
   --------------------------------------------------------------------- */
function initSidebarToggle() {
    const toggleBtn = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!toggleBtn || !sidebar) return;

    const closeSidebar = () => {
        sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
    };

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        if (backdrop) backdrop.classList.toggle('show');
    });

    if (backdrop) backdrop.addEventListener('click', closeSidebar);
}

document.addEventListener('DOMContentLoaded', initSidebarToggle);

/* ---------------------------------------------------------------------
   Reusable confirmation dialog (replaces native confirm() with something
   that matches the glassmorphism look). Returns a Promise<boolean>.
   Usage: const ok = await confirmAction('Delete this record?');
   --------------------------------------------------------------------- */
function confirmAction(message, confirmLabel = 'Confirm') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay show';
        overlay.innerHTML = `
            <div class="glass-card modal-box" style="max-width:380px;">
                <p style="margin:0 0 20px;font-size:14px;">${message}</p>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>
                    <button type="button" class="btn btn-danger" data-action="confirm">${confirmLabel}</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.addEventListener('click', (e) => {
            const action = e.target.dataset ? e.target.dataset.action : null;
            if (e.target === overlay || action === 'cancel') {
                overlay.remove();
                resolve(false);
            } else if (action === 'confirm') {
                overlay.remove();
                resolve(true);
            }
        });
    });
}

/* ---------------------------------------------------------------------
   Generic modal open/close helpers
   Usage: openModal('addStudentModal'); closeModal('addStudentModal');
   --------------------------------------------------------------------- */
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('show');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('show');
}

// Close any modal when clicking its overlay background
document.addEventListener('click', (e) => {
    if (e.target.classList && e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
    }
});
