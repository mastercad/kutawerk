(() => {
    document.querySelectorAll('[data-home-carousel]').forEach((carousel) => {
        const slides = Array.from(carousel.querySelectorAll(':scope > ul > li'));
        const thumbnails = Array.from(carousel.querySelectorAll('.gallery-thumbnails [data-slide-index]'));

        if (!slides.length) return;

        let activeIndex = 0;
        let timer = null;

        const show = (nextIndex) => {
            const normalizedIndex = (nextIndex + slides.length) % slides.length;
            const movingForward = normalizedIndex > activeIndex || (activeIndex === slides.length - 1 && normalizedIndex === 0);

            slides.forEach((slide, index) => {
                slide.classList.toggle('active', index === normalizedIndex);
                slide.classList.toggle('is-before', index !== normalizedIndex && (movingForward ? index === activeIndex : index !== activeIndex));
                slide.setAttribute('aria-hidden', index === normalizedIndex ? 'false' : 'true');
            });

            thumbnails.forEach((thumbnail, index) => {
                const isActive = index === normalizedIndex;
                thumbnail.classList.toggle('active', isActive);
                thumbnail.setAttribute('aria-current', isActive ? 'true' : 'false');
            });

            activeIndex = normalizedIndex;
            carousel.dataset.activeSlide = String(activeIndex);
        };

        const stop = () => {
            if (timer !== null) window.clearInterval(timer);
            timer = null;
        };

        const start = () => {
            stop();
            if (slides.length > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                timer = window.setInterval(() => show(activeIndex + 1), 4000);
            }
        };

        thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', (event) => {
                event.preventDefault();
                show(index);
                start();
            });
        });

        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', start);
        document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());

        show(0);
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                carousel.classList.add('carousel-ready');
                start();
            });
        });
    });
})();
