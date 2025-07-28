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

## 📚 Documentation

### Complete Documentation Hub
Access our comprehensive documentation at **[docs/README.md](./docs/README.md)**

### Quick Links
- **🚀 [Getting Started](./docs/user/getting-started.md)** - Quick setup guide (5 minutes)
- **📖 [User Manual](./docs/user/user-manual.md)** - Complete user guide
- **⚙️ [Admin Guide](./docs/admin/admin-guide.md)** - Administrator configuration
- **🔧 [API Documentation](./docs/developer/api/README.md)** - Developer integration guide
- **⚖️ [Legal & Compliance](./docs/legal/)** - Terms, Privacy, and Compliance
- **🚀 [Launch Planning](./docs/operations/launch-planning.md)** - Deployment and operations

### Support Resources
- **❓ [FAQ](./docs/user/faq.md)** - Frequently asked questions
- **🔧 [Troubleshooting](./docs/user/troubleshooting.md)** - Problem resolution
- **📋 [Changelog](./docs/changelog/CHANGELOG.md)** - Version history and updates

## Plugin Structure

```
skylearn-billing-pro/
├── skylearn-billing-pro.php          # Main plugin file
├── docs/                              # 📚 Complete documentation hub
│   ├── user/                         # User guides and manuals
│   ├── admin/                        # Administrator documentation
│   ├── developer/                    # API and integration guides
│   ├── legal/                        # Legal and compliance docs
│   ├── operations/                   # Launch and operations guides
│   └── changelog/                    # Version history
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
├── assets/                           # Plugin assets
│   └── images/                       # Image assets
├── examples/                         # Code examples and integrations
├── templates/                        # Email and page templates
└── monitoring/                       # Performance and analytics tools
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

## 🚀 Quick Start

### For End Users
1. **[Get Started in 5 Minutes](./docs/user/getting-started.md)** - Quick setup guide
2. **[Read the User Manual](./docs/user/user-manual.md)** - Complete usage guide
3. **[Check the FAQ](./docs/user/faq.md)** - Common questions answered

### For Administrators
1. **[Installation Guide](./docs/admin/admin-guide.md#installation--setup)** - Detailed setup
2. **[Configuration Guide](./docs/admin/admin-guide.md#configuration-management)** - Settings setup
3. **[Payment Gateway Setup](./docs/admin/admin-guide.md#payment-gateway-administration)** - Gateway configuration

### For Developers
1. **[API Documentation](./docs/developer/api/README.md)** - Complete API reference
2. **[OpenAPI Specification](./docs/developer/api/openapi.yaml)** - Machine-readable API spec
3. **[Integration Examples](./docs/developer/examples/)** - Code samples and tutorials

## Configuration

### Basic Setup (WordPress Admin)
The plugin creates the following database tables:

- `wp_slbp_transactions`: Payment transaction records
- `wp_slbp_subscriptions`: Subscription management
- `wp_slbp_licenses`: License key management

### Quick Configuration Steps
1. **Install & Activate**: Upload plugin and activate in WordPress
2. **Configure Gateway**: Set up your Lemon Squeezy API credentials
3. **Connect LearnDash**: Enable automatic course enrollment
4. **Test Setup**: Process a test transaction
5. **Go Live**: Switch to production mode

*For detailed configuration instructions, see the [Administrator Guide](./docs/admin/admin-guide.md)*

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

## 📞 Support & Contact

### Documentation & Help
- **📚 [Complete Documentation](./docs/README.md)** - Full documentation hub
- **❓ [FAQ](./docs/user/faq.md)** - Frequently asked questions  
- **🔧 [Troubleshooting](./docs/user/troubleshooting.md)** - Problem resolution guide
- **🚀 [Getting Started](./docs/user/getting-started.md)** - Quick start guide

### Professional Support
- **📧 Email**: contact@skyianllc.com
- **🌐 Website**: https://skyianllc.com
- **📋 Support Portal**: [Customer Support Portal]
- **📞 Phone Support**: Available for Enterprise customers

### Development & Community
- **🐛 Bug Reports**: [GitHub Issues](https://github.com/Faiver55/skylearn-billing-pro/issues)
- **💡 Feature Requests**: [GitHub Discussions]
- **👥 Community Forum**: [Coming Soon]
- **📺 Video Tutorials**: [YouTube Channel]

### Legal & Compliance
- **⚖️ [Terms of Service](./docs/legal/terms-of-service.md)**
- **🔒 [Privacy Policy](./docs/legal/privacy-policy.md)**
- **📋 [GDPR Compliance](./docs/legal/compliance/gdpr-compliance.md)**
- **🛡️ [Data Retention Policy](./docs/legal/data-retention.md)**

## 📋 Changelog

See [CHANGELOG.md](./docs/changelog/CHANGELOG.md) for detailed version history and updates.

---

*This plugin is under active development. Features and functionality will be expanded in future releases.*