<?php
/**
 * Abstract Payment Gateway Class
 *
 * Defines the interface and common functionality for all payment gateways.
 * All payment gateway implementations must extend this abstract class.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/payment-gateways
 */

/**
 * Abstract Payment Gateway Class
 *
 * This abstract class defines the required methods and common functionality
 * that all payment gateway integrations must implement.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/payment-gateways
 * @author     Skyian LLC <contact@skyianllc.com>
 */
abstract class SLBP_Abstract_Payment_Gateway {

	/**
	 * Gateway identifier.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $gateway_id    Unique identifier for this gateway.
	 */
	protected $gateway_id;

	/**
	 * Gateway name.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $gateway_name    Human-readable name for this gateway.
	 */
	protected $gateway_name;

	/**
	 * Gateway version.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    Gateway integration version.
	 */
	protected $version;

	/**
	 * Test mode flag.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      bool    $test_mode    Whether the gateway is in test mode.
	 */
	protected $test_mode;

	/**
	 * Gateway configuration.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $config    Gateway configuration options.
	 */
	protected $config;

	/**
	 * Logger instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SLBP_Logger    $logger    Logger instance for this gateway.
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    array    $config    Gateway configuration.
	 */
	public function __construct( $config = array() ) {
		$this->config = $config;
		$this->test_mode = isset( $config['test_mode'] ) ? (bool) $config['test_mode'] : false;
		
		// Initialize logger if available
		if ( class_exists( 'SLBP_Logger' ) ) {
			$this->logger = new SLBP_Logger( $this->gateway_id );
		}

		$this->init();
	}

	/**
	 * Initialize the gateway.
	 *
	 * Called after construction to allow gateway-specific initialization.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		// Override in child classes for gateway-specific initialization
	}

	/**
	 * Get gateway ID.
	 *
	 * @since    1.0.0
	 * @return   string    Gateway identifier.
	 */
	public function get_gateway_id() {
		return $this->gateway_id;
	}

	/**
	 * Get gateway name.
	 *
	 * @since    1.0.0
	 * @return   string    Gateway name.
	 */
	public function get_gateway_name() {
		return $this->gateway_name;
	}

	/**
	 * Get gateway version.
	 *
	 * @since    1.0.0
	 * @return   string    Gateway version.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Check if gateway is in test mode.
	 *
	 * @since    1.0.0
	 * @return   bool    True if in test mode, false otherwise.
	 */
	public function is_test_mode() {
		return $this->test_mode;
	}

	/**
	 * Get gateway configuration.
	 *
	 * @since    1.0.0
	 * @param    string    $key       Configuration key (optional).
	 * @param    mixed     $default   Default value if key not found.
	 * @return   mixed                Configuration value or array.
	 */
	public function get_config( $key = null, $default = null ) {
		if ( $key === null ) {
			return $this->config;
		}
		return isset( $this->config[ $key ] ) ? $this->config[ $key ] : $default;
	}

	/**
	 * Update gateway configuration.
	 *
	 * @since    1.0.0
	 * @param    array    $config    New configuration values.
	 */
	public function update_config( $config ) {
		$this->config = array_merge( $this->config, $config );
		$this->test_mode = isset( $this->config['test_mode'] ) ? (bool) $this->config['test_mode'] : false;
	}

	/**
	 * Log a message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    string    $level      Log level (error, warning, info, debug).
	 * @param    array     $context    Additional context data.
	 */
	protected function log( $message, $level = 'info', $context = array() ) {
		if ( $this->logger ) {
			$this->logger->log( $level, $message, $context );
		} else {
			// Fallback to error_log if logger not available
			error_log( sprintf( '[%s] %s: %s', $this->gateway_id, strtoupper( $level ), $message ) );
		}
	}

	/**
	 * Test the gateway connection.
	 *
	 * Tests the API connection with the current configuration.
	 *
	 * @since    1.0.0
	 * @return   array    Result array with 'success' boolean and 'message' string.
	 */
	abstract public function test_connection();

	/**
	 * Connect to the gateway.
	 *
	 * Establishes connection with the payment gateway using stored credentials.
	 *
	 * @since    1.0.0
	 * @return   bool    True if connection successful, false otherwise.
	 */
	abstract public function connect();

	/**
	 * Get available products from the gateway.
	 *
	 * Retrieves list of products/plans from the payment gateway.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Optional arguments for filtering products.
	 * @return   array|WP_Error    Array of products or WP_Error on failure.
	 */
	abstract public function get_products( $args = array() );

	/**
	 * Get a specific product by ID.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Product identifier.
	 * @return   array|WP_Error           Product data or WP_Error on failure.
	 */
	abstract public function get_product( $product_id );

	/**
	 * Create a checkout session.
	 *
	 * Creates a new checkout session for the specified product.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Checkout arguments (product_id, customer info, etc.).
	 * @return   array|WP_Error    Checkout session data or WP_Error on failure.
	 */
	abstract public function create_checkout( $args = array() );

	/**
	 * Get checkout session details.
	 *
	 * @since    1.0.0
	 * @param    string    $session_id    Checkout session ID.
	 * @return   array|WP_Error           Session data or WP_Error on failure.
	 */
	abstract public function get_checkout_session( $session_id );

	/**
	 * Cancel a subscription.
	 *
	 * @since    1.0.0
	 * @param    string    $subscription_id    Subscription identifier.
	 * @return   bool|WP_Error                True if successful, WP_Error on failure.
	 */
	abstract public function cancel_subscription( $subscription_id );

	/**
	 * Get subscription details.
	 *
	 * @since    1.0.0
	 * @param    string    $subscription_id    Subscription identifier.
	 * @return   array|WP_Error                Subscription data or WP_Error on failure.
	 */
	abstract public function get_subscription( $subscription_id );

	/**
	 * Update subscription.
	 *
	 * @since    1.0.0
	 * @param    string    $subscription_id    Subscription identifier.
	 * @param    array     $args              Update arguments.
	 * @return   array|WP_Error                Updated subscription data or WP_Error on failure.
	 */
	abstract public function update_subscription( $subscription_id, $args = array() );

	/**
	 * Get customer subscriptions.
	 *
	 * @since    1.0.0
	 * @param    string    $customer_id    Customer identifier.
	 * @return   array|WP_Error            Array of subscriptions or WP_Error on failure.
	 */
	abstract public function get_customer_subscriptions( $customer_id );

	/**
	 * Get customer transactions/invoices.
	 *
	 * @since    1.0.0
	 * @param    string    $customer_id    Customer identifier.
	 * @param    array     $args          Optional arguments for filtering.
	 * @return   array|WP_Error            Array of transactions or WP_Error on failure.
	 */
	abstract public function get_customer_transactions( $customer_id, $args = array() );

	/**
	 * Handle webhook request.
	 *
	 * Processes incoming webhook requests from the payment gateway.
	 *
	 * @since    1.0.0
	 * @param    array    $payload    Webhook payload data.
	 * @param    array    $headers    Request headers.
	 * @return   bool                True if webhook processed successfully, false otherwise.
	 */
	abstract public function handle_webhook( $payload, $headers = array() );

	/**
	 * Validate webhook signature.
	 *
	 * Validates that the webhook request came from the payment gateway.
	 *
	 * @since    1.0.0
	 * @param    string    $payload      Raw webhook payload.
	 * @param    string    $signature    Webhook signature.
	 * @param    array     $headers      Request headers.
	 * @return   bool                    True if signature is valid, false otherwise.
	 */
	abstract public function validate_webhook_signature( $payload, $signature, $headers = array() );

	/**
	 * Get webhook URL.
	 *
	 * Returns the URL that should be configured in the payment gateway
	 * for webhook notifications.
	 *
	 * @since    1.0.0
	 * @return   string    Webhook URL.
	 */
	public function get_webhook_url() {
		return rest_url( 'slbp/v1/webhook/' . $this->gateway_id );
	}

	/**
	 * Format error response.
	 *
	 * Helper method to format consistent error responses.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Error message.
	 * @param    string    $code       Error code (optional).
	 * @param    array     $data       Additional error data (optional).
	 * @return   WP_Error              Formatted error object.
	 */
	protected function format_error( $message, $code = 'gateway_error', $data = array() ) {
		$this->log( $message, 'error', $data );
		return new WP_Error( $code, $message, $data );
	}

	/**
	 * Format success response.
	 *
	 * Helper method to format consistent success responses.
	 *
	 * @since    1.0.0
	 * @param    mixed     $data       Response data.
	 * @param    string    $message    Success message (optional).
	 * @return   array                 Formatted response array.
	 */
	protected function format_success( $data, $message = '' ) {
		$response = array(
			'success' => true,
			'data'    => $data,
		);

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
			$this->log( $message, 'info' );
		}

		return $response;
	}

	/**
	 * Make HTTP request.
	 *
	 * Helper method for making HTTP requests to payment gateway APIs.
	 *
	 * @since    1.0.0
	 * @param    string    $url        Request URL.
	 * @param    array     $args       Request arguments.
	 * @param    string    $method     HTTP method (GET, POST, PUT, DELETE).
	 * @return   array|WP_Error        Response data or WP_Error on failure.
	 */
	protected function make_request( $url, $args = array(), $method = 'GET' ) {
		$defaults = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'SkyLearn-Billing-Pro/' . SLBP_VERSION,
			),
		);

		$args = wp_parse_args( $args, $defaults );

		// Add authentication headers (implemented in child classes)
		$args['headers'] = array_merge( $args['headers'], $this->get_auth_headers() );

		// Log request (without sensitive data)
		$log_args = $args;
		if ( isset( $log_args['headers']['Authorization'] ) ) {
			$log_args['headers']['Authorization'] = 'Bearer [REDACTED]';
		}
		$this->log( sprintf( '%s request to %s', $method, $url ), 'debug', $log_args );

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log( 'HTTP request failed: ' . $response->get_error_message(), 'error' );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// Log response
		$this->log( sprintf( 'Response received: %d', $status_code ), 'debug', array( 'body' => $body ) );

		if ( $status_code >= 400 ) {
			$error_message = sprintf( 'HTTP %d error', $status_code );
			$error_data = array( 'status_code' => $status_code, 'body' => $body );
			
			// Try to extract error message from response body
			$decoded_body = json_decode( $body, true );
			if ( is_array( $decoded_body ) && isset( $decoded_body['message'] ) ) {
				$error_message = $decoded_body['message'];
			} elseif ( is_array( $decoded_body ) && isset( $decoded_body['error'] ) ) {
				$error_message = $decoded_body['error'];
			}

			return $this->format_error( $error_message, 'http_error', $error_data );
		}

		$decoded_response = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return $this->format_error( 'Invalid JSON response from gateway', 'json_error', array( 'body' => $body ) );
		}

		return $decoded_response;
	}

	/**
	 * Get authentication headers.
	 *
	 * Returns headers needed for API authentication.
	 * Must be implemented by child classes.
	 *
	 * @since    1.0.0
	 * @return   array    Authentication headers.
	 */
	abstract protected function get_auth_headers();

	/**
	 * Sanitize product data.
	 *
	 * Helper method to sanitize product data from gateway API.
	 *
	 * @since    1.0.0
	 * @param    array    $product    Raw product data.
	 * @return   array                Sanitized product data.
	 */
	protected function sanitize_product( $product ) {
		return array(
			'id'          => sanitize_text_field( $product['id'] ?? '' ),
			'name'        => sanitize_text_field( $product['name'] ?? '' ),
			'description' => sanitize_textarea_field( $product['description'] ?? '' ),
			'price'       => floatval( $product['price'] ?? 0 ),
			'currency'    => sanitize_text_field( $product['currency'] ?? 'USD' ),
			'type'        => sanitize_text_field( $product['type'] ?? 'one_time' ),
			'status'      => sanitize_text_field( $product['status'] ?? 'active' ),
		);
	}

	/**
	 * Sanitize subscription data.
	 *
	 * Helper method to sanitize subscription data from gateway API.
	 *
	 * @since    1.0.0
	 * @param    array    $subscription    Raw subscription data.
	 * @return   array                     Sanitized subscription data.
	 */
	protected function sanitize_subscription( $subscription ) {
		return array(
			'id'               => sanitize_text_field( $subscription['id'] ?? '' ),
			'customer_id'      => sanitize_text_field( $subscription['customer_id'] ?? '' ),
			'product_id'       => sanitize_text_field( $subscription['product_id'] ?? '' ),
			'status'           => sanitize_text_field( $subscription['status'] ?? '' ),
			'current_period_start' => sanitize_text_field( $subscription['current_period_start'] ?? '' ),
			'current_period_end'   => sanitize_text_field( $subscription['current_period_end'] ?? '' ),
			'created_at'       => sanitize_text_field( $subscription['created_at'] ?? '' ),
			'updated_at'       => sanitize_text_field( $subscription['updated_at'] ?? '' ),
		);
	}
}