<?php
/**
 * Signups Admin Component
 *
 * Handles all backend/admin functionality for the Signups module.
 * Manages event signups, registrations, and settings.
 */

class SimplePCO_Signups_Admin {

    private $loader;
    private $api_model;
    private $signups_handler;

    public function __construct($loader, $api_model) {
        $this->loader = $loader;
        $this->api_model = $api_model;
        
        // Initialize signups handler
        global $simplepco_signups_handler;
        if ($simplepco_signups_handler) {
            $this->signups_handler = $simplepco_signups_handler;
        }
    }

    /**
     * Initialize admin functionality.
     */
    public function init() {
        // Add admin pages
        $this->loader->add_action('admin_menu', $this, 'add_admin_pages');
        
        // Enqueue admin assets
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
        
        // Handle form submissions
        $this->loader->add_action('admin_init', $this, 'handle_signup_save');
        $this->loader->add_action('admin_init', $this, 'handle_registration_add');
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_pages() {
        // Signups management page
        add_submenu_page(
            'simplepco-settings',
            __('Event Signups', 'simplepco'),
            __('Signups', 'simplepco'),
            'edit_posts',
            'simplepco-signups',
            [$this, 'render_signups_page']
        );

        // Registrations page
        add_submenu_page(
            'simplepco-settings',
            __('Event Registrations', 'simplepco'),
            __('Registrations', 'simplepco'),
            'edit_posts',
            'simplepco-registrations',
            [$this, 'render_registrations_page']
        );
    }

    /**
     * Enqueue admin-specific assets.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our pages
        if (strpos($hook, 'simplepco-signups') === false && strpos($hook, 'simplepco-registrations') === false) {
            return;
        }

        wp_enqueue_style(
            'simplepco-signups-admin',
            SIMPLEPCO_PLUGIN_URL . 'modules/signups/admin/assets/css/signups-admin.css',
            [],
            SIMPLEPCO_VERSION
        );

        wp_enqueue_script(
            'simplepco-signups-admin',
            SIMPLEPCO_PLUGIN_URL . 'modules/signups/admin/assets/js/signups-admin.js',
            ['jquery'],
            SIMPLEPCO_VERSION,
            true
        );
    }

    /**
     * Main router for Signups pages.
     */
    public function render_signups_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'simplepco'));
        }

        $view = $_REQUEST['view'] ?? 'list';

        if ($view === 'edit' || $view === 'new') {
            $this->render_signup_edit_page();
        } else {
            $this->render_signups_list_page();
        }
    }

    /**
     * Main router for Registrations pages.
     */
    public function render_registrations_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'simplepco'));
        }

        $view = $_REQUEST['view'] ?? 'list';

        if ($view === 'registrants' && isset($_GET['signup_id'])) {
            $this->render_registrants_list_page(intval($_GET['signup_id']));
        } elseif ($view === 'detail' && isset($_GET['id'])) {
            $this->render_registrant_detail_page(intval($_GET['id']));
        } elseif ($view === 'add' && isset($_GET['signup_id'])) {
            $this->render_add_registration_page(intval($_GET['signup_id']));
        } else {
            $this->render_signups_with_registrations_page();
        }
    }

    /**
     * Render Signups List Page.
     * Prepares data and loads template.
     */
    private function render_signups_list_page() {
        // Handle deletion
        $deleted = false;
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('delete_signup_' . $_GET['id']);
            $this->signups_handler->delete_signup(intval($_GET['id']));
            $deleted = true;
        }

        $signups = $this->signups_handler->get_signups();
        
        // Add registration counts to each signup
        foreach ($signups as $signup) {
            $signup->registration_count = $this->signups_handler->get_registration_count($signup->id, false);
        }

        $template_data = [
            'signups' => $signups,
            'deleted' => $deleted
        ];

        $this->load_template('signups-list', $template_data);
    }

    /**
     * Render Signup Edit Page.
     * Prepares data and loads template.
     */
    private function render_signup_edit_page() {
        $signup_id = $_GET['id'] ?? null;
        $signup = $signup_id ? $this->signups_handler->get_signup($signup_id) : null;
        $is_new = empty($signup);

        // Get PCO events for dropdown
        $pco_events = $this->signups_handler->get_pco_events(100);

        $template_data = [
            'signup' => $signup,
            'is_new' => $is_new,
            'pco_events' => $pco_events,
            'updated' => isset($_GET['updated'])
        ];

        $this->load_template('signup-edit', $template_data);
    }

    /**
     * Render Registrations Overview Page.
     * Shows all signups with registration counts.
     */
    private function render_signups_with_registrations_page() {
        $signups = $this->signups_handler->get_signups_with_registrations();

        $template_data = [
            'signups' => $signups
        ];

        $this->load_template('registrations-list', $template_data);
    }

    /**
     * Render Registrants List for a specific signup.
     */
    private function render_registrants_list_page($signup_id) {
        // Handle deletion
        $deleted = false;
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['reg_id'])) {
            check_admin_referer('delete_registration_' . $_GET['reg_id']);
            $this->signups_handler->delete_registration(intval($_GET['reg_id']));
            $deleted = true;
        }

        $signup = $this->signups_handler->get_signup($signup_id);
        $registrations = $this->signups_handler->get_registrations_by_signup($signup_id);

        // Separate confirmed and waitlist
        $confirmed = [];
        $waitlist = [];
        foreach ($registrations as $reg) {
            if ($reg->is_waitlist) {
                $waitlist[] = $reg;
            } else {
                $confirmed[] = $reg;
            }
        }

        $template_data = [
            'signup' => $signup,
            'confirmed' => $confirmed,
            'waitlist' => $waitlist,
            'deleted' => $deleted
        ];

        $this->load_template('registrants-list', $template_data);
    }

    /**
     * Render Registrant Detail Page.
     */
    private function render_registrant_detail_page($registration_id) {
        $registration = $this->signups_handler->get_registration($registration_id);

        if (!$registration) {
            $this->load_template('registrant-detail', ['error' => 'Registration not found']);
            return;
        }

        $signup = $this->signups_handler->get_signup($registration->signup_id);
        $form_data = json_decode($registration->form_data, true);

        $template_data = [
            'registration' => $registration,
            'signup' => $signup,
            'form_data' => $form_data
        ];

        $this->load_template('registrant-detail', $template_data);
    }

    /**
     * Render Add Registration Page.
     */
    private function render_add_registration_page($signup_id) {
        $signup = $this->signups_handler->get_signup($signup_id);

        if (!$signup) {
            $this->load_template('registration-add', ['error' => 'Signup not found']);
            return;
        }

        $template_data = [
            'signup' => $signup,
            'saved' => isset($_GET['saved'])
        ];

        $this->load_template('registration-add', $template_data);
    }

    /**
     * Handle signup form submission.
     */
    public function handle_signup_save() {
        if (!isset($_POST['save_signup'])) {
            return;
        }

        check_admin_referer('save_signup');

        if (!current_user_can('edit_posts')) {
            return;
        }

        $signup_id = $this->signups_handler->save_signup($_POST);

        $redirect_url = add_query_arg([
            'page' => 'simplepco-signups',
            'view' => 'edit',
            'id' => $signup_id,
            'updated' => '1'
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handle registration form submission.
     */
    public function handle_registration_add() {
        if (!isset($_POST['add_registration'])) {
            return;
        }

        check_admin_referer('add_registration');

        if (!current_user_can('edit_posts')) {
            return;
        }

        $signup_id = intval($_POST['signup_id']);
        
        // Save registration
        $registration_data = [
            'signup_id' => $signup_id,
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'registration_date' => current_time('mysql'),
            'form_data' => json_encode(['manual_entry' => true]),
            'payment_status' => 'pending'
        ];

        $this->signups_handler->save_registration($registration_data);

        $redirect_url = add_query_arg([
            'page' => 'simplepco-registrations',
            'view' => 'add',
            'signup_id' => $signup_id,
            'saved' => '1'
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Load a template file.
     */
    private function load_template($template_name, $data = []) {
        extract($data);
        $template_path = SIMPLEPCO_PLUGIN_DIR . 'templates/signups/admin/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>Error</h1><p>Template not found: ' . esc_html($template_name) . '</p></div>';
        }
    }
}
