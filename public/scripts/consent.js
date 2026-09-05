(()=>{
    const key='kutawerk_consent_v1';
    const panel=document.querySelector('[data-consent-panel]');
    const backdrop=document.querySelector('[data-consent-backdrop]');
    const externalInput=panel?.querySelector('[data-consent-external]');
    let choice=null;
    try{choice=JSON.parse(localStorage.getItem(key))}catch(error){choice=null}

    const loadExternal=()=>{
        document.querySelectorAll('[data-consent-src]').forEach(element=>{
            if(!element.getAttribute('src'))element.setAttribute('src',element.dataset.consentSrc);
            element.hidden=false;
        });
        document.querySelectorAll('[data-consent-script]').forEach(marker=>{
            if(marker.dataset.loaded)return;
            const script=document.createElement('script');
            script.src=marker.dataset.consentScript;
            script.async=true;
            marker.dataset.loaded='true';
            marker.after(script);
        });
        document.querySelectorAll('[data-external-media]').forEach(element=>element.classList.add('external-media-enabled'));
    };
    const open=()=>{
        if(!panel||!backdrop)return;
        if(externalInput)externalInput.checked=Boolean(choice?.external);
        panel.hidden=false;
        backdrop.hidden=false;
        document.body.classList.add('consent-open');
        panel.querySelector('button')?.focus();
    };
    const close=()=>{
        if(!panel||!backdrop)return;
        panel.hidden=true;
        backdrop.hidden=true;
        document.body.classList.remove('consent-open');
    };
    const save=external=>{
        const mustUnload=Boolean(choice?.external)&&!external;
        choice={external:Boolean(external),updatedAt:new Date().toISOString()};
        localStorage.setItem(key,JSON.stringify(choice));
        if(choice.external)loadExternal();
        close();
        if(mustUnload)window.location.reload();
    };

    document.querySelectorAll('[data-cookie-settings]').forEach(button=>button.addEventListener('click',event=>{event.preventDefault();open()}));
    document.querySelectorAll('[data-consent-essential]').forEach(button=>button.addEventListener('click',()=>save(false)));
    document.querySelectorAll('[data-consent-save]').forEach(button=>button.addEventListener('click',()=>save(externalInput?.checked)));
    document.querySelectorAll('[data-consent-all]').forEach(button=>button.addEventListener('click',()=>save(true)));
    if(choice?.external)loadExternal();
    if(!choice)open();
})();
