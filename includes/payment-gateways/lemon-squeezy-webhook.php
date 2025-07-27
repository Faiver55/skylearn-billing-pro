<?php
/**
 * Lemon Squeezy Webhook Handler
 *
 * Handles incoming webhook requests from Lemon Squeezy
 * and processes payment events.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/payment-gateways
 */

/**
 * Lemon Squeezy Webhook Handler Class
 *
 * Processes webhook requests from Lemon Squeezy and handles
 * payment events for subscriptions, orders, and other events.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/payment-gateways
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Lemon_Squeezy_Webhook {

	/**
	 * Gateway instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Lemon_Squeezy    $gateway    Gateway instance.
	 */
	private $gateway;

	/**
	 * Logger instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Logger    $logger    Logger instance.
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Initialize logger if available
		if ( class_exists( 'SLBP_Logger' ) ) {
			$this->logger = new SLBP_Logger( 'lemon_squeezy_webhook' );
		}

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Register REST API endpoint for webhook
		add_action( 'rest_api_init', array( $this, 'register_webhook_endpoint' ) );
	}

	/**
	 * Register webhook REST API endpoint.
	 *
	 * @since    1.0.0
	 */
	public function register_webhook_endpoint() {
		register_rest_route( 'slbp/v1', '/webhook/lemon_squeezy', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_webhook_request' ),
			'permission_callback' => '__return_true', // Public endpoint
		) );
	}

	/**
	 * Handle incoming webhook request.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    The webhook request.
	 * @return   WP_REST_Response              The response.
	 */
	public function handle_webhook_request( $request ) {
		$this->log( 'Webhook request received', 'info' );

		// Get raw body and headers
		$raw_body = $request->get_body();
		$headers = $request->get_headers();

		// Validate content type
		$content_type = $request->get_content_type();
		if ( strpos( $content_type['value'], 'application/json' ) === false ) {
			$this->log( 'Invalid content type: ' . $content_type['value'], 'error' );
			return new WP_REST_Response( array( 'error' => 'Invalid content type' ), 400 );
		}

		// Parse JSON payload
		$payload = json_decode( $raw_body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->log( 'Invalid JSON payload: ' . json_last_error_msg(), 'error' );
			return new WP_REST_Response( array( 'error' => 'Invalid JSON' ), 400 );
		}

		// Get signature from headers
		$signature = null;
		if ( isset( $headers['x_signature'] ) ) {
			$signature = $headers['x_signature'][0];
		} elseif ( isset( $headers['X-Signature'] ) ) {
			$signature = $headers['X-Signature'][0];
		}

		if ( empty( $signature ) ) {
			$this->log( 'Missing webhook signature', 'error' );
			return new WP_REST_Response( array( 'error' => 'Missing signature' ), 400 );
		}

		// Initialize gateway with current settings
		$this->initialize_gateway();

		// Validate webhook signature
		if ( ! $this->gateway->validate_webhook_signature( $raw_body, $signature, $headers ) ) {
			$this->log( 'Webhook signature validation failed', 'error' );
			return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 401 );
		}

		// Log webhook payload (sanitized)
		$this->log_webhook_payload( $payload );

		// Process webhook
		try {
			$result = $this->gateway->handle_webhook( $payload, $headers );
			
			if ( $result ) {
				$this->log( 'Webhook processed successfully', 'info' );
				return new WP_REST_Response( array( 'status' => 'success' ), 200 );
			} else {
				$this->log( 'Webhook processing failed', 'error' );
				return new WP_REST_Response( array( 'error' => 'Processing failed' ), 500 );
			}
		} catch ( Exception $e ) {
			$this->log( 'Webhook processing exception: ' . $e->getMessage(), 'error' );
			return new WP_REST_Response( array( 'error' => 'Internal error' ), 500 );
		}
	}

	/**
	 * Initialize gateway with current settings.
	 *
	 * @since    1.0.0
	 */
	private function initialize_gateway() {
		// Get payment settings
		$payment_settings = get_option( 'slbp_payment_settings', array() );

		$config = array(
			'api_key'        => $payment_settings['lemon_squeezy_api_key'] ?? '',
			'store_id'       => $payment_settings['lemon_squeezy_store_id'] ?? '',
			'test_mode'      => $payment_settings['lemon_squeezy_test_mode'] ?? false,
			'webhook_secret' => $payment_settings['webhook_secret'] ?? '',
		);

		$this->gateway = new SLBP_Lemon_Squeezy( $config );
	}

	/**
	 * Log webhook payload (with sensitive data removed).
	 *
	 * @since    1.0.0
	 * @param    array    $payload    Webhook payload.
	 */
	private function log_webhook_payload( $payload ) {
		// Create sanitized copy for logging
		$sanitized_payload = $payload;

		// Remove sensitive customer data
		if ( isset( $sanitized_payload['data']['attributes'] ) ) {
			$attributes = &$sanitized_payload['data']['attributes'];
			
			// Remove email and personal info
			if ( isset( $attributes['user_email'] ) ) {
				$attributes['user_email'] = '[REDACTED]';
			}
			if ( isset( $attributes['user_name'] ) ) {
				$attributes['user_name'] = '[REDACTED]';
			}
			if ( isset( $attributes['billing_address'] ) ) {
				$attributes['billing_address'] = '[REDACTED]';
			}
			if ( isset( $attributes['tax_address'] ) ) {
				$attributes['tax_address'] = '[REDACTED]';
			}

			// Remove payment method details
			if ( isset( $attributes['card_brand'] ) ) {
				$attributes['card_brand'] = '[REDACTED]';
			}
			if ( isset( $attributes['card_last_four'] ) ) {
				$attributes['card_last_four'] = '[REDACTED]';
			}
		}

		$event_name = $payload['meta']['event_name'] ?? 'unknown';
		$this->log( sprintf( 'Webhook payload for event %s', $event_name ), 'debug', $sanitized_payload );
	}

	/**
	 * Log a message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    string    $level      Log level.
	 * @param    array     $context    Additional context.
	 */
	private function log( $message, $level = 'info', $context = array() ) {
		if ( $this->logger ) {
			$this->logger->log( $level, $message, $context );
		} else {
			// Fallback to error_log
			error_log( sprintf( '[SLBP-LS-Webhook] %s: %s', strtoupper( $level ), $message ) );
		}
	}

	/**
	 * Get webhook URL for this gateway.
	 *
	 * @since    1.0.0
	 * @return   string    Webhook URL.
	 */
	public static function get_webhook_url() {
		return rest_url( 'slbp/v1/webhook/lemon_squeezy' );
	}

	/**
	 * Get webhook status information.
	 *
	 * @since    1.0.0
	 * @return   array    Status information.
	 */
	public static function get_webhook_status() {
		$webhook_url = self::get_webhook_url();
		$payment_settings = get_option( 'slbp_payment_settings', array() );
		$webhook_secret = $payment_settings['webhook_secret'] ?? '';

		$status = array(
			'url'           => $webhook_url,
			'secret_set'    => ! empty( $webhook_secret ),
			'reachable'     => null, // This would require actually testing the endpoint
			'last_received' => get_option( 'slbp_webhook_last_received_lemon_squeezy', null ),
		);

		return $status;
	}

	/**
	 * Test webhook endpoint accessibility.
	 *
	 * @since    1.0.0
	 * @return   array    Test result.
	 */
	public static function test_webhook_endpoint() {
		$webhook_url = self::get_webhook_url();

		// Make a test request to the webhook endpoint
		$response = wp_remote_post( $webhook_url, array(
			'body'    => wp_json_encode( array( 'test' => true ) ),
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Signature'  => 'test_signature',
			),
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf( 'Webhook endpoint test failed: %s', $response->get_error_message() ),
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		
		// We expect a 400 or 401 response for a test request (due to invalid signature)
		// This confirms the endpoint is reachable and processing requests
		if ( in_array( $status_code, array( 400, 401 ) ) ) {
			return array(
				'success' => true,
				'message' => 'Webhook endpoint is reachable and processing requests',
			);
		}

		return array(
			'success' => false,
			'message' => sprintf( 'Webhook endpoint returned unexpected status code: %d', $status_code ),
		);
	}

	/**
	 * Generate webhook documentation.
	 *
	 * @since    1.0.0
	 * @return   array    Documentation data.
	 */
	public static function get_webhook_documentation() {
		return array(
			'url'         => self::get_webhook_url(),
			'method'      => 'POST',
			'content_type' => 'application/json',
			'headers'     => array(
				'X-Signature' => 'HMAC-SHA256 signature for verification',
			),
			'events'      => array(
				'order_created'                 => 'New order/purchase completed',
				'subscription_created'          => 'New subscription created',
				'subscription_updated'          => 'Subscription details updated',
				'subscription_cancelled'        => 'Subscription cancelled',
				'subscription_resumed'          => 'Subscription resumed after cancellation',
				'subscription_expired'          => 'Subscription expired',
				'subscription_paused'           => 'Subscription paused',
				'subscription_unpaused'         => 'Subscription unpaused',
				'subscription_payment_failed'   => 'Subscription payment failed',
				'subscription_payment_success'  => 'Subscription payment successful',
			),
			'configuration' => array(
				'description' => 'Configure this URL in your Lemon Squeezy webhook settings',
				'instructions' => array(
					'1. Log in to your Lemon Squeezy dashboard',
					'2. Navigate to Settings → Webhooks',
					'3. Click "Add webhook endpoint"',
					'4. Enter the webhook URL above',
					'5. Select the events you want to receive',
					'6. Set a secret key in your SkyLearn Billing Pro settings',
					'7. Save the webhook configuration',
				),
			),
		);
	}
}

// Initialize webhook handler if WordPress is loaded
if ( defined( 'ABSPATH' ) ) {
	new SLBP_Lemon_Squeezy_Webhook();
}