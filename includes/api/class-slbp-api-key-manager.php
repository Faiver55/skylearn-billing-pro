<?php
/**
 * SkyLearn Billing Pro API Key Manager
 *
 * Handles creation, management, and validation of API keys.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 */

/**
 * API Key Manager Class
 *
 * Manages API keys for authentication and authorization.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/api
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_API_Key_Manager {

	/**
	 * Generate a new API key.
	 *
	 * @since    1.0.0
	 * @param    int      $user_id       User ID.
	 * @param    string   $name          Key name.
	 * @param    array    $permissions   Permissions array.
	 * @param    array    $args          Additional arguments.
	 * @return   string|WP_Error         API key or WP_Error on failure.
	 */
	public function create_api_key( $user_id, $name, $permissions = array(), $args = array() ) {
		global $wpdb;

		// Validate user
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'invalid_user', 'Invalid user ID' );
		}

		// Validate permissions
		$valid_permissions = $this->get_valid_permissions();
		foreach ( $permissions as $permission ) {
			if ( ! in_array( $permission, $valid_permissions ) ) {
				return new WP_Error( 'invalid_permission', "Invalid permission: $permission" );
			}
		}

		// Generate API key
		$api_key = $this->generate_api_key();

		// Prepare data
		$defaults = array(
			'rate_limit'  => 1000,
			'expires_at'  => null,
		);
		$args = wp_parse_args( $args, $defaults );

		$table_name = $wpdb->prefix . 'slbp_api_keys';

		// Insert API key
		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'     => $user_id,
				'name'        => sanitize_text_field( $name ),
				'api_key'     => $api_key,
				'permissions' => wp_json_encode( $permissions ),
				'rate_limit'  => (int) $args['rate_limit'],
				'expires_at'  => $args['expires_at'],
				'created_at'  => current_time( 'mysql' ),
			),
			array(
				'%d',  // user_id
				'%s',  // name
				'%s',  // api_key
				'%s',  // permissions
				'%d',  // rate_limit
				'%s',  // expires_at
				'%s',  // created_at
			)
		);

		if ( $result === false ) {
			return new WP_Error( 'creation_failed', 'Failed to create API key' );
		}

		// Log the creation
		do_action( 'slbp_api_key_created', $api_key, $user_id, $permissions );

		return $api_key;
	}

	/**
	 * Get API keys for a user.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID.
	 * @return   array              Array of API keys.
	 */
	public function get_user_api_keys( $user_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_api_keys';

		$keys = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, user_id, name, CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 4)) as masked_key,
			        permissions, rate_limit, is_active, last_used_at, expires_at, created_at
			 FROM $table_name 
			 WHERE user_id = %d 
			 ORDER BY created_at DESC",
			$user_id
		) );

		foreach ( $keys as &$key ) {
			$key->permissions = json_decode( $key->permissions, true ) ?: array();
		}

		return $keys;
	}

	/**
	 * Get API key details.
	 *
	 * @since    1.0.0
	 * @param    int    $key_id    API key ID.
	 * @return   object|null       API key object or null.
	 */
	public function get_api_key( $key_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_api_keys';

		$key = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$key_id
		) );

		if ( $key ) {
			$key->permissions = json_decode( $key->permissions, true ) ?: array();
		}

		return $key;
	}

	/**
	 * Update API key.
	 *
	 * @since    1.0.0
	 * @param    int      $key_id    API key ID.
	 * @param    array    $data      Data to update.
	 * @return   bool|WP_Error       True on success, WP_Error on failure.
	 */
	public function update_api_key( $key_id, $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_api_keys';

		// Get existing key
		$existing_key = $this->get_api_key( $key_id );
		if ( ! $existing_key ) {
			return new WP_Error( 'key_not_found', 'API key not found' );
		}

		// Validate permissions if provided
		if ( isset( $data['permissions'] ) ) {
			$valid_permissions = $this->get_valid_permissions();
			foreach ( $data['permissions'] as $permission ) {
				if ( ! in_array( $permission, $valid_permissions ) ) {
					return new WP_Error( 'invalid_permission', "Invalid permission: $permission" );
				}
			}
			$data['permissions'] = wp_json_encode( $data['permissions'] );
		}

		// Prepare update data
		$update_data = array();
		$update_format = array();

		$allowed_fields = array( 'name', 'permissions', 'rate_limit', 'is_active', 'expires_at' );
		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$update_data[ $field ] = $data[ $field ];
				$update_format[] = in_array( $field, array( 'rate_limit', 'is_active' ) ) ? '%d' : '%s';
			}
		}

		if ( empty( $update_data ) ) {
			return new WP_Error( 'no_data', 'No valid data provided for update' );
		}

		$result = $wpdb->update(
			$table_name,
			$update_data,
			array( 'id' => $key_id ),
			$update_format,
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'update_failed', 'Failed to update API key' );
		}

		do_action( 'slbp_api_key_updated', $key_id, $data );

		return true;
	}

	/**
	 * Delete API key.
	 *
	 * @since    1.0.0
	 * @param    int    $key_id    API key ID.
	 * @return   bool|WP_Error     True on success, WP_Error on failure.
	 */
	public function delete_api_key( $key_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_api_keys';

		// Get existing key for logging
		$existing_key = $this->get_api_key( $key_id );
		if ( ! $existing_key ) {
			return new WP_Error( 'key_not_found', 'API key not found' );
		}

		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $key_id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'deletion_failed', 'Failed to delete API key' );
		}

		do_action( 'slbp_api_key_deleted', $key_id, $existing_key );

		return true;
	}

	/**
	 * Validate API key.
	 *
	 * @since    1.0.0
	 * @param    string    $api_key    API key to validate.
	 * @return   object|null           Key data or null if invalid.
	 */
	public function validate_api_key( $api_key ) {
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

		$key_data->permissions = json_decode( $key_data->permissions, true ) ?: array();

		return $key_data;
	}

	/**
	 * Generate a secure API key.
	 *
	 * @since    1.0.0
	 * @return   string    Generated API key.
	 */
	protected function generate_api_key() {
		$prefix = 'slbp_';
		$random_bytes = random_bytes( 32 );
		$key = $prefix . bin2hex( $random_bytes );

		return $key;
	}

	/**
	 * Get valid permissions.
	 *
	 * @since    1.0.0
	 * @return   array    Array of valid permissions.
	 */
	public function get_valid_permissions() {
		$permissions = array(
			'read',
			'write',
			'admin',
			'read_billing',
			'write_billing',
			'read_subscriptions',
			'write_subscriptions',
			'read_users',
			'write_users',
			'read_courses',
			'write_courses',
			'read_analytics',
			'manage_webhooks',
		);

		return apply_filters( 'slbp_api_valid_permissions', $permissions );
	}

	/**
	 * Get permission descriptions.
	 *
	 * @since    1.0.0
	 * @return   array    Array of permission descriptions.
	 */
	public function get_permission_descriptions() {
		return array(
			'read'                => __( 'Read access to all resources', 'skylearn-billing-pro' ),
			'write'               => __( 'Write access to all resources', 'skylearn-billing-pro' ),
			'admin'               => __( 'Full administrative access', 'skylearn-billing-pro' ),
			'read_billing'        => __( 'Read billing data (invoices, transactions)', 'skylearn-billing-pro' ),
			'write_billing'       => __( 'Modify billing data (refunds, etc.)', 'skylearn-billing-pro' ),
			'read_subscriptions'  => __( 'Read subscription data', 'skylearn-billing-pro' ),
			'write_subscriptions' => __( 'Modify subscriptions (cancel, update)', 'skylearn-billing-pro' ),
			'read_users'          => __( 'Read user and enrollment data', 'skylearn-billing-pro' ),
			'write_users'         => __( 'Modify user enrollments', 'skylearn-billing-pro' ),
			'read_courses'        => __( 'Read course and product data', 'skylearn-billing-pro' ),
			'write_courses'       => __( 'Modify course and product mappings', 'skylearn-billing-pro' ),
			'read_analytics'      => __( 'Read analytics and reports', 'skylearn-billing-pro' ),
			'manage_webhooks'     => __( 'Create and manage webhooks', 'skylearn-billing-pro' ),
		);
	}

	/**
	 * Get API usage statistics for a key.
	 *
	 * @since    1.0.0
	 * @param    int      $key_id    API key ID.
	 * @param    array    $args      Query arguments.
	 * @return   array               Usage statistics.
	 */
	public function get_key_usage_stats( $key_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'start_date' => date( 'Y-m-d', strtotime( '-30 days' ) ),
			'end_date'   => date( 'Y-m-d' ),
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = $wpdb->prefix . 'slbp_api_logs';

		// Total requests
		$total_requests = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name 
			 WHERE api_key_id = %d 
			 AND created_at >= %s 
			 AND created_at <= %s",
			$key_id,
			$args['start_date'] . ' 00:00:00',
			$args['end_date'] . ' 23:59:59'
		) );

		// Success rate
		$success_requests = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name 
			 WHERE api_key_id = %d 
			 AND response_code < 400 
			 AND created_at >= %s 
			 AND created_at <= %s",
			$key_id,
			$args['start_date'] . ' 00:00:00',
			$args['end_date'] . ' 23:59:59'
		) );

		// Most used endpoints
		$top_endpoints = $wpdb->get_results( $wpdb->prepare(
			"SELECT endpoint, COUNT(*) as count FROM $table_name 
			 WHERE api_key_id = %d 
			 AND created_at >= %s 
			 AND created_at <= %s 
			 GROUP BY endpoint 
			 ORDER BY count DESC 
			 LIMIT 5",
			$key_id,
			$args['start_date'] . ' 00:00:00',
			$args['end_date'] . ' 23:59:59'
		) );

		return array(
			'total_requests'   => (int) $total_requests,
			'success_requests' => (int) $success_requests,
			'success_rate'     => $total_requests > 0 ? ( $success_requests / $total_requests ) * 100 : 0,
			'top_endpoints'    => $top_endpoints,
		);
	}

	/**
	 * Clean up expired API keys.
	 *
	 * @since    1.0.0
	 */
	public function cleanup_expired_keys() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_api_keys';

		$expired_keys = $wpdb->get_results(
			"SELECT id, user_id FROM $table_name 
			 WHERE expires_at IS NOT NULL 
			 AND expires_at < NOW() 
			 AND is_active = 1"
		);

		foreach ( $expired_keys as $key ) {
			$wpdb->update(
				$table_name,
				array( 'is_active' => 0 ),
				array( 'id' => $key->id ),
				array( '%d' ),
				array( '%d' )
			);

			do_action( 'slbp_api_key_expired', $key->id, $key->user_id );
		}

		return count( $expired_keys );
	}
}