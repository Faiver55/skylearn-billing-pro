<?php
/**
 * SkyLearn Billing Pro Webhook Manager
 *
 * Manages webhook creation, delivery, and logging.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 */

/**
 * Webhook Manager Class
 *
 * Handles webhook registration, delivery, and management.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Webhook_Manager {

	/**
	 * Maximum retry attempts for failed webhooks.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $max_retries    Maximum retry attempts.
	 */
	private $max_retries = 3;

	/**
	 * Timeout for webhook requests in seconds.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $timeout    Request timeout.
	 */
	private $timeout = 30;

	/**
	 * Initialize webhook manager.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Listen for webhook events
		add_action( 'slbp_payment_success', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'slbp_subscription_created', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'slbp_subscription_updated', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'slbp_subscription_cancelled', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'slbp_enrollment_created', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'slbp_refund_processed', array( $this, 'trigger_webhook' ), 10, 2 );

		// Schedule webhook retry job
		add_action( 'slbp_retry_webhook', array( $this, 'retry_webhook' ), 10, 2 );
	}

	/**
	 * Create a new webhook.
	 *
	 * @since    1.0.0
	 * @param    int      $user_id    User ID.
	 * @param    string   $name       Webhook name.
	 * @param    string   $url        Webhook URL.
	 * @param    array    $events     Events to listen for.
	 * @param    array    $args       Additional arguments.
	 * @return   int|WP_Error         Webhook ID or WP_Error on failure.
	 */
	public function create_webhook( $user_id, $name, $url, $events = array(), $args = array() ) {
		global $wpdb;

		// Validate user
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'invalid_user', 'Invalid user ID' );
		}

		// Validate URL
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', 'Invalid webhook URL' );
		}

		// Validate events
		$valid_events = $this->get_valid_events();
		foreach ( $events as $event ) {
			if ( ! in_array( $event, $valid_events ) ) {
				return new WP_Error( 'invalid_event', "Invalid event: $event" );
			}
		}

		// Generate secret
		$secret = $this->generate_webhook_secret();

		$table_name = $wpdb->prefix . 'slbp_webhooks';

		// Insert webhook
		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'    => $user_id,
				'name'       => sanitize_text_field( $name ),
				'url'        => esc_url_raw( $url ),
				'events'     => wp_json_encode( $events ),
				'secret'     => $secret,
				'created_at' => current_time( 'mysql' ),
			),
			array(
				'%d',  // user_id
				'%s',  // name
				'%s',  // url
				'%s',  // events
				'%s',  // secret
				'%s',  // created_at
			)
		);

		if ( $result === false ) {
			return new WP_Error( 'creation_failed', 'Failed to create webhook' );
		}

		$webhook_id = $wpdb->insert_id;

		do_action( 'slbp_webhook_created', $webhook_id, $user_id, $events );

		return $webhook_id;
	}

	/**
	 * Get webhooks for a user.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID.
	 * @return   array              Array of webhooks.
	 */
	public function get_user_webhooks( $user_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_webhooks';

		$webhooks = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
			$user_id
		) );

		foreach ( $webhooks as &$webhook ) {
			$webhook->events = json_decode( $webhook->events, true ) ?: array();
		}

		return $webhooks;
	}

	/**
	 * Get webhook by ID.
	 *
	 * @since    1.0.0
	 * @param    int    $webhook_id    Webhook ID.
	 * @return   object|null           Webhook object or null.
	 */
	public function get_webhook( $webhook_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_webhooks';

		$webhook = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$webhook_id
		) );

		if ( $webhook ) {
			$webhook->events = json_decode( $webhook->events, true ) ?: array();
		}

		return $webhook;
	}

	/**
	 * Update webhook.
	 *
	 * @since    1.0.0
	 * @param    int      $webhook_id    Webhook ID.
	 * @param    array    $data          Data to update.
	 * @return   bool|WP_Error           True on success, WP_Error on failure.
	 */
	public function update_webhook( $webhook_id, $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_webhooks';

		// Get existing webhook
		$existing_webhook = $this->get_webhook( $webhook_id );
		if ( ! $existing_webhook ) {
			return new WP_Error( 'webhook_not_found', 'Webhook not found' );
		}

		// Validate URL if provided
		if ( isset( $data['url'] ) && ! filter_var( $data['url'], FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', 'Invalid webhook URL' );
		}

		// Validate events if provided
		if ( isset( $data['events'] ) ) {
			$valid_events = $this->get_valid_events();
			foreach ( $data['events'] as $event ) {
				if ( ! in_array( $event, $valid_events ) ) {
					return new WP_Error( 'invalid_event', "Invalid event: $event" );
				}
			}
			$data['events'] = wp_json_encode( $data['events'] );
		}

		// Prepare update data
		$update_data = array();
		$update_format = array();

		$allowed_fields = array( 'name', 'url', 'events', 'is_active' );
		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$update_data[ $field ] = $data[ $field ];
				$update_format[] = $field === 'is_active' ? '%d' : '%s';
			}
		}

		if ( empty( $update_data ) ) {
			return new WP_Error( 'no_data', 'No valid data provided for update' );
		}

		$result = $wpdb->update(
			$table_name,
			$update_data,
			array( 'id' => $webhook_id ),
			$update_format,
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'update_failed', 'Failed to update webhook' );
		}

		do_action( 'slbp_webhook_updated', $webhook_id, $data );

		return true;
	}

	/**
	 * Delete webhook.
	 *
	 * @since    1.0.0
	 * @param    int    $webhook_id    Webhook ID.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	public function delete_webhook( $webhook_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_webhooks';

		// Get existing webhook for logging
		$existing_webhook = $this->get_webhook( $webhook_id );
		if ( ! $existing_webhook ) {
			return new WP_Error( 'webhook_not_found', 'Webhook not found' );
		}

		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $webhook_id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'deletion_failed', 'Failed to delete webhook' );
		}

		// Also delete associated logs
		$logs_table = $wpdb->prefix . 'slbp_webhook_logs';
		$wpdb->delete(
			$logs_table,
			array( 'webhook_id' => $webhook_id ),
			array( '%d' )
		);

		do_action( 'slbp_webhook_deleted', $webhook_id, $existing_webhook );

		return true;
	}

	/**
	 * Trigger webhook for an event.
	 *
	 * @since    1.0.0
	 * @param    string    $event    Event name.
	 * @param    array     $data     Event data.
	 */
	public function trigger_webhook( $event, $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_webhooks';

		// Get all active webhooks that listen for this event
		$webhooks = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE is_active = 1 AND events LIKE %s",
			'%' . $wpdb->esc_like( '"' . $event . '"' ) . '%'
		) );

		foreach ( $webhooks as $webhook ) {
			$webhook_events = json_decode( $webhook->events, true ) ?: array();
			
			if ( in_array( $event, $webhook_events ) ) {
				$this->deliver_webhook( $webhook->id, $event, $data );
			}
		}
	}

	/**
	 * Deliver webhook.
	 *
	 * @since    1.0.0
	 * @param    int       $webhook_id    Webhook ID.
	 * @param    string    $event         Event name.
	 * @param    array     $data          Event data.
	 * @param    int       $attempt       Attempt number.
	 */
	public function deliver_webhook( $webhook_id, $event, $data, $attempt = 1 ) {
		$webhook = $this->get_webhook( $webhook_id );
		
		if ( ! $webhook || ! $webhook->is_active ) {
			return;
		}

		// Prepare payload
		$payload = array(
			'event'     => $event,
			'data'      => $data,
			'timestamp' => time(),
			'webhook'   => array(
				'id'   => $webhook->id,
				'name' => $webhook->name,
			),
		);

		// Generate signature
		$signature = $this->generate_signature( wp_json_encode( $payload ), $webhook->secret );

		// Prepare headers
		$headers = array(
			'Content-Type'     => 'application/json',
			'User-Agent'       => 'SkyLearn-Billing-Pro-Webhook/1.0',
			'X-SLBP-Event'     => $event,
			'X-SLBP-Signature' => $signature,
			'X-SLBP-Delivery'  => wp_generate_uuid4(),
		);

		// Make the request
		$response = wp_remote_post( $webhook->url, array(
			'body'        => wp_json_encode( $payload ),
			'headers'     => $headers,
			'timeout'     => $this->timeout,
			'redirection' => 0,
			'sslverify'   => true,
		) );

		// Handle response
		$this->handle_webhook_response( $webhook_id, $event, $payload, $response, $attempt );
	}

	/**
	 * Handle webhook response.
	 *
	 * @since    1.0.0
	 * @param    int       $webhook_id    Webhook ID.
	 * @param    string    $event         Event name.
	 * @param    array     $payload       Webhook payload.
	 * @param    mixed     $response      HTTP response.
	 * @param    int       $attempt       Attempt number.
	 */
	private function handle_webhook_response( $webhook_id, $event, $payload, $response, $attempt ) {
		global $wpdb;

		$logs_table = $wpdb->prefix . 'slbp_webhook_logs';
		$webhooks_table = $wpdb->prefix . 'slbp_webhooks';

		$status = 'failed';
		$response_code = 0;
		$response_body = '';

		if ( is_wp_error( $response ) ) {
			$response_body = $response->get_error_message();
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			
			if ( $response_code >= 200 && $response_code < 300 ) {
				$status = 'success';
			}
		}

		// Log the attempt
		$wpdb->insert(
			$logs_table,
			array(
				'webhook_id'    => $webhook_id,
				'event'         => $event,
				'payload'       => wp_json_encode( $payload ),
				'response_code' => $response_code,
				'response_body' => $response_body,
				'status'        => $status,
				'attempts'      => $attempt,
				'created_at'    => current_time( 'mysql' ),
			),
			array(
				'%d',  // webhook_id
				'%s',  // event
				'%s',  // payload
				'%d',  // response_code
				'%s',  // response_body
				'%s',  // status
				'%d',  // attempts
				'%s',  // created_at
			)
		);

		if ( $status === 'success' ) {
			// Update last success timestamp
			$wpdb->update(
				$webhooks_table,
				array(
					'last_success_at' => current_time( 'mysql' ),
					'failed_attempts' => 0,
				),
				array( 'id' => $webhook_id ),
				array( '%s', '%d' ),
				array( '%d' )
			);
		} else {
			// Increment failed attempts
			$wpdb->query( $wpdb->prepare(
				"UPDATE $webhooks_table SET failed_attempts = failed_attempts + 1, last_failure_at = %s WHERE id = %d",
				current_time( 'mysql' ),
				$webhook_id
			) );

			// Schedule retry if not exceeded max attempts
			if ( $attempt < $this->max_retries ) {
				$delay = $this->get_retry_delay( $attempt );
				wp_schedule_single_event( time() + $delay, 'slbp_retry_webhook', array( $webhook_id, $event, $payload, $attempt + 1 ) );
			} else {
				// Disable webhook after max retries
				$webhook = $this->get_webhook( $webhook_id );
				if ( $webhook && $webhook->failed_attempts >= 10 ) {
					$this->update_webhook( $webhook_id, array( 'is_active' => 0 ) );
					do_action( 'slbp_webhook_disabled', $webhook_id, 'too_many_failures' );
				}
			}
		}
	}

	/**
	 * Retry webhook delivery.
	 *
	 * @since    1.0.0
	 * @param    int       $webhook_id    Webhook ID.
	 * @param    string    $event         Event name.
	 * @param    array     $payload       Webhook payload.
	 * @param    int       $attempt       Attempt number.
	 */
	public function retry_webhook( $webhook_id, $event, $payload, $attempt ) {
		$this->deliver_webhook( $webhook_id, $event, $payload['data'], $attempt );
	}

	/**
	 * Get retry delay in seconds.
	 *
	 * @since    1.0.0
	 * @param    int    $attempt    Attempt number.
	 * @return   int                Delay in seconds.
	 */
	private function get_retry_delay( $attempt ) {
		// Exponential backoff: 30s, 2m, 8m
		$delays = array( 30, 120, 480 );
		return isset( $delays[ $attempt - 1 ] ) ? $delays[ $attempt - 1 ] : 480;
	}

	/**
	 * Generate webhook signature.
	 *
	 * @since    1.0.0
	 * @param    string    $payload    Webhook payload.
	 * @param    string    $secret     Webhook secret.
	 * @return   string                Signature.
	 */
	private function generate_signature( $payload, $secret ) {
		return 'sha256=' . hash_hmac( 'sha256', $payload, $secret );
	}

	/**
	 * Generate webhook secret.
	 *
	 * @since    1.0.0
	 * @return   string    Generated secret.
	 */
	private function generate_webhook_secret() {
		return bin2hex( random_bytes( 32 ) );
	}

	/**
	 * Get valid webhook events.
	 *
	 * @since    1.0.0
	 * @return   array    Array of valid events.
	 */
	public function get_valid_events() {
		$events = array(
			'payment_success',
			'payment_failed',
			'subscription_created',
			'subscription_updated',
			'subscription_cancelled',
			'subscription_renewed',
			'enrollment_created',
			'enrollment_updated',
			'refund_processed',
			'user_created',
			'course_purchased',
		);

		return apply_filters( 'slbp_webhook_valid_events', $events );
	}

	/**
	 * Get event descriptions.
	 *
	 * @since    1.0.0
	 * @return   array    Array of event descriptions.
	 */
	public function get_event_descriptions() {
		return array(
			'payment_success'        => __( 'Payment completed successfully', 'skylearn-billing-pro' ),
			'payment_failed'         => __( 'Payment failed', 'skylearn-billing-pro' ),
			'subscription_created'   => __( 'New subscription created', 'skylearn-billing-pro' ),
			'subscription_updated'   => __( 'Subscription updated', 'skylearn-billing-pro' ),
			'subscription_cancelled' => __( 'Subscription cancelled', 'skylearn-billing-pro' ),
			'subscription_renewed'   => __( 'Subscription renewed', 'skylearn-billing-pro' ),
			'enrollment_created'     => __( 'User enrolled in course', 'skylearn-billing-pro' ),
			'enrollment_updated'     => __( 'User enrollment updated', 'skylearn-billing-pro' ),
			'refund_processed'       => __( 'Refund processed', 'skylearn-billing-pro' ),
			'user_created'           => __( 'New user account created', 'skylearn-billing-pro' ),
			'course_purchased'       => __( 'Course purchased', 'skylearn-billing-pro' ),
		);
	}

	/**
	 * Get webhook logs.
	 *
	 * @since    1.0.0
	 * @param    int      $webhook_id    Webhook ID.
	 * @param    array    $args          Query arguments.
	 * @return   array                   Webhook logs.
	 */
	public function get_webhook_logs( $webhook_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'  => 50,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$logs_table = $wpdb->prefix . 'slbp_webhook_logs';

		$logs = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $logs_table WHERE webhook_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$webhook_id,
			$args['limit'],
			$args['offset']
		) );

		return $logs;
	}

	/**
	 * Test webhook endpoint.
	 *
	 * @since    1.0.0
	 * @param    string    $url        Webhook URL.
	 * @param    string    $secret     Webhook secret.
	 * @return   array|WP_Error        Test result or WP_Error.
	 */
	public function test_webhook( $url, $secret ) {
		$payload = array(
			'event'     => 'test',
			'data'      => array( 'message' => 'This is a test webhook' ),
			'timestamp' => time(),
		);

		$signature = $this->generate_signature( wp_json_encode( $payload ), $secret );

		$headers = array(
			'Content-Type'     => 'application/json',
			'User-Agent'       => 'SkyLearn-Billing-Pro-Webhook/1.0',
			'X-SLBP-Event'     => 'test',
			'X-SLBP-Signature' => $signature,
		);

		$response = wp_remote_post( $url, array(
			'body'        => wp_json_encode( $payload ),
			'headers'     => $headers,
			'timeout'     => $this->timeout,
			'redirection' => 0,
			'sslverify'   => true,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		return array(
			'success'       => $response_code >= 200 && $response_code < 300,
			'response_code' => $response_code,
			'response_body' => $response_body,
		);
	}
}