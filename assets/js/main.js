document.addEventListener('DOMContentLoaded', () => {
    // 1. Navigation Header & Footer Component Injection
    injectHeaderFooter();

    // 2. Setup Video Lightbox / Modal
    setupVideoModal();

    // 3. Setup Contact Form Handler
    setupContactForm();

    // 4. Setup Accordion (for products page)
    setupAccordions();

    // 5. Setup Category Filter navigation (for products page)
    setupProductFilters();

    // 6. Setup Homepage Hero Slider
    initHeroSlider();
});

// Function to dynamically inject identical header and footer across all pages
function injectHeaderFooter() {
    const currentPath = window.location.pathname;
    const pageName = currentPath.substring(currentPath.lastIndexOf('/') + 1) || 'index.html';

    // Top bar HTML (email + phone strip + social links)
    const topBarHTML = `
        <div class="top-bar">
            <div class="container">
                <div class="top-bar-inner">
                    <div class="top-bar-contacts">
                        <a href="mailto:sales@indeva-ergomatic.co.in">
                            <i data-lucide="mail"></i> sales@indeva-ergomatic.co.in
                        </a>
                        <a href="tel:+917447420691">
                            <i data-lucide="phone"></i> +91 7447420691
                        </a>
                    </div>
                    <div class="top-bar-socials" style="display: flex; gap: 1.25rem; align-items: center; margin-left: auto;">
                        <a href="https://www.linkedin.com/company/indeva-ergomatic-india-private-limited/?lipi=urn%3Ali%3Apage%3Acompanies_company_index%3B7b8cea74-8ef3-4127-850e-5336daa02f46" target="_blank" title="LinkedIn"><i data-lucide="linkedin" style="width: 16px; height: 16px;"></i></a>
                        <a href="https://www.facebook.com/share/1Xip1Gqg46/" target="_blank" title="Facebook"><i data-lucide="facebook" style="width: 16px; height: 16px;"></i></a>
                        <a href="https://www.instagram.com/indevaergomatic?igsh=MWtkbjMxdzFkcmI1OQ==" target="_blank" title="Instagram"><i data-lucide="instagram" style="width: 16px; height: 16px;"></i></a>
                        <a href="https://youtube.com/@indevaergomaticindia?si=D7vrwJ9uI8p68Yzt" target="_blank" title="YouTube"><i data-lucide="youtube" style="width: 16px; height: 16px;"></i></a>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Header HTML template — 8 nav items per PDF spec
    const headerHTML = `
        ${topBarHTML}
        <div class="container">
            <div class="nav-container">
                <a href="index.html" class="logo">
                    <img src="assets/logo.png" alt="INDEVA Ergomatic Logo" style="height: 45px; width: auto; object-fit: contain;">
                </a>
                <button class="mobile-nav-toggle" aria-label="Toggle Navigation" id="mobile-toggle">
                    ☰
                </button>
                <ul class="nav-menu" id="nav-menu">
                    <li><a href="index.html" class="nav-link ${pageName === 'index.html' || pageName === '' ? 'active' : ''}">Home</a></li>
                    <li><a href="about.html" class="nav-link ${pageName === 'about.html' ? 'active' : ''}">About</a></li>
                    <li class="nav-dropdown-wrapper">
                        <a href="#" class="nav-link ${['industrial-manipulators.html', 'automatic-guided-vehicles.html', 'robotic-automation.html'].includes(pageName) ? 'active' : ''}">Products <span class="dropdown-caret">▼</span></a>
                        <ul class="nav-dropdown">
                            <li><a href="industrial-manipulators.html" class="dropdown-link ${pageName === 'industrial-manipulators.html' ? 'active' : ''}">Industrial Manipulators</a></li>
                            <li><a href="automatic-guided-vehicles.html" class="dropdown-link ${pageName === 'automatic-guided-vehicles.html' ? 'active' : ''}">AGV / AMR Systems</a></li>
                            <li><a href="robotic-automation.html" class="dropdown-link ${pageName === 'robotic-automation.html' ? 'active' : ''}">Robotic Automation</a></li>
                        </ul>
                    </li>
                    <li><a href="application.html" class="nav-link ${pageName === 'application.html' ? 'active' : ''}">Application</a></li>
                    <li><a href="news.html" class="nav-link ${pageName === 'news.html' ? 'active' : ''}">Articles & News</a></li>
                    <li><a href="gallery.html" class="nav-link ${pageName === 'gallery.html' ? 'active' : ''}">Gallery</a></li>
                    <li><a href="downloads.html" class="nav-link ${pageName === 'downloads.html' ? 'active' : ''}">Downloads</a></li>
                    <li><a href="contact.html" class="nav-link ${pageName === 'contact.html' ? 'active' : ''}">Contact Us</a></li>
                    <li><a href="contact.html?quote=true" class="nav-cta">Get A Quote</a></li>
                </ul>
            </div>
        </div>
    `;

    // Footer HTML template — with both India & Italy HQ
    const footerHTML = `
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="index.html" class="footer-logo-badge" style="display: inline-block; margin-bottom: 1.25rem;">
                        <img src="assets/logo.png" alt="INDEVA Ergomatic Logo">
                    </a>
                    <p>INDEVA Ergomatic India is a leading provider of advanced industrial manipulators, AGVs, and custom material handling automation solutions, delivering cutting-edge technology and local engineering expertise.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="industrial-manipulators.html">Products</a></li>
                        <li><a href="application.html">Application</a></li>
                        <li><a href="gallery.html">Gallery</a></li>
                        <li><a href="contact.html">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Products</h4>
                    <ul>
                        <li><a href="industrial-manipulators.html">Industrial Manipulators</a></li>
                        <li><a href="automatic-guided-vehicles.html">Automated Guided Vehicles</a></li>
                        <li><a href="robotic-automation.html">Robotic Automation</a></li>
                        <li><a href="downloads.html">Downloads</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <p style="margin-bottom: 0.5rem;"><strong>India Office:</strong></p>
                    <p style="margin-bottom: 0.75rem;">Gat No. 728/B, Jaywant Industrial Estate, A/P:-Velu, Pune-Bangalore Highway, Taluka:- Bhor, Pune – 412205</p>
                    <p style="margin-bottom: 0.5rem;"><strong>Headquarters (Italy):</strong></p>
                    <p style="margin-bottom: 0.75rem; color: rgba(255,255,255,0.45); font-size: 0.85rem;">Scaglia INDEVA S.p.A. · Via Guglielmo Marconi, 42, 24012 Val Brembilla BG, Italy</p>
                    <p style="margin-bottom: 0.25rem;">📧 <a href="mailto:sales@indeva-ergomatic.co.in">sales@indeva-ergomatic.co.in</a></p>
                    <p style="margin-bottom: 0.25rem;">📞 <a href="tel:+917447420691">+91 7447420691</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; ${new Date().getFullYear()} INDEVA Ergomatic India Pvt. Ltd. All rights reserved.</p>
                <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                    <a href="https://www.linkedin.com/company/indeva-ergomatic-india-private-limited/?lipi=urn%3Ali%3Apage%3Acompanies_company_index%3B7b8cea74-8ef3-4127-850e-5336daa02f46" target="_blank" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                        <i data-lucide="linkedin" style="width: 14px; height: 14px;"></i> LinkedIn
                    </a>
                    <a href="https://www.facebook.com/share/1Xip1Gqg46/" target="_blank" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                        <i data-lucide="facebook" style="width: 14px; height: 14px;"></i> Facebook
                    </a>
                    <a href="https://www.instagram.com/indevaergomatic?igsh=MWtkbjMxdzFkcmI1OQ==" target="_blank" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                        <i data-lucide="instagram" style="width: 14px; height: 14px;"></i> Instagram
                    </a>
                    <a href="https://youtube.com/@indevaergomaticindia?si=D7vrwJ9uI8p68Yzt" target="_blank" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                        <i data-lucide="youtube" style="width: 14px; height: 14px;"></i> YouTube
                    </a>
                    <a href="https://www.indevagroup.com" target="_blank" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.85rem; margin-left: auto;">www.indevagroup.com</a>
                </div>
            </div>
        </div>
    `;

    // Inject if placeholders exist
    const headerElem = document.getElementById('site-header');
    if (headerElem) {
        headerElem.innerHTML = headerHTML;
        setupMobileMenu();
        
        // Scroll listener for Phantom Floating Header with hysteresis to prevent scroll jitter
        let isPhantom = false;
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            if (!isPhantom && scrollY > 120) {
                headerElem.classList.add('phantom-active');
                isPhantom = true;
            } else if (isPhantom && scrollY < 40) {
                headerElem.classList.remove('phantom-active');
                isPhantom = false;
            }
        });
    }

    const footerElem = document.getElementById('site-footer');
    if (footerElem) {
        footerElem.innerHTML = footerHTML;
    }

    // Initialize/recreate Lucide icons inside injected content
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Mobile Menu toggler setup
function setupMobileMenu() {
    const mobileToggle = document.getElementById('mobile-toggle');
    const navMenu = document.getElementById('nav-menu');

    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            mobileToggle.textContent = navMenu.classList.contains('open') ? '✕' : '☰';
        });
    }
}

// Video Lightbox Popup/Modal Handler
function setupVideoModal() {
    // Create modal elements dynamically if not already in HTML
    let modal = document.getElementById('video-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'video-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <button class="modal-close" id="modal-close-btn">&times;</button>
                <iframe id="modal-iframe" src="" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const iframe = document.getElementById('modal-iframe');
    const closeBtn = document.getElementById('modal-close-btn');

    // Close modal function
    const closeModal = () => {
        modal.classList.remove('open');
        if (iframe) iframe.src = '';
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Escape key closes modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    // Delegate click events on document for video-play links
    document.addEventListener('click', (e) => {
        const videoBtn = e.target.closest('[data-video-url]');
        if (videoBtn) {
            e.preventDefault();
            let rawUrl = videoBtn.getAttribute('data-video-url');
            
            // Format YouTube link into embed format
            let embedUrl = rawUrl;
            if (rawUrl.includes('youtu.be/')) {
                const id = rawUrl.split('youtu.be/')[1].split('?')[0];
                embedUrl = `https://www.youtube.com/embed/${id}?autoplay=1`;
            } else if (rawUrl.includes('youtube.com/shorts/')) {
                const id = rawUrl.split('youtube.com/shorts/')[1].split('?')[0];
                embedUrl = `https://www.youtube.com/embed/${id}?autoplay=1`;
            } else if (rawUrl.includes('watch?v=')) {
                const id = rawUrl.split('watch?v=')[1].split('&')[0];
                embedUrl = `https://www.youtube.com/embed/${id}?autoplay=1`;
            }

            if (iframe) {
                iframe.src = embedUrl;
                modal.classList.add('open');
            }
        }
    });
}

// Accordion handler for product sub-groups
function setupAccordions() {
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            const body = header.nextElementSibling;
            const isOpen = header.classList.contains('active');
            
            // Close all other accordions in same parent
            const parent = header.closest('.accordion-group');
            if (parent) {
                parent.querySelectorAll('.accordion-header.active').forEach(h => {
                    h.classList.remove('active');
                    h.nextElementSibling?.classList.remove('open');
                });
            }
            
            // Toggle this one
            if (!isOpen) {
                header.classList.add('active');
                if (body) body.classList.add('open');
                
                // Auto scroll to the opened accordion header smoothly, accounting for sticky header offset
                setTimeout(() => {
                    const headerOffset = 90; // height of sticky header
                    const elementPosition = header.getBoundingClientRect().top + window.scrollY;
                    const offsetPosition = elementPosition - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }, 200); // delay allows the opening transition to start
            }
        });
    });
}

// Contact form handler
function setupContactForm() {
    const contactForm = document.getElementById('enquiry-form');
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Extract values
            const name = document.getElementById('form-name')?.value;
            const email = document.getElementById('form-email')?.value;
            const phone = document.getElementById('form-phone')?.value;
            const message = document.getElementById('form-message')?.value;

            // In actual cPanel hosting this would submit to a PHP mailer backend.
            // We simulate a successful submission alert.
            alert(`Thank you for your enquiry, ${name}! Our representative Mr. Umesh Kedari will get back to you at ${email} or ${phone} shortly.`);
            contactForm.reset();
        });
    }
}

// Category filter tab highlights and smooth scroll
function setupProductFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const sections = [
        document.getElementById('manipulators'),
        document.getElementById('agv'),
        document.getElementById('automation'),
        document.getElementById('lean')
    ].filter(el => el !== null);

    if (filterBtns.length === 0) return;

    // Smooth scroll and active class toggling on click
    filterBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetId = btn.getAttribute('href');
            if (targetId && targetId.startsWith('#')) {
                e.preventDefault();
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const headerOffset = 100; // Adjust for sticky header height
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Set URL hash without jump
                    history.pushState(null, null, targetId);
                }
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
        });
    });

    // Scroll active tracking (IntersectionObserver)
    if ('IntersectionObserver' in window && sections.length > 0) {
        const observerOptions = {
            root: null,
            rootMargin: '-10% 0px -65% 0px', // Triggers when section is near top-center
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    filterBtns.forEach(btn => {
                        if (btn.getAttribute('href') === `#${id}`) {
                            btn.classList.add('active');
                        } else {
                            btn.classList.remove('active');
                        }
                    });
                }
            });
        }, observerOptions);

        sections.forEach(section => observer.observe(section));
    }
}

// Homepage Hero Slider implementation
function initHeroSlider() {
    const slider = document.querySelector('.hero-slider');
    if (!slider) return;

    const slides = slider.querySelectorAll('.slide');
    const dots = slider.querySelectorAll('.slider-dot');
    const prevBtn = slider.querySelector('.slider-arrow-prev');
    const nextBtn = slider.querySelector('.slider-arrow-next');

    let currentIndex = 0;
    let slideInterval;
    const intervalTime = 6000; // 6 seconds

    function showSlide(index) {
        // Remove active class from current slide and dot
        slides[currentIndex].classList.remove('active');
        if (dots.length > 0) dots[currentIndex].classList.remove('active');

        // Update index
        currentIndex = (index + slides.length) % slides.length;

        // Add active class to new slide and dot
        slides[currentIndex].classList.add('active');
        if (dots.length > 0) dots[currentIndex].classList.add('active');
    }

    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    function startAutoSlide() {
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, intervalTime);
    }

    // Event Listeners for dot controls
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
            startAutoSlide();
        });
    });

    // Event Listeners for arrow buttons
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            startAutoSlide();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            startAutoSlide();
        });
    }

    // Initialize auto sliding
    startAutoSlide();
}

