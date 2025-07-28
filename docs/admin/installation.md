# Installation Guide

Complete installation instructions for SkyLearn Billing Pro WordPress plugin.

## Prerequisites

Before installing SkyLearn Billing Pro, ensure your system meets the following requirements:

### System Requirements
- **WordPress**: 5.0 or higher (6.0+ recommended)
- **PHP**: 7.4 or higher (8.0+ recommended)  
- **MySQL**: 5.6 or higher (8.0+ recommended)
- **Memory**: 128MB minimum (256MB+ recommended)
- **SSL Certificate**: Required for payment processing

### Required Plugins
- **LearnDash LMS**: 4.0 or higher (must be installed first)

### Recommended Environment
- **WordPress**: Latest stable version
- **PHP**: 8.1 with OPcache enabled
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Database**: MySQL 8.0+ or MariaDB 10.5+

## Installation Methods

### Method 1: WordPress Admin Upload (Recommended)

1. **Download Plugin**
   - Download the latest version from your account
   - Save the `skylearn-billing-pro.zip` file

2. **Access WordPress Admin**
   - Log in to your WordPress admin dashboard
   - Navigate to **Plugins → Add New**

3. **Upload Plugin**
   - Click **Upload Plugin** button
   - Choose `skylearn-billing-pro.zip` file
   - Click **Install Now**

4. **Activate Plugin**
   - Click **Activate Plugin** after installation
   - Verify activation in **Plugins → Installed Plugins**

### Method 2: FTP/SFTP Upload

1. **Extract Plugin Files**
   ```bash
   unzip skylearn-billing-pro.zip
   ```

2. **Upload via FTP**
   ```bash
   # Using FTP client or command line
   ftp your-site.com
   cd /public_html/wp-content/plugins/
   put -r skylearn-billing-pro/
   ```

3. **Set Permissions**
   ```bash
   chmod -R 755 skylearn-billing-pro/
   chown -R www-data:www-data skylearn-billing-pro/
   ```

4. **Activate in WordPress**
   - Go to **Plugins → Installed Plugins**
   - Find "SkyLearn Billing Pro"
   - Click **Activate**

### Method 3: WP-CLI (Advanced Users)

```bash
# Install plugin
wp plugin install skylearn-billing-pro.zip

# Activate plugin
wp plugin activate skylearn-billing-pro

# Verify installation
wp plugin list | grep skylearn-billing-pro
```

## Post-Installation Setup

### 1. License Activation

1. **Access Plugin Settings**
   - Go to **Settings → SkyLearn Billing Pro**
   - Navigate to **License** tab

2. **Enter License Key**
   - Paste your license key
   - Click **Activate License**
   - Verify green "Active" status

### 2. Basic Configuration

1. **General Settings**
   ```
   Currency: USD (or your preferred currency)
   Tax Calculation: Enabled (if applicable)
   Email Notifications: Enabled
   ```

2. **Payment Gateway Setup**
   - Go to **Payment Gateways** tab
   - Configure Lemon Squeezy credentials
   - Test connection

3. **LearnDash Integration**
   - Navigate to **LearnDash** tab
   - Enable automatic enrollment
   - Configure access duration settings

### 3. Email Configuration

1. **SMTP Settings** (Recommended)
   ```
   SMTP Host: your-smtp-server.com
   SMTP Port: 587 (or 465 for SSL)
   SMTP Security: TLS
   Username: your-email@domain.com
   Password: your-app-password
   ```

2. **Email Templates**
   - Customize purchase confirmation emails
   - Set up subscription notification emails
   - Configure refund notification emails

## Database Setup

### Automatic Database Creation

The plugin automatically creates required database tables during activation:

```sql
-- Transaction records
wp_slbp_transactions

-- Subscription management  
wp_slbp_subscriptions

-- License management
wp_slbp_licenses

-- Plugin settings
wp_slbp_settings
```

### Manual Database Setup (If Needed)

If automatic setup fails, run these SQL commands:

```sql
-- Create transactions table
CREATE TABLE IF NOT EXISTS `wp_slbp_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` varchar(50) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `gateway_transaction_id` varchar(255) DEFAULT NULL,
  `metadata` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create subscriptions table
CREATE TABLE IF NOT EXISTS `wp_slbp_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `subscription_id` varchar(255) NOT NULL,
  `plan_id` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `billing_cycle` varchar(20) NOT NULL,
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `trial_start` datetime DEFAULT NULL,
  `trial_end` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `metadata` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create licenses table
CREATE TABLE IF NOT EXISTS `wp_slbp_licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `license_key` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `activations` int(11) NOT NULL DEFAULT 0,
  `activation_limit` int(11) NOT NULL DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_key` (`license_key`),
  KEY `email` (`email`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Verification Steps

### 1. Plugin Activation Check

```bash
# WordPress CLI check
wp plugin status skylearn-billing-pro

# Expected output: Status: Active
```

### 2. Database Verification

```sql
-- Check if tables exist
SHOW TABLES LIKE 'wp_slbp_%';

-- Verify table structure
DESCRIBE wp_slbp_transactions;
DESCRIBE wp_slbp_subscriptions;
DESCRIBE wp_slbp_licenses;
```

### 3. Functionality Tests

1. **Admin Access**
   - Navigate to **Settings → SkyLearn Billing Pro**
   - Verify all tabs load without errors

2. **Payment Gateway Test**
   - Configure test API credentials
   - Click "Test Connection"
   - Verify successful connection

3. **LearnDash Integration**
   - Check course list synchronization
   - Verify enrollment settings

## Troubleshooting Installation Issues

### Common Installation Problems

#### Plugin Won't Activate

**Symptoms:**
- Error message during activation
- Plugin appears inactive after activation

**Solutions:**
1. **Check PHP Version**
   ```bash
   php -v  # Must be 7.4+
   ```

2. **Increase Memory Limit**
   ```php
   // Add to wp-config.php
   ini_set('memory_limit', '256M');
   ```

3. **Check Error Logs**
   ```bash
   tail -f /var/log/apache2/error.log
   # or
   tail -f /path/to/php/error.log
   ```

#### Database Tables Not Created

**Symptoms:**
- Plugin activates but settings don't save
- Database errors in logs

**Solutions:**
1. **Check Database Permissions**
   ```sql
   SHOW GRANTS FOR 'wp_user'@'localhost';
   ```

2. **Manual Table Creation**
   - Run SQL commands from "Manual Database Setup" section

3. **WordPress Database Repair**
   ```php
   // Add to wp-config.php temporarily
   define('WP_ALLOW_REPAIR', true);
   // Visit: yoursite.com/wp-admin/maint/repair.php
   ```

#### License Activation Fails

**Symptoms:**
- "Invalid license key" error
- Network connection errors

**Solutions:**
1. **Check License Key Format**
   - Ensure no extra spaces or characters
   - Verify key from purchase email

2. **Network Connectivity**
   ```bash
   curl -I https://license-server.skyianllc.com
   ```

3. **Firewall/SSL Issues**
   - Check server firewall rules
   - Verify SSL certificate validity

### Permission Issues

#### File Permission Problems

```bash
# Set correct permissions
find /wp-content/plugins/skylearn-billing-pro/ -type d -exec chmod 755 {} \;
find /wp-content/plugins/skylearn-billing-pro/ -type f -exec chmod 644 {} \;

# Set ownership (adjust user:group as needed)
chown -R www-data:www-data /wp-content/plugins/skylearn-billing-pro/
```

#### Directory Writable Issues

```bash
# Make uploads directory writable
chmod 755 /wp-content/uploads/
chown www-data:www-data /wp-content/uploads/

# Check if directories are writable
ls -la /wp-content/plugins/skylearn-billing-pro/
```

## Security Considerations

### SSL Certificate Setup

**Why SSL is Required:**
- PCI compliance for payment processing
- Secure API communications
- Customer data protection

**SSL Verification:**
```bash
# Check SSL certificate
openssl s_client -connect yoursite.com:443 -servername yoursite.com

# Verify certificate validity
curl -I https://yoursite.com
```

### File Security

**Secure File Permissions:**
```bash
# Plugin directory
chmod 755 skylearn-billing-pro/
chmod 644 skylearn-billing-pro/*.php

# Configuration files (if any)
chmod 600 skylearn-billing-pro/config/*.conf
```

**Security Headers:**
```php
// Add to .htaccess or server config
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

## Performance Optimization

### Caching Configuration

**Object Caching:**
```php
// wp-config.php
define('WP_CACHE', true);
define('ENABLE_CACHE', true);
```

**Database Optimization:**
```sql
-- Optimize plugin tables
OPTIMIZE TABLE wp_slbp_transactions;
OPTIMIZE TABLE wp_slbp_subscriptions;
OPTIMIZE TABLE wp_slbp_licenses;
```

### CDN Setup

**Static Asset Delivery:**
- Configure CDN for plugin assets
- Exclude dynamic pages from caching
- Cache static CSS/JS files

## Multi-Site Installation

### WordPress Multisite Setup

1. **Network Activation**
   ```bash
   wp plugin activate skylearn-billing-pro --network
   ```

2. **Per-Site Configuration**
   - Each site needs individual license
   - Separate payment gateway configuration
   - Independent course management

3. **Shared Resources**
   - Common database tables (if configured)
   - Shared license management
   - Centralized reporting

## Maintenance and Updates

### Update Procedure

1. **Backup Before Update**
   ```bash
   # Database backup
   wp db export backup_$(date +%Y%m%d).sql
   
   # File backup
   tar -czf plugin_backup_$(date +%Y%m%d).tar.gz skylearn-billing-pro/
   ```

2. **Update Plugin**
   - Download new version
   - Deactivate current version
   - Replace plugin files
   - Reactivate plugin

3. **Post-Update Verification**
   - Test critical functionality
   - Verify payment processing
   - Check database integrity

### Scheduled Maintenance

**Daily Tasks:**
- Monitor error logs
- Check payment processing
- Verify backup completion

**Weekly Tasks:**
- Review performance metrics
- Update documentation
- Test disaster recovery

**Monthly Tasks:**
- Security audit
- Performance optimization
- License compliance review

## Getting Help

### Support Resources

- **Documentation**: [Complete Docs](../README.md)
- **FAQ**: [Frequently Asked Questions](../user/faq.md)
- **Troubleshooting**: [Problem Resolution](../user/troubleshooting.md)

### Contact Support

- **Email**: contact@skyianllc.com
- **Priority Support**: Available for paid licenses
- **Emergency Support**: Critical payment issues

### Useful Information to Provide

When contacting support, include:
- WordPress version
- Plugin version
- PHP version
- Error messages
- Steps to reproduce issue

---

*Installation guide last updated: July 28, 2024*