<?php
/**
 * SkyLearn Billing Pro API Logger
 *
 * Handles logging of API requests and responses for monitoring and debugging.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 */

/**
 * API Logger Class
 *
 * Logs API requests and responses to the database for analysis and debugging.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_API_Logger {

	/**
	 * Log an API request.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    The request object.
	 */
	public function log_request( $request ) {
		// Get request details
		$endpoint = $request->get_route();
		$method = $request->get_method();
		$params = $request->get_params();
		$api_key_id = $this->get_api_key_id_from_request( $request );
		$user_id = get_current_user_id();
		$ip_address = $this->get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

		// Store request start time for response time calculation
		$request_start_time = microtime( true );
		$request->set_param( '_slbp_start_time', $request_start_time );
		$request->set_param( '_slbp_api_key_id', $api_key_id );

		// Sanitize sensitive data from params
		$sanitized_params = $this->sanitize_request_params( $params );

		// Store request details for later use in response logging
		$request->set_param( '_slbp_log_data', array(
			'endpoint'      => $endpoint,
			'method'        => $method,
			'api_key_id'    => $api_key_id,
			'user_id'       => $user_id ?: null,
			'ip_address'    => $ip_address,
			'user_agent'    => $user_agent,
			'params'        => $sanitized_params,
			'start_time'    => $request_start_time,
		) );
	}

	/**
	 * Log an API response.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request     $request    The request object.
	 * @param    WP_REST_Response    $response   The response object.
	 */
	public function log_response( $request, $response ) {
		global $wpdb;

		// Get logged request data
		$log_data = $request->get_param( '_slbp_log_data' );
		$start_time = $request->get_param( '_slbp_start_time' );

		if ( ! $log_data || ! $start_time ) {
			return;
		}

		// Calculate response time
		$response_time = microtime( true ) - $start_time;

		// Get response details
		$response_code = $response->get_status();

		// Insert log entry
		$table_name = $wpdb->prefix . 'slbp_api_logs';
		
		$wpdb->insert(
			$table_name,
			array(
				'api_key_id'      => $log_data['api_key_id'],
				'user_id'         => $log_data['user_id'],
				'endpoint'        => $log_data['endpoint'],
				'method'          => $log_data['method'],
				'request_params'  => wp_json_encode( $log_data['params'] ),
				'response_code'   => $response_code,
				'response_time'   => $response_time,
				'ip_address'      => $log_data['ip_address'],
				'user_agent'      => $log_data['user_agent'],
				'created_at'      => current_time( 'mysql' ),
			),
			array(
				'%d',  // api_key_id
				'%d',  // user_id
				'%s',  // endpoint
				'%s',  // method
				'%s',  // request_params
				'%d',  // response_code
				'%f',  // response_time
				'%s',  // ip_address
				'%s',  // user_agent
				'%s',  // created_at
			)
		);

		// Clean up old logs if needed
		$this->cleanup_old_logs();
	}

	/**
	 * Get API key ID from request.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    The request object.
	 * @return   int|null                       API key ID or null.
	 */
	private function get_api_key_id_from_request( $request ) {
		$api_key = $this->get_api_key_from_request( $request );
		
		if ( ! $api_key ) {
			return null;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_api_keys';
		
		$key_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM $table_name WHERE api_key = %s",
			$api_key
		) );

		return $key_data ? $key_data->id : null;
	}

	/**
	 * Get API key from request.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    The request object.
	 * @return   string|null                    API key or null.
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
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '127.0.0.1'; // Fallback
	}

	/**
	 * Sanitize request parameters for logging.
	 *
	 * @since    1.0.0
	 * @param    array    $params    Request parameters.
	 * @return   array               Sanitized parameters.
	 */
	private function sanitize_request_params( $params ) {
		$sensitive_keys = array(
			'password',
			'api_key',
			'secret',
			'token',
			'credit_card',
			'card_number',
			'cvv',
			'ssn',
		);

		array_walk_recursive( $params, function( &$value, $key ) use ( $sensitive_keys ) {
			if ( in_array( strtolower( $key ), $sensitive_keys ) ) {
				$value = '[REDACTED]';
			}
		} );

		return $params;
	}

	/**
	 * Clean up old API logs.
	 *
	 * @since    1.0.0
	 */
	private function cleanup_old_logs() {
		// Only run cleanup occasionally to avoid performance impact
		if ( random_int( 1, 100 ) > 5 ) {
			return;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_api_logs';
		$retention_days = apply_filters( 'slbp_api_log_retention_days', 30 );

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
			$retention_days
		) );
	}

	/**
	 * Get API usage statistics.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Usage statistics.
	 */
	public function get_usage_stats( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'start_date' => date( 'Y-m-d', strtotime( '-30 days' ) ),
			'end_date'   => date( 'Y-m-d' ),
			'api_key_id' => null,
			'user_id'    => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = $wpdb->prefix . 'slbp_api_logs';
		$where_clauses = array( '1=1' );
		$where_values = array();

		// Date range
		$where_clauses[] = 'created_at >= %s';
		$where_values[] = $args['start_date'] . ' 00:00:00';
		
		$where_clauses[] = 'created_at <= %s';
		$where_values[] = $args['end_date'] . ' 23:59:59';

		// API key filter
		if ( $args['api_key_id'] ) {
			$where_clauses[] = 'api_key_id = %d';
			$where_values[] = $args['api_key_id'];
		}

		// User filter
		if ( $args['user_id'] ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[] = $args['user_id'];
		}

		$where_sql = implode( ' AND ', $where_clauses );

		// Total requests
		$total_requests = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE $where_sql",
			$where_values
		) );

		// Success rate
		$success_requests = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE $where_sql AND response_code < 400",
			$where_values
		) );

		// Average response time
		$avg_response_time = $wpdb->get_var( $wpdb->prepare(
			"SELECT AVG(response_time) FROM $table_name WHERE $where_sql",
			$where_values
		) );

		// Most used endpoints
		$top_endpoints = $wpdb->get_results( $wpdb->prepare(
			"SELECT endpoint, COUNT(*) as count FROM $table_name WHERE $where_sql GROUP BY endpoint ORDER BY count DESC LIMIT 10",
			$where_values
		) );

		// Requests by day
		$daily_stats = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(created_at) as date, COUNT(*) as requests 
			 FROM $table_name WHERE $where_sql 
			 GROUP BY DATE(created_at) 
			 ORDER BY date",
			$where_values
		) );

		return array(
			'total_requests'     => (int) $total_requests,
			'success_requests'   => (int) $success_requests,
			'success_rate'       => $total_requests > 0 ? ( $success_requests / $total_requests ) * 100 : 0,
			'avg_response_time'  => (float) $avg_response_time,
			'top_endpoints'      => $top_endpoints,
			'daily_stats'        => $daily_stats,
		);
	}

	/**
	 * Get error logs.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Error logs.
	 */
	public function get_error_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'start_date' => date( 'Y-m-d', strtotime( '-7 days' ) ),
			'end_date'   => date( 'Y-m-d' ),
			'limit'      => 100,
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = $wpdb->prefix . 'slbp_api_logs';

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name 
			 WHERE response_code >= 400 
			 AND created_at >= %s 
			 AND created_at <= %s 
			 ORDER BY created_at DESC 
			 LIMIT %d",
			$args['start_date'] . ' 00:00:00',
			$args['end_date'] . ' 23:59:59',
			$args['limit']
		) );
	}
}