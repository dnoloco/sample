<?php
/**
 * SimplePCO License Server Configuration
 *
 * IMPORTANT: After uploading to your Hostinger site:
 * 1. Update the database credentials below
 * 2. Keep this file secure - do not expose publicly
 * 3. Generate a new API secret key
 *
 * Upload this folder to: your-site.com/simplepco-license/
 */

// Prevent direct access
if (!defined('SIMPLEPCO_LICENSE_SERVER')) {
    die('Direct access not permitted');
}

// Database Configuration (Update these with your Hostinger database credentials)
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// API Configuration
define('API_SECRET_KEY', 'change-this-to-a-random-string-at-least-32-characters');

// License Tiers and their allowed modules
define('LICENSE_TIERS', serialize([
    'starter' => [
        'name' => 'Starter',
        'modules' => ['services', 'calendar'], // Only premium features of these
        'max_sites' => 1,
        'price' => 49
    ],
    'professional' => [
        'name' => 'Professional',
        'modules' => ['services', 'calendar', 'groups', 'signups', 'messages'],
        'max_sites' => 3,
        'price' => 99
    ],
    'agency' => [
        'name' => 'Agency',
        'modules' => ['services', 'calendar', 'groups', 'signups', 'messages'],
        'max_sites' => 25,
        'price' => 249
    ]
]));

// Plugin update information
define('PLUGIN_CURRENT_VERSION', '2.0.0');
define('PLUGIN_DOWNLOAD_URL', 'https://your-site.com/downloads/simplepco-online.zip');
define('PLUGIN_CHANGELOG_URL', 'https://your-site.com/changelog/');

// CORS - Add your customer domains or use '*' for development
define('ALLOWED_ORIGINS', '*');
