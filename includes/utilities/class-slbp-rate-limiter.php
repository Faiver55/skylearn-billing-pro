<?php
/**
 * Rate limiting and throttling for SkyLearn Billing Pro
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 */

/**
 * Rate limiting and throttling manager.
 *
 * Handles API rate limiting, request throttling, and abuse prevention.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Rate_Limiter {

	/**
	 * Rate limit storage table name.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $rate_limit_table    Rate limit table name.
	 */
	private $rate_limit_table;

	/**
	 * Default rate limits configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $default_limits    Default rate limits.
	 */
	private $default_limits;

	/**
	 * Cache storage for rate limits.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $cache    Rate limit cache.
	 */
	private $cache = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->rate_limit_table = $wpdb->prefix . 'slbp_rate_limits';

		$this->default_limits = $this->get_default_limits();

		// Create rate limit table if it doesn't exist
		$this->maybe_create_rate_limit_table();

		// Hook into WordPress actions
		add_action( 'rest_api_init', array( $this, 'register_rate_limit_endpoints' ) );
		add_action( 'wp_ajax_slbp_api_request', array( $this, 'check_ajax_rate_limit' ), 1 );
		add_action( 'wp_ajax_nopriv_slbp_api_request', array( $this, 'check_ajax_rate_limit' ), 1 );

		// Add rate limiting middleware to REST API
		add_filter( 'rest_pre_dispatch', array( $this, 'check_rest_rate_limit' ), 10, 3 );
	}

	/**
	 * Register rate limiting REST API endpoints.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function register_rate_limit_endpoints() {
		// Rate limit status endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/rate-limit/status', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_rate_limit_status' ),
			'permission_callback' => array( $this, 'check_rate_limit_permissions' ),
		) );

		// Rate limit configuration endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/rate-limit/config', array(
			'methods' => array( 'GET', 'POST' ),
			'callback' => array( $this, 'manage_rate_limit_config' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );

		// Whitelist/blacklist management
		register_rest_route( 'skylearn-billing-pro/v1', '/rate-limit/whitelist', array(
			'methods' => array( 'GET', 'POST', 'DELETE' ),
			'callback' => array( $this, 'manage_whitelist' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );

		register_rest_route( 'skylearn-billing-pro/v1', '/rate-limit/blacklist', array(
			'methods' => array( 'GET', 'POST', 'DELETE' ),
			'callback' => array( $this, 'manage_blacklist' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );
	}

	/**
	 * Check rate limit for REST API requests.
	 *
	 * @since    1.0.0
	 * @param    mixed           $result   Response to replace the requested version with.
	 * @param    WP_REST_Server  $server   Server instance.
	 * @param    WP_REST_Request $request  Request used to generate the response.
	 * @return   mixed                    Original result or error response.
	 */
	public function check_rest_rate_limit( $result, $server, $request ) {
		// Only check SkyLearn Billing Pro endpoints
		$route = $request->get_route();
		if ( strpos( $route, '/skylearn-billing-pro/' ) === false ) {
			return $result;
		}

		$client_id = $this->get_client_identifier( $request );
		$endpoint = $this->normalize_endpoint( $route );

		// Check if client is blacklisted
		if ( $this->is_blacklisted( $client_id ) ) {
			return new WP_Error(
				'rate_limit_blacklisted',
				'Client is blacklisted',
				array( 'status' => 403 )
			);
		}

		// Check if client is whitelisted (skip rate limiting)
		if ( $this->is_whitelisted( $client_id ) ) {
			return $result;
		}

		// Check rate limit
		$rate_limit_check = $this->check_rate_limit( $client_id, $endpoint );

		if ( ! $rate_limit_check['allowed'] ) {
			// Add rate limit headers
			$response = new WP_REST_Response(
				array(
					'error' => 'rate_limit_exceeded',
					'message' => 'Rate limit exceeded. Try again later.',
					'retry_after' => $rate_limit_check['retry_after'],
				),
				429
			);

			$response->header( 'X-RateLimit-Limit', $rate_limit_check['limit'] );
			$response->header( 'X-RateLimit-Remaining', 0 );
			$response->header( 'X-RateLimit-Reset', time() + $rate_limit_check['retry_after'] );
			$response->header( 'Retry-After', $rate_limit_check['retry_after'] );

			return $response;
		}

		// Add rate limit headers to successful responses
		add_filter( 'rest_post_dispatch', function( $response ) use ( $rate_limit_check ) {
			if ( $response instanceof WP_REST_Response ) {
				$response->header( 'X-RateLimit-Limit', $rate_limit_check['limit'] );
				$response->header( 'X-RateLimit-Remaining', $rate_limit_check['remaining'] );
				$response->header( 'X-RateLimit-Reset', time() + $rate_limit_check['window'] );
			}
			return $response;
		}, 10, 1 );

		return $result;
	}

	/**
	 * Check rate limit for AJAX requests.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function check_ajax_rate_limit() {
		$client_id = $this->get_client_identifier();
		$endpoint = 'ajax_' . ( $_POST['action'] ?? 'unknown' );

		// Check if client is blacklisted
		if ( $this->is_blacklisted( $client_id ) ) {
			wp_die( 'Client is blacklisted', 'Rate Limit', array( 'response' => 403 ) );
		}

		// Check if client is whitelisted (skip rate limiting)
		if ( $this->is_whitelisted( $client_id ) ) {
			return;
		}

		// Check rate limit
		$rate_limit_check = $this->check_rate_limit( $client_id, $endpoint );

		if ( ! $rate_limit_check['allowed'] ) {
			wp_die( 'Rate limit exceeded. Try again later.', 'Rate Limit', array( 'response' => 429 ) );
		}
	}

	/**
	 * Check rate limit for a client and endpoint.
	 *
	 * @since    1.0.0
	 * @param    string $client_id    Client identifier.
	 * @param    string $endpoint     Endpoint identifier.
	 * @return   array               Rate limit check result.
	 */
	public function check_rate_limit( $client_id, $endpoint ) {
		$limit_config = $this->get_limit_config( $endpoint );
		$window_start = $this->get_window_start( $limit_config['window'] );
		$cache_key = $this->get_cache_key( $client_id, $endpoint, $window_start );

		// Check cache first
		if ( isset( $this->cache[ $cache_key ] ) ) {
			$current_count = $this->cache[ $cache_key ];
		} else {
			$current_count = $this->get_request_count( $client_id, $endpoint, $window_start );
			$this->cache[ $cache_key ] = $current_count;
		}

		$allowed = $current_count < $limit_config['limit'];

		if ( $allowed ) {
			// Increment counter
			$this->record_request( $client_id, $endpoint );
			$this->cache[ $cache_key ] = $current_count + 1;
		}

		return array(
			'allowed' => $allowed,
			'limit' => $limit_config['limit'],
			'remaining' => max( 0, $limit_config['limit'] - $current_count - ( $allowed ? 1 : 0 ) ),
			'window' => $limit_config['window'],
			'retry_after' => $allowed ? 0 : $this->calculate_retry_after( $window_start, $limit_config['window'] ),
		);
	}

	/**
	 * Record a request for rate limiting.
	 *
	 * @since    1.0.0
	 * @param    string $client_id    Client identifier.
	 * @param    string $endpoint     Endpoint identifier.
	 * @return   bool                True on success, false on failure.
	 */
	private function record_request( $client_id, $endpoint ) {
		global $wpdb;

		return $wpdb->insert(
			$this->rate_limit_table,
			array(
				'client_id' => $client_id,
				'endpoint' => $endpoint,
				'request_time' => current_time( 'mysql' ),
				'ip_address' => $this->get_client_ip(),
				'user_agent' => $this->get_user_agent(),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		) !== false;
	}

	/**
	 * Get request count for a time window.
	 *
	 * @since    1.0.0
	 * @param    string $client_id      Client identifier.
	 * @param    string $endpoint       Endpoint identifier.
	 * @param    string $window_start   Window start time.
	 * @return   int                   Request count.
	 */
	private function get_request_count( $client_id, $endpoint, $window_start ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->rate_limit_table} 
				WHERE client_id = %s 
				AND endpoint = %s 
				AND request_time >= %s",
				$client_id,
				$endpoint,
				$window_start
			)
		);
	}

	/**
	 * Get limit configuration for an endpoint.
	 *
	 * @since    1.0.0
	 * @param    string $endpoint    Endpoint identifier.
	 * @return   array              Limit configuration.
	 */
	private function get_limit_config( $endpoint ) {
		$custom_limits = get_option( 'slbp_rate_limits', array() );

		// Check for specific endpoint configuration
		if ( isset( $custom_limits[ $endpoint ] ) ) {
			return $custom_limits[ $endpoint ];
		}

		// Check for pattern matches
		foreach ( $custom_limits as $pattern => $config ) {
			if ( fnmatch( $pattern, $endpoint ) ) {
				return $config;
			}
		}

		// Fall back to default limits
		return $this->get_default_limit_for_endpoint( $endpoint );
	}

	/**
	 * Get default limits configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Default limits.
	 */
	private function get_default_limits() {
		return array(
			'general' => array(
				'limit' => 100,
				'window' => 3600, // 1 hour
			),
			'auth' => array(
				'limit' => 10,
				'window' => 3600, // 1 hour
			),
			'payment' => array(
				'limit' => 50,
				'window' => 3600, // 1 hour
			),
			'webhook' => array(
				'limit' => 1000,
				'window' => 3600, // 1 hour
			),
		);
	}

	/**
	 * Get default limit for specific endpoint.
	 *
	 * @since    1.0.0
	 * @param    string $endpoint    Endpoint identifier.
	 * @return   array              Default limit configuration.
	 */
	private function get_default_limit_for_endpoint( $endpoint ) {
		// Authentication endpoints
		if ( strpos( $endpoint, 'auth' ) !== false || strpos( $endpoint, 'login' ) !== false ) {
			return $this->default_limits['auth'];
		}

		// Payment endpoints
		if ( strpos( $endpoint, 'payment' ) !== false || strpos( $endpoint, 'billing' ) !== false ) {
			return $this->default_limits['payment'];
		}

		// Webhook endpoints
		if ( strpos( $endpoint, 'webhook' ) !== false ) {
			return $this->default_limits['webhook'];
		}

		// Default general limit
		return $this->default_limits['general'];
	}

	/**
	 * Get client identifier.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request|null $request    REST request object.
	 * @return   string                         Client identifier.
	 */
	private function get_client_identifier( $request = null ) {
		// Try API key first
		if ( $request && $request->get_header( 'X-API-Key' ) ) {
			return 'api_key:' . $request->get_header( 'X-API-Key' );
		}

		// Try Authorization header
		if ( $request && $request->get_header( 'Authorization' ) ) {
			$auth_header = $request->get_header( 'Authorization' );
			if ( preg_match( '/Bearer\s+(.+)/', $auth_header, $matches ) ) {
				return 'bearer:' . substr( $matches[1], 0, 32 );
			}
		}

		// Try user ID if logged in
		$current_user_id = get_current_user_id();
		if ( $current_user_id ) {
			return 'user:' . $current_user_id;
		}

		// Fall back to IP address
		return 'ip:' . $this->get_client_ip();
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
	 * Get user agent.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string    User agent string.
	 */
	private function get_user_agent() {
		return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
	}

	/**
	 * Normalize endpoint identifier.
	 *
	 * @since    1.0.0
	 * @param    string $route    REST route.
	 * @return   string          Normalized endpoint.
	 */
	private function normalize_endpoint( $route ) {
		// Remove version and base path
		$endpoint = str_replace( array( '/wp-json/skylearn-billing-pro/v1/', '/skylearn-billing-pro/v1/' ), '', $route );
		
		// Remove query parameters
		$endpoint = strtok( $endpoint, '?' );
		
		// Replace dynamic segments with placeholders
		$endpoint = preg_replace( '/\/\d+/', '/{id}', $endpoint );
		
		return $endpoint;
	}

	/**
	 * Get window start time.
	 *
	 * @since    1.0.0
	 * @param    int $window_seconds    Window size in seconds.
	 * @return   string                Window start time.
	 */
	private function get_window_start( $window_seconds ) {
		$current_time = time();
		$window_start = $current_time - ( $current_time % $window_seconds );
		return date( 'Y-m-d H:i:s', $window_start );
	}

	/**
	 * Calculate retry after time.
	 *
	 * @since    1.0.0
	 * @param    string $window_start    Window start time.
	 * @param    int    $window_seconds  Window size in seconds.
	 * @return   int                    Retry after seconds.
	 */
	private function calculate_retry_after( $window_start, $window_seconds ) {
		$window_start_timestamp = strtotime( $window_start );
		$window_end = $window_start_timestamp + $window_seconds;
		return max( 1, $window_end - time() );
	}

	/**
	 * Get cache key for rate limit data.
	 *
	 * @since    1.0.0
	 * @param    string $client_id      Client identifier.
	 * @param    string $endpoint       Endpoint identifier.
	 * @param    string $window_start   Window start time.
	 * @return   string                Cache key.
	 */
	private function get_cache_key( $client_id, $endpoint, $window_start ) {
		return md5( $client_id . ':' . $endpoint . ':' . $window_start );
	}

	/**
	 * Check if client is whitelisted.
	 *
	 * @since    1.0.0
	 * @param    string $client_id    Client identifier.
	 * @return   bool                True if whitelisted, false otherwise.
	 */
	private function is_whitelisted( $client_id ) {
		$whitelist = get_option( 'slbp_rate_limit_whitelist', array() );
		
		// Check exact match
		if ( in_array( $client_id, $whitelist, true ) ) {
			return true;
		}

		// Check IP patterns for IP-based clients
		if ( strpos( $client_id, 'ip:' ) === 0 ) {
			$ip = substr( $client_id, 3 );
			foreach ( $whitelist as $pattern ) {
				if ( $this->ip_matches_pattern( $ip, $pattern ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if client is blacklisted.
	 *
	 * @since    1.0.0
	 * @param    string $client_id    Client identifier.
	 * @return   bool                True if blacklisted, false otherwise.
	 */
	private function is_blacklisted( $client_id ) {
		$blacklist = get_option( 'slbp_rate_limit_blacklist', array() );
		
		// Check exact match
		if ( in_array( $client_id, $blacklist, true ) ) {
			return true;
		}

		// Check IP patterns for IP-based clients
		if ( strpos( $client_id, 'ip:' ) === 0 ) {
			$ip = substr( $client_id, 3 );
			foreach ( $blacklist as $pattern ) {
				if ( $this->ip_matches_pattern( $ip, $pattern ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if IP matches pattern.
	 *
	 * @since    1.0.0
	 * @param    string $ip        IP address.
	 * @param    string $pattern   IP pattern (supports CIDR and wildcards).
	 * @return   bool             True if matches, false otherwise.
	 */
	private function ip_matches_pattern( $ip, $pattern ) {
		// Exact match
		if ( $ip === $pattern ) {
			return true;
		}

		// CIDR notation
		if ( strpos( $pattern, '/' ) !== false ) {
			return $this->ip_in_range( $ip, $pattern );
		}

		// Wildcard pattern
		if ( strpos( $pattern, '*' ) !== false ) {
			$pattern = str_replace( '*', '.*', $pattern );
			return preg_match( "/^{$pattern}$/", $ip );
		}

		return false;
	}

	/**
	 * Check if IP is in CIDR range.
	 *
	 * @since    1.0.0
	 * @param    string $ip     IP address.
	 * @param    string $range  CIDR range.
	 * @return   bool          True if in range, false otherwise.
	 */
	private function ip_in_range( $ip, $range ) {
		list( $subnet, $bits ) = explode( '/', $range );
		
		$ip_long = ip2long( $ip );
		$subnet_long = ip2long( $subnet );
		$mask = -1 << ( 32 - $bits );
		
		return ( $ip_long & $mask ) === ( $subnet_long & $mask );
	}

	/**
	 * Get rate limit status.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Rate limit status response.
	 */
	public function get_rate_limit_status( $request ) {
		$client_id = $request->get_param( 'client_id' ) ?? $this->get_client_identifier( $request );
		$endpoint = $request->get_param( 'endpoint' ) ?? 'general';

		$limit_config = $this->get_limit_config( $endpoint );
		$window_start = $this->get_window_start( $limit_config['window'] );
		$current_count = $this->get_request_count( $client_id, $endpoint, $window_start );

		return new WP_REST_Response( array(
			'client_id' => $client_id,
			'endpoint' => $endpoint,
			'limit' => $limit_config['limit'],
			'remaining' => max( 0, $limit_config['limit'] - $current_count ),
			'window' => $limit_config['window'],
			'reset_time' => strtotime( $window_start ) + $limit_config['window'],
			'whitelisted' => $this->is_whitelisted( $client_id ),
			'blacklisted' => $this->is_blacklisted( $client_id ),
		) );
	}

	/**
	 * Manage rate limit configuration.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Rate limit config response.
	 */
	public function manage_rate_limit_config( $request ) {
		if ( $request->get_method() === 'POST' ) {
			$new_config = $request->get_json_params();
			
			if ( $this->validate_rate_limit_config( $new_config ) ) {
				update_option( 'slbp_rate_limits', $new_config );
				
				return new WP_REST_Response( array(
					'success' => true,
					'message' => 'Rate limit configuration updated successfully',
					'config' => $new_config,
				) );
			} else {
				return new WP_REST_Response( array(
					'success' => false,
					'message' => 'Invalid rate limit configuration',
				), 400 );
			}
		}

		$current_config = get_option( 'slbp_rate_limits', array() );
		
		return new WP_REST_Response( array(
			'config' => $current_config,
			'defaults' => $this->default_limits,
		) );
	}

	/**
	 * Manage whitelist.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Whitelist response.
	 */
	public function manage_whitelist( $request ) {
		$whitelist = get_option( 'slbp_rate_limit_whitelist', array() );

		switch ( $request->get_method() ) {
			case 'POST':
				$client_id = $request->get_param( 'client_id' );
				if ( $client_id && ! in_array( $client_id, $whitelist, true ) ) {
					$whitelist[] = $client_id;
					update_option( 'slbp_rate_limit_whitelist', $whitelist );
				}
				break;

			case 'DELETE':
				$client_id = $request->get_param( 'client_id' );
				if ( $client_id ) {
					$whitelist = array_diff( $whitelist, array( $client_id ) );
					update_option( 'slbp_rate_limit_whitelist', array_values( $whitelist ) );
				}
				break;
		}

		return new WP_REST_Response( array(
			'whitelist' => $whitelist,
		) );
	}

	/**
	 * Manage blacklist.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Blacklist response.
	 */
	public function manage_blacklist( $request ) {
		$blacklist = get_option( 'slbp_rate_limit_blacklist', array() );

		switch ( $request->get_method() ) {
			case 'POST':
				$client_id = $request->get_param( 'client_id' );
				if ( $client_id && ! in_array( $client_id, $blacklist, true ) ) {
					$blacklist[] = $client_id;
					update_option( 'slbp_rate_limit_blacklist', $blacklist );
				}
				break;

			case 'DELETE':
				$client_id = $request->get_param( 'client_id' );
				if ( $client_id ) {
					$blacklist = array_diff( $blacklist, array( $client_id ) );
					update_option( 'slbp_rate_limit_blacklist', array_values( $blacklist ) );
				}
				break;
		}

		return new WP_REST_Response( array(
			'blacklist' => $blacklist,
		) );
	}

	/**
	 * Check rate limit permissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   bool                       True if authorized, false otherwise.
	 */
	public function check_rate_limit_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check admin permissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   bool                       True if authorized, false otherwise.
	 */
	public function check_admin_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Validate rate limit configuration.
	 *
	 * @since    1.0.0
	 * @param    array $config    Rate limit configuration.
	 * @return   bool            True if valid, false otherwise.
	 */
	private function validate_rate_limit_config( $config ) {
		if ( ! is_array( $config ) ) {
			return false;
		}

		foreach ( $config as $endpoint => $limit_config ) {
			if ( ! is_array( $limit_config ) ) {
				return false;
			}

			if ( ! isset( $limit_config['limit'] ) || ! is_numeric( $limit_config['limit'] ) ) {
				return false;
			}

			if ( ! isset( $limit_config['window'] ) || ! is_numeric( $limit_config['window'] ) ) {
				return false;
			}

			if ( $limit_config['limit'] <= 0 || $limit_config['window'] <= 0 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Create rate limit table if it doesn't exist.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function maybe_create_rate_limit_table() {
		global $wpdb;

		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->rate_limit_table}'" );

		if ( $table_exists !== $this->rate_limit_table ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->rate_limit_table} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				client_id varchar(255) NOT NULL,
				endpoint varchar(255) NOT NULL,
				request_time datetime NOT NULL,
				ip_address varchar(45) NOT NULL,
				user_agent text DEFAULT NULL,
				PRIMARY KEY (id),
				KEY client_endpoint_time (client_id, endpoint, request_time),
				KEY request_time (request_time),
				KEY ip_address (ip_address)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Get rate limiting statistics.
	 *
	 * @since    1.0.0
	 * @param    string $timeframe    Timeframe for statistics (1h, 24h, 7d).
	 * @return   array               Rate limiting statistics.
	 */
	public function get_rate_limit_stats( $timeframe = '24h' ) {
		global $wpdb;

		$hours_map = array(
			'1h' => 1,
			'24h' => 24,
			'7d' => 168,
		);

		$hours = $hours_map[ $timeframe ] ?? 24;
		$start_time = date( 'Y-m-d H:i:s', strtotime( "-{$hours} hours" ) );

		// Get request counts by endpoint
		$endpoint_stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT endpoint, COUNT(*) as request_count, COUNT(DISTINCT client_id) as unique_clients
				FROM {$this->rate_limit_table} 
				WHERE request_time >= %s 
				GROUP BY endpoint 
				ORDER BY request_count DESC",
				$start_time
			),
			ARRAY_A
		);

		// Get top clients
		$client_stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT client_id, COUNT(*) as request_count
				FROM {$this->rate_limit_table} 
				WHERE request_time >= %s 
				GROUP BY client_id 
				ORDER BY request_count DESC 
				LIMIT 10",
				$start_time
			),
			ARRAY_A
		);

		// Get blocked requests (this would require additional tracking)
		$blocked_requests = 0; // Placeholder

		return array(
			'timeframe' => $timeframe,
			'total_requests' => array_sum( array_column( $endpoint_stats, 'request_count' ) ),
			'blocked_requests' => $blocked_requests,
			'unique_clients' => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT client_id) FROM {$this->rate_limit_table} WHERE request_time >= %s",
					$start_time
				)
			),
			'endpoint_stats' => $endpoint_stats,
			'top_clients' => $client_stats,
			'whitelist_count' => count( get_option( 'slbp_rate_limit_whitelist', array() ) ),
			'blacklist_count' => count( get_option( 'slbp_rate_limit_blacklist', array() ) ),
		);
	}

	/**
	 * Clean up old rate limit records.
	 *
	 * @since    1.0.0
	 * @param    int $days    Number of days to retain records.
	 * @return   int         Number of records deleted.
	 */
	public function cleanup_old_records( $days = 7 ) {
		global $wpdb;

		$cleanup_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->rate_limit_table} WHERE request_time < %s",
				$cleanup_date
			)
		);
	}

	/**
	 * Auto-ban clients based on behavior patterns.
	 *
	 * @since    1.0.0
	 * @return   array    Auto-ban results.
	 */
	public function auto_ban_abusive_clients() {
		global $wpdb;

		$banned_clients = array();
		$threshold_config = get_option( 'slbp_auto_ban_config', array(
			'requests_per_minute' => 100,
			'failed_auth_attempts' => 50,
			'timeframe_hours' => 1,
		) );

		$start_time = date( 'Y-m-d H:i:s', strtotime( "-{$threshold_config['timeframe_hours']} hours" ) );

		// Find clients with excessive requests
		$excessive_clients = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT client_id, COUNT(*) as request_count
				FROM {$this->rate_limit_table} 
				WHERE request_time >= %s 
				GROUP BY client_id 
				HAVING request_count > %d",
				$start_time,
				$threshold_config['requests_per_minute'] * 60 * $threshold_config['timeframe_hours']
			),
			ARRAY_A
		);

		$blacklist = get_option( 'slbp_rate_limit_blacklist', array() );

		foreach ( $excessive_clients as $client ) {
			if ( ! in_array( $client['client_id'], $blacklist, true ) ) {
				$blacklist[] = $client['client_id'];
				$banned_clients[] = array(
					'client_id' => $client['client_id'],
					'reason' => 'excessive_requests',
					'request_count' => $client['request_count'],
				);
			}
		}

		update_option( 'slbp_rate_limit_blacklist', $blacklist );

		return $banned_clients;
	}
}