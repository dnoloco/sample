<?php
/**
 * Calendar Event Detail View Template
 *
 * Displays detailed information for a single event.
 * This view is dynamically populated by JavaScript when a user clicks on an event.
 *
 * Note: Event data is populated via JavaScript using the data-event attributes
 * from other views. This template provides the structure and placeholders.
 */

defined('ABSPATH') || exit;
?>

<div id="pco-view-detail" class="pco-view-section">

    <!-- Breadcrumb Navigation -->
    <div class="pco-detail-breadcrumb">
        <a href="#" id="pco-detail-back" class="pco-breadcrumb-link">
            <?php _e('All events', 'mypco-online'); ?>
        </a>
        <span class="pco-breadcrumb-separator">&rsaquo;</span>
        <span id="pco-breadcrumb-event-name" class="pco-breadcrumb-current">
            <!-- Event name populated by JavaScript -->
        </span>
    </div>

    <!-- Event Detail Container - Two Column Layout -->
    <div class="pco-detail-container">

        <!-- Left Column - Event Information -->
        <div class="pco-detail-left">

            <!-- Event Title -->
            <h1 id="pco-detail-title" class="pco-detail-title">
                <!-- Event title populated by JavaScript -->
            </h1>

            <!-- Date and Time -->
            <div id="pco-detail-datetime" class="pco-detail-datetime">
                <!-- Date/time populated by JavaScript, e.g., "Sunday, February 1, 10–11:15am" -->
            </div>

            <!-- Event Description Section -->
            <div class="pco-detail-section">
                <h2 class="pco-detail-section-title">
                    <?php _e('Details', 'mypco-online'); ?>
                </h2>
                <div id="pco-detail-description" class="pco-detail-description">
                    <!-- Description populated by JavaScript -->
                </div>
            </div>

        </div>

        <!-- Right Column - Image, Categories, Location -->
        <div class="pco-detail-right">

            <!-- Event Image -->
            <div id="pco-detail-image-container" class="pco-detail-image-container" style="display: none;">
                <img id="pco-detail-image"
                     src=""
                     alt=""
                     class="pco-detail-image">
            </div>

            <!-- Categories Section -->
            <div id="pco-detail-categories-container" class="pco-detail-info-box" style="display: none;">
                <h3 class="pco-detail-info-heading">
                    <?php _e('CATEGORIES', 'mypco-online'); ?>
                </h3>
                <div id="pco-detail-categories" class="pco-detail-categories">
                    <!-- Categories populated by JavaScript -->
                </div>
            </div>

            <!-- Location Information -->
            <div id="pco-detail-location-container" class="pco-detail-info-box" style="display: none;">
                <h3 class="pco-detail-info-heading">
                    <?php _e('LOCATION', 'mypco-online'); ?>
                </h3>

                <!-- Location Name -->
                <p id="pco-detail-location-name" class="pco-detail-location-name">
                    <!-- Location name populated by JavaScript -->
                </p>

                <!-- Location Address -->
                <p id="pco-detail-location-address" class="pco-detail-location-address">
                    <!-- Address populated by JavaScript -->
                </p>

                <!-- Location Buttons -->
                <div class="pco-detail-location-buttons">
                    <a id="pco-detail-directions"
                       href="#"
                       class="pco-detail-btn-outline"
                       target="_blank"
                       rel="noopener"
                       style="display: none;">
                        <?php _e('Get directions', 'mypco-online'); ?>
                    </a>
                </div>

                <!-- Map Container (shown when "Show map" clicked) -->
                <div id="pco-detail-map-container" class="pco-detail-map-container" style="display: none;">
                    <iframe id="pco-detail-map-iframe"
                            width="100%"
                            height="200"
                            frameborder="0"
                            style="border:0; border-radius: 8px;"
                            allowfullscreen=""
                            loading="lazy">
                    </iframe>
                </div>
            </div>

            <!-- Registration Button (if available) -->
            <div id="pco-detail-signup-container" class="pco-detail-signup-box" style="display: none;">
                <a id="pco-detail-signup-btn"
                   href="#"
                   class="pco-detail-btn-primary"
                   target="_blank"
                   rel="noopener">
                    <?php _e('Register', 'mypco-online'); ?>
                </a>
            </div>

        </div>

    </div>

</div>
