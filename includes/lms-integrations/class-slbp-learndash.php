<?php
/**
 * LearnDash LMS Integration
 *
 * Handles all LearnDash LMS interactions including enrollment, unenrollment,
 * course progress sync, and user management.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/lms-integrations
 */

/**
 * LearnDash LMS Integration Class
 *
 * Provides complete integration with LearnDash LMS including user enrollment,
 * course management, progress tracking, and subscription-based access control.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/lms-integrations
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_LearnDash extends SLBP_Abstract_LMS_Integration {

	/**
	 * LMS identifier.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $lms_id    LMS identifier.
	 */
	protected $lms_id = 'learndash';

	/**
	 * LMS name.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $lms_name    LMS display name.
	 */
	protected $lms_name = 'LearnDash';

	/**
	 * LMS version.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    LMS integration version.
	 */
	protected $version = '1.0.0';

	/**
	 * Initialize the LearnDash integration.
	 *
	 * @since    1.0.0
	 */
	protected function init() {
		// No specific initialization required for LearnDash
		$this->log( 'LearnDash LMS integration initialized', 'info' );
	}

	/**
	 * Check if LearnDash is available and properly configured.
	 *
	 * @since    1.0.0
	 * @return   bool    True if LearnDash is available, false otherwise.
	 */
	public function is_available() {
		// Check if LearnDash is active
		if ( ! defined( 'LEARNDASH_VERSION' ) ) {
			$this->log( 'LearnDash is not active', 'warning' );
			return false;
		}

		// Check if required LearnDash functions exist
		$required_functions = array(
			'ld_update_course_access',
			'learndash_user_get_enrolled_courses',
			'learndash_course_get_users',
			'learndash_get_course_progress',
			'learndash_is_user_in_course_basis',
		);

		foreach ( $required_functions as $function ) {
			if ( ! function_exists( $function ) ) {
				$this->log( "Required LearnDash function {$function} not found", 'error' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Enroll user in a LearnDash course.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @param    array     $args       Optional enrollment arguments.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	public function enroll_user( $user_id, $course_id, $args = array() ) {
		// Validate capability
		$validation = $this->validate_enrollment_capability( $user_id, $course_id, 'enroll' );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Check if user is already enrolled
		if ( $this->is_user_enrolled( $user_id, $course_id ) ) {
			$this->log( 
				sprintf( 'User %d is already enrolled in course %d', $user_id, $course_id ), 
				'info' 
			);
			return true;
		}

		// Sanitize arguments
		$args = $this->sanitize_course_args( $args );

		try {
			// Enroll user using LearnDash function
			$result = ld_update_course_access( $user_id, $course_id, $remove = false );

			if ( $result ) {
				$this->log( 
					sprintf( 'Successfully enrolled user %d in course %d', $user_id, $course_id ), 
					'info' 
				);

				// Update enrollment metadata
				$transaction_id = $args['transaction_id'] ?? '';
				$subscription_id = $args['subscription_id'] ?? '';
				$metadata = array(
					'access_mode' => $args['access_mode'],
					'status' => $args['status'],
				);

				$this->update_enrollment_metadata( $user_id, $course_id, $transaction_id, $subscription_id, $metadata );

				// Fire action hook for other plugins
				do_action( 'slbp_user_enrolled', $user_id, $course_id, $this->lms_id, $args );

				return true;
			} else {
				$error_message = sprintf( 'Failed to enroll user %d in course %d', $user_id, $course_id );
				$this->log( $error_message, 'error' );
				return new WP_Error( 'enrollment_failed', $error_message );
			}
		} catch ( Exception $e ) {
			$error_message = sprintf( 
				'Exception during enrollment of user %d in course %d: %s', 
				$user_id, 
				$course_id, 
				$e->getMessage() 
			);
			$this->log( $error_message, 'error' );
			return new WP_Error( 'enrollment_exception', $error_message );
		}
	}

	/**
	 * Unenroll user from a LearnDash course.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @param    array     $args       Optional unenrollment arguments.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	public function unenroll_user( $user_id, $course_id, $args = array() ) {
		// Validate capability
		$validation = $this->validate_enrollment_capability( $user_id, $course_id, 'unenroll' );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Check if user is enrolled
		if ( ! $this->is_user_enrolled( $user_id, $course_id ) ) {
			$this->log( 
				sprintf( 'User %d is not enrolled in course %d', $user_id, $course_id ), 
				'info' 
			);
			return true;
		}

		try {
			// Unenroll user using LearnDash function
			$result = ld_update_course_access( $user_id, $course_id, $remove = true );

			if ( $result !== false ) {
				$this->log( 
					sprintf( 'Successfully unenrolled user %d from course %d', $user_id, $course_id ), 
					'info' 
				);

				// Remove enrollment metadata
				$this->remove_enrollment_metadata( $user_id, $course_id );

				// Fire action hook for other plugins
				do_action( 'slbp_user_unenrolled', $user_id, $course_id, $this->lms_id, $args );

				return true;
			} else {
				$error_message = sprintf( 'Failed to unenroll user %d from course %d', $user_id, $course_id );
				$this->log( $error_message, 'error' );
				return new WP_Error( 'unenrollment_failed', $error_message );
			}
		} catch ( Exception $e ) {
			$error_message = sprintf( 
				'Exception during unenrollment of user %d from course %d: %s', 
				$user_id, 
				$course_id, 
				$e->getMessage() 
			);
			$this->log( $error_message, 'error' );
			return new WP_Error( 'unenrollment_exception', $error_message );
		}
	}

	/**
	 * Check if user is enrolled in a LearnDash course.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @return   bool                  True if enrolled, false otherwise.
	 */
	public function is_user_enrolled( $user_id, $course_id ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		return learndash_is_user_in_course_basis( $user_id, $course_id );
	}

	/**
	 * Get user's enrolled LearnDash courses.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @return   array                 Array of course IDs.
	 */
	public function get_user_courses( $user_id ) {
		if ( ! $this->is_available() ) {
			return array();
		}

		$courses = learndash_user_get_enrolled_courses( $user_id );
		return is_array( $courses ) ? $courses : array();
	}

	/**
	 * Get course progress for a user.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $course_id  Course ID.
	 * @return   array|WP_Error        Progress data or WP_Error on failure.
	 */
	public function get_course_progress( $user_id, $course_id ) {
		if ( ! $this->is_available() ) {
			return new WP_Error( 'lms_unavailable', 'LearnDash is not available' );
		}

		if ( ! $this->is_user_enrolled( $user_id, $course_id ) ) {
			return new WP_Error( 'not_enrolled', 'User is not enrolled in this course' );
		}

		$progress = learndash_get_course_progress( $user_id, $course_id );
		
		if ( $progress === false ) {
			return new WP_Error( 'progress_error', 'Failed to retrieve course progress' );
		}

		return $progress;
	}

	/**
	 * Get available LearnDash courses for enrollment.
	 *
	 * @since    1.0.0
	 * @param    array     $args       Optional query arguments.
	 * @return   array                 Array of course data.
	 */
	public function get_courses( $args = array() ) {
		$defaults = array(
			'post_type'      => 'sfwd-courses',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$args = wp_parse_args( $args, $defaults );
		$course_ids = get_posts( $args );

		$courses = array();
		foreach ( $course_ids as $course_id ) {
			$course_data = $this->get_course( $course_id );
			if ( ! is_wp_error( $course_data ) ) {
				$courses[] = $course_data;
			}
		}

		return $courses;
	}

	/**
	 * Get LearnDash course information.
	 *
	 * @since    1.0.0
	 * @param    int       $course_id  Course ID.
	 * @return   array|WP_Error        Course data or WP_Error on failure.
	 */
	public function get_course( $course_id ) {
		$course = get_post( $course_id );
		
		if ( ! $course || $course->post_type !== 'sfwd-courses' ) {
			return new WP_Error( 
				'invalid_course', 
				sprintf( __( 'Course with ID %d does not exist or is not a valid LearnDash course.', 'skylearn-billing-pro' ), $course_id )
			);
		}

		$course_data = array(
			'id'          => $course_id,
			'title'       => $course->post_title,
			'description' => $course->post_content,
			'excerpt'     => $course->post_excerpt,
			'status'      => $course->post_status,
			'url'         => get_permalink( $course_id ),
			'date'        => $course->post_date,
			'modified'    => $course->post_modified,
		);

		// Add LearnDash-specific metadata
		$course_settings = learndash_get_setting( $course_id );
		if ( $course_settings ) {
			$course_data['settings'] = $course_settings;
		}

		// Add enrollment count
		$enrolled_users = learndash_course_get_users( $course_id );
		$course_data['enrolled_count'] = is_array( $enrolled_users ) ? count( $enrolled_users ) : 0;

		return $course_data;
	}

	/**
	 * Get course mapping for product enrollment.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Payment gateway product ID.
	 * @return   array                    Array of mapped course IDs.
	 */
	public function get_product_course_mapping( $product_id ) {
		$mappings = get_option( 'slbp_course_mappings', array() );
		return isset( $mappings[ $product_id ] ) ? (array) $mappings[ $product_id ] : array();
	}

	/**
	 * Update course mapping for product enrollment.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Payment gateway product ID.
	 * @param    array     $course_ids    Array of course IDs to map.
	 * @return   bool                     True on success, false on failure.
	 */
	public function update_product_course_mapping( $product_id, $course_ids ) {
		$mappings = get_option( 'slbp_course_mappings', array() );
		$mappings[ $product_id ] = (array) $course_ids;
		
		return update_option( 'slbp_course_mappings', $mappings );
	}

	/**
	 * Remove course mapping for product.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Payment gateway product ID.
	 * @return   bool                     True on success, false on failure.
	 */
	public function remove_product_course_mapping( $product_id ) {
		$mappings = get_option( 'slbp_course_mappings', array() );
		
		if ( isset( $mappings[ $product_id ] ) ) {
			unset( $mappings[ $product_id ] );
			return update_option( 'slbp_course_mappings', $mappings );
		}
		
		return true;
	}

	/**
	 * Process payment success for course enrollment.
	 *
	 * @since    1.0.0
	 * @param    array     $payment_data    Payment data from webhook.
	 * @return   bool|WP_Error              True on success, WP_Error on failure.
	 */
	public function process_payment_success( $payment_data ) {
		$user_email = $payment_data['user_email'] ?? '';
		$product_id = $payment_data['product_id'] ?? '';
		$transaction_id = $payment_data['transaction_id'] ?? '';
		$subscription_id = $payment_data['subscription_id'] ?? '';

		if ( empty( $user_email ) || empty( $product_id ) ) {
			return new WP_Error( 'missing_data', 'Required payment data is missing' );
		}

		// Get or create user
		$user = $this->get_or_create_user( $payment_data );
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Get mapped courses for this product
		$course_ids = $this->get_product_course_mapping( $product_id );
		if ( empty( $course_ids ) ) {
			$this->log( 
				sprintf( 'No courses mapped for product %s', $product_id ), 
				'warning' 
			);
			return true; // Not an error, just no courses to enroll
		}

		$enrollment_results = array();
		$errors = array();

		// Enroll user in each mapped course
		foreach ( $course_ids as $course_id ) {
			$result = $this->enroll_user( $user->ID, $course_id, array(
				'transaction_id'   => $transaction_id,
				'subscription_id'  => $subscription_id,
				'access_mode'      => 'full',
				'status'           => 'active',
			) );

			if ( is_wp_error( $result ) ) {
				$errors[] = $result;
				$this->log( 
					sprintf( 'Failed to enroll user %d in course %d: %s', $user->ID, $course_id, $result->get_error_message() ), 
					'error' 
				);
			} else {
				$enrollment_results[] = $course_id;
				$this->log_enrollment_action( $user->ID, $course_id, 'enrolled', 'success', $transaction_id );
			}
		}

		if ( ! empty( $errors ) && empty( $enrollment_results ) ) {
			// All enrollments failed
			return new WP_Error( 'all_enrollments_failed', 'All course enrollments failed', $errors );
		}

		return true;
	}

	/**
	 * Process payment failure/cancellation for course unenrollment.
	 *
	 * @since    1.0.0
	 * @param    array     $payment_data    Payment data from webhook.
	 * @return   bool|WP_Error              True on success, WP_Error on failure.
	 */
	public function process_payment_failure( $payment_data ) {
		$user_email = $payment_data['user_email'] ?? '';
		$product_id = $payment_data['product_id'] ?? '';
		$transaction_id = $payment_data['transaction_id'] ?? '';

		if ( empty( $user_email ) ) {
			return new WP_Error( 'missing_user_email', 'User email is required' );
		}

		// Get user
		$user = get_user_by( 'email', $user_email );
		if ( ! $user ) {
			$this->log( 
				sprintf( 'User with email %s not found for unenrollment', $user_email ), 
				'warning' 
			);
			return true; // User doesn't exist, nothing to unenroll
		}

		// Get mapped courses for this product
		$course_ids = ! empty( $product_id ) ? $this->get_product_course_mapping( $product_id ) : array();
		
		// If no product mapping, get all enrolled courses for this user
		if ( empty( $course_ids ) ) {
			$course_ids = $this->get_user_courses( $user->ID );
		}

		$unenrollment_results = array();
		$errors = array();

		// Unenroll user from each course
		foreach ( $course_ids as $course_id ) {
			$result = $this->unenroll_user( $user->ID, $course_id );

			if ( is_wp_error( $result ) ) {
				$errors[] = $result;
				$this->log( 
					sprintf( 'Failed to unenroll user %d from course %d: %s', $user->ID, $course_id, $result->get_error_message() ), 
					'error' 
				);
			} else {
				$unenrollment_results[] = $course_id;
				$this->log_enrollment_action( $user->ID, $course_id, 'unenrolled', 'success', $transaction_id );
			}
		}

		return true;
	}

	/**
	 * Get or create user from payment data.
	 *
	 * @since    1.0.0
	 * @param    array     $payment_data    Payment data containing user information.
	 * @return   WP_User|WP_Error           User object or WP_Error on failure.
	 */
	private function get_or_create_user( $payment_data ) {
		$user_email = sanitize_email( $payment_data['user_email'] );
		$user_name = sanitize_text_field( $payment_data['user_name'] ?? '' );

		// Try to get existing user
		$user = get_user_by( 'email', $user_email );
		
		if ( $user ) {
			return $user;
		}

		// Create new user
		$username = ! empty( $user_name ) ? sanitize_user( $user_name ) : sanitize_user( $user_email );
		
		// Ensure username is unique
		$original_username = $username;
		$counter = 1;
		while ( username_exists( $username ) ) {
			$username = $original_username . $counter;
			$counter++;
		}

		$user_data = array(
			'user_login' => $username,
			'user_email' => $user_email,
			'user_pass'  => wp_generate_password(),
			'role'       => 'subscriber',
		);

		if ( ! empty( $user_name ) ) {
			$name_parts = explode( ' ', $user_name, 2 );
			$user_data['first_name'] = $name_parts[0];
			if ( isset( $name_parts[1] ) ) {
				$user_data['last_name'] = $name_parts[1];
			}
			$user_data['display_name'] = $user_name;
		}

		$user_id = wp_insert_user( $user_data );
		
		if ( is_wp_error( $user_id ) ) {
			$this->log( 
				sprintf( 'Failed to create user with email %s: %s', $user_email, $user_id->get_error_message() ), 
				'error' 
			);
			return $user_id;
		}

		$this->log( 
			sprintf( 'Created new user %d with email %s', $user_id, $user_email ), 
			'info' 
		);

		return get_user_by( 'id', $user_id );
	}

	/**
	 * Log enrollment action for admin tracking.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id         WordPress user ID.
	 * @param    int       $course_id       Course ID.
	 * @param    string    $action          Action performed (enrolled, unenrolled).
	 * @param    string    $status          Action status (success, failed).
	 * @param    string    $transaction_id  Transaction identifier.
	 * @param    string    $notes           Optional notes.
	 */
	private function log_enrollment_action( $user_id, $course_id, $action, $status, $transaction_id = '', $notes = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_enrollment_logs';
		
		$wpdb->insert(
			$table_name,
			array(
				'user_id'        => $user_id,
				'course_id'      => $course_id,
				'action'         => $action,
				'status'         => $status,
				'transaction_id' => $transaction_id,
				'lms'            => $this->lms_id,
				'notes'          => $notes,
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}
}