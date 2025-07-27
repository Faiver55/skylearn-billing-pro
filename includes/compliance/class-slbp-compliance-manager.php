<?php
/**
 * The GDPR/CCPA compliance functionality of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/compliance
 */

/**
 * The GDPR/CCPA compliance functionality of the plugin.
 *
 * Provides GDPR and CCPA compliance features including data export,
 * data deletion, consent management, and retention policies.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/compliance
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Compliance_Manager {

	/**
	 * The audit logger instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Audit_Logger    $audit_logger    The audit logger instance.
	 */
	private $audit_logger;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->audit_logger = new SLBP_Audit_Logger();
		$this->init_hooks();
	}

	/**
	 * Initialize compliance hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// WordPress privacy hooks
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_data_exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_data_erasers' ) );

		// AJAX handlers for compliance actions
		add_action( 'wp_ajax_slbp_export_user_data', array( $this, 'ajax_export_user_data' ) );
		add_action( 'wp_ajax_slbp_delete_user_data', array( $this, 'ajax_delete_user_data' ) );
		add_action( 'wp_ajax_slbp_update_consent', array( $this, 'ajax_update_consent' ) );

		// Scheduled data retention cleanup
		add_action( 'slbp_data_retention_cleanup', array( $this, 'cleanup_expired_data' ) );
	}

	/**
	 * Register data exporters with WordPress privacy framework.
	 *
	 * @since    1.0.0
	 * @param    array    $exporters    Existing exporters.
	 * @return   array                  Updated exporters array.
	 */
	public function register_data_exporters( $exporters ) {
		$exporters['slbp-billing-data'] = array(
			'exporter_friendly_name' => __( 'SkyLearn Billing Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'export_billing_data' ),
		);

		$exporters['slbp-subscription-data'] = array(
			'exporter_friendly_name' => __( 'SkyLearn Subscription Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'export_subscription_data' ),
		);

		$exporters['slbp-enrollment-data'] = array(
			'exporter_friendly_name' => __( 'SkyLearn Enrollment Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'export_enrollment_data' ),
		);

		$exporters['slbp-audit-data'] = array(
			'exporter_friendly_name' => __( 'SkyLearn Audit Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'export_audit_data' ),
		);

		return $exporters;
	}

	/**
	 * Register data erasers with WordPress privacy framework.
	 *
	 * @since    1.0.0
	 * @param    array    $erasers    Existing erasers.
	 * @return   array                Updated erasers array.
	 */
	public function register_data_erasers( $erasers ) {
		$erasers['slbp-billing-data'] = array(
			'eraser_friendly_name' => __( 'SkyLearn Billing Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'erase_billing_data' ),
		);

		$erasers['slbp-subscription-data'] = array(
			'eraser_friendly_name' => __( 'SkyLearn Subscription Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'erase_subscription_data' ),
		);

		$erasers['slbp-enrollment-data'] = array(
			'eraser_friendly_name' => __( 'SkyLearn Enrollment Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'erase_enrollment_data' ),
		);

		$erasers['slbp-audit-data'] = array(
			'eraser_friendly_name' => __( 'SkyLearn Audit Data', 'skylearn-billing-pro' ),
			'callback' => array( $this, 'erase_audit_data' ),
		);

		return $erasers;
	}

	/**
	 * Export billing data for a user.
	 *
	 * @since    1.0.0
	 * @param    string    $email_address    User email address.
	 * @param    int       $page            Page number.
	 * @return   array                      Export data.
	 */
	public function export_billing_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		global $wpdb;
		$transactions_table = $wpdb->prefix . 'slbp_transactions';
		
		$per_page = 100;
		$offset = ( $page - 1 ) * $per_page;

		$transactions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$transactions_table} WHERE user_id = %d LIMIT %d OFFSET %d",
				$user->ID,
				$per_page,
				$offset
			)
		);

		$export_items = array();

		foreach ( $transactions as $transaction ) {
			$data = array(
				array(
					'name' => __( 'Transaction ID', 'skylearn-billing-pro' ),
					'value' => $transaction->transaction_id,
				),
				array(
					'name' => __( 'Amount', 'skylearn-billing-pro' ),
					'value' => $transaction->amount . ' ' . $transaction->currency,
				),
				array(
					'name' => __( 'Status', 'skylearn-billing-pro' ),
					'value' => $transaction->status,
				),
				array(
					'name' => __( 'Gateway', 'skylearn-billing-pro' ),
					'value' => $transaction->gateway,
				),
				array(
					'name' => __( 'Created Date', 'skylearn-billing-pro' ),
					'value' => $transaction->created_at,
				),
			);

			$export_items[] = array(
				'group_id' => 'slbp-billing',
				'group_label' => __( 'SkyLearn Billing Data', 'skylearn-billing-pro' ),
				'item_id' => 'transaction-' . $transaction->id,
				'data' => $data,
			);
		}

		$done = count( $transactions ) < $per_page;

		// Log the export action
		$this->audit_logger->log_event(
			'compliance',
			'data_exported',
			$user->ID,
			array(
				'export_type' => 'billing_data',
				'records_count' => count( $transactions ),
				'page' => $page,
			),
			'info'
		);

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Export subscription data for a user.
	 *
	 * @since    1.0.0
	 * @param    string    $email_address    User email address.
	 * @param    int       $page            Page number.
	 * @return   array                      Export data.
	 */
	public function export_subscription_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		global $wpdb;
		$subscriptions_table = $wpdb->prefix . 'slbp_subscriptions';
		
		$per_page = 100;
		$offset = ( $page - 1 ) * $per_page;

		$subscriptions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$subscriptions_table} WHERE user_id = %d LIMIT %d OFFSET %d",
				$user->ID,
				$per_page,
				$offset
			)
		);

		$export_items = array();

		foreach ( $subscriptions as $subscription ) {
			$data = array(
				array(
					'name' => __( 'Subscription ID', 'skylearn-billing-pro' ),
					'value' => $subscription->subscription_id,
				),
				array(
					'name' => __( 'Product ID', 'skylearn-billing-pro' ),
					'value' => $subscription->product_id,
				),
				array(
					'name' => __( 'Status', 'skylearn-billing-pro' ),
					'value' => $subscription->status,
				),
				array(
					'name' => __( 'Plan Name', 'skylearn-billing-pro' ),
					'value' => $subscription->plan_name,
				),
				array(
					'name' => __( 'Created Date', 'skylearn-billing-pro' ),
					'value' => $subscription->created_at,
				),
			);

			$export_items[] = array(
				'group_id' => 'slbp-subscriptions',
				'group_label' => __( 'SkyLearn Subscription Data', 'skylearn-billing-pro' ),
				'item_id' => 'subscription-' . $subscription->id,
				'data' => $data,
			);
		}

		$done = count( $subscriptions ) < $per_page;

		// Log the export action
		$this->audit_logger->log_event(
			'compliance',
			'data_exported',
			$user->ID,
			array(
				'export_type' => 'subscription_data',
				'records_count' => count( $subscriptions ),
				'page' => $page,
			),
			'info'
		);

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Export enrollment data for a user.
	 *
	 * @since    1.0.0
	 * @param    string    $email_address    User email address.
	 * @param    int       $page            Page number.
	 * @return   array                      Export data.
	 */
	public function export_enrollment_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		// Get LearnDash enrollments if available
		$enrolled_courses = array();
		if ( function_exists( 'learndash_user_get_enrolled_courses' ) ) {
			$enrolled_courses = learndash_user_get_enrolled_courses( $user->ID );
		}

		$export_items = array();

		foreach ( $enrolled_courses as $course_id ) {
			$course = get_post( $course_id );
			if ( ! $course ) {
				continue;
			}

			$enrollment_date = get_user_meta( $user->ID, 'course_' . $course_id . '_access_from', true );
			
			$data = array(
				array(
					'name' => __( 'Course ID', 'skylearn-billing-pro' ),
					'value' => $course_id,
				),
				array(
					'name' => __( 'Course Title', 'skylearn-billing-pro' ),
					'value' => $course->post_title,
				),
				array(
					'name' => __( 'Enrollment Date', 'skylearn-billing-pro' ),
					'value' => $enrollment_date ? date( 'Y-m-d H:i:s', $enrollment_date ) : __( 'Unknown', 'skylearn-billing-pro' ),
				),
			);

			$export_items[] = array(
				'group_id' => 'slbp-enrollments',
				'group_label' => __( 'SkyLearn Enrollment Data', 'skylearn-billing-pro' ),
				'item_id' => 'enrollment-' . $course_id,
				'data' => $data,
			);
		}

		// Log the export action
		$this->audit_logger->log_event(
			'compliance',
			'data_exported',
			$user->ID,
			array(
				'export_type' => 'enrollment_data',
				'records_count' => count( $enrolled_courses ),
				'page' => $page,
			),
			'info'
		);

		return array(
			'data' => $export_items,
			'done' => true, // All enrollments returned in one page
		);
	}

	/**
	 * Export audit data for a user.
	 *
	 * @since    1.0.0
	 * @param    string    $email_address    User email address.
	 * @param    int       $page            Page number.
	 * @return   array                      Export data.
	 */
	public function export_audit_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$per_page = 100;
		$offset = ( $page - 1 ) * $per_page;

		$logs_result = $this->audit_logger->get_logs( array(
			'user_id' => $user->ID,
			'limit' => $per_page,
			'offset' => $offset,
		) );

		$export_items = array();

		foreach ( $logs_result['logs'] as $log ) {
			$data = array(
				array(
					'name' => __( 'Event Type', 'skylearn-billing-pro' ),
					'value' => $log->event_type,
				),
				array(
					'name' => __( 'Action', 'skylearn-billing-pro' ),
					'value' => $log->action,
				),
				array(
					'name' => __( 'IP Address', 'skylearn-billing-pro' ),
					'value' => $log->user_ip,
				),
				array(
					'name' => __( 'Severity', 'skylearn-billing-pro' ),
					'value' => $log->severity,
				),
				array(
					'name' => __( 'Date', 'skylearn-billing-pro' ),
					'value' => $log->created_at,
				),
			);

			$export_items[] = array(
				'group_id' => 'slbp-audit',
				'group_label' => __( 'SkyLearn Audit Data', 'skylearn-billing-pro' ),
				'item_id' => 'audit-' . $log->id,
				'data' => $data,
			);
		}

		$done = count( $logs_result['logs'] ) < $per_page;

		// Log the export action
		$this->audit_logger->log_event(
			'compliance',
			'data_exported',
			$user->ID,
			array(
				'export_type' => 'audit_data',
				'records_count' => count( $logs_result['logs'] ),
				'page' => $page,
			),
			'info'
		);

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Erase billing data for a user.
	 *
	 * @since    1.0.0
	 * @param    string    $email_address    User email address.
	 * @param    int       $page            Page number.
	 * @return   array                      Erasure result.
	 */
	public function erase_billing_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		
		if ( ! $user ) {
			return array(
				'items_removed' => false,
				'items_retained' => false,
				'messages' => array(),
				'done' => true,
			);
		}

		global $wpdb;
		$transactions_table = $wpdb->prefix . 'slbp_transactions';

		// Check if we can anonymize instead of delete (for regulatory compliance)
		$retention_policy = get_option( 'slbp_data_retention_policy', 'anonymize' );

		if ( 'anonymize' === $retention_policy ) {
			// Anonymize billing data instead of deleting
			$anonymized = $wpdb->update(
				$transactions_table,
				array(
					'user_email' => 'anonymized@example.com',
					'billing_address' => wp_json_encode( array( 'anonymized' => true ) ),
				),
				array( 'user_id' => $user->ID ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			$this->audit_logger->log_event(
				'compliance',
				'data_anonymized',
				$user->ID,
				array(
					'data_type' => 'billing_data',
					'records_affected' => $anonymized,
				),
				'info'
			);

			return array(
				'items_removed' => true,
				'items_retained' => false,
				'messages' => array( __( 'Billing data has been anonymized.', 'skylearn-billing-pro' ) ),
				'done' => true,
			);
		} else {
			// Actually delete the data
			$deleted = $wpdb->delete(
				$transactions_table,
				array( 'user_id' => $user->ID ),
				array( '%d' )
			);

			$this->audit_logger->log_event(
				'compliance',
				'data_deleted',
				$user->ID,
				array(
					'data_type' => 'billing_data',
					'records_deleted' => $deleted,
				),
				'warning'
			);

			return array(
				'items_removed' => $deleted > 0,
				'items_retained' => false,
				'messages' => array( sprintf( __( '%d billing records deleted.', 'skylearn-billing-pro' ), $deleted ) ),
				'done' => true,
			);
		}
	}

	/**
	 * Erase subscription data for a user.
	 *
	 * @since    1.0.0
	 * @param    string    $email_address    User email address.
	 * @param    int       $page            Page number.
	 * @return   array                      Erasure result.
	 */
	public function erase_subscription_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		
		if ( ! $user ) {
			return array(
				'items_removed' => false,
				'items_retained' => false,
				'messages' => array(),
				'done' => true,
			);
		}

		global $wpdb;
		$subscriptions_table = $wpdb->prefix . 'slbp_subscriptions';

		// Cancel active subscriptions before deletion
		$active_subscriptions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$subscriptions_table} WHERE user_id = %d AND status = 'active'",
				$user->ID
			)
		);

		foreach ( $active_subscriptions as $subscription ) {
			// Cancel subscription with payment gateway
			do_action( 'slbp_cancel_subscription', $subscription->subscription_id, $user->ID );
		}

		// Delete subscription records
		$deleted = $wpdb->delete(
			$subscriptions_table,
			array( 'user_id' => $user->ID ),
			array( '%d' )
		);

		$this->audit_logger->log_event(
			'compliance',
			'data_deleted',
			$user->ID,
			array(
				'data_type' => 'subscription_data',
				'records_deleted' => $deleted,
				'active_subscriptions_cancelled' => count( $active_subscriptions ),
			),
			'warning'
		);

		return array(
			'items_removed' => $deleted > 0,
			'items_retained' => false,
			'messages' => array( sprintf( __( '%d subscription records deleted.', 'skylearn-billing-pro' ), $deleted ) ),
			'done' => true,
		);
	}

	/**
	 * Erase enrollment data for a user.
	 *
	 * @since    1.0.0
	 * @param    string    $email_address    User email address.
	 * @param    int       $page            Page number.
	 * @return   array                      Erasure result.
	 */
	public function erase_enrollment_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		
		if ( ! $user ) {
			return array(
				'items_removed' => false,
				'items_retained' => false,
				'messages' => array(),
				'done' => true,
			);
		}

		$removed_count = 0;

		// Remove LearnDash enrollments if available
		if ( function_exists( 'learndash_user_get_enrolled_courses' ) ) {
			$enrolled_courses = learndash_user_get_enrolled_courses( $user->ID );
			
			foreach ( $enrolled_courses as $course_id ) {
				ld_update_course_access( $user->ID, $course_id, true ); // Remove access
				$removed_count++;
			}
		}

		$this->audit_logger->log_event(
			'compliance',
			'data_deleted',
			$user->ID,
			array(
				'data_type' => 'enrollment_data',
				'courses_unenrolled' => $removed_count,
			),
			'warning'
		);

		return array(
			'items_removed' => $removed_count > 0,
			'items_retained' => false,
			'messages' => array( sprintf( __( 'Unenrolled from %d courses.', 'skylearn-billing-pro' ), $removed_count ) ),
			'done' => true,
		);
	}

	/**
	 * Erase audit data for a user (with retention consideration).
	 *
	 * @since    1.0.0
	 * @param    string    $email_address    User email address.
	 * @param    int       $page            Page number.
	 * @return   array                      Erasure result.
	 */
	public function erase_audit_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		
		if ( ! $user ) {
			return array(
				'items_removed' => false,
				'items_retained' => false,
				'messages' => array(),
				'done' => true,
			);
		}

		// Audit logs may need to be retained for legal/compliance reasons
		$audit_retention_policy = get_option( 'slbp_audit_retention_policy', 'retain' );

		if ( 'retain' === $audit_retention_policy ) {
			return array(
				'items_removed' => false,
				'items_retained' => true,
				'messages' => array( __( 'Audit logs retained for compliance purposes.', 'skylearn-billing-pro' ) ),
				'done' => true,
			);
		}

		// If allowed to delete, anonymize the logs
		global $wpdb;
		$audit_table = $wpdb->prefix . 'slbp_audit_logs';

		$anonymized = $wpdb->update(
			$audit_table,
			array(
				'user_ip' => '0.0.0.0',
				'user_agent' => 'anonymized',
			),
			array( 'user_id' => $user->ID ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$this->audit_logger->log_event(
			'compliance',
			'data_anonymized',
			0, // Use system user for this log
			array(
				'data_type' => 'audit_data',
				'original_user_id' => $user->ID,
				'records_anonymized' => $anonymized,
			),
			'info'
		);

		return array(
			'items_removed' => true,
			'items_retained' => false,
			'messages' => array( sprintf( __( '%d audit records anonymized.', 'skylearn-billing-pro' ), $anonymized ) ),
			'done' => true,
		);
	}

	/**
	 * Update user consent preferences.
	 *
	 * @since    1.0.0
	 * @param    int      $user_id         User ID.
	 * @param    array    $consent_data    Consent preferences.
	 * @return   bool                     Success status.
	 */
	public function update_user_consent( $user_id, $consent_data ) {
		$current_consent = get_user_meta( $user_id, 'slbp_consent_preferences', true );
		if ( ! is_array( $current_consent ) ) {
			$current_consent = array();
		}

		// Update consent data with timestamp
		$consent_data['updated_at'] = current_time( 'mysql' );
		$consent_data['ip_address'] = $this->get_user_ip();

		update_user_meta( $user_id, 'slbp_consent_preferences', $consent_data );

		// Log consent update
		$this->audit_logger->log_event(
			'compliance',
			'consent_updated',
			$user_id,
			array(
				'previous_consent' => $current_consent,
				'new_consent' => $consent_data,
			),
			'info'
		);

		return true;
	}

	/**
	 * Get user consent preferences.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID.
	 * @return   array              Consent preferences.
	 */
	public function get_user_consent( $user_id ) {
		$consent = get_user_meta( $user_id, 'slbp_consent_preferences', true );
		
		if ( ! is_array( $consent ) ) {
			return array(
				'marketing' => false,
				'analytics' => false,
				'data_processing' => false,
				'updated_at' => '',
			);
		}

		return $consent;
	}

	/**
	 * AJAX handler for exporting user data.
	 *
	 * @since    1.0.0
	 */
	public function ajax_export_user_data() {
		// Verify nonce and capabilities
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_compliance_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Security check failed.' );
		}

		$user_id = intval( $_POST['user_id'] );
		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			wp_send_json_error( __( 'User not found.', 'skylearn-billing-pro' ) );
		}

		// Generate comprehensive export
		$export_data = array(
			'billing' => $this->export_billing_data( $user->user_email ),
			'subscriptions' => $this->export_subscription_data( $user->user_email ),
			'enrollments' => $this->export_enrollment_data( $user->user_email ),
			'audit' => $this->export_audit_data( $user->user_email ),
		);

		// Create JSON file
		$upload_dir = wp_upload_dir();
		$filename = 'slbp-user-data-' . $user_id . '-' . date( 'Y-m-d-H-i-s' ) . '.json';
		$file_path = $upload_dir['basedir'] . '/slbp-exports/' . $filename;

		wp_mkdir_p( dirname( $file_path ) );
		
		if ( file_put_contents( $file_path, wp_json_encode( $export_data, JSON_PRETTY_PRINT ) ) ) {
			$download_url = $upload_dir['baseurl'] . '/slbp-exports/' . $filename;
			wp_send_json_success( array( 'download_url' => $download_url ) );
		} else {
			wp_send_json_error( __( 'Failed to create export file.', 'skylearn-billing-pro' ) );
		}
	}

	/**
	 * AJAX handler for updating consent.
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_consent() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_compliance_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'User not logged in.', 'skylearn-billing-pro' ) );
		}

		$consent_data = array(
			'marketing' => isset( $_POST['marketing'] ) && $_POST['marketing'],
			'analytics' => isset( $_POST['analytics'] ) && $_POST['analytics'],
			'data_processing' => isset( $_POST['data_processing'] ) && $_POST['data_processing'],
		);

		if ( $this->update_user_consent( $user_id, $consent_data ) ) {
			wp_send_json_success( __( 'Consent preferences updated.', 'skylearn-billing-pro' ) );
		} else {
			wp_send_json_error( __( 'Failed to update consent preferences.', 'skylearn-billing-pro' ) );
		}
	}

	/**
	 * Clean up expired data based on retention policies.
	 *
	 * @since    1.0.0
	 */
	public function cleanup_expired_data() {
		$retention_settings = get_option( 'slbp_data_retention_settings', array() );

		// Clean up transaction data if retention period is set
		if ( isset( $retention_settings['transaction_retention_days'] ) && $retention_settings['transaction_retention_days'] > 0 ) {
			$this->cleanup_old_transactions( $retention_settings['transaction_retention_days'] );
		}

		// Clean up old export files
		$this->cleanup_old_export_files();

		$this->audit_logger->log_event(
			'system',
			'data_retention_cleanup',
			0,
			array( 'retention_settings' => $retention_settings ),
			'info'
		);
	}

	/**
	 * Clean up old transaction records.
	 *
	 * @since    1.0.0
	 * @param    int    $retention_days    Number of days to retain data.
	 */
	private function cleanup_old_transactions( $retention_days ) {
		global $wpdb;
		$transactions_table = $wpdb->prefix . 'slbp_transactions';

		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$transactions_table} WHERE created_at < %s AND status = 'completed'",
				$cutoff_date
			)
		);

		if ( $deleted ) {
			$this->audit_logger->log_event(
				'system',
				'old_transactions_deleted',
				0,
				array(
					'deleted_count' => $deleted,
					'cutoff_date' => $cutoff_date,
				),
				'info'
			);
		}
	}

	/**
	 * Clean up old export files.
	 *
	 * @since    1.0.0
	 */
	private function cleanup_old_export_files() {
		$upload_dir = wp_upload_dir();
		$exports_dir = $upload_dir['basedir'] . '/slbp-exports/';

		if ( ! is_dir( $exports_dir ) ) {
			return;
		}

		$cutoff_time = time() - ( 7 * 24 * 60 * 60 ); // 7 days ago
		$files = glob( $exports_dir . '*' );
		$deleted_count = 0;

		foreach ( $files as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < $cutoff_time ) {
				unlink( $file );
				$deleted_count++;
			}
		}

		if ( $deleted_count ) {
			$this->audit_logger->log_event(
				'system',
				'old_export_files_deleted',
				0,
				array( 'deleted_count' => $deleted_count ),
				'info'
			);
		}
	}

	/**
	 * Get the user's IP address.
	 *
	 * @since    1.0.0
	 * @return   string    User IP address.
	 */
	private function get_user_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$ip = $_SERVER[ $key ];
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip )[0];
				}
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}