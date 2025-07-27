<?php
/**
 * Lemon Squeezy Payment Gateway Integration
 *
 * Handles all Lemon Squeezy API interactions including products,
 * checkouts, subscriptions, and webhook processing.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/payment-gateways
 */

/**
 * Lemon Squeezy Payment Gateway Class
 *
 * Provides complete integration with Lemon Squeezy payment platform
 * including product management, checkout creation, subscription handling,
 * and webhook processing.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/payment-gateways
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Lemon_Squeezy extends SLBP_Abstract_Payment_Gateway {

	/**
	 * Gateway identifier.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $gateway_id    Gateway identifier.
	 */
	protected $gateway_id = 'lemon_squeezy';

	/**
	 * Gateway name.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $gateway_name    Gateway display name.
	 */
	protected $gateway_name = 'Lemon Squeezy';

	/**
	 * Gateway version.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    Gateway integration version.
	 */
	protected $version = '1.0.0';

	/**
	 * API base URL.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $api_base_url    Lemon Squeezy API base URL.
	 */
	private $api_base_url = 'https://api.lemonsqueezy.com/v1';

	/**
	 * API key.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $api_key    Lemon Squeezy API key.
	 */
	private $api_key;

	/**
	 * Store ID.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $store_id    Lemon Squeezy store ID.
	 */
	private $store_id;

	/**
	 * Webhook secret.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $webhook_secret    Webhook verification secret.
	 */
	private $webhook_secret;

	/**
	 * Initialize the gateway.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		$this->api_key = $this->get_config( 'api_key' );
		$this->store_id = $this->get_config( 'store_id' );
		$this->webhook_secret = $this->get_config( 'webhook_secret' );

		// Validate required configuration
		if ( empty( $this->api_key ) ) {
			$this->log( 'Lemon Squeezy API key not configured', 'warning' );
		}

		if ( empty( $this->store_id ) ) {
			$this->log( 'Lemon Squeezy store ID not configured', 'warning' );
		}
	}

	/**
	 * Test the gateway connection.
	 *
	 * @since    1.0.0
	 * @return   array    Result array with 'success' boolean and 'message' string.
	 */
	public function test_connection() {
		if ( empty( $this->api_key ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'API key is required.', 'skylearn-billing-pro' ),
			);
		}

		if ( empty( $this->store_id ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Store ID is required.', 'skylearn-billing-pro' ),
			);
		}

		// Test connection by fetching store information
		$response = $this->make_request( $this->api_base_url . '/stores/' . $this->store_id );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					esc_html__( 'Connection failed: %s', 'skylearn-billing-pro' ),
					$response->get_error_message()
				),
			);
		}

		if ( isset( $response['data']['attributes']['name'] ) ) {
			$store_name = $response['data']['attributes']['name'];
			return array(
				'success' => true,
				'message' => sprintf(
					esc_html__( 'Connection successful! Connected to store: %s', 'skylearn-billing-pro' ),
					$store_name
				),
			);
		}

		return array(
			'success' => false,
			'message' => esc_html__( 'Invalid response from Lemon Squeezy API.', 'skylearn-billing-pro' ),
		);
	}

	/**
	 * Connect to the gateway.
	 *
	 * @since    1.0.0
	 * @return   bool    True if connection successful, false otherwise.
	 */
	public function connect() {
		$test_result = $this->test_connection();
		return $test_result['success'];
	}

	/**
	 * Get authentication headers.
	 *
	 * @since    1.0.0
	 * @return   array    Authentication headers.
	 */
	protected function get_auth_headers() {
		$headers = array();
		
		if ( ! empty( $this->api_key ) ) {
			$headers['Authorization'] = 'Bearer ' . $this->api_key;
		}

		return $headers;
	}

	/**
	 * Get available products from the gateway.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Optional arguments for filtering products.
	 * @return   array|WP_Error    Array of products or WP_Error on failure.
	 */
	public function get_products( $args = array() ) {
		$defaults = array(
			'page'     => 1,
			'per_page' => 50,
			'status'   => 'published',
		);

		$args = wp_parse_args( $args, $defaults );

		// Build query parameters
		$query_params = array(
			'filter[store_id]' => $this->store_id,
			'page[number]'     => $args['page'],
			'page[size]'       => $args['per_page'],
		);

		if ( ! empty( $args['status'] ) ) {
			$query_params['filter[status]'] = $args['status'];
		}

		$url = $this->api_base_url . '/products?' . http_build_query( $query_params );
		$response = $this->make_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return $this->format_error( 'Invalid products response from Lemon Squeezy' );
		}

		$products = array();
		foreach ( $response['data'] as $product_data ) {
			$products[] = $this->format_product( $product_data );
		}

		return $products;
	}

	/**
	 * Get a specific product by ID.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Product identifier.
	 * @return   array|WP_Error           Product data or WP_Error on failure.
	 */
	public function get_product( $product_id ) {
		$url = $this->api_base_url . '/products/' . $product_id;
		$response = $this->make_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) ) {
			return $this->format_error( 'Product not found' );
		}

		return $this->format_product( $response['data'] );
	}

	/**
	 * Create a checkout session.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Checkout arguments.
	 * @return   array|WP_Error    Checkout session data or WP_Error on failure.
	 */
	public function create_checkout( $args = array() ) {
		$required_fields = array( 'product_id' );
		foreach ( $required_fields as $field ) {
			if ( empty( $args[ $field ] ) ) {
				return $this->format_error( sprintf( 'Missing required field: %s', $field ) );
			}
		}

		// Get variant ID for the product
		$variant_id = $this->get_product_variant( $args['product_id'] );
		if ( is_wp_error( $variant_id ) ) {
			return $variant_id;
		}

		$checkout_data = array(
			'data' => array(
				'type'       => 'checkouts',
				'attributes' => array(
					'custom_price'    => isset( $args['custom_price'] ) ? intval( $args['custom_price'] * 100 ) : null,
					'product_options' => array(
						'enabled_variants' => array( $variant_id ),
					),
					'checkout_options' => array(
						'embed'           => isset( $args['embed'] ) ? $args['embed'] : false,
						'media'           => isset( $args['media'] ) ? $args['media'] : true,
						'logo'            => isset( $args['logo'] ) ? $args['logo'] : true,
						'desc'            => isset( $args['desc'] ) ? $args['desc'] : true,
						'discount'        => isset( $args['discount'] ) ? $args['discount'] : true,
						'dark'            => isset( $args['dark'] ) ? $args['dark'] : false,
						'subscription_preview' => isset( $args['subscription_preview'] ) ? $args['subscription_preview'] : true,
					),
					'checkout_data' => array(),
					'expires_at'    => isset( $args['expires_at'] ) ? $args['expires_at'] : null,
				),
				'relationships' => array(
					'store' => array(
						'data' => array(
							'type' => 'stores',
							'id'   => $this->store_id,
						),
					),
					'variant' => array(
						'data' => array(
							'type' => 'variants',
							'id'   => $variant_id,
						),
					),
				),
			),
		);

		// Add customer information if provided
		if ( ! empty( $args['customer_email'] ) ) {
			$checkout_data['data']['attributes']['checkout_data']['email'] = $args['customer_email'];
		}

		if ( ! empty( $args['customer_name'] ) ) {
			$checkout_data['data']['attributes']['checkout_data']['name'] = $args['customer_name'];
		}

		// Add custom data for webhook identification
		if ( ! empty( $args['user_id'] ) ) {
			$checkout_data['data']['attributes']['checkout_data']['custom'] = array(
				'user_id' => $args['user_id'],
				'source'  => 'skylearn_billing_pro',
			);
		}

		$url = $this->api_base_url . '/checkouts';
		$response = $this->make_request( $url, array( 'body' => wp_json_encode( $checkout_data ) ), 'POST' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) ) {
			return $this->format_error( 'Invalid checkout response from Lemon Squeezy' );
		}

		return $this->format_checkout( $response['data'] );
	}

	/**
	 * Get product variant ID.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Product ID.
	 * @return   string|WP_Error          Variant ID or WP_Error on failure.
	 */
	private function get_product_variant( $product_id ) {
		$url = $this->api_base_url . '/variants?' . http_build_query( array(
			'filter[product_id]' => $product_id,
		) );

		$response = $this->make_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return $this->format_error( 'No variants found for product' );
		}

		// Return the first variant ID
		return $response['data'][0]['id'];
	}

	/**
	 * Get checkout session details.
	 *
	 * @since    1.0.0
	 * @param    string    $session_id    Checkout session ID.
	 * @return   array|WP_Error           Session data or WP_Error on failure.
	 */
	public function get_checkout_session( $session_id ) {
		$url = $this->api_base_url . '/checkouts/' . $session_id;
		$response = $this->make_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) ) {
			return $this->format_error( 'Checkout session not found' );
		}

		return $this->format_checkout( $response['data'] );
	}

	/**
	 * Cancel a subscription.
	 *
	 * @since    1.0.0
	 * @param    string    $subscription_id    Subscription identifier.
	 * @return   bool|WP_Error                True if successful, WP_Error on failure.
	 */
	public function cancel_subscription( $subscription_id ) {
		$url = $this->api_base_url . '/subscriptions/' . $subscription_id;
		
		$cancel_data = array(
			'data' => array(
				'type'       => 'subscriptions',
				'id'         => $subscription_id,
				'attributes' => array(
					'cancelled' => true,
				),
			),
		);

		$response = $this->make_request( $url, array( 'body' => wp_json_encode( $cancel_data ) ), 'PATCH' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Get subscription details.
	 *
	 * @since    1.0.0
	 * @param    string    $subscription_id    Subscription identifier.
	 * @return   array|WP_Error                Subscription data or WP_Error on failure.
	 */
	public function get_subscription( $subscription_id ) {
		$url = $this->api_base_url . '/subscriptions/' . $subscription_id;
		$response = $this->make_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) ) {
			return $this->format_error( 'Subscription not found' );
		}

		return $this->format_subscription( $response['data'] );
	}

	/**
	 * Update subscription.
	 *
	 * @since    1.0.0
	 * @param    string    $subscription_id    Subscription identifier.
	 * @param    array     $args              Update arguments.
	 * @return   array|WP_Error                Updated subscription data or WP_Error on failure.
	 */
	public function update_subscription( $subscription_id, $args = array() ) {
		$url = $this->api_base_url . '/subscriptions/' . $subscription_id;
		
		$update_data = array(
			'data' => array(
				'type'       => 'subscriptions',
				'id'         => $subscription_id,
				'attributes' => array(),
			),
		);

		// Handle pause/resume
		if ( isset( $args['pause'] ) ) {
			$update_data['data']['attributes']['pause'] = array(
				'mode' => $args['pause'] ? 'void' : null,
			);
		}

		// Handle billing anchor change
		if ( isset( $args['billing_anchor'] ) ) {
			$update_data['data']['attributes']['billing_anchor'] = $args['billing_anchor'];
		}

		$response = $this->make_request( $url, array( 'body' => wp_json_encode( $update_data ) ), 'PATCH' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) ) {
			return $this->format_error( 'Invalid subscription update response' );
		}

		return $this->format_subscription( $response['data'] );
	}

	/**
	 * Get customer subscriptions.
	 *
	 * @since    1.0.0
	 * @param    string    $customer_id    Customer identifier.
	 * @return   array|WP_Error            Array of subscriptions or WP_Error on failure.
	 */
	public function get_customer_subscriptions( $customer_id ) {
		$url = $this->api_base_url . '/subscriptions?' . http_build_query( array(
			'filter[store_id]'    => $this->store_id,
			'filter[customer_id]' => $customer_id,
		) );

		$response = $this->make_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return array(); // Return empty array if no subscriptions
		}

		$subscriptions = array();
		foreach ( $response['data'] as $subscription_data ) {
			$subscriptions[] = $this->format_subscription( $subscription_data );
		}

		return $subscriptions;
	}

	/**
	 * Get customer transactions/invoices.
	 *
	 * @since    1.0.0
	 * @param    string    $customer_id    Customer identifier.
	 * @param    array     $args          Optional arguments for filtering.
	 * @return   array|WP_Error            Array of transactions or WP_Error on failure.
	 */
	public function get_customer_transactions( $customer_id, $args = array() ) {
		$defaults = array(
			'page'     => 1,
			'per_page' => 50,
		);

		$args = wp_parse_args( $args, $defaults );

		$query_params = array(
			'filter[store_id]'    => $this->store_id,
			'filter[customer_id]' => $customer_id,
			'page[number]'        => $args['page'],
			'page[size]'          => $args['per_page'],
		);

		$url = $this->api_base_url . '/orders?' . http_build_query( $query_params );
		$response = $this->make_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return array(); // Return empty array if no transactions
		}

		$transactions = array();
		foreach ( $response['data'] as $order_data ) {
			$transactions[] = $this->format_transaction( $order_data );
		}

		return $transactions;
	}

	/**
	 * Handle webhook request.
	 *
	 * @since    1.0.0
	 * @param    array    $payload    Webhook payload data.
	 * @param    array    $headers    Request headers.
	 * @return   bool                True if webhook processed successfully, false otherwise.
	 */
	public function handle_webhook( $payload, $headers = array() ) {
		// Extract event type and data
		$event_name = $payload['meta']['event_name'] ?? '';
		$event_data = $payload['data'] ?? array();

		$this->log( sprintf( 'Processing webhook event: %s', $event_name ), 'info' );

		switch ( $event_name ) {
			case 'order_created':
				return $this->handle_order_created( $event_data );

			case 'subscription_created':
				return $this->handle_subscription_created( $event_data );

			case 'subscription_updated':
				return $this->handle_subscription_updated( $event_data );

			case 'subscription_cancelled':
				return $this->handle_subscription_cancelled( $event_data );

			case 'subscription_resumed':
				return $this->handle_subscription_resumed( $event_data );

			case 'subscription_expired':
				return $this->handle_subscription_expired( $event_data );

			case 'subscription_paused':
				return $this->handle_subscription_paused( $event_data );

			case 'subscription_unpaused':
				return $this->handle_subscription_unpaused( $event_data );

			case 'subscription_payment_failed':
				return $this->handle_subscription_payment_failed( $event_data );

			case 'subscription_payment_success':
				return $this->handle_subscription_payment_success( $event_data );

			default:
				$this->log( sprintf( 'Unhandled webhook event: %s', $event_name ), 'warning' );
				return false;
		}
	}

	/**
	 * Validate webhook signature.
	 *
	 * @since    1.0.0
	 * @param    string    $payload      Raw webhook payload.
	 * @param    string    $signature    Webhook signature.
	 * @param    array     $headers      Request headers.
	 * @return   bool                    True if signature is valid, false otherwise.
	 */
	public function validate_webhook_signature( $payload, $signature, $headers = array() ) {
		if ( empty( $this->webhook_secret ) ) {
			$this->log( 'Webhook secret not configured', 'warning' );
			return false;
		}

		$expected_signature = hash_hmac( 'sha256', $payload, $this->webhook_secret );

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			$this->log( 'Webhook signature validation failed', 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Handle order created webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $order_data    Order data from webhook.
	 * @return   bool                   True if processed successfully.
	 */
	private function handle_order_created( $order_data ) {
		$order = $this->format_transaction( $order_data );
		
		// Extract user ID from custom data if available
		$user_id = null;
		if ( isset( $order_data['attributes']['custom']['user_id'] ) ) {
			$user_id = intval( $order_data['attributes']['custom']['user_id'] );
		}

		// Store order data
		$this->store_order_data( $order, $user_id );

		// Enroll user in courses if this is a one-time purchase
		if ( $user_id && $order['status'] === 'paid' ) {
			$this->enroll_user_in_courses( $user_id, $order['product_id'] );
		}

		$this->log( sprintf( 'Order created: %s', $order['id'] ), 'info' );
		return true;
	}

	/**
	 * Handle subscription created webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data from webhook.
	 * @return   bool                          True if processed successfully.
	 */
	private function handle_subscription_created( $subscription_data ) {
		$subscription = $this->format_subscription( $subscription_data );
		
		// Store subscription data
		$this->store_subscription_data( $subscription );

		// Extract user ID from custom data if available
		$user_id = $this->get_user_id_from_subscription( $subscription_data );

		// Enroll user in courses
		if ( $user_id && in_array( $subscription['status'], array( 'active', 'trialing' ) ) ) {
			$this->enroll_user_in_courses( $user_id, $subscription['product_id'] );
		}

		$this->log( sprintf( 'Subscription created: %s', $subscription['id'] ), 'info' );
		return true;
	}

	/**
	 * Handle subscription updated webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data from webhook.
	 * @return   bool                          True if processed successfully.
	 */
	private function handle_subscription_updated( $subscription_data ) {
		$subscription = $this->format_subscription( $subscription_data );
		
		// Update stored subscription data
		$this->store_subscription_data( $subscription );

		$this->log( sprintf( 'Subscription updated: %s', $subscription['id'] ), 'info' );
		return true;
	}

	/**
	 * Handle subscription cancelled webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data from webhook.
	 * @return   bool                          True if processed successfully.
	 */
	private function handle_subscription_cancelled( $subscription_data ) {
		$subscription = $this->format_subscription( $subscription_data );
		
		// Update stored subscription data
		$this->store_subscription_data( $subscription );

		// Get user ID and unenroll from courses
		$user_id = $this->get_user_id_from_subscription( $subscription_data );
		if ( $user_id ) {
			$this->unenroll_user_from_courses( $user_id, $subscription['product_id'] );
		}

		$this->log( sprintf( 'Subscription cancelled: %s', $subscription['id'] ), 'info' );
		return true;
	}

	/**
	 * Handle subscription resumed webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data from webhook.
	 * @return   bool                          True if processed successfully.
	 */
	private function handle_subscription_resumed( $subscription_data ) {
		$subscription = $this->format_subscription( $subscription_data );
		
		// Update stored subscription data
		$this->store_subscription_data( $subscription );

		// Get user ID and re-enroll in courses
		$user_id = $this->get_user_id_from_subscription( $subscription_data );
		if ( $user_id ) {
			$this->enroll_user_in_courses( $user_id, $subscription['product_id'] );
		}

		$this->log( sprintf( 'Subscription resumed: %s', $subscription['id'] ), 'info' );
		return true;
	}

	/**
	 * Handle subscription expired webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data from webhook.
	 * @return   bool                          True if processed successfully.
	 */
	private function handle_subscription_expired( $subscription_data ) {
		$subscription = $this->format_subscription( $subscription_data );
		
		// Update stored subscription data
		$this->store_subscription_data( $subscription );

		// Get user ID and unenroll from courses
		$user_id = $this->get_user_id_from_subscription( $subscription_data );
		if ( $user_id ) {
			$this->unenroll_user_from_courses( $user_id, $subscription['product_id'] );
		}

		$this->log( sprintf( 'Subscription expired: %s', $subscription['id'] ), 'info' );
		return true;
	}

	/**
	 * Handle subscription paused webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data from webhook.
	 * @return   bool                          True if processed successfully.
	 */
	private function handle_subscription_paused( $subscription_data ) {
		$subscription = $this->format_subscription( $subscription_data );
		
		// Update stored subscription data
		$this->store_subscription_data( $subscription );

		// Optionally unenroll user from courses when paused
		$unenroll_on_pause = apply_filters( 'slbp_unenroll_on_subscription_pause', false );
		if ( $unenroll_on_pause ) {
			$user_id = $this->get_user_id_from_subscription( $subscription_data );
			if ( $user_id ) {
				$this->unenroll_user_from_courses( $user_id, $subscription['product_id'] );
			}
		}

		$this->log( sprintf( 'Subscription paused: %s', $subscription['id'] ), 'info' );
		return true;
	}

	/**
	 * Handle subscription unpaused webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data from webhook.
	 * @return   bool                          True if processed successfully.
	 */
	private function handle_subscription_unpaused( $subscription_data ) {
		$subscription = $this->format_subscription( $subscription_data );
		
		// Update stored subscription data
		$this->store_subscription_data( $subscription );

		// Re-enroll user in courses when unpaused
		$user_id = $this->get_user_id_from_subscription( $subscription_data );
		if ( $user_id ) {
			$this->enroll_user_in_courses( $user_id, $subscription['product_id'] );
		}

		$this->log( sprintf( 'Subscription unpaused: %s', $subscription['id'] ), 'info' );
		return true;
	}

	/**
	 * Handle subscription payment failed webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data from webhook.
	 * @return   bool                          True if processed successfully.
	 */
	private function handle_subscription_payment_failed( $subscription_data ) {
		$subscription = $this->format_subscription( $subscription_data );
		
		// Update stored subscription data
		$this->store_subscription_data( $subscription );

		// Send notification about payment failure
		$user_id = $this->get_user_id_from_subscription( $subscription_data );
		if ( $user_id ) {
			do_action( 'slbp_subscription_payment_failed', $user_id, $subscription );
		}

		$this->log( sprintf( 'Subscription payment failed: %s', $subscription['id'] ), 'warning' );
		return true;
	}

	/**
	 * Handle subscription payment success webhook.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data from webhook.
	 * @return   bool                          True if processed successfully.
	 */
	private function handle_subscription_payment_success( $subscription_data ) {
		$subscription = $this->format_subscription( $subscription_data );
		
		// Update stored subscription data
		$this->store_subscription_data( $subscription );

		// Ensure user is enrolled in courses
		$user_id = $this->get_user_id_from_subscription( $subscription_data );
		if ( $user_id ) {
			$this->enroll_user_in_courses( $user_id, $subscription['product_id'] );
			do_action( 'slbp_subscription_payment_success', $user_id, $subscription );
		}

		$this->log( sprintf( 'Subscription payment successful: %s', $subscription['id'] ), 'info' );
		return true;
	}

	/**
	 * Format product data.
	 *
	 * @since    1.0.0
	 * @param    array    $product_data    Raw product data from API.
	 * @return   array                     Formatted product data.
	 */
	private function format_product( $product_data ) {
		$attributes = $product_data['attributes'] ?? array();
		
		return array(
			'id'          => $product_data['id'] ?? '',
			'name'        => $attributes['name'] ?? '',
			'description' => $attributes['description'] ?? '',
			'price'       => isset( $attributes['price'] ) ? floatval( $attributes['price'] ) / 100 : 0,
			'currency'    => 'USD', // Lemon Squeezy uses USD
			'type'        => $this->determine_product_type( $attributes ),
			'status'      => $attributes['status'] ?? 'draft',
			'created_at'  => $attributes['created_at'] ?? '',
			'updated_at'  => $attributes['updated_at'] ?? '',
			'image_url'   => $attributes['thumb_url'] ?? '',
			'buy_now_url' => $attributes['buy_now_url'] ?? '',
		);
	}

	/**
	 * Format checkout data.
	 *
	 * @since    1.0.0
	 * @param    array    $checkout_data    Raw checkout data from API.
	 * @return   array                      Formatted checkout data.
	 */
	private function format_checkout( $checkout_data ) {
		$attributes = $checkout_data['attributes'] ?? array();
		
		return array(
			'id'         => $checkout_data['id'] ?? '',
			'url'        => $attributes['url'] ?? '',
			'embed_url'  => $attributes['embed_url'] ?? '',
			'expires_at' => $attributes['expires_at'] ?? '',
			'created_at' => $attributes['created_at'] ?? '',
			'test_mode'  => $attributes['test_mode'] ?? false,
		);
	}

	/**
	 * Format subscription data.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Raw subscription data from API.
	 * @return   array                          Formatted subscription data.
	 */
	private function format_subscription( $subscription_data ) {
		$attributes = $subscription_data['attributes'] ?? array();
		
		return array(
			'id'                   => $subscription_data['id'] ?? '',
			'customer_id'          => $attributes['customer_id'] ?? '',
			'order_id'             => $attributes['order_id'] ?? '',
			'product_id'           => $attributes['product_id'] ?? '',
			'variant_id'           => $attributes['variant_id'] ?? '',
			'status'               => $attributes['status'] ?? '',
			'card_brand'           => $attributes['card_brand'] ?? '',
			'card_last_four'       => $attributes['card_last_four'] ?? '',
			'pause'                => $attributes['pause'] ?? null,
			'cancelled'            => $attributes['cancelled'] ?? false,
			'trial_ends_at'        => $attributes['trial_ends_at'] ?? '',
			'billing_anchor'       => $attributes['billing_anchor'] ?? '',
			'renews_at'            => $attributes['renews_at'] ?? '',
			'ends_at'              => $attributes['ends_at'] ?? '',
			'created_at'           => $attributes['created_at'] ?? '',
			'updated_at'           => $attributes['updated_at'] ?? '',
			'test_mode'            => $attributes['test_mode'] ?? false,
		);
	}

	/**
	 * Format transaction data.
	 *
	 * @since    1.0.0
	 * @param    array    $order_data    Raw order data from API.
	 * @return   array                   Formatted transaction data.
	 */
	private function format_transaction( $order_data ) {
		$attributes = $order_data['attributes'] ?? array();
		
		return array(
			'id'                => $order_data['id'] ?? '',
			'customer_id'       => $attributes['customer_id'] ?? '',
			'product_id'        => $attributes['first_order_item']['product_id'] ?? '',
			'variant_id'        => $attributes['first_order_item']['variant_id'] ?? '',
			'order_number'      => $attributes['order_number'] ?? '',
			'status'            => $attributes['status'] ?? '',
			'currency'          => $attributes['currency'] ?? 'USD',
			'subtotal'          => isset( $attributes['subtotal'] ) ? floatval( $attributes['subtotal'] ) / 100 : 0,
			'discount_total'    => isset( $attributes['discount_total'] ) ? floatval( $attributes['discount_total'] ) / 100 : 0,
			'tax'               => isset( $attributes['tax'] ) ? floatval( $attributes['tax'] ) / 100 : 0,
			'total'             => isset( $attributes['total'] ) ? floatval( $attributes['total'] ) / 100 : 0,
			'refunded'          => $attributes['refunded'] ?? false,
			'refunded_amount'   => isset( $attributes['refunded_amount'] ) ? floatval( $attributes['refunded_amount'] ) / 100 : 0,
			'customer_email'    => $attributes['user_email'] ?? '',
			'customer_name'     => $attributes['user_name'] ?? '',
			'created_at'        => $attributes['created_at'] ?? '',
			'updated_at'        => $attributes['updated_at'] ?? '',
			'test_mode'         => $attributes['test_mode'] ?? false,
		);
	}

	/**
	 * Determine product type from attributes.
	 *
	 * @since    1.0.0
	 * @param    array    $attributes    Product attributes.
	 * @return   string                  Product type.
	 */
	private function determine_product_type( $attributes ) {
		// Check if product has variants with recurring billing
		if ( isset( $attributes['variants'] ) && is_array( $attributes['variants'] ) ) {
			foreach ( $attributes['variants'] as $variant ) {
				if ( isset( $variant['attributes']['is_subscription'] ) && $variant['attributes']['is_subscription'] ) {
					return 'subscription';
				}
			}
		}

		return 'one_time';
	}

	/**
	 * Get user ID from subscription data.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription_data    Subscription data.
	 * @return   int|null                       User ID or null if not found.
	 */
	private function get_user_id_from_subscription( $subscription_data ) {
		// Try to get from custom data
		if ( isset( $subscription_data['attributes']['order']['custom']['user_id'] ) ) {
			return intval( $subscription_data['attributes']['order']['custom']['user_id'] );
		}

		// Try to get from stored subscription data
		$subscription_id = $subscription_data['id'] ?? '';
		if ( $subscription_id ) {
			$user_id = get_option( 'slbp_subscription_user_' . $subscription_id );
			if ( $user_id ) {
				return intval( $user_id );
			}
		}

		return null;
	}

	/**
	 * Store order data.
	 *
	 * @since    1.0.0
	 * @param    array    $order     Order data.
	 * @param    int      $user_id   WordPress user ID.
	 */
	private function store_order_data( $order, $user_id = null ) {
		// Store order data as WordPress option
		update_option( 'slbp_order_' . $order['id'], $order );

		// Link user to order if provided
		if ( $user_id ) {
			update_option( 'slbp_order_user_' . $order['id'], $user_id );
		}

		// Store user orders list
		if ( $user_id ) {
			$user_orders = get_user_meta( $user_id, 'slbp_orders', true ) ?: array();
			if ( ! in_array( $order['id'], $user_orders ) ) {
				$user_orders[] = $order['id'];
				update_user_meta( $user_id, 'slbp_orders', $user_orders );
			}
		}
	}

	/**
	 * Store subscription data.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription    Subscription data.
	 */
	private function store_subscription_data( $subscription ) {
		// Store subscription data as WordPress option
		update_option( 'slbp_subscription_' . $subscription['id'], $subscription );

		// Try to link user to subscription
		$user_id = $this->get_user_id_from_subscription( array( 'id' => $subscription['id'] ) );
		if ( $user_id ) {
			update_option( 'slbp_subscription_user_' . $subscription['id'], $user_id );

			// Store user subscriptions list
			$user_subscriptions = get_user_meta( $user_id, 'slbp_subscriptions', true ) ?: array();
			if ( ! in_array( $subscription['id'], $user_subscriptions ) ) {
				$user_subscriptions[] = $subscription['id'];
				update_user_meta( $user_id, 'slbp_subscriptions', $user_subscriptions );
			}
		}
	}

	/**
	 * Enroll user in courses based on product mapping.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id      WordPress user ID.
	 * @param    string    $product_id   Product ID from payment gateway.
	 */
	private function enroll_user_in_courses( $user_id, $product_id ) {
		// Get product mappings from settings
		$product_settings = get_option( 'slbp_product_settings', array() );
		$mappings = $product_settings['product_mappings'] ?? array();

		foreach ( $mappings as $mapping ) {
			if ( $mapping['product_id'] === $product_id && ! empty( $mapping['course_id'] ) ) {
				$course_id = intval( $mapping['course_id'] );
				
				// Check if LearnDash is available
				if ( function_exists( 'ld_update_course_access' ) ) {
					ld_update_course_access( $user_id, $course_id );
					$this->log( sprintf( 'User %d enrolled in course %d for product %s', $user_id, $course_id, $product_id ), 'info' );
				} else {
					$this->log( 'LearnDash not available for course enrollment', 'warning' );
				}

				// Allow other plugins to hook into enrollment
				do_action( 'slbp_user_enrolled', $user_id, $course_id, $product_id );
			}
		}
	}

	/**
	 * Unenroll user from courses based on product mapping.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id      WordPress user ID.
	 * @param    string    $product_id   Product ID from payment gateway.
	 */
	private function unenroll_user_from_courses( $user_id, $product_id ) {
		// Get product mappings from settings
		$product_settings = get_option( 'slbp_product_settings', array() );
		$mappings = $product_settings['product_mappings'] ?? array();

		foreach ( $mappings as $mapping ) {
			if ( $mapping['product_id'] === $product_id && ! empty( $mapping['course_id'] ) ) {
				$course_id = intval( $mapping['course_id'] );
				
				// Check if LearnDash is available
				if ( function_exists( 'ld_update_course_access' ) ) {
					ld_update_course_access( $user_id, $course_id, $remove = true );
					$this->log( sprintf( 'User %d unenrolled from course %d for product %s', $user_id, $course_id, $product_id ), 'info' );
				} else {
					$this->log( 'LearnDash not available for course unenrollment', 'warning' );
				}

				// Allow other plugins to hook into unenrollment
				do_action( 'slbp_user_unenrolled', $user_id, $course_id, $product_id );
			}
		}
	}
}