/**
 * SimplePCO Calendar JavaScript
 *
 * Handles calendar view switching, mini calendar, month view rendering,
 * and event detail display.
 */

(function($) {
    'use strict';

    // Calendar state
    var state = {
        currentView: 'list',
        previousView: 'list',
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        expandedEvents: window.pcoExpandedEvents || window.simplepcoCalendarData?.expandedEvents || {},
        allEventButtons: [],
        selectedTagId: '' // Current category filter
    };

    /**
     * Initialize the calendar
     */
    function init() {
        // Cache event buttons for navigation
        state.allEventButtons = $('.pco-event-title-btn').toArray();

        // Initialize components
        initViewSwitcher();
        initMiniCalendar();
        initMonthCalendar();
        initEventDetail();
        initEventNavigation();
        initCategoryFilter();
        initClearFiltersHandler();

        // Get initial view from PHP-rendered data attribute (server reads cookie)
        var initialView = $('.pco-wrapper').data('initial-view') || 'list';

        // Set state to match server-rendered view
        state.currentView = initialView;
        state.previousView = initialView;

        // Render month calendar if starting on month view
        if (initialView === 'month') {
            renderMonthCalendar();
        }
    }

    /**
     * Initialize category filter dropdown
     */
    function initCategoryFilter() {
        $('#pco-category-filter').on('change', function() {
            state.selectedTagId = $(this).val();
            applyFilters();
        });
    }

    /**
     * Apply category filters across all views
     */
    function applyFilters() {
        // Filter list view
        filterListView();

        // Filter gallery view
        filterGalleryView();

        // Re-render month view (it uses expandedEvents which we'll filter)
        renderMiniCalendar();
        if (state.currentView === 'month') {
            renderMonthCalendar();
        }
    }

    /**
     * Check if event has the selected tag (handles string/number comparison)
     */
    function eventHasTag(tagIds, selectedTagId) {
        if (!selectedTagId) return true;
        if (!tagIds || !Array.isArray(tagIds)) return false;
        // Compare as strings since API returns string IDs
        return tagIds.some(function(id) {
            return String(id) === String(selectedTagId);
        });
    }

    /**
     * Filter list view by selected category
     */
    function filterListView() {
        var tagId = state.selectedTagId;

        // Remove existing "no events" message
        $('#pco-view-list .pco-filter-no-events').remove();

        // IMPORTANT: Show all containers first so :visible checks work correctly
        // jQuery's :visible selector returns false for elements inside hidden parents,
        // even if the elements themselves are not hidden
        $('.pco-featured-section').show();
        $('.pco-month-group').show();
        $('.pco-day-group').show();
        $('.pco-events-section').show();
        $('.pco-upcoming-title').show();

        // If no filter, reset to default state
        if (!tagId) {
            // Show featured cards that are not initially hidden, hide those that are
            $('.pco-featured-card').each(function() {
                var $card = $(this);
                if ($card.hasClass('pco-featured-initially-hidden')) {
                    $card.hide();
                } else {
                    $card.show();
                }
            });
            // Hide featured section if no visible cards
            var visibleFeaturedDefault = $('.pco-featured-card:visible').length;
            if (visibleFeaturedDefault === 0) {
                $('.pco-featured-section').hide();
            }
            $('.pco-event-item').show();
            return;
        }

        // When filtering, show ALL featured cards that match the tag (including initially hidden ones)
        $('.pco-featured-card').each(function() {
            var $card = $(this);
            var eventData = $card.find('.pco-event-title-btn').data('event');
            if (typeof eventData === 'string') {
                try { eventData = JSON.parse(eventData); } catch(e) { return; }
            }
            var tagIds = eventData?.tag_ids || [];

            if (eventHasTag(tagIds, tagId)) {
                $card.show();
            } else {
                $card.hide();
            }
        });

        // Hide featured section if no visible cards
        var visibleFeatured = $('.pco-featured-card:visible').length;
        if (visibleFeatured === 0) {
            $('.pco-featured-section').hide();
        }

        // Show/hide regular events based on tag match
        $('.pco-event-item').each(function() {
            var $item = $(this);
            var eventData = $item.find('.pco-event-title-btn').data('event');
            if (typeof eventData === 'string') {
                try { eventData = JSON.parse(eventData); } catch(e) { return; }
            }
            var tagIds = eventData?.tag_ids || [];

            if (eventHasTag(tagIds, tagId)) {
                $item.show();
            } else {
                $item.hide();
            }
        });

        // Hide day groups with no visible events
        $('.pco-day-group').each(function() {
            var $group = $(this);
            var visibleEvents = $group.find('.pco-event-item:visible').length;
            if (visibleEvents === 0) {
                $group.hide();
            }
        });

        // Hide month groups with no visible day groups
        $('.pco-month-group').each(function() {
            var $group = $(this);
            var visibleDays = $group.find('.pco-day-group:visible').length;
            var hasNoEventsBox = $group.find('.pco-no-events-box').length > 0;
            if (visibleDays === 0 && !hasNoEventsBox) {
                $group.hide();
            }
        });

        // Show "No events found" message if no visible events (featured or regular)
        var visibleEvents = $('.pco-event-item:visible').length;
        if (visibleFeatured === 0 && visibleEvents === 0) {
            // Hide all content when showing no events message
            $('.pco-month-group').hide();
            $('.pco-upcoming-title').hide();
            $('.pco-events-section').append(
                '<div class="pco-filter-no-events">' +
                '<div class="pco-filter-no-events-icon">📅</div>' +
                '<p>No events found.</p>' +
                '<button class="pco-clear-filters-btn" type="button">Clear filters</button>' +
                '</div>'
            );
        }
    }

    /**
     * Filter gallery view by selected category
     */
    function filterGalleryView() {
        var tagId = state.selectedTagId;

        // Remove existing "no events" message
        $('#pco-view-gallery .pco-filter-no-events').remove();

        // If no filter, show everything and return
        if (!tagId) {
            $('.pco-gallery-item').show();
            return;
        }

        var visibleCount = 0;

        $('.pco-gallery-item').each(function() {
            var $item = $(this);
            var eventData = $item.find('.pco-event-title-btn').data('event');
            if (typeof eventData === 'string') {
                try { eventData = JSON.parse(eventData); } catch(e) { return; }
            }
            var tagIds = eventData?.tag_ids || [];

            if (eventHasTag(tagIds, tagId)) {
                $item.show();
                visibleCount++;
            } else {
                $item.hide();
            }
        });

        // Show "No events found" message if filtering and no visible events
        if (tagId && visibleCount === 0) {
            $('.pco-gallery-grid').after(
                '<div class="pco-filter-no-events">' +
                '<div class="pco-filter-no-events-icon">📅</div>' +
                '<p>No events found.</p>' +
                '<button class="pco-clear-filters-btn" type="button">Clear filters</button>' +
                '</div>'
            );
        }
    }

    /**
     * Check if an event matches the current filter
     */
    function eventMatchesFilter(event) {
        if (!state.selectedTagId) return true;
        var tagIds = event.tag_ids || [];
        return eventHasTag(tagIds, state.selectedTagId);
    }

    /**
     * Clear filters button handler
     */
    function initClearFiltersHandler() {
        $(document).on('click', '.pco-clear-filters-btn', function() {
            // Reset dropdown to "All Categories"
            $('#pco-category-filter').val('');
            state.selectedTagId = '';
            applyFilters();
        });
    }

    /**
     * View Switcher - handles switching between List, Month, Gallery views
     */
    function initViewSwitcher() {
        $(document).on('click', '.pco-view-btn', function(e) {
            e.preventDefault();
            var target = $(this).data('target');

            if (target) {
                var viewName = target.replace('pco-view-', '');
                switchView(viewName);
            }
        });
    }

    /**
     * Switch to a specific view
     */
    function switchView(viewName) {
        // Update button states
        $('.pco-view-btn').removeClass('active');
        $('.pco-view-btn[data-target="pco-view-' + viewName + '"]').addClass('active');

        // Hide all views
        $('.pco-view-section').removeClass('active');

        // Show target view
        $('#pco-view-' + viewName).addClass('active');

        // Reset to current month when switching between list and month views
        // (but not when navigating to/from detail view)
        if ((state.currentView === 'list' && viewName === 'month') ||
            (state.currentView === 'month' && viewName === 'list')) {
            var today = new Date();
            state.currentMonth = today.getMonth();
            state.currentYear = today.getFullYear();
            renderMiniCalendar();
        }

        // Track view state - save where we came from when going to detail view
        if (viewName === 'detail' && state.currentView !== 'detail') {
            state.previousView = state.currentView;
        }
        state.currentView = viewName;

        // Save view to cookie (except detail view which needs event data)
        // Cookie allows PHP to read it server-side for flicker-free page loads
        if (viewName !== 'detail') {
            document.cookie = 'pco_calendar_view=' + viewName + ';path=/;max-age=31536000;SameSite=Lax';
        }

        // Toggle body classes for view-specific styling
        $('body').removeClass('pco-detail-active pco-view-detail-active pco-view-month-active pco-view-gallery-active');

        if (viewName === 'detail') {
            $('body').addClass('pco-detail-active pco-view-detail-active');
        } else if (viewName === 'month') {
            $('body').addClass('pco-detail-active pco-view-month-active');
        } else if (viewName === 'gallery') {
            $('body').addClass('pco-view-gallery-active');
        }

        // Toggle sidebar/grid classes (sync with PHP-driven initial state)
        if (viewName === 'month' || viewName === 'gallery' || viewName === 'detail') {
            $('.pco-sidebar').addClass('pco-sidebar-hidden');
            $('.pco-layout-grid').addClass('pco-grid-full-width');
        } else {
            $('.pco-sidebar').removeClass('pco-sidebar-hidden');
            $('.pco-layout-grid').removeClass('pco-grid-full-width');
        }

        // Render month calendar if switching to month view
        if (viewName === 'month') {
            renderMonthCalendar();
        }

        // Apply current filter to the view we're switching to
        // This ensures filter state is consistent across view switches
        if (viewName === 'list') {
            filterListView();
        } else if (viewName === 'gallery') {
            filterGalleryView();
        }
    }

    /**
     * Mini Calendar - sidebar navigation calendar
     */
    function initMiniCalendar() {
        // Navigation buttons
        $(document).on('click', '.pco-mini-cal-nav', function() {
            var nav = $(this).data('nav');

            if (nav === 'prev') {
                state.currentMonth--;
                if (state.currentMonth < 0) {
                    state.currentMonth = 11;
                    state.currentYear--;
                }
            } else {
                state.currentMonth++;
                if (state.currentMonth > 11) {
                    state.currentMonth = 0;
                    state.currentYear++;
                }
            }

            renderMiniCalendar();
            renderMonthCalendar();
        });

        // Initial render
        renderMiniCalendar();
    }

    /**
     * Render the mini calendar grid
     */
    function renderMiniCalendar() {
        var months = ['January', 'February', 'March', 'April', 'May', 'June',
                      'July', 'August', 'September', 'October', 'November', 'December'];

        // Update header
        $('.pco-mini-cal-month-display').text(months[state.currentMonth] + ' ' + state.currentYear);

        // Get first day of month and total days
        var firstDay = new Date(state.currentYear, state.currentMonth, 1).getDay();
        var daysInMonth = new Date(state.currentYear, state.currentMonth + 1, 0).getDate();
        var today = new Date();

        // Build grid HTML
        var html = '<span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>';

        // Empty cells before first day
        for (var i = 0; i < firstDay; i++) {
            html += '<span class="pco-mini-cal-empty"></span>';
        }

        // Day cells
        for (var day = 1; day <= daysInMonth; day++) {
            var dateKey = state.currentYear + '-' +
                          String(state.currentMonth + 1).padStart(2, '0') + '-' +
                          String(day).padStart(2, '0');

            var classes = ['pco-mini-cal-day'];

            // Check if today
            if (today.getDate() === day &&
                today.getMonth() === state.currentMonth &&
                today.getFullYear() === state.currentYear) {
                classes.push('is-today');
            }

            // Check if has events (only show dots for today and future dates, filtered by category)
            var cellDate = new Date(state.currentYear, state.currentMonth, day);
            var todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            var dayEvents = state.expandedEvents[dateKey] || [];
            var filteredEvents = dayEvents.filter(function(evt) {
                return eventMatchesFilter(evt);
            });
            if (filteredEvents.length > 0 && cellDate >= todayStart) {
                classes.push('has-events');
            }

            html += '<span class="' + classes.join(' ') + '" data-date="' + dateKey + '">' + day + '</span>';
        }

        $('.pco-mini-cal-grid').html(html);

        // Add click handler for all days to scroll to that date in list view
        $('.pco-mini-cal-day').on('click', function() {
            var dateKey = $(this).data('date');
            scrollToDate(dateKey);
        });
    }

    /**
     * Scroll to a specific date in list view
     */
    function scrollToDate(dateKey) {
        // Switch to list view if not already
        if (state.currentView !== 'list') {
            switchView('list');
        }

        // Find and scroll to the day group (which has the data-date attribute)
        var $dayGroup = $('.pco-day-group[data-date="' + dateKey + '"]');
        if ($dayGroup.length) {
            $('html, body').animate({
                scrollTop: $dayGroup.offset().top - 100
            }, 300);

            // Highlight briefly
            $dayGroup.addClass('highlight');
            setTimeout(function() {
                $dayGroup.removeClass('highlight');
            }, 2000);
        }
    }

    /**
     * Month Calendar - full month view
     */
    function initMonthCalendar() {
        // Navigation for month view - prev/next arrows
        $(document).on('click', '.pco-month-nav-btn', function() {
            var nav = $(this).data('nav');

            if (nav === 'prev') {
                state.currentMonth--;
                if (state.currentMonth < 0) {
                    state.currentMonth = 11;
                    state.currentYear--;
                }
            } else if (nav === 'next') {
                state.currentMonth++;
                if (state.currentMonth > 11) {
                    state.currentMonth = 0;
                    state.currentYear++;
                }
            }

            renderMiniCalendar();
            renderMonthCalendar();
        });

        // Today button
        $(document).on('click', '.pco-month-nav-today', function() {
            var today = new Date();
            state.currentMonth = today.getMonth();
            state.currentYear = today.getFullYear();
            renderMiniCalendar();
            renderMonthCalendar();
        });
    }

    /**
     * Render the full month calendar
     */
    function renderMonthCalendar() {
        var months = ['January', 'February', 'March', 'April', 'May', 'June',
                      'July', 'August', 'September', 'October', 'November', 'December'];
        var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        var firstDay = new Date(state.currentYear, state.currentMonth, 1).getDay();
        var daysInMonth = new Date(state.currentYear, state.currentMonth + 1, 0).getDate();
        var daysInPrevMonth = new Date(state.currentYear, state.currentMonth, 0).getDate();
        var today = new Date();

        var html = '';

        // Header with title and navigation
        html += '<div class="pco-month-view-header">';
        html += '<h2 class="pco-month-view-title">' + months[state.currentMonth] + ' ' + state.currentYear + '</h2>';
        html += '<div class="pco-month-view-nav">';
        html += '<button class="pco-month-nav-btn" data-nav="prev" aria-label="Previous month">&#8249;</button>';
        html += '<button class="pco-month-nav-today">Today</button>';
        html += '<button class="pco-month-nav-btn" data-nav="next" aria-label="Next month">&#8250;</button>';
        html += '</div>';
        html += '</div>';

        // Calendar grid container
        html += '<div class="pco-month-calendar-grid">';

        // Day headers
        for (var i = 0; i < 7; i++) {
            html += '<div class="pco-month-day-header">' + days[i] + '</div>';
        }

        // Previous month's trailing days
        var prevMonth = state.currentMonth - 1;
        var prevYear = state.currentYear;
        if (prevMonth < 0) {
            prevMonth = 11;
            prevYear--;
        }
        for (var i = firstDay - 1; i >= 0; i--) {
            var prevDay = daysInPrevMonth - i;
            var prevDateKey = prevYear + '-' +
                              String(prevMonth + 1).padStart(2, '0') + '-' +
                              String(prevDay).padStart(2, '0');
            html += '<div class="pco-month-day-cell pco-month-day-other">';
            html += '<div class="pco-month-day-number">' + prevDay + '</div>';
            html += renderDayEvents(prevDateKey);
            html += '</div>';
        }

        // Track if there are any events remaining in the month (today or future)
        var hasUpcomingEvents = false;
        var todayKey = today.getFullYear() + '-' +
                       String(today.getMonth() + 1).padStart(2, '0') + '-' +
                       String(today.getDate()).padStart(2, '0');

        // Current month days
        for (var day = 1; day <= daysInMonth; day++) {
            var dateKey = state.currentYear + '-' +
                          String(state.currentMonth + 1).padStart(2, '0') + '-' +
                          String(day).padStart(2, '0');

            var cellClasses = ['pco-month-day-cell'];
            var dayNumClasses = ['pco-month-day-number'];

            // Check if today
            var isToday = (today.getDate() === day &&
                          today.getMonth() === state.currentMonth &&
                          today.getFullYear() === state.currentYear);
            if (isToday) {
                cellClasses.push('pco-month-day-today');
                dayNumClasses.push('is-today');
            }

            // Check if past date (before today)
            if (dateKey < todayKey) {
                cellClasses.push('pco-month-day-past');
            }

            // Check if this date has events (for tracking upcoming events, filtered by category)
            if (dateKey >= todayKey && state.expandedEvents[dateKey]) {
                var dayFilteredEvents = state.expandedEvents[dateKey].filter(function(evt) {
                    return eventMatchesFilter(evt);
                });
                if (dayFilteredEvents.length > 0) {
                    hasUpcomingEvents = true;
                }
            }

            html += '<div class="' + cellClasses.join(' ') + '" data-date="' + dateKey + '">';
            html += '<div class="' + dayNumClasses.join(' ') + '">' + day + '</div>';
            html += renderDayEvents(dateKey);
            html += '</div>';
        }

        // Next month's leading days to fill grid
        var totalCells = firstDay + daysInMonth;
        var remainingCells = (7 - (totalCells % 7)) % 7;
        var nextMonth = state.currentMonth + 1;
        var nextYear = state.currentYear;
        if (nextMonth > 11) {
            nextMonth = 0;
            nextYear++;
        }
        for (var i = 1; i <= remainingCells; i++) {
            var nextDateKey = nextYear + '-' +
                              String(nextMonth + 1).padStart(2, '0') + '-' +
                              String(i).padStart(2, '0');
            html += '<div class="pco-month-day-cell pco-month-day-other">';
            html += '<div class="pco-month-day-number">' + String(i).padStart(2, '0') + '</div>';
            html += renderDayEvents(nextDateKey);
            html += '</div>';
        }

        html += '</div>';

        $('#pco-view-month').html(html);

        // Add click handlers for month events
        $('.pco-month-event').on('click', function(e) {
            e.stopPropagation();
            var eventData = $(this).data('event');
            if (eventData) {
                showEventDetail(eventData);
            }
        });

        // Click on day cell to go to that date in list view (only for today and future dates)
        $('.pco-month-day-cell').on('click', function(e) {
            if ($(e.target).closest('.pco-month-event').length) return;
            var dateKey = $(this).data('date');
            // Don't navigate for past dates
            if (isDatePast(dateKey)) return;
            if (dateKey && state.expandedEvents[dateKey] && state.expandedEvents[dateKey].length > 0) {
                scrollToDate(dateKey);
            }
        });
    }

    /**
     * Check if a date is in the past (before today)
     */
    function isDatePast(dateKey) {
        var today = new Date();
        var todayKey = today.getFullYear() + '-' +
                       String(today.getMonth() + 1).padStart(2, '0') + '-' +
                       String(today.getDate()).padStart(2, '0');
        return dateKey < todayKey;
    }

    /**
     * Render events for a specific day in month view
     */
    function renderDayEvents(dateKey) {
        // Don't show events for past dates
        if (isDatePast(dateKey)) {
            return '';
        }

        var allEvents = state.expandedEvents[dateKey] || [];

        // Filter events by selected category
        var events = allEvents.filter(function(evt) {
            return eventMatchesFilter(evt);
        });

        if (events.length === 0) return '';

        var html = '<div class="pco-month-day-events">';
        var displayCount = Math.min(events.length, 3);

        for (var e = 0; e < displayCount; e++) {
            var evt = events[e];
            var isFeatured = evt.is_featured || false;
            var starIcon = isFeatured ? '<span class="pco-star">★</span> ' : '';

            html += '<div class="pco-month-event' + (isFeatured ? ' is-featured' : '') + '" data-event=\'' + JSON.stringify(evt).replace(/'/g, '&#39;') + '\'>';
            html += starIcon + '<span class="pco-month-event-time">' + escapeHtml(evt.time) + '</span> ';
            html += '<span class="pco-month-event-name">' + escapeHtml(evt.name) + '</span>';
            html += '</div>';
        }

        if (events.length > 3) {
            html += '<div class="pco-month-event-more">+' + (events.length - 3) + ' more</div>';
        }

        html += '</div>';
        return html;
    }

    /**
     * Event Detail View
     */
    function initEventDetail() {
        // Click on event title to show detail
        $(document).on('click', '.pco-event-title-btn', function(e) {
            e.preventDefault();
            var eventData = $(this).data('event');

            if (typeof eventData === 'string') {
                try {
                    eventData = JSON.parse(eventData);
                } catch (err) {
                    console.error('Failed to parse event data:', err);
                    return;
                }
            }

            if (eventData) {
                showEventDetail(eventData);
            }
        });

        // Back button
        $(document).on('click', '#pco-detail-back', function(e) {
            e.preventDefault();
            switchView(state.previousView);
        });

        // Registration/signup button
        $(document).on('click', '#pco-detail-signup-btn', function() {
            var url = $(this).data('signup-url');
            if (url) {
                window.open(url, '_blank');
            }
        });

        // Location link
        $(document).on('click', '#pco-detail-location-link', function(e) {
            // Let the default link behavior happen
        });
    }

    /**
     * Show event detail view
     */
    function showEventDetail(eventData) {
        // Populate detail view
        $('#pco-detail-title').text(eventData.name || '');
        $('#pco-breadcrumb-event-name').text(eventData.name || '');

        // Format date/time display (e.g., "Sunday, February 1, 10–11:15am")
        // Use time_full (full range) if available, otherwise fall back to time
        var timeDisplay = eventData.time_full || eventData.time;
        var dateTimeDisplay = '';
        if (eventData.date) {
            dateTimeDisplay = eventData.date;
            if (timeDisplay && timeDisplay !== 'All Day') {
                dateTimeDisplay += ', ' + timeDisplay;
            } else if (timeDisplay === 'All Day') {
                dateTimeDisplay += ' (All Day)';
            }
        }
        $('#pco-detail-datetime').text(dateTimeDisplay);

        // Description - use description or summary
        var description = eventData.description || eventData.summary || 'No description available.';
        $('#pco-detail-description').html(description);

        // Image
        if (eventData.image_url) {
            $('#pco-detail-image').attr('src', eventData.image_url).attr('alt', eventData.name);
            $('#pco-detail-image-container').show();
        } else {
            $('#pco-detail-image-container').hide();
        }

        // Categories (if available)
        if (eventData.categories && eventData.categories.length > 0) {
            var categoriesHtml = '';
            eventData.categories.forEach(function(cat) {
                categoriesHtml += '<span class="pco-detail-category-badge">' + escapeHtml(cat) + '</span>';
            });
            $('#pco-detail-categories').html(categoriesHtml);
            $('#pco-detail-categories-container').show();
        } else {
            $('#pco-detail-categories-container').hide();
        }

        // Location
        if (eventData.location) {
            var locationName = eventData.location_name || eventData.location;
            var address = '';

            // Parse address if available (format: "Location Name - Address")
            if (eventData.location.indexOf(' - ') !== -1) {
                address = eventData.location.substring(eventData.location.indexOf(' - ') + 3);
            }

            $('#pco-detail-location-name').text(locationName);
            $('#pco-detail-location-address').html(address.replace(/, /g, '<br>'));

            // Google Maps directions link
            var mapsQuery = encodeURIComponent(eventData.location);
            var directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + mapsQuery;

            $('#pco-detail-directions').attr('href', directionsUrl).show();

            $('#pco-detail-location-container').show();
        } else {
            $('#pco-detail-location-container').hide();
        }

        // Registration button
        if (eventData.registration_url) {
            $('#pco-detail-signup-btn').attr('href', eventData.registration_url);
            $('#pco-detail-signup-container').show();
        } else {
            $('#pco-detail-signup-container').hide();
        }

        // Switch to detail view
        switchView('detail');

        // Scroll to top
        window.scrollTo(0, 0);
    }

    /**
     * Event Navigation (prev/next)
     */
    function initEventNavigation() {
        $(document).on('click', '#pco-detail-prev', function() {
            navigateEvent(-1);
        });

        $(document).on('click', '#pco-detail-next', function() {
            navigateEvent(1);
        });
    }

    /**
     * Navigate to prev/next event
     */
    function navigateEvent(direction) {
        var currentTitle = $('#pco-detail-title').text();
        var currentIndex = -1;

        // Find current event in the list
        for (var i = 0; i < state.allEventButtons.length; i++) {
            var $btn = $(state.allEventButtons[i]);
            var eventData = $btn.data('event');

            if (typeof eventData === 'string') {
                try {
                    eventData = JSON.parse(eventData);
                } catch (e) {
                    continue;
                }
            }

            if (eventData && eventData.name === currentTitle) {
                currentIndex = i;
                break;
            }
        }

        // Navigate to next/prev
        var newIndex = currentIndex + direction;
        if (newIndex >= 0 && newIndex < state.allEventButtons.length) {
            var $newBtn = $(state.allEventButtons[newIndex]);
            var newEventData = $newBtn.data('event');

            if (typeof newEventData === 'string') {
                try {
                    newEventData = JSON.parse(newEventData);
                } catch (e) {
                    return;
                }
            }

            if (newEventData) {
                showEventDetail(newEventData);
            }
        }
    }

    /**
     * Utility: Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('.pco-wrapper').length) {
            init();
        }
    });

})(jQuery);
