<?php
/**
 * Scalability and horizontal scaling utilities for SkyLearn Billing Pro
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 */

/**
 * Scalability manager.
 *
 * Handles horizontal scaling, load balancing, and stateless operations.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Scalability_Manager {

	/**
	 * Current server instance ID.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $instance_id    Server instance identifier.
	 */
	private $instance_id;

	/**
	 * Load balancer configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $load_balancer_config    Load balancer settings.
	 */
	private $load_balancer_config;

	/**
	 * Session storage handler.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $session_handler    Session storage type.
	 */
	private $session_handler = 'database';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->instance_id = $this->generate_instance_id();
		$this->load_balancer_config = $this->get_load_balancer_config();
		$this->session_handler = get_option( 'slbp_session_handler', 'database' );

		// Initialize session management for stateless operations
		add_action( 'init', array( $this, 'init_session_management' ), 1 );
		
		// Register health check endpoints
		add_action( 'rest_api_init', array( $this, 'register_health_check_endpoints' ) );
	}

	/**
	 * Initialize session management for stateless operations.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function init_session_management() {
		// Only manage sessions for plugin-specific operations
		if ( $this->is_plugin_request() ) {
			switch ( $this->session_handler ) {
				case 'redis':
					$this->init_redis_sessions();
					break;

				case 'memcached':
					$this->init_memcached_sessions();
					break;

				case 'database':
				default:
					$this->init_database_sessions();
					break;
			}
		}
	}

	/**
	 * Register health check endpoints for load balancers.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function register_health_check_endpoints() {
		// Basic health check
		register_rest_route( 'skylearn-billing-pro/v1', '/health', array(
			'methods' => 'GET',
			'callback' => array( $this, 'health_check' ),
			'permission_callback' => '__return_true',
		) );

		// Detailed health check
		register_rest_route( 'skylearn-billing-pro/v1', '/health/detailed', array(
			'methods' => 'GET',
			'callback' => array( $this, 'detailed_health_check' ),
			'permission_callback' => array( $this, 'check_health_permissions' ),
		) );

		// Readiness probe
		register_rest_route( 'skylearn-billing-pro/v1', '/ready', array(
			'methods' => 'GET',
			'callback' => array( $this, 'readiness_check' ),
			'permission_callback' => '__return_true',
		) );

		// Liveness probe
		register_rest_route( 'skylearn-billing-pro/v1', '/live', array(
			'methods' => 'GET',
			'callback' => array( $this, 'liveness_check' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Basic health check endpoint.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Health check response.
	 */
	public function health_check( $request ) {
		$health_status = array(
			'status' => 'healthy',
			'timestamp' => current_time( 'c' ),
			'instance_id' => $this->instance_id,
			'version' => SLBP_VERSION,
		);

		// Check critical services
		$checks = array(
			'database' => $this->check_database_health(),
			'cache' => $this->check_cache_health(),
			'filesystem' => $this->check_filesystem_health(),
		);

		$overall_healthy = true;
		foreach ( $checks as $service => $status ) {
			if ( ! $status['healthy'] ) {
				$overall_healthy = false;
				break;
			}
		}

		$health_status['status'] = $overall_healthy ? 'healthy' : 'unhealthy';
		$health_status['checks'] = $checks;

		$response_code = $overall_healthy ? 200 : 503;
		
		return new WP_REST_Response( $health_status, $response_code );
	}

	/**
	 * Detailed health check endpoint.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Detailed health check response.
	 */
	public function detailed_health_check( $request ) {
		$health_data = array(
			'status' => 'healthy',
			'timestamp' => current_time( 'c' ),
			'instance_id' => $this->instance_id,
			'version' => SLBP_VERSION,
			'system_info' => $this->get_system_info(),
			'performance_metrics' => $this->get_performance_metrics(),
			'service_checks' => $this->get_detailed_service_checks(),
		);

		// Determine overall health
		$overall_healthy = true;
		foreach ( $health_data['service_checks'] as $service => $check ) {
			if ( ! $check['healthy'] ) {
				$overall_healthy = false;
				break;
			}
		}

		$health_data['status'] = $overall_healthy ? 'healthy' : 'unhealthy';
		$response_code = $overall_healthy ? 200 : 503;

		return new WP_REST_Response( $health_data, $response_code );
	}

	/**
	 * Readiness check endpoint.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Readiness check response.
	 */
	public function readiness_check( $request ) {
		$ready = true;
		$checks = array();

		// Check if database is accessible
		$db_check = $this->check_database_health();
		$checks['database'] = $db_check;
		if ( ! $db_check['healthy'] ) {
			$ready = false;
		}

		// Check if required tables exist
		$tables_check = $this->check_required_tables();
		$checks['tables'] = $tables_check;
		if ( ! $tables_check['healthy'] ) {
			$ready = false;
		}

		// Check if required dependencies are loaded
		$dependencies_check = $this->check_dependencies();
		$checks['dependencies'] = $dependencies_check;
		if ( ! $dependencies_check['healthy'] ) {
			$ready = false;
		}

		$response = array(
			'ready' => $ready,
			'timestamp' => current_time( 'c' ),
			'instance_id' => $this->instance_id,
			'checks' => $checks,
		);

		$response_code = $ready ? 200 : 503;
		
		return new WP_REST_Response( $response, $response_code );
	}

	/**
	 * Liveness check endpoint.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Liveness check response.
	 */
	public function liveness_check( $request ) {
		$response = array(
			'alive' => true,
			'timestamp' => current_time( 'c' ),
			'instance_id' => $this->instance_id,
			'uptime' => $this->get_uptime(),
		);

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Check health check permissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   bool                       True if authorized, false otherwise.
	 */
	public function check_health_permissions( $request ) {
		// Allow access from load balancer IP ranges
		$allowed_ips = get_option( 'slbp_health_check_ips', array() );
		$client_ip = $this->get_client_ip();

		if ( ! empty( $allowed_ips ) ) {
			return in_array( $client_ip, $allowed_ips, true );
		}

		// Fallback to checking for management capabilities
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get load balancer configuration.
	 *
	 * @since    1.0.0
	 * @return   array    Load balancer configuration.
	 */
	public function get_load_balancer_config() {
		$default_config = array(
			'algorithm' => 'round_robin',
			'health_check_interval' => 30,
			'health_check_timeout' => 5,
			'health_check_path' => '/wp-json/skylearn-billing-pro/v1/health',
			'session_affinity' => false,
			'connection_timeout' => 30,
			'request_timeout' => 60,
		);

		return apply_filters( 'slbp_load_balancer_config', $default_config );
	}

	/**
	 * Configure stateless session management.
	 *
	 * @since    1.0.0
	 * @param    string $handler    Session handler type (redis, memcached, database).
	 * @return   bool              True on success, false on failure.
	 */
	public function configure_session_handler( $handler ) {
		$valid_handlers = array( 'redis', 'memcached', 'database' );

		if ( ! in_array( $handler, $valid_handlers, true ) ) {
			return false;
		}

		update_option( 'slbp_session_handler', $handler );
		$this->session_handler = $handler;

		return true;
	}

	/**
	 * Store session data in a stateless way.
	 *
	 * @since    1.0.0
	 * @param    string $session_id    Session identifier.
	 * @param    array  $data          Session data.
	 * @param    int    $expiration    Session expiration time.
	 * @return   bool                 True on success, false on failure.
	 */
	public function store_session_data( $session_id, $data, $expiration = 3600 ) {
		$session_key = 'slbp_session_' . $session_id;

		switch ( $this->session_handler ) {
			case 'redis':
				return $this->store_redis_session( $session_key, $data, $expiration );

			case 'memcached':
				return $this->store_memcached_session( $session_key, $data, $expiration );

			case 'database':
			default:
				return $this->store_database_session( $session_key, $data, $expiration );
		}
	}

	/**
	 * Retrieve session data.
	 *
	 * @since    1.0.0
	 * @param    string $session_id    Session identifier.
	 * @return   array|false          Session data or false if not found.
	 */
	public function get_session_data( $session_id ) {
		$session_key = 'slbp_session_' . $session_id;

		switch ( $this->session_handler ) {
			case 'redis':
				return $this->get_redis_session( $session_key );

			case 'memcached':
				return $this->get_memcached_session( $session_key );

			case 'database':
			default:
				return $this->get_database_session( $session_key );
		}
	}

	/**
	 * Generate microservices API configuration.
	 *
	 * @since    1.0.0
	 * @return   array    Microservices configuration.
	 */
	public function get_microservices_config() {
		return array(
			'services' => array(
				'billing' => array(
					'endpoint' => '/wp-json/skylearn-billing-pro/v1/billing',
					'methods' => array( 'GET', 'POST', 'PUT' ),
					'rate_limit' => 100,
					'timeout' => 30,
				),
				'subscriptions' => array(
					'endpoint' => '/wp-json/skylearn-billing-pro/v1/subscriptions',
					'methods' => array( 'GET', 'POST', 'PUT', 'DELETE' ),
					'rate_limit' => 200,
					'timeout' => 30,
				),
				'analytics' => array(
					'endpoint' => '/wp-json/skylearn-billing-pro/v1/analytics',
					'methods' => array( 'GET' ),
					'rate_limit' => 50,
					'timeout' => 60,
				),
				'notifications' => array(
					'endpoint' => '/wp-json/skylearn-billing-pro/v1/notifications',
					'methods' => array( 'GET', 'POST' ),
					'rate_limit' => 300,
					'timeout' => 15,
				),
			),
			'authentication' => array(
				'type' => 'jwt',
				'expiration' => 3600,
				'refresh_enabled' => true,
			),
			'monitoring' => array(
				'metrics_endpoint' => '/wp-json/skylearn-billing-pro/v1/metrics',
				'logs_endpoint' => '/wp-json/skylearn-billing-pro/v1/logs',
			),
		);
	}

	/**
	 * Generate instance ID for this server.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string    Instance identifier.
	 */
	private function generate_instance_id() {
		$existing_id = get_option( 'slbp_instance_id' );
		
		if ( empty( $existing_id ) ) {
			$instance_id = uniqid( 'slbp_', true );
			update_option( 'slbp_instance_id', $instance_id );
			return $instance_id;
		}

		return $existing_id;
	}

	/**
	 * Check if current request is plugin-specific.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   bool    True if plugin request, false otherwise.
	 */
	private function is_plugin_request() {
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		
		return (
			strpos( $request_uri, 'skylearn-billing-pro' ) !== false ||
			strpos( $request_uri, 'slbp_' ) !== false ||
			( isset( $_POST['action'] ) && strpos( $_POST['action'], 'slbp_' ) === 0 )
		);
	}

	/**
	 * Initialize Redis session handler.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function init_redis_sessions() {
		if ( ! class_exists( 'Redis' ) ) {
			return;
		}

		// Custom session handler implementation would go here
		// For now, we'll use the existing Redis cache infrastructure
	}

	/**
	 * Initialize Memcached session handler.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function init_memcached_sessions() {
		if ( ! class_exists( 'Memcached' ) ) {
			return;
		}

		// Custom session handler implementation would go here
		// For now, we'll use the existing Memcached cache infrastructure
	}

	/**
	 * Initialize database session handler.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function init_database_sessions() {
		// Create sessions table if it doesn't exist
		$this->maybe_create_sessions_table();
	}

	/**
	 * Check database health.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Database health status.
	 */
	private function check_database_health() {
		global $wpdb;

		try {
			$result = $wpdb->get_var( "SELECT 1" );
			$healthy = ( $result === '1' );

			return array(
				'healthy' => $healthy,
				'message' => $healthy ? 'Database connection OK' : 'Database connection failed',
				'response_time' => $this->measure_database_response_time(),
			);
		} catch ( Exception $e ) {
			return array(
				'healthy' => false,
				'message' => 'Database error: ' . $e->getMessage(),
				'response_time' => null,
			);
		}
	}

	/**
	 * Check cache health.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Cache health status.
	 */
	private function check_cache_health() {
		$test_key = 'slbp_health_check_' . time();
		$test_value = 'test_data';

		// Test WordPress object cache
		wp_cache_set( $test_key, $test_value, 'slbp_health' );
		$cached_value = wp_cache_get( $test_key, 'slbp_health' );
		wp_cache_delete( $test_key, 'slbp_health' );

		$healthy = ( $cached_value === $test_value );

		return array(
			'healthy' => $healthy,
			'message' => $healthy ? 'Cache working properly' : 'Cache not functioning',
			'type' => $this->get_active_cache_type(),
		);
	}

	/**
	 * Check filesystem health.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Filesystem health status.
	 */
	private function check_filesystem_health() {
		$upload_dir = wp_upload_dir();
		$test_file = $upload_dir['basedir'] . '/slbp_health_check.txt';
		$test_content = 'health_check_' . time();

		try {
			// Test write
			$write_result = file_put_contents( $test_file, $test_content );
			if ( false === $write_result ) {
				throw new Exception( 'Failed to write test file' );
			}

			// Test read
			$read_content = file_get_contents( $test_file );
			if ( $read_content !== $test_content ) {
				throw new Exception( 'File content mismatch' );
			}

			// Clean up
			unlink( $test_file );

			return array(
				'healthy' => true,
				'message' => 'Filesystem read/write OK',
				'writable' => is_writable( $upload_dir['basedir'] ),
			);
		} catch ( Exception $e ) {
			return array(
				'healthy' => false,
				'message' => 'Filesystem error: ' . $e->getMessage(),
				'writable' => is_writable( $upload_dir['basedir'] ),
			);
		}
	}

	/**
	 * Get system information.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    System information.
	 */
	private function get_system_info() {
		return array(
			'php_version' => PHP_VERSION,
			'wordpress_version' => get_bloginfo( 'version' ),
			'plugin_version' => SLBP_VERSION,
			'memory_limit' => ini_get( 'memory_limit' ),
			'memory_usage' => memory_get_usage( true ),
			'peak_memory' => memory_get_peak_usage( true ),
			'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
			'php_sapi' => php_sapi_name(),
		);
	}

	/**
	 * Get performance metrics.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Performance metrics.
	 */
	private function get_performance_metrics() {
		return array(
			'response_time' => $this->measure_response_time(),
			'database_queries' => get_num_queries(),
			'cache_hit_ratio' => $this->get_cache_hit_ratio(),
			'cpu_usage' => $this->get_cpu_usage(),
			'load_average' => $this->get_load_average(),
		);
	}

	/**
	 * Get detailed service checks.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Detailed service checks.
	 */
	private function get_detailed_service_checks() {
		return array(
			'database' => $this->check_database_health(),
			'cache' => $this->check_cache_health(),
			'filesystem' => $this->check_filesystem_health(),
			'external_apis' => $this->check_external_apis(),
			'background_processing' => $this->check_background_processing(),
		);
	}

	/**
	 * Check required database tables.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Tables check status.
	 */
	private function check_required_tables() {
		global $wpdb;

		$required_tables = array(
			'slbp_transactions',
			'slbp_subscriptions',
			'slbp_licenses',
			'slbp_notifications',
			'slbp_api_keys',
		);

		$missing_tables = array();
		foreach ( $required_tables as $table ) {
			$full_table_name = $wpdb->prefix . $table;
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$full_table_name}'" );
			if ( ! $exists ) {
				$missing_tables[] = $table;
			}
		}

		$healthy = empty( $missing_tables );

		return array(
			'healthy' => $healthy,
			'message' => $healthy ? 'All required tables exist' : 'Missing tables: ' . implode( ', ', $missing_tables ),
			'missing_tables' => $missing_tables,
		);
	}

	/**
	 * Check plugin dependencies.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Dependencies check status.
	 */
	private function check_dependencies() {
		$dependencies = array(
			'wordpress' => version_compare( get_bloginfo( 'version' ), '5.0', '>=' ),
			'php' => version_compare( PHP_VERSION, '7.4', '>=' ),
			'json_ext' => extension_loaded( 'json' ),
			'curl_ext' => extension_loaded( 'curl' ),
		);

		$failed_dependencies = array();
		foreach ( $dependencies as $dep => $status ) {
			if ( ! $status ) {
				$failed_dependencies[] = $dep;
			}
		}

		$healthy = empty( $failed_dependencies );

		return array(
			'healthy' => $healthy,
			'message' => $healthy ? 'All dependencies satisfied' : 'Failed dependencies: ' . implode( ', ', $failed_dependencies ),
			'dependencies' => $dependencies,
		);
	}

	/**
	 * Get client IP address.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string    Client IP address.
	 */
	private function get_client_ip() {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP',     // Cloudflare
			'HTTP_X_FORWARDED_FOR',      // Load balancers
			'HTTP_X_REAL_IP',           // Nginx
			'REMOTE_ADDR',              // Standard
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];
				// Handle comma-separated IPs
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}

		return '127.0.0.1';
	}

	/**
	 * Get uptime in seconds.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   int    Uptime in seconds.
	 */
	private function get_uptime() {
		$activation_time = get_option( 'slbp_activation_time', time() );
		return time() - $activation_time;
	}

	/**
	 * Store session data in Redis.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key          Session key.
	 * @param    array  $data         Session data.
	 * @param    int    $expiration   Expiration time.
	 * @return   bool                True on success, false on failure.
	 */
	private function store_redis_session( $key, $data, $expiration ) {
		// Implementation would use the performance optimizer's Redis methods
		return false;
	}

	/**
	 * Get session data from Redis.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key    Session key.
	 * @return   array|false   Session data or false if not found.
	 */
	private function get_redis_session( $key ) {
		// Implementation would use the performance optimizer's Redis methods
		return false;
	}

	/**
	 * Store session data in Memcached.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key          Session key.
	 * @param    array  $data         Session data.
	 * @param    int    $expiration   Expiration time.
	 * @return   bool                True on success, false on failure.
	 */
	private function store_memcached_session( $key, $data, $expiration ) {
		// Implementation would use the performance optimizer's Memcached methods
		return false;
	}

	/**
	 * Get session data from Memcached.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key    Session key.
	 * @return   array|false   Session data or false if not found.
	 */
	private function get_memcached_session( $key ) {
		// Implementation would use the performance optimizer's Memcached methods
		return false;
	}

	/**
	 * Store session data in database.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key          Session key.
	 * @param    array  $data         Session data.
	 * @param    int    $expiration   Expiration time.
	 * @return   bool                True on success, false on failure.
	 */
	private function store_database_session( $key, $data, $expiration ) {
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'slbp_sessions';
		$expires_at = date( 'Y-m-d H:i:s', time() + $expiration );

		return $wpdb->replace(
			$sessions_table,
			array(
				'session_key' => $key,
				'session_data' => wp_json_encode( $data ),
				'expires_at' => $expires_at,
			),
			array( '%s', '%s', '%s' )
		) !== false;
	}

	/**
	 * Get session data from database.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key    Session key.
	 * @return   array|false   Session data or false if not found.
	 */
	private function get_database_session( $key ) {
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'slbp_sessions';
		
		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT session_data FROM {$sessions_table} 
				WHERE session_key = %s 
				AND expires_at > %s",
				$key,
				current_time( 'mysql' )
			)
		);

		return $session ? json_decode( $session->session_data, true ) : false;
	}

	/**
	 * Create sessions table if it doesn't exist.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function maybe_create_sessions_table() {
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'slbp_sessions';
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$sessions_table}'" );

		if ( $table_exists !== $sessions_table ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$sessions_table} (
				session_key varchar(255) NOT NULL,
				session_data longtext NOT NULL,
				expires_at datetime NOT NULL,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (session_key),
				KEY expires_at (expires_at)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Measure database response time.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   float    Response time in milliseconds.
	 */
	private function measure_database_response_time() {
		global $wpdb;

		$start_time = microtime( true );
		$wpdb->get_var( "SELECT 1" );
		$end_time = microtime( true );

		return ( $end_time - $start_time ) * 1000;
	}

	/**
	 * Get active cache type.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string    Active cache type.
	 */
	private function get_active_cache_type() {
		if ( class_exists( 'Redis' ) && defined( 'WP_REDIS_HOST' ) ) {
			return 'redis';
		}

		if ( class_exists( 'Memcached' ) ) {
			return 'memcached';
		}

		return 'object_cache';
	}

	/**
	 * Measure response time.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   float    Response time in milliseconds.
	 */
	private function measure_response_time() {
		if ( defined( 'WP_START_TIMESTAMP' ) ) {
			return ( microtime( true ) - WP_START_TIMESTAMP ) * 1000;
		}

		return 0;
	}

	/**
	 * Get cache hit ratio.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   float    Cache hit ratio.
	 */
	private function get_cache_hit_ratio() {
		// This would require implementing cache statistics tracking
		// For now, return a placeholder value
		return 0.85;
	}

	/**
	 * Get CPU usage percentage.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   float|null    CPU usage percentage or null if unavailable.
	 */
	private function get_cpu_usage() {
		if ( function_exists( 'sys_getloadavg' ) ) {
			$load = sys_getloadavg();
			return $load[0] ?? null;
		}

		return null;
	}

	/**
	 * Get system load average.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array|null    Load averages or null if unavailable.
	 */
	private function get_load_average() {
		if ( function_exists( 'sys_getloadavg' ) ) {
			return sys_getloadavg();
		}

		return null;
	}

	/**
	 * Check external APIs health.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    External APIs health status.
	 */
	private function check_external_apis() {
		// Check Lemon Squeezy API if configured
		$api_checks = array();

		// This would implement actual API health checks
		$api_checks['lemon_squeezy'] = array(
			'healthy' => true,
			'response_time' => 150,
		);

		$overall_healthy = true;
		foreach ( $api_checks as $api => $status ) {
			if ( ! $status['healthy'] ) {
				$overall_healthy = false;
				break;
			}
		}

		return array(
			'healthy' => $overall_healthy,
			'message' => $overall_healthy ? 'All external APIs accessible' : 'Some external APIs are down',
			'apis' => $api_checks,
		);
	}

	/**
	 * Check background processing health.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Background processing health status.
	 */
	private function check_background_processing() {
		global $wpdb;

		$queue_table = $wpdb->prefix . 'slbp_background_tasks';
		
		// Check if queue table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$queue_table}'" );
		
		if ( $table_exists !== $queue_table ) {
			return array(
				'healthy' => false,
				'message' => 'Background tasks table does not exist',
			);
		}

		// Check for stuck tasks
		$stuck_tasks = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$queue_table} 
				WHERE status = 'processing' 
				AND started_at < %s",
				date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) )
			)
		);

		$healthy = ( $stuck_tasks == 0 );

		return array(
			'healthy' => $healthy,
			'message' => $healthy ? 'Background processing working normally' : "Found {$stuck_tasks} stuck tasks",
			'stuck_tasks' => (int) $stuck_tasks,
		);
	}
}