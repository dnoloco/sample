<?php
/**
 * Services Admin Component
 *
 * Handles all backend/admin functionality for the Services module.
 * Manages service plans, team members, reports, and message composition.
 */

class SimplePCO_Services_Admin
{

    private $loader;
    private $api_model;

    public function __construct($loader, $api_model)
    {
        $this->loader = $loader;
        $this->api_model = $api_model;
    }

    /**
     * Initialize admin functionality.
     */
    public function init()
    {
        // Add admin pages
        $this->loader->add_action('admin_menu', $this, 'add_admin_pages');

        // Enqueue admin assets
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');

        // Handle Clearstream message sending
        $this->loader->add_action('admin_init', $this, 'handle_clearstream_send');

        // Keep Messages menu active when on compose page
        $this->loader->add_filter('parent_file', $this, 'set_messages_menu_active');
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_pages()
    {
        // Change 'simplepco-settings' to 'simplepco-dashboard'
        add_submenu_page(
            'simplepco-dashboard',
            __('Service Plans', 'simplepco'),
            __('Service Plans', 'simplepco'),
            'edit_posts',
            'simplepco-services',
            [$this, 'render_services_page']
        );

        // Team Reports - hidden from menu (will be added as premium module later)
        // Use 'options.php' as parent to create a hidden page (avoids null deprecation in PHP 8)
        add_submenu_page(
            'options.php',
            __('Team Reports', 'simplepco'),
            __('Team Reports', 'simplepco'),
            'edit_posts',
            'simplepco-services-reports',
            [$this, 'render_reports_page']
        );
    }

    /**
     * Enqueue admin-specific assets.
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on our pages
        if (strpos($hook, 'simplepco-services') === false) {
            return;
        }

        wp_enqueue_style(
            'simplepco-services-admin',
            SIMPLEPCO_PLUGIN_URL . 'inc/modules/services/admin/assets/css/services-admin.css',
            [],
            SIMPLEPCO_VERSION
        );

        wp_enqueue_script(
            'simplepco-services-admin',
            SIMPLEPCO_PLUGIN_URL . 'inc/modules/services/admin/assets/js/services-admin.js',
            ['jquery'],
            SIMPLEPCO_VERSION,
            true
        );
    }

    /**
     * Main router for Services pages.
     */
    public function render_services_page()
    {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'simplepco'));
        }

        $view = $_REQUEST['view'] ?? 'list';

        if ($view === 'plan_details' && isset($_REQUEST['plan_id'])) {
            $this->render_plan_details_page(sanitize_text_field($_REQUEST['plan_id']));
        } elseif ($view === 'clearstream_compose') {
            $this->render_compose_message_page();
        } else {
            $this->render_plans_list_page();
        }
    }

    /**
     * Render Plans List Page.
     * Prepares data and loads template.
     */
    private function render_plans_list_page()
    {
        // Get service types
        $service_types_response = $this->api_model->get_service_types();
        $service_types = $service_types_response['data'] ?? [];

        if (empty($service_types)) {
            $this->load_template('plans-list', ['error' => 'No service types found.']);
            return;
        }

        // Get filter parameters
        $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : 'all';
        $filter_month = isset($_GET['filter_month']) ? sanitize_text_field($_GET['filter_month']) : 'all';
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc';

        // Fetch and process all plans
        $plans_data = $this->fetch_all_plans($service_types);

        // Sort plans
        $all_plans = $this->sort_plans($plans_data['plans'], $orderby, $order);

        // Prepare data for template
        $template_data = [
            'service_types' => $service_types,
            'all_plans' => $all_plans,
            'type_counts' => $plans_data['type_counts'],
            'available_months' => $plans_data['available_months'],
            'filter_type' => $filter_type,
            'filter_month' => $filter_month,
            'orderby' => $orderby,
            'order' => $order,
            'success_message' => $this->get_success_message(),
            'error_message' => $this->get_error_message()
        ];

        $this->load_template('plans-list', $template_data);
    }

    /**
     * Fetch all plans from all service types.
     */
    private function fetch_all_plans($service_types)
    {
        $all_plans = [];
        $type_counts = ['all' => 0];
        $available_months = [];

        foreach ($service_types as $type) {
            $type_id = $type['id'];
            $type_name = $type['attributes']['name'] ?? 'Unknown';
            $default_service_time = $type['attributes']['default_service_time'] ?? '10:00:00';

            $type_counts[$type_id] = 0;

            // Fetch plans for this service type
            $plans_response = $this->api_model->get_upcoming_plans($type_id, 100);
            $plans = $plans_response['data'] ?? [];

            foreach ($plans as $plan) {
                $processed_plan = $this->process_plan($plan, $type_id, $type_name, $default_service_time);

                if ($processed_plan) {
                    $all_plans[] = $processed_plan;
                    $type_counts['all']++;
                    $type_counts[$type_id]++;

                    if (!in_array($processed_plan['month_year'], $available_months)) {
                        $available_months[] = $processed_plan['month_year'];
                    }
                }
            }
        }

        return [
            'plans' => $all_plans,
            'type_counts' => $type_counts,
            'available_months' => $available_months
        ];
    }

    /**
     * Process a single plan for display.
     */
    private function process_plan($plan, $type_id, $type_name, $default_service_time)
    {
        $p_attr = $plan['attributes'];
        $plan_id = $plan['id'];

        // Get date/time
        $pco_datetime_string = $p_attr['sort_date'] ?? $p_attr['dates'] ?? null;
        if (empty($pco_datetime_string)) {
            return null;
        }

        // Determine service time
        $actual_service_time = $default_service_time;
        if (!empty($p_attr['times']) && is_array($p_attr['times'])) {
            $time_data = $p_attr['times'][0] ?? null;
            if (!empty($time_data['time'])) {
                $actual_service_time = $time_data['time'];
            }
        }

        // Extract date and combine with time
        $date_part = substr($pco_datetime_string, 0, 10);
        $combined_datetime_string = $date_part . ' ' . $actual_service_time;

        try {
            $local_timezone = wp_timezone_string();
            $tz_object = new DateTimeZone($local_timezone);
            $plan_date = new DateTime($combined_datetime_string, $tz_object);

            $date_str = $plan_date->format('D, M j, Y');
            $time_str = $plan_date->format('g:i A');
            $month_year = $plan_date->format('F Y');
            $sort_date = $plan_date->format('Y-m-d H:i:s');

        } catch (Exception $e) {
            return null;
        }

        $specific_plan_title = $p_attr['title'] ?? $p_attr['series_title'] ?? 'Untitled Plan';
        $series_title = $p_attr['series_title'] ?? '—';

        return [
            'plan_id' => $plan_id,
            'title' => $specific_plan_title,
            'series' => $series_title,
            'date_str' => $date_str,
            'time_str' => $time_str,
            'month_year' => $month_year,
            'sort_date' => $sort_date,
            'type_id' => $type_id,
            'type_name' => $type_name,
            'pco_edit_link' => "https://services.planningcenteronline.com/plans/" . $plan_id
        ];
    }

    /**
     * Sort plans by specified column and order.
     */
    private function sort_plans($plans, $orderby, $order)
    {
        usort($plans, function ($a, $b) use ($orderby, $order) {
            if ($orderby === 'date') {
                $val_a = $a['sort_date'];
                $val_b = $b['sort_date'];
            } elseif ($orderby === 'type_name') {
                $val_a = $a['type_name'];
                $val_b = $b['type_name'];
            } elseif ($orderby === 'title') {
                $val_a = $a['title'];
                $val_b = $b['title'];
            } else {
                $val_a = $a['sort_date'];
                $val_b = $b['sort_date'];
            }

            $result = strcasecmp($val_a, $val_b);
            return $order === 'asc' ? $result : -$result;
        });

        return $plans;
    }

    /**
     * Get success message from transient.
     */
    private function get_success_message()
    {
        if (!isset($_GET['message'])) {
            return null;
        }

        if ($_GET['message'] === 'sent' || $_GET['message'] === 'scheduled') {
            $success_data = get_transient('simplepco_clearstream_success_message');
            if ($success_data) {
                delete_transient('simplepco_clearstream_success_message');
                return $success_data;
            }
        }

        return null;
    }

    /**
     * Get error message from transient.
     */
    private function get_error_message()
    {
        if (!isset($_GET['message']) || $_GET['message'] !== 'error') {
            return null;
        }

        $error = get_transient('simplepco_clearstream_error_message');
        if ($error) {
            delete_transient('simplepco_clearstream_error_message');
            return $error;
        }

        return null;
    }

    /**
     * Render Plan Details Page.
     * Prepares data and loads template.
     */
    private function render_plan_details_page($plan_id)
    {
        $response = $this->api_model->get_single_plan($plan_id);

        if (isset($response['error']) || empty($response['data'])) {
            $this->load_template('plan-details', ['error' => 'Plan not found or API error.']);
            return;
        }

        $plan = $response['data'];

        // Process plan data
        $plan_data = $this->process_plan_details($plan);

        // Fetch team members
        $team_data = $this->fetch_plan_team_members($plan_id, $plan_data['service_type_id']);

        // Combine data for template
        $template_data = array_merge($plan_data, $team_data);

        $this->load_template('plan-details', $template_data);
    }

    /**
     * Process plan details for display.
     */
    private function process_plan_details($plan)
    {
        $p_attr = $plan['attributes'];

        // Get service type
        $service_type_id = $plan['relationships']['service_type']['data']['id'] ?? null;
        $default_time = '10:00:00';

        if ($service_type_id) {
            $service_type_details = $this->api_model->get_single_service_type($service_type_id);
            $default_time = $service_type_details['data']['attributes']['default_service_time'] ?? '10:00:00';
        }

        // Get timezone
        $local_timezone_string = $this->api_model->get_timezone() ?? wp_timezone_string();
        try {
            $tz_object = new DateTimeZone($local_timezone_string);
        } catch (Exception $e) {
            $tz_object = new DateTimeZone('UTC');
        }

        // Process date/time
        $pco_datetime_string = $p_attr['dates'] ?? null;
        $actual_service_time = $default_time;

        if (!empty($p_attr['times']) && is_array($p_attr['times'])) {
            $time_data = $p_attr['times'][0] ?? null;
            if (!empty($time_data['time'])) {
                $actual_service_time = $time_data['time'];
            }
        }

        $title = $p_attr['title'] ?? $p_attr['series_title'] ?? 'Untitled Plan';
        $series = $p_attr['series_title'] ?? '';

        $date_str = 'N/A';
        $time_str = 'N/A';
        $day_str = 'N/A';

        if (!empty($pco_datetime_string)) {
            try {
                $plan_date = new DateTime($pco_datetime_string);
                $plan_date->setTimezone($tz_object);

                list($hour, $minute, $second) = explode(':', $actual_service_time);
                $plan_date->setTime((int)$hour, (int)$minute, (int)$second);

                $day_str = $plan_date->format('D');
                $date_str = $plan_date->format('M j, Y');
                $time_str = $plan_date->format('g:ia');

            } catch (Exception $e) {
                $date_str = 'Date Error';
                $time_str = 'Time Error';
            }
        }

        return [
            'plan_id' => $plan['id'],
            'title' => $title,
            'series' => $series,
            'day_str' => $day_str,
            'date_str' => $date_str,
            'time_str' => $time_str,
            'service_type_id' => $service_type_id,
            'pco_edit_link' => "https://services.planningcenteronline.com/plans/" . $plan['id']
        ];
    }

    /**
     * Fetch and process team members for a plan.
     */
    private function fetch_plan_team_members($plan_id, $service_type_id)
    {
        // Fetch team name mappings
        $teams_response = $this->api_model->get_all_teams();
        $team_name_map = [];

        if (!empty($teams_response['data'])) {
            foreach ($teams_response['data'] as $team_obj) {
                $id = $team_obj['id'];
                $name = $team_obj['attributes']['name'] ?? 'Unknown Team';
                $team_name_map[$id] = $name;
            }
        }

        // Fetch position name mappings
        $position_response = $this->api_model->get_team_positions($service_type_id);
        $position_name_map = [];

        if (!empty($position_response['data'])) {
            foreach ($position_response['data'] as $pos_obj) {
                $id = $pos_obj['id'];
                $name = $pos_obj['attributes']['name'] ?? 'Unknown Position';
                $position_name_map[$id] = $name;
            }
        }

        // Fetch team members
        $all_members = [];
        $team_summary = [];
        $status_counts = ['all' => 0, 'C' => 0, 'U' => 0, 'D' => 0];

        $team_response = $this->api_model->get_plan_team_members($plan_id);
        $team_members_data = $team_response['data'] ?? [];

        // Process included positions
        $included_positions = [];
        if (!empty($team_response['included'])) {
            foreach ($team_response['included'] as $included_item) {
                if ($included_item['type'] === 'TeamPosition') {
                    $included_positions[$included_item['id']] = $included_item['attributes']['name'] ?? '';
                }
            }
        }

        // Process each team member
        foreach ($team_members_data as $tm_obj) {
            $member = $tm_obj['attributes'];
            $team_id = $tm_obj['relationships']['team']['data']['id'] ?? null;
            $person_id = $tm_obj['relationships']['person']['data']['id'] ?? null;
            $team_name = $team_name_map[$team_id] ?? 'Unassigned Team';

            $name = $member['name'] ?? 'Unknown Person';
            $status = $member['status'] ?? 'N';

            // Get position name from multiple sources
            $position_name = $this->get_position_name(
                $tm_obj,
                $included_positions,
                $position_name_map,
                $person_id,
                $plan_id
            );

            if (!in_array($status, ['C', 'U', 'D'])) {
                continue;
            }

            // Extract names for sorting
            $name_parts = explode(' ', $name);
            $last_name = end($name_parts);
            $first_name = count($name_parts) > 1 ? $name_parts[0] : '';

            $member_data = [
                'name' => $name,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'status' => $status,
                'person_id' => $person_id,
                'team_id' => $team_id,
                'team_name' => $team_name,
                'position' => $position_name
            ];

            $all_members[] = $member_data;
            $status_counts['all']++;
            $status_counts[$status]++;

            // Build team summary
            if (!isset($team_summary[$team_name])) {
                $team_summary[$team_name] = 0;
            }
            $team_summary[$team_name]++;
        }

        // Sort members by last name
        usort($all_members, function ($a, $b) {
            return strcmp($a['last_name'], $b['last_name']);
        });

        return [
            'team_members' => $all_members,
            'team_summary' => $team_summary,
            'status_counts' => $status_counts,
            'team_name_map' => $team_name_map
        ];
    }

    /**
     * Get position name from multiple sources.
     */
    private function get_position_name($tm_obj, $included_positions, $position_name_map, $person_id, $plan_id)
    {
        $position_name = '';

        // 1. Check team_position relationship from included data
        $team_position_id = $tm_obj['relationships']['team_position']['data']['id'] ?? null;
        if ($team_position_id && isset($included_positions[$team_position_id])) {
            $position_name = $included_positions[$team_position_id];
        }

        // 2. Check if position is directly in attributes
        if (empty($position_name)) {
            $position_name = $tm_obj['attributes']['team_position_name'] ?? '';
        }

        // 3. Check schedules
        if (empty($position_name) && $person_id) {
            $schedule_response = $this->api_model->get_person_schedules($person_id);
            $schedules = $schedule_response['data'] ?? [];

            foreach ($schedules as $schedule) {
                $schedule_plan_id = $schedule['relationships']['plan']['data']['id'] ?? null;
                if ($schedule_plan_id == $plan_id) {
                    $position_name = $schedule['attributes']['position_name'] ??
                        $schedule['attributes']['team_position_name'] ??
                        $schedule['attributes']['assignment_name'] ??
                        $schedule['attributes']['title'] ??
                        '';
                    break;
                }
            }
        }

        // 4. Fallback to position map
        if (empty($position_name) && $team_position_id && isset($position_name_map[$team_position_id])) {
            $position_name = $position_name_map[$team_position_id];
        }

        return $position_name;
    }

    /**
     * Keep Messages menu active when on compose page.
     */
    public function set_messages_menu_active($parent_file)
    {
        global $submenu_file;

        if (isset($_GET['page']) && $_GET['page'] === 'simplepco-services'
            && isset($_GET['view']) && $_GET['view'] === 'clearstream_compose'
            && isset($_GET['from']) && $_GET['from'] === 'messages') {
            $submenu_file = 'simplepco-message-log';
        }

        return $parent_file ?? '';
    }

    /**
     * Load a template file.
     */
    private function load_template($template_name, $data = [])
    {
        extract($data);
        $template_path = SIMPLEPCO_PLUGIN_DIR . 'templates/services/admin/' . $template_name . '.php';

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>Error</h1><p>Template not found: ' . esc_html($template_name) . '</p></div>';
        }
    }

    /**
     * Render Reports Page.
     * Prepares data and loads template.
     */
    public function render_reports_page()
    {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'simplepco'));
        }

        // For now, just load the placeholder template
        // TODO: Implement actual reports functionality
        $template_data = [];

        $this->load_template('reports-page', $template_data);
    }

    /**
     * Render Compose Message Page.
     * Note: This may be moved to Messages module.
     */
    private function render_compose_message_page()
    {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'simplepco'));
        }

        // Get plan ID if composing from plan details
        $plan_id = isset($_GET['plan_id']) ? sanitize_text_field($_GET['plan_id']) : null;
        $from = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : 'services';

        // Prepare data for message composition
        $template_data = [
            'plan_id' => $plan_id,
            'from' => $from
        ];

        // If plan_id provided, fetch plan and team members
        if ($plan_id) {
            $response = $this->api_model->get_single_plan($plan_id);
            if (!isset($response['error']) && !empty($response['data'])) {
                $plan = $response['data'];
                $plan_data = $this->process_plan_details($plan);
                $team_data = $this->fetch_plan_team_members($plan_id, $plan_data['service_type_id']);

                $template_data = array_merge($template_data, $plan_data, $team_data);
            }
        }

        $this->load_template('compose-message', $template_data);
    }

    /**
     * Handle Clearstream message sending.
     * This processes the form submission from compose message page.
     */
    public function handle_clearstream_send()
    {
        // Check if this is a Clearstream send request
        if (!isset($_POST['send_clearstream_message']) && !isset($_POST['schedule_clearstream_message'])) {
            return;
        }

        // Verify nonce
        check_admin_referer('send_clearstream_message');

        // Check permissions
        if (!$this->user_can_send_clearstream()) {
            wp_redirect(admin_url('admin.php?page=simplepco-services&message=no_permission'));
            exit;
        }

        // Get form data
        $message_body = isset($_POST['message_body']) ? sanitize_textarea_field($_POST['message_body']) : '';
        $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : [];
        $send_type = isset($_POST['schedule_clearstream_message']) ? 'scheduled' : 'immediate';
        $scheduled_datetime = isset($_POST['scheduled_datetime']) ? sanitize_text_field($_POST['scheduled_datetime']) : '';

        // Validate
        if (empty($message_body)) {
            set_transient('simplepco_clearstream_error_message', [
                'code' => 400,
                'message' => 'Message body is required'
            ], 60);
            wp_redirect(admin_url('admin.php?page=simplepco-services&view=clearstream_compose&message=error'));
            exit;
        }

        if (empty($recipients)) {
            set_transient('simplepco_clearstream_error_message', [
                'code' => 400,
                'message' => 'At least one recipient is required'
            ], 60);
            wp_redirect(admin_url('admin.php?page=simplepco-services&view=clearstream_compose&message=error'));
            exit;
        }

        // Send via Clearstream
        $result = $this->send_clearstream_message($message_body, $recipients, $send_type, $scheduled_datetime);

        if ($result['success']) {
            set_transient('simplepco_clearstream_success_message', [
                'count' => $result['count'],
                'status' => $send_type,
                'scheduled_at' => $scheduled_datetime
            ], 60);

            $message_param = $send_type === 'scheduled' ? 'scheduled' : 'sent';
            wp_redirect(admin_url('admin.php?page=simplepco-services&message=' . $message_param));
        } else {
            set_transient('simplepco_clearstream_error_message', [
                'code' => $result['code'],
                'message' => $result['message']
            ], 60);
            wp_redirect(admin_url('admin.php?page=simplepco-services&view=clearstream_compose&message=error'));
        }

        exit;
    }

    /**
     * Check if current user can send Clearstream messages.
     */
    private function user_can_send_clearstream()
    {
        // Editors and above can send
        return current_user_can('edit_pages');
    }

    /**
     * Send message via Clearstream API.
     *
     * @param string $message_body Message text
     * @param array $recipients Array of phone numbers or subscriber IDs
     * @param string $send_type 'immediate' or 'scheduled'
     * @param string $scheduled_datetime Datetime for scheduled messages
     * @return array Result with success, count, code, message
     */
    private function send_clearstream_message($message_body, $recipients, $send_type = 'immediate', $scheduled_datetime = '')
    {
        $api_token = get_option('clearstream_api_token');

        if (empty($api_token)) {
            return [
                'success' => false,
                'code' => 500,
                'message' => 'Clearstream API token not configured'
            ];
        }

        // Build API request
        $api_url = 'https://api.getclearstream.com/v1/messages';

        $body = [
            'message' => $message_body,
            'subscribers' => array_map('sanitize_text_field', $recipients)
        ];

        if ($send_type === 'scheduled' && !empty($scheduled_datetime)) {
            $body['scheduled_at'] = $scheduled_datetime;
        }

        $args = [
            'headers' => [
                'X-API-Key' => $api_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($body),
            'timeout' => 30
        ];

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'code' => 500,
                'message' => $response->get_error_message()
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code === 200 || $response_code === 201) {
            // Log the message
            $this->log_clearstream_message($message_body, count($recipients), $send_type, $scheduled_datetime);

            return [
                'success' => true,
                'count' => count($recipients),
                'code' => $response_code,
                'message' => 'Message sent successfully'
            ];
        }

        return [
            'success' => false,
            'code' => $response_code,
            'message' => $data['message'] ?? 'Unknown error'
        ];
    }

    /**
     * Log Clearstream message to database.
     */
    private function log_clearstream_message($message_body, $recipient_count, $status, $scheduled_at = null)
    {
        global $wpdb;

        $current_user = wp_get_current_user();

        $wpdb->insert(
            $wpdb->prefix . 'simplepco_clearstream_log',
            [
                'sender_name' => $current_user->display_name,
                'sender_id' => $current_user->ID,
                'recipient_count' => $recipient_count,
                'message_body' => $message_body,
                'status' => $status,
                'scheduled_at' => $scheduled_at,
                'sent_at' => current_time('mysql')
            ],
            ['%s', '%d', '%d', '%s', '%s', '%s', '%s']
        );
    }
}
