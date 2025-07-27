# SkyLearn Billing Pro

Professional LearnDash billing management with multiple payment gateway support.

## Description

SkyLearn Billing Pro is a professional WordPress plugin designed to provide comprehensive billing management for LearnDash Learning Management System (LMS) with seamless integration to multiple payment gateways, including Lemon Squeezy.

## Features

- **Multi-Gateway Support**: Integrate with Lemon Squeezy and other popular payment gateways
- **LearnDash Integration**: Seamless course enrollment and access management
- **Subscription Management**: Handle recurring subscriptions and billing cycles
- **Professional Architecture**: Built with WordPress coding standards and best practices
- **Secure & Scalable**: Implements security best practices and dependency injection
- **License Management**: Built-in licensing system for professional deployments

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- LearnDash LMS plugin
- Active license for production use

## Installation

1. Upload the plugin files to `/wp-content/plugins/skylearn-billing-pro/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Settings > SkyLearn Billing Pro to configure your payment gateways
4. Configure your LearnDash integration settings

## Plugin Structure

```
skylearn-billing-pro/
├── skylearn-billing-pro.php          # Main plugin file
├── includes/                          # Core plugin includes
│   ├── core/                         # Core architecture files
│   │   ├── class-slbp-plugin.php     # Main plugin class (Singleton)
│   │   ├── class-slbp-loader.php     # Hook loader and manager
│   │   ├── class-slbp-activator.php  # Plugin activation handler
│   │   └── class-slbp-deactivator.php # Plugin deactivation handler
│   ├── admin/                        # Admin-specific functionality
│   ├── payment-gateways/             # Payment gateway integrations
│   ├── lms-integrations/             # LMS integration modules
│   ├── licensing/                    # License management
│   └── utilities/                    # Utility classes
├── admin/                            # Admin interface assets
│   ├── css/                          # Admin stylesheets
│   ├── js/                           # Admin JavaScript
│   └── partials/                     # Admin template files
├── public/                           # Public-facing assets
│   ├── css/                          # Public stylesheets
│   ├── js/                           # Public JavaScript
│   └── partials/                     # Public template files
└── assets/                           # Plugin assets
    └── images/                       # Image assets
```

## Architecture

### Core Classes

- **SLBP_Plugin**: Main plugin class implementing singleton pattern with dependency injection
- **SLBP_Loader**: Manages WordPress hooks and actions/filters registration
- **SLBP_Activator**: Handles plugin activation, database setup, and initial configuration
- **SLBP_Deactivator**: Manages plugin deactivation and cleanup procedures

### Key Features

- **Singleton Pattern**: Ensures single instance of main plugin class
- **Dependency Injection**: Clean dependency management for better testing
- **Hook Management**: Centralized action and filter registration
- **Database Management**: Proper table creation and data handling
- **Security First**: ABSPATH checks and sanitization throughout
- **WordPress Standards**: Follows WordPress coding standards and best practices

## Configuration

The plugin creates the following database tables:

- `wp_slbp_transactions`: Payment transaction records
- `wp_slbp_subscriptions`: Subscription management
- `wp_slbp_licenses`: License key management

## Development

### Phase 1: Foundation & Architecture ✅
- [x] Plugin structure and core architecture
- [x] Database schema and activation/deactivation
- [x] Main plugin class with singleton pattern
- [x] Hook loader and dependency injection

### Phase 2: Admin Interface (Planned)
- [ ] Admin dashboard and settings pages
- [ ] Payment gateway configuration
- [ ] LearnDash integration settings
- [ ] Transaction and subscription management

### Phase 3: Payment Gateway Integration (Planned)
- [ ] Lemon Squeezy integration
- [ ] Webhook handling
- [ ] Transaction processing
- [ ] Subscription management

### Phase 4: LearnDash Integration (Planned)
- [ ] Course enrollment automation
- [ ] Access control management
- [ ] Progress tracking integration

## License

GPL v2 or later

## Author

**Skyian LLC**  
Website: https://skyianllc.com  
Contact: contact@skyianllc.com

## Changelog

### 1.0.0 - Initial Release
- Core plugin architecture
- Database schema setup
- Activation/deactivation handlers
- Foundation for payment gateway integration
- Basic admin menu structure

---

*This plugin is under active development. Features and functionality will be expanded in future releases.*