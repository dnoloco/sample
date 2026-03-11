<?php
/**
 * MyPCO License Server - Database Installation
 *
 * Run this once to set up the database tables.
 * Access via: your-site.com/mypco-license/install.php?secret=YOUR_API_SECRET_KEY
 *
 * DELETE THIS FILE AFTER INSTALLATION FOR SECURITY!
 */

define('MYPCO_LICENSE_SERVER', true);
require_once __DIR__ . '/config.php';

// Security check
if (!isset($_GET['secret']) || $_GET['secret'] !== API_SECRET_KEY) {
    die('Unauthorized. Provide ?secret=YOUR_API_SECRET_KEY');
}

echo "<pre>\n";
echo "MyPCO License Server - Database Setup\n";
echo "======================================\n\n";

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Connected to database successfully.\n\n";

    // Create licenses table
    echo "Creating mypco_licenses table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mypco_licenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            license_key VARCHAR(64) NOT NULL UNIQUE,
            customer_email VARCHAR(255) NOT NULL,
            customer_name VARCHAR(255) DEFAULT '',
            tier ENUM('starter', 'professional', 'agency') NOT NULL DEFAULT 'starter',
            max_activations INT NOT NULL DEFAULT 1,
            status ENUM('active', 'suspended', 'expired', 'revoked') NOT NULL DEFAULT 'active',
            expires_at DATETIME NULL,
            order_id VARCHAR(100) DEFAULT NULL COMMENT 'For payment integration',
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_license_key (license_key),
            INDEX idx_customer_email (customer_email),
            INDEX idx_status (status),
            INDEX idx_tier (tier)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - mypco_licenses table created.\n";

    // Create activations table
    echo "Creating mypco_activations table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mypco_activations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            license_id INT NOT NULL,
            site_url VARCHAR(255) NOT NULL,
            site_name VARCHAR(255) DEFAULT '',
            activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_check TIMESTAMP NULL,
            FOREIGN KEY (license_id) REFERENCES mypco_licenses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_license_site (license_id, site_url),
            INDEX idx_site_url (site_url)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - mypco_activations table created.\n";

    // Create logs table for tracking
    echo "Creating mypco_license_logs table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mypco_license_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            license_id INT NULL,
            action VARCHAR(50) NOT NULL,
            site_url VARCHAR(255) DEFAULT '',
            ip_address VARCHAR(45) DEFAULT '',
            user_agent TEXT DEFAULT NULL,
            request_data TEXT DEFAULT NULL,
            response_data TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (license_id) REFERENCES mypco_licenses(id) ON DELETE SET NULL,
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  - mypco_license_logs table created.\n";

    echo "\n======================================\n";
    echo "Database setup completed successfully!\n";
    echo "======================================\n\n";

    echo "Next steps:\n";
    echo "1. DELETE this install.php file for security\n";
    echo "2. Update config.php with a strong API_SECRET_KEY\n";
    echo "3. Generate your first license using:\n\n";

    $example_curl = 'curl -X POST "https://your-site.com/mypco-license/api.php?action=generate" \\
     -H "Content-Type: application/json" \\
     -d \'{
       "api_secret": "' . API_SECRET_KEY . '",
       "customer_email": "customer@example.com",
       "customer_name": "John Doe",
       "tier": "professional",
       "expires_days": 365
     }\'';

    echo $example_curl . "\n\n";

    echo "4. Test license validation:\n\n";

    $test_curl = 'curl -X POST "https://your-site.com/mypco-license/api.php?action=validate" \\
     -H "Content-Type: application/json" \\
     -d \'{
       "license_key": "MYPCO-XXXX-XXXX-XXXX-XXXX",
       "site_url": "https://customer-site.com"
     }\'';

    echo $test_curl . "\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
