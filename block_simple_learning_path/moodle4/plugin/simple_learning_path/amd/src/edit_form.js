// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AMD module for the learning path edit/create form.
 *
 * Handles:
 *   - Dynamic course list filtered by category and search term.
 *   - Adding / removing courses from the selected list.
 *   - Showing / hiding cohort selector based on selected criteria.
 *   - Showing / hiding date fields based on "fecha" criteria.
 *   - Showing / hiding role selector based on "rol" criteria.
 *   - Live counter of selected courses.
 *
 * @module     block_simple_learning_path/edit_form
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/log'], function(Log) {

    'use strict';

    /** @type {Object} Map of categoryId → [{id, fullname, categoryname}] */
    var coursesByCategory = {};

    /** @type {string} Unique prefix for all element IDs in this form instance */
    var uid = '';

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns a DOM element by its id (prefixed with the unique id).
     * @param {string} suffix
     * @returns {HTMLElement|null}
     */
    function el(suffix) {
        return document.getElementById(uid + '-' + suffix);
    }

    /**
     * Returns whether a course is already in the selected list.
     * @param {string|number} courseId
     * @returns {boolean}
     */
    function isCourseAdded(courseId) {
        var list = el('added-courses');
        return list ? list.querySelector('[data-course-id="' + courseId + '"]') !== null : false;
    }

    /**
     * Updates the course <select> options based on current category and search.
     */
    function updateCourseSelect() {
        var categorySelect = el('category-select');
        var courseSelect   = el('course-select');
        var courseSearch   = el('course-search');
        var addBtn         = el('add-course');
        var resetBtn       = el('reset-search');

        if (!categorySelect || !courseSelect) {
            return;
        }

        var selectedCategory = categorySelect.value;
        var searchQuery      = (courseSearch ? courseSearch.value : '').toLowerCase().trim();

        courseSelect.innerHTML = '';
        var hasOptions = false;

        var categoryCourses = coursesByCategory[selectedCategory] || [];
        categoryCourses.forEach(function(course) {
            if (isCourseAdded(course.id)) {
                return;
            }
            var nameMatch = course.fullname.toLowerCase().indexOf(searchQuery) !== -1;
            var idMatch   = String(course.id).indexOf(searchQuery) !== -1;
            if (!searchQuery || nameMatch || idMatch) {
                var option       = document.createElement('option');
                option.value     = course.id;
                option.textContent = course.id + ' - ' + course.categoryname + ' - ' + course.fullname;
                courseSelect.appendChild(option);
                hasOptions = true;
            }
        });

        // Deselect all and disable add button until user picks one.
        courseSelect.selectedIndex = -1;
        if (addBtn) {
            addBtn.disabled = true;
        }
        if (resetBtn) {
            resetBtn.disabled = !courseSearch || courseSearch.value.trim() === '';
        }

        if (!hasOptions) {
            Log.debug('block_simple_learning_path/edit_form: no courses for category ' + selectedCategory);
        }
    }

    /**
     * Adds a course to the selected-courses list.
     * @param {string|number} courseId
     * @param {string} categoryName
     * @param {string} courseName
     */
    function addCourseToList(courseId, categoryName, courseName) {
        if (isCourseAdded(courseId)) {
            return;
        }

        var list = el('added-courses');
        if (!list) {
            return;
        }

        var li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center slp-course-item';
        li.setAttribute('data-course-id', courseId);
        li.setAttribute('draggable', 'true');

        var handle = document.createElement('span');
        handle.className = 'slp-drag-handle mr-2 text-muted';
        handle.innerHTML = '&#9776;';
        handle.title = 'Arrastrar para reordenar';

        var info = document.createElement('span');
        info.className = 'flex-grow-1';
        info.textContent = courseId + ' – ' + categoryName + ' – ' + courseName;

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-danger btn-sm ml-2';
        btn.setAttribute('data-remove-course', courseId);
        btn.setAttribute('aria-label', 'Eliminar curso');
        btn.innerHTML = '<i class="fa fa-times" aria-hidden="true"></i>';

        var input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = 'category_course[]';
        input.value = courseId;

        li.appendChild(handle);
        li.appendChild(info);
        li.appendChild(btn);
        li.appendChild(input);
        list.appendChild(li);

        updateCourseCounter();
        initDragSort();
    }

    /**
     * Removes a course from the selected-courses list.
     * @param {string|number} courseId
     */
    function removeCourseFromList(courseId) {
        var list = el('added-courses');
        if (!list) {
            return;
        }
        var item = list.querySelector('[data-course-id="' + courseId + '"]');
        if (item) {
            item.remove();
            updateCourseCounter();
            updateCourseSelect();
        }
    }

    /**
     * Updates the live counter badge showing how many courses are selected.
     */
    function updateCourseCounter() {
        var list    = el('added-courses');
        var counter = el('course-counter');
        if (!list || !counter) {
            return;
        }
        var count = list.querySelectorAll('[data-course-id]').length;
        counter.textContent = count + ' curso' + (count !== 1 ? 's' : '') + ' seleccionado' + (count !== 1 ? 's' : '');
        counter.style.display = count > 0 ? 'inline-block' : 'none';
    }

    // ── Criteria visibility ───────────────────────────────────────────────────

    /**
     * Shows / hides the extra fields that depend on the selected criteria radio.
     */
    function updateCriteriaFields() {
        var checked = document.querySelector('input[name="criterio"]:checked');
        if (!checked) {
            return;
        }
        var value = checked.value;

        var cohortContainer = document.getElementById('cohort-select-container');
        var dateContainer   = document.getElementById('date-range-container');
        var rolContainer    = document.getElementById('rol-select-container');
        var prereqContainer = document.getElementById('prereq-container');

        if (cohortContainer) {
            cohortContainer.style.display = (value === 'cohorte')      ? 'block' : 'none';
        }
        if (dateContainer) {
            dateContainer.style.display   = (value === 'fecha')        ? 'block' : 'none';
        }
        if (rolContainer) {
            rolContainer.style.display    = (value === 'rol')          ? 'block' : 'none';
        }
        if (prereqContainer) {
            prereqContainer.style.display = (value === 'prerequisito') ? 'block' : 'none';
        }
    }

    // ── Drag & drop sort ──────────────────────────────────────────────────────

    var dragSrc = null;

    function initDragSort() {
        var list = el('added-courses');
        if (!list) {
            return;
        }
        var items = list.querySelectorAll('.slp-course-item');
        items.forEach(function(item) {
            item.removeEventListener('dragstart', onDragStart);
            item.removeEventListener('dragover',  onDragOver);
            item.removeEventListener('drop',      onDrop);
            item.removeEventListener('dragend',   onDragEnd);
            item.addEventListener('dragstart', onDragStart);
            item.addEventListener('dragover',  onDragOver);
            item.addEventListener('drop',      onDrop);
            item.addEventListener('dragend',   onDragEnd);
        });
    }

    function onDragStart(e) {
        dragSrc = this;
        e.dataTransfer.effectAllowed = 'move';
        this.style.opacity = '0.4';
    }

    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    function onDrop(e) {
        e.stopPropagation();
        if (dragSrc !== this) {
            var list = dragSrc.parentNode;
            var allItems = Array.from(list.querySelectorAll('.slp-course-item'));
            var srcIdx  = allItems.indexOf(dragSrc);
            var dstIdx  = allItems.indexOf(this);
            if (srcIdx < dstIdx) {
                list.insertBefore(dragSrc, this.nextSibling);
            } else {
                list.insertBefore(dragSrc, this);
            }
        }
        return false;
    }

    function onDragEnd() {
        this.style.opacity = '1';
        dragSrc = null;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    return {
        /**
         * Initialises the form.
         *
         * @param {string} uniqid  Unique ID prefix matching element IDs in the template.
         * @param {Object} coursesData  Map of categoryId → course array (from PHP).
         */
        init: function(uniqid, coursesData) {
            uid              = uniqid;
            coursesByCategory = coursesData || {};

            var categorySelect = el('category-select');
            var courseSelect   = el('course-select');
            var courseSearch   = el('course-search');
            var addBtn         = el('add-course');
            var resetBtn       = el('reset-search');
            var list           = el('added-courses');

            if (!categorySelect) {
                Log.error('block_simple_learning_path/edit_form: category select not found for uid=' + uid);
                return;
            }

            // Category change → refresh course list.
            categorySelect.addEventListener('change', function() {
                if (courseSearch) {
                    courseSearch.value = '';
                }
                updateCourseSelect();
            });

            // Search input → live filter.
            if (courseSearch) {
                courseSearch.addEventListener('input', updateCourseSelect);
            }

            // Course select → enable/disable add button.
            if (courseSelect) {
                courseSelect.addEventListener('change', function() {
                    if (addBtn) {
                        addBtn.disabled = courseSelect.selectedIndex === -1;
                    }
                });
            }

            // Add course button.
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    if (!courseSelect || courseSelect.selectedIndex === -1) {
                        return;
                    }
                    var option   = courseSelect.options[courseSelect.selectedIndex];
                    var courseId = option.value;
                    var parts    = option.textContent.split(' - ');
                    var catName  = parts.length >= 2 ? parts[1] : '';
                    var courseName = parts.length >= 3 ? parts.slice(2).join(' - ') : parts[0];
                    addCourseToList(courseId, catName, courseName);
                    if (courseSearch) {
                        courseSearch.value = '';
                    }
                    updateCourseSelect();
                });
            }

            // Reset search button.
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    if (courseSearch) {
                        courseSearch.value = '';
                    }
                    updateCourseSelect();
                });
            }

            // Remove course (delegated to list).
            if (list) {
                list.addEventListener('click', function(e) {
                    var target = e.target.closest('[data-remove-course]');
                    if (target) {
                        removeCourseFromList(target.getAttribute('data-remove-course'));
                    }
                });
            }

            // Criteria radio buttons.
            document.querySelectorAll('input[name="criterio"]').forEach(function(radio) {
                radio.addEventListener('change', updateCriteriaFields);
            });

            // Initial state.
            updateCourseSelect();
            updateCriteriaFields();
            updateCourseCounter();
            initDragSort();
        }
    };
});
