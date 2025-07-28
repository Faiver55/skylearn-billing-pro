# Frequently Asked Questions (FAQ)

Common questions and answers about SkyLearn Billing Pro.

## General Questions

### What is SkyLearn Billing Pro?
SkyLearn Billing Pro is a professional WordPress plugin that integrates with LearnDash to provide comprehensive billing management, payment processing, and subscription management for online courses.

### What payment gateways are supported?
Currently supported gateways include:
- Lemon Squeezy (primary integration)
- PayPal (planned)
- Stripe (planned)
- Square (planned)

### Do I need LearnDash to use this plugin?
Yes, SkyLearn Billing Pro is specifically designed to work with LearnDash LMS and requires it to be installed and activated.

### What WordPress versions are supported?
- **Minimum**: WordPress 5.0
- **Recommended**: WordPress 6.0+
- **PHP Required**: 7.4+

## Installation & Setup

### How do I install the plugin?
1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the zip file and activate
4. Configure your settings at Settings → SkyLearn Billing Pro

### Why isn't the plugin showing up after installation?
Check these common issues:
- Ensure LearnDash is installed and activated first
- Verify you have administrator permissions
- Check if there are any PHP errors in your error log
- Make sure your WordPress and PHP versions meet requirements

### How do I get my Lemon Squeezy API credentials?
1. Log in to your Lemon Squeezy account
2. Go to Settings → API
3. Generate a new API key
4. Copy the key and paste it in the plugin settings

## Payment & Billing

### How quickly do students get course access after payment?
Access is typically granted immediately after successful payment processing. In rare cases, it may take 1-2 minutes for the enrollment to complete.

### What happens if a payment fails?
- Student receives an email notification
- Course access is not granted
- Payment can be retried automatically (for subscriptions)
- Manual retry options are available

### Can I offer refunds?
Yes, you can process refunds through:
- Your payment gateway dashboard
- The plugin's transaction management interface
- Manual refund processing

### How are taxes handled?
- Tax calculation depends on your payment gateway settings
- EU VAT and US sales tax can be configured
- Tax rates are applied automatically based on customer location
- Consult with a tax professional for specific requirements

## Subscriptions

### What subscription models are supported?
- Monthly subscriptions
- Yearly subscriptions
- Custom billing cycles
- Trial periods (free trials)
- Lifetime access options

### Can students pause their subscriptions?
Yes, if enabled in your settings, students can:
- Pause subscriptions temporarily
- Resume when ready
- Access content during pause period (configurable)

### What happens when a subscription expires?
- Course access is automatically revoked
- Student receives notification emails
- Grace period can be configured
- Re-enrollment options are provided

### How do I handle failed subscription payments?
The system automatically:
- Retries failed payments (configurable attempts)
- Sends dunning emails to customers
- Provides grace period before access removal
- Offers manual payment retry options

## Course Management

### Can I sell courses individually and as subscriptions?
Yes, you can offer the same course through multiple pricing models:
- One-time purchase for lifetime access
- Monthly subscription
- Yearly subscription with discount
- Trial + subscription combination

### How do I create course bundles?
1. Go to SkyLearn Billing Pro → Products
2. Create a new product
3. Select "Bundle" type
4. Choose multiple courses to include
5. Set bundle pricing and access rules

### Can I restrict course access by time?
Yes, you can set:
- Access duration (30 days, 1 year, lifetime, etc.)
- Start date for course availability
- End date for enrollment
- Drip content release schedules

## Technical Questions

### Is the plugin GDPR compliant?
Yes, the plugin includes:
- Data privacy controls
- Consent management
- Data export capabilities
- Right to deletion compliance
- Privacy policy templates

### How secure is payment processing?
- All payments are processed through PCI-compliant gateways
- No sensitive payment data is stored on your server
- SSL encryption for all transactions
- Fraud detection and prevention measures

### Can I customize the checkout experience?
Yes, you can:
- Customize checkout page styling
- Add custom fields to checkout forms
- Modify email templates
- Create custom thank-you pages
- Add terms and conditions

### Does it work with caching plugins?
Yes, but some configuration may be needed:
- Exclude checkout pages from caching
- Exclude user account pages from caching
- Test thoroughly with your specific caching setup

## Troubleshooting

### Students can't access courses after payment
Check these items:
1. Verify payment was successful in gateway dashboard
2. Check LearnDash user enrollment
3. Review plugin error logs
4. Ensure course is published and accessible
5. Verify user has correct permissions

### Webhook notifications not working
Common solutions:
1. Check webhook URL configuration in payment gateway
2. Verify SSL certificate is valid
3. Check server firewall settings
4. Review webhook logs for errors
5. Test webhook endpoint manually

### Plugin dashboard shows errors
Troubleshooting steps:
1. Check PHP error logs
2. Verify database permissions
3. Ensure plugin is up to date
4. Deactivate other plugins to test conflicts
5. Switch to default theme temporarily

## Integration Questions

### Does it work with membership plugins?
Limited compatibility with:
- MemberPress (basic integration)
- Restrict Content Pro (basic integration)
- WP Members (basic integration)

### Can I use it with WooCommerce?
SkyLearn Billing Pro is designed to replace WooCommerce for course sales, but they can coexist for selling other products.

### Does it support affiliate programs?
Yes, with compatible affiliate plugins:
- AffiliateWP
- Post Affiliate Pro
- ThirstyAffiliates

## Pricing & Licensing

### What does the license include?
- Plugin updates for 1 year
- Support access
- Documentation access
- Use on unlimited sites (per license terms)

### Can I use it on multiple sites?
License terms depend on your purchase:
- Single site license: 1 site
- Multi-site license: Up to 5 sites
- Developer license: Unlimited sites

### How do I renew my license?
1. Log in to your account
2. Go to license management
3. Click "Renew" for your license
4. Complete payment process

## Support

### How do I get support?
- **Documentation**: Check this FAQ and user manual first
- **Support Ticket**: Submit via plugin dashboard or website
- **Email**: contact@skyianllc.com
- **GitHub Issues**: For bug reports and feature requests

### What information should I include in support requests?
- WordPress version
- PHP version
- Plugin version
- LearnDash version
- Description of the issue
- Steps to reproduce
- Screenshots or error messages
- Any relevant error logs

### Response times for support
- **Email Support**: 24-48 hours
- **Support Tickets**: 12-24 hours
- **Critical Issues**: 4-8 hours
- **Emergency Support**: Available for enterprise customers

---

## Still Need Help?

If you can't find the answer to your question:

1. **Search the Documentation**: Use the search function in our docs
2. **Check the Troubleshooting Guide**: [View troubleshooting steps](./troubleshooting.md)
3. **Contact Support**: [Submit a support request](mailto:contact@skyianllc.com)
4. **Community Forum**: Join our user community (coming soon)

*Last Updated: 2024-07-28*