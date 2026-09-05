const eventForm = document.querySelector('.event-form');

document.addEventListener('click', (event) => {
    const clickedMenu = event.target.closest('.record-menu');
    const clickedSummary = event.target.closest('.record-menu > summary');
    document.querySelectorAll('.record-menu[open]').forEach((menu) => {
        if (menu !== clickedMenu || !clickedSummary) menu.removeAttribute('open');
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const menu = document.querySelector('.record-menu[open]');
    if (!menu) return;
    menu.removeAttribute('open');
    menu.querySelector('summary')?.focus();
});

function clearCompletedMessage() {
    document.querySelectorAll('[data-success-message]').forEach((message) => message.remove());
}

document.querySelectorAll('.edit-event').forEach((button) => {
    button.addEventListener('click', () => {
        if (!eventForm) return;
        ['id', 'title', 'date', 'time', 'location', 'description', 'link'].forEach((name) => {
            const field = eventForm.elements.namedItem(name);
            if (field) field.value = button.dataset[name] || '';
        });
        eventForm.querySelector('button[type="submit"]').textContent = 'Änderungen speichern';
        eventForm.scrollIntoView({behavior: 'smooth'});
    });
});

document.querySelectorAll('.editor-edit').forEach((button) => {
    button.addEventListener('click', () => {
        clearCompletedMessage();
        const form = document.querySelector('[data-editor-form]');
        if (!form) return;
        form.hidden = false;
        Object.entries(button.dataset).forEach(([name, value]) => {
            const fieldName = name.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
            const checkboxGroup = form.querySelectorAll(`[name="${fieldName}[]"]`);
            if (checkboxGroup.length) {
                const selected = value.split(',').filter(Boolean);
                checkboxGroup.forEach((field) => { field.checked = selected.includes(field.value); });
                return;
            }
            const field = form.elements.namedItem(fieldName);
            if (!field) return;
            if (field.type === 'checkbox') field.checked = value === '1';
            else field.value = value;
        });
        if (form.dataset.editorForm === 'users') {
            const contact = document.querySelector(`[data-user-contact-map] [data-user-id="${CSS.escape(button.dataset.id)}"]`);
            const checkbox = form.elements.namedItem('contact_person');
            const field = form.elements.namedItem('contact_function');
            if (checkbox) checkbox.checked = contact?.dataset.contactPerson === '1';
            if (field) field.value = contact?.dataset.contactFunction || '';
        }
        if (form.dataset.editorForm === 'documents') {
            const upload = form.querySelector('.document-upload');
            if (upload) upload.hidden = true;
        }
        const submit = form.querySelector('button[type="submit"]');
        if (submit) submit.textContent = 'Änderungen speichern';
        const cancel = form.querySelector('.editor-cancel');
        if (cancel) cancel.hidden = false;
        const title = form.querySelector('.editor-form-title');
        if (title) title.textContent = button.dataset.editTitle || 'Eintrag bearbeiten';
        hydrateRichEditor(form, button.dataset.imagePath || '');
        hydrateGalleryEditor(form, button.dataset.gallery || '[]');
        form.scrollIntoView({behavior: 'smooth'});
    });
});

document.querySelectorAll('.editor-create').forEach((button) => {
    button.addEventListener('click', () => {
        clearCompletedMessage();
        const form = document.querySelector('[data-editor-form]');
        if (!form) return;
        form.reset();
        Object.entries(button.dataset).forEach(([name, value]) => {
            if (['createTitle', 'submitLabel'].includes(name)) return;
            const fieldName = name.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
            const field = form.elements.namedItem(fieldName);
            if (field) field.value = value;
        });
        const id = form.elements.namedItem('id');
        if (id) id.value = '';
        if (form.dataset.editorForm === 'documents') {
            const upload = form.querySelector('.document-upload');
            if (upload) upload.hidden = false;
        }
        form.hidden = false;
        const title = form.querySelector('.editor-form-title');
        if (title) title.textContent = button.dataset.createTitle || 'Neuen Eintrag anlegen';
        const submit = form.querySelector('button[type="submit"]');
        if (submit) submit.textContent = button.dataset.submitLabel || 'Speichern';
        const cancel = form.querySelector('.editor-cancel');
        if (cancel) cancel.hidden = false;
        hydrateRichEditor(form, '');
        hydrateGalleryEditor(form, '[]');
        form.scrollIntoView({behavior: 'smooth'});
    });
});

function hydrateRichEditor(form, imagePath = '') {
    const field = form.elements.namedItem('content');
    const editor = form.querySelector('[data-rich-editor]');
    const attachedManager = form.querySelector('[data-gallery-manager]');
    if (attachedManager && editor?.contains(attachedManager)) { attachedManager.hidden = true; form.append(attachedManager); }
    if (field && editor) {
        const htmlMode = form.elements.namedItem('content_is_html')?.value === '1';
        const content = htmlMode ? field.value : escapeHtml(field.value).replace(/\n/g, '<br>');
        editor.innerHTML = content.replaceAll('[[NEWS_GALLERY]]', galleryMarkerHtml());
    }
    const preview = form.querySelector('[data-image-preview]');
    if (preview) {
        const image = preview.querySelector('img');
        const empty = preview.querySelector('[data-image-empty]');
        if (imagePath) { image.src = imagePath; image.hidden = false; if(empty)empty.hidden = true; }
        else if(empty) { image.removeAttribute('src'); image.hidden = true; empty.hidden = false; }
        else { image.src='/media/trainer-placeholder.svg'; image.hidden=false; }
    }
}

function escapeHtml(value) {
    const node = document.createElement('div');
    node.textContent = value;
    return node.innerHTML;
}

function hydrateGalleryEditor(form, encodedGallery) {
    const container = form.querySelector('[data-gallery-editor]');
    if (!container) return;
    let gallery = [];
    try { gallery = JSON.parse(encodedGallery); } catch (_) { gallery = []; }
    container.innerHTML = '';
    gallery.forEach((item) => {
        const row = document.createElement('article');
        row.className = 'gallery-editor-item'; row.draggable = true; row.dataset.galleryItem = `id:${item.id}`;
        row.innerHTML = `<img src="${escapeHtml(item.path)}" alt=""><div class="gallery-image-toolbar"><span class="gallery-drag-handle" title="Zum Verschieben ziehen">⠿ Verschieben</span><button type="button" data-gallery-details>✎ Beschreiben</button><label>↻ Austauschen<input class="visually-hidden" type="file" name="gallery_replace[${item.id}]" accept="image/jpeg,image/png,image/webp"></label><button type="button" class="danger" data-gallery-delete>× Entfernen</button></div><div class="gallery-card-fields" data-gallery-detail-panel hidden><label>Bildbeschreibung <span class="optional">(optional)</span><input name="gallery_caption[${item.id}]" maxlength="180" value="${escapeHtml(item.caption || '')}" placeholder="Was ist auf dem Bild zu sehen?"></label></div><input type="checkbox" class="visually-hidden" name="delete_gallery[]" value="${item.id}">`;
        container.append(row);
    });
    if (!gallery.length) container.innerHTML = '<p class="gallery-editor-empty">Noch keine Galeriebilder hinterlegt.</p>';
    const manager = form.querySelector('[data-gallery-manager]');
    manager.hidden = gallery.length === 0 && !form.querySelector('[data-gallery-marker]');
    if (gallery.length && !form.querySelector('[data-gallery-marker]')) form.querySelector('[data-rich-editor]').insertAdjacentHTML('beforeend', galleryMarkerHtml());
    attachGalleryManager(form);
    updateGallery(form);
}

function galleryMarkerHtml() {
    return '<div class="editor-gallery-block" data-gallery-marker contenteditable="false"><header><span><strong>Bildergalerie</strong><small data-gallery-count>Position im Beitrag</small></span><button type="button" data-gallery-move>Galerie verschieben</button></header><div data-gallery-slot></div></div><p><br></p>';
}

function attachGalleryManager(form) {
    const marker = form.querySelector('[data-gallery-marker]'); const manager = form.querySelector('[data-gallery-manager]'); const slot = marker?.querySelector('[data-gallery-slot]');
    if (slot && manager) { manager.hidden = false; slot.append(manager); }
}

function updateGallery(form) {
    const items = [...form.querySelectorAll('[data-gallery-item]')];
    const active = items.filter((item) => !item.classList.contains('pending-delete'));
    const orderField = form.elements.namedItem('gallery_order'); if (orderField) orderField.value = active.map((item) => item.dataset.galleryItem).join(',');
    form.querySelectorAll('[data-gallery-count]').forEach((node) => { node.textContent = `${active.length} ${active.length === 1 ? 'Bild' : 'Bilder'} · hier im Beitrag`; });
    const empty = form.querySelector('.gallery-editor-empty');
    if (empty) empty.hidden = active.length > 0;
}

document.querySelectorAll('[data-rich-editor]').forEach((editor) => {
    const form = editor.closest('form');
    const field = form.elements.namedItem('content');
    field.required = false;
    editor.addEventListener('input', () => { field.value = editor.innerHTML; });
    editor.addEventListener('click', (event) => { if (event.target.closest('a')) event.preventDefault(); });
    form.addEventListener('submit', () => {
        const copy = editor.cloneNode(true);
        copy.querySelectorAll('[data-gallery-marker]').forEach((marker) => marker.replaceWith(document.createTextNode('[[NEWS_GALLERY]]')));
        form.elements.namedItem('content').value = copy.innerHTML;
        form.elements.namedItem('content_is_html').value = '1';
        updateGallery(form);
    });
});

document.addEventListener('click', (event) => {
    const insert = event.target.closest('[data-gallery-insert]');
    if (insert) {
        const form = insert.closest('form'); const editor = form.querySelector('[data-rich-editor]');
        editor.querySelector('[data-gallery-marker]')?.remove(); editor.focus();
        document.execCommand('insertHTML', false, galleryMarkerHtml());
        attachGalleryManager(form);
        updateGallery(form); return;
    }
    const details = event.target.closest('[data-gallery-details]');
    if (details) { const panel = details.closest('[data-gallery-item]').querySelector('[data-gallery-detail-panel]'); panel.hidden = !panel.hidden; details.textContent = panel.hidden ? '✎ Beschreiben' : 'Beschreibung schließen'; return; }
    const move = event.target.closest('[data-gallery-move]');
    if (move) { const form = move.closest('form'); const manager = form.querySelector('[data-gallery-manager]'); manager.hidden = true; form.append(manager); move.closest('[data-gallery-marker]').remove(); form.querySelector('[data-gallery-insert]').focus(); return; }
    const remove = event.target.closest('[data-gallery-delete]');
    if (remove) {
        const item = remove.closest('[data-gallery-item]'); const checkbox = item.querySelector('[name="delete_gallery[]"]');
        item.classList.toggle('pending-delete'); checkbox.checked = item.classList.contains('pending-delete');
        remove.textContent = checkbox.checked ? 'Entfernen rückgängig' : 'Bild entfernen'; updateGallery(remove.closest('form'));
    }
});

document.querySelectorAll('[data-editor-command]').forEach((button) => {
    button.addEventListener('click', async () => {
        const form = button.closest('form');
        const editor = form.querySelector('[data-rich-editor]');
        editor.focus();
        let value = button.dataset.value || null;
        if (button.dataset.editorCommand === 'createLink') {
            value = await window.KutaDialog.prompt({
                title: 'Link einfügen',
                message: 'Geben Sie die vollständige Internetadresse ein.',
                label: 'Internetadresse',
                value: 'https://',
                confirmLabel: 'Link einfügen'
            });
            editor.focus();
        }
        if (value !== null || button.dataset.editorCommand !== 'createLink') document.execCommand(button.dataset.editorCommand, false, value);
        setTimeout(()=>updateToolbarState(editor),0);
    });
});

function updateToolbarState(editor) {
    const form=editor.closest('form');
    form.querySelectorAll('[data-editor-command]').forEach((button)=>{
        const command=button.dataset.editorCommand; let active=false;
        if(command==='bold'||command==='italic'||command==='insertUnorderedList') active=document.queryCommandState(command);
        if(command==='formatBlock') active=document.queryCommandValue('formatBlock').toLowerCase()===button.dataset.value;
        button.classList.toggle('active',active);button.setAttribute('aria-pressed',String(active));
    });
}
document.addEventListener('selectionchange',()=>{const anchor=getSelection()?.anchorNode;if(!anchor)return;document.querySelectorAll('[data-rich-editor]').forEach((editor)=>{if(editor.contains(anchor))updateToolbarState(editor);});});
document.querySelectorAll('[data-rich-editor]').forEach((editor)=>{editor.addEventListener('keyup',()=>updateToolbarState(editor));editor.addEventListener('mouseup',()=>updateToolbarState(editor));});

document.addEventListener('change', (event) => {
    const input = event.target.closest('input[type="file"][name="image"], input[type="file"][name="trainer_image"]');
    if (!input) return;
    const file = input.files?.[0];
    const form = input.closest('form');
    const preview = form?.querySelector('[data-image-preview]');
    const status = input.closest('[data-file-drop]')?.querySelector('[data-upload-status]');
    if (!file || !preview) return;
    const image = preview.querySelector('img');
    const empty = preview.querySelector('[data-image-empty]');
    const reader = new FileReader();
    reader.addEventListener('load', () => {
        image.src = String(reader.result);
        image.hidden = false;
        if (empty) empty.hidden = true;
        if (status) status.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(1)} MB ausgewählt`;
    });
    reader.readAsDataURL(file);
});

document.querySelectorAll('input[type="file"][name="gallery_images[]"]').forEach((input) => {
    input.addEventListener('change', () => {
        const container = input.closest('form').querySelector('[data-gallery-editor]');
        container.querySelector('.gallery-editor-empty')?.remove();
        container.querySelectorAll('.gallery-new-item').forEach((item) => item.remove());
        Array.from(input.files || []).forEach((file, index) => {
            const item = document.createElement('article');
            item.className = 'gallery-editor-item gallery-new-item'; item.draggable = true; item.dataset.galleryItem = `new:${index}`;
            item.innerHTML = `<img src="${URL.createObjectURL(file)}" alt=""><div class="gallery-image-toolbar"><span class="gallery-drag-handle">⠿ Verschieben</span><button type="button" class="danger" data-gallery-remove-new>× Entfernen</button></div><input type="checkbox" class="visually-hidden" name="skip_gallery_new[]" value="${index}">`;
            container.append(item);
        });
        const form = input.closest('form');
        if (!form.querySelector('[data-gallery-marker]')) form.querySelector('[data-gallery-insert]').click();
        updateGallery(form);
    });
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-gallery-remove-new]');
    if (!button) return;
    const item = button.closest('[data-gallery-item]'); item.classList.add('pending-delete'); item.querySelector('[name="skip_gallery_new[]"]').checked = true; item.hidden = true; updateGallery(button.closest('form'));
});

document.querySelectorAll('[data-gallery-editor]').forEach((list) => {
    let dragged = null;
    list.addEventListener('dragstart', (event) => { dragged = event.target.closest('[data-gallery-item]'); if (dragged) dragged.classList.add('dragging'); });
    list.addEventListener('dragover', (event) => { event.preventDefault(); const target = event.target.closest('[data-gallery-item]'); if (dragged && target && target !== dragged) { const box = target.getBoundingClientRect(); const after = event.clientY > box.top + box.height / 2 || (Math.abs(event.clientY - (box.top + box.height / 2)) < box.height / 3 && event.clientX > box.left + box.width / 2); list.insertBefore(dragged, after ? target.nextSibling : target); } });
    list.addEventListener('dragend', () => { dragged?.classList.remove('dragging'); updateGallery(list.closest('form')); dragged = null; });
});

document.querySelectorAll('[data-file-drop]').forEach((zone) => {
    ['dragenter','dragover'].forEach((type) => zone.addEventListener(type, (event) => { event.preventDefault(); zone.classList.add('is-dragging'); }));
    ['dragleave','drop'].forEach((type) => zone.addEventListener(type, (event) => { event.preventDefault(); zone.classList.remove('is-dragging'); if (type === 'drop' && event.dataTransfer?.files.length) { const input = zone.querySelector('input[type=file]'); input.files = event.dataTransfer.files; input.dispatchEvent(new Event('change')); } }));
});

document.querySelectorAll('.editor-cancel').forEach((button) => {
    button.addEventListener('click', () => window.location.reload());
});

document.querySelectorAll('[data-list-search]').forEach((input) => {
    input.addEventListener('input', () => {
        const query = input.value.trim().toLocaleLowerCase('de');
        document.querySelectorAll(input.dataset.listSearch).forEach((item) => {
            item.hidden = query !== '' && !item.textContent.toLocaleLowerCase('de').includes(query);
        });
    });
});
