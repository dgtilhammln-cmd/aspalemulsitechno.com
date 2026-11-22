(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* -------------------------------------
       HERO SLIDER
    ------------------------------------- */
    const heroState = {
        index: 1,
        slides: [],
        dots: [],
        timer: null
    };

    const updateHeroSlides = () => {
        if (!heroState.slides.length) return;

        if (heroState.index > heroState.slides.length) {
            heroState.index = 1;
        }
        if (heroState.index < 1) {
            heroState.index = heroState.slides.length;
        }

        heroState.slides.forEach((slide, i) => {
            slide.classList.toggle('active', i + 1 === heroState.index);
        });
        heroState.dots.forEach((dot, i) => {
            dot.classList.toggle('active', i + 1 === heroState.index);
        });
    };

    const restartHeroTimer = () => {
        if (prefersReducedMotion || !heroState.slides.length) return;
        window.clearInterval(heroState.timer);
        heroState.timer = window.setInterval(() => {
            window.changeSlide(1);
        }, 5000);
    };

    window.changeSlide = (increment) => {
        heroState.index += increment;
        updateHeroSlides();
        restartHeroTimer();
    };

    window.currentSlide = (n) => {
        heroState.index = n;
        updateHeroSlides();
        restartHeroTimer();
    };

    const initHeroSlider = () => {
        heroState.slides = Array.from(document.querySelectorAll('.hero-background-img'));
        heroState.dots = Array.from(document.querySelectorAll('.dot'));

        if (!heroState.slides.length) return;

        heroState.index = 1;
        updateHeroSlides();
        restartHeroTimer();
    };

    /* -------------------------------------
       CAROUSEL HANDLER
    ------------------------------------- */
    const setupCarousel = ({
        trackSelector,
        wrapperSelector,
        prevSelector,
        nextSelector,
        breakpoints = [
            { min: 1200, value: 3 },
            { min: 768, value: 2 }
        ],
        defaultValue = 1
    }) => {
        const track = document.querySelector(trackSelector);
        if (!track) return;

        const wrapper = wrapperSelector ? document.querySelector(wrapperSelector) : track.parentElement;
        const cards = Array.from(track.children);
        const prevBtn = prevSelector ? document.querySelector(prevSelector) : null;
        const nextBtn = nextSelector ? document.querySelector(nextSelector) : null;

        if (!cards.length) return;

        let currentIndex = 0;
        let cardsPerView = defaultValue;
        let resizeTimeout;

        const orderedBreakpoints = [...breakpoints].sort((a, b) => b.min - a.min);

        const computeCardsPerView = () => {
            let value = defaultValue;
            const width = window.innerWidth;
            for (const bp of orderedBreakpoints) {
                if (width >= bp.min) {
                    value = bp.value;
                    break;
                }
            }
            return value;
        };

        const getGapValue = () => {
            const styles = window.getComputedStyle(track);
            const gapValue = parseFloat(styles.columnGap || styles.gap || '0');
            return Number.isFinite(gapValue) ? gapValue : 0;
        };

        const updateTrack = () => {
            const gap = getGapValue();
            const cardWidth = cards[0].getBoundingClientRect().width + gap;
            const maxIndex = Math.max(cards.length - cardsPerView, 0);
            currentIndex = Math.min(currentIndex, maxIndex);
            const offset = cardWidth * currentIndex;
            track.style.transform = `translateX(-${offset}px)`;

            if (prevBtn) prevBtn.disabled = currentIndex === 0;
            if (nextBtn) nextBtn.disabled = currentIndex === maxIndex;
        };

        const updateCardsPerView = () => {
            cardsPerView = computeCardsPerView();
            if (wrapper) {
                wrapper.style.setProperty('--cards-per-view', cardsPerView);
            }
            currentIndex = Math.min(currentIndex, Math.max(cards.length - cardsPerView, 0));
            updateTrack();
        };

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex -= 1;
                    updateTrack();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                const maxIndex = Math.max(cards.length - cardsPerView, 0);
                if (currentIndex < maxIndex) {
                    currentIndex += 1;
                    updateTrack();
                }
            });
        }

        window.addEventListener('resize', () => {
            window.clearTimeout(resizeTimeout);
            resizeTimeout = window.setTimeout(updateCardsPerView, 160);
        });

        updateCardsPerView();
    };

    const initCarousels = () => {
        setupCarousel({
            trackSelector: '.product-track',
            wrapperSelector: '.product-track-wrapper',
            prevSelector: '.product-arrow.prev',
            nextSelector: '.product-arrow.next',
            breakpoints: [
                { min: 1200, value: 3 },
                { min: 768, value: 2 }
            ],
            defaultValue: 1
        });

        setupCarousel({
            trackSelector: '.gallery-track',
            wrapperSelector: '.gallery-track-wrapper',
            prevSelector: '.gallery-arrow.prev',
            nextSelector: '.gallery-arrow.next',
            breakpoints: [],
            defaultValue: 1
        });

        setupCarousel({
            trackSelector: '.testi-track',
            wrapperSelector: '.testi-track-wrapper',
            prevSelector: '.testi-arrow.prev',
            nextSelector: '.testi-arrow.next',
            breakpoints: [
                { min: 1100, value: 3 },
                { min: 720, value: 2 }
            ],
            defaultValue: 1
        });
    };

    /* -------------------------------------
       SCROLL REVEAL
    ------------------------------------- */
    const initScrollReveal = () => {
        const revealTargets = document.querySelectorAll('[data-animate]');
        if (!revealTargets.length) return;

        if (!('IntersectionObserver' in window)) {
            revealTargets.forEach((el) => el.classList.add('is-visible'));
            return;
        }

        // Optimize for mobile: lower threshold and earlier trigger
        const isMobile = window.innerWidth <= 768;
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, {
            threshold: isMobile ? 0.1 : 0.25, // Lower threshold for mobile
            rootMargin: isMobile ? '0px 0px 0px' : '0px 0px -10%' // Trigger earlier on mobile
        });

        const revealIfInView = () => {
            revealTargets.forEach((target) => {
                if (target.classList.contains('is-visible')) return;
                const rect = target.getBoundingClientRect();
                // More aggressive check for mobile - trigger when element is closer to viewport
                const viewportThreshold = isMobile ? 0.95 : 0.9;
                const inView = rect.top <= window.innerHeight * viewportThreshold && rect.bottom >= window.innerHeight * 0.1;
                if (inView) {
                    target.classList.add('is-visible');
                    observer.unobserve(target);
                }
            });
        };

        revealTargets.forEach((target) => {
            const delay = target.getAttribute('data-animate-delay');
            if (delay) {
                target.style.setProperty('--animate-delay', `${delay}ms`);
            }
            observer.observe(target);
        });

        revealIfInView();
        window.addEventListener('load', revealIfInView, { once: true });
    };

    /* -------------------------------------
       BACK TO TOP BUTTON
    ------------------------------------- */
    const initBackToTop = () => {
        const backToTopBtn = document.querySelector('.floating-btn--top');
        if (!backToTopBtn) return;

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        const toggleVisibility = () => {
            const shouldShow = window.scrollY > 320;
            backToTopBtn.classList.toggle('is-visible', shouldShow);
        };

        toggleVisibility();
        window.addEventListener('scroll', () => {
            window.requestAnimationFrame(toggleVisibility);
        }, { passive: true });
    };

    /* -------------------------------------
       NAVIGATION HIGHLIGHT ON SCROLL
    ------------------------------------- */
    const initNavHighlight = () => {
        const navLinks = Array.from(document.querySelectorAll('.nav-link')).filter((link) => link.hash);
        if (!navLinks.length) return;

        const sections = navLinks
            .map((link) => ({ link, section: document.querySelector(link.hash) }))
            .filter(({ section }) => section);

        if (!sections.length) return;

        let activeHash = null;

        const setActive = (hash) => {
            if (hash === activeHash) return;
            activeHash = hash;
            navLinks.forEach((link) => {
                link.classList.toggle('is-active', link.hash === activeHash);
            });
        };

        const onScroll = () => {
            const scrollPos = window.scrollY + window.innerHeight * 0.3;
            let nextActive = sections[0].link.hash;

            for (const { link, section } of sections) {
                const top = section.offsetTop;
                const bottom = top + section.offsetHeight;
                if (scrollPos >= top && scrollPos < bottom) {
                    nextActive = link.hash;
                    break;
                }
            }

            setActive(nextActive);
        };

        onScroll();
        window.addEventListener('scroll', () => {
            window.requestAnimationFrame(onScroll);
        }, { passive: true });
    };

    /* -------------------------------------
       MOBILE NAVIGATION TOGGLE
       ------------------------------------- */
    const initMobileNav = () => {
        const toggle = document.querySelector('.nav-toggle');
        const nav = document.querySelector('.main-nav');
        const overlay = document.querySelector('.nav-overlay');
        if (!toggle || !nav || !overlay) return;

        const navLinks = Array.from(nav.querySelectorAll('a'));

        const setOpen = (shouldOpen) => {
            toggle.classList.toggle('is-active', shouldOpen);
            nav.classList.toggle('is-open', shouldOpen);
            overlay.classList.toggle('is-visible', shouldOpen);
            document.body.classList.toggle('is-nav-open', shouldOpen);
            toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        };

        const closeNav = () => setOpen(false);

        toggle.addEventListener('click', () => {
            const willOpen = !nav.classList.contains('is-open');
            setOpen(willOpen);
        });

        overlay.addEventListener('click', closeNav);

        navLinks.forEach((link) => {
            link.addEventListener('click', closeNav);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeNav();
            }
        });
    };

    /* -------------------------------------
       INIT SEQUENCE
    ------------------------------------- */
    document.addEventListener('DOMContentLoaded', () => {
        document.body.classList.add('reveal-init');
        initHeroSlider();
        initCarousels();
        initScrollReveal();
        initNavHighlight();
        initBackToTop();
        initMobileNav();
    });
})();

