<?php
/**
 * Simple Logger Class
 *
 * Provides basic logging functionality for the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 */

/**
 * Simple Logger Class
 *
 * Handles logging for debugging and monitoring plugin activity.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Logger {

	/**
	 * Logger context.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $context    Logger context identifier.
	 */
	private $context;

	/**
	 * Log levels.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $log_levels    Available log levels.
	 */
	private $log_levels = array(
		'emergency' => 0,
		'alert'     => 1,
		'critical'  => 2,
		'error'     => 3,
		'warning'   => 4,
		'notice'    => 5,
		'info'      => 6,
		'debug'     => 7,
	);

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    string    $context    Logger context.
	 */
	public function __construct( $context = 'general' ) {
		$this->context = $context;
	}

	/**
	 * Log a message.
	 *
	 * @since    1.0.0
	 * @param    string    $level      Log level.
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 */
	public function log( $level, $message, $context = array() ) {
		// Check if logging is enabled
		$general_settings = get_option( 'slbp_general_settings', array() );
		$debug_mode = $general_settings['debug_mode'] ?? false;
		$log_level = $general_settings['log_level'] ?? 'error';

		if ( ! $debug_mode ) {
			return;
		}

		// Check if level should be logged
		if ( ! $this->should_log( $level, $log_level ) ) {
			return;
		}

		// Format log entry
		$log_entry = $this->format_log_entry( $level, $message, $context );

		// Write to log
		$this->write_log( $log_entry );
	}

	/**
	 * Check if level should be logged.
	 *
	 * @since    1.0.0
	 * @param    string    $level         Current log level.
	 * @param    string    $min_level     Minimum log level.
	 * @return   bool                     True if should log, false otherwise.
	 */
	private function should_log( $level, $min_level ) {
		$current_level = $this->log_levels[ $level ] ?? 7;
		$minimum_level = $this->log_levels[ $min_level ] ?? 3;

		return $current_level <= $minimum_level;
	}

	/**
	 * Format log entry.
	 *
	 * @since    1.0.0
	 * @param    string    $level      Log level.
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 * @return   string                Formatted log entry.
	 */
	private function format_log_entry( $level, $message, $context = array() ) {
		$timestamp = current_time( 'Y-m-d H:i:s' );
		$level_upper = strtoupper( $level );
		
		$log_entry = sprintf(
			'[%s] [%s] [%s] %s',
			$timestamp,
			$level_upper,
			$this->context,
			$message
		);

		// Add context data if provided
		if ( ! empty( $context ) ) {
			$log_entry .= ' | Context: ' . wp_json_encode( $context );
		}

		return $log_entry;
	}

	/**
	 * Write log entry.
	 *
	 * @since    1.0.0
	 * @param    string    $log_entry    Formatted log entry.
	 */
	private function write_log( $log_entry ) {
		// Use WordPress debug log if WP_DEBUG_LOG is enabled
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[SLBP] ' . $log_entry );
		}

		// Also store in database for admin review
		$this->store_log_entry( $log_entry );
	}

	/**
	 * Store log entry in database.
	 *
	 * @since    1.0.0
	 * @param    string    $log_entry    Formatted log entry.
	 */
	private function store_log_entry( $log_entry ) {
		// Get existing logs
		$logs = get_option( 'slbp_debug_logs', array() );

		// Add new entry
		$logs[] = array(
			'timestamp' => current_time( 'timestamp' ),
			'entry'     => $log_entry,
		);

		// Keep only last 100 entries to prevent database bloat
		if ( count( $logs ) > 100 ) {
			$logs = array_slice( $logs, -100 );
		}

		// Update logs
		update_option( 'slbp_debug_logs', $logs );
	}

	/**
	 * Get recent log entries.
	 *
	 * @since    1.0.0
	 * @param    int    $limit    Number of entries to retrieve.
	 * @return   array            Array of log entries.
	 */
	public static function get_recent_logs( $limit = 50 ) {
		$logs = get_option( 'slbp_debug_logs', array() );
		
		// Sort by timestamp descending
		usort( $logs, function( $a, $b ) {
			return $b['timestamp'] - $a['timestamp'];
		} );

		return array_slice( $logs, 0, $limit );
	}

	/**
	 * Clear all log entries.
	 *
	 * @since    1.0.0
	 */
	public static function clear_logs() {
		delete_option( 'slbp_debug_logs' );
	}

	/**
	 * Emergency log.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 */
	public function emergency( $message, $context = array() ) {
		$this->log( 'emergency', $message, $context );
	}

	/**
	 * Alert log.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 */
	public function alert( $message, $context = array() ) {
		$this->log( 'alert', $message, $context );
	}

	/**
	 * Critical log.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 */
	public function critical( $message, $context = array() ) {
		$this->log( 'critical', $message, $context );
	}

	/**
	 * Error log.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 */
	public function error( $message, $context = array() ) {
		$this->log( 'error', $message, $context );
	}

	/**
	 * Warning log.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 */
	public function warning( $message, $context = array() ) {
		$this->log( 'warning', $message, $context );
	}

	/**
	 * Notice log.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 */
	public function notice( $message, $context = array() ) {
		$this->log( 'notice', $message, $context );
	}

	/**
	 * Info log.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 */
	public function info( $message, $context = array() ) {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Debug log.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    array     $context    Additional context data.
	 */
	public function debug( $message, $context = array() ) {
		$this->log( 'debug', $message, $context );
	}
}