<?php
/**
 * Mock factory for creating test objects
 *
 * @package SkyLearnBillingPro\Tests
 */

/**
 * Factory class for creating mock objects for testing.
 */
class SLBP_Mock_Factory {

    /**
     * Create a mock payment gateway.
     *
     * @param array $args Gateway configuration.
     * @return object Mock gateway object.
     */
    public static function create_payment_gateway( $args = array() ) {
        $defaults = array(
            'id'          => 'test-gateway',
            'title'       => 'Test Gateway',
            'description' => 'Test payment gateway',
            'enabled'     => true,
            'test_mode'   => true,
        );

        $config = array_merge( $defaults, $args );

        return (object) array(
            'get_id'          => function() use ( $config ) { return $config['id']; },
            'get_title'       => function() use ( $config ) { return $config['title']; },
            'get_description' => function() use ( $config ) { return $config['description']; },
            'is_enabled'      => function() use ( $config ) { return $config['enabled']; },
            'is_test_mode'    => function() use ( $config ) { return $config['test_mode']; },
            'process_payment' => function( $amount, $data ) {
                return array(
                    'success' => true,
                    'transaction_id' => 'test_' . uniqid(),
                    'amount' => $amount,
                );
            },
        );
    }

    /**
     * Create a mock transaction.
     *
     * @param array $args Transaction data.
     * @return array Mock transaction data.
     */
    public static function create_transaction( $args = array() ) {
        $defaults = array(
            'id'             => uniqid(),
            'user_id'        => 1,
            'amount'         => 29.99,
            'currency'       => 'USD',
            'status'         => 'completed',
            'gateway'        => 'test-gateway',
            'transaction_id' => 'test_' . uniqid(),
            'created_at'     => current_time( 'mysql' ),
            'metadata'       => array(),
        );

        return array_merge( $defaults, $args );
    }

    /**
     * Create a mock subscription.
     *
     * @param array $args Subscription data.
     * @return array Mock subscription data.
     */
    public static function create_subscription( $args = array() ) {
        $defaults = array(
            'id'             => uniqid(),
            'user_id'        => 1,
            'plan_id'        => 'test-plan',
            'status'         => 'active',
            'amount'         => 29.99,
            'currency'       => 'USD',
            'interval'       => 'monthly',
            'trial_days'     => 0,
            'next_payment'   => date( 'Y-m-d H:i:s', strtotime( '+1 month' ) ),
            'created_at'     => current_time( 'mysql' ),
            'gateway'        => 'test-gateway',
            'gateway_sub_id' => 'sub_' . uniqid(),
        );

        return array_merge( $defaults, $args );
    }

    /**
     * Create a mock course.
     *
     * @param array $args Course data.
     * @return object Mock course object.
     */
    public static function create_course( $args = array() ) {
        $defaults = array(
            'ID'          => rand( 1000, 9999 ),
            'post_title'  => 'Test Course',
            'post_type'   => 'sfwd-courses',
            'post_status' => 'publish',
            'price'       => 99.99,
            'access_type' => 'closed',
        );

        $data = array_merge( $defaults, $args );

        return (object) array(
            'ID'         => $data['ID'],
            'post_title' => $data['post_title'],
            'post_type'  => $data['post_type'],
            'get_price'  => function() use ( $data ) { return $data['price']; },
            'get_access_type' => function() use ( $data ) { return $data['access_type']; },
        );
    }

    /**
     * Create a mock API request.
     *
     * @param array $args Request data.
     * @return WP_REST_Request Mock request object.
     */
    public static function create_api_request( $args = array() ) {
        $defaults = array(
            'method' => 'GET',
            'route'  => '/skylearn-billing-pro/v1/test',
            'params' => array(),
            'body'   => '',
            'headers' => array(),
        );

        $data = array_merge( $defaults, $args );

        // Create a simple mock of WP_REST_Request
        return (object) array(
            'get_method'    => function() use ( $data ) { return $data['method']; },
            'get_route'     => function() use ( $data ) { return $data['route']; },
            'get_params'    => function() use ( $data ) { return $data['params']; },
            'get_param'     => function( $key ) use ( $data ) {
                return isset( $data['params'][ $key ] ) ? $data['params'][ $key ] : null;
            },
            'get_body'      => function() use ( $data ) { return $data['body']; },
            'get_headers'   => function() use ( $data ) { return $data['headers']; },
            'get_header'    => function( $key ) use ( $data ) {
                return isset( $data['headers'][ $key ] ) ? $data['headers'][ $key ] : null;
            },
        );
    }

    /**
     * Create a mock webhook payload.
     *
     * @param string $event Event type.
     * @param array  $data  Event data.
     * @return array Mock webhook payload.
     */
    public static function create_webhook_payload( $event = 'payment.completed', $data = array() ) {
        $base_payload = array(
            'event' => $event,
            'timestamp' => time(),
            'id' => uniqid(),
        );

        switch ( $event ) {
            case 'payment.completed':
                $base_payload['data'] = array_merge(
                    array(
                        'transaction_id' => 'test_' . uniqid(),
                        'amount' => 29.99,
                        'currency' => 'USD',
                        'customer_email' => 'test@example.com',
                    ),
                    $data
                );
                break;

            case 'subscription.created':
                $base_payload['data'] = array_merge(
                    array(
                        'subscription_id' => 'sub_' . uniqid(),
                        'plan_id' => 'test-plan',
                        'customer_email' => 'test@example.com',
                        'status' => 'active',
                    ),
                    $data
                );
                break;

            default:
                $base_payload['data'] = $data;
        }

        return $base_payload;
    }

    /**
     * Create mock WordPress environment variables.
     *
     * @param array $overrides Environment overrides.
     * @return array Mock environment data.
     */
    public static function create_wp_environment( $overrides = array() ) {
        $defaults = array(
            'wp_version' => '6.3',
            'php_version' => PHP_VERSION,
            'is_multisite' => false,
            'current_user_id' => 1,
            'current_user_can_manage_options' => true,
            'plugin_active' => true,
        );

        return array_merge( $defaults, $overrides );
    }
}