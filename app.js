/**
 * ==========================================================================
 * Universal UI Architecture Interactive Script (app.js)
 * Provides dynamic interactions for index.html (search, filter animations)
 * and all 15 component prototypes (password toggle, OTP focus, form feedback).
 * ==========================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    initCatalogHub();
    initComponentInteractions();
    initSidebarController();
    initProfileDropdown();
});

/**
 * 1. Catalog Hub Logic (index.html)
 * Handles real-time search filtering, URL state synchronization, and smooth transitions.
 */
function initCatalogHub() {
    const searchInput = document.querySelector('.search-box input');
    const cards = document.querySelectorAll('.component-card');
    const filterRadios = document.querySelectorAll('.filter-radio');
    const filterLabels = document.querySelectorAll('.filter-btn');

    if (!cards.length) return; // Exit if not on index.html catalog

    let currentFilter = 'all';
    let currentSearch = '';

    // Remove readonly attribute if present so user can type search queries
    if (searchInput) {
        searchInput.removeAttribute('readonly');
        searchInput.setAttribute('placeholder', 'Search templates or auth (Press "/" to focus)...');
    }

    // Determine initial state from URL parameters or hash
    const urlParams = new URLSearchParams(window.location.search);
    const initialGroup = urlParams.get('group') || window.location.hash.replace('#', '');
    if (initialGroup) {
        const targetRadio = document.querySelector(`.filter-radio#filter-${initialGroup.toLowerCase()}`) || 
                            document.querySelector(`.filter-radio[id*="${initialGroup.toLowerCase()}"]`);
        if (targetRadio) {
            targetRadio.checked = true;
            currentFilter = initialGroup;
        }
    }

    // Core Filtering Function
    const applyFilters = () => {
        cards.forEach(card => {
            const cardGroup = card.getAttribute('data-group') || '';
            const cardTitle = card.querySelector('.card-title')?.textContent || '';
            const cardDesc = card.querySelector('.card-desc')?.textContent || '';
            const cardText = (cardTitle + ' ' + cardDesc + ' ' + cardGroup).toLowerCase();

            // Check group match
            const matchesGroup = (currentFilter.toLowerCase() === 'all') || 
                                 (cardGroup.toLowerCase() === currentFilter.toLowerCase());

            // Check search query match
            const matchesSearch = !currentSearch || cardText.includes(currentSearch);

            if (matchesGroup && matchesSearch) {
                card.style.display = 'flex';
                card.style.opacity = '0';
                card.style.transform = 'translateY(12px)';
                requestAnimationFrame(() => {
                    card.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                });
            } else {
                card.style.display = 'none';
            }
        });

        // Update URL parameter without reloading
        const url = new URL(window.location);
        if (currentFilter.toLowerCase() === 'all' && !currentSearch) {
            url.searchParams.delete('group');
            url.hash = '';
        } else if (currentFilter.toLowerCase() !== 'all') {
            url.searchParams.set('group', currentFilter);
        }
        window.history.replaceState({}, '', url);
    };

    // Listen to Radio Button Filter Changes
    filterRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.checked) {
                const id = e.target.id.replace('filter-', '');
                if (id === 'all') currentFilter = 'all';
                else if (id === 'auth') currentFilter = 'Authentication';
                else if (id === 'comp') currentFilter = 'Components';
                else if (id === 'layout') currentFilter = 'Layouts';
                else if (id === 'system') currentFilter = 'Design Systems';
                applyFilters();
            }
        });
    });

    // Listen to Search Input
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value.toLowerCase().trim();
            applyFilters();
        });

        // Keyboard shortcut: Press '/' to focus search box
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && document.activeElement !== searchInput) {
                e.preventDefault();
                searchInput.focus();
            } else if (e.key === 'Escape' && document.activeElement === searchInput) {
                searchInput.value = '';
                currentSearch = '';
                searchInput.blur();
                applyFilters();
            }
        });
    }

    // Add click ripple and scale effect to View buttons
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => { this.style.transform = ''; }, 150);
        });
    });
}

/**
 * 1.5. Subsystem Module Selector & Dashboard
 *
 * Removed: the Module Selector is now a standalone page under module_selector/code.html.
 */

function initComponentInteractions() {
    // A. Password Visibility Toggle
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        const wrapper = input.parentElement;
        if (!wrapper) return;

        const toggleBtn = wrapper.querySelector('button[type="button"]');
        const iconSpan = toggleBtn
            ? toggleBtn.querySelector('span.material-symbols-outlined, [data-icon]')
            : wrapper.querySelector('span#visibility-icon, span[data-icon="visibility"], span[data-icon="visibility_off"]');

        if (!toggleBtn && !iconSpan) return;

        const target = toggleBtn || iconSpan;
        if (target.dataset.toggleBound) return;
        target.dataset.toggleBound = 'true';
        target.style.cursor = 'pointer';
        target.setAttribute('title', 'Toggle Password Visibility');
        if (toggleBtn) {
            toggleBtn.classList.add('password-field-toggle');
            toggleBtn.style.position = 'relative';
            toggleBtn.style.zIndex = '10';
        }

        const updateIcon = () => {
            const el = iconSpan
                || (toggleBtn && toggleBtn.querySelector('span.material-symbols-outlined, [data-icon]'))
                || (target.tagName !== 'BUTTON' && target.tagName !== 'INPUT' ? target : null);
            if (!el) return;
            if (input.type === 'password') {
                el.textContent = 'visibility';
                el.dataset.icon = 'visibility';
                target.style.opacity = '0.7';
                target.style.color = '';
            } else {
                el.textContent = 'visibility_off';
                el.dataset.icon = 'visibility_off';
                target.style.opacity = '1';
                target.style.color = 'var(--primary)';
            }
        };

        const handler = (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
            updateIcon();
        };

        target.addEventListener('click', handler);
        if (toggleBtn && target !== toggleBtn) {
            toggleBtn.addEventListener('click', handler);
        }
        updateIcon();
    });

    // B. MFA OTP Auto-Focus & Auto-Advance
    const otpInputs = document.querySelectorAll('input[maxlength="1"], input[data-otp], .otp-input');
    if (otpInputs.length > 1) {
        otpInputs.forEach((input, index) => {
            input.setAttribute('autocomplete', 'off');
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpInputs[index - 1].focus();
                } else if (e.key === 'ArrowLeft' && index > 0) {
                    otpInputs[index - 1].focus();
                } else if (e.key === 'ArrowRight' && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
                if (pasteData.length === otpInputs.length && /^\d+$/.test(pasteData)) {
                    e.preventDefault();
                    pasteData.split('').forEach((char, i) => {
                        if (otpInputs[i]) otpInputs[i].value = char;
                    });
                    otpInputs[otpInputs.length - 1].focus();
                }
            });
        });
    }

    // C. Form Submission & Action Feedback
    // Skip any forms that use fetch()/async submit or server-side action URLs.
    // We detect this by checking: specific form ids, form action attribute, or that the page already
    // manually wired addEventListener('submit') on the form element itself (non-demo forms).
    const explicitSkipIds = new Set([
        'login-form',
        'register-form',
        'forgot-password-form',
        'reset-password-form'
    ]);

    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const hasAction = form.getAttribute('action') && form.getAttribute('action') !== '#';
        const hasHidden = form.querySelector('input[type="hidden"]') !== null;
        const hasPassword = form.querySelector('input[type="password"]') !== null;
        const hasEmail = form.querySelector('input[type="email"]') !== null;
        const hasFile = form.querySelector('input[type="file"]') !== null;

        const skip = explicitSkipIds.has(form.id)
            || hasAction
            || hasHidden
            || hasFile
            || (hasEmail && hasPassword)
            || (hasEmail && form.querySelector('button[type="submit"]'));

        if (skip) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"], .btn-primary');
            if (!submitBtn) return;

            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.8';
            submitBtn.innerHTML = '<span>Processing...</span>';

            setTimeout(() => {
                submitBtn.style.backgroundColor = '#059669';
                submitBtn.style.borderColor = '#059669';
                submitBtn.style.color = '#ffffff';
                submitBtn.innerHTML = '<span>✓ Success</span>';

                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.style.backgroundColor = '';
                    submitBtn.style.borderColor = '';
                    submitBtn.style.color = '';
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = originalText;
                    form.reset();
                }, 1500);
            }, 800);
        });
    });

    // D. Interactive Input Focus Glow
    const allInputs = document.querySelectorAll('input, select, textarea');
    allInputs.forEach(input => {
        input.addEventListener('focus', () => {
            const label = document.querySelector(`label[for="${input.id}"]`) || input.previousElementSibling;
            if (label && label.tagName === 'LABEL') {
                label.style.color = 'var(--primary)';
                label.style.transition = 'color 0.2s ease';
            }
        });
        input.addEventListener('blur', () => {
            const label = document.querySelector(`label[for="${input.id}"]`) || input.previousElementSibling;
            if (label && label.tagName === 'LABEL') {
                label.style.color = '';
            }
        });
    });
}

/**
 * 3. Sidebar Open/Close Controller
 * Replicates the smooth collapsible desktop sidebar and slide-over mobile navigation drawer
 * from the Laravel Breeze UI theme without external framework dependencies.
 */
function initSidebarController() {
    const sidebar = document.getElementById('app-sidebar');
    const desktopToggle = document.getElementById('desktop-sidebar-toggle');
    const mobileToggle = document.getElementById('mobile-sidebar-toggle');
    const mobileClose = document.getElementById('close-mobile-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggleIcon = document.getElementById('sidebar-toggle-icon');

    if (!sidebar) return;

    let isDesktopOpen = true;
    let isMobileOpen = false;

    const updateIcon = () => {
        if (!toggleIcon) return;
        if (window.innerWidth < 768) {
            toggleIcon.textContent = isMobileOpen ? 'menu_open' : 'menu';
        } else {
            toggleIcon.textContent = isDesktopOpen ? 'menu_open' : 'menu';
        }
    };

    // Initialize icon on page load
    updateIcon();

    const openMobile = () => {
        isMobileOpen = true;
        sidebar.classList.remove('-translate-x-full', 'w-0', 'md:w-0', 'border-r-0', 'md:border-r-0', 'opacity-0', 'md:opacity-0', 'pointer-events-none', 'md:pointer-events-none');
        sidebar.classList.add('translate-x-0', 'w-72', 'border-r', 'opacity-100');
        if (backdrop) {
            backdrop.classList.remove('hidden', 'opacity-0');
            backdrop.classList.add('block', 'opacity-100', 'pointer-events-auto');
            backdrop.setAttribute('aria-hidden', 'false');
        }
        updateIcon();
        if (desktopToggle) {
            desktopToggle.setAttribute('title', 'Close Sidebar');
            desktopToggle.setAttribute('aria-expanded', 'true');
        }
    };

    const closeMobile = () => {
        isMobileOpen = false;
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        if (backdrop) {
            backdrop.classList.remove('block', 'opacity-100', 'pointer-events-auto');
            backdrop.classList.add('hidden', 'opacity-0');
            backdrop.setAttribute('aria-hidden', 'true');
        }
        updateIcon();
        if (desktopToggle) {
            desktopToggle.setAttribute('title', 'Open Navigation');
            desktopToggle.setAttribute('aria-expanded', 'false');
        }
    };

    const toggleHandler = () => {
        if (window.innerWidth < 768) {
            if (isMobileOpen) {
                closeMobile();
            } else {
                openMobile();
            }
        } else {
            isDesktopOpen = !isDesktopOpen;
            if (isDesktopOpen) {
                sidebar.classList.remove('w-0', 'border-r-0', 'opacity-0', 'pointer-events-none', 'md:opacity-0', 'md:border-r-0', 'md:w-0');
                sidebar.classList.add('w-72', 'border-r', 'opacity-100');
                if (desktopToggle) {
                    desktopToggle.setAttribute('title', 'Close Sidebar');
                    desktopToggle.setAttribute('aria-expanded', 'true');
                }
            } else {
                sidebar.classList.remove('w-72', 'border-r', 'opacity-100');
                sidebar.classList.add('w-0', 'border-r-0', 'opacity-0', 'pointer-events-none', 'md:opacity-0', 'md:border-r-0', 'md:w-0');
                if (desktopToggle) {
                    desktopToggle.setAttribute('title', 'Open Sidebar');
                    desktopToggle.setAttribute('aria-expanded', 'false');
                }
            }
            updateIcon();
        }
    };

    if (desktopToggle) desktopToggle.addEventListener('click', toggleHandler);
    if (mobileToggle && mobileToggle !== desktopToggle) mobileToggle.addEventListener('click', toggleHandler);
    if (mobileClose) mobileClose.addEventListener('click', closeMobile);
    if (backdrop) backdrop.addEventListener('click', closeMobile);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isMobileOpen) {
            closeMobile();
        }
    });

    // Responsive Breakpoint Reset on Window Resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            isMobileOpen = false;
            sidebar.classList.remove('-translate-x-full', 'translate-x-0');
            if (backdrop) {
                backdrop.classList.remove('block', 'opacity-100', 'pointer-events-auto');
                backdrop.classList.add('hidden', 'opacity-0');
                backdrop.setAttribute('aria-hidden', 'true');
            }
            if (isDesktopOpen) {
                sidebar.classList.remove('w-0', 'border-r-0', 'opacity-0', 'pointer-events-none', 'md:opacity-0', 'md:border-r-0', 'md:w-0');
                sidebar.classList.add('w-72', 'border-r', 'opacity-100');
            } else {
                sidebar.classList.remove('w-72', 'border-r', 'opacity-100');
                sidebar.classList.add('w-0', 'border-r-0', 'opacity-0', 'pointer-events-none', 'md:opacity-0', 'md:border-r-0', 'md:w-0');
            }
        } else if (!isMobileOpen) {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0', 'w-0', 'border-r-0', 'opacity-0', 'pointer-events-none');
            sidebar.classList.add('w-72', 'border-r', 'opacity-100');
        }
        updateIcon();
    });
}

/**
 * Profile Dropdown Controller
 * Handles toggling the account profile dropdown menu in the header and closing when clicking outside or pressing Escape.
 */
function initProfileDropdown() {
    const toggleBtn = document.getElementById('profile-dropdown-toggle');
    const dropdownMenu = document.getElementById('profile-dropdown-menu');

    if (!toggleBtn || !dropdownMenu) return;

    let isOpen = false;

    const openDropdown = () => {
        isOpen = true;
        dropdownMenu.classList.remove('hidden', 'opacity-0', 'scale-95');
        dropdownMenu.classList.add('block', 'opacity-100', 'scale-100');
        toggleBtn.setAttribute('aria-expanded', 'true');
        const arrow = toggleBtn.querySelector('.material-symbols-outlined');
        if (arrow && arrow.textContent === 'expand_more') {
            arrow.textContent = 'expand_less';
        }
    };

    const closeDropdown = () => {
        if (!isOpen) return;
        isOpen = false;
        dropdownMenu.classList.remove('block', 'opacity-100', 'scale-100');
        dropdownMenu.classList.add('opacity-0', 'scale-95');
        toggleBtn.setAttribute('aria-expanded', 'false');
        const arrow = toggleBtn.querySelector('.material-symbols-outlined');
        if (arrow && arrow.textContent === 'expand_less') {
            arrow.textContent = 'expand_more';
        }
        setTimeout(() => {
            if (!isOpen) dropdownMenu.classList.add('hidden');
        }, 200);
    };

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (isOpen) {
            closeDropdown();
        } else {
            openDropdown();
        }
    });

    document.addEventListener('click', (e) => {
        if (isOpen && !dropdownMenu.contains(e.target) && !toggleBtn.contains(e.target)) {
            closeDropdown();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            closeDropdown();
        }
    });
}

