<?php
/**
 * Zapier integration for SkyLearn Billing Pro.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/integrations
 */

/**
 * Zapier integration class.
 *
 * Handles Zapier webhook integration for triggering Zaps.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/integrations
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Zapier_Integration extends SLBP_Abstract_Integration {

	/**
	 * Get the integration ID.
	 *
	 * @since    1.0.0
	 * @return   string   The integration ID.
	 */
	protected function get_integration_id() {
		return 'zapier';
	}

	/**
	 * Handle integration events.
	 *
	 * @since    1.0.0
	 * @param    string $event_name The event name.
	 * @param    int    $user_id    The user ID.
	 * @param    array  $data       Event data.
	 */
	public function handle_event( $event_name, $user_id, $data ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$enabled_events = $this->get_setting( 'events', array() );
		$event_key = str_replace( 'slbp_', '', $event_name );
		
		if ( ! in_array( $event_key, $enabled_events ) ) {
			return;
		}

		$webhook_url = $this->get_setting( 'webhook_url' );
		
		if ( empty( $webhook_url ) ) {
			$this->log( 'No webhook URL configured for Zapier', 'warning' );
			return;
		}

		$payload = $this->prepare_webhook_payload( $event_name, $user_id, $data );
		$result = $this->send_webhook( $webhook_url, $payload );
		
		if ( is_wp_error( $result ) ) {
			$this->log( sprintf( 'Failed to send Zapier webhook: %s', $result->get_error_message() ), 'error' );
		} else {
			$this->log( sprintf( 'Successfully sent webhook for event: %s', $event_name ), 'info' );
		}
	}

	/**
	 * Prepare webhook payload.
	 *
	 * @since    1.0.0
	 * @param    string $event_name The event name.
	 * @param    int    $user_id    The user ID.
	 * @param    array  $data       Event data.
	 * @return   array              Webhook payload.
	 */
	private function prepare_webhook_payload( $event_name, $user_id, $data ) {
		$user_data = $this->prepare_user_data( $user_id );
		
		$payload = array(
			'event'     => str_replace( 'slbp_', '', $event_name ),
			'timestamp' => current_time( 'c' ),
			'site_url'  => home_url(),
			'site_name' => get_bloginfo( 'name' ),
			'user'      => $user_data,
			'data'      => $data,
		);

		// Add course information if available
		if ( ! empty( $data['course_id'] ) ) {
			$course = get_post( $data['course_id'] );
			if ( $course ) {
				$payload['course'] = array(
					'id'    => $course->ID,
					'title' => $course->post_title,
					'url'   => get_permalink( $course->ID ),
				);
			}
		}

		// Add product information if available
		if ( ! empty( $data['product_id'] ) ) {
			$payload['product'] = array(
				'id'   => $data['product_id'],
				'name' => $data['product_name'] ?? '',
			);
		}

		// Add payment information
		if ( ! empty( $data['amount'] ) ) {
			$payload['payment'] = array(
				'amount'   => $data['amount'],
				'currency' => $data['currency'] ?? 'USD',
				'gateway'  => $data['payment_gateway'] ?? '',
			);
		}

		// Add subscription information
		if ( ! empty( $data['subscription_id'] ) ) {
			$payload['subscription'] = array(
				'id'     => $data['subscription_id'],
				'status' => $data['subscription_status'] ?? '',
				'plan'   => $data['plan_name'] ?? '',
			);
		}

		/**
		 * Filter the Zapier webhook payload.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $payload    The webhook payload.
		 * @param string $event_name The event name.
		 * @param int    $user_id    The user ID.
		 * @param array  $data       Event data.
		 */
		return apply_filters( 'slbp_zapier_webhook_payload', $payload, $event_name, $user_id, $data );
	}

	/**
	 * Send webhook to Zapier.
	 *
	 * @since    1.0.0
	 * @param    string $webhook_url The webhook URL.
	 * @param    array  $payload     The payload to send.
	 * @return   bool|WP_Error       True on success, WP_Error on failure.
	 */
	private function send_webhook( $webhook_url, $payload ) {
		$response = $this->make_request( $webhook_url, array(
			'method' => 'POST',
			'body'   => wp_json_encode( $payload ),
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Zapier-Source' => 'SkyLearn Billing Pro',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Zapier typically returns 200 for successful webhooks
		if ( $response['code'] >= 200 && $response['code'] < 300 ) {
			return true;
		}

		return new WP_Error( 'webhook_failed', sprintf( 'Webhook failed with status %d', $response['code'] ) );
	}

	/**
	 * Test the integration connection.
	 *
	 * @since    1.0.0
	 * @return   bool|WP_Error   True if connection successful, WP_Error on failure.
	 */
	public function test_connection() {
		$webhook_url = $this->get_setting( 'webhook_url' );
		
		if ( empty( $webhook_url ) ) {
			return new WP_Error( 'no_webhook_url', __( 'No webhook URL provided.', 'skylearn-billing-pro' ) );
		}

		// Validate URL format
		if ( ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', __( 'Invalid webhook URL format.', 'skylearn-billing-pro' ) );
		}

		// Send test payload
		$test_payload = array(
			'event'     => 'test',
			'timestamp' => current_time( 'c' ),
			'site_url'  => home_url(),
			'site_name' => get_bloginfo( 'name' ),
			'message'   => 'This is a test webhook from SkyLearn Billing Pro',
		);

		$response = $this->send_webhook( $webhook_url, $test_payload );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Handle webhook (for future use - Zapier to WordPress).
	 *
	 * @since    1.0.0
	 * @param    array           $data    Webhook data.
	 * @param    WP_REST_Request $request Request object.
	 * @return   bool|WP_Error            True on success, WP_Error on failure.
	 */
	public function handle_webhook( $data, $request ) {
		// Handle incoming webhooks from Zapier (for bidirectional integration)
		$this->log( 'Zapier webhook received', 'info', $data );
		
		$action = $data['action'] ?? '';
		
		switch ( $action ) {
			case 'create_user':
				return $this->handle_create_user( $data );
			case 'enroll_user':
				return $this->handle_enroll_user( $data );
			case 'update_subscription':
				return $this->handle_update_subscription( $data );
			default:
				/**
				 * Allow custom handling of Zapier webhook actions.
				 *
				 * @since 1.0.0
				 *
				 * @param mixed $result  The result of handling the action.
				 * @param string $action The webhook action.
				 * @param array  $data   Webhook data.
				 */
				return apply_filters( 'slbp_zapier_handle_webhook_action', false, $action, $data );
		}
	}

	/**
	 * Handle create user webhook.
	 *
	 * @since    1.0.0
	 * @param    array $data Webhook data.
	 * @return   bool|WP_Error True on success, WP_Error on failure.
	 */
	private function handle_create_user( $data ) {
		$email = sanitize_email( $data['email'] ?? '' );
		$username = sanitize_user( $data['username'] ?? $email );
		$first_name = sanitize_text_field( $data['first_name'] ?? '' );
		$last_name = sanitize_text_field( $data['last_name'] ?? '' );
		
		if ( empty( $email ) ) {
			return new WP_Error( 'missing_email', __( 'Email is required.', 'skylearn-billing-pro' ) );
		}

		// Check if user already exists
		if ( email_exists( $email ) ) {
			return new WP_Error( 'user_exists', __( 'User with this email already exists.', 'skylearn-billing-pro' ) );
		}

		$user_id = wp_create_user( $username, wp_generate_password(), $email );
		
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Update user meta
		wp_update_user( array(
			'ID'         => $user_id,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		) );

		$this->log( sprintf( 'Created user via Zapier webhook: %s', $email ), 'info' );
		
		return true;
	}

	/**
	 * Handle enroll user webhook.
	 *
	 * @since    1.0.0
	 * @param    array $data Webhook data.
	 * @return   bool|WP_Error True on success, WP_Error on failure.
	 */
	private function handle_enroll_user( $data ) {
		$email = sanitize_email( $data['email'] ?? '' );
		$course_id = intval( $data['course_id'] ?? 0 );
		
		if ( empty( $email ) ) {
			return new WP_Error( 'missing_email', __( 'Email is required.', 'skylearn-billing-pro' ) );
		}

		if ( empty( $course_id ) ) {
			return new WP_Error( 'missing_course_id', __( 'Course ID is required.', 'skylearn-billing-pro' ) );
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return new WP_Error( 'user_not_found', __( 'User not found.', 'skylearn-billing-pro' ) );
		}

		// Use LearnDash enrollment if available
		if ( function_exists( 'ld_update_course_access' ) ) {
			ld_update_course_access( $user->ID, $course_id, true );
			$this->log( sprintf( 'Enrolled user %s in course %d via Zapier', $email, $course_id ), 'info' );
			return true;
		}

		return new WP_Error( 'learndash_not_available', __( 'LearnDash enrollment function not available.', 'skylearn-billing-pro' ) );
	}

	/**
	 * Handle update subscription webhook.
	 *
	 * @since    1.0.0
	 * @param    array $data Webhook data.
	 * @return   bool|WP_Error True on success, WP_Error on failure.
	 */
	private function handle_update_subscription( $data ) {
		$subscription_id = sanitize_text_field( $data['subscription_id'] ?? '' );
		$status = sanitize_text_field( $data['status'] ?? '' );
		
		if ( empty( $subscription_id ) ) {
			return new WP_Error( 'missing_subscription_id', __( 'Subscription ID is required.', 'skylearn-billing-pro' ) );
		}

		// Update subscription in database
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'slbp_subscriptions';
		$result = $wpdb->update(
			$table_name,
			array( 'status' => $status ),
			array( 'subscription_id' => $subscription_id ),
			array( '%s' ),
			array( '%s' )
		);

		if ( $result !== false ) {
			$this->log( sprintf( 'Updated subscription %s status to %s via Zapier', $subscription_id, $status ), 'info' );
			return true;
		}

		return new WP_Error( 'update_failed', __( 'Failed to update subscription.', 'skylearn-billing-pro' ) );
	}

	/**
	 * Get available webhook events.
	 *
	 * @since    1.0.0
	 * @return   array Available events.
	 */
	public function get_available_events() {
		return array(
			'payment_success'       => __( 'Payment Success', 'skylearn-billing-pro' ),
			'payment_failed'        => __( 'Payment Failed', 'skylearn-billing-pro' ),
			'subscription_created'  => __( 'Subscription Created', 'skylearn-billing-pro' ),
			'subscription_renewed'  => __( 'Subscription Renewed', 'skylearn-billing-pro' ),
			'subscription_cancelled' => __( 'Subscription Cancelled', 'skylearn-billing-pro' ),
			'enrollment_created'    => __( 'Course Enrollment', 'skylearn-billing-pro' ),
			'refund_processed'      => __( 'Refund Processed', 'skylearn-billing-pro' ),
		);
	}
}