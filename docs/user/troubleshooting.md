# Troubleshooting Guide

Step-by-step solutions for common issues with SkyLearn Billing Pro.

## 🚨 Quick Diagnostics

Before diving into specific issues, run this quick checklist:

### System Requirements Check
- [ ] WordPress 5.0+ (check: WordPress Admin → Dashboard)
- [ ] PHP 7.4+ (check: WordPress Admin → Tools → Site Health)
- [ ] LearnDash installed and activated
- [ ] Plugin is up to date
- [ ] Valid SSL certificate

### Basic Functionality Test
- [ ] Plugin settings page loads without errors
- [ ] Payment gateway connection test passes
- [ ] LearnDash courses are visible in plugin
- [ ] No PHP errors in error log

## 🔧 Installation Issues

### Plugin Won't Activate

**Symptoms:**
- Error message during activation
- Plugin appears inactive after activation attempt
- White screen of death

**Solutions:**

1. **Check PHP Errors**
   ```
   Enable WordPress debugging:
   - Add to wp-config.php:
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
   - Check /wp-content/debug.log for errors
   ```

2. **Verify System Requirements**
   ```
   Check PHP version:
   - Go to WordPress Admin → Tools → Site Health
   - Review "Info" tab for PHP version
   - Ensure it's 7.4 or higher
   ```

3. **Memory Limit Issues**
   ```
   Increase PHP memory limit:
   - Add to wp-config.php:
     ini_set('memory_limit', '256M');
   - Or contact your host to increase limit
   ```

4. **Plugin Conflicts**
   ```
   Test for conflicts:
   1. Deactivate all other plugins
   2. Try activating SkyLearn Billing Pro
   3. If successful, reactivate plugins one by one
   4. Identify conflicting plugin
   ```

### Missing Dependencies

**Error:** "LearnDash is required"

**Solution:**
1. Install and activate LearnDash LMS
2. Ensure LearnDash is properly configured
3. Deactivate and reactivate SkyLearn Billing Pro

### Database Errors

**Symptoms:**
- "Database table creation failed"
- Missing plugin tables

**Solutions:**

1. **Check Database Permissions**
   ```
   Verify user permissions:
   - CREATE, ALTER, INSERT, UPDATE, DELETE
   - Contact hosting provider if unsure
   ```

2. **Manual Table Creation**
   ```sql
   -- Run in phpMyAdmin if needed
   CREATE TABLE IF NOT EXISTS wp_slbp_transactions (
     id int(11) NOT NULL AUTO_INCREMENT,
     user_id int(11) NOT NULL,
     course_id int(11) NOT NULL,
     transaction_id varchar(255) NOT NULL,
     amount decimal(10,2) NOT NULL,
     status varchar(50) NOT NULL,
     created_at datetime NOT NULL,
     PRIMARY KEY (id)
   );
   ```

3. **Reset Plugin Tables**
   ```
   Via plugin settings:
   1. Go to SkyLearn Billing Pro → Tools
   2. Click "Reset Database Tables"
   3. Confirm the action
   ```

## 💳 Payment Issues

### Payments Not Processing

**Symptoms:**
- Checkout button doesn't work
- Payment form won't submit
- "Payment failed" errors

**Diagnostics:**

1. **Check Browser Console**
   ```
   Open browser developer tools:
   - Press F12
   - Look for JavaScript errors in Console tab
   - Screenshot any errors for support
   ```

2. **Test Payment Gateway**
   ```
   In plugin settings:
   1. Go to Payment Gateways
   2. Click "Test Connection"
   3. Verify API credentials are correct
   ```

3. **Verify SSL Certificate**
   ```
   Check SSL status:
   - Visit your site's checkout page
   - Look for lock icon in browser
   - Test at ssllabs.com/ssltest
   ```

**Solutions:**

1. **API Credential Issues**
   ```
   Fix credentials:
   1. Log in to payment gateway account
   2. Regenerate API keys
   3. Update in plugin settings
   4. Test connection again
   ```

2. **Webhook Configuration**
   ```
   Set up webhooks:
   1. In payment gateway dashboard
   2. Add webhook URL: yoursite.com/wp-json/slbp/v1/webhook
   3. Enable relevant events
   4. Test webhook delivery
   ```

3. **Currency Mismatch**
   ```
   Check currency settings:
   - Plugin currency matches gateway currency
   - Course pricing uses correct currency
   - Gateway supports your currency
   ```

### Failed Transactions

**Symptoms:**
- Payment shows as failed
- Student charged but no course access
- Duplicate charges

**Solutions:**

1. **Check Transaction Status**
   ```
   In plugin dashboard:
   1. Go to Transactions
   2. Find the failed transaction
   3. Check error details
   4. Retry processing if possible
   ```

2. **Manual Enrollment**
   ```
   If payment succeeded but enrollment failed:
   1. Go to LearnDash → Users
   2. Edit the user
   3. Manually enroll in course
   4. Send confirmation email
   ```

3. **Refund Processing**
   ```
   For duplicate charges:
   1. Process refund via payment gateway
   2. Update transaction status in plugin
   3. Notify customer of resolution
   ```

## 🎓 Course Access Issues

### Students Can't Access Courses

**Symptoms:**
- Payment successful but no course access
- "Access denied" messages
- Courses not showing in student dashboard

**Diagnostics:**

1. **Check User Enrollment**
   ```
   In WordPress admin:
   1. Go to LearnDash → Users
   2. Edit the affected user
   3. Check "Enrolled Courses" section
   4. Verify course is listed
   ```

2. **Verify Course Settings**
   ```
   In LearnDash:
   1. Edit the course
   2. Check "Settings" tab
   3. Ensure "Open" or "Closed" access is correct
   4. Review prerequisites
   ```

**Solutions:**

1. **Manual Enrollment**
   ```
   Quick fix:
   1. LearnDash → Users → Edit User
   2. Add course to "Enrolled Courses"
   3. Save user profile
   4. Test course access
   ```

2. **Re-trigger Enrollment**
   ```
   Via plugin:
   1. SkyLearn Billing Pro → Transactions
   2. Find transaction
   3. Click "Re-process Enrollment"
   4. Verify success
   ```

3. **Check User Roles**
   ```
   Verify permissions:
   1. Users → All Users → Edit User
   2. Check "Role" is appropriate
   3. Ensure no capability conflicts
   ```

### Course Content Not Loading

**Symptoms:**
- Course accessible but content doesn't load
- Lesson pages show errors
- Progress not tracking

**Solutions:**

1. **Clear Caching**
   ```
   Clear all caches:
   - Plugin caches
   - WordPress caches
   - CDN caches
   - Browser cache
   ```

2. **Check LearnDash Settings**
   ```
   Verify configuration:
   1. LearnDash → Settings → General
   2. Check permalinks structure
   3. Review course progression settings
   ```

3. **Theme Compatibility**
   ```
   Test with default theme:
   1. Switch to Twenty Twenty-Four
   2. Test course access
   3. If fixed, contact theme developer
   ```

## 🔄 Subscription Issues

### Subscription Not Renewing

**Symptoms:**
- Subscription shows as active but access lost
- Payment method declined
- No renewal notification

**Solutions:**

1. **Check Payment Method**
   ```
   Update payment info:
   1. Student logs in to account
   2. Goes to Subscriptions
   3. Updates payment method
   4. Tests new method
   ```

2. **Review Subscription Status**
   ```
   In payment gateway:
   1. Check subscription status
   2. Review payment history
   3. Check for failed attempts
   4. Update billing retry settings
   ```

3. **Manual Renewal**
   ```
   Emergency renewal:
   1. Process manual payment
   2. Extend access in LearnDash
   3. Update subscription records
   4. Notify customer
   ```

### Cancellation Issues

**Symptoms:**
- Student can't cancel subscription
- Subscription cancelled but still billing
- Access not revoked after cancellation

**Solutions:**

1. **Process Cancellation**
   ```
   Manual cancellation:
   1. Cancel in payment gateway
   2. Update status in plugin
   3. Set access expiration date
   4. Send confirmation email
   ```

2. **Access Management**
   ```
   Configure cancellation behavior:
   1. Settings → Subscriptions
   2. Set "Access After Cancellation"
   3. Choose immediate or period-end
   4. Save settings
   ```

## 🔌 Integration Issues

### LearnDash Integration Problems

**Symptoms:**
- Courses not appearing in plugin
- Enrollment not working
- Progress not syncing

**Solutions:**

1. **Re-sync LearnDash Data**
   ```
   Force synchronization:
   1. SkyLearn Billing Pro → Tools
   2. Click "Sync LearnDash Data"
   3. Wait for completion
   4. Check course list
   ```

2. **Check LearnDash Version**
   ```
   Compatibility check:
   - Ensure LearnDash is updated
   - Review compatibility notes
   - Check for known issues
   ```

3. **Database Sync Issues**
   ```
   Manual database check:
   1. Check wp_learndash_user_activity
   2. Verify user course relationships
   3. Check for orphaned records
   ```

### Payment Gateway Integration

**Symptoms:**
- Gateway not responding
- Webhook failures
- Connection timeouts

**Solutions:**

1. **Test API Connectivity**
   ```
   Network diagnostics:
   1. Test API from server command line
   2. Check firewall rules
   3. Verify SSL/TLS versions
   4. Test with different gateway
   ```

2. **Webhook Troubleshooting**
   ```
   Debug webhooks:
   1. Check webhook logs in gateway
   2. Verify endpoint URL is correct
   3. Test webhook manually
   4. Check server response codes
   ```

## 📊 Performance Issues

### Slow Loading Times

**Symptoms:**
- Plugin pages load slowly
- Checkout process is sluggish
- Dashboard timeouts

**Solutions:**

1. **Enable Caching**
   ```
   Optimize performance:
   1. Enable object caching
   2. Use page caching (exclude checkout)
   3. Enable database query caching
   4. Optimize images and assets
   ```

2. **Database Optimization**
   ```
   Clean up database:
   1. Remove old transaction logs
   2. Optimize database tables
   3. Clean up orphaned data
   4. Update database indexes
   ```

3. **Server Resources**
   ```
   Check server specs:
   - Adequate PHP memory limit
   - Sufficient disk space
   - Good database performance
   - Consider upgrading hosting
   ```

## 🚨 Error Messages

### Common Error Messages and Solutions

#### "Invalid API Key"
```
Solution:
1. Check API key format
2. Regenerate key in gateway dashboard
3. Copy/paste carefully (no extra spaces)
4. Test connection again
```

#### "Course Not Found"
```
Solution:
1. Verify course exists in LearnDash
2. Check course is published
3. Re-sync course data
4. Check course ID mapping
```

#### "User Already Enrolled"
```
Solution:
1. Check if user has existing enrollment
2. If duplicate, remove old enrollment
3. Process new enrollment
4. Update access dates
```

#### "Payment Gateway Timeout"
```
Solution:
1. Check gateway status page
2. Verify network connectivity
3. Increase timeout settings
4. Try again later
```

## 🆘 Emergency Procedures

### Site Down After Plugin Activation

**Immediate Actions:**
1. Access site via FTP/cPanel
2. Rename plugin folder to deactivate
3. Check error logs for cause
4. Fix issue before reactivating

### Payment Processing Stopped

**Emergency Steps:**
1. Switch to backup payment method
2. Notify customers of temporary issues
3. Process payments manually if needed
4. Investigate and fix root cause

### Data Loss Prevention

**Backup Strategy:**
1. Regular database backups
2. Plugin file backups
3. Configuration export
4. Transaction log archives

## 📞 Getting Help

### Before Contacting Support

1. **Gather Information:**
   - WordPress version
   - Plugin version
   - PHP version
   - Error messages
   - Steps to reproduce issue

2. **Test Environment:**
   - Try on staging site
   - Test with default theme
   - Deactivate other plugins
   - Clear all caches

3. **Document the Issue:**
   - Screenshots of errors
   - Error log entries
   - Network tab from browser dev tools
   - Transaction IDs if relevant

### Support Channels

- **Email**: contact@skyianllc.com
- **Documentation**: Check this guide first
- **GitHub Issues**: For bugs and feature requests
- **Priority Support**: Available for premium customers

### Information to Include

```
When contacting support, include:
- WordPress version: X.X.X
- Plugin version: X.X.X
- PHP version: X.X.X
- Payment gateway: [Gateway name]
- Error message: [Exact error text]
- Steps to reproduce: [Detailed steps]
- Browser: [Chrome/Firefox/Safari version]
- Device: [Desktop/Mobile/Tablet]
```

---

**Need immediate help?** Contact our support team at contact@skyianllc.com with "URGENT" in the subject line for critical issues affecting payment processing.