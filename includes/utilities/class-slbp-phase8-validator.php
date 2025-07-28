<?php
/**
 * Phase 8 Feature Validation Script
 * 
 * This script validates that all Phase 8 classes and features are properly implemented.
 * Run this script to ensure Phase 8 functionality is working correctly.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	die( 'This script must be run within WordPress.' );
}

/**
 * Phase 8 validation class.
 */
class SLBP_Phase8_Validator {

	/**
	 * Run all validation checks.
	 */
	public function run_validation() {
		echo "<h2>SkyLearn Billing Pro - Phase 8 Validation</h2>\n";
		
		$this->check_classes();
		$this->check_database_tables();
		$this->check_cron_jobs();
		$this->check_capabilities();
		
		echo "<h3>Validation Complete</h3>\n";
	}

	/**
	 * Check if all Phase 8 classes exist and are loadable.
	 */
	private function check_classes() {
		echo "<h3>Class Validation</h3>\n";
		
		$required_classes = array(
			'SLBP_Advanced_Reports' => 'Advanced reporting system',
			'SLBP_External_Analytics' => 'External analytics integrations',
		);

		foreach ( $required_classes as $class => $description ) {
			if ( class_exists( $class ) ) {
				echo "✅ <strong>{$class}</strong>: {$description}<br>\n";
			} else {
				echo "❌ <strong>{$class}</strong>: Missing - {$description}<br>\n";
			}
		}
		echo "<br>\n";
	}

	/**
	 * Check if required database tables exist.
	 */
	private function check_database_tables() {
		global $wpdb;
		
		echo "<h3>Database Table Validation</h3>\n";
		
		$required_tables = array(
			// No Phase 8 specific tables required after security/compliance removal
		);

		foreach ( $required_tables as $table => $description ) {
			$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			
			if ( $table_exists ) {
				$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
				echo "✅ <strong>{$table}</strong>: {$description} ({$row_count} records)<br>\n";
			} else {
				echo "❌ <strong>{$table}</strong>: Missing - {$description}<br>\n";
			}
		}
		echo "<br>\n";
	}

	/**
	 * Check if Phase 8 cron jobs are scheduled.
	 */
	private function check_cron_jobs() {
		echo "<h3>Scheduled Task Validation</h3>\n";
		
		$required_crons = array(
			'slbp_daily_cleanup' => 'Daily audit log cleanup',
			'slbp_daily_security_audit' => 'Daily security audit',
			'slbp_external_analytics_sync' => 'External analytics sync',
			'slbp_data_retention_cleanup' => 'Data retention cleanup',
		);

		foreach ( $required_crons as $cron => $description ) {
			$next_run = wp_next_scheduled( $cron );
			
			if ( $next_run ) {
				$next_run_date = date( 'Y-m-d H:i:s', $next_run );
				echo "✅ <strong>{$cron}</strong>: {$description} (next: {$next_run_date})<br>\n";
			} else {
				echo "❌ <strong>{$cron}</strong>: Not scheduled - {$description}<br>\n";
			}
		}
		echo "<br>\n";
	}

	/**
	 * Check Phase 8 functionality capabilities.
	 */
	private function check_capabilities() {
		echo "<h3>Functionality Validation</h3>\n";
		
		// Test advanced reports
		try {
			if ( class_exists( 'SLBP_Advanced_Reports' ) ) {
				$advanced_reports = new SLBP_Advanced_Reports();
				$report_types = $advanced_reports->get_report_types();
				
				if ( is_array( $report_types ) && count( $report_types ) > 0 ) {
					$count = count( $report_types );
					echo "✅ <strong>Advanced Reports</strong>: Working correctly ({$count} report types available)<br>\n";
				} else {
					echo "❌ <strong>Advanced Reports</strong>: No report types available<br>\n";
				}
			}
		} catch ( Exception $e ) {
			echo "❌ <strong>Advanced Reports</strong>: Error - " . $e->getMessage() . "<br>\n";
		}

		echo "<br>\n";
	}
}

// Run validation if accessed directly (for testing purposes)
if ( defined( 'WP_CLI' ) || ( isset( $_GET['slbp_validate_phase8'] ) && current_user_can( 'manage_options' ) ) ) {
	$validator = new SLBP_Phase8_Validator();
	$validator->run_validation();
}