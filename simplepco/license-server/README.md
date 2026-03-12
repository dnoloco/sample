# SimplePCO License Server

A simple PHP-based license server for the SimplePCO Online WordPress plugin.

## Installation on Hostinger

### 1. Upload Files

Upload this entire `license-server` folder to your Hostinger site:
```
your-site.com/simplepco-license/
├── api.php
├── config.php
├── install.php
└── README.md
```

### 2. Create Database

In your Hostinger control panel:
1. Go to **Databases** > **MySQL Databases**
2. Create a new database (e.g., `simplepco_licenses`)
3. Note the database name, username, and password

### 3. Configure

Edit `config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// IMPORTANT: Change this to a random 32+ character string
define('API_SECRET_KEY', 'your-super-secret-key-here');
```

### 4. Run Installation

Visit in your browser:
```
https://your-site.com/simplepco-license/install.php?secret=YOUR_API_SECRET_KEY
```

This creates the necessary database tables.

### 5. Security

**IMPORTANT:** Delete `install.php` after installation!

```bash
rm install.php
```

## API Endpoints

### Validate License
```bash
POST /api.php?action=validate
{
  "license_key": "SIMPLEPCO-XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://customer-site.com"
}
```

### Activate License
```bash
POST /api.php?action=activate
{
  "license_key": "SIMPLEPCO-XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://customer-site.com",
  "site_name": "Customer Site Name"
}
```

### Deactivate License
```bash
POST /api.php?action=deactivate
{
  "license_key": "SIMPLEPCO-XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://customer-site.com"
}
```

### Check for Updates
```bash
POST /api.php?action=check_update
{
  "license_key": "SIMPLEPCO-XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://customer-site.com",
  "current_version": "2.0.0"
}
```

### Generate License (Admin Only)
```bash
POST /api.php?action=generate
{
  "api_secret": "YOUR_API_SECRET_KEY",
  "customer_email": "customer@example.com",
  "customer_name": "John Doe",
  "tier": "professional",
  "expires_days": 365
}
```

## License Tiers

| Tier | Modules | Max Sites | Price |
|------|---------|-----------|-------|
| Starter | Services*, Calendar* | 1 | $49 |
| Professional | All modules | 3 | $99 |
| Agency | All modules | 25 | $249 |

*Premium features only

## Response Format

All responses are JSON:
```json
{
  "success": true,
  "message": "License is valid",
  "data": {
    "status": "active",
    "tier": "professional",
    "modules": ["services", "calendar", "groups", "signups", "messages"],
    "max_sites": 3,
    "active_sites": 1,
    "sites_remaining": 2,
    "expires_at": "2027-01-28 00:00:00"
  },
  "timestamp": 1738099200
}
```

## Future Payment Integration

To integrate with payment systems (Stripe, PayPal, etc.), you can:

1. Create a webhook endpoint that receives payment notifications
2. Call the `generate` action with the `api_secret`
3. Email the license key to the customer

Example webhook flow:
```
Payment Received → Your Webhook → Generate License → Email Customer
```

## Troubleshooting

### "Database connection failed"
- Check your database credentials in `config.php`
- Ensure the database exists and user has permissions

### "License key not found"
- Verify the license key format: `SIMPLEPCO-XXXX-XXXX-XXXX-XXXX`
- Check if the license exists in the database

### CORS Issues
- Update `ALLOWED_ORIGINS` in `config.php` if needed
