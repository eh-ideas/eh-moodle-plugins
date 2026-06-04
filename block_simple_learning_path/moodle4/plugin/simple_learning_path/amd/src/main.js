// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AMD module for the student-facing learning path block view.
 *
 * Responsibilities:
 *  - Toggle expand/collapse of route bodies with accessible aria-expanded.
 *  - Animate chevron icon on open/close.
 *  - Intercept locked course card clicks (aria-disabled).
 *  - Animate progress bars on first visible render.
 *
 * Works standalone — no jQuery, no Bootstrap dependency —
 * so it is compatible with any Moodle 4.x theme.
 *
 * @module     block_simple_learning_path/main
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/log'], function(Log) {

    'use strict';

    var MODULE = 'block_simple_learning_path/main';

    // ── Collapse / Expand ─────────────────────────────────────────────────────

    /**
     * Opens a route body panel.
     * @param {HTMLElement} header  The button.slp-route-header element.
     * @param {HTMLElement} body    The div.slp-route-body element.
     */
    function openPanel(header, body) {
        body.classList.add('slp-route-body--open');
        header.setAttribute('aria-expanded', 'true');
    }

    /**
     * Closes a route body panel.
     * @param {HTMLElement} header
     * @param {HTMLElement} body
     */
    function closePanel(header, body) {
        body.classList.remove('slp-route-body--open');
        header.setAttribute('aria-expanded', 'false');
    }

    /**
     * Toggles a route body panel open/closed.
     * @param {HTMLElement} header
     * @param {HTMLElement} body
     */
    function togglePanel(header, body) {
        var isOpen = header.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            closePanel(header, body);
        } else {
            openPanel(header, body);
        }
    }

    // ── Locked course protection ──────────────────────────────────────────────

    /**
     * Prevents navigation to locked courses (aria-disabled="true").
     * The CSS already sets pointer-events:none but we also block keyboard Enter/Space.
     * @param {HTMLElement} container
     */
    function initLockedCourses(container) {
        container.querySelectorAll('.slp-course-card--locked').forEach(function(card) {
            card.addEventListener('click', function(e) {
                e.preventDefault();
            });
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                }
            });
        });
    }

    // ── Progress bar animation ────────────────────────────────────────────────

    /**
     * Animates progress bars from 0 to their target width using IntersectionObserver.
     * Falls back gracefully if IntersectionObserver is not available.
     * @param {HTMLElement} container
     */
    function animateProgressBars(container) {
        var bars = container.querySelectorAll('.slp-progress-fill, .slp-course-card__progress .slp-progress-fill');

        if (!bars.length) {
            return;
        }

        // Store the target width and start from 0.
        bars.forEach(function(bar) {
            var target = bar.style.width || '0%';
            bar.setAttribute('data-target', target);
            bar.style.width = '0%';
        });

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var bar = entry.target;
                        // Small delay so the animation is visible.
                        setTimeout(function() {
                            bar.style.width = bar.getAttribute('data-target');
                        }, 80);
                        observer.unobserve(bar);
                    }
                });
            }, { threshold: 0.1 });

            bars.forEach(function(bar) {
                observer.observe(bar);
            });
        } else {
            // Fallback: set immediately.
            bars.forEach(function(bar) {
                bar.style.width = bar.getAttribute('data-target');
            });
        }
    }

    // ── Continue button: stop header toggle ───────────────────────────────────

    /**
     * Prevents clicks on .slp-btn-continue from toggling the route header.
     * (onclick="event.stopPropagation()" in the template handles this,
     * but we add a listener for robustness with dynamically-rendered content.)
     * @param {HTMLElement} container
     */
    function initContinueButtons(container) {
        container.querySelectorAll('.slp-btn-continue').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    }

    // ── Keyboard navigation ───────────────────────────────────────────────────

    /**
     * Allows Enter/Space to toggle route headers (they are <button> elements,
     * so browsers handle this natively, but we add it explicitly for clarity).
     * @param {HTMLElement} header
     * @param {HTMLElement} body
     */
    function initHeaderKeyboard(header, body) {
        header.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePanel(header, body);
            }
        });
    }

    // ── Public API ────────────────────────────────────────────────────────────

    return {
        /**
         * Initialises the student view block.
         * @param {string} containerId  The id of the .slp-container element.
         */
        init: function(containerId) {
            var container = document.getElementById(containerId);
            if (!container) {
                Log.warn(MODULE + ': container #' + containerId + ' not found.');
                return;
            }

            Log.debug(MODULE + ': init on #' + containerId);

            // Wire up each route header toggle.
            container.querySelectorAll('.slp-route-header[data-slp-toggle]').forEach(function(header) {
                var targetId = header.getAttribute('data-slp-toggle');
                var body = document.getElementById(targetId);

                if (!body) {
                    Log.warn(MODULE + ': body panel #' + targetId + ' not found.');
                    return;
                }

                // Click toggle.
                header.addEventListener('click', function() {
                    togglePanel(header, body);
                });

                // Keyboard toggle.
                initHeaderKeyboard(header, body);

                // Ensure initial state matches aria-expanded attribute.
                var isExpanded = header.getAttribute('aria-expanded') === 'true';
                if (isExpanded) {
                    body.classList.add('slp-route-body--open');
                }
            });

            // Locked course protection.
            initLockedCourses(container);

            // Continue button isolation.
            initContinueButtons(container);

            // Progress bar entrance animation.
            animateProgressBars(container);
        }
    };
});
