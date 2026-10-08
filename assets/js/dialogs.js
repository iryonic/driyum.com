/**
 * Driyum Executive Custom Dialog System
 * Replaces window.alert and window.confirm with luxury, brand-consistent modals.
 */
(function (global) {
    if (global.__DRIYUM_DIALOGS_READY__) return;
    global.__DRIYUM_DIALOGS_READY__ = true;

    const STYLES_ID = 'driyum-custom-dialog-styles';
    function ensureStyles() {
        if (document.getElementById(STYLES_ID)) return;
        const style = document.createElement('style');
        style.id = STYLES_ID;
        style.textContent = `
            @keyframes driyumDlgBackdropIn {
                from { opacity: 0; backdrop-filter: blur(0px); -webkit-backdrop-filter: blur(0px); }
                to { opacity: 1; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
            }
            @keyframes driyumDlgBackdropOut {
                from { opacity: 1; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
                to { opacity: 0; backdrop-filter: blur(0px); -webkit-backdrop-filter: blur(0px); }
            }
            @keyframes driyumDlgCardIn {
                0% { opacity: 0; transform: scale(0.88) translateY(18px); }
                65% { transform: scale(1.02) translateY(-2px); }
                100% { opacity: 1; transform: scale(1) translateY(0); }
            }
            @keyframes driyumDlgCardOut {
                0% { opacity: 1; transform: scale(1) translateY(0); }
                100% { opacity: 0; transform: scale(0.92) translateY(12px); }
            }
            .driyum-dialog-backdrop {
                position: fixed;
                inset: 0;
                z-index: 9999999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.25rem;
                background-color: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                animation: driyumDlgBackdropIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                font-family: 'Figtree', 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .driyum-dialog-backdrop.closing {
                animation: driyumDlgBackdropOut 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .driyum-dialog-card {
                position: relative;
                width: 100%;
                max-width: 440px;
                background: #ffffff;
                border-radius: 28px;
                padding: 34px 28px 28px 28px;
                text-align: center;
                box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(15, 23, 42, 0.06);
                border: 1px solid rgba(226, 232, 240, 0.9);
                animation: driyumDlgCardIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                box-sizing: border-box;
            }
            .driyum-dialog-backdrop.closing .driyum-dialog-card {
                animation: driyumDlgCardOut 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .driyum-dialog-close {
                position: absolute;
                top: 18px;
                right: 18px;
                width: 34px;
                height: 34px;
                border-radius: 50%;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                color: #64748b;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                outline: none;
                padding: 0;
            }
            .driyum-dialog-close:hover {
                background: #f1f5f9;
                color: #0f172a;
                transform: rotate(90deg);
            }
            .driyum-dialog-icon-wrapper {
                display: flex;
                justify-content: center;
                margin-bottom: 20px;
            }
            .driyum-dialog-icon-badge {
                width: 68px;
                height: 68px;
                border-radius: 22px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .driyum-dialog-card:hover .driyum-dialog-icon-badge {
                transform: scale(1.06);
            }
            .driyum-dialog-danger .driyum-dialog-icon-badge {
                background: #fff1f2;
                color: #e11d48;
                border: 1px solid #ffe4e6;
                box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.25), 0 0 0 8px rgba(254, 226, 226, 0.55);
            }
            .driyum-dialog-warning .driyum-dialog-icon-badge {
                background: #fffbeb;
                color: #d97706;
                border: 1px solid #fef3c7;
                box-shadow: 0 10px 25px -5px rgba(217, 119, 6, 0.25), 0 0 0 8px rgba(254, 243, 199, 0.55);
            }
            .driyum-dialog-success .driyum-dialog-icon-badge {
                background: #ecfdf5;
                color: #24B25D;
                border: 1px solid #d1fae5;
                box-shadow: 0 10px 25px -5px rgba(36, 178, 93, 0.25), 0 0 0 8px rgba(209, 250, 229, 0.55);
            }
            .driyum-dialog-info .driyum-dialog-icon-badge {
                background: #f0fdf4;
                color: #15803d;
                border: 1px solid #bbf7d0;
                box-shadow: 0 10px 25px -5px rgba(36, 178, 93, 0.2), 0 0 0 8px rgba(240, 253, 244, 0.7);
            }
            .driyum-dialog-title {
                font-size: 21px;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 10px 0;
                letter-spacing: -0.02em;
                line-height: 1.3;
            }
            .driyum-dialog-message {
                font-size: 14.5px;
                line-height: 1.6;
                color: #475569;
                margin: 0;
                font-weight: 500;
                white-space: pre-line;
                word-break: break-word;
                max-height: 50vh;
                overflow-y: auto;
            }
            .driyum-dialog-actions {
                display: flex;
                gap: 12px;
                margin-top: 28px;
                width: 100%;
            }
            .driyum-dialog-btn {
                position: relative;
                outline: none;
                cursor: pointer;
                border-radius: 16px;
                padding: 14px 20px;
                font-size: 13px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                box-sizing: border-box;
                font-family: inherit;
            }
            .driyum-dialog-btn:active {
                transform: scale(0.97);
            }
            .driyum-dialog-btn-cancel {
                flex: 1;
                background: #f1f5f9;
                color: #475569;
                border: 1px solid #e2e8f0;
            }
            .driyum-dialog-btn-cancel:hover {
                background: #e2e8f0;
                color: #0f172a;
            }
            .driyum-dialog-btn-confirm {
                flex: 1.25;
                border: none;
            }
            .driyum-dialog-danger .driyum-dialog-btn-confirm {
                background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
                color: #ffffff;
                box-shadow: 0 8px 22px -4px rgba(225, 29, 72, 0.4);
            }
            .driyum-dialog-danger .driyum-dialog-btn-confirm:hover {
                box-shadow: 0 12px 28px -4px rgba(225, 29, 72, 0.5);
                filter: brightness(1.08);
                transform: translateY(-1px);
            }
            .driyum-dialog-warning .driyum-dialog-btn-confirm {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                color: #ffffff;
                box-shadow: 0 8px 22px -4px rgba(217, 119, 6, 0.35);
            }
            .driyum-dialog-warning .driyum-dialog-btn-confirm:hover {
                box-shadow: 0 12px 28px -4px rgba(217, 119, 6, 0.45);
                filter: brightness(1.06);
                transform: translateY(-1px);
            }
            .driyum-dialog-success .driyum-dialog-btn-confirm {
                background: linear-gradient(135deg, #24B25D 0%, #1ea153 100%);
                color: #000000;
                font-weight: 900;
                box-shadow: 0 8px 22px -4px rgba(36, 178, 93, 0.35);
            }
            .driyum-dialog-success .driyum-dialog-btn-confirm:hover {
                box-shadow: 0 12px 28px -4px rgba(36, 178, 93, 0.45);
                filter: brightness(1.06);
                transform: translateY(-1px);
            }
            .driyum-dialog-info .driyum-dialog-btn-confirm {
                background: #0f172a;
                color: #ffffff;
                box-shadow: 0 8px 22px -4px rgba(15, 23, 42, 0.25);
            }
            .driyum-dialog-info .driyum-dialog-btn-confirm:hover {
                background: #1e293b;
                transform: translateY(-1px);
            }
            .driyum-dialog-alert .driyum-dialog-btn-confirm {
                flex: 1;
                width: 100%;
            }
        `;
        (document.head || document.documentElement).appendChild(style);
    }

    const ICONS = {
        danger: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>`,
        warning: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`,
        success: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`,
        info: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`
    };

    function escapeHtml(str) {
        if (typeof str !== 'string') return String(str || '');
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    let activeDialog = null;

    function renderDialog(config) {
        ensureStyles();
        return new Promise((resolve) => {
            if (activeDialog) {
                activeDialog.close(false);
            }

            const isConfirm = config.mode === 'confirm';
            const type = config.type || (isConfirm ? 'warning' : 'info');
            const iconSvg = ICONS[type] || ICONS.info;

            const backdrop = document.createElement('div');
            backdrop.className = `driyum-dialog-backdrop driyum-dialog-${type} ${isConfirm ? 'driyum-dialog-confirm' : 'driyum-dialog-alert'}`;
            backdrop.setAttribute('role', 'dialog');
            backdrop.setAttribute('aria-modal', 'true');

            backdrop.innerHTML = `
                <div class="driyum-dialog-card" id="driyum-active-card">
                    <button type="button" class="driyum-dialog-close" id="driyum-dlg-close" aria-label="Close">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1L13 13M1 13L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                    <div class="driyum-dialog-icon-wrapper">
                        <div class="driyum-dialog-icon-badge">
                            ${iconSvg}
                        </div>
                    </div>
                    <h3 class="driyum-dialog-title">${escapeHtml(config.title)}</h3>
                    <div class="driyum-dialog-message">${config.html ? config.message : escapeHtml(config.message)}</div>
                    <div class="driyum-dialog-actions">
                        ${isConfirm ? `<button type="button" class="driyum-dialog-btn driyum-dialog-btn-cancel" id="driyum-dlg-cancel">${escapeHtml(config.cancelText || 'Cancel')}</button>` : ''}
                        <button type="button" class="driyum-dialog-btn driyum-dialog-btn-confirm" id="driyum-dlg-confirm">${escapeHtml(config.confirmText || (isConfirm ? (type === 'danger' ? 'Delete' : 'Confirm') : 'OK'))}</button>
                    </div>
                </div>
            `;

            (document.body || document.documentElement).appendChild(backdrop);

            const confirmBtn = backdrop.querySelector('#driyum-dlg-confirm');
            const cancelBtn = backdrop.querySelector('#driyum-dlg-cancel');
            const closeBtn = backdrop.querySelector('#driyum-dlg-close');

            setTimeout(() => {
                if (confirmBtn) confirmBtn.focus();
            }, 50);

            let closed = false;
            function close(result) {
                if (closed) return;
                closed = true;
                document.removeEventListener('keydown', handleKey);
                backdrop.classList.add('closing');
                setTimeout(() => {
                    if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                    if (activeDialog && activeDialog.element === backdrop) {
                        activeDialog = null;
                    }
                    resolve(result);
                }, 180);
            }

            function handleKey(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    close(isConfirm ? false : true);
                } else if (e.key === 'Enter') {
                    if (document.activeElement !== cancelBtn) {
                        e.preventDefault();
                        close(true);
                    }
                }
            }
            document.addEventListener('keydown', handleKey);

            confirmBtn.addEventListener('click', () => close(true));
            if (cancelBtn) cancelBtn.addEventListener('click', () => close(false));
            if (closeBtn) closeBtn.addEventListener('click', () => close(isConfirm ? false : true));

            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    close(isConfirm ? false : true);
                }
            });

            activeDialog = { element: backdrop, close };
        });
    }

    function autoDetectType(message, isConfirm) {
        const text = String(message || '').toLowerCase();
        if (/delete|erase|remove|nuke|danger|destroy|forever|trash/.test(text)) {
            return 'danger';
        }
        if (/warning|caution|alert|risk|irreversible/.test(text)) {
            return 'warning';
        }
        if (/success|congratulat|saved|updated|done/.test(text)) {
            return 'success';
        }
        if (/error|failed|failure|invalid|wrong|cannot/.test(text)) {
            return 'danger';
        }
        return isConfirm ? 'warning' : 'info';
    }

    function autoDetectTitle(type, isConfirm) {
        if (isConfirm) {
            if (type === 'danger') return 'Confirm Deletion';
            if (type === 'warning') return 'Are you sure?';
            if (type === 'success') return 'Confirm Action';
            return 'Please Confirm';
        } else {
            if (type === 'danger') return 'Attention';
            if (type === 'warning') return 'Notice';
            if (type === 'success') return 'Success';
            return 'Notice';
        }
    }

    // --- GLOBAL API EXPORTS ---
    global.showConfirm = function (message, options = {}) {
        if (typeof options === 'string') options = { title: options };
        const type = options.type || autoDetectType(message, true);
        const title = options.title || autoDetectTitle(type, true);
        const defaultConfirmText = type === 'danger' ? 'Confirm' : 'Confirm';
        return renderDialog({
            mode: 'confirm',
            message: String(message || ''),
            title: title,
            type: type,
            confirmText: options.confirmText || defaultConfirmText,
            cancelText: options.cancelText || 'Cancel',
            html: !!options.html
        });
    };
    global.customConfirm = global.showConfirm;

    global.showAlert = function (message, options = {}) {
        if (typeof options === 'string') options = { title: options };
        const type = options.type || autoDetectType(message, false);
        const title = options.title || autoDetectTitle(type, false);
        return renderDialog({
            mode: 'alert',
            message: String(message || ''),
            title: title,
            type: type,
            confirmText: options.confirmText || 'OK',
            html: !!options.html
        });
    };
    global.customAlert = global.showAlert;

    // Override native alert & confirm
    let _bypassConfirmValue = null;

    global.confirm = function (message) {
        if (_bypassConfirmValue !== null) {
            return _bypassConfirmValue;
        }
        return global.showConfirm(message);
    };

    global.alert = function (message) {
        return global.showAlert(message);
    };

    // --- INLINE HANDLER INTERCEPTORS ---
    document.addEventListener('click', async function (e) {
        const el = e.target.closest('a, button, input[type="submit"], [onclick*="confirm("], [data-confirm]');
        if (!el) return;

        if (el._dialogConfirmed) {
            delete el._dialogConfirmed;
            return;
        }

        const onclickAttr = el.getAttribute('onclick') || '';
        const dataConfirm = el.getAttribute('data-confirm');

        let message = null;
        if (dataConfirm) {
            message = dataConfirm;
        } else if (onclickAttr && /confirm\s*\(/.test(onclickAttr)) {
            const match = onclickAttr.match(/confirm\s*\(\s*(['"`])(.*?)\1\s*\)/s);
            if (match && match[2]) {
                message = match[2];
            } else {
                message = 'Are you sure you want to proceed?';
            }
        }

        if (!message) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const isDanger = /delete|erase|remove|nuke|danger|destroy|forever|trash/i.test(message);
        const confirmed = await global.showConfirm(message, {
            type: isDanger ? 'danger' : 'warning',
            confirmText: isDanger ? 'Yes, Delete' : 'Confirm'
        });

        if (confirmed) {
            el._dialogConfirmed = true;
            _bypassConfirmValue = true;

            if (el.tagName === 'A' && el.href && !el.href.startsWith('javascript:')) {
                if (/^\s*return\s+confirm\s*\(.*?\)\s*;?\s*$/.test(onclickAttr) || !onclickAttr) {
                    window.location.href = el.href;
                    _bypassConfirmValue = null;
                    return;
                }
            }

            const locMatch = onclickAttr.match(/window\.location\s*=\s*(['"`])(.*?)\1/);
            if (locMatch && locMatch[2]) {
                window.location.href = locMatch[2];
                _bypassConfirmValue = null;
                return;
            }

            if ((el.type === 'submit' || el.getAttribute('type') === 'submit') && el.form) {
                const preStatements = onclickAttr.replace(/return\s+confirm\s*\(.*?\)\s*;?/g, '').trim();
                if (preStatements) {
                    try { new Function(preStatements).call(el); } catch (err) { console.error(err); }
                }
                el.form._dialogConfirmed = true;
                if (el.name) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = el.name;
                    hidden.value = el.value || '1';
                    el.form.appendChild(hidden);
                }
                el.form.submit();
                _bypassConfirmValue = null;
                return;
            }

            el.click();
            setTimeout(() => {
                _bypassConfirmValue = null;
                delete el._dialogConfirmed;
            }, 50);
        }
    }, true);

    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!form || !(form instanceof HTMLFormElement)) return;

        if (form._dialogConfirmed) {
            delete form._dialogConfirmed;
            return;
        }

        const onsubmitAttr = form.getAttribute('onsubmit') || '';
        const dataConfirm = form.getAttribute('data-confirm');

        let message = null;
        if (dataConfirm) {
            message = dataConfirm;
        } else if (onsubmitAttr && /confirm\s*\(/.test(onsubmitAttr)) {
            const match = onsubmitAttr.match(/confirm\s*\(\s*(['"`])(.*?)\1\s*\)/s);
            if (match && match[2]) {
                message = match[2];
            } else {
                message = 'Are you sure you want to submit this form?';
            }
        }

        if (!message) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const isDanger = /delete|erase|remove|nuke|danger|destroy|forever|trash/i.test(message);
        const confirmed = await global.showConfirm(message, {
            type: isDanger ? 'danger' : 'warning',
            confirmText: isDanger ? 'Yes, Proceed' : 'Confirm'
        });

        if (confirmed) {
            form._dialogConfirmed = true;
            _bypassConfirmValue = true;
            form.submit();
            setTimeout(() => {
                _bypassConfirmValue = null;
                delete form._dialogConfirmed;
            }, 50);
        }
    }, true);
})(window);
