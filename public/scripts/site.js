const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('.main-navigation');
if (menuButton && navigation) {
    menuButton.addEventListener('click', () => {
        const open = menuButton.getAttribute('aria-expanded') !== 'true';
        menuButton.setAttribute('aria-expanded', String(open));
        navigation.classList.toggle('open', open);
    });
}

const hero = document.querySelector('.site-hero');
if (hero) {
    const backgrounds = Array.from({length: 10}, (_, index) => `/media/backgrounds/optimized/${index + 1}.webp`);
    const layers = [...hero.querySelectorAll('.site-hero-slide')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let backgroundIndex = 0;
    let visibleLayer = 0;

    const loadImage = (source) => new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(source);
        image.onerror = reject;
        image.src = source;
    });

    layers[0].style.backgroundImage = `url('${backgrounds[0]}')`;

    if (!reducedMotion && layers.length === 2) {
        const showNext = async () => {
            const nextIndex = (backgroundIndex + 1) % backgrounds.length;
            try {
                const source = await loadImage(backgrounds[nextIndex]);
                const nextLayer = 1 - visibleLayer;
                layers[nextLayer].style.backgroundImage = `url('${source}')`;
                layers[nextLayer].classList.add('is-visible');
                layers[visibleLayer].classList.remove('is-visible');
                visibleLayer = nextLayer;
                backgroundIndex = nextIndex;
            } catch (_) {
                // Das aktuelle Motiv bleibt sichtbar; beim nächsten Intervall wird erneut geladen.
            }
        };

        loadImage(backgrounds[1]).catch(() => {});
        window.setInterval(showNext, 7000);
    }
}

document.querySelectorAll('.gallery-slider:not([data-home-carousel])').forEach((gallery) => {
    const slides = Array.from(gallery.querySelectorAll(':scope>ul>li'));
    const buttons = Array.from(gallery.querySelectorAll('.gallery-thumbnails [data-slide-index]'));
    if (!slides.length) return;
    let active = 0;
    let timer;
    const show = (index) => {
        active = (index + slides.length) % slides.length;
        slides.forEach((slide, itemIndex) => slide.classList.toggle('active', itemIndex === active));
        buttons.forEach((button, itemIndex) => button.classList.toggle('active', itemIndex === active));
    };
    const start = () => {
        clearInterval(timer);
        if (slides.length > 1) timer = setInterval(() => show(active + 1), 5000);
    };
    buttons.forEach((button, index) => button.addEventListener('click', (event) => {
        event.preventDefault();
        show(index);
        start();
    }));
    show(0);
    start();
});

document.querySelectorAll('.contact-map-shell').forEach((shell) => {
    const map = shell.querySelector('iframe');
    if (!map) return;
    map.addEventListener('load', () => {
        shell.classList.add('is-loaded');
        shell.setAttribute('aria-busy', 'false');
    }, {once: true});
});
