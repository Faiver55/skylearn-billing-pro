<?php
/**
 * Mailchimp integration for SkyLearn Billing Pro.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/integrations
 */

/**
 * Mailchimp integration class.
 *
 * Handles Mailchimp API integration for adding subscribers.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/integrations
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Mailchimp_Integration extends SLBP_Abstract_Integration {

	/**
	 * Mailchimp API base URL.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string   $api_base_url   Mailchimp API base URL.
	 */
	private $api_base_url;

	/**
	 * Initialize the Mailchimp integration.
	 *
	 * @since    1.0.0
	 * @param    array  $settings Integration settings.
	 * @param    array  $config   Integration configuration.
	 */
	public function __construct( $settings, $config ) {
		parent::__construct( $settings, $config );
		$this->setup_api_url();
	}

	/**
	 * Get the integration ID.
	 *
	 * @since    1.0.0
	 * @return   string   The integration ID.
	 */
	protected function get_integration_id() {
		return 'mailchimp';
	}

	/**
	 * Setup API URL based on API key.
	 *
	 * @since    1.0.0
	 */
	private function setup_api_url() {
		$api_key = $this->get_setting( 'api_key' );
		
		if ( empty( $api_key ) ) {
			return;
		}

		// Extract datacenter from API key
		$parts = explode( '-', $api_key );
		$datacenter = end( $parts );
		
		$this->api_base_url = sprintf( 'https://%s.api.mailchimp.com/3.0', $datacenter );
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

		// Events that should trigger Mailchimp subscription
		$trigger_events = array(
			'slbp_payment_success',
			'slbp_subscription_created',
			'slbp_enrollment_created',
		);

		if ( ! in_array( $event_name, $trigger_events ) ) {
			return;
		}

		$user_data = $this->prepare_user_data( $user_id );
		if ( empty( $user_data['email'] ) ) {
			$this->log( 'No email found for user ID: ' . $user_id, 'warning' );
			return;
		}

		$result = $this->add_subscriber( $user_data, $data );
		
		if ( is_wp_error( $result ) ) {
			$this->log( sprintf( 'Failed to add subscriber: %s', $result->get_error_message() ), 'error' );
		} else {
			$this->log( sprintf( 'Successfully added subscriber: %s', $user_data['email'] ), 'info' );
		}
	}

	/**
	 * Add subscriber to Mailchimp list.
	 *
	 * @since    1.0.0
	 * @param    array $user_data User data.
	 * @param    array $event_data Event data.
	 * @return   bool|WP_Error    True on success, WP_Error on failure.
	 */
	public function add_subscriber( $user_data, $event_data = array() ) {
		$list_id = $this->get_setting( 'list_id' );
		
		if ( empty( $list_id ) ) {
			return new WP_Error( 'no_list_id', __( 'No Mailchimp list ID configured.', 'skylearn-billing-pro' ) );
		}

		$subscriber_data = array(
			'email_address' => $user_data['email'],
			'status'        => $this->get_setting( 'double_optin', true ) ? 'pending' : 'subscribed',
			'merge_fields'  => array(
				'FNAME' => $user_data['first_name'] ?: $user_data['display_name'],
				'LNAME' => $user_data['last_name'],
			),
			'tags'          => $this->get_tags_for_event( $event_data ),
		);

		// Add custom merge fields if configured
		$custom_fields = $this->get_setting( 'custom_fields', array() );
		foreach ( $custom_fields as $mc_field => $wp_field ) {
			if ( isset( $user_data[ $wp_field ] ) ) {
				$subscriber_data['merge_fields'][ $mc_field ] = $user_data[ $wp_field ];
			}
		}

		$url = sprintf( '%s/lists/%s/members', $this->api_base_url, $list_id );
		
		$response = $this->make_request( $url, array(
			'method' => 'POST',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->get_setting( 'api_key' ) ),
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode( $subscriber_data ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( $response['body'], true );
		
		// Handle "Member Exists" error - try to update instead
		if ( isset( $body['title'] ) && $body['title'] === 'Member Exists' ) {
			return $this->update_subscriber( $user_data['email'], $subscriber_data, $list_id );
		}

		if ( isset( $body['status'] ) && in_array( $body['status'], array( 'subscribed', 'pending' ) ) ) {
			return true;
		}

		return new WP_Error( 'mailchimp_error', $body['detail'] ?? __( 'Unknown Mailchimp error.', 'skylearn-billing-pro' ) );
	}

	/**
	 * Update existing subscriber.
	 *
	 * @since    1.0.0
	 * @param    string $email         Subscriber email.
	 * @param    array  $subscriber_data Subscriber data.
	 * @param    string $list_id       List ID.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	private function update_subscriber( $email, $subscriber_data, $list_id ) {
		$subscriber_hash = md5( strtolower( $email ) );
		$url = sprintf( '%s/lists/%s/members/%s', $this->api_base_url, $list_id, $subscriber_hash );
		
		// Don't change status for existing subscribers
		unset( $subscriber_data['status'] );
		
		$response = $this->make_request( $url, array(
			'method' => 'PATCH',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->get_setting( 'api_key' ) ),
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode( $subscriber_data ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( $response['body'], true );
		
		if ( isset( $body['email_address'] ) ) {
			return true;
		}

		return new WP_Error( 'mailchimp_update_error', $body['detail'] ?? __( 'Failed to update subscriber.', 'skylearn-billing-pro' ) );
	}

	/**
	 * Get tags for event data.
	 *
	 * @since    1.0.0
	 * @param    array $event_data Event data.
	 * @return   array             Tags array.
	 */
	private function get_tags_for_event( $event_data ) {
		$tags = array( 'SkyLearn Billing' );
		
		if ( ! empty( $event_data['course_name'] ) ) {
			$tags[] = 'Course: ' . $event_data['course_name'];
		}
		
		if ( ! empty( $event_data['product_name'] ) ) {
			$tags[] = 'Product: ' . $event_data['product_name'];
		}

		return $tags;
	}

	/**
	 * Test the integration connection.
	 *
	 * @since    1.0.0
	 * @return   bool|WP_Error   True if connection successful, WP_Error on failure.
	 */
	public function test_connection() {
		$api_key = $this->get_setting( 'api_key' );
		
		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', __( 'No API key provided.', 'skylearn-billing-pro' ) );
		}

		if ( empty( $this->api_base_url ) ) {
			return new WP_Error( 'invalid_api_key', __( 'Invalid API key format.', 'skylearn-billing-pro' ) );
		}

		$url = $this->api_base_url . '/ping';
		
		$response = $this->make_request( $url, array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( $response['body'], true );
		
		if ( isset( $body['health_status'] ) && $body['health_status'] === 'Everything\'s Chimpy!' ) {
			return true;
		}

		return new WP_Error( 'connection_failed', __( 'Failed to connect to Mailchimp.', 'skylearn-billing-pro' ) );
	}

	/**
	 * Get Mailchimp lists.
	 *
	 * @since    1.0.0
	 * @return   array|WP_Error   Lists array or error.
	 */
	public function get_lists() {
		$url = $this->api_base_url . '/lists';
		
		$response = $this->make_request( $url, array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->get_setting( 'api_key' ) ),
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( $response['body'], true );
		
		if ( isset( $body['lists'] ) ) {
			$lists = array();
			foreach ( $body['lists'] as $list ) {
				$lists[ $list['id'] ] = $list['name'];
			}
			return $lists;
		}

		return new WP_Error( 'no_lists', __( 'No lists found.', 'skylearn-billing-pro' ) );
	}

	/**
	 * Handle webhook (for future use).
	 *
	 * @since    1.0.0
	 * @param    array           $data    Webhook data.
	 * @param    WP_REST_Request $request Request object.
	 * @return   bool|WP_Error            True on success, WP_Error on failure.
	 */
	public function handle_webhook( $data, $request ) {
		// Handle Mailchimp webhooks (unsubscribes, profile updates, etc.)
		$this->log( 'Mailchimp webhook received', 'info', $data );
		
		// Process webhook based on type
		$type = $data['type'] ?? '';
		
		switch ( $type ) {
			case 'unsubscribe':
				$this->handle_unsubscribe( $data );
				break;
			case 'profile':
				$this->handle_profile_update( $data );
				break;
			default:
				$this->log( 'Unknown webhook type: ' . $type, 'warning' );
		}
		
		return true;
	}

	/**
	 * Handle unsubscribe webhook.
	 *
	 * @since    1.0.0
	 * @param    array $data Webhook data.
	 */
	private function handle_unsubscribe( $data ) {
		$email = $data['data']['email'] ?? '';
		
		if ( empty( $email ) ) {
			return;
		}

		// Update user meta to track unsubscribe
		$user = get_user_by( 'email', $email );
		if ( $user ) {
			update_user_meta( $user->ID, 'slbp_mailchimp_unsubscribed', true );
			$this->log( sprintf( 'User %s unsubscribed from Mailchimp', $email ), 'info' );
		}
	}

	/**
	 * Handle profile update webhook.
	 *
	 * @since    1.0.0
	 * @param    array $data Webhook data.
	 */
	private function handle_profile_update( $data ) {
		// Handle profile updates from Mailchimp
		$this->log( 'Profile update webhook processed', 'info' );
	}
}