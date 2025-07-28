<?php
require_once plugin_dir_path(__FILE__) . 'class-slbp-rest-controllers.php';
/**
 * SkyLearn Billing Pro REST API Controller
 *
 * Main REST API controller that registers all API endpoints and handles authentication.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 */

/**
 * REST API Controller Class
 *
 * Handles registration and management of all REST API endpoints.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_REST_API {

	/**
	 * API namespace.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $namespace    API namespace.
	 */
	private $namespace = 'slbp/v1';

	/**
	 * API controllers.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $controllers    Array of API controllers.
	 */
	private $controllers = array();

	/**
	 * API rate limiter.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_API_Rate_Limiter    $rate_limiter    Rate limiter instance.
	 */
	private $rate_limiter;

	/**
	 * API logger.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_API_Logger    $logger    API logger instance.
	 */
	private $logger;

	/**
	 * API documentation generator.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_API_Documentation_Generator    $doc_generator    Documentation generator instance.
	 */
	private $doc_generator;

	/**
	 * Initialize the REST API.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->rate_limiter = new SLBP_API_Rate_Limiter();
		$this->logger = new SLBP_API_Logger();
		$this->doc_generator = new SLBP_API_Documentation_Generator();
		
		$this->init_hooks();
		$this->register_controllers();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'rest_api_init', array( $this, 'register_authentication' ) );
		add_filter( 'rest_pre_dispatch', array( $this, 'pre_dispatch' ), 10, 3 );
		add_filter( 'rest_post_dispatch', array( $this, 'post_dispatch' ), 10, 3 );
	}

	/**
	 * Register API controllers.
	 *
	 * @since    1.0.0
	 */
	private function register_controllers() {
		$this->controllers = array(
			'billing'       => new SLBP_REST_Billing_Controller( $this->namespace ),
			'subscriptions' => new SLBP_REST_Subscriptions_Controller( $this->namespace ),
			'users'         => new SLBP_REST_Users_Controller( $this->namespace ),
			'courses'       => new SLBP_REST_Courses_Controller( $this->namespace ),
			'analytics'     => new SLBP_REST_Analytics_Controller( $this->namespace ),
			'webhooks'      => new SLBP_REST_Webhooks_Controller( $this->namespace ),
		);
	}

	/**
	 * Register all API routes.
	 *
	 * @since    1.0.0
	 */
	public function register_routes() {
		// Register routes for each controller
		foreach ( $this->controllers as $controller ) {
			$controller->register_routes();
		}

		// Register documentation endpoints
		$this->doc_generator->register_documentation_endpoints();

		// Register authentication endpoint
		register_rest_route( $this->namespace, '/auth', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'authenticate' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'api_key' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		// Register API status endpoint
		register_rest_route( $this->namespace, '/status', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_api_status' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Register API authentication.
	 *
	 * @since    1.0.0
	 */
	public function register_authentication() {
		add_filter( 'determine_current_user', array( $this, 'determine_current_user' ), 20 );
	}

	/**
	 * Determine current user based on API key.
	 *
	 * @since    1.0.0
	 * @param    int|bool    $user_id    Current user ID or false.
	 * @return   int|bool                User ID or false.
	 */
	public function determine_current_user( $user_id ) {
		// Skip if already authenticated or not a REST request
		if ( $user_id || ! $this->is_rest_api_request() ) {
			return $user_id;
		}

		// Get API key from header or query parameter
		$api_key = $this->get_api_key_from_request();
		
		if ( ! $api_key ) {
			return $user_id;
		}

		// Validate API key and get user
		$api_user = $this->validate_api_key( $api_key );
		
		if ( $api_user ) {
			return $api_user->ID;
		}

		return $user_id;
	}

	/**
	 * Pre-dispatch hook for rate limiting and logging.
	 *
	 * @since    1.0.0
	 * @param    mixed           $result      Response to replace the requested version with.
	 * @param    WP_REST_Server  $server      Server instance.
	 * @param    WP_REST_Request $request     Request used to generate the response.
	 * @return   mixed                        Response or WP_Error.
	 */
	public function pre_dispatch( $result, $server, $request ) {
		// Only handle our API requests
		if ( strpos( $request->get_route(), '/' . $this->namespace ) !== 0 ) {
			return $result;
		}

		// Check rate limiting
		$rate_limit_result = $this->rate_limiter->check_rate_limit( $request );
		if ( is_wp_error( $rate_limit_result ) ) {
			return $rate_limit_result;
		}

		// Log the request
		$this->logger->log_request( $request );

		return $result;
	}

	/**
	 * Post-dispatch hook for logging responses.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Response $result     Response generated by the route callback.
	 * @param    WP_REST_Server   $server     Server instance.
	 * @param    WP_REST_Request  $request    Request used to generate the response.
	 * @return   WP_REST_Response             The response.
	 */
	public function post_dispatch( $result, $server, $request ) {
		// Only handle our API requests
		if ( strpos( $request->get_route(), '/' . $this->namespace ) !== 0 ) {
			return $result;
		}

		// Log the response
		$this->logger->log_response( $request, $result );

		// Add API headers
		$result->header( 'X-API-Version', '1.0' );
		$result->header( 'X-Rate-Limit-Remaining', $this->rate_limiter->get_remaining_requests( $request ) );

		return $result;
	}

	/**
	 * Authenticate API request.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response            The response.
	 */
	public function authenticate( $request ) {
		$api_key = $request->get_param( 'api_key' );
		
		$user = $this->validate_api_key( $api_key );
		
		if ( ! $user ) {
			return new WP_Error( 'invalid_api_key', 'Invalid API key', array( 'status' => 401 ) );
		}

		return rest_ensure_response( array(
			'authenticated' => true,
			'user_id'       => $user->ID,
			'permissions'   => $this->get_api_key_permissions( $api_key ),
		) );
	}

	/**
	 * Get API status.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    The request object.
	 * @return   WP_REST_Response            The response.
	 */
	public function get_api_status( $request ) {
		return rest_ensure_response( array(
			'status'    => 'active',
			'version'   => '1.0',
			'endpoints' => array_keys( $this->controllers ),
			'timestamp' => current_time( 'timestamp' ),
		) );
	}

	/**
	 * Check if current request is a REST API request.
	 *
	 * @since    1.0.0
	 * @return   bool    True if REST API request, false otherwise.
	 */
	private function is_rest_api_request() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		global $wp_rewrite;
		if ( null === $wp_rewrite ) {
			$wp_rewrite = new WP_Rewrite();
		}

		$rest_url = wp_parse_url( site_url( $wp_rewrite->rest_prefix ) );
		$current_url = wp_parse_url( add_query_arg( array() ) );

		return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
	}

	/**
	 * Get API key from request.
	 *
	 * @since    1.0.0
	 * @return   string|null    API key or null if not found.
	 */
	private function get_api_key_from_request() {
		// Check Authorization header
		$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
		
		if ( ! empty( $auth_header ) && strpos( $auth_header, 'Bearer ' ) === 0 ) {
			return substr( $auth_header, 7 );
		}

		// Check X-API-Key header
		if ( isset( $_SERVER['HTTP_X_API_KEY'] ) ) {
			return sanitize_text_field( $_SERVER['HTTP_X_API_KEY'] );
		}

		// Check query parameter
		if ( isset( $_GET['api_key'] ) ) {
			return sanitize_text_field( $_GET['api_key'] );
		}

		return null;
	}

	/**
	 * Validate API key and return associated user.
	 *
	 * @since    1.0.0
	 * @param    string    $api_key    API key to validate.
	 * @return   WP_User|null          User object or null if invalid.
	 */
	private function validate_api_key( $api_key ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_api_keys';
		
		$key_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE api_key = %s AND is_active = 1",
			$api_key
		) );

		if ( ! $key_data ) {
			return null;
		}

		// Check if key is expired
		if ( $key_data->expires_at && strtotime( $key_data->expires_at ) < time() ) {
			return null;
		}

		// Update last used timestamp
		$wpdb->update(
			$table_name,
			array( 'last_used_at' => current_time( 'mysql' ) ),
			array( 'id' => $key_data->id ),
			array( '%s' ),
			array( '%d' )
		);

		return get_user_by( 'id', $key_data->user_id );
	}

	/**
	 * Get API key permissions.
	 *
	 * @since    1.0.0
	 * @param    string    $api_key    API key.
	 * @return   array                 Permissions array.
	 */
	private function get_api_key_permissions( $api_key ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_api_keys';
		
		$key_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT permissions FROM $table_name WHERE api_key = %s AND is_active = 1",
			$api_key
		) );

		if ( ! $key_data ) {
			return array();
		}

		return json_decode( $key_data->permissions, true ) ?: array();
	}

	/**
	 * Get the API namespace.
	 *
	 * @since    1.0.0
	 * @return   string    API namespace.
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * Get a specific controller.
	 *
	 * @since    1.0.0
	 * @param    string    $controller_name    Controller name.
	 * @return   object|null                   Controller instance or null.
	 */
	public function get_controller( $controller_name ) {
		return isset( $this->controllers[ $controller_name ] ) ? $this->controllers[ $controller_name ] : null;
	}
}
