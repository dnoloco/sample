<?php
/**
 * PCO Date Helper
 *
 * Utility class for parsing and formatting Planning Center Online dates.
 * Handles all-day events, multi-day events, and timezone conversions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class MyPCO_Date_Helper {

    /**
     * Default timezone for display.
     */
    private static $default_timezone = 'America/Chicago';

    /**
     * Get the target timezone object.
     *
     * @return DateTimeZone
     */
    public static function get_timezone() {
        $timezone_string = get_option('timezone_string');

        if (empty($timezone_string)) {
            $timezone_string = self::$default_timezone;
        }

        try {
            return new DateTimeZone($timezone_string);
        } catch (Exception $e) {
            return new DateTimeZone(self::$default_timezone);
        }
    }

    /**
     * Parse an event date from PCO API format.
     *
     * @param string $iso_string ISO 8601 date string from API
     * @param bool $is_all_day Whether the event is all-day
     * @param DateTimeZone|null $target_tz Target timezone (optional)
     * @param bool $is_end_date Whether this is an end date (affects all-day handling)
     * @return DateTime
     */
    public static function parse_event_date($iso_string, $is_all_day = false, $target_tz = null, $is_end_date = false) {
        if ($target_tz === null) {
            $target_tz = self::get_timezone();
        }

        if ($is_all_day) {
            // For all-day events, extract the date portion only
            $dateStr = substr($iso_string, 0, 10);
            $dt = new DateTime($dateStr . ' 12:00:00', $target_tz);

            // For END dates of all-day events, subtract one day
            // (PCO API stores end-of-day as ~5am UTC next day)
            if ($is_end_date) {
                $time = substr($iso_string, 11, 8);
                $hour = (int) substr($time, 0, 2);
                if ($hour < 12) {
                    $dt->modify('-1 day');
                }
            }

            return $dt;
        } else {
            // Parse as UTC, then convert to target timezone (handles DST automatically)
            $dt = new DateTime($iso_string, new DateTimeZone('UTC'));
            $dt->setTimezone($target_tz);
            return $dt;
        }
    }

    /**
     * Expand a multi-day event into array of individual dates.
     *
     * @param string $starts_at Start date ISO string
     * @param string $ends_at End date ISO string
     * @param bool $is_all_day Whether the event is all-day
     * @param DateTimeZone|null $target_tz Target timezone
     * @return array Array of date strings (Y-m-d format)
     */
    public static function expand_multi_day_event($starts_at, $ends_at, $is_all_day, $target_tz = null) {
        $dates = [];

        if ($target_tz === null) {
            $target_tz = self::get_timezone();
        }

        try {
            $start = self::parse_event_date($starts_at, $is_all_day, $target_tz, false);
            $end = self::parse_event_date($ends_at, $is_all_day, $target_tz, true);

            $current = clone $start;
            while ($current <= $end) {
                $dates[] = $current->format('Y-m-d');
                $current->modify('+1 day');
            }
        } catch (Exception $e) {
            try {
                $start = self::parse_event_date($starts_at, $is_all_day, $target_tz, false);
                $dates[] = $start->format('Y-m-d');
            } catch (Exception $e2) {
                // Return empty array if all parsing fails
            }
        }

        return $dates;
    }

    /**
     * Get formatted date display string for an event.
     * Handles single-day and multi-day events appropriately.
     *
     * @param string $starts_at Start date ISO string
     * @param string|null $ends_at End date ISO string (optional)
     * @param bool $is_all_day Whether the event is all-day
     * @param DateTimeZone|null $target_tz Target timezone
     * @return string Formatted date string
     */
    public static function get_date_display($starts_at, $ends_at = null, $is_all_day = false, $target_tz = null) {
        if ($target_tz === null) {
            $target_tz = self::get_timezone();
        }

        try {
            $start_dt = self::parse_event_date($starts_at, $is_all_day, $target_tz, false);

            if ($ends_at) {
                $end_dt = self::parse_event_date($ends_at, $is_all_day, $target_tz, true);

                if ($start_dt->format('Y-m-d') !== $end_dt->format('Y-m-d')) {
                    // Multi-day event
                    if ($start_dt->format('Y') === $end_dt->format('Y')) {
                        if ($start_dt->format('m') === $end_dt->format('m')) {
                            // Same month: "April 23-26, 2026"
                            return $start_dt->format('F j') . '-' . $end_dt->format('j, Y');
                        } else {
                            // Different months: "April 30-May 3, 2026"
                            return $start_dt->format('F j') . '-' . $end_dt->format('F j, Y');
                        }
                    } else {
                        // Different years
                        return $start_dt->format('F j, Y') . '-' . $end_dt->format('F j, Y');
                    }
                }
            }

            // Single day
            return $start_dt->format('l, M j');
        } catch (Exception $e) {
            return 'Date Error';
        }
    }

    /**
     * Get time display string.
     *
     * @param string $iso_string ISO date string for start time
     * @param bool $is_all_day Whether the event is all-day
     * @param DateTimeZone|null $target_tz Target timezone
     * @param string|null $end_iso_string ISO date string for end time (optional)
     * @return string Time string (e.g., "10–11:15am" or "All Day")
     */
    public static function get_time_display($iso_string, $is_all_day = false, $target_tz = null, $end_iso_string = null) {
        if ($is_all_day) {
            return 'All Day';
        }

        if ($target_tz === null) {
            $target_tz = self::get_timezone();
        }

        try {
            $start_dt = self::parse_event_date($iso_string, false, $target_tz, false);

            // If we have an end time, format as a range
            if (!empty($end_iso_string)) {
                $end_dt = self::parse_event_date($end_iso_string, false, $target_tz, false);

                // Format: "10–11:15am" or "10am–12pm" if different am/pm
                $start_hour = $start_dt->format('g');
                $end_minutes = $end_dt->format('i');
                $end_format = ($end_minutes === '00') ? 'ga' : 'g:ia';

                return $start_hour . '–' . $end_dt->format($end_format);
            }

            // Just start time
            $minutes = $start_dt->format('i');
            if ($minutes === '00') {
                return $start_dt->format('ga'); // "6pm"
            }
            return $start_dt->format('g:ia'); // "6:30pm"
        } catch (Exception $e) {
            return 'Time Error';
        }
    }

    /**
     * Format a date for display with optional time.
     *
     * @param string $iso_string ISO date string
     * @param string $format PHP date format
     * @param DateTimeZone|null $target_tz Target timezone
     * @return string Formatted date string
     */
    public static function format_date($iso_string, $format = 'M j, Y', $target_tz = null) {
        if ($target_tz === null) {
            $target_tz = self::get_timezone();
        }

        try {
            $dt = new DateTime($iso_string, new DateTimeZone('UTC'));
            $dt->setTimezone($target_tz);
            return $dt->format($format);
        } catch (Exception $e) {
            return 'Invalid Date';
        }
    }
}
