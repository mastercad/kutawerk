(() => {
    const links = Array.from(document.querySelectorAll('a[rel^="lightbox"]')).filter((link) => link.querySelector('img'));
    if (!links.length) return;
    const overlay = document.createElement('div');
    overlay.className = 'gallery-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Bildergalerie');
    overlay.innerHTML = '<button class="gallery-lightbox-close" aria-label="Galerie schließen">×</button><button class="gallery-lightbox-prev" aria-label="Vorheriges Bild">‹</button><figure><img alt=""></figure><button class="gallery-lightbox-next" aria-label="Nächstes Bild">›</button><span class="gallery-lightbox-counter"></span>';
    document.body.append(overlay);
    const image = overlay.querySelector('img');
    const counter = overlay.querySelector('.gallery-lightbox-counter');
    let group = [];
    let index = 0;
    const render = () => {
        const source = group[index].querySelector('img');
        image.src = source.currentSrc || source.src || group[index].dataset.href;
        image.alt = source.alt || group[index].dataset.title || '';
        counter.textContent = `${index + 1} von ${group.length}`;
    };
    const close = () => { overlay.classList.remove('open'); document.body.style.overflow = ''; };
    const move = (step) => { index = (index + step + group.length) % group.length; render(); };
    links.forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        group = links.filter((candidate) => candidate.getAttribute('rel') === link.getAttribute('rel'));
        index = group.indexOf(link);
        render(); overlay.classList.add('open'); document.body.style.overflow = 'hidden'; overlay.querySelector('.gallery-lightbox-close').focus();
    }));
    overlay.querySelector('.gallery-lightbox-close').addEventListener('click', close);
    overlay.querySelector('.gallery-lightbox-prev').addEventListener('click', () => move(-1));
    overlay.querySelector('.gallery-lightbox-next').addEventListener('click', () => move(1));
    overlay.addEventListener('click', (event) => { if (event.target === overlay) close(); });
    document.addEventListener('keydown', (event) => { if (!overlay.classList.contains('open')) return; if (event.key === 'Escape') close(); if (event.key === 'ArrowLeft') move(-1); if (event.key === 'ArrowRight') move(1); });
})();
