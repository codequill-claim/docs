/**
 * Sidebar scroll-to-active and TOC scroll tracking.
 *
 * 1. Scrolls the sidebar so the active link is visible on page load.
 * 2. Tracks scroll position to highlight the current heading in the TOC.
 */
(function () {
    'use strict';

    // ── Sidebar: scroll active link into view ──────────────────
    var activeLink = document.querySelector('.sidebar-link.active');
    if (activeLink) {
        var sidebar = document.querySelector('.docs-sidebar');
        if (sidebar) {
            // Use a small delay so the DOM is fully laid out.
            setTimeout(function () {
                activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
            }, 50);
        }
    }

    // ── TOC: scroll-spy for heading tracking ───────────────────
    var tocLinks = document.querySelectorAll('.toc-link');
    if (tocLinks.length === 0) return;

    var headingIds = [];
    tocLinks.forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && href.startsWith('#')) {
            headingIds.push(href.substring(1));
        }
    });

    if (headingIds.length === 0) return;

    var navHeight = parseInt(
        getComputedStyle(document.documentElement).getPropertyValue('--nav-height') || '64',
        10
    );
    var offset = navHeight + 32;

    function updateActiveToc() {
        var currentId = null;
        var scrollY = window.scrollY || window.pageYOffset;

        for (var i = headingIds.length - 1; i >= 0; i--) {
            var el = document.getElementById(headingIds[i]);
            if (el && el.getBoundingClientRect().top <= offset) {
                currentId = headingIds[i];
                break;
            }
        }

        // If we're near the top, select the first heading.
        if (currentId === null && scrollY < 100) {
            currentId = headingIds[0];
        }

        tocLinks.forEach(function (link) {
            var href = link.getAttribute('href');
            if (href === '#' + currentId) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Throttle scroll events.
    var ticking = false;
    window.addEventListener('scroll', function () {
        if (!ticking) {
            requestAnimationFrame(function () {
                updateActiveToc();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    // Initial state.
    updateActiveToc();
})();
