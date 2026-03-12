
document.addEventListener('DOMContentLoaded', function() {

    // ========================================
    // GLOBAL STATE
    // ========================================

    let currentView = 'simplepco_*-view-list';
    let previousView = 'simplepco-view-list';
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let allEventsData = [];
    let currentEventIndex = -1;

    // List View pagination
    const EVENTS_PER_PAGE = 15;
    let visibleEventCount = EVENTS_PER_PAGE;

    // ========================================
    // TIME FORMATTER (10:00 am -> 10am)
    // ========================================

    function formatTime(timeStr) {
        if (!timeStr || timeStr === 'All Day' || timeStr === 'N/A') {
            return timeStr;
        }
        return timeStr.replace(/\s+(am|pm)/gi, '$1').replace(/:00/g, '');
    }

    // ========================================
    // VIEW SWITCHER
    // ========================================

    const viewButtons = document.querySelectorAll('.simplepco-view-btn');
    const viewSections = document.querySelectorAll('.simplepco-view-section');

    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetView = this.getAttribute('data-target');
            switchView(targetView);
        });
    });

    function switchView(viewId) {
        if (currentView !== 'simplepco-view-detail') {
            previousView = currentView;
        }

        viewButtons.forEach(b => b.classList.remove('active'));
        const targetBtn = document.querySelector(`[data-target="${viewId}"]`);
        if (targetBtn) targetBtn.classList.add('active');

        viewSections.forEach(section => {
            section.classList.remove('active');
            if (section.id === viewId) {
                section.classList.add('active');
            }
        });

        document.body.classList.remove('simplepco-view-list-active', 'simplepco-view-month-active', 'simplepco-view-gallery-active', 'simplepco-view-detail-active');
        document.body.classList.add(viewId + '-active');

        currentView = viewId;

        if (viewId === 'simplepco-view-month') {
            renderMonthView();
        }
    }

    // ========================================
    // MINI CALENDAR (SIDEBAR) - FIXED VERSION
    // ========================================

    const calendarGrid = document.querySelector('.simplepco-mini-cal-grid');
    const monthDisplay = document.querySelector('.simplepco-mini-cal-month-display');
    const navButtons = document.querySelectorAll('.simplepco-mini-cal-nav');

    let miniCalMonth = new Date().getMonth();
    let miniCalYear = new Date().getFullYear();
    let allEventsFromPage = [];

    // Collect event dates - call this AFTER pcoExpandedEvents is available
    function collectEventDates() {
        allEventsFromPage = [];

        // Method 1: From pcoExpandedEvents (preferred - includes multi-day events)
        if (window.pcoExpandedEvents) {
            allEventsFromPage = Object.keys(window.pcoExpandedEvents);
        }

        // Method 2: Also collect from DOM elements as backup
        document.querySelectorAll('.simplepco-event-date[data-date], .simplepco-day-header[data-date]').forEach(el => {
            const date = el.getAttribute('data-date');
            if (date && !allEventsFromPage.includes(date)) {
                allEventsFromPage.push(date);
            }
        });

        console.log('Mini calendar found events for ' + allEventsFromPage.length + ' dates');
    }

    function renderMiniCalendar(month, year) {
        if (!calendarGrid) return;

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];

        if (monthDisplay) {
            monthDisplay.textContent = `${monthNames[month]} ${year}`;
        }

        // CRITICAL: Completely clear and rebuild the grid each time
        calendarGrid.innerHTML = '';

        // Add day headers (S M T W T F S) - recreate fresh each time
        const dayNames = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
        dayNames.forEach(day => {
            const header = document.createElement('span');
            header.className = 'simplepco-day-header-cell';
            header.textContent = day;
            calendarGrid.appendChild(header);
        });

        // Add empty cells BEFORE the first day of the month
        for (let i = 0; i < firstDayOfMonth; i++) {
            const empty = document.createElement('span');
            empty.className = 'simplepco-empty-cell';
            calendarGrid.appendChild(empty);
        }

        // Add the date cells
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const hasEvents = allEventsFromPage.includes(dateStr);
            const isToday = (day === today.getDate() &&
                month === today.getMonth() &&
                year === today.getFullYear());

            const dateCell = document.createElement('span');
            dateCell.className = 'simplepco-calendar-date';
            dateCell.textContent = day;

            if (isToday) {
                dateCell.classList.add('today');
            }

            if (hasEvents) {
                dateCell.classList.add('has-events');
                dateCell.setAttribute('tabindex', '0');
                dateCell.setAttribute('role', 'button');

                const indicator = document.createElement('span');
                indicator.className = 'event-indicator';
                indicator.textContent = '.';
                dateCell.appendChild(indicator);

                dateCell.addEventListener('click', () => scrollToDate(dateStr));
                dateCell.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        scrollToDate(dateStr);
                    }
                });
            }

            calendarGrid.appendChild(dateCell);
        }
    }

    function scrollToDate(dateStr) {
        switchView('simplepco-view-list');
        setTimeout(() => {
            const targetSection = document.querySelector(`.simplepco-day-header[data-date="${dateStr}"], .simplepco-event-date[data-date="${dateStr}"]`);
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                targetSection.classList.add('simplepco-highlight');
                setTimeout(() => targetSection.classList.remove('simplepco-highlight'), 2000);
            }
        }, 100);
    }

    navButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const direction = this.getAttribute('data-nav');
            if (direction === 'prev') {
                miniCalMonth--;
                if (miniCalMonth < 0) {
                    miniCalMonth = 11;
                    miniCalYear--;
                }
            } else {
                miniCalMonth++;
                if (miniCalMonth > 11) {
                    miniCalMonth = 0;
                    miniCalYear++;
                }
            }
            renderMiniCalendar(miniCalMonth, miniCalYear);
        });
    });

    // ========================================
    // LIST VIEW - SHOW 15 EVENTS + LOAD MORE
    // ========================================

    function initListViewPagination() {
        const eventItems = document.querySelectorAll('#simplepco-view-list .simplepco-event-item');
        const dayHeaders = document.querySelectorAll('#simplepco-view-list .simplepco-day-header');
        const monthHeaders = document.querySelectorAll('#simplepco-view-list .simplepco-month-header');

        if (eventItems.length === 0) return;

        // Create load more button if it doesn't exist
        let loadMoreBtn = document.getElementById('simplepco-load-more-btn');
        if (!loadMoreBtn) {
            loadMoreBtn = document.createElement('button');
            loadMoreBtn.id = 'simplepco-load-more-btn';
            loadMoreBtn.className = 'simplepco-load-more-btn';
            loadMoreBtn.textContent = 'Load More Events';

            // Insert after the last event item
            const listView = document.getElementById('simplepco-view-list');
            if (listView) {
                listView.appendChild(loadMoreBtn);
            }
        }

        // Track which dates have visible events
        function updateVisibility() {
            const visibleDates = new Set();
            const visibleMonths = new Set();
            let shownCount = 0;

            // First pass: determine which events to show
            eventItems.forEach((item, index) => {
                if (index < visibleEventCount) {
                    item.style.display = '';
                    shownCount++;

                    // Find the associated day header
                    let prevSibling = item.previousElementSibling;
                    while (prevSibling) {
                        if (prevSibling.classList.contains('simplepco-day-header')) {
                            visibleDates.add(prevSibling.getAttribute('data-date'));
                            break;
                        }
                        if (prevSibling.classList.contains('simplepco-month-header')) {
                            visibleMonths.add(prevSibling.textContent);
                            break;
                        }
                        prevSibling = prevSibling.previousElementSibling;
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            // Second pass: show/hide headers based on visible events
            dayHeaders.forEach(header => {
                const dateKey = header.getAttribute('data-date');
                // Check if any visible event comes after this header
                let hasVisibleEvent = false;
                let nextSibling = header.nextElementSibling;
                while (nextSibling && !nextSibling.classList.contains('simplepco-day-header') && !nextSibling.classList.contains('simplepco-month-header')) {
                    if (nextSibling.classList.contains('simplepco-event-item') && nextSibling.style.display !== 'none') {
                        hasVisibleEvent = true;
                        break;
                    }
                    nextSibling = nextSibling.nextElementSibling;
                }
                header.style.display = hasVisibleEvent ? '' : 'none';
            });

            monthHeaders.forEach(header => {
                // Check if any day header after this month header is visible
                let hasVisibleDay = false;
                let nextSibling = header.nextElementSibling;
                while (nextSibling && !nextSibling.classList.contains('simplepco-month-header')) {
                    if (nextSibling.classList.contains('simplepco-day-header') && nextSibling.style.display !== 'none') {
                        hasVisibleDay = true;
                        break;
                    }
                    nextSibling = nextSibling.nextElementSibling;
                }
                header.style.display = hasVisibleDay ? '' : 'none';
            });

            // Update load more button
            const remainingEvents = eventItems.length - visibleEventCount;
            if (remainingEvents > 0) {
                loadMoreBtn.style.display = 'block';
                loadMoreBtn.textContent = `Load More Events (${remainingEvents} remaining)`;
            } else {
                loadMoreBtn.style.display = 'none';
            }

            console.log('Showing ' + shownCount + ' of ' + eventItems.length + ' events');
        }

        // Load more click handler
        loadMoreBtn.addEventListener('click', function() {
            visibleEventCount += EVENTS_PER_PAGE;
            updateVisibility();
        });

        // Initial visibility update
        updateVisibility();
    }

    // ========================================
    // MONTH VIEW - Full Calendar Grid
    // Uses pcoExpandedEvents for all dates
    // ========================================

    function renderMonthView() {
        const monthContainer = document.querySelector('.simplepco-month-calendar-container');
        if (!monthContainer) return;

        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];

        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const today = new Date();

        // Use pcoExpandedEvents directly for month view (includes all multi-day dates)
        const eventsByDate = window.pcoExpandedEvents || {};

        let html = `
            <div class="simplepco-month-view-header">
                <h2 class="simplepco-month-view-title">${monthNames[currentMonth]} ${currentYear}</h2>
                <div class="simplepco-month-view-nav">
                    <button class="simplepco-month-nav-btn" data-dir="prev">< Previous</button>
                    <button class="simplepco-month-nav-today">Today</button>
                    <button class="simplepco-month-nav-btn" data-dir="next">Next ></button>
                </div>
            </div>
            <div class="simplepco-month-calendar-grid">
                <div class="simplepco-month-day-header">Sun</div>
                <div class="simplepco-month-day-header">Mon</div>
                <div class="simplepco-month-day-header">Tue</div>
                <div class="simplepco-month-day-header">Wed</div>
                <div class="simplepco-month-day-header">Thu</div>
                <div class="simplepco-month-day-header">Fri</div>
                <div class="simplepco-month-day-header">Sat</div>
        `;

        // Empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="simplepco-month-day-cell simplepco-month-day-empty"></div>';
        }

        // Day cells
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = day === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear();

            let cellClass = 'simplepco-month-day-cell';
            if (isToday) cellClass += ' simplepco-month-day-today';

            // Get events for this date from pcoExpandedEvents
            const dayEvents = eventsByDate[dateStr] || [];

            html += `<div class="${cellClass}">
                <div class="simplepco-month-day-number">${day}</div>
                <div class="simplepco-month-day-events">`;

            // Add events for this day (limit display to prevent overflow)
            const maxEventsToShow = 3;
            dayEvents.slice(0, maxEventsToShow).forEach((event) => {
                const displayTime = event.time === 'All Day' ? '' : formatTime(event.time) + ' ';
                const eventName = event.name.length > 15 ? event.name.substring(0, 15) + '...' : event.name;
                html += `<div class="simplepco-month-event" data-event='${JSON.stringify(event).replace(/'/g, "&#39;")}'>${displayTime}${eventName}</div>`;
            });

            // Show "+X more" if there are additional events
            if (dayEvents.length > maxEventsToShow) {
                html += `<div class="simplepco-month-event-more">+${dayEvents.length - maxEventsToShow} more</div>`;
            }

            html += `</div></div>`;
        }

        html += '</div>';
        monthContainer.innerHTML = html;

        // Attach event click handlers
        monthContainer.querySelectorAll('.simplepco-month-event').forEach(el => {
            el.addEventListener('click', function() {
                const eventDataStr = this.getAttribute('data-event');
                if (eventDataStr) {
                    try {
                        const eventData = JSON.parse(eventDataStr);
                        // Find index in allEventsData
                        const index = allEventsData.findIndex(evt =>
                            evt.name === eventData.name && evt.dateKey === eventData.dateKey
                        );
                        showEventDetail(eventData, index >= 0 ? index : 0);
                    } catch (e) {
                        console.error('Error parsing event data:', e);
                    }
                }
            });
        });

        // Navigation handlers
        monthContainer.querySelectorAll('.simplepco-month-nav-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const dir = this.getAttribute('data-dir');
                if (dir === 'prev') {
                    currentMonth--;
                    if (currentMonth < 0) {
                        currentMonth = 11;
                        currentYear--;
                    }
                } else {
                    currentMonth++;
                    if (currentMonth > 11) {
                        currentMonth = 0;
                        currentYear++;
                    }
                }
                renderMonthView();
            });
        });

        monthContainer.querySelector('.simplepco-month-nav-today')?.addEventListener('click', function() {
            const now = new Date();
            currentMonth = now.getMonth();
            currentYear = now.getFullYear();
            renderMonthView();
        });

        console.log('Month view rendered for ' + monthNames[currentMonth] + ' ' + currentYear);
    }

    // ========================================
    // EVENT DETAIL VIEW
    // ========================================

    function showEventDetail(eventData, eventIndex) {
        currentEventIndex = eventIndex;

        const detailView = document.getElementById('simplepco-view-detail');
        if (!detailView) return;

        document.getElementById('simplepco-detail-title').textContent = eventData.name || '';
        document.getElementById('simplepco-detail-date').textContent = eventData.date || '';
        document.getElementById('simplepco-detail-time').textContent = eventData.time || '';
        document.getElementById('simplepco-detail-description').innerHTML = formatDescription(eventData.description || eventData.summary || '');

        // Image
        const detailImage = document.getElementById('simplepco-detail-image');
        const imageContainer = document.getElementById('simplepco-detail-image-container');
        if (eventData.image_url) {
            detailImage.src = eventData.image_url;
            detailImage.alt = eventData.name;
            imageContainer.style.display = 'block';
        } else {
            imageContainer.style.display = 'none';
        }

        // Location with embedded Google Map
        const locationContainer = document.getElementById('simplepco-detail-location-container');
        const locationLink = document.getElementById('simplepco-detail-location-link');
        const mapContainer = document.getElementById('simplepco-detail-map-container');
        const mapIframe = document.getElementById('simplepco-detail-map-iframe');
        const locationHeader = locationContainer.querySelector('h3');

        if (eventData.location) {
            const mapsQuery = encodeURIComponent(eventData.location);
            const mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + mapsQuery;
            const embedUrl = 'https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=' + mapsQuery;

            document.getElementById('simplepco-detail-location-text').textContent = eventData.location_name || eventData.location;
            document.getElementById('simplepco-detail-address').textContent = eventData.location;

            locationLink.href = mapsUrl;
            locationLink.style.display = '';
            locationHeader.style.display = '';

            // Set up embedded map
            mapIframe.src = embedUrl;
            mapContainer.style.display = 'block';

            locationContainer.style.display = 'block';
        } else {
            // Hide location-specific elements but keep container visible if there's a signup button
            locationLink.style.display = 'none';
            mapContainer.style.display = 'none';
            locationHeader.style.display = 'none';  // Hide "LOCATION" header
            document.getElementById('simplepco-detail-location-text').textContent = '';
            document.getElementById('simplepco-detail-address').textContent = '';

            // Keep container visible if there's a registration URL
            if (!eventData.registration_url) {
                locationContainer.style.display = 'none';
            } else {
                locationContainer.style.display = 'block';
            }
        }

        // Signup button
        const signupBtn = document.getElementById('simplepco-detail-signup-btn');
        const signupContainer = document.getElementById('simplepco-detail-signup-container');

        if (eventData.registration_url) {
            // Ensure location container is visible (in case there's no location)
            locationContainer.style.display = 'block';

            signupBtn.setAttribute('data-signup-url', eventData.registration_url);
            signupContainer.style.display = 'flex';
        } else {
            signupContainer.style.display = 'none';
        }

        // Breadcrumb
        document.getElementById('simplepco-breadcrumb-event-name').textContent = eventData.name || '';

        // Update navigation
        updateDetailNavigation();

        // Switch to detail view
        switchView('simplepco-view-detail');

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateDetailNavigation() {
        const prevBtn = document.getElementById('simplepco-detail-prev');
        const nextBtn = document.getElementById('simplepco-detail-next');

        if (currentEventIndex <= 0) {
            prevBtn.style.opacity = '0.3';
            prevBtn.style.pointerEvents = 'none';
        } else {
            prevBtn.style.opacity = '1';
            prevBtn.style.pointerEvents = 'auto';
        }

        if (currentEventIndex >= allEventsData.length - 1) {
            nextBtn.style.opacity = '0.3';
            nextBtn.style.pointerEvents = 'none';
        } else {
            nextBtn.style.opacity = '1';
            nextBtn.style.pointerEvents = 'auto';
        }
    }

    function formatDescription(text) {
        if (!text) return '';
        return text.split('\n\n').map(p => '<p>' + p.replace(/\n/g, '<br>') + '</p>').join('');
    }

    // ========================================
    // EVENT DATA COLLECTION
    // ========================================

    // Load events from pcoExpandedEvents (created by PHP)
    if (window.pcoExpandedEvents) {
        for (const dateKey in window.pcoExpandedEvents) {
            const eventsOnDate = window.pcoExpandedEvents[dateKey];
            eventsOnDate.forEach(eventData => {
                allEventsData.push(eventData);
            });
        }
        console.log('Loaded ' + allEventsData.length + ' event instances from expanded data');
    }

    // Attach click handlers to event title buttons
    document.querySelectorAll('.simplepco-event-title-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const eventData = JSON.parse(btn.getAttribute('data-event'));
            const index = allEventsData.findIndex(evt =>
                evt.name === eventData.name && evt.dateKey === eventData.dateKey
            );
            showEventDetail(eventData, index >= 0 ? index : 0);
        });
    });

    // ========================================
    // DETAIL VIEW NAVIGATION
    // ========================================

    const backBtn = document.getElementById('simplepco-detail-back');
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            switchView(previousView);
        });
    }

    const prevBtn = document.getElementById('simplepco-detail-prev');
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            if (currentEventIndex > 0) {
                showEventDetail(allEventsData[currentEventIndex - 1], currentEventIndex - 1);
            }
        });
    }

    const nextBtn = document.getElementById('simplepco-detail-next');
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            if (currentEventIndex < allEventsData.length - 1) {
                showEventDetail(allEventsData[currentEventIndex + 1], currentEventIndex + 1);
            }
        });
    }

    // ========================================
    // SIGNUP BUTTON - Opens in new tab
    // ========================================

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('simplepco-signup-link')) {
            e.preventDefault();
            e.stopPropagation();
            const url = e.target.getAttribute('data-signup-url');
            if (url) {
                const directSignupUrl = convertToDirectSignupUrl(url);
                window.open(directSignupUrl, '_blank');
            }
        }
    });

    function convertToDirectSignupUrl(registrationUrl) {
        if (registrationUrl.includes('/registrations/events/')) {
            const cleanUrl = registrationUrl.replace(/\/+$/, '');
            return cleanUrl + '/reservations/new';
        }
        return registrationUrl;
    }

    // ========================================
    // INITIALIZATION
    // ========================================

    // Initialize mini calendar
    collectEventDates();
    renderMiniCalendar(miniCalMonth, miniCalYear);

    // Initialize list view pagination
    initListViewPagination();

    console.log('Calendar initialized - ' + allEventsData.length + ' events loaded');
});
