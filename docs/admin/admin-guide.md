# Administrator Guide

Complete guide for administrators managing SkyLearn Billing Pro installations and configurations.

## Table of Contents

1. [Overview](#overview)
2. [System Requirements](#system-requirements)
3. [Installation & Setup](#installation--setup)
4. [Configuration Management](#configuration-management)
5. [Payment Gateway Administration](#payment-gateway-administration)
6. [User & Access Management](#user--access-management)
7. [Product & Course Management](#product--course-management)
8. [Financial Management](#financial-management)
9. [Security & Compliance](#security--compliance)
10. [Monitoring & Maintenance](#monitoring--maintenance)
11. [Backup & Recovery](#backup--recovery)
12. [Troubleshooting for Admins](#troubleshooting-for-admins)

## Overview

As a SkyLearn Billing Pro administrator, you're responsible for managing the entire billing infrastructure for your LearnDash-powered learning platform. This guide covers everything from initial setup to advanced configuration and ongoing maintenance.

### Administrator Responsibilities
- **System Configuration**: Set up and configure all plugin components
- **Payment Gateway Management**: Configure and maintain payment processing
- **User Management**: Handle user roles, permissions, and access
- **Financial Oversight**: Monitor transactions, refunds, and revenue
- **Security Management**: Ensure compliance and data protection
- **System Maintenance**: Keep the system updated and optimized

### Key Administration Areas
- Plugin settings and configuration
- Payment gateway integration
- LearnDash course integration
- User enrollment management
- Transaction monitoring
- Subscription administration
- Reporting and analytics
- Security and compliance

## System Requirements

### Minimum Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory**: 128MB minimum (256MB recommended)
- **Storage**: 10MB for plugin files

### Recommended Environment
- **WordPress**: 6.2+
- **PHP**: 8.1+
- **MySQL**: 8.0+
- **Memory**: 512MB+
- **SSL Certificate**: Required for payment processing
- **CDN**: Recommended for performance

### Required Plugins
- **LearnDash LMS**: 4.0+ (required)
- **Compatible Cache Plugin**: Optional but recommended

### Server Requirements
- **HTTPS**: Required for PCI compliance
- **cURL**: For API communications
- **OpenSSL**: For secure communications
- **mod_rewrite**: For clean URLs

## Installation & Setup

### Pre-Installation Checklist

1. **Environment Preparation**
   ```
   ✓ WordPress installation ready
   ✓ LearnDash installed and configured
   ✓ SSL certificate installed
   ✓ PHP version compatible
   ✓ Database backup completed
   ✓ Admin access confirmed
   ```

2. **Gateway Account Setup**
   ```
   ✓ Payment gateway account created
   ✓ API credentials obtained
   ✓ Webhook endpoints prepared
   ✓ Business verification completed
   ✓ Test mode configured
   ```

### Installation Process

#### Step 1: Plugin Installation
1. **Download & Upload**
   ```
   - Download plugin from licensed source
   - Upload via WordPress Admin → Plugins → Add New
   - Or upload via FTP to /wp-content/plugins/
   ```

2. **Activation**
   ```
   - Navigate to Plugins → Installed Plugins
   - Find "SkyLearn Billing Pro"
   - Click "Activate"
   - Verify no activation errors
   ```

#### Step 2: Initial Configuration
1. **Access Settings**
   ```
   Navigate to: WordPress Admin → Settings → SkyLearn Billing Pro
   ```

2. **License Activation**
   ```
   - Enter your license key
   - Click "Activate License"
   - Verify activation status
   ```

3. **Basic Settings**
   ```
   Configure:
   - Currency settings
   - Tax calculation
   - Email notifications
   - Default access duration
   ```

#### Step 3: Payment Gateway Setup
1. **Gateway Selection**
   ```
   - Go to "Payment Gateways" tab
   - Choose primary gateway
   - Configure API credentials
   - Test connection
   ```

2. **Webhook Configuration**
   ```
   - Set webhook URL in gateway dashboard
   - Configure webhook events
   - Test webhook delivery
   - Enable live processing
   ```

#### Step 4: LearnDash Integration
1. **Course Synchronization**
   ```
   - Go to "LearnDash Integration" tab
   - Click "Sync Courses"
   - Verify course list
   - Configure enrollment settings
   ```

2. **Access Control**
   ```
   - Set default course access rules
   - Configure drip content settings
   - Set up progress tracking
   ```

### Post-Installation Verification

#### Functionality Tests
1. **Test Purchase Flow**
   ```
   - Create test user account
   - Attempt course purchase
   - Verify payment processing
   - Confirm course enrollment
   - Check email notifications
   ```

2. **Subscription Testing**
   ```
   - Test subscription signup
   - Verify recurring billing
   - Test subscription management
   - Check cancellation flow
   ```

3. **Administrative Functions**
   ```
   - Verify admin dashboard access
   - Test transaction management
   - Check reporting functions
   - Validate user management
   ```

## Configuration Management

### Core Settings

#### General Configuration
```
Settings → SkyLearn Billing Pro → General

Currency Settings:
- Default Currency: USD
- Currency Position: Before ($100)
- Decimal Places: 2
- Thousands Separator: ,

Regional Settings:
- Timezone: Site timezone
- Date Format: Site format
- Number Format: Regional standard
```

#### Email Configuration
```
Settings → SkyLearn Billing Pro → Emails

Email Templates:
- Purchase confirmation
- Subscription notifications
- Payment failed alerts
- Cancellation confirmations

SMTP Settings:
- Email provider configuration
- Authentication settings
- Delivery optimization
```

#### Tax Configuration
```
Settings → SkyLearn Billing Pro → Tax

Tax Calculation:
- Enable tax calculation: Yes/No
- Tax inclusive pricing: Yes/No
- Default tax rate: X%
- Tax exemption rules

Location-Based Tax:
- Enable geo-location: Yes/No
- Tax rules by country/state
- VAT/GST configuration
```

### Advanced Configuration

#### Performance Settings
```
Settings → SkyLearn Billing Pro → Performance

Caching:
- Enable object caching: Yes
- Cache duration: 1 hour
- Cache excluded pages: Checkout, Account

Database Optimization:
- Enable query caching: Yes
- Cleanup old logs: 90 days
- Optimize tables: Weekly
```

#### Security Settings
```
Settings → SkyLearn Billing Pro → Security

Access Control:
- Enable rate limiting: Yes
- Login attempt limits: 5/hour
- IP blocking: Enabled
- Session timeout: 24 hours

Data Protection:
- PII encryption: Enabled
- Log retention: 6 months
- Anonymize old data: Yes
```

#### Integration Settings
```
Settings → SkyLearn Billing Pro → Integrations

LearnDash:
- Auto-enrollment: Enabled
- Progress tracking: Enabled
- Certificate generation: Yes
- Group management: Enabled

Third-Party:
- Analytics tracking: Google Analytics
- Marketing integration: Mailchimp
- CRM connection: Contact form
```

## Payment Gateway Administration

### Gateway Configuration

#### Primary Gateway Setup
1. **Lemon Squeezy Configuration**
   ```
   API Configuration:
   - API Key: [Your API Key]
   - Store ID: [Your Store ID]
   - Webhook Secret: [Generated Secret]
   - Environment: Live/Test
   
   Settings:
   - Default product type: Digital
   - Tax handling: Automatic
   - Refund policy: Configurable
   ```

2. **Webhook Management**
   ```
   Webhook Events:
   - order_created
   - subscription_created
   - subscription_updated
   - subscription_cancelled
   - payment_success
   - payment_failed
   - refund_created
   
   Webhook URL: yoursite.com/wp-json/slbp/v1/webhook
   ```

#### Multi-Gateway Setup
```
Configuration for multiple gateways:

Primary Gateway: Lemon Squeezy
- Handle: Credit cards, digital wallets
- Region: Global
- Priority: 1

Secondary Gateway: PayPal (if configured)
- Handle: PayPal payments
- Region: Global
- Priority: 2

Fallback: Manual payments
- Handle: Bank transfers
- Region: Specific countries
- Priority: 3
```

### Gateway Management

#### Transaction Monitoring
```
SkyLearn Billing Pro → Transactions

Monitor:
- Real-time transaction status
- Payment success/failure rates
- Gateway response times
- Error frequency and types

Actions:
- View transaction details
- Process refunds
- Retry failed payments
- Update transaction status
```

#### Gateway Health Checks
```
Regular monitoring tasks:
- Test gateway connectivity daily
- Verify webhook delivery
- Check API rate limits
- Monitor gateway status pages
- Review error logs weekly
```

## User & Access Management

### User Role Configuration

#### Standard Roles
```
Administrator:
- Full plugin access
- All configuration rights
- Financial data access
- User management

Instructor:
- Course-specific access
- Student enrollment viewing
- Limited reporting access

Student:
- Course purchase capability
- Subscription management
- Payment method updates
```

#### Custom Role Creation
```
Create custom roles for:
- Financial managers
- Customer service
- Content managers
- Sales representatives

Configure capabilities:
- View transactions: Yes/No
- Process refunds: Yes/No
- Manage users: Yes/No
- Access reports: Yes/No
```

### Access Control Management

#### Course Access Rules
```
Configure access based on:
- Payment status
- Subscription status
- Geographic location
- User role
- Custom criteria

Access Duration:
- Lifetime access
- Time-limited (30 days, 1 year)
- Subscription-based
- Custom durations
```

#### Enrollment Management
```
Automatic Enrollment:
- Triggered by successful payment
- Immediate course access
- Email confirmation sent
- Progress tracking enabled

Manual Enrollment:
- Admin-initiated enrollment
- Bulk enrollment tools
- CSV import capability
- Custom enrollment rules
```

## Product & Course Management

### Product Configuration

#### Product Types
```
Individual Courses:
- Single course access
- One-time payment
- Lifetime or limited access
- Direct LearnDash integration

Course Bundles:
- Multiple course packages
- Discounted pricing
- Sequential or immediate access
- Bundle-specific settings

Subscriptions:
- Recurring access
- Multiple billing cycles
- Trial periods
- Cancellation policies
```

#### Pricing Management
```
Pricing Strategies:
- Fixed pricing
- Tiered pricing (regional)
- Dynamic pricing
- Promotional pricing

Discount Management:
- Coupon codes
- Percentage discounts
- Fixed amount discounts
- Time-limited offers
- User-specific discounts
```

### Course Integration

#### LearnDash Synchronization
```
Sync Management:
- Automatic sync: Daily
- Manual sync: On-demand
- Selective sync: Course-specific
- Bulk operations: Available

Sync Data:
- Course metadata
- Pricing information
- Access rules
- Prerequisites
```

#### Content Management
```
Course Settings:
- Access duration
- Drip content schedule
- Prerequisites
- Completion requirements

Content Protection:
- Login required
- Payment verification
- IP restrictions
- Download limitations
```

## Financial Management

### Revenue Tracking

#### Financial Dashboard
```
Key Metrics:
- Daily/Monthly/Yearly revenue
- Transaction count and value
- Subscription metrics (MRR, ARR)
- Refund rates
- Churn analysis

Revenue Streams:
- One-time purchases
- Subscription revenue
- Upsells and cross-sells
- Renewal revenue
```

#### Reporting System
```
Standard Reports:
- Sales by period
- Product performance
- Customer analytics
- Financial summaries

Custom Reports:
- Date range selection
- Filter by product/user
- Export capabilities (CSV, PDF)
- Scheduled reports
```

### Transaction Management

#### Transaction Processing
```
Process Management:
- Real-time processing
- Failed payment handling
- Retry mechanisms
- Manual intervention tools

Status Tracking:
- Pending payments
- Completed transactions
- Failed attempts
- Refunded orders
```

#### Refund Management
```
Refund Process:
- Partial and full refunds
- Reason tracking
- Automated course access removal
- Customer notification

Refund Policies:
- Time-based policies (30 days)
- Product-specific policies
- Subscription refund rules
- Administrative overrides
```

### Subscription Administration

#### Subscription Management
```
Subscription Lifecycle:
- Creation and activation
- Billing cycle management
- Payment method updates
- Cancellation processing

Customer Self-Service:
- Subscription dashboard
- Payment method updates
- Plan changes
- Cancellation requests
```

#### Billing Management
```
Billing Operations:
- Automated billing cycles
- Proration calculations
- Failed payment handling
- Dunning management

Billing Analytics:
- Monthly recurring revenue (MRR)
- Annual recurring revenue (ARR)
- Churn rate analysis
- Customer lifetime value
```

## Security & Compliance

### Data Security

#### Payment Data Protection
```
PCI DSS Compliance:
- No card data storage
- Secure transmission (TLS 1.2+)
- Regular security audits
- Vulnerability assessments

Data Encryption:
- Database encryption
- In-transit encryption
- API communication security
- Backup encryption
```

#### User Data Protection
```
Privacy Controls:
- Data minimization
- Purpose limitation
- Consent management
- Right to erasure

Access Controls:
- Role-based permissions
- Two-factor authentication
- Session management
- Activity logging
```

### Compliance Management

#### GDPR Compliance
```
Data Protection:
- Privacy by design
- Data subject rights
- Breach notification
- Data protection impact assessments

User Rights:
- Right to access
- Right to rectification
- Right to erasure
- Right to portability
```

#### Financial Compliance
```
Financial Regulations:
- Anti-money laundering (AML)
- Know your customer (KYC)
- Tax compliance
- Financial reporting

Record Keeping:
- Transaction records
- Audit trails
- Compliance documentation
- Retention policies
```

## Monitoring & Maintenance

### System Monitoring

#### Performance Monitoring
```
Key Metrics:
- Page load times
- Database query performance
- API response times
- Error rates

Monitoring Tools:
- Built-in performance dashboard
- Third-party monitoring
- Uptime monitoring
- Performance alerts
```

#### Health Checks
```
Daily Checks:
- System functionality
- Payment gateway connectivity
- Database integrity
- Security status

Weekly Checks:
- Performance optimization
- Log file review
- Security updates
- Backup verification
```

### Maintenance Procedures

#### Regular Maintenance
```
Daily Tasks:
- Monitor transactions
- Review error logs
- Check system alerts
- Verify backup completion

Weekly Tasks:
- Performance review
- Security updates
- Database cleanup
- User access audit

Monthly Tasks:
- Comprehensive system review
- Performance optimization
- Security assessment
- Documentation updates
```

#### Update Management
```
Plugin Updates:
- Test in staging environment
- Review changelog
- Schedule maintenance window
- Update production system
- Verify functionality

Security Updates:
- Apply immediately for critical updates
- Test thoroughly
- Monitor for issues
- Document changes
```

## Backup & Recovery

### Backup Strategy

#### Backup Components
```
Data to Backup:
- WordPress database
- Plugin configuration
- Transaction data
- User data
- System files

Backup Frequency:
- Database: Daily
- Files: Weekly
- Full system: Monthly
- Critical changes: Immediate
```

#### Backup Storage
```
Storage Options:
- Local storage (short-term)
- Cloud storage (long-term)
- Off-site storage (disaster recovery)
- Encrypted storage (security)

Retention Policy:
- Daily backups: 30 days
- Weekly backups: 12 weeks
- Monthly backups: 12 months
- Annual backups: 7 years
```

### Disaster Recovery

#### Recovery Procedures
```
Recovery Scenarios:
- Data corruption
- Security breach
- System failure
- Human error

Recovery Steps:
1. Assess damage
2. Restore from backup
3. Verify data integrity
4. Test functionality
5. Monitor for issues
```

#### Business Continuity
```
Continuity Planning:
- Identify critical functions
- Define recovery time objectives
- Establish communication plans
- Document procedures

Emergency Contacts:
- Hosting provider
- Payment gateway support
- Development team
- Key stakeholders
```

## Troubleshooting for Admins

### Common Administrative Issues

#### Configuration Problems
```
Settings Not Saving:
- Check file permissions
- Verify database connectivity
- Review PHP error logs
- Test with minimal plugins

Gateway Connection Issues:
- Verify API credentials
- Check firewall settings
- Test network connectivity
- Review SSL certificates
```

#### Performance Issues
```
Slow Performance:
- Enable caching
- Optimize database
- Review server resources
- Monitor query performance

High Resource Usage:
- Identify resource-heavy processes
- Optimize queries
- Implement caching
- Consider server upgrade
```

### Advanced Troubleshooting

#### Database Issues
```
Database Problems:
- Check table integrity
- Repair corrupted tables
- Optimize database structure
- Review query logs

Data Synchronization:
- Force re-sync operations
- Check API connectivity
- Verify webhook delivery
- Review error logs
```

#### Security Incidents
```
Security Response:
- Isolate affected systems
- Change all passwords
- Review access logs
- Update security measures

Incident Documentation:
- Record timeline
- Document actions taken
- Identify root cause
- Implement preventive measures
```

---

## Quick Reference

### Essential Admin URLs
```
Main Settings: /wp-admin/admin.php?page=slbp-settings
Transactions: /wp-admin/admin.php?page=slbp-transactions
Users: /wp-admin/users.php
Reports: /wp-admin/admin.php?page=slbp-reports
Tools: /wp-admin/admin.php?page=slbp-tools
```

### Emergency Procedures
```
Payment Issues: Check gateway status, verify API
Site Down: Deactivate plugin, check error logs
Data Loss: Restore from backup, verify integrity
Security Breach: Change passwords, review logs
```

### Support Resources
- **Emergency Support**: contact@skyianllc.com
- **Documentation**: /docs/
- **GitHub Issues**: Technical problems
- **Community Forum**: User discussions

---

*This guide is updated regularly. Last update: 2024-07-28*