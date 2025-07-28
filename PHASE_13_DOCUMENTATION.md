# Phase 13: Advanced Analytics & Reporting - Documentation

## Overview

Phase 13 introduces robust analytics and reporting capabilities to SkyLearn Billing Pro, enabling data-driven decision-making through interactive dashboards, customizable reports, and comprehensive data analysis tools.

## Features Implemented

### 1. Dashboard & Visualization

#### Interactive Analytics Dashboard
- **Location**: `/admin/partials/analytics.php`
- **Features**:
  - Real-time metrics display (revenue, MRR, users, churn)
  - Interactive charts using Chart.js
  - Date range filtering (7 days, 30 days, custom ranges)
  - Export capabilities for all data types

#### Key Metrics Displayed
- **Total Revenue**: Aggregate revenue from all transactions
- **Monthly Recurring Revenue (MRR)**: Subscription-based recurring revenue
- **Active Users**: Currently active user count
- **Churn Rate**: Percentage of cancelled subscriptions
- **New Signups**: Recent user registrations
- **Course Completions**: Completed course count
- **Refund Rate**: Percentage of refunded transactions
- **Top Products**: Best-performing courses/products

#### Chart Capabilities
- Line charts for revenue trends
- Doughnut charts for subscription analytics
- Responsive design for all screen sizes
- Real-time data updates via AJAX

### 2. Enhanced Export System

#### Multi-Format Export Manager
- **Class**: `SLBP_Export_Manager`
- **Location**: `/includes/analytics/class-slbp-export-manager.php`

#### Supported Formats
1. **CSV Export**
   - UTF-8 BOM for Excel compatibility
   - Customizable headers
   - Large dataset support

2. **PDF Export**
   - TCPDF library support
   - DomPDF library support
   - HTML fallback for basic PDF functionality
   - Professional report formatting

3. **XLSX Export**
   - PhpSpreadsheet library support
   - Styled headers and data
   - Fallback to CSV when libraries unavailable

#### Export Features
- Automatic file cleanup (configurable retention)
- Secure file storage with .htaccess protection
- Download URLs with temporary access
- Audit logging of all exports

### 3. Advanced Reporting Engine

#### Report Types Available
- **Revenue Reports**: Detailed revenue analytics and trends
- **Subscription Reports**: Subscription metrics and lifecycle analysis
- **User Activity Reports**: User engagement and activity patterns
- **Course Performance Reports**: Course enrollment and completion metrics
- **Refund Reports**: Refund analysis and trends
- **Compliance Reports**: Data handling and privacy compliance metrics

#### Custom Report Generation
- **Class**: `SLBP_Advanced_Reports`
- **Location**: `/includes/reporting/class-slbp-advanced-reports.php`

#### Features
- Configurable filters for all report types
- Scheduled report generation
- Email delivery of reports
- Drill-down capabilities
- Real-time data processing

### 4. Custom KPI Management

#### KPI Manager Interface
- **Location**: `/admin/partials/kpi-management.php`
- **JavaScript**: `/admin/js/kpi-management.js`

#### KPI Types Supported
1. **Simple Metrics**: Direct metric values
2. **Ratio/Percentage**: Calculated ratios between metrics
3. **Growth Rate**: Period-over-period growth calculations
4. **Average Values**: Time-based averages
5. **Custom Formulas**: User-defined mathematical expressions

#### KPI Features
- Pre-built templates for common KPIs
- Custom threshold alerts (warning and critical levels)
- Historical trend charts
- Email notifications for threshold breaches
- Active/inactive status management

#### Available Metrics for KPIs
- Total Revenue
- Monthly Recurring Revenue
- Active Users
- Churn Rate
- New Signups
- Course Completions
- Refund Rate
- Average Order Value
- Customer Acquisition Cost
- Total Customers

### 5. Third-Party Analytics Integration

#### Supported Platforms
- **Google Analytics (Universal)**: Traditional GA tracking
- **Google Analytics 4**: Next-generation GA tracking
- **Mixpanel**: Advanced user analytics
- **Segment**: Multi-platform data routing
- **Facebook Pixel**: Conversion tracking
- **Custom Webhooks**: Flexible external integrations

#### Integration Features
- **Class**: `SLBP_Third_Party_Analytics`
- **Location**: `/includes/external-integrations/class-slbp-third-party-analytics.php`

#### Event Tracking
- Purchase events
- Refund events
- Subscription lifecycle events
- Course enrollment and completion
- User login tracking
- Admin activity tracking

#### Configuration
Each integration supports:
- Flexible configuration options
- Secure credential storage
- Event filtering and customization
- Error handling and logging

### 6. API Endpoints for Data Access

#### REST API Implementation
- **Class**: `SLBP_Analytics_API`
- **Location**: `/includes/api/class-slbp-analytics-api.php`
- **Namespace**: `slbp/v1`

#### Available Endpoints

##### Dashboard Metrics
```
GET /wp-json/slbp/v1/analytics/metrics
```
Parameters:
- `date_range`: Time period filter
- `start_date`: Custom start date
- `end_date`: Custom end date

##### Revenue Chart Data
```
GET /wp-json/slbp/v1/analytics/revenue-chart
```
Parameters:
- `date_range`: Time period filter
- `grouping`: Data grouping (daily, weekly, monthly)

##### Subscription Analytics
```
GET /wp-json/slbp/v1/analytics/subscriptions
```

##### Custom Report Generation
```
POST /wp-json/slbp/v1/analytics/reports/{type}
```
Parameters:
- `type`: Report type (revenue, subscriptions, etc.)
- `filters`: Report filters object
- `format`: Export format (json, csv, pdf)

##### Data Export
```
POST /wp-json/slbp/v1/analytics/export
```
Parameters:
- `type`: Data type to export
- `format`: Export format
- `filters`: Data filters

##### KPI Management
```
GET|POST /wp-json/slbp/v1/analytics/kpis
GET|PUT|DELETE /wp-json/slbp/v1/analytics/kpis/{id}
```

#### Authentication
- WordPress user authentication (manage_options capability)
- API key authentication for external access
- Request logging and rate limiting

### 7. Data Anonymization & Privacy

#### Anonymization Engine
- **Class**: `SLBP_Data_Anonymizer`
- **Location**: `/includes/analytics/class-slbp-data-anonymizer.php`

#### Anonymization Methods
1. **Hashing**: Irreversible data transformation
2. **IP Masking**: Preserve country-level data while masking identity
3. **Pseudonymization**: Replace with consistent fake identifiers
4. **Generalization**: Reduce data specificity
5. **Filtering**: Remove sensitive fields
6. **Complete Removal**: Delete data entirely

#### Data Types Covered
- User personal information (email, name, phone, address)
- Transaction data (preserving business intelligence)
- Analytics session data
- IP addresses and user agents

#### GDPR Compliance
- Personal data export functionality
- Personal data erasure (with anonymization option)
- Automated data retention policies
- Audit logging of all privacy operations

#### Scheduled Anonymization
- Configurable retention periods
- Automatic anonymization of old data
- Background processing to avoid performance impact

### 8. Security & Audit Features

#### Access Controls
- Role-based permissions for all analytics features
- API key management for external access
- Secure file storage for exports
- NONCE verification for all AJAX requests

#### Audit Logging
- All analytics access logged
- Export activities tracked
- KPI modifications recorded
- Anonymization operations logged
- User permission changes documented

#### Data Protection
- Secure salt generation for hashing
- Protected export directories
- Temporary file cleanup
- Encrypted API communications

## Installation & Setup

### Requirements
- WordPress 5.0+
- PHP 7.4+
- LearnDash plugin (for course analytics)
- Optional: TCPDF or DomPDF for PDF exports
- Optional: PhpSpreadsheet for XLSX exports

### Basic Setup
1. The analytics system is automatically initialized when the plugin is activated
2. Navigate to **SkyLearn Billing Pro > Analytics** to access the dashboard
3. Configure third-party integrations via **Settings > Integrations**
4. Set up custom KPIs via **Analytics > KPI Management**

### API Setup
1. Generate API keys via **Settings > API Keys**
2. Configure external applications with the provided endpoints
3. Test authentication using the provided examples

### Privacy Setup
1. Configure anonymization settings via **Settings > Privacy**
2. Set data retention policies
3. Schedule automatic anonymization tasks
4. Review GDPR compliance settings

## Usage Guide

### Viewing Analytics
1. Access the main analytics dashboard from the admin menu
2. Use date range filters to focus on specific time periods
3. Export data using the Export dropdown
4. View detailed reports through the Quick Reports section

### Creating Custom KPIs
1. Navigate to **Analytics > KPI Management**
2. Click "Add New KPI" or use a template
3. Configure calculation method and thresholds
4. Save and monitor the KPI on your dashboard

### Setting Up Integrations
1. Go to **Settings > Third-Party Analytics**
2. Choose your analytics platform
3. Enter configuration details (tracking IDs, API keys)
4. Test the integration and monitor event tracking

### Managing Data Privacy
1. Access **Settings > Privacy & Anonymization**
2. Configure anonymization rules
3. Set up scheduled anonymization
4. Review and export user data for GDPR requests

## API Usage Examples

### Get Dashboard Metrics
```javascript
fetch('/wp-json/slbp/v1/analytics/metrics?date_range=last_30_days', {
  headers: {
    'X-WP-Nonce': wpApiSettings.nonce
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Generate Custom Report
```javascript
fetch('/wp-json/slbp/v1/analytics/reports/revenue', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  body: JSON.stringify({
    filters: { date_range: 'this_month' },
    format: 'pdf'
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    window.open(data.data.download_url);
  }
});
```

### External API Access
```bash
curl -X GET \
  "https://yoursite.com/wp-json/slbp/v1/analytics/metrics" \
  -H "X-SLBP-API-Key: your-api-key-here"
```

## Troubleshooting

### Common Issues

#### Charts Not Loading
- Ensure Chart.js library is properly loaded
- Check for JavaScript errors in browser console
- Verify AJAX endpoints are responding correctly

#### Export Failures
- Check file permissions in wp-uploads directory
- Verify available disk space
- Ensure export libraries are installed (for PDF/XLSX)

#### API Authentication Errors
- Verify API key is correctly configured
- Check user permissions for analytics access
- Ensure NONCE is properly generated and passed

#### Performance Issues
- Enable caching for analytics queries
- Consider increasing PHP memory limit for large exports
- Schedule heavy operations during off-peak hours

### Debug Mode
Enable debug logging by adding to wp-config.php:
```php
define('SLBP_DEBUG', true);
```

### Support
For technical support and customization requests:
- Email: support@skyianllc.com
- Documentation: https://skyianllc.com/skylearn-billing-pro/docs
- GitHub: https://github.com/skyianllc/skylearn-billing-pro

## Customization

### Adding Custom Metrics
```php
add_filter('slbp_available_metrics', function($metrics) {
    $metrics['custom_metric'] = array(
        'name' => 'Custom Metric',
        'description' => 'Your custom metric description',
        'type' => 'number',
        'source' => 'calculated',
    );
    return $metrics;
});
```

### Custom Anonymization Rules
```php
add_filter('slbp_anonymization_rules', function($rules) {
    $rules['custom_data'] = array(
        'field_name' => array(
            'method' => 'hash',
            'preserve_domain' => false,
        ),
    );
    return $rules;
});
```

### Adding Analytics Providers
```php
add_filter('slbp_analytics_providers', function($providers) {
    $providers['custom_provider'] = array(
        'name' => 'Custom Analytics',
        'description' => 'Custom analytics integration',
        'fields' => array(
            'api_key' => array(
                'label' => 'API Key',
                'type' => 'password',
                'required' => true,
            ),
        ),
    );
    return $providers;
});
```

## Performance Considerations

### Caching Strategy
- Analytics data is cached for 1 hour by default
- Cache is automatically cleared when new data is added
- Custom cache duration can be configured per metric

### Database Optimization
- Indexes are automatically created for analytics queries
- Old data can be automatically archived or anonymized
- Query optimization for large datasets

### Scalability
- API endpoints support pagination for large datasets
- Background processing for heavy operations
- CDN support for exported files

## Security Best Practices

### Data Protection
- All sensitive data is encrypted at rest
- API communications use HTTPS
- Regular security audits are performed

### Access Control
- Implement principle of least privilege
- Regular review of user permissions
- API key rotation policies

### Compliance
- GDPR-compliant data handling
- Automatic data retention policies
- Comprehensive audit trails

---

*This documentation covers the core functionality of Phase 13. For detailed API reference and advanced customization options, please refer to the inline code documentation and additional developer resources.*