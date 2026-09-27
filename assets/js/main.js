/**
 * School Management System - Main Interactive JavaScript Engine
 * Handles Navigation, Live Filtering, UI Alerts, System-Wide Custom Confirmation Modals,
 * Custom Searchable Select Dropdowns, and Custom Calendar Datepickers
 */

(function() {
    'use strict';

    // Global Modal State
    let confirmCallback = null;

    // Active Open Popovers Reference
    let activeOpenDropdown = null;
    let activeOpenCalendar = null;

    /**
     * Helper: Close any open custom popovers
     */
    function closeAllPopovers(exceptElement) {
        if (activeOpenDropdown && activeOpenDropdown !== exceptElement) {
            activeOpenDropdown.classList.remove('open');
            if (activeOpenDropdown.parentElement) {
                activeOpenDropdown.parentElement.classList.remove('open');
                const menu = activeOpenDropdown.parentElement.querySelector('.custom-select-dropdown');
                if (menu) menu.classList.remove('show');
            }
            activeOpenDropdown = null;
        }
        if (activeOpenCalendar && activeOpenCalendar !== exceptElement) {
            activeOpenCalendar.classList.remove('open');
            if (activeOpenCalendar.parentElement) {
                activeOpenCalendar.parentElement.classList.remove('open');
                const popover = activeOpenCalendar.parentElement.querySelector('.custom-calendar-popover');
                if (popover) popover.classList.remove('show');
            }
            activeOpenCalendar = null;
        }
        const attCal = document.getElementById('calendarPopover');
        const attBtn = document.getElementById('calendarPickerBtn');
        if (attCal && attBtn && attBtn !== exceptElement) {
            attCal.classList.remove('show');
            attBtn.classList.remove('open');
        }
        const repMonth = document.getElementById('reportMonthPopover');
        const repBtn = document.getElementById('reportMonthPickerBtn');
        if (repMonth && repBtn && repBtn !== exceptElement) {
            repMonth.classList.remove('show');
            repBtn.classList.remove('open');
        }
    }

    // Global document click to dismiss popovers
    document.addEventListener('click', function(e) {
        const isInsideSelect = e.target.closest('.custom-select-wrapper');
        const isInsideDate = e.target.closest('.custom-date-wrapper');
        const isInsideAtt = e.target.closest('.custom-att-calendar, .custom-att-monthpicker');
        
        if (!isInsideSelect && !isInsideDate && !isInsideAtt) {
            closeAllPopovers(null);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllPopovers(null);
        }
    });

    /* ==========================================================
       1. Custom Confirmation Modal Engine
       ========================================================== */
    function initConfirmModal() {
        let modal = document.getElementById('customConfirmModal');
        if (!modal) {
            const modalHTML = `
            <div id="customConfirmModal" class="custom-modal-backdrop" aria-hidden="true" role="dialog">
                <div class="custom-modal-dialog">
                    <div id="customModalIcon" class="custom-modal-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="28" height="28">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 id="customModalTitle" class="custom-modal-title">Confirm Deletion</h3>
                    <div id="customModalBody" class="custom-modal-body">
                        Are you sure you want to permanently delete this record?
                    </div>
                    <div class="custom-modal-actions">
                        <button type="button" id="customModalCancelBtn" class="btn btn-cancel">
                            Cancel
                        </button>
                        <button type="button" id="customModalConfirmBtn" class="btn btn-confirm-delete">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span id="customModalConfirmBtnText">Yes, Delete</span>
                        </button>
                    </div>
                </div>
            </div>`;
            if (document.body) {
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                modal = document.getElementById('customConfirmModal');
            }
        }

        if (modal && !modal.dataset.bound) {
            modal.dataset.bound = 'true';
            const cancelBtn = document.getElementById('customModalCancelBtn');
            const confirmBtn = document.getElementById('customModalConfirmBtn');

            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeModal);
            }

            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    if (typeof confirmCallback === 'function') {
                        const cb = confirmCallback;
                        confirmCallback = null;
                        closeModal();
                        cb();
                    } else {
                        closeModal();
                    }
                });
            }

            document.addEventListener('keydown', (e) => {
                if (modal && modal.classList.contains('active')) {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        closeModal();
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (confirmBtn) confirmBtn.click();
                    }
                }
            });
        }
        return modal;
    }

    function closeModal() {
        const modal = document.getElementById('customConfirmModal');
        if (modal) {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    window.closeCustomModal = closeModal;
    window.showCustomConfirm = function(options) {
        options = options || {};
        const modal = initConfirmModal();
        if (!modal) return;

        const titleEl = document.getElementById('customModalTitle');
        const bodyEl = document.getElementById('customModalBody');
        const iconEl = document.getElementById('customModalIcon');
        const confirmBtn = document.getElementById('customModalConfirmBtn');
        const confirmBtnText = document.getElementById('customModalConfirmBtnText');
        const title = options.title || 'Confirm Deletion';
        const rawName = options.itemName || '';
        const itemHighlight = rawName ? `<span class="custom-modal-item-highlight">${escapeHTML(rawName)}</span>` : '';
        
        let message = options.message;
        if (!message) {
            if (itemHighlight) {
                message = `Are you sure you want to permanently delete?${itemHighlight}`;
            } else {
                message = 'Are you sure you want to permanently delete this record?';
            }
        }

        const confirmText = options.confirmText || 'Yes, Delete';
        const type = options.type || 'danger';

        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.innerHTML = message;
        if (confirmBtnText) confirmBtnText.textContent = confirmText;

        if (iconEl && confirmBtn) {
            if (type === 'warning') {
                iconEl.className = 'custom-modal-icon warning';
                iconEl.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`;
                confirmBtn.className = 'btn btn-primary';
            } else if (type === 'info') {
                iconEl.className = 'custom-modal-icon info';
                iconEl.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
                confirmBtn.className = 'btn btn-primary';
            } else {
                iconEl.className = 'custom-modal-icon';
                iconEl.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="28" height="28"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>`;
                confirmBtn.className = 'btn btn-confirm-delete';
            }
        }

        confirmCallback = options.onConfirm || null;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    };

    function escapeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.innerText = str;
        return div.innerHTML;
    }

    // Capture-phase delete interceptor
    document.addEventListener('click', function(e) {
        const target = e.target;
        if (!target) return;

        const deleteTrigger = target.closest(
            '.btn-delete-confirm, [data-confirm], a[href*="delete"], button[name*="delete"], input[value="Delete"], .btn-danger, a[href*="user_delete"]'
        );

        if (deleteTrigger) {
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();

            const href = deleteTrigger.getAttribute('href');
            const dataName = deleteTrigger.getAttribute('data-name');
            const customMsg = deleteTrigger.getAttribute('data-confirm');
            const customTitle = deleteTrigger.getAttribute('data-title') || 'Confirm Deletion';
            const actionType = deleteTrigger.getAttribute('data-type') || (customMsg && !href?.includes('delete') ? 'warning' : 'danger');

            let itemName = dataName;
            if (!itemName && !customMsg) {
                const row = deleteTrigger.closest('tr');
                if (row) {
                    const textCells = Array.from(row.querySelectorAll('td')).map(td => td.innerText.trim()).filter(t => t.length > 0 && !t.includes('Edit') && !t.includes('Delete'));
                    if (textCells.length > 0) {
                        itemName = textCells[1] || textCells[0];
                    }
                }
            }

            window.showCustomConfirm({
                title: customTitle,
                itemName: itemName,
                message: customMsg,
                type: actionType,
                confirmText: actionType === 'warning' ? 'Confirm Action' : 'Yes, Delete',
                onConfirm: function() {
                    if (href && href !== '#' && !href.startsWith('javascript:')) {
                        window.location.href = href;
                    } else if (deleteTrigger.tagName === 'BUTTON' && deleteTrigger.type === 'submit') {
                        const form = deleteTrigger.closest('form');
                        if (form) form.submit();
                    } else if (deleteTrigger.tagName === 'FORM') {
                        deleteTrigger.submit();
                    }
                }
            });
        }
    }, true);

    /* ==========================================================
       2. Custom Select Dropdown Component Engine
       Feature: Search input appears automatically if options > 10
       ========================================================== */
    function initCustomSelects() {
        const selects = document.querySelectorAll('select:not([data-customized="true"])');
        
        selects.forEach(select => {
            // Do not alter attendance module custom calendar widgets or explicitly excluded elements
            if (select.closest('.custom-att-calendar, .custom-att-monthpicker') || select.classList.contains('no-custom-select')) {
                return;
            }

            select.dataset.customized = 'true';

            const options = Array.from(select.options);
            const optionCount = options.length;
            const hasSearch = optionCount > 10; // User requirement: Add search field only if options > 10
            
            const isCompact = select.style.width === 'auto' || select.closest('.table-responsive, .card-header, .filter-bar, .grid-stats');
            const selectedOpt = select.options[select.selectedIndex] || options[0];
            const initialText = selectedOpt ? selectedOpt.text : 'Select...';

            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.className = `custom-select-wrapper ${isCompact ? 'compact' : ''}`;
            if (select.style.width && select.style.width !== '100%') {
                wrapper.style.width = select.style.width;
                wrapper.style.minWidth = '180px';
            }

            // Build trigger HTML
            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'custom-select-trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.innerHTML = `
                <div class="custom-select-left">
                    <div class="custom-select-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <span class="custom-select-label">${escapeHTML(initialText)}</span>
                </div>
                <svg class="custom-select-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="15" height="15">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            `;

            // Build dropdown popover
            const dropdown = document.createElement('div');
            dropdown.className = 'custom-select-dropdown';

            let dropdownHTML = `
                <div class="custom-select-header">
                    <span>Select Option</span>
                    <span class="count-pill">${optionCount} items</span>
                </div>
            `;

            if (hasSearch) {
                dropdownHTML += `
                <div class="custom-select-search-wrap">
                    <svg class="custom-select-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" class="custom-select-search-input" placeholder="Search ${optionCount} options...">
                </div>
                `;
            }

            dropdownHTML += `<div class="custom-select-options-list"></div>`;
            dropdownHTML += `<div class="custom-select-empty" style="display: none;">No matching options found</div>`;

            dropdown.innerHTML = dropdownHTML;
            const optionsList = dropdown.querySelector('.custom-select-options-list');
            const emptyMsg = dropdown.querySelector('.custom-select-empty');
            const searchInput = dropdown.querySelector('.custom-select-search-input');
            const labelEl = trigger.querySelector('.custom-select-label');

            // Render option items
            function renderOptions() {
                optionsList.innerHTML = '';
                options.forEach((opt, idx) => {
                    const isSelected = opt.selected || select.selectedIndex === idx;
                    const optItem = document.createElement('div');
                    optItem.className = `custom-select-option ${isSelected ? 'selected' : ''}`;
                    optItem.dataset.value = opt.value;
                    optItem.dataset.index = idx;
                    optItem.innerHTML = `
                        <span>${escapeHTML(opt.text)}</span>
                        ${isSelected ? '<span class="opt-check">✓</span>' : ''}
                    `;

                    optItem.addEventListener('click', (e) => {
                        e.stopPropagation();
                        select.selectedIndex = idx;
                        select.value = opt.value;
                        labelEl.textContent = opt.text;

                        // Update selected UI
                        optionsList.querySelectorAll('.custom-select-option').forEach(el => {
                            el.classList.remove('selected');
                            const chk = el.querySelector('.opt-check');
                            if (chk) chk.remove();
                        });
                        optItem.classList.add('selected');
                        optItem.insertAdjacentHTML('beforeend', '<span class="opt-check">✓</span>');

                        // Trigger change & input events on original select
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        select.dispatchEvent(new Event('input', { bubbles: true }));

                        closeDropdown();
                    });

                    optionsList.appendChild(optItem);
                });
            }
            renderOptions();

            // Search filtering handler
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const q = e.target.value.toLowerCase().trim();
                    let matchCount = 0;
                    const optItems = optionsList.querySelectorAll('.custom-select-option');

                    optItems.forEach(item => {
                        const txt = item.textContent.toLowerCase();
                        if (txt.includes(q)) {
                            item.style.display = 'flex';
                            matchCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    emptyMsg.style.display = matchCount === 0 ? 'block' : 'none';
                });

                searchInput.addEventListener('click', (e) => e.stopPropagation());
            }

            function openDropdown() {
                closeAllPopovers(trigger);
                
                // Smart auto-flip & boundary positioning
                const rect = trigger.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                const spaceAbove = rect.top;
                
                if (spaceBelow < 280 && spaceAbove > spaceBelow) {
                    dropdown.classList.add('dropup');
                } else {
                    dropdown.classList.remove('dropup');
                }

                if (rect.left + 260 > window.innerWidth - 20) {
                    dropdown.classList.add('align-right');
                } else {
                    dropdown.classList.remove('align-right');
                }

                wrapper.classList.add('open');
                trigger.classList.add('open');
                dropdown.classList.add('show');
                activeOpenDropdown = trigger;

                if (searchInput) {
                    searchInput.value = '';
                    optionsList.querySelectorAll('.custom-select-option').forEach(it => it.style.display = 'flex');
                    emptyMsg.style.display = 'none';
                    setTimeout(() => searchInput.focus(), 80);
                }
            }

            function closeDropdown() {
                wrapper.classList.remove('open');
                trigger.classList.remove('open');
                dropdown.classList.remove('show');
                if (activeOpenDropdown === trigger) activeOpenDropdown = null;
            }

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (dropdown.classList.contains('show')) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            });

            // Keep native select hidden and synced
            select.style.position = 'absolute';
            select.style.opacity = '0';
            select.style.width = '0px';
            select.style.height = '0px';
            select.style.pointerEvents = 'none';
            select.style.zIndex = '-1';
            select.tabIndex = -1;

            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);
            wrapper.appendChild(trigger);
            wrapper.appendChild(dropdown);

            // Sync on external select change
            select.addEventListener('change', () => {
                const curOpt = select.options[select.selectedIndex];
                if (curOpt) {
                    labelEl.textContent = curOpt.text;
                    renderOptions();
                }
            });
        });
    }

    /* ==========================================================
       3. Custom Calendar Datepicker Component Engine
       ========================================================== */
    const MONTH_NAMES = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const DAY_NAMES_SHORT = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

    function formatDateDisplay(dateStr) {
        if (!dateStr) return 'Select Date';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        if (isNaN(d.getTime())) return dateStr;
        return `${DAY_NAMES_SHORT[d.getDay()]}, ${MONTH_NAMES[d.getMonth()].slice(0, 3)} ${d.getDate()}, ${d.getFullYear()}`;
    }

    function initCustomDatepickers() {
        const dateInputs = document.querySelectorAll('input[type="date"]:not([data-customized="true"])');

        dateInputs.forEach(input => {
            if (input.closest('.attendance-filter-bar, .custom-att-calendar') || input.classList.contains('no-custom-date')) {
                return;
            }

            input.dataset.customized = 'true';

            let curDateVal = input.value || '';
            let viewYear, viewMonth;

            if (curDateVal && curDateVal.includes('-')) {
                const p = curDateVal.split('-');
                viewYear = parseInt(p[0], 10) || new Date().getFullYear();
                viewMonth = (parseInt(p[1], 10) - 1) || new Date().getMonth();
            } else {
                const now = new Date();
                viewYear = now.getFullYear();
                viewMonth = now.getMonth();
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'custom-date-wrapper';
            if (input.style.width && input.style.width !== '100%') {
                wrapper.style.width = input.style.width;
            }

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'custom-date-trigger';
            trigger.innerHTML = `
                <div class="custom-date-left">
                    <div class="custom-date-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="custom-date-label">${escapeHTML(formatDateDisplay(curDateVal))}</span>
                </div>
                <svg class="custom-date-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="15" height="15">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            `;

            const popover = document.createElement('div');
            popover.className = 'custom-calendar-popover';

            function renderCalendar() {
                const today = new Date();
                const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

                const firstDayOfMonth = new Date(viewYear, viewMonth, 1).getDay();
                const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
                const daysInPrevMonth = new Date(viewYear, viewMonth, 0).getDate();

                let monthItemsHTML = '';
                MONTH_NAMES.forEach((mName, mIdx) => {
                    const isSel = mIdx === viewMonth;
                    monthItemsHTML += `<div class="cal-picker-item ${isSel ? 'selected' : ''}" data-month="${mIdx}">
                        <span>${mName}</span>
                        ${isSel ? '<span class="cal-picker-check">✓</span>' : ''}
                    </div>`;
                });

                let yearItemsHTML = '';
                const currentYear = new Date().getFullYear();
                const startYear = currentYear - 75;
                const endYear = currentYear + 15;
                for (let y = startYear; y <= endYear; y++) {
                    const isSel = y === viewYear;
                    yearItemsHTML += `<div class="cal-picker-item ${isSel ? 'selected' : ''}" data-year="${y}">
                        <span>${y}</span>
                        ${isSel ? '<span class="cal-picker-check">✓</span>' : ''}
                    </div>`;
                }

                let calHTML = `
                    <div class="cal-header-bar">
                        <button type="button" class="cal-nav-btn cal-prev" title="Previous Month">◀</button>
                        <div class="cal-selects-wrap">
                            <div class="cal-custom-picker cal-month-picker">
                                <button type="button" class="cal-picker-btn cal-month-btn" aria-haspopup="listbox">
                                    <span>${MONTH_NAMES[viewMonth]}</span>
                                    <svg class="cal-picker-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div class="cal-picker-menu cal-month-menu">
                                    ${monthItemsHTML}
                                </div>
                            </div>
                            <div class="cal-custom-picker cal-year-picker">
                                <button type="button" class="cal-picker-btn cal-year-btn" aria-haspopup="listbox">
                                    <span>${viewYear}</span>
                                    <svg class="cal-picker-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div class="cal-picker-menu cal-year-menu">
                                    ${yearItemsHTML}
                                </div>
                            </div>
                        </div>
                        <button type="button" class="cal-nav-btn cal-next" title="Next Month">▶</button>
                    </div>
                    <div class="cal-weekdays">
                        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                    </div>
                    <div class="cal-days">
                `;

                // Previous month padding days
                for (let i = firstDayOfMonth - 1; i >= 0; i--) {
                    const prevD = daysInPrevMonth - i;
                    calHTML += `<div class="cal-day out-of-month" data-action="prev-month-day" data-day="${prevD}">${prevD}</div>`;
                }

                // Current month days
                for (let day = 1; day <= daysInMonth; day++) {
                    const dateISO = `${viewYear}-${String(viewMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const isSelected = input.value === dateISO;
                    const isToday = dateISO === todayStr;

                    let classes = 'cal-day';
                    if (isSelected) classes += ' selected-day';
                    if (isToday) classes += ' today-day';

                    calHTML += `<div class="${classes}" data-date="${dateISO}">${day}</div>`;
                }

                // Next month padding days
                const totalCells = firstDayOfMonth + daysInMonth;
                const nextMonthDays = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
                for (let day = 1; day <= nextMonthDays; day++) {
                    calHTML += `<div class="cal-day out-of-month" data-action="next-month-day" data-day="${day}">${day}</div>`;
                }

                calHTML += `
                    </div>
                    <div class="cal-shortcuts">
                        <button type="button" class="cal-shortcut-btn" data-act="today">Today</button>
                        <button type="button" class="cal-shortcut-btn" data-act="clear">Clear</button>
                    </div>
                `;

                popover.innerHTML = calHTML;

                // Event Bindings for Month & Year Custom Pickers
                const monthBtn = popover.querySelector('.cal-month-btn');
                const monthMenu = popover.querySelector('.cal-month-menu');
                const yearBtn = popover.querySelector('.cal-year-btn');
                const yearMenu = popover.querySelector('.cal-year-menu');

                function closePickerMenus() {
                    if (monthBtn && monthMenu) {
                        monthBtn.classList.remove('open');
                        monthMenu.classList.remove('open');
                    }
                    if (yearBtn && yearMenu) {
                        yearBtn.classList.remove('open');
                        yearMenu.classList.remove('open');
                    }
                }

                if (monthBtn && monthMenu) {
                    monthBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const isOpen = monthMenu.classList.contains('open');
                        closePickerMenus();
                        if (!isOpen) {
                            monthBtn.classList.add('open');
                            monthMenu.classList.add('open');
                            const sel = monthMenu.querySelector('.cal-picker-item.selected');
                            if (sel) sel.scrollIntoView({ block: 'nearest' });
                        }
                    });
                }

                if (yearBtn && yearMenu) {
                    yearBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const isOpen = yearMenu.classList.contains('open');
                        closePickerMenus();
                        if (!isOpen) {
                            yearBtn.classList.add('open');
                            yearMenu.classList.add('open');
                            const sel = yearMenu.querySelector('.cal-picker-item.selected');
                            if (sel) sel.scrollIntoView({ block: 'center' });
                        }
                    });
                }

                popover.querySelectorAll('.cal-month-menu .cal-picker-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.stopPropagation();
                        viewMonth = parseInt(item.getAttribute('data-month'), 10);
                        renderCalendar();
                    });
                });

                popover.querySelectorAll('.cal-year-menu .cal-picker-item').forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.stopPropagation();
                        viewYear = parseInt(item.getAttribute('data-year'), 10);
                        renderCalendar();
                    });
                });

                popover.querySelector('.cal-prev').addEventListener('click', (e) => {
                    e.stopPropagation();
                    viewMonth--;
                    if (viewMonth < 0) {
                        viewMonth = 11;
                        viewYear--;
                    }
                    renderCalendar();
                });

                popover.querySelector('.cal-next').addEventListener('click', (e) => {
                    e.stopPropagation();
                    viewMonth++;
                    if (viewMonth > 11) {
                        viewMonth = 0;
                        viewYear++;
                    }
                    renderCalendar();
                });

                popover.querySelectorAll('.cal-day:not(.out-of-month)').forEach(dayCell => {
                    dayCell.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const pickedDate = dayCell.getAttribute('data-date');
                        selectDate(pickedDate);
                    });
                });

                // Dismiss mini pickers if clicking within calendar body
                popover.addEventListener('click', (e) => {
                    if (!e.target.closest('.cal-custom-picker')) {
                        closePickerMenus();
                    }
                });

                popover.querySelector('[data-act="today"]').addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectDate(todayStr);
                });

                popover.querySelector('[data-act="clear"]').addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectDate('');
                });
            }

            function selectDate(dateISO) {
                input.value = dateISO;
                trigger.querySelector('.custom-date-label').textContent = formatDateDisplay(dateISO);
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('input', { bubbles: true }));
                closeCalendar();
            }

            function openCalendar() {
                closeAllPopovers(trigger);

                // Smart auto-flip & boundary positioning
                const rect = trigger.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                const spaceAbove = rect.top;

                if (spaceBelow < 340 && spaceAbove > spaceBelow) {
                    popover.classList.add('dropup');
                } else {
                    popover.classList.remove('dropup');
                }

                if (rect.left + 310 > window.innerWidth - 20) {
                    popover.classList.add('align-right');
                } else {
                    popover.classList.remove('align-right');
                }

                wrapper.classList.add('open');
                trigger.classList.add('open');
                popover.classList.add('show');
                activeOpenCalendar = trigger;
                renderCalendar();
            }

            function closeCalendar() {
                wrapper.classList.remove('open');
                trigger.classList.remove('open');
                popover.classList.remove('show');
                if (activeOpenCalendar === trigger) activeOpenCalendar = null;
            }

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (popover.classList.contains('show')) {
                    closeCalendar();
                } else {
                    openCalendar();
                }
            });

            // Hide native date input
            input.style.position = 'absolute';
            input.style.opacity = '0';
            input.style.width = '0px';
            input.style.height = '0px';
            input.style.pointerEvents = 'none';
            input.style.zIndex = '-1';
            input.tabIndex = -1;

            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            wrapper.appendChild(trigger);
            wrapper.appendChild(popover);

            input.addEventListener('change', () => {
                trigger.querySelector('.custom-date-label').textContent = formatDateDisplay(input.value);
            });
        });
    }

    /* ==========================================================
       4. Theme Toggle Engine (Dark / Light Mode)
       ========================================================== */
    function initThemeToggle() {
        const themeBtn = document.getElementById('themeToggleBtn');
        const textLabel = themeBtn ? themeBtn.querySelector('.theme-toggle-label') : null;
        const rootPath = window.SMS_ROOT_PATH || '';

        function applyTheme(theme, saveRemote) {
            document.documentElement.setAttribute('data-theme', theme);
            if (document.body) {
                if (theme === 'light') {
                    document.body.classList.add('light-theme');
                } else {
                    document.body.classList.remove('light-theme');
                }
            }
            if (textLabel) {
                textLabel.textContent = (theme === 'light') ? 'Light' : 'Dark';
            }
            if (themeBtn) {
                themeBtn.setAttribute('data-current', theme);
            }

            try {
                localStorage.setItem('sms_theme', theme);
            } catch(e) {}

            if (saveRemote) {
                const endpoint = rootPath + 'api/update_theme.php';
                const formData = new FormData();
                formData.append('theme', theme);

                fetch(endpoint, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .catch(err => {
                    console.warn('Theme preference sync:', err);
                });
            }
        }

        const initialTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('sms_theme') || 'dark';
        applyTheme(initialTheme, false);

        if (themeBtn && !themeBtn.dataset.bound) {
            themeBtn.dataset.bound = 'true';
            themeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const current = document.documentElement.getAttribute('data-theme') || 'dark';
                const nextTheme = (current === 'light') ? 'dark' : 'light';
                applyTheme(nextTheme, true);
            });
        }
    }

    /* ==========================================================
       5. App Initialization (Sidebar, Search, Alerts, Widgets)
       ========================================================== */
    function initAppFeatures() {
        initThemeToggle();
        initConfirmModal();
        initCustomSelects();
        initCustomDatepickers();

        // 1. Mobile Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
            });

            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 900 && 
                    !sidebar.contains(e.target) && 
                    !sidebarToggle.contains(e.target) && 
                    sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            });
        }

        // 2. Client-side Live Search for Tables
        const tableSearch = document.getElementById('tableSearch');
        if (tableSearch) {
            tableSearch.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                const tableRows = document.querySelectorAll('.data-table tbody tr');
                
                tableRows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }

        // 3. Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                alert.style.transition = 'all 0.4s ease';
                setTimeout(() => alert.remove(), 400);
            }, 5000);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAppFeatures);
    } else {
        initAppFeatures();
    }
})();
