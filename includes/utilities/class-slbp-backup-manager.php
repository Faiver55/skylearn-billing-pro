<?php
/**
 * Backup and recovery system for SkyLearn Billing Pro
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 */

/**
 * Backup and recovery manager.
 *
 * Handles automated backups, disaster recovery, and data restoration.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Backup_Manager {

	/**
	 * Backup storage directory.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $backup_dir    Backup storage directory.
	 */
	private $backup_dir;

	/**
	 * Backup configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $backup_config    Backup configuration.
	 */
	private $backup_config;

	/**
	 * Plugin tables to backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $plugin_tables    Plugin tables.
	 */
	private $plugin_tables;

	/**
	 * Maximum backup retention period.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $retention_days    Retention period in days.
	 */
	private $retention_days = 30;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->backup_config = $this->get_backup_config();
		$this->retention_days = $this->backup_config['retention_days'] ?? 30;
		
		$upload_dir = wp_upload_dir();
		$this->backup_dir = $upload_dir['basedir'] . '/slbp-backups/';

		$this->plugin_tables = $this->get_plugin_tables();

		// Ensure backup directory exists
		$this->ensure_backup_directory();

		// Hook into WordPress actions
		add_action( 'slbp_daily_backup', array( $this, 'create_daily_backup' ) );
		add_action( 'slbp_cleanup_old_backups', array( $this, 'cleanup_old_backups' ) );
		
		// Register REST API endpoints
		add_action( 'rest_api_init', array( $this, 'register_backup_endpoints' ) );

		// Schedule backup tasks
		$this->schedule_backup_tasks();
	}

	/**
	 * Register backup REST API endpoints.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function register_backup_endpoints() {
		// Create backup endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/backup/create', array(
			'methods' => 'POST',
			'callback' => array( $this, 'create_backup_endpoint' ),
			'permission_callback' => array( $this, 'check_backup_permissions' ),
		) );

		// List backups endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/backup/list', array(
			'methods' => 'GET',
			'callback' => array( $this, 'list_backups_endpoint' ),
			'permission_callback' => array( $this, 'check_backup_permissions' ),
		) );

		// Restore backup endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/backup/restore/(?P<backup_id>[a-zA-Z0-9_-]+)', array(
			'methods' => 'POST',
			'callback' => array( $this, 'restore_backup_endpoint' ),
			'permission_callback' => array( $this, 'check_backup_permissions' ),
		) );

		// Delete backup endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/backup/delete/(?P<backup_id>[a-zA-Z0-9_-]+)', array(
			'methods' => 'DELETE',
			'callback' => array( $this, 'delete_backup_endpoint' ),
			'permission_callback' => array( $this, 'check_backup_permissions' ),
		) );

		// Backup configuration endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/backup/config', array(
			'methods' => array( 'GET', 'POST' ),
			'callback' => array( $this, 'manage_backup_config' ),
			'permission_callback' => array( $this, 'check_backup_permissions' ),
		) );

		// Disaster recovery test endpoint
		register_rest_route( 'skylearn-billing-pro/v1', '/backup/dr-test', array(
			'methods' => 'POST',
			'callback' => array( $this, 'disaster_recovery_test' ),
			'permission_callback' => array( $this, 'check_backup_permissions' ),
		) );
	}

	/**
	 * Create a backup.
	 *
	 * @since    1.0.0
	 * @param    string $type        Backup type (full, incremental, differential).
	 * @param    array  $components  Components to backup (database, files, config).
	 * @return   array              Backup result.
	 */
	public function create_backup( $type = 'full', $components = array( 'database', 'files', 'config' ) ) {
		$backup_id = $this->generate_backup_id();
		$backup_path = $this->backup_dir . $backup_id . '/';
		
		if ( ! wp_mkdir_p( $backup_path ) ) {
			return array(
				'success' => false,
				'message' => 'Failed to create backup directory',
			);
		}

		$backup_info = array(
			'id' => $backup_id,
			'type' => $type,
			'components' => $components,
			'created_at' => current_time( 'mysql' ),
			'status' => 'in_progress',
			'size' => 0,
			'files' => array(),
		);

		$this->save_backup_info( $backup_id, $backup_info );

		try {
			// Backup database
			if ( in_array( 'database', $components, true ) ) {
				$db_backup_result = $this->backup_database( $backup_path, $type );
				if ( ! $db_backup_result['success'] ) {
					throw new Exception( $db_backup_result['message'] );
				}
				$backup_info['files']['database'] = $db_backup_result['file'];
				$backup_info['size'] += $db_backup_result['size'];
			}

			// Backup files
			if ( in_array( 'files', $components, true ) ) {
				$files_backup_result = $this->backup_files( $backup_path, $type );
				if ( ! $files_backup_result['success'] ) {
					throw new Exception( $files_backup_result['message'] );
				}
				$backup_info['files']['uploads'] = $files_backup_result['file'];
				$backup_info['size'] += $files_backup_result['size'];
			}

			// Backup configuration
			if ( in_array( 'config', $components, true ) ) {
				$config_backup_result = $this->backup_configuration( $backup_path );
				if ( ! $config_backup_result['success'] ) {
					throw new Exception( $config_backup_result['message'] );
				}
				$backup_info['files']['config'] = $config_backup_result['file'];
				$backup_info['size'] += $config_backup_result['size'];
			}

			$backup_info['status'] = 'completed';
			$backup_info['completed_at'] = current_time( 'mysql' );

		} catch ( Exception $e ) {
			$backup_info['status'] = 'failed';
			$backup_info['error'] = $e->getMessage();
			$backup_info['failed_at'] = current_time( 'mysql' );
		}

		$this->save_backup_info( $backup_id, $backup_info );

		// Compress backup if successful
		if ( $backup_info['status'] === 'completed' ) {
			$this->compress_backup( $backup_id );
		}

		return array(
			'success' => $backup_info['status'] === 'completed',
			'backup_id' => $backup_id,
			'message' => $backup_info['status'] === 'completed' ? 'Backup created successfully' : $backup_info['error'],
			'backup_info' => $backup_info,
		);
	}

	/**
	 * Create daily backup.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function create_daily_backup() {
		$backup_type = $this->get_daily_backup_type();
		$components = $this->backup_config['daily_components'] ?? array( 'database', 'config' );

		$result = $this->create_backup( $backup_type, $components );

		// Log the result
		if ( $result['success'] ) {
			error_log( 'SLBP Daily Backup: Successfully created backup ' . $result['backup_id'] );
		} else {
			error_log( 'SLBP Daily Backup: Failed to create backup - ' . $result['message'] );
		}

		// Send notification if configured
		if ( $this->backup_config['notifications']['email_on_completion'] ) {
			$this->send_backup_notification( $result );
		}
	}

	/**
	 * Restore from backup.
	 *
	 * @since    1.0.0
	 * @param    string $backup_id    Backup ID to restore.
	 * @param    array  $components   Components to restore.
	 * @return   array               Restore result.
	 */
	public function restore_backup( $backup_id, $components = array( 'database', 'files', 'config' ) ) {
		$backup_info = $this->get_backup_info( $backup_id );
		
		if ( ! $backup_info ) {
			return array(
				'success' => false,
				'message' => 'Backup not found',
			);
		}

		if ( $backup_info['status'] !== 'completed' ) {
			return array(
				'success' => false,
				'message' => 'Backup is not in completed state',
			);
		}

		$backup_path = $this->backup_dir . $backup_id . '/';
		
		// Extract backup if compressed
		$this->extract_backup( $backup_id );

		$restore_results = array();

		try {
			// Restore database
			if ( in_array( 'database', $components, true ) && isset( $backup_info['files']['database'] ) ) {
				$db_restore_result = $this->restore_database( $backup_path . $backup_info['files']['database'] );
				$restore_results['database'] = $db_restore_result;
				
				if ( ! $db_restore_result['success'] ) {
					throw new Exception( 'Database restore failed: ' . $db_restore_result['message'] );
				}
			}

			// Restore files
			if ( in_array( 'files', $components, true ) && isset( $backup_info['files']['uploads'] ) ) {
				$files_restore_result = $this->restore_files( $backup_path . $backup_info['files']['uploads'] );
				$restore_results['files'] = $files_restore_result;
				
				if ( ! $files_restore_result['success'] ) {
					throw new Exception( 'Files restore failed: ' . $files_restore_result['message'] );
				}
			}

			// Restore configuration
			if ( in_array( 'config', $components, true ) && isset( $backup_info['files']['config'] ) ) {
				$config_restore_result = $this->restore_configuration( $backup_path . $backup_info['files']['config'] );
				$restore_results['config'] = $config_restore_result;
				
				if ( ! $config_restore_result['success'] ) {
					throw new Exception( 'Configuration restore failed: ' . $config_restore_result['message'] );
				}
			}

			// Clear caches after successful restore
			$this->clear_caches_after_restore();

			return array(
				'success' => true,
				'message' => 'Backup restored successfully',
				'restore_results' => $restore_results,
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
				'restore_results' => $restore_results,
			);
		}
	}

	/**
	 * List available backups.
	 *
	 * @since    1.0.0
	 * @param    array $filters    Filters for backup list.
	 * @return   array            List of backups.
	 */
	public function list_backups( $filters = array() ) {
		$backups = array();
		$backup_dirs = glob( $this->backup_dir . '*', GLOB_ONLYDIR );

		foreach ( $backup_dirs as $backup_dir ) {
			$backup_id = basename( $backup_dir );
			$backup_info = $this->get_backup_info( $backup_id );
			
			if ( $backup_info && $this->matches_filters( $backup_info, $filters ) ) {
				$backups[] = $backup_info;
			}
		}

		// Sort by creation date (newest first)
		usort( $backups, function( $a, $b ) {
			return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
		} );

		return $backups;
	}

	/**
	 * Delete a backup.
	 *
	 * @since    1.0.0
	 * @param    string $backup_id    Backup ID to delete.
	 * @return   array               Delete result.
	 */
	public function delete_backup( $backup_id ) {
		$backup_path = $this->backup_dir . $backup_id . '/';
		$compressed_path = $this->backup_dir . $backup_id . '.tar.gz';

		if ( ! file_exists( $backup_path ) && ! file_exists( $compressed_path ) ) {
			return array(
				'success' => false,
				'message' => 'Backup not found',
			);
		}

		$deleted = true;

		// Delete directory
		if ( file_exists( $backup_path ) ) {
			$deleted = $this->delete_directory( $backup_path ) && $deleted;
		}

		// Delete compressed file
		if ( file_exists( $compressed_path ) ) {
			$deleted = unlink( $compressed_path ) && $deleted;
		}

		return array(
			'success' => $deleted,
			'message' => $deleted ? 'Backup deleted successfully' : 'Failed to delete backup',
		);
	}

	/**
	 * Cleanup old backups.
	 *
	 * @since    1.0.0
	 * @return   array    Cleanup result.
	 */
	public function cleanup_old_backups() {
		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$this->retention_days} days" ) );
		$backups = $this->list_backups();
		$deleted_count = 0;
		$errors = array();

		foreach ( $backups as $backup ) {
			if ( $backup['created_at'] < $cutoff_date ) {
				$delete_result = $this->delete_backup( $backup['id'] );
				
				if ( $delete_result['success'] ) {
					$deleted_count++;
				} else {
					$errors[] = "Failed to delete backup {$backup['id']}: {$delete_result['message']}";
				}
			}
		}

		return array(
			'deleted_count' => $deleted_count,
			'errors' => $errors,
		);
	}

	/**
	 * Test disaster recovery procedures.
	 *
	 * @since    1.0.0
	 * @return   array    Test results.
	 */
	public function test_disaster_recovery() {
		$test_results = array(
			'backup_creation' => false,
			'backup_compression' => false,
			'backup_extraction' => false,
			'database_verification' => false,
			'files_verification' => false,
			'cleanup' => false,
		);

		$test_backup_id = 'dr_test_' . time();

		try {
			// Test backup creation
			$backup_result = $this->create_backup( 'full', array( 'database', 'config' ) );
			$test_results['backup_creation'] = $backup_result['success'];
			
			if ( ! $backup_result['success'] ) {
				throw new Exception( 'Backup creation failed' );
			}

			$backup_id = $backup_result['backup_id'];

			// Test compression
			$compression_result = $this->compress_backup( $backup_id );
			$test_results['backup_compression'] = $compression_result;

			// Test extraction
			$extraction_result = $this->extract_backup( $backup_id );
			$test_results['backup_extraction'] = $extraction_result;

			// Test database verification
			$test_results['database_verification'] = $this->verify_database_backup( $backup_id );

			// Test files verification
			$test_results['files_verification'] = $this->verify_files_backup( $backup_id );

			// Cleanup test backup
			$cleanup_result = $this->delete_backup( $backup_id );
			$test_results['cleanup'] = $cleanup_result['success'];

		} catch ( Exception $e ) {
			$test_results['error'] = $e->getMessage();
		}

		$test_results['overall_success'] = ! in_array( false, $test_results, true ) && ! isset( $test_results['error'] );

		return $test_results;
	}

	/**
	 * Create backup endpoint.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Backup creation response.
	 */
	public function create_backup_endpoint( $request ) {
		$type = $request->get_param( 'type' ) ?? 'full';
		$components = $request->get_param( 'components' ) ?? array( 'database', 'files', 'config' );

		$result = $this->create_backup( $type, $components );

		return new WP_REST_Response( $result );
	}

	/**
	 * List backups endpoint.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Backups list response.
	 */
	public function list_backups_endpoint( $request ) {
		$filters = array(
			'type' => $request->get_param( 'type' ),
			'status' => $request->get_param( 'status' ),
			'from_date' => $request->get_param( 'from_date' ),
			'to_date' => $request->get_param( 'to_date' ),
		);

		$backups = $this->list_backups( array_filter( $filters ) );

		return new WP_REST_Response( array(
			'backups' => $backups,
			'total' => count( $backups ),
		) );
	}

	/**
	 * Restore backup endpoint.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Restore response.
	 */
	public function restore_backup_endpoint( $request ) {
		$backup_id = $request->get_param( 'backup_id' );
		$components = $request->get_param( 'components' ) ?? array( 'database', 'files', 'config' );

		$result = $this->restore_backup( $backup_id, $components );

		return new WP_REST_Response( $result );
	}

	/**
	 * Delete backup endpoint.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Delete response.
	 */
	public function delete_backup_endpoint( $request ) {
		$backup_id = $request->get_param( 'backup_id' );
		$result = $this->delete_backup( $backup_id );

		return new WP_REST_Response( $result );
	}

	/**
	 * Manage backup configuration.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Config response.
	 */
	public function manage_backup_config( $request ) {
		if ( $request->get_method() === 'POST' ) {
			$new_config = $request->get_json_params();
			
			if ( $this->validate_backup_config( $new_config ) ) {
				update_option( 'slbp_backup_config', $new_config );
				$this->backup_config = $new_config;
				
				return new WP_REST_Response( array(
					'success' => true,
					'message' => 'Backup configuration updated successfully',
					'config' => $new_config,
				) );
			} else {
				return new WP_REST_Response( array(
					'success' => false,
					'message' => 'Invalid backup configuration',
				), 400 );
			}
		}

		return new WP_REST_Response( array(
			'config' => $this->backup_config,
		) );
	}

	/**
	 * Disaster recovery test endpoint.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   WP_REST_Response           Test response.
	 */
	public function disaster_recovery_test( $request ) {
		$test_results = $this->test_disaster_recovery();

		return new WP_REST_Response( array(
			'test_results' => $test_results,
			'timestamp' => current_time( 'c' ),
		) );
	}

	/**
	 * Check backup permissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request    REST request object.
	 * @return   bool                       True if authorized, false otherwise.
	 */
	public function check_backup_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Backup database.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_path    Backup directory path.
	 * @param    string $type          Backup type.
	 * @return   array                 Backup result.
	 */
	private function backup_database( $backup_path, $type ) {
		global $wpdb;

		$filename = 'database_' . date( 'Y-m-d_H-i-s' ) . '.sql';
		$file_path = $backup_path . $filename;

		try {
			$sql_dump = '';

			// Get tables to backup
			$tables = $this->get_tables_for_backup( $type );

			foreach ( $tables as $table ) {
				// Add CREATE TABLE statement
				$create_table = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
				$sql_dump .= "\n\n" . $create_table[1] . ";\n\n";

				// Add INSERT statements
				$rows = $wpdb->get_results( "SELECT * FROM `{$table}`", ARRAY_A );
				
				if ( ! empty( $rows ) ) {
					foreach ( $rows as $row ) {
						$sql_dump .= $this->generate_insert_statement( $table, $row );
					}
				}
			}

			// Write to file
			$bytes_written = file_put_contents( $file_path, $sql_dump );
			
			if ( false === $bytes_written ) {
				throw new Exception( 'Failed to write database backup file' );
			}

			return array(
				'success' => true,
				'file' => $filename,
				'size' => filesize( $file_path ),
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Backup files.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_path    Backup directory path.
	 * @param    string $type          Backup type.
	 * @return   array                 Backup result.
	 */
	private function backup_files( $backup_path, $type ) {
		$filename = 'files_' . date( 'Y-m-d_H-i-s' ) . '.tar.gz';
		$file_path = $backup_path . $filename;

		try {
			$upload_dir = wp_upload_dir();
			$source_dir = $upload_dir['basedir'] . '/slbp-*';

			// Create tar.gz archive
			$command = "tar -czf '{$file_path}' -C '{$upload_dir['basedir']}' slbp-* 2>/dev/null";
			exec( $command, $output, $return_code );

			if ( $return_code !== 0 || ! file_exists( $file_path ) ) {
				throw new Exception( 'Failed to create files archive' );
			}

			return array(
				'success' => true,
				'file' => $filename,
				'size' => filesize( $file_path ),
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Backup configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_path    Backup directory path.
	 * @return   array                 Backup result.
	 */
	private function backup_configuration( $backup_path ) {
		$filename = 'config_' . date( 'Y-m-d_H-i-s' ) . '.json';
		$file_path = $backup_path . $filename;

		try {
			$config_data = array(
				'plugin_version' => SLBP_VERSION,
				'wordpress_version' => get_bloginfo( 'version' ),
				'php_version' => PHP_VERSION,
				'options' => array(),
				'site_url' => get_site_url(),
				'admin_email' => get_option( 'admin_email' ),
				'backup_timestamp' => current_time( 'c' ),
			);

			// Get plugin options
			$plugin_options = array(
				'slbp_settings',
				'slbp_license_key',
				'slbp_license_status',
				'slbp_alert_config',
				'slbp_backup_config',
				'slbp_rate_limits',
				'slbp_rate_limit_whitelist',
				'slbp_rate_limit_blacklist',
			);

			foreach ( $plugin_options as $option ) {
				$config_data['options'][ $option ] = get_option( $option );
			}

			$json_data = wp_json_encode( $config_data, JSON_PRETTY_PRINT );
			$bytes_written = file_put_contents( $file_path, $json_data );

			if ( false === $bytes_written ) {
				throw new Exception( 'Failed to write configuration backup file' );
			}

			return array(
				'success' => true,
				'file' => $filename,
				'size' => filesize( $file_path ),
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Restore database from backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_file    Database backup file path.
	 * @return   array                 Restore result.
	 */
	private function restore_database( $backup_file ) {
		global $wpdb;

		if ( ! file_exists( $backup_file ) ) {
			return array(
				'success' => false,
				'message' => 'Database backup file not found',
			);
		}

		try {
			$sql_content = file_get_contents( $backup_file );
			
			if ( false === $sql_content ) {
				throw new Exception( 'Failed to read database backup file' );
			}

			// Split SQL content into individual statements
			$statements = preg_split( '/;\s*\n/', $sql_content );

			foreach ( $statements as $statement ) {
				$statement = trim( $statement );
				
				if ( ! empty( $statement ) ) {
					$result = $wpdb->query( $statement );
					
					if ( false === $result ) {
						throw new Exception( 'Database query failed: ' . $wpdb->last_error );
					}
				}
			}

			return array(
				'success' => true,
				'message' => 'Database restored successfully',
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Restore files from backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_file    Files backup file path.
	 * @return   array                 Restore result.
	 */
	private function restore_files( $backup_file ) {
		if ( ! file_exists( $backup_file ) ) {
			return array(
				'success' => false,
				'message' => 'Files backup file not found',
			);
		}

		try {
			$upload_dir = wp_upload_dir();
			
			// Extract files
			$command = "tar -xzf '{$backup_file}' -C '{$upload_dir['basedir']}' 2>/dev/null";
			exec( $command, $output, $return_code );

			if ( $return_code !== 0 ) {
				throw new Exception( 'Failed to extract files archive' );
			}

			return array(
				'success' => true,
				'message' => 'Files restored successfully',
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Restore configuration from backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_file    Configuration backup file path.
	 * @return   array                 Restore result.
	 */
	private function restore_configuration( $backup_file ) {
		if ( ! file_exists( $backup_file ) ) {
			return array(
				'success' => false,
				'message' => 'Configuration backup file not found',
			);
		}

		try {
			$config_content = file_get_contents( $backup_file );
			
			if ( false === $config_content ) {
				throw new Exception( 'Failed to read configuration backup file' );
			}

			$config_data = json_decode( $config_content, true );
			
			if ( null === $config_data ) {
				throw new Exception( 'Invalid configuration backup file format' );
			}

			// Restore options
			if ( isset( $config_data['options'] ) ) {
				foreach ( $config_data['options'] as $option_name => $option_value ) {
					update_option( $option_name, $option_value );
				}
			}

			return array(
				'success' => true,
				'message' => 'Configuration restored successfully',
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Generate backup ID.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string    Backup ID.
	 */
	private function generate_backup_id() {
		return 'backup_' . date( 'Y-m-d_H-i-s' ) . '_' . wp_generate_password( 8, false );
	}

	/**
	 * Get plugin tables for backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Plugin tables.
	 */
	private function get_plugin_tables() {
		global $wpdb;

		return array(
			$wpdb->prefix . 'slbp_transactions',
			$wpdb->prefix . 'slbp_subscriptions',
			$wpdb->prefix . 'slbp_licenses',
			$wpdb->prefix . 'slbp_enrollment_logs',
			$wpdb->prefix . 'slbp_notifications',
			$wpdb->prefix . 'slbp_api_keys',
			$wpdb->prefix . 'slbp_webhooks',
			$wpdb->prefix . 'slbp_webhook_logs',
			$wpdb->prefix . 'slbp_api_logs',
			$wpdb->prefix . 'slbp_audit_logs',
			// Language/region tables removed in Phase 3 refactor
			$wpdb->prefix . 'slbp_background_tasks',
			$wpdb->prefix . 'slbp_sessions',
			$wpdb->prefix . 'slbp_metrics',
			$wpdb->prefix . 'slbp_alerts',
			$wpdb->prefix . 'slbp_rate_limits',
		);
	}

	/**
	 * Get backup configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Backup configuration.
	 */
	private function get_backup_config() {
		$default_config = array(
			'enabled' => true,
			'schedule' => 'daily',
			'retention_days' => 30,
			'compression' => true,
			'daily_components' => array( 'database', 'config' ),
			'weekly_components' => array( 'database', 'files', 'config' ),
			'notifications' => array(
				'email_on_completion' => false,
				'email_on_failure' => true,
				'webhook_url' => '',
			),
			'remote_storage' => array(
				'enabled' => false,
				'type' => 's3',
				'credentials' => array(),
			),
		);

		return get_option( 'slbp_backup_config', $default_config );
	}

	/**
	 * Ensure backup directory exists.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function ensure_backup_directory() {
		if ( ! file_exists( $this->backup_dir ) ) {
			wp_mkdir_p( $this->backup_dir );
			
			// Create .htaccess to protect backup files
			$htaccess_content = "Order deny,allow\nDeny from all\n";
			file_put_contents( $this->backup_dir . '.htaccess', $htaccess_content );
		}
	}

	/**
	 * Schedule backup tasks.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function schedule_backup_tasks() {
		// Schedule daily backup
		if ( ! wp_next_scheduled( 'slbp_daily_backup' ) && $this->backup_config['enabled'] ) {
			wp_schedule_event( time(), 'daily', 'slbp_daily_backup' );
		}

		// Schedule cleanup
		if ( ! wp_next_scheduled( 'slbp_cleanup_old_backups' ) ) {
			wp_schedule_event( time(), 'weekly', 'slbp_cleanup_old_backups' );
		}
	}

	/**
	 * Get tables for backup based on type.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $type    Backup type.
	 * @return   array          Tables to backup.
	 */
	private function get_tables_for_backup( $type ) {
		switch ( $type ) {
			case 'incremental':
				// For incremental backups, only backup tables with recent changes
				return $this->get_recently_modified_tables();

			case 'differential':
				// For differential backups, backup all changes since last full backup
				return $this->get_tables_changed_since_full_backup();

			case 'full':
			default:
				return $this->plugin_tables;
		}
	}

	/**
	 * Get recently modified tables.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Recently modified tables.
	 */
	private function get_recently_modified_tables() {
		// This is a simplified implementation
		// In practice, you'd track table modification timestamps
		return $this->plugin_tables;
	}

	/**
	 * Get tables changed since last full backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array    Tables changed since last full backup.
	 */
	private function get_tables_changed_since_full_backup() {
		// This is a simplified implementation
		// In practice, you'd track changes since the last full backup
		return $this->plugin_tables;
	}

	/**
	 * Generate INSERT statement for a row.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $table    Table name.
	 * @param    array  $row      Table row data.
	 * @return   string          INSERT statement.
	 */
	private function generate_insert_statement( $table, $row ) {
		global $wpdb;

		$columns = array_keys( $row );
		$values = array_values( $row );

		// Escape values
		$escaped_values = array();
		foreach ( $values as $value ) {
			if ( null === $value ) {
				$escaped_values[] = 'NULL';
			} else {
				$escaped_values[] = "'" . esc_sql( $value ) . "'";
			}
		}

		$columns_str = '`' . implode( '`, `', $columns ) . '`';
		$values_str = implode( ', ', $escaped_values );

		return "INSERT INTO `{$table}` ({$columns_str}) VALUES ({$values_str});\n";
	}

	/**
	 * Save backup information.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_id    Backup ID.
	 * @param    array  $backup_info  Backup information.
	 * @return   void
	 */
	private function save_backup_info( $backup_id, $backup_info ) {
		$info_file = $this->backup_dir . $backup_id . '/backup_info.json';
		file_put_contents( $info_file, wp_json_encode( $backup_info, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Get backup information.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_id    Backup ID.
	 * @return   array|null          Backup information or null if not found.
	 */
	private function get_backup_info( $backup_id ) {
		$info_file = $this->backup_dir . $backup_id . '/backup_info.json';
		
		if ( ! file_exists( $info_file ) ) {
			return null;
		}

		$content = file_get_contents( $info_file );
		return json_decode( $content, true );
	}

	/**
	 * Check if backup matches filters.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array $backup_info    Backup information.
	 * @param    array $filters        Filters to apply.
	 * @return   bool                 True if matches, false otherwise.
	 */
	private function matches_filters( $backup_info, $filters ) {
		foreach ( $filters as $key => $value ) {
			if ( ! isset( $backup_info[ $key ] ) ) {
				continue;
			}

			switch ( $key ) {
				case 'from_date':
					if ( $backup_info['created_at'] < $value ) {
						return false;
					}
					break;

				case 'to_date':
					if ( $backup_info['created_at'] > $value ) {
						return false;
					}
					break;

				default:
					if ( $backup_info[ $key ] !== $value ) {
						return false;
					}
					break;
			}
		}

		return true;
	}

	/**
	 * Compress backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_id    Backup ID.
	 * @return   bool                True on success, false on failure.
	 */
	private function compress_backup( $backup_id ) {
		if ( ! $this->backup_config['compression'] ) {
			return true;
		}

		$backup_path = $this->backup_dir . $backup_id . '/';
		$compressed_path = $this->backup_dir . $backup_id . '.tar.gz';

		$command = "tar -czf '{$compressed_path}' -C '{$this->backup_dir}' '{$backup_id}' 2>/dev/null";
		exec( $command, $output, $return_code );

		if ( $return_code === 0 && file_exists( $compressed_path ) ) {
			// Remove uncompressed directory
			$this->delete_directory( $backup_path );
			return true;
		}

		return false;
	}

	/**
	 * Extract backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_id    Backup ID.
	 * @return   bool                True on success, false on failure.
	 */
	private function extract_backup( $backup_id ) {
		$compressed_path = $this->backup_dir . $backup_id . '.tar.gz';
		$backup_path = $this->backup_dir . $backup_id . '/';

		if ( file_exists( $backup_path ) ) {
			return true; // Already extracted
		}

		if ( ! file_exists( $compressed_path ) ) {
			return false;
		}

		$command = "tar -xzf '{$compressed_path}' -C '{$this->backup_dir}' 2>/dev/null";
		exec( $command, $output, $return_code );

		return $return_code === 0 && file_exists( $backup_path );
	}

	/**
	 * Delete directory recursively.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $dir    Directory path.
	 * @return   bool          True on success, false on failure.
	 */
	private function delete_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );

		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			
			if ( is_dir( $path ) ) {
				$this->delete_directory( $path );
			} else {
				unlink( $path );
			}
		}

		return rmdir( $dir );
	}

	/**
	 * Get daily backup type.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   string    Backup type.
	 */
	private function get_daily_backup_type() {
		// Implement backup rotation strategy
		$day_of_week = date( 'w' ); // 0 = Sunday, 6 = Saturday
		
		if ( $day_of_week === '0' ) { // Sunday = full backup
			return 'full';
		}
		
		return 'incremental';
	}

	/**
	 * Send backup notification.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array $backup_result    Backup result.
	 * @return   void
	 */
	private function send_backup_notification( $backup_result ) {
		$admin_email = get_option( 'admin_email' );
		$subject = 'SkyLearn Billing Pro Backup ' . ( $backup_result['success'] ? 'Completed' : 'Failed' );
		
		$message = "Backup Status: " . ( $backup_result['success'] ? 'SUCCESS' : 'FAILED' ) . "\n";
		$message .= "Backup ID: " . $backup_result['backup_id'] . "\n";
		$message .= "Message: " . $backup_result['message'] . "\n";
		$message .= "Timestamp: " . current_time( 'c' ) . "\n";

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Clear caches after restore.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function clear_caches_after_restore() {
		// Clear WordPress object cache
		wp_cache_flush();

		// Clear OPcache if available
		if ( function_exists( 'opcache_reset' ) ) {
			opcache_reset();
		}

		// Clear plugin-specific caches
		delete_transient( 'slbp_settings_cache' );
		delete_transient( 'slbp_license_cache' );
	}

	/**
	 * Verify database backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_id    Backup ID.
	 * @return   bool                True if valid, false otherwise.
	 */
	private function verify_database_backup( $backup_id ) {
		$backup_info = $this->get_backup_info( $backup_id );
		
		if ( ! $backup_info || ! isset( $backup_info['files']['database'] ) ) {
			return false;
		}

		$backup_path = $this->backup_dir . $backup_id . '/';
		$db_file = $backup_path . $backup_info['files']['database'];

		return file_exists( $db_file ) && filesize( $db_file ) > 0;
	}

	/**
	 * Verify files backup.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $backup_id    Backup ID.
	 * @return   bool                True if valid, false otherwise.
	 */
	private function verify_files_backup( $backup_id ) {
		$backup_info = $this->get_backup_info( $backup_id );
		
		if ( ! $backup_info || ! isset( $backup_info['files']['uploads'] ) ) {
			return true; // Files backup is optional
		}

		$backup_path = $this->backup_dir . $backup_id . '/';
		$files_archive = $backup_path . $backup_info['files']['uploads'];

		return file_exists( $files_archive ) && filesize( $files_archive ) > 0;
	}

	/**
	 * Validate backup configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array $config    Backup configuration.
	 * @return   bool            True if valid, false otherwise.
	 */
	private function validate_backup_config( $config ) {
		if ( ! is_array( $config ) ) {
			return false;
		}

		$required_fields = array( 'enabled', 'schedule', 'retention_days' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $config[ $field ] ) ) {
				return false;
			}
		}

		if ( ! is_numeric( $config['retention_days'] ) || $config['retention_days'] < 1 ) {
			return false;
		}

		$valid_schedules = array( 'daily', 'weekly', 'monthly' );
		if ( ! in_array( $config['schedule'], $valid_schedules, true ) ) {
			return false;
		}

		return true;
	}
}