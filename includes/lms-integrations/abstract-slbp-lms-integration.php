<?php
/**
 * Abstract LMS Integration Class
 *
 * Defines the interface and common functionality for all LMS integrations.
 * All LMS integration implementations must extend this abstract class.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/lms-integrations
 */

/**
 * Abstract LMS Integration Class
 *
 * This abstract class defines the required methods and common functionality
 * that all LMS integrations must implement.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/lms-integrations
 * @author     Skyian LLC <contact@skyianllc.com>
 */
abstract class SLBP_Abstract_LMS_Integration {

	/**
	 * LMS identifier.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $lms_id    Unique identifier for this LMS.
	 */
	protected $lms_id;

	/**
	 * LMS name.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $lms_name    Human-readable name for this LMS.
	 */
	protected $lms_name;

	/**
	 * LMS version.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    LMS integration version.
	 */
	protected $version;

	/**
	 * LMS configuration.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $config    LMS configuration options.
	 */
	protected $config;

	/**
	 * Logger instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SLBP_Logger    $logger    Logger instance for this LMS.
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    array    $config    LMS configuration.
	 */
	public function __construct( $config = array() ) {
		$this->config = $config;
		
		// Initialize logger if available
		if ( class_exists( 'SLBP_Logger' ) ) {
			$this->logger = new SLBP_Logger( $this->lms_id );
		}

		$this->init();
	}

	/**
	 * Initialize the LMS integration.
	 *
	 * This method is called after the constructor and should be used
	 * to set up the LMS-specific configuration and dependencies.
	 *
	 * @since    1.0.0
	 */
	abstract protected function init();

	/**
	 * Check if LMS is available and properly configured.
	 *
	 * @since    1.0.0
	 * @return   bool    True if LMS is available, false otherwise.
	 */
	abstract public function is_available();

	/**
	 * Get LMS identifier.
	 *
	 * @since    1.0.0
	 * @return   string    LMS identifier.
	 */
	public function get_id() {
		return $this->lms_id;
	}

	/**
	 * Get LMS name.
	 *
	 * @since    1.0.0
	 * @return   string    LMS name.
	 */
	public function get_name() {
		return $this->lms_name;
	}

	/**
	 * Get LMS version.
	 *
	 * @since    1.0.0
	 * @return   string    LMS version.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Enroll user in a course.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @param    array     $args       Optional enrollment arguments.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	abstract public function enroll_user( $user_id, $course_id, $args = array() );

	/**
	 * Unenroll user from a course.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @param    array     $args       Optional unenrollment arguments.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	abstract public function unenroll_user( $user_id, $course_id, $args = array() );

	/**
	 * Check if user is enrolled in a course.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @return   bool                  True if enrolled, false otherwise.
	 */
	abstract public function is_user_enrolled( $user_id, $course_id );

	/**
	 * Get user's enrolled courses.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @return   array                 Array of course IDs.
	 */
	abstract public function get_user_courses( $user_id );

	/**
	 * Get course progress for a user.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @return   array|WP_Error        Progress data or WP_Error on failure.
	 */
	abstract public function get_course_progress( $user_id, $course_id );

	/**
	 * Get available courses for enrollment.
	 *
	 * @since    1.0.0
	 * @param    array     $args       Optional query arguments.
	 * @return   array                 Array of course data.
	 */
	abstract public function get_courses( $args = array() );

	/**
	 * Get course information.
	 *
	 * @since    1.0.0
	 * @param    int       $course_id  Course ID.
	 * @return   array|WP_Error        Course data or WP_Error on failure.
	 */
	abstract public function get_course( $course_id );

	/**
	 * Create or update user enrollment metadata.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id           WordPress user ID.
	 * @param    int       $course_id         Course ID.
	 * @param    string    $transaction_id    Transaction identifier.
	 * @param    string    $subscription_id   Subscription identifier (optional).
	 * @param    array     $metadata          Additional metadata.
	 * @return   bool                         True on success, false on failure.
	 */
	public function update_enrollment_metadata( $user_id, $course_id, $transaction_id, $subscription_id = '', $metadata = array() ) {
		$enrollment_data = array(
			'transaction_id'   => $transaction_id,
			'subscription_id'  => $subscription_id,
			'enrolled_date'    => current_time( 'mysql' ),
			'source'           => 'skylearn_billing_pro',
			'lms'              => $this->lms_id,
			'metadata'         => $metadata,
		);

		return update_user_meta( 
			$user_id, 
			"slbp_enrollment_{$course_id}", 
			$enrollment_data 
		) !== false;
	}

	/**
	 * Get user enrollment metadata.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @return   array|false           Enrollment metadata or false if not found.
	 */
	public function get_enrollment_metadata( $user_id, $course_id ) {
		return get_user_meta( $user_id, "slbp_enrollment_{$course_id}", true );
	}

	/**
	 * Remove user enrollment metadata.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @return   bool                  True on success, false on failure.
	 */
	public function remove_enrollment_metadata( $user_id, $course_id ) {
		return delete_user_meta( $user_id, "slbp_enrollment_{$course_id}" );
	}

	/**
	 * Get configuration value.
	 *
	 * @since    1.0.0
	 * @param    string    $key        Configuration key.
	 * @param    mixed     $default    Default value if key is not found.
	 * @return   mixed                 Configuration value.
	 */
	protected function get_config( $key, $default = null ) {
		return isset( $this->config[ $key ] ) ? $this->config[ $key ] : $default;
	}

	/**
	 * Log a message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    string    $level      Log level (debug, info, warning, error).
	 * @param    array     $context    Additional context data.
	 */
	protected function log( $message, $level = 'info', $context = array() ) {
		if ( $this->logger ) {
			$this->logger->log( $level, $message, $context );
		} else {
			// Fallback to error_log
			error_log( sprintf( '[SLBP-%s] %s: %s', strtoupper( $this->lms_id ), strtoupper( $level ), $message ) );
		}
	}

	/**
	 * Validate user capabilities for course management.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @param    string    $action     Action being performed (enroll, unenroll).
	 * @return   bool|WP_Error         True if valid, WP_Error if not.
	 */
	protected function validate_enrollment_capability( $user_id, $course_id, $action ) {
		// Check if user exists
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 
				'invalid_user', 
				sprintf( __( 'User with ID %d does not exist.', 'skylearn-billing-pro' ), $user_id )
			);
		}

		// Check if course exists
		$course = $this->get_course( $course_id );
		if ( is_wp_error( $course ) ) {
			return $course;
		}

		// Additional LMS-specific validation can be implemented in child classes
		return true;
	}

	/**
	 * Sanitize and validate course arguments.
	 *
	 * @since    1.0.0
	 * @param    array     $args       Raw arguments.
	 * @return   array                 Sanitized arguments.
	 */
	protected function sanitize_course_args( $args ) {
		$defaults = array(
			'access_mode'     => 'full',
			'enrollment_date' => current_time( 'mysql' ),
			'status'          => 'active',
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize values
		$args['access_mode'] = sanitize_text_field( $args['access_mode'] );
		$args['status'] = sanitize_text_field( $args['status'] );
		
		return $args;
	}
}