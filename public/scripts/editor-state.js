(() => {
    const forms = [...document.querySelectorAll('[data-editor-form]')];
    if (!forms.length) return;
    let activeForm = null, pendingAction = null, savingTimer = null;
    const storageKey = (form) => `kutawerk:draft:${location.pathname}:${form.dataset.editorForm}:${form.elements.namedItem('id')?.value || 'new'}`;

    const modal = document.createElement('div');
    modal.className = 'editor-leave-modal'; modal.hidden = true;
    modal.innerHTML = '<section class="editor-leave-dialog" role="dialog" aria-modal="true" aria-labelledby="editor-modal-title"><header><span class="editor-leave-icon">!</span><h2 id="editor-modal-title"></h2></header><p data-modal-text></p><div class="editor-leave-actions"><button type="button" data-modal-secondary></button><button type="button" class="primary" data-modal-primary></button></div></section>';
    document.body.append(modal);
    const showModal = ({title,text,primary,secondary,onPrimary,onSecondary}) => { modal.querySelector('h2').textContent=title;modal.querySelector('[data-modal-text]').innerHTML=text;modal.querySelector('[data-modal-primary]').textContent=primary;modal.querySelector('[data-modal-secondary]').textContent=secondary;modal.hidden=false;pendingAction={onPrimary,onSecondary};modal.querySelector('[data-modal-primary]').focus(); };
    const closeModal = () => { modal.hidden=true;pendingAction=null; };
    modal.querySelector('[data-modal-primary]').addEventListener('click',()=>{const action=pendingAction?.onPrimary;closeModal();action?.();});
    modal.querySelector('[data-modal-secondary]').addEventListener('click',()=>{const action=pendingAction?.onSecondary;closeModal();action?.();});

    function indicator(form) {
        let node=form.querySelector('[data-change-state]');
        if(!node){node=document.createElement('span');node.className='editor-change-state';node.dataset.changeState='';const heading=form.querySelector('.editor-form-title,.news-editor-heading>div');heading?.append(node);}
        return node;
    }
    function mark(form, dirty=true) {
        if(!form.dataset.dirtyReady)return;form.dataset.dirty=dirty?'1':'0';const node=indicator(form);
        if(!dirty){node.hidden=true;node.classList.remove('dirty');node.textContent='';return;}
        node.hidden=false;node.classList.add('dirty');node.textContent='Noch nicht gespeichert';
        clearTimeout(savingTimer);savingTimer=setTimeout(()=>saveDraft(form),250);
    }
    function saveDraft(form){const values=[];new FormData(form).forEach((value,key)=>{if(typeof value==='string'&&key!=='_token')values.push([key,value]);});const editor=form.querySelector('[data-rich-editor]');let html=null;if(editor){const copy=editor.cloneNode(true);copy.querySelectorAll('[data-gallery-marker]').forEach((marker)=>marker.replaceWith(document.createTextNode(`[[NEWS_GALLERY:${marker.dataset.galleryKey}]]`)));copy.querySelectorAll('[data-inline-image]').forEach((image)=>image.remove());html=copy.innerHTML;}localStorage.setItem(storageKey(form),JSON.stringify({version:2,values,html,at:Date.now()}));}
    function clearDraft(form){localStorage.removeItem(storageKey(form));}
    function prepare(form){activeForm=form;delete form.dataset.dirtyReady;setTimeout(()=>{indicator(form);form.dataset.dirtyReady='1';mark(form,false);},50);}
    forms.forEach((form)=>{
        form.addEventListener('input',()=>mark(form));form.addEventListener('change',()=>mark(form));
        form.addEventListener('submit',()=>{form.dataset.dirty='0';clearDraft(form);});
    });
    document.addEventListener('click',(event)=>{
        const opener=event.target.closest('.editor-edit,.editor-create');if(opener){if(activeForm?.dataset.dirty==='1'){event.preventDefault();event.stopImmediatePropagation();showModal({title:'Änderungen noch nicht gespeichert',text:'Möchtest du den aktuellen Eintrag weiter bearbeiten oder deine Änderungen verwerfen?',primary:'Weiter bearbeiten',secondary:'Änderungen verwerfen',onPrimary:()=>{},onSecondary:()=>{clearDraft(activeForm);activeForm.dataset.dirty='0';opener.click();}});return;}setTimeout(()=>prepare(document.querySelector('[data-editor-form]:not([hidden])')),0);return;}
        const cancel=event.target.closest('.editor-cancel');
        const link=event.target.closest('a[href]');
        const form=activeForm&&activeForm.dataset.dirty==='1'?activeForm:null;if(!form||(!cancel&&!link))return;
        event.preventDefault();event.stopImmediatePropagation();const destination=link?.href;
        showModal({title:'Änderungen noch nicht gespeichert',text:'Wenn du diesen Bereich verlässt, gehen die aktuellen Änderungen verloren.',primary:'Weiter bearbeiten',secondary:'Änderungen verwerfen',onPrimary:()=>{},onSecondary:()=>{clearDraft(form);form.dataset.dirty='0';destination?location.assign(destination):location.reload();}});
    },true);
    document.addEventListener('click',(event)=>{if(event.target.closest('[data-gallery-add],[data-v2-gallery-delete],[data-v2-gallery-remove-new],[data-gallery-remove]'))setTimeout(()=>{const form=event.target.closest('form');if(form)mark(form);},0);});
    document.addEventListener('dragend',(event)=>{const form=event.target.closest('form');if(form&&event.target.closest('[data-gallery-marker],[data-gallery-item]'))mark(form);});

    const drafts=[];for(let index=localStorage.length-1;index>=0;index--){const key=localStorage.key(index);if(!key?.startsWith(`kutawerk:draft:${location.pathname}:`))continue;const draft=JSON.parse(localStorage.getItem(key));if(draft.version!==2){localStorage.removeItem(key);continue;}drafts.push([key,draft]);}
    if(drafts.length){const [key,draft]=drafts.sort((a,b)=>b[1].at-a[1].at)[0];showModal({title:'Nicht gespeicherte Änderungen gefunden',text:'Die Seite wurde neu geladen. Dein letzter Bearbeitungsstand kann wiederhergestellt werden.<span class="editor-draft-note">Aus Sicherheitsgründen müssen zuvor ausgewählte Bilddateien erneut ausgewählt werden.</span>',primary:'Entwurf wiederherstellen',secondary:'Entwurf verwerfen',onSecondary:()=>localStorage.removeItem(key),onPrimary:()=>{const id=new Map(draft.values).get('id')||'';const opener=id?document.querySelector(`.editor-edit[data-id="${CSS.escape(id)}"]`):document.querySelector('.editor-create');opener?.click();setTimeout(()=>{const form=document.querySelector('[data-editor-form]:not([hidden])'),content=form.elements.namedItem('content');if(content&&draft.html!==null){content.value=draft.html;form.elements.namedItem('content_is_html').value='1';hydrateRichEditor(form,opener?.dataset.imagePath||'');hydrateGalleryEditor(form,opener?.dataset.gallery||'[]');}const grouped={};draft.values.forEach(([name,value])=>(grouped[name]??=[]).push(value));Object.entries(grouped).forEach(([name,values])=>{const fields=form.querySelectorAll(`[name="${CSS.escape(name)}"]`);fields.forEach((field)=>{if(field.type==='checkbox'||field.type==='radio')field.checked=values.includes(field.value);else field.value=values[0];});});form.dataset.dirtyReady='1';mark(form,true);},100);}});}
})();
