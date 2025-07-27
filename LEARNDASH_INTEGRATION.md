# LearnDash LMS Integration - Developer Documentation

## Overview

The SkyLearn Billing Pro LearnDash integration provides seamless automation of user enrollment and unenrollment based on payment events from Lemon Squeezy. This integration ensures that users are automatically enrolled in courses when they make successful payments and unenrolled when payments fail or subscriptions are cancelled.

## Architecture

### Class Structure

- **`SLBP_Abstract_LMS_Integration`**: Base abstract class defining the interface for all LMS integrations
- **`SLBP_LearnDash`**: Concrete implementation for LearnDash LMS
- **`SLBP_Enrollment_Admin`**: Admin interface for managing enrollments and viewing logs

### Key Features

1. **Automated Enrollment**: Users are automatically enrolled in mapped courses upon successful payment
2. **User Creation**: New users are created automatically if they don't exist in WordPress
3. **Course Mapping**: Products can be mapped to multiple courses for bundle support
4. **Enrollment Logging**: All enrollment actions are logged for audit and troubleshooting
5. **Manual Management**: Admin interface for manual enrollment/unenrollment
6. **Progress Tracking**: Integration with LearnDash progress tracking

## Configuration

### Prerequisites

1. LearnDash LMS plugin must be installed and activated
2. SkyLearn Billing Pro plugin must be configured with Lemon Squeezy
3. Database tables must be created (handled by plugin activation)

### Product-to-Course Mapping

Products from your payment gateway need to be mapped to LearnDash courses:

```php
$plugin = SLBP_Plugin::get_instance();
$learndash = $plugin->get_lms_integration( 'learndash' );

// Map a product to multiple courses
$learndash->update_product_course_mapping( 'product_123', array( 101, 102, 103 ) );

// Get mapped courses for a product
$course_ids = $learndash->get_product_course_mapping( 'product_123' );

// Remove mapping
$learndash->remove_product_course_mapping( 'product_123' );
```

## Webhook Integration

The integration automatically processes payment events from Lemon Squeezy:

### Enrollment Events
- `order_created` - One-time purchase completed
- `subscription_created` - New subscription created
- `subscription_resumed` - Subscription resumed after cancellation
- `subscription_unpaused` - Subscription unpaused
- `subscription_payment_success` - Recurring payment successful

### Unenrollment Events
- `subscription_cancelled` - Subscription cancelled
- `subscription_expired` - Subscription expired
- `subscription_paused` - Subscription paused (optional, controlled by filter)

## API Usage

### Basic Enrollment Operations

```php
$plugin = SLBP_Plugin::get_instance();
$learndash = $plugin->get_lms_integration( 'learndash' );

// Check if LearnDash is available
if ( $learndash->is_available() ) {
    // Enroll user in course
    $result = $learndash->enroll_user( $user_id, $course_id, array(
        'transaction_id' => 'txn_123',
        'subscription_id' => 'sub_456',
        'access_mode' => 'full',
        'status' => 'active'
    ) );
    
    if ( is_wp_error( $result ) ) {
        // Handle error
        error_log( 'Enrollment failed: ' . $result->get_error_message() );
    }
    
    // Unenroll user from course
    $result = $learndash->unenroll_user( $user_id, $course_id );
    
    // Check enrollment status
    $is_enrolled = $learndash->is_user_enrolled( $user_id, $course_id );
    
    // Get user's enrolled courses
    $course_ids = $learndash->get_user_courses( $user_id );
    
    // Get course progress
    $progress = $learndash->get_course_progress( $user_id, $course_id );
}
```

### Payment Processing

```php
// Process successful payment for enrollment
$payment_data = array(
    'user_email' => 'user@example.com',
    'user_name' => 'John Doe',
    'product_id' => 'product_123',
    'transaction_id' => 'txn_456',
    'subscription_id' => 'sub_789',  // Optional for subscriptions
);

$result = $learndash->process_payment_success( $payment_data );

// Process payment failure for unenrollment
$result = $learndash->process_payment_failure( $payment_data );
```

## Database Schema

### Enrollment Logs Table

```sql
CREATE TABLE wp_slbp_enrollment_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    course_id bigint(20) NOT NULL,
    action varchar(50) NOT NULL,           -- 'enrolled' or 'unenrolled'
    status varchar(20) NOT NULL,           -- 'success' or 'failed'
    transaction_id varchar(100) DEFAULT NULL,
    lms varchar(50) NOT NULL,              -- 'learndash'
    notes text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY course_id (course_id),
    KEY action (action),
    KEY status (status),
    KEY transaction_id (transaction_id),
    KEY lms (lms),
    KEY created_at (created_at)
);
```

### User Metadata

Enrollment metadata is stored as user meta:

- `slbp_enrollment_{course_id}`: Contains enrollment details including transaction ID, subscription ID, enrollment date, and source

## Admin Interface

### Enrollment Logs Page

Located at `Admin > SkyLearn Billing > Enrollment Logs`, this page provides:

- Searchable and filterable log of all enrollment actions
- Manual enrollment/unenrollment tools
- Bulk operations for log management
- Export functionality for reporting

### User Profile Integration

User profiles display enrolled courses with enrollment metadata:

- Course name and ID
- Enrollment date
- Transaction ID
- Source (automatic or manual)

## Hooks and Filters

### Action Hooks

```php
// Fired when user is enrolled
do_action( 'slbp_user_enrolled', $user_id, $course_id, $lms_id, $args );

// Fired when user is unenrolled
do_action( 'slbp_user_unenrolled', $user_id, $course_id, $lms_id, $args );
```

### Filter Hooks

```php
// Control whether to unenroll users when subscription is paused
apply_filters( 'slbp_unenroll_on_subscription_pause', false );
```

## Extending the Integration

### Adding New LMS Support

1. Create a new class extending `SLBP_Abstract_LMS_Integration`
2. Implement all abstract methods
3. Register the integration in the plugin's `init_lms_integrations()` method

```php
class SLBP_TutorLMS extends SLBP_Abstract_LMS_Integration {
    protected $lms_id = 'tutorlms';
    protected $lms_name = 'TutorLMS';
    
    protected function init() {
        // Initialize TutorLMS-specific functionality
    }
    
    public function is_available() {
        return function_exists( 'tutor' );
    }
    
    // Implement other abstract methods...
}
```

### Custom Enrollment Logic

Override methods in the LearnDash class or use hooks to customize behavior:

```php
add_action( 'slbp_user_enrolled', function( $user_id, $course_id, $lms_id, $args ) {
    // Custom logic after enrollment
    if ( $lms_id === 'learndash' ) {
        // Send welcome email
        // Update user role
        // Trigger automation
    }
} );
```

## Security Considerations

- All admin operations require `manage_options` capability
- AJAX requests are nonce-protected
- User input is sanitized and validated
- Database queries use prepared statements
- Enrollment operations validate user and course existence

## Troubleshooting

### Common Issues

1. **LearnDash not detected**: Ensure LearnDash plugin is active
2. **Enrollments not working**: Check webhook configuration and product mappings
3. **Users not created**: Verify email validation and user creation permissions
4. **Logs not appearing**: Ensure database table was created during activation

### Debug Mode

Enable debug logging by adding to wp-config.php:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Log messages will appear in `/wp-content/debug.log`

### Manual Testing

Use the admin enrollment tools to test functionality without webhook events:

1. Go to `SkyLearn Billing > Enrollment Logs`
2. Use the manual enrollment form
3. Check the logs for success/failure status
4. Verify enrollment in LearnDash

## Performance Considerations

- Enrollment operations are logged asynchronously when possible
- Bulk operations are paginated to prevent timeouts
- Database queries are optimized with proper indexing
- User creation is cached to prevent duplicate processing

## Future Enhancements

- Support for additional LMS platforms
- Advanced course access controls (time-limited, drip content)
- Integration with membership plugins
- Enhanced reporting and analytics
- Automated email notifications
- Group enrollment support