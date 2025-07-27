<?php
/**
 * Abstract class for third-party integrations.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/integrations
 */

/**
 * Abstract integration class.
 *
 * Base class for all third-party integrations.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/integrations
 * @author     Skyian LLC <contact@skyianllc.com>
 */
abstract class SLBP_Abstract_Integration {

	/**
	 * Integration settings.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $settings    Integration settings.
	 */
	protected $settings;

	/**
	 * Integration configuration.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $config      Integration configuration.
	 */
	protected $config;

	/**
	 * Integration ID.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string   $integration_id   Integration ID.
	 */
	protected $integration_id;

	/**
	 * Initialize the integration.
	 *
	 * @since    1.0.0
	 * @param    array  $settings Integration settings.
	 * @param    array  $config   Integration configuration.
	 */
	public function __construct( $settings, $config ) {
		$this->settings = $settings;
		$this->config = $config;
		$this->integration_id = $this->get_integration_id();
	}

	/**
	 * Get the integration ID.
	 *
	 * @since    1.0.0
	 * @return   string   The integration ID.
	 */
	abstract protected function get_integration_id();

	/**
	 * Handle integration events.
	 *
	 * @since    1.0.0
	 * @param    string $event_name The event name.
	 * @param    int    $user_id    The user ID.
	 * @param    array  $data       Event data.
	 */
	abstract public function handle_event( $event_name, $user_id, $data );

	/**
	 * Test the integration connection.
	 *
	 * @since    1.0.0
	 * @return   bool|WP_Error   True if connection successful, WP_Error on failure.
	 */
	abstract public function test_connection();

	/**
	 * Get setting value.
	 *
	 * @since    1.0.0
	 * @param    string $key     Setting key.
	 * @param    mixed  $default Default value.
	 * @return   mixed           Setting value.
	 */
	protected function get_setting( $key, $default = null ) {
		return $this->settings[ $key ] ?? $default;
	}

	/**
	 * Check if integration is enabled.
	 *
	 * @since    1.0.0
	 * @return   bool   True if enabled.
	 */
	protected function is_enabled() {
		return ! empty( $this->settings['enabled'] );
	}

	/**
	 * Log integration activity.
	 *
	 * @since    1.0.0
	 * @param    string $message The log message.
	 * @param    string $level   Log level (info, warning, error).
	 * @param    array  $context Additional context.
	 */
	protected function log( $message, $level = 'info', $context = array() ) {
		if ( class_exists( 'SLBP_Logger' ) ) {
			$logger = new SLBP_Logger();
			$logger->log( $level, sprintf( '[%s] %s', $this->integration_id, $message ), $context );
		}
	}

	/**
	 * Make HTTP request.
	 *
	 * @since    1.0.0
	 * @param    string $url     Request URL.
	 * @param    array  $args    Request arguments.
	 * @return   array|WP_Error  Response or error.
	 */
	protected function make_request( $url, $args = array() ) {
		$defaults = array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'SkyLearn Billing Pro/' . SLBP_VERSION,
			),
		);

		$args = wp_parse_args( $args, $defaults );
		
		$response = wp_remote_request( $url, $args );
		
		if ( is_wp_error( $response ) ) {
			$this->log( sprintf( 'HTTP request failed: %s', $response->get_error_message() ), 'error' );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 400 ) {
			$this->log( sprintf( 'HTTP request failed with code %d: %s', $response_code, $response_body ), 'error' );
			return new WP_Error( 'http_error', sprintf( 'HTTP %d: %s', $response_code, $response_body ) );
		}

		return array(
			'code' => $response_code,
			'body' => $response_body,
			'headers' => wp_remote_retrieve_headers( $response ),
		);
	}

	/**
	 * Prepare user data for integration.
	 *
	 * @since    1.0.0
	 * @param    int $user_id The user ID.
	 * @return   array        User data.
	 */
	protected function prepare_user_data( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		
		if ( ! $user ) {
			return array();
		}

		return array(
			'id'         => $user->ID,
			'email'      => $user->user_email,
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
			'username'   => $user->user_login,
			'display_name' => $user->display_name,
			'registered' => $user->user_registered,
		);
	}

	/**
	 * Sanitize and validate settings.
	 *
	 * @since    1.0.0
	 * @param    array $settings Raw settings.
	 * @return   array           Sanitized settings.
	 */
	public function sanitize_settings( $settings ) {
		$sanitized = array();
		
		foreach ( $this->config['settings'] as $key => $field_config ) {
			$value = $settings[ $key ] ?? $field_config['default'] ?? '';
			
			switch ( $field_config['type'] ) {
				case 'text':
				case 'password':
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;
				case 'email':
					$sanitized[ $key ] = sanitize_email( $value );
					break;
				case 'url':
					$sanitized[ $key ] = esc_url_raw( $value );
					break;
				case 'checkbox':
					$sanitized[ $key ] = ! empty( $value );
					break;
				case 'multiselect':
					$sanitized[ $key ] = is_array( $value ) ? array_map( 'sanitize_key', $value ) : array();
					break;
				default:
					$sanitized[ $key ] = sanitize_text_field( $value );
			}
		}
		
		return $sanitized;
	}
}