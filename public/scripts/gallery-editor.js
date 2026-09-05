let draggedGallery = null;
let draggedImage = null;
const galleryStyles = document.createElement('link'); galleryStyles.rel = 'stylesheet'; galleryStyles.href = '/styles/wysiwyg.css?v=20260904-20'; document.head.append(galleryStyles);
const galleryInsertionRanges = new WeakMap();
const inlineImageFiles = new WeakMap();
const captionBackdrop = document.createElement('div');
captionBackdrop.className = 'gallery-caption-backdrop'; captionBackdrop.hidden = true;
document.body.append(captionBackdrop);

function closeCaptionEditor() {
    document.querySelectorAll('.gallery-editor-item.editing-caption').forEach((item) => {
        item.classList.remove('editing-caption');
        item.draggable = true;
        const panel = item.querySelector('[data-gallery-detail-panel]');
        if (panel) panel.hidden = true;
    });
    captionBackdrop.hidden = true;
}

captionBackdrop.addEventListener('click', closeCaptionEditor);

function insertEditorBlock(editor, html) {
    const range=galleryInsertionRanges.get(editor);
    if(range&&editor.contains(range.commonAncestorContainer)){
        editor.focus();
        const selection=getSelection();selection.removeAllRanges();selection.addRange(range);
        document.execCommand('insertHTML',false,html);
        rememberInsertion(editor);
        return;
    }
    editor.insertAdjacentHTML('beforeend',html);
}

function inlineImageHtml(index, file) {
    return `<figure class="inline-image-editor" data-inline-image="${index}" contenteditable="false"><img src="${URL.createObjectURL(file)}" alt="" draggable="false"><span class="inline-image-grip" role="button" tabindex="0" aria-label="Bild verschieben">⠿</span><button type="button" data-inline-image-remove aria-label="Bild entfernen">×</button></figure><p><br></p>`;
}

function addInlineImages(form, selected, before = undefined) {
    const editor=form.querySelector('[data-rich-editor]'),input=form.querySelector('[data-inline-image-input]');
    const files=inlineImageFiles.get(form)||[],start=files.length;
    files.push(...selected);inlineImageFiles.set(form,files);
    const transfer=new DataTransfer();files.forEach((file)=>transfer.items.add(file));input.files=transfer.files;
    selected.forEach((file,offset)=>{
        const html=inlineImageHtml(start+offset,file);
        if(before===null)editor.insertAdjacentHTML('beforeend',html);
        else if(before)before.insertAdjacentHTML('beforebegin',html);
        else insertGalleryBlock(editor,html);
    });
    form.dispatchEvent(new Event('input',{bubbles:true}));
}

function insertGalleryBlock(editor, html) {
    const range=galleryInsertionRanges.get(editor);
    if(range&&editor.contains(range.commonAncestorContainer)){
        let block=range.commonAncestorContainer.nodeType===Node.ELEMENT_NODE?range.commonAncestorContainer:range.commonAncestorContainer.parentElement;
        block=block?.closest('p,h1,h2,h3,h4,h5,h6,blockquote,li,figure');
        if(block&&editor.contains(block)){
            if(block.matches('li'))block=block.closest('ul,ol')||block;
            block.insertAdjacentHTML('afterend',html);
            return;
        }
    }
    editor.insertAdjacentHTML('beforeend',html);
}

function normalizeGalleryBlocks(editor) {
    editor.querySelectorAll('[data-gallery-marker]').forEach((marker)=>{
        let formattingParent=marker.parentElement?.closest('p,h1,h2,h3,h4,h5,h6,blockquote,li,figure');
        if(!formattingParent||!editor.contains(formattingParent))return;
        if(formattingParent.matches('li'))formattingParent=formattingParent.closest('ul,ol')||formattingParent;
        formattingParent.insertAdjacentElement('afterend',marker);
    });
}

function rememberInsertion(editor) {
    const selection=getSelection();
    if(!selection?.rangeCount)return;
    const range=selection.getRangeAt(0);
    if(editor.contains(range.commonAncestorContainer)&&!range.commonAncestorContainer.parentElement?.closest?.('[data-gallery-marker]'))galleryInsertionRanges.set(editor,range.cloneRange());
}

function galleryKey() {
    return `gallery-${crypto.randomUUID().slice(0, 8)}`;
}

function galleryMarkerHtml(key) {
    return `<div class="editor-gallery-block" data-gallery-marker data-gallery-key="${key}" contenteditable="false"><header><span class="gallery-block-grip" role="button" tabindex="0" aria-label="Galerie verschieben">⠿</span><strong>Bildergalerie</strong><small data-gallery-count>0 Bilder</small><button type="button" class="gallery-block-remove" data-gallery-remove aria-label="Galerie entfernen">×</button></header><div data-gallery-slot></div></div><p><br></p>`;
}

function galleryManager(key) {
    const manager = document.createElement('div');
    manager.className = 'gallery-manager'; manager.dataset.galleryManager = key;
    manager.innerHTML = `<input type="hidden" name="gallery_order[${key}]"><div class="gallery-editor-list" data-gallery-editor="${key}"></div><label class="gallery-upload-tile" data-file-drop><span>＋</span><strong>Bilder hinzufügen</strong><small>Anklicken oder ablegen</small><input class="visually-hidden" type="file" name="gallery_images[${key}][]" accept="image/jpeg,image/png,image/webp" multiple></label>`;
    return manager;
}

function existingImage(item) {
    const row = document.createElement('article');
    row.className = 'gallery-editor-item'; row.draggable = true; row.dataset.galleryItem = `id:${item.id}`;
    row.innerHTML = `<img src="${escapeHtml(item.path)}" alt=""><button type="button" class="gallery-image-edit" data-v2-gallery-details>Bild bearbeiten</button><div class="gallery-card-fields" data-gallery-detail-panel hidden><header><div><strong>Bild bearbeiten</strong><span>Beschreibung und Bilddatei verwalten</span></div><button type="button" data-v2-gallery-details aria-label="Dialog schließen">×</button></header><div class="gallery-caption-body"><img src="${escapeHtml(item.path)}" alt=""><label>Bildbeschreibung<textarea name="gallery_caption[${item.id}]" maxlength="180" rows="5" data-auto-grow placeholder="Was ist auf dem Bild zu sehen?">${escapeHtml(item.caption || '')}</textarea><small>Optional · wird unter dem Bild angezeigt</small></label></div><footer><label class="gallery-replace-button">Bild ersetzen<input class="visually-hidden" type="file" name="gallery_replace[${item.id}]" accept="image/jpeg,image/png,image/webp"></label><button type="button" class="gallery-remove-button" data-v2-gallery-delete>Bild entfernen</button></footer></div><input type="hidden" name="gallery_group[${item.id}]" value="${item.galleryKey || 'main'}"><input type="checkbox" class="visually-hidden" name="delete_gallery[]" value="${item.id}">`;
    row.querySelector(':scope > img').draggable = false;
    return row;
}

function ensureGallery(form, key) {
    const marker = form.querySelector(`[data-gallery-marker][data-gallery-key="${key}"]`);
    if (!marker) return null;
    let manager = marker.querySelector(`[data-gallery-manager="${key}"]`);
    if (!manager) { manager = galleryManager(key); marker.querySelector('[data-gallery-slot]').append(manager); }
    return manager;
}

function hydrateRichEditor(form, imagePath = '') {
    const field = form.elements.namedItem('content'); const editor = form.querySelector('[data-rich-editor]');
    if (field && editor) {
        const htmlMode = form.elements.namedItem('content_is_html')?.value === '1';
        let content = htmlMode ? field.value : escapeHtml(field.value).replace(/\n/g, '<br>');
        content = content.replaceAll('[[NEWS_GALLERY]]', '[[NEWS_GALLERY:main]]');
        editor.innerHTML = content.replace(/\[\[NEWS_GALLERY:([a-zA-Z0-9-]+)\]\]/g, (_, key) => galleryMarkerHtml(key));
        normalizeGalleryBlocks(editor);
    }
    const preview = form.querySelector('[data-image-preview]');
    if (preview) { const image = preview.querySelector('img'); const empty = preview.querySelector('[data-image-empty]'); if (imagePath) { image.src=imagePath; image.hidden=false; empty.hidden=true; } else { image.removeAttribute('src'); image.hidden=true; empty.hidden=false; } }
}

function hydrateGalleryEditor(form, encodedGallery) {
    let images=[]; try { images=JSON.parse(encodedGallery); } catch (_) {}
    const keys = new Set([...form.querySelectorAll('[data-gallery-marker]')].map((marker) => marker.dataset.galleryKey));
    images.forEach((image) => keys.add(image.galleryKey || 'main'));
    keys.forEach((key) => {
        if (!form.querySelector(`[data-gallery-marker][data-gallery-key="${key}"]`)) form.querySelector('[data-rich-editor]').insertAdjacentHTML('beforeend', galleryMarkerHtml(key));
        const manager=ensureGallery(form,key); const list=manager.querySelector('[data-gallery-editor]');
        images.filter((image) => (image.galleryKey || 'main') === key).sort((a,b)=>(a.position??0)-(b.position??0)).forEach((image)=>list.append(existingImage(image)));
    });
    updateGalleries(form);
}

function updateGalleries(form) {
    form.querySelectorAll('[data-gallery-marker]').forEach((marker) => {
        const key=marker.dataset.galleryKey; const items=[...marker.querySelectorAll('[data-gallery-item]:not(.pending-delete)')];
        const order=marker.querySelector(`[name="gallery_order[${key}]"]`); if(order) order.value=items.map((item)=>item.dataset.galleryItem).join(',');
        marker.querySelector('[data-gallery-count]').textContent=`${items.length} ${items.length===1?'Bild':'Bilder'}`;
        marker.querySelectorAll('[name^="gallery_group"]').forEach((input)=>{input.value=key;});
    });
}

document.addEventListener('click', async (event) => {
    const emojiToggle=event.target.closest('[data-emoji-toggle]');
    if(emojiToggle){const picker=emojiToggle.parentElement.querySelector('[data-emoji-picker]'),opening=picker.hidden;document.querySelectorAll('[data-emoji-picker]').forEach((item)=>item.hidden=true);picker.hidden=!opening;emojiToggle.setAttribute('aria-expanded',String(opening));return;}
    const emojiButton=event.target.closest('[data-emoji]');
    if(emojiButton){const form=emojiButton.closest('form'),editor=form.querySelector('[data-rich-editor]'),range=galleryInsertionRanges.get(editor);editor.focus();if(range&&editor.contains(range.commonAncestorContainer)){const selection=getSelection();selection.removeAllRanges();selection.addRange(range);}document.execCommand('insertText',false,emojiButton.dataset.emoji);rememberInsertion(editor);editor.dispatchEvent(new Event('input',{bubbles:true}));emojiButton.closest('[data-emoji-picker]').hidden=true;form.querySelector('[data-emoji-toggle]').setAttribute('aria-expanded','false');return;}
    const add=event.target.closest('[data-gallery-add]');
    if(add){const form=add.closest('form'),editor=form.querySelector('[data-rich-editor]'),key=galleryKey();insertGalleryBlock(editor,galleryMarkerHtml(key));ensureGallery(form,key);updateGalleries(form);return;}
    const addImage=event.target.closest('[data-inline-image-add]');if(addImage){const input=addImage.closest('form').querySelector('[data-inline-image-input]');input.value='';input.click();return;}
    const removeInline=event.target.closest('[data-inline-image-remove]');if(removeInline){removeInline.closest('[data-inline-image]').remove();return;}
    const details=event.target.closest('[data-v2-gallery-details]');
    if(details){const item=details.closest('[data-gallery-item]'),panel=item.querySelector('[data-gallery-detail-panel]'),opening=panel.hidden;closeCaptionEditor();if(opening){panel.hidden=false;item.classList.add('editing-caption');item.draggable=false;captionBackdrop.hidden=false;panel.querySelector('textarea').focus();}return;}
    const removeImage=event.target.closest('[data-v2-gallery-delete]');
    if(removeImage){const item=removeImage.closest('[data-gallery-item]'),checkbox=item.querySelector('[name="delete_gallery[]"]');item.classList.toggle('pending-delete');checkbox.checked=item.classList.contains('pending-delete');closeCaptionEditor();updateGalleries(removeImage.closest('form'));return;}
    const removeGallery=event.target.closest('[data-gallery-remove]');
    if(removeGallery){const marker=removeGallery.closest('[data-gallery-marker]'),form=marker.closest('form');if(marker.querySelectorAll('[data-gallery-item]:not(.pending-delete)').length&& !await window.KutaDialog.confirm({title:'Galerie entfernen?',message:'Die Galerie und alle darin enthaltenen Bilder werden aus dem Beitrag entfernt.',confirmLabel:'Galerie entfernen',tone:'danger'}))return;marker.querySelectorAll('[name="delete_gallery[]"]').forEach((input)=>{input.checked=true;form.append(input);});marker.remove();return;}
});

document.addEventListener('change',(event)=>{
    const replacement=event.target.closest('input[type="file"][name^="gallery_replace["]');
    if(replacement){const file=replacement.files?.[0],panel=replacement.closest('[data-gallery-detail-panel]');if(file&&panel)panel.querySelector('.gallery-caption-body>img').src=URL.createObjectURL(file);return;}
    const inlineInput=event.target.closest('[data-inline-image-input]');
    if(inlineInput){addInlineImages(inlineInput.closest('form'),Array.from(inlineInput.files||[]));return;}
    const input=event.target.closest('input[type="file"][name^="gallery_images["]'); if(!input)return;
    const manager=input.closest('[data-gallery-manager]'),key=manager.dataset.galleryManager,list=manager.querySelector('[data-gallery-editor]');
    list.querySelectorAll('.gallery-new-item').forEach((item)=>item.remove());
    Array.from(input.files||[]).forEach((file,index)=>{const item=document.createElement('article');item.className='gallery-editor-item gallery-new-item';item.draggable=true;item.dataset.galleryItem=`new:${index}`;item.innerHTML=`<img src="${URL.createObjectURL(file)}" alt="" draggable="false"><div class="gallery-image-toolbar"><span class="gallery-drag-handle" aria-label="Bild verschieben">⠿</span><button type="button" class="danger" data-v2-gallery-remove-new aria-label="Auswahl entfernen">×</button></div><input type="checkbox" class="visually-hidden" name="skip_gallery_new[${key}][]" value="${index}">`;list.append(item);});updateGalleries(input.closest('form'));
});

document.addEventListener('click',(event)=>{const button=event.target.closest('[data-v2-gallery-remove-new]');if(!button)return;const item=button.closest('[data-gallery-item]');item.classList.add('pending-delete');item.querySelector('input').checked=true;item.hidden=true;updateGalleries(button.closest('form'));});

document.addEventListener('dragstart',(event)=>{
    const grip=event.target.closest('.gallery-block-grip');
    if(grip){draggedGallery=grip.closest('[data-gallery-marker]');draggedGallery.classList.add('dragging-gallery');event.dataTransfer.effectAllowed='move';return;}
    const item=event.target.closest('[data-gallery-item]');if(item){draggedImage=item;item.classList.add('dragging');event.dataTransfer.effectAllowed='move';}
});
document.addEventListener('pointerdown',(event)=>{
    const grip=event.target.closest('.gallery-block-grip,.inline-image-grip');
    if(!grip)return;
    event.preventDefault();
    draggedGallery=grip.closest('[data-gallery-marker],[data-inline-image]');
    draggedGallery.classList.add(draggedGallery.matches('[data-gallery-marker]')?'dragging-gallery':'dragging-inline-image');
    grip.setPointerCapture?.(event.pointerId);
});
document.addEventListener('pointermove',(event)=>{
    if(!draggedGallery)return;
    event.preventDefault();
    const editor=draggedGallery.closest('[data-rich-editor]');
    if(!editor)return;
    const blocks=[...editor.querySelectorAll('p,h1,h2,h3,h4,h5,h6,blockquote,ul,ol,figure')].filter((block)=>{
        const box=block.getBoundingClientRect();
        return !block.contains(draggedGallery)&&!draggedGallery.contains(block)&&box.height>0&&box.width>0;
    }).sort((left,right)=>left.getBoundingClientRect().top-right.getBoundingClientRect().top);
    const before=blocks.find((block)=>event.clientY<block.getBoundingClientRect().top+block.getBoundingClientRect().height/2);
    if(before)before.parentElement.insertBefore(draggedGallery,before);
    else if(blocks.length){const last=blocks[blocks.length-1];last.parentElement.insertBefore(draggedGallery,last.nextSibling);}
    else editor.append(draggedGallery);
    if(event.clientY<110)window.scrollBy({top:-18,left:0});
    else if(event.clientY>window.innerHeight-110)window.scrollBy({top:18,left:0});
});
document.addEventListener('pointerup',()=>{
    if(!draggedGallery)return;
    const form=draggedGallery.closest('form');
    draggedGallery.classList.remove('dragging-gallery','dragging-inline-image');
    draggedGallery=null;
    form?.dispatchEvent(new Event('input',{bubbles:true}));
});
document.addEventListener('dragover',(event)=>{
    const fileDropEditor=event.target.closest('[data-rich-editor]');
    if(fileDropEditor&&!event.target.closest('[data-file-drop]')&&Array.from(event.dataTransfer?.types||[]).includes('Files')){
        event.preventDefault();
        event.dataTransfer.dropEffect='copy';
        return;
    }
    if(draggedImage){
        const list=event.target.closest('[data-gallery-editor]');
        if(!list||list!==draggedImage.parentElement)return;
        event.preventDefault();
        const siblings=[...list.querySelectorAll(':scope > [data-gallery-item]:not(.dragging):not([hidden])')];
        const before=siblings.find((item)=>{const box=item.getBoundingClientRect();return event.clientY<box.top+box.height/2&&(event.clientY<box.top+box.height*.3||event.clientX<box.left+box.width/2);});
        list.insertBefore(draggedImage,before||null);
        return;
    }
    if(!draggedGallery)return;
    const editor=event.target.closest('[data-rich-editor]');
    if(!editor)return;
    event.preventDefault();
    const blocks=[...editor.children].filter((child)=>child!==draggedGallery);
    const before=blocks.find((block)=>event.clientY<block.getBoundingClientRect().top+block.getBoundingClientRect().height/2);
    editor.insertBefore(draggedGallery,before||null);
});
document.addEventListener('drop',(event)=>{
    const editor=event.target.closest('[data-rich-editor]');
    if(!editor||event.target.closest('[data-file-drop]'))return;
    const files=Array.from(event.dataTransfer?.files||[]).filter((file)=>['image/jpeg','image/png','image/webp'].includes(file.type));
    if(!files.length)return;
    event.preventDefault();
    event.stopPropagation();
    let block=event.target;
    while(block&&block.parentElement!==editor)block=block.parentElement;
    if(block&&event.clientY>block.getBoundingClientRect().top+block.getBoundingClientRect().height/2)block=block.nextElementSibling;
    addInlineImages(editor.closest('form'),files,block||null);
});
document.addEventListener('dragend',()=>{draggedGallery?.classList.remove('dragging-gallery');draggedImage?.classList.remove('dragging');if(draggedImage)updateGalleries(draggedImage.closest('form'));draggedGallery=null;draggedImage=null;});

document.addEventListener('mousedown',(event)=>{if(event.target.closest('[data-gallery-add],[data-inline-image-add],[data-emoji-toggle],[data-emoji]')){const editor=event.target.closest('form')?.querySelector('[data-rich-editor]');if(editor)rememberInsertion(editor);event.preventDefault();}});
document.addEventListener('click',(event)=>{if(event.target.closest('.emoji-control'))return;document.querySelectorAll('[data-emoji-picker]').forEach((picker)=>picker.hidden=true);document.querySelectorAll('[data-emoji-toggle]').forEach((button)=>button.setAttribute('aria-expanded','false'));});
document.addEventListener('keydown',(event)=>{if(event.key!=='Escape')return;document.querySelectorAll('[data-emoji-picker]').forEach((picker)=>picker.hidden=true);document.querySelectorAll('[data-emoji-toggle]').forEach((button)=>button.setAttribute('aria-expanded','false'));});
document.addEventListener('selectionchange',()=>document.querySelectorAll('[data-rich-editor]').forEach(rememberInsertion));
document.querySelectorAll('[data-rich-editor]').forEach((editor)=>{
    editor.addEventListener('keydown',(event)=>{
        if(event.key!=='Enter'||event.shiftKey)return;
        const selection=getSelection(),anchor=selection?.anchorNode;
        const element=anchor?.nodeType===Node.ELEMENT_NODE?anchor:anchor?.parentElement;
        const heading=element?.closest('h1,h2,h3,h4,h5,h6');
        if(!heading||!selection.isCollapsed)return;
        event.preventDefault();
        const caret=selection.getRangeAt(0),before=document.createRange(),after=document.createRange();
        before.selectNodeContents(heading);before.setEnd(caret.startContainer,caret.startOffset);
        after.selectNodeContents(heading);after.setStart(caret.startContainer,caret.startOffset);
        const paragraph=document.createElement('p');paragraph.append(document.createElement('br'));
        if(before.toString()==='')heading.before(paragraph);
        else if(after.toString()==='')heading.after(paragraph);
        else{paragraph.replaceChildren(after.extractContents());heading.after(paragraph);}
        const nextCaret=document.createRange();nextCaret.setStart(paragraph,0);nextCaret.collapse(true);selection.removeAllRanges();selection.addRange(nextCaret);
        rememberInsertion(editor);
        editor.dispatchEvent(new Event('input',{bubbles:true}));
        if(typeof updateToolbarState==='function')updateToolbarState(editor);
    });
    editor.addEventListener('mouseup',()=>rememberInsertion(editor));editor.addEventListener('keyup',()=>rememberInsertion(editor));editor.addEventListener('focus',()=>rememberInsertion(editor));
});
document.addEventListener('dragenter',(event)=>{const zone=event.target.closest('[data-file-drop]');if(zone){event.preventDefault();zone.classList.add('is-dragging');}});
document.addEventListener('dragleave',(event)=>{const zone=event.target.closest('[data-file-drop]');if(zone)zone.classList.remove('is-dragging');});
document.addEventListener('drop',(event)=>{if(draggedGallery||draggedImage){event.preventDefault();return;}const zone=event.target.closest('[data-file-drop]');if(!zone||!event.dataTransfer?.files.length)return;event.preventDefault();zone.classList.remove('is-dragging');const input=zone.querySelector('input[type=file]');input.files=event.dataTransfer.files;input.dispatchEvent(new Event('change',{bubbles:true}));});

document.querySelectorAll('[data-rich-editor]').forEach((editor)=>editor.closest('form').addEventListener('submit',()=>{const form=editor.closest('form'),copy=editor.cloneNode(true);copy.querySelectorAll('[data-gallery-marker]').forEach((marker)=>marker.replaceWith(document.createTextNode(`[[NEWS_GALLERY:${marker.dataset.galleryKey}]]`)));copy.querySelectorAll('[data-inline-image]').forEach((figure)=>figure.replaceWith(document.createTextNode(`[[NEWS_IMAGE:${figure.dataset.inlineImage}]]`)));form.elements.namedItem('content').value=copy.innerHTML;form.elements.namedItem('content_is_html').value='1';updateGalleries(form);}));

document.querySelectorAll('[data-auto-grow]').forEach((textarea)=>{const resize=()=>{textarea.style.height='auto';textarea.style.height=`${Math.max(118,textarea.scrollHeight)}px`;const count=textarea.closest('label').querySelector('[data-character-count]');if(count)count.textContent=`${textarea.value.length} / ${textarea.maxLength}`;};textarea.addEventListener('input',resize);document.addEventListener('click',(event)=>{if(event.target.closest('.editor-edit,.editor-create'))setTimeout(resize,20);});resize();});
document.addEventListener('input',(event)=>{const textarea=event.target.closest('textarea[data-auto-grow]');if(!textarea)return;textarea.style.height='auto';textarea.style.height=`${Math.max(88,textarea.scrollHeight)}px`;});
