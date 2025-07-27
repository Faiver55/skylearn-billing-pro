<?php
/**
 * The integrations manager for handling 3rd-party integrations.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/integrations
 */

/**
 * The integrations manager class.
 *
 * Handles registration and management of 3rd-party integrations like Mailchimp, Zapier, etc.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/integrations
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Integrations_Manager {

	/**
	 * Registered integrations.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $integrations    Array of registered integrations.
	 */
	private $integrations = array();

	/**
	 * Active integrations.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $active_integrations    Array of active integration instances.
	 */
	private $active_integrations = array();

	/**
	 * Initialize the integrations manager.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->register_default_integrations();
		$this->init_active_integrations();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// REST API endpoints for webhooks
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Integration event hooks
		add_action( 'slbp_payment_success', array( $this, 'trigger_integration_events' ), 10, 2 );
		add_action( 'slbp_subscription_created', array( $this, 'trigger_integration_events' ), 10, 2 );
		add_action( 'slbp_enrollment_created', array( $this, 'trigger_integration_events' ), 10, 2 );
		add_action( 'slbp_subscription_cancelled', array( $this, 'trigger_integration_events' ), 10, 2 );
		add_action( 'slbp_refund_processed', array( $this, 'trigger_integration_events' ), 10, 2 );
	}

	/**
	 * Register default integrations.
	 *
	 * @since    1.0.0
	 */
	private function register_default_integrations() {
		$this->register_integration( 'mailchimp', array(
			'name'        => __( 'Mailchimp', 'skylearn-billing-pro' ),
			'description' => __( 'Add customers to Mailchimp lists automatically', 'skylearn-billing-pro' ),
			'class'       => 'SLBP_Mailchimp_Integration',
			'settings'    => array(
				'api_key' => array(
					'label'       => __( 'API Key', 'skylearn-billing-pro' ),
					'type'        => 'password',
					'description' => __( 'Your Mailchimp API key', 'skylearn-billing-pro' ),
				),
				'list_id' => array(
					'label'       => __( 'Default List ID', 'skylearn-billing-pro' ),
					'type'        => 'text',
					'description' => __( 'Default Mailchimp list ID for new subscribers', 'skylearn-billing-pro' ),
				),
				'double_optin' => array(
					'label'   => __( 'Double Opt-in', 'skylearn-billing-pro' ),
					'type'    => 'checkbox',
					'default' => true,
				),
			),
		) );

		$this->register_integration( 'zapier', array(
			'name'        => __( 'Zapier', 'skylearn-billing-pro' ),
			'description' => __( 'Connect to 1000+ apps via Zapier webhooks', 'skylearn-billing-pro' ),
			'class'       => 'SLBP_Zapier_Integration',
			'settings'    => array(
				'webhook_url' => array(
					'label'       => __( 'Webhook URL', 'skylearn-billing-pro' ),
					'type'        => 'url',
					'description' => __( 'Your Zapier webhook URL', 'skylearn-billing-pro' ),
				),
				'events' => array(
					'label'       => __( 'Trigger Events', 'skylearn-billing-pro' ),
					'type'        => 'multiselect',
					'options'     => array(
						'payment_success'       => __( 'Payment Success', 'skylearn-billing-pro' ),
						'subscription_created'  => __( 'Subscription Created', 'skylearn-billing-pro' ),
						'enrollment_created'    => __( 'Course Enrollment', 'skylearn-billing-pro' ),
						'subscription_cancelled' => __( 'Subscription Cancelled', 'skylearn-billing-pro' ),
						'refund_processed'      => __( 'Refund Processed', 'skylearn-billing-pro' ),
					),
					'default'     => array( 'payment_success', 'subscription_created' ),
				),
			),
		) );

		/**
		 * Allow custom integrations to be registered.
		 *
		 * @since 1.0.0
		 *
		 * @param SLBP_Integrations_Manager $this The integrations manager instance.
		 */
		do_action( 'slbp_register_integrations', $this );
	}

	/**
	 * Initialize active integrations.
	 *
	 * @since    1.0.0
	 */
	private function init_active_integrations() {
		$settings = get_option( 'slbp_integrations_settings', array() );

		foreach ( $this->integrations as $integration_id => $integration_config ) {
			$integration_settings = $settings[ $integration_id ] ?? array();
			
			if ( ! empty( $integration_settings['enabled'] ) && class_exists( $integration_config['class'] ) ) {
				$this->active_integrations[ $integration_id ] = new $integration_config['class']( 
					$integration_settings, 
					$integration_config 
				);
			}
		}
	}

	/**
	 * Register an integration.
	 *
	 * @since    1.0.0
	 * @param    string $integration_id   The integration ID.
	 * @param    array  $config          The integration configuration.
	 */
	public function register_integration( $integration_id, $config ) {
		$defaults = array(
			'name'        => '',
			'description' => '',
			'class'       => '',
			'settings'    => array(),
			'events'      => array(),
		);

		$this->integrations[ $integration_id ] = wp_parse_args( $config, $defaults );

		/**
		 * Fires after an integration is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $integration_id The integration ID.
		 * @param array  $config         The integration configuration.
		 */
		do_action( 'slbp_integration_registered', $integration_id, $config );
	}

	/**
	 * Trigger integration events.
	 *
	 * @since    1.0.0
	 * @param    int   $user_id The user ID.
	 * @param    array $data    Event data.
	 */
	public function trigger_integration_events( $user_id, $data ) {
		$event_name = current_action();
		
		foreach ( $this->active_integrations as $integration_id => $integration ) {
			if ( method_exists( $integration, 'handle_event' ) ) {
				$integration->handle_event( $event_name, $user_id, $data );
			}
		}

		/**
		 * Allow custom handling of integration events.
		 *
		 * @since 1.0.0
		 *
		 * @param string $event_name The event name.
		 * @param int    $user_id    The user ID.
		 * @param array  $data       Event data.
		 */
		do_action( 'slbp_integration_event', $event_name, $user_id, $data );
	}

	/**
	 * Register REST API routes for webhooks.
	 *
	 * @since    1.0.0
	 */
	public function register_rest_routes() {
		register_rest_route( 'slbp/v1', '/webhook/(?P<integration>[a-zA-Z0-9_-]+)', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_webhook' ),
			'permission_callback' => array( $this, 'verify_webhook_permission' ),
			'args'                => array(
				'integration' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
			),
		) );

		// Generic webhook endpoint for Zapier and others
		register_rest_route( 'slbp/v1', '/webhook', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_generic_webhook' ),
			'permission_callback' => array( $this, 'verify_webhook_permission' ),
		) );
	}

	/**
	 * Handle incoming webhooks.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The request object.
	 * @return   WP_REST_Response         The response object.
	 */
	public function handle_webhook( $request ) {
		$integration_id = $request->get_param( 'integration' );
		$data = $request->get_json_params();

		if ( ! isset( $this->active_integrations[ $integration_id ] ) ) {
			return new WP_REST_Response( array(
				'error' => 'Integration not found or not active',
			), 404 );
		}

		$integration = $this->active_integrations[ $integration_id ];
		
		if ( method_exists( $integration, 'handle_webhook' ) ) {
			$result = $integration->handle_webhook( $data, $request );
			
			if ( is_wp_error( $result ) ) {
				return new WP_REST_Response( array(
					'error' => $result->get_error_message(),
				), 400 );
			}

			return new WP_REST_Response( array(
				'success' => true,
				'data'    => $result,
			), 200 );
		}

		return new WP_REST_Response( array(
			'error' => 'Webhook handler not implemented',
		), 501 );
	}

	/**
	 * Handle generic webhooks.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The request object.
	 * @return   WP_REST_Response         The response object.
	 */
	public function handle_generic_webhook( $request ) {
		$data = $request->get_json_params();

		/**
		 * Allow custom handling of generic webhooks.
		 *
		 * @since 1.0.0
		 *
		 * @param array             $data    Webhook data.
		 * @param WP_REST_Request   $request The request object.
		 */
		$result = apply_filters( 'slbp_handle_generic_webhook', null, $data, $request );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array(
				'error' => $result->get_error_message(),
			), 400 );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'data'    => $result,
		), 200 );
	}

	/**
	 * Verify webhook permissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The request object.
	 * @return   bool                     True if permission granted.
	 */
	public function verify_webhook_permission( $request ) {
		// Basic verification - can be enhanced with API keys, signatures, etc.
		$api_key = $request->get_header( 'X-SLBP-API-Key' );
		$expected_key = get_option( 'slbp_webhook_api_key' );

		if ( empty( $expected_key ) ) {
			// If no API key is set, allow for now (should be configured in production)
			return true;
		}

		return hash_equals( $expected_key, $api_key );
	}

	/**
	 * Get all registered integrations.
	 *
	 * @since    1.0.0
	 * @return   array The registered integrations.
	 */
	public function get_integrations() {
		return $this->integrations;
	}

	/**
	 * Get active integrations.
	 *
	 * @since    1.0.0
	 * @return   array The active integrations.
	 */
	public function get_active_integrations() {
		return $this->active_integrations;
	}

	/**
	 * Get integration settings.
	 *
	 * @since    1.0.0
	 * @param    string $integration_id The integration ID.
	 * @return   array                  The integration settings.
	 */
	public function get_integration_settings( $integration_id ) {
		$settings = get_option( 'slbp_integrations_settings', array() );
		return $settings[ $integration_id ] ?? array();
	}

	/**
	 * Update integration settings.
	 *
	 * @since    1.0.0
	 * @param    string $integration_id The integration ID.
	 * @param    array  $settings       The new settings.
	 * @return   bool                   True if updated successfully.
	 */
	public function update_integration_settings( $integration_id, $settings ) {
		$all_settings = get_option( 'slbp_integrations_settings', array() );
		$all_settings[ $integration_id ] = $settings;
		
		$result = update_option( 'slbp_integrations_settings', $all_settings );
		
		if ( $result ) {
			// Reinitialize integrations after settings change
			$this->active_integrations = array();
			$this->init_active_integrations();
		}
		
		return $result;
	}

	/**
	 * Test an integration connection.
	 *
	 * @since    1.0.0
	 * @param    string $integration_id The integration ID.
	 * @param    array  $settings       Optional. Test with specific settings.
	 * @return   bool|WP_Error          True if connection successful, WP_Error on failure.
	 */
	public function test_integration( $integration_id, $settings = null ) {
		if ( ! isset( $this->integrations[ $integration_id ] ) ) {
			return new WP_Error( 'invalid_integration', __( 'Integration not found.', 'skylearn-billing-pro' ) );
		}

		$integration_config = $this->integrations[ $integration_id ];
		$test_settings = $settings ?: $this->get_integration_settings( $integration_id );

		if ( ! class_exists( $integration_config['class'] ) ) {
			return new WP_Error( 'class_not_found', __( 'Integration class not found.', 'skylearn-billing-pro' ) );
		}

		$integration = new $integration_config['class']( $test_settings, $integration_config );
		
		if ( method_exists( $integration, 'test_connection' ) ) {
			return $integration->test_connection();
		}

		return new WP_Error( 'test_not_implemented', __( 'Connection test not implemented for this integration.', 'skylearn-billing-pro' ) );
	}

	/**
	 * Send data to active integrations.
	 *
	 * @since    1.0.0
	 * @param    string $event_type The event type.
	 * @param    array  $data       The data to send.
	 */
	public function send_to_integrations( $event_type, $data ) {
		foreach ( $this->active_integrations as $integration_id => $integration ) {
			if ( method_exists( $integration, 'send_data' ) ) {
				$integration->send_data( $event_type, $data );
			}
		}
	}
}