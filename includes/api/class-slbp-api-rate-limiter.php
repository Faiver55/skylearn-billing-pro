<?php
/**
 * SkyLearn Billing Pro API Rate Limiter
 *
 * Handles rate limiting for API requests to prevent abuse.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 */

/**
 * API Rate Limiter Class
 *
 * Implements token bucket algorithm for rate limiting API requests.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_API_Rate_Limiter {

	/**
	 * Default rate limit per hour.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $default_limit    Default rate limit.
	 */
	private $default_limit = 1000;

	/**
	 * Rate limit window in seconds.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $window    Rate limit window.
	 */
	private $window = 3600; // 1 hour

	/**
	 * Check rate limit for a request.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    The request object.
	 * @return   bool|WP_Error                  True if within limit, WP_Error if exceeded.
	 */
	public function check_rate_limit( $request ) {
		$identifier = $this->get_request_identifier( $request );
		$limit = $this->get_rate_limit_for_request( $request );
		
		$current_count = $this->get_current_request_count( $identifier );
		
		if ( $current_count >= $limit ) {
			return new WP_Error(
				'rate_limit_exceeded',
				'API rate limit exceeded. Please try again later.',
				array( 'status' => 429 )
			);
		}

		// Increment the counter
		$this->increment_request_count( $identifier );

		return true;
	}

	/**
	 * Get remaining requests for an identifier.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    The request object.
	 * @return   int                            Remaining requests.
	 */
	public function get_remaining_requests( $request ) {
		$identifier = $this->get_request_identifier( $request );
		$limit = $this->get_rate_limit_for_request( $request );
		$current_count = $this->get_current_request_count( $identifier );

		return max( 0, $limit - $current_count );
	}

	/**
	 * Get request identifier for rate limiting.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    The request object.
	 * @return   string                         Request identifier.
	 */
	private function get_request_identifier( $request ) {
		// Try to get API key first
		$api_key = $this->get_api_key_from_request( $request );
		
		if ( $api_key ) {
			return 'api_key_' . hash( 'sha256', $api_key );
		}

		// Fall back to IP address
		$ip_address = $this->get_client_ip();
		return 'ip_' . hash( 'sha256', $ip_address );
	}

	/**
	 * Get rate limit for a specific request.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    The request object.
	 * @return   int                            Rate limit.
	 */
	private function get_rate_limit_for_request( $request ) {
		$api_key = $this->get_api_key_from_request( $request );
		
		if ( $api_key ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'slbp_api_keys';
			
			$key_data = $wpdb->get_row( $wpdb->prepare(
				"SELECT rate_limit FROM $table_name WHERE api_key = %s AND is_active = 1",
				$api_key
			) );

			if ( $key_data && $key_data->rate_limit > 0 ) {
				return $key_data->rate_limit;
			}
		}

		return $this->default_limit;
	}

	/**
	 * Get current request count for an identifier.
	 *
	 * @since    1.0.0
	 * @param    string    $identifier    Request identifier.
	 * @return   int                      Current request count.
	 */
	private function get_current_request_count( $identifier ) {
		$cache_key = 'slbp_rate_limit_' . $identifier;
		$count = wp_cache_get( $cache_key );

		if ( false === $count ) {
			$count = 0;
		}

		return (int) $count;
	}

	/**
	 * Increment request count for an identifier.
	 *
	 * @since    1.0.0
	 * @param    string    $identifier    Request identifier.
	 */
	private function increment_request_count( $identifier ) {
		$cache_key = 'slbp_rate_limit_' . $identifier;
		$current_count = $this->get_current_request_count( $identifier );
		$new_count = $current_count + 1;

		wp_cache_set( $cache_key, $new_count, '', $this->window );
	}

	/**
	 * Get API key from request.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    The request object.
	 * @return   string|null                    API key or null if not found.
	 */
	private function get_api_key_from_request( $request ) {
		// Check Authorization header
		$auth_header = $request->get_header( 'authorization' );
		
		if ( ! empty( $auth_header ) && strpos( $auth_header, 'Bearer ' ) === 0 ) {
			return substr( $auth_header, 7 );
		}

		// Check X-API-Key header
		$api_key_header = $request->get_header( 'x-api-key' );
		if ( ! empty( $api_key_header ) ) {
			return $api_key_header;
		}

		// Check query parameter
		$api_key_param = $request->get_param( 'api_key' );
		if ( ! empty( $api_key_param ) ) {
			return $api_key_param;
		}

		return null;
	}

	/**
	 * Get client IP address.
	 *
	 * @since    1.0.0
	 * @return   string    Client IP address.
	 */
	private function get_client_ip() {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];
				
				// Handle comma-separated IPs (from proxies)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}

				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return '127.0.0.1'; // Fallback
	}

	/**
	 * Reset rate limit for an identifier.
	 *
	 * @since    1.0.0
	 * @param    string    $identifier    Request identifier.
	 */
	public function reset_rate_limit( $identifier ) {
		$cache_key = 'slbp_rate_limit_' . $identifier;
		wp_cache_delete( $cache_key );
	}

	/**
	 * Get rate limit info for an identifier.
	 *
	 * @since    1.0.0
	 * @param    string    $identifier    Request identifier.
	 * @param    int       $limit         Rate limit.
	 * @return   array                    Rate limit info.
	 */
	public function get_rate_limit_info( $identifier, $limit ) {
		$current_count = $this->get_current_request_count( $identifier );
		$remaining = max( 0, $limit - $current_count );
		$reset_time = time() + $this->window;

		return array(
			'limit'     => $limit,
			'remaining' => $remaining,
			'used'      => $current_count,
			'reset'     => $reset_time,
			'window'    => $this->window,
		);
	}
}