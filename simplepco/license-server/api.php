<?php
/**
 * SimplePCO License Server API
 *
 * Endpoints:
 * - POST /api.php?action=validate    - Validate a license key
 * - POST /api.php?action=activate    - Activate license on a site
 * - POST /api.php?action=deactivate  - Deactivate license from a site
 * - POST /api.php?action=check_update - Check for plugin updates
 *
 * Upload this to your Hostinger site: your-site.com/simplepco-license/api.php
 */

define('SIMPLEPCO_LICENSE_SERVER', true);

// Load configuration
require_once __DIR__ . '/config.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGINS);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Database connection
 */
function get_db_connection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            send_response(false, 'Database connection failed', null, 500);
        }
    }

    return $pdo;
}

/**
 * Send JSON response
 */
function send_response($success, $message, $data = null, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ]);
    exit;
}

/**
 * Get POST data
 */
function get_post_data() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        $data = $_POST;
    }

    return $data;
}

/**
 * Validate license key format
 */
function is_valid_key_format($key) {
    // Format: SIMPLEPCO-XXXX-XXXX-XXXX-XXXX (alphanumeric)
    return preg_match('/^SIMPLEPCO-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key);
}

/**
 * Get license by key
 */
function get_license($license_key) {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare('
        SELECT l.*,
               COUNT(a.id) as active_sites,
               (SELECT GROUP_CONCAT(a2.site_url) FROM simplepco_activations a2 WHERE a2.license_id = l.id) as activated_urls
        FROM simplepco_licenses l
        LEFT JOIN simplepco_activations a ON l.id = a.license_id
        WHERE l.license_key = ?
        GROUP BY l.id
    ');
    $stmt->execute([$license_key]);

    return $stmt->fetch();
}

/**
 * Get site activation for a license
 */
function get_activation($license_id, $site_url) {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare('
        SELECT * FROM simplepco_activations
        WHERE license_id = ? AND site_url = ?
    ');
    $stmt->execute([$license_id, normalize_url($site_url)]);

    return $stmt->fetch();
}

/**
 * Normalize URL for comparison
 */
function normalize_url($url) {
    $url = strtolower(trim($url));
    $url = preg_replace('#^https?://#', '', $url);
    $url = rtrim($url, '/');
    return $url;
}

/**
 * ACTION: Validate License
 */
function action_validate() {
    $data = get_post_data();

    $license_key = isset($data['license_key']) ? trim($data['license_key']) : '';
    $site_url = isset($data['site_url']) ? trim($data['site_url']) : '';

    if (empty($license_key)) {
        send_response(false, 'License key is required');
    }

    if (!is_valid_key_format($license_key)) {
        send_response(false, 'Invalid license key format');
    }

    $license = get_license($license_key);

    if (!$license) {
        send_response(false, 'License key not found');
    }

    // Check if license is active
    if ($license['status'] !== 'active') {
        send_response(false, 'License is ' . $license['status'], [
            'status' => $license['status']
        ]);
    }

    // Check expiration
    if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
        send_response(false, 'License has expired', [
            'status' => 'expired',
            'expired_at' => $license['expires_at']
        ]);
    }

    // Get tier info
    $tiers = unserialize(LICENSE_TIERS);
    $tier_info = isset($tiers[$license['tier']]) ? $tiers[$license['tier']] : null;

    // Check if this site is activated
    $is_site_activated = false;
    if (!empty($site_url)) {
        $activation = get_activation($license['id'], $site_url);
        $is_site_activated = (bool)$activation;
    }

    send_response(true, 'License is valid', [
        'status' => 'active',
        'tier' => $license['tier'],
        'tier_name' => $tier_info ? $tier_info['name'] : $license['tier'],
        'modules' => $tier_info ? $tier_info['modules'] : [],
        'max_sites' => $license['max_activations'],
        'active_sites' => (int)$license['active_sites'],
        'sites_remaining' => $license['max_activations'] - (int)$license['active_sites'],
        'is_site_activated' => $is_site_activated,
        'expires_at' => $license['expires_at'],
        'customer_email' => $license['customer_email']
    ]);
}

/**
 * ACTION: Activate License
 */
function action_activate() {
    $data = get_post_data();

    $license_key = isset($data['license_key']) ? trim($data['license_key']) : '';
    $site_url = isset($data['site_url']) ? trim($data['site_url']) : '';
    $site_name = isset($data['site_name']) ? trim($data['site_name']) : '';

    if (empty($license_key) || empty($site_url)) {
        send_response(false, 'License key and site URL are required');
    }

    if (!is_valid_key_format($license_key)) {
        send_response(false, 'Invalid license key format');
    }

    $license = get_license($license_key);

    if (!$license) {
        send_response(false, 'License key not found');
    }

    if ($license['status'] !== 'active') {
        send_response(false, 'License is not active');
    }

    if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
        send_response(false, 'License has expired');
    }

    // Check if already activated on this site
    $existing = get_activation($license['id'], $site_url);
    if ($existing) {
        send_response(true, 'Site is already activated', [
            'activated_at' => $existing['activated_at']
        ]);
    }

    // Check activation limit
    if ((int)$license['active_sites'] >= $license['max_activations']) {
        send_response(false, 'Maximum activation limit reached', [
            'max_activations' => $license['max_activations'],
            'active_sites' => (int)$license['active_sites']
        ]);
    }

    // Activate
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('
        INSERT INTO simplepco_activations (license_id, site_url, site_name, activated_at, last_check)
        VALUES (?, ?, ?, NOW(), NOW())
    ');
    $stmt->execute([$license['id'], normalize_url($site_url), $site_name]);

    // Get tier info
    $tiers = unserialize(LICENSE_TIERS);
    $tier_info = isset($tiers[$license['tier']]) ? $tiers[$license['tier']] : null;

    send_response(true, 'License activated successfully', [
        'tier' => $license['tier'],
        'tier_name' => $tier_info ? $tier_info['name'] : $license['tier'],
        'modules' => $tier_info ? $tier_info['modules'] : [],
        'sites_remaining' => $license['max_activations'] - (int)$license['active_sites'] - 1
    ]);
}

/**
 * ACTION: Deactivate License
 */
function action_deactivate() {
    $data = get_post_data();

    $license_key = isset($data['license_key']) ? trim($data['license_key']) : '';
    $site_url = isset($data['site_url']) ? trim($data['site_url']) : '';

    if (empty($license_key) || empty($site_url)) {
        send_response(false, 'License key and site URL are required');
    }

    $license = get_license($license_key);

    if (!$license) {
        send_response(false, 'License key not found');
    }

    $pdo = get_db_connection();
    $stmt = $pdo->prepare('
        DELETE FROM simplepco_activations
        WHERE license_id = ? AND site_url = ?
    ');
    $stmt->execute([$license['id'], normalize_url($site_url)]);

    if ($stmt->rowCount() > 0) {
        send_response(true, 'License deactivated from this site');
    } else {
        send_response(false, 'Site was not activated with this license');
    }
}

/**
 * ACTION: Check for Updates
 */
function action_check_update() {
    $data = get_post_data();

    $license_key = isset($data['license_key']) ? trim($data['license_key']) : '';
    $site_url = isset($data['site_url']) ? trim($data['site_url']) : '';
    $current_version = isset($data['current_version']) ? trim($data['current_version']) : '0.0.0';

    // Validate license for update access
    $has_valid_license = false;
    $download_url = null;

    if (!empty($license_key)) {
        $license = get_license($license_key);

        if ($license && $license['status'] === 'active') {
            // Check if not expired
            if (!$license['expires_at'] || strtotime($license['expires_at']) >= time()) {
                // Check if site is activated
                if (!empty($site_url)) {
                    $activation = get_activation($license['id'], $site_url);
                    $has_valid_license = (bool)$activation;

                    // Update last check time
                    if ($activation) {
                        $pdo = get_db_connection();
                        $stmt = $pdo->prepare('UPDATE simplepco_activations SET last_check = NOW() WHERE id = ?');
                        $stmt->execute([$activation['id']]);
                    }
                }
            }
        }
    }

    // Compare versions
    $update_available = version_compare(PLUGIN_CURRENT_VERSION, $current_version, '>');

    $response_data = [
        'update_available' => $update_available,
        'current_version' => $current_version,
        'latest_version' => PLUGIN_CURRENT_VERSION,
        'changelog_url' => PLUGIN_CHANGELOG_URL
    ];

    // Only provide download URL if license is valid
    if ($update_available && $has_valid_license) {
        $response_data['download_url'] = PLUGIN_DOWNLOAD_URL;
        $response_data['package'] = PLUGIN_DOWNLOAD_URL;
    } elseif ($update_available && !$has_valid_license) {
        $response_data['message'] = 'Valid license required for automatic updates';
    }

    send_response(true, $update_available ? 'Update available' : 'Plugin is up to date', $response_data);
}

/**
 * ACTION: Generate License (Admin only - protected by API secret)
 */
function action_generate() {
    $data = get_post_data();

    $api_secret = isset($data['api_secret']) ? $data['api_secret'] : '';

    if ($api_secret !== API_SECRET_KEY) {
        send_response(false, 'Unauthorized', null, 401);
    }

    $customer_email = isset($data['customer_email']) ? trim($data['customer_email']) : '';
    $customer_name = isset($data['customer_name']) ? trim($data['customer_name']) : '';
    $tier = isset($data['tier']) ? trim($data['tier']) : 'starter';
    $expires_days = isset($data['expires_days']) ? (int)$data['expires_days'] : 365;

    if (empty($customer_email)) {
        send_response(false, 'Customer email is required');
    }

    // Validate tier
    $tiers = unserialize(LICENSE_TIERS);
    if (!isset($tiers[$tier])) {
        send_response(false, 'Invalid tier: ' . $tier);
    }

    // Generate license key
    $license_key = 'SIMPLEPCO-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)) . '-'
                 . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)) . '-'
                 . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)) . '-'
                 . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));

    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_days} days"));
    $max_activations = $tiers[$tier]['max_sites'];

    $pdo = get_db_connection();
    $stmt = $pdo->prepare('
        INSERT INTO simplepco_licenses (license_key, customer_email, customer_name, tier, max_activations, status, expires_at, created_at)
        VALUES (?, ?, ?, ?, ?, "active", ?, NOW())
    ');
    $stmt->execute([$license_key, $customer_email, $customer_name, $tier, $max_activations, $expires_at]);

    send_response(true, 'License generated successfully', [
        'license_key' => $license_key,
        'tier' => $tier,
        'tier_name' => $tiers[$tier]['name'],
        'max_activations' => $max_activations,
        'expires_at' => $expires_at
    ]);
}

// Route the request
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'validate':
        action_validate();
        break;
    case 'activate':
        action_activate();
        break;
    case 'deactivate':
        action_deactivate();
        break;
    case 'check_update':
        action_check_update();
        break;
    case 'generate':
        action_generate();
        break;
    default:
        send_response(false, 'Invalid action. Available: validate, activate, deactivate, check_update', null, 400);
}
