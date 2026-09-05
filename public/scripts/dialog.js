(() => {
    const backdrop = document.querySelector('[data-app-dialog-backdrop]');
    if (!backdrop) return;
    const dialog = backdrop.querySelector('.app-dialog');
    const title = backdrop.querySelector('[data-app-dialog-title]');
    const message = backdrop.querySelector('[data-app-dialog-message]');
    const field = backdrop.querySelector('[data-app-dialog-field]');
    const label = backdrop.querySelector('[data-app-dialog-label]');
    const input = backdrop.querySelector('[data-app-dialog-input]');
    const confirmButton = backdrop.querySelector('[data-app-dialog-confirm]');
    const cancelButtons = [...backdrop.querySelectorAll('[data-app-dialog-cancel]')];
    const approvedForms = new WeakSet();
    let finish = null;
    let previousFocus = null;
    let mode = 'confirm';
    const focusable = () => [...dialog.querySelectorAll('button:not([hidden]), input:not([hidden])')].filter((element) => !element.disabled && !element.closest('[hidden]'));
    const close = (result) => {
        if (!finish) return;
        const resolve = finish; finish = null; backdrop.hidden = true;
        document.body.classList.remove('app-dialog-open'); previousFocus?.focus(); resolve(result);
    };
    const open = (options = {}, requestedMode = 'confirm') => new Promise((resolve) => {
        if (finish) close(mode === 'prompt' ? null : false);
        mode = requestedMode; finish = resolve; previousFocus = document.activeElement;
        const tone = options.tone || (requestedMode === 'confirm' ? 'danger' : 'default');
        dialog.dataset.tone = tone; confirmButton.dataset.tone = tone;
        title.textContent = options.title || (requestedMode === 'prompt' ? 'Eingabe erforderlich' : 'Aktion bestätigen');
        message.textContent = options.message || ''; message.hidden = !options.message;
        confirmButton.textContent = options.confirmLabel || (requestedMode === 'prompt' ? 'Übernehmen' : 'Bestätigen');
        field.hidden = requestedMode !== 'prompt';
        if (requestedMode === 'prompt') { label.textContent = options.label || 'Eingabe'; input.value = options.value || ''; input.placeholder = options.placeholder || ''; }
        backdrop.hidden = false; document.body.classList.add('app-dialog-open');
        requestAnimationFrame(() => requestedMode === 'prompt' ? input.focus() : cancelButtons[1].focus());
    });
    confirmButton.addEventListener('click', () => close(mode === 'prompt' ? input.value : true));
    cancelButtons.forEach((button) => button.addEventListener('click', () => close(mode === 'prompt' ? null : false)));
    input.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); confirmButton.click(); } });
    backdrop.addEventListener('click', (event) => { if (event.target === backdrop) close(mode === 'prompt' ? null : false); });
    document.addEventListener('keydown', (event) => {
        if (backdrop.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); close(mode === 'prompt' ? null : false); return; }
        if (event.key !== 'Tab') return;
        const items = focusable(); const first = items[0]; const last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    window.KutaDialog = { confirm: (options) => open(options, 'confirm'), prompt: (options) => open(options, 'prompt') };
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-confirm]');
        if (!form || approvedForms.has(form)) { if (form) approvedForms.delete(form); return; }
        event.preventDefault();
        const accepted = await window.KutaDialog.confirm({title: form.dataset.confirmTitle, message: form.dataset.confirm, confirmLabel: form.dataset.confirmAction, tone: form.dataset.confirmTone || 'danger'});
        if (!accepted) return;
        approvedForms.add(form); form.requestSubmit(event.submitter || undefined);
    });
})();
