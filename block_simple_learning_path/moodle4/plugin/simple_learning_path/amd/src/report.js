// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AMD module for the learning path statistics report page.
 *
 * Loads Chart.js from CDN and renders:
 *  - Horizontal bar chart: average progress per course.
 *  - Doughnut chart: completion rate per course.
 *  - Route selector: navigates to the selected path's report on change.
 *
 * Chart.js is loaded lazily so it does not affect the main block load time.
 *
 * @module     block_simple_learning_path/report
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/log'], function(Log) {

    'use strict';

    var MODULE = 'block_simple_learning_path/report';

    // Chart.js CDN — only loaded on the report page.
    var CHARTJS_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';

    var cfg = {};

    // ── Chart helpers ─────────────────────────────────────────────────────────

    /**
     * Loads Chart.js dynamically from CDN and resolves when ready.
     * @returns {Promise}
     */
    function loadChartJs() {
        return new Promise(function(resolve, reject) {
            if (window.Chart) {
                resolve(window.Chart);
                return;
            }
            var script  = document.createElement('script');
            script.src  = CHARTJS_CDN;
            script.async = true;
            script.onload  = function() { resolve(window.Chart); };
            script.onerror = function() { reject(new Error('Chart.js failed to load')); };
            document.head.appendChild(script);
        });
    }

    /**
     * Truncates long course names for chart labels.
     * @param {string[]} labels
     * @param {number}   maxLen
     * @returns {string[]}
     */
    function truncateLabels(labels, maxLen) {
        maxLen = maxLen || 28;
        return labels.map(function(l) {
            return l.length > maxLen ? l.substring(0, maxLen - 1) + '…' : l;
        });
    }

    /**
     * Renders the horizontal bar chart (avg progress per course).
     * @param {typeof Chart} Chart
     */
    function renderProgressChart(Chart) {
        var canvas = document.getElementById('slp-chart-progress');
        if (!canvas) {
            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels:   truncateLabels(cfg.labels),
                datasets: [{
                    label:           'Progreso promedio (%)',
                    data:            cfg.avgProgress,
                    backgroundColor: 'rgba(15, 108, 191, 0.75)',
                    borderColor:     'rgba(15, 108, 191, 1)',
                    borderWidth:     1,
                    borderRadius:    4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.parsed.x + '% promedio';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        min: 0,
                        max: 100,
                        ticks: {
                            callback: function(v) { return v + '%'; },
                            font: { size: 11 }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    y: {
                        ticks: { font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    /**
     * Renders the doughnut chart (completion rate per course).
     * @param {typeof Chart} Chart
     */
    function renderCompletionChart(Chart) {
        var canvas = document.getElementById('slp-chart-done');
        if (!canvas) {
            return;
        }

        var colors = [
            '#0f6cbf', '#17a2b8', '#28a745', '#ffc107',
            '#fd7e14', '#6f42c1', '#e83e8c', '#dc3545',
        ];

        // Pad or cycle colors to match number of courses.
        var bgColors = cfg.labels.map(function(_, i) {
            return colors[i % colors.length];
        });

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels:   truncateLabels(cfg.labels),
                datasets: [{
                    label:           '% completaron',
                    data:            cfg.completion,
                    backgroundColor: bgColors.map(function(c) { return c + 'cc'; }),
                    borderColor:     bgColors,
                    borderWidth:     2,
                    hoverOffset:     6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 11 },
                            boxWidth: 12,
                            padding: 12,
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.parsed + '% completaron';
                            }
                        }
                    }
                }
            }
        });
    }

    // ── Route selector navigation ─────────────────────────────────────────────

    function initSelector() {
        var select = document.getElementById(cfg.selectorId);
        if (!select) {
            return;
        }
        select.addEventListener('change', function() {
            var url = select.value;
            if (url) {
                window.location.href = url;
            }
        });
    }

    // ── Public API ────────────────────────────────────────────────────────────

    return {
        /**
         * @param {Object}   config
         * @param {string[]} config.labels      Course names.
         * @param {number[]} config.avgProgress Average progress % per course.
         * @param {number[]} config.completion  Completion % per course.
         * @param {string}   config.selectorId  ID of the route selector <select>.
         */
        init: function(config) {
            cfg = config;
            Log.debug(MODULE + ': init');

            initSelector();

            if (!cfg.labels || cfg.labels.length === 0) {
                Log.debug(MODULE + ': no chart data, skipping charts');
                return;
            }

            loadChartJs()
                .then(function(Chart) {
                    renderProgressChart(Chart);
                    renderCompletionChart(Chart);
                })
                .catch(function(err) {
                    Log.warn(MODULE + ': ' + err.message);
                    // Hide canvas elements gracefully.
                    ['slp-chart-progress', 'slp-chart-done'].forEach(function(id) {
                        var el = document.getElementById(id);
                        if (el) {
                            el.parentElement.innerHTML =
                                '<p class="text-muted small text-center py-3">' +
                                '<i class="fa fa-exclamation-triangle mr-1"></i>' +
                                'Los gráficos no pudieron cargarse. Verificá tu conexión a internet.' +
                                '</p>';
                        }
                    });
                });
        }
    };
});
