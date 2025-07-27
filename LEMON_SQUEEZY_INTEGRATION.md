# SkyLearn Billing Pro - Lemon Squeezy Integration

This document provides an overview of the Lemon Squeezy payment gateway integration and how to use it.

## Features

- **Complete Lemon Squeezy API Integration**: Products, checkouts, subscriptions, webhooks
- **Automatic Course Enrollment**: Users are automatically enrolled/unenrolled based on payment status
- **Real-time Webhooks**: Process subscription updates, cancellations, payment failures in real-time
- **Product Mapping**: Map Lemon Squeezy products to LearnDash courses
- **Developer API**: Simple functions for creating checkouts and managing billing
- **Security**: HMAC signature validation for webhooks, secure credential storage

## Setup

### 1. Configure Lemon Squeezy Settings

1. Go to **SkyLearn Billing** → **Settings** → **Payment Gateways**
2. Enter your Lemon Squeezy API key and Store ID
3. Set a webhook secret (use a secure random string)
4. Test the connection to verify your credentials

### 2. Set Up Webhooks

1. Copy the webhook URL from the settings page
2. Log in to your Lemon Squeezy dashboard
3. Navigate to Settings → Webhooks
4. Add a new webhook endpoint with the copied URL
5. Select all subscription and order events
6. Set the webhook secret (same as in plugin settings)

### 3. Map Products to Courses

1. Go to **SkyLearn Billing** → **Settings** → **Product Mapping**
2. Add your Lemon Squeezy product IDs and map them to LearnDash courses
3. Users will be automatically enrolled when they purchase these products

## Developer API

### Create Checkout Sessions

```php
// Simple checkout for current user
$checkout = slbp_create_checkout('product_123');

// Checkout with custom options
$checkout = slbp_create_checkout('product_123', array(
    'customer_email' => 'user@example.com',
    'customer_name'  => 'John Doe',
    'user_id'        => 123,
    'embed'          => true
));

if (!is_wp_error($checkout)) {
    // Redirect to checkout URL
    wp_redirect($checkout['url']);
}
```

### Check User Access

```php
// Check if user has access to a course through billing
if (slbp_user_has_course_access(123, get_current_user_id())) {
    echo 'User has access to this course';
}

// Get all courses user has access to
$courses = SLBP_API::get_user_course_access(get_current_user_id());
```

### Manage Subscriptions

```php
// Get user's subscriptions
$subscriptions = slbp_get_user_subscriptions();

// Cancel a subscription
$result = SLBP_API::cancel_subscription('sub_123', get_current_user_id());
```

### Get Billing Stats

```php
// Get comprehensive billing statistics
$stats = slbp_get_user_billing_stats();
echo "Total spent: $" . $stats['total_spent'];
echo "Active subscriptions: " . $stats['active_subscriptions'];
```

## Hooks and Filters

### Actions

```php
// User enrolled in product
add_action('slbp_user_enrolled_in_product', function($user_id, $product_id, $course_ids, $args) {
    // Custom logic after enrollment
}, 10, 4);

// User unenrolled from product
add_action('slbp_user_unenrolled_from_product', function($user_id, $product_id, $course_ids, $args) {
    // Custom logic after unenrollment
}, 10, 4);

// Subscription payment failed
add_action('slbp_subscription_payment_failed', function($user_id, $subscription) {
    // Send custom notification
}, 10, 2);

// Subscription payment successful
add_action('slbp_subscription_payment_success', function($user_id, $subscription) {
    // Custom success handling
}, 10, 2);
```

### Filters

```php
// Control whether to unenroll users when subscription is paused
add_filter('slbp_unenroll_on_subscription_pause', '__return_true');
```

## Webhook Events

The following Lemon Squeezy webhook events are automatically processed:

- `order_created` - New purchase completed
- `subscription_created` - New subscription started
- `subscription_updated` - Subscription details changed
- `subscription_cancelled` - Subscription cancelled by user/admin
- `subscription_resumed` - Cancelled subscription resumed
- `subscription_expired` - Subscription ended naturally
- `subscription_paused` - Subscription temporarily paused
- `subscription_unpaused` - Paused subscription resumed
- `subscription_payment_failed` - Payment failed for renewal
- `subscription_payment_success` - Successful renewal payment

## Data Storage

### User Meta

- `slbp_subscriptions` - Array of subscription IDs
- `slbp_orders` - Array of order IDs
- `slbp_enrollment_history` - Enrollment/unenrollment history

### Options

- `slbp_subscription_{id}` - Individual subscription data
- `slbp_order_{id}` - Individual order data
- `slbp_enrollment_records` - Global enrollment history

## Error Handling

All API functions return `WP_Error` objects on failure:

```php
$result = slbp_create_checkout('invalid_product');
if (is_wp_error($result)) {
    echo 'Error: ' . $result->get_error_message();
}
```

## Logging

Enable debug mode in **General Settings** to log all payment gateway activity. Logs can be viewed in the admin interface or via `WP_DEBUG_LOG`.

## Security

- All webhook requests are validated using HMAC signatures
- API credentials are stored securely
- User permissions are verified before subscription actions
- Input data is sanitized and validated

## Testing

Use test mode in the settings to test the integration:

1. Enable "Test Mode" in payment settings
2. Use Lemon Squeezy test products
3. Monitor logs for webhook processing
4. Verify course enrollments work correctly

## Extending the Gateway

The abstract payment gateway class allows for easy extension:

```php
class SLBP_Custom_Gateway extends SLBP_Abstract_Payment_Gateway {
    protected $gateway_id = 'custom_gateway';
    protected $gateway_name = 'Custom Gateway';
    
    // Implement required abstract methods
    public function test_connection() { /* ... */ }
    public function create_checkout($args) { /* ... */ }
    // ... etc
}

// Register the gateway
$plugin = SLBP_Plugin::get_instance();
$plugin->register_payment_gateway('custom_gateway', 'SLBP_Custom_Gateway');
```

## Support

For issues or questions:

1. Check the debug logs if debug mode is enabled
2. Verify webhook configuration in Lemon Squeezy dashboard
3. Test API connection in plugin settings
4. Review product mappings for correct course assignments