<?php
/**
 * Product Manager Class
 *
 * Handles product mapping and course enrollment management.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 */

/**
 * Product Manager Class
 *
 * Manages product mappings between payment gateways and LMS courses,
 * handles user enrollment and unenrollment.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Product_Manager {

	/**
	 * Logger instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Logger    $logger    Logger instance.
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( class_exists( 'SLBP_Logger' ) ) {
			$this->logger = new SLBP_Logger( 'product_manager' );
		}
	}

	/**
	 * Get product mappings.
	 *
	 * @since    1.0.0
	 * @return   array    Array of product mappings.
	 */
	public function get_product_mappings() {
		$product_settings = get_option( 'slbp_product_settings', array() );
		return $product_settings['product_mappings'] ?? array();
	}

	/**
	 * Get course IDs for a product.
	 *
	 * @since    1.0.0
	 * @param    string    $product_id    Product ID from payment gateway.
	 * @return   array                    Array of course IDs.
	 */
	public function get_courses_for_product( $product_id ) {
		$mappings = $this->get_product_mappings();
		$course_ids = array();

		foreach ( $mappings as $mapping ) {
			if ( $mapping['product_id'] === $product_id && ! empty( $mapping['course_id'] ) ) {
				$course_ids[] = intval( $mapping['course_id'] );
			}
		}

		return $course_ids;
	}

	/**
	 * Get products for a course.
	 *
	 * @since    1.0.0
	 * @param    int       $course_id    Course ID.
	 * @return   array                   Array of product IDs.
	 */
	public function get_products_for_course( $course_id ) {
		$mappings = $this->get_product_mappings();
		$product_ids = array();

		foreach ( $mappings as $mapping ) {
			if ( intval( $mapping['course_id'] ) === $course_id && ! empty( $mapping['product_id'] ) ) {
				$product_ids[] = $mapping['product_id'];
			}
		}

		return $product_ids;
	}

	/**
	 * Enroll user in courses for a product.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id      WordPress user ID.
	 * @param    string    $product_id   Product ID from payment gateway.
	 * @param    array     $args         Additional enrollment arguments.
	 * @return   bool                    True if enrollment successful, false otherwise.
	 */
	public function enroll_user_in_product_courses( $user_id, $product_id, $args = array() ) {
		$course_ids = $this->get_courses_for_product( $product_id );

		if ( empty( $course_ids ) ) {
			$this->log( sprintf( 'No courses mapped for product %s', $product_id ), 'warning' );
			return false;
		}

		$enrolled_courses = array();
		foreach ( $course_ids as $course_id ) {
			if ( $this->enroll_user_in_course( $user_id, $course_id, $args ) ) {
				$enrolled_courses[] = $course_id;
			}
		}

		if ( ! empty( $enrolled_courses ) ) {
			// Store enrollment record
			$this->store_enrollment_record( $user_id, $product_id, $enrolled_courses, 'enrolled' );
			
			// Trigger action for other plugins
			do_action( 'slbp_user_enrolled_in_product', $user_id, $product_id, $enrolled_courses, $args );
			
			$this->log( sprintf( 'User %d enrolled in %d courses for product %s', $user_id, count( $enrolled_courses ), $product_id ), 'info' );
			return true;
		}

		return false;
	}

	/**
	 * Unenroll user from courses for a product.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id      WordPress user ID.
	 * @param    string    $product_id   Product ID from payment gateway.
	 * @param    array     $args         Additional unenrollment arguments.
	 * @return   bool                    True if unenrollment successful, false otherwise.
	 */
	public function unenroll_user_from_product_courses( $user_id, $product_id, $args = array() ) {
		$course_ids = $this->get_courses_for_product( $product_id );

		if ( empty( $course_ids ) ) {
			$this->log( sprintf( 'No courses mapped for product %s', $product_id ), 'warning' );
			return false;
		}

		$unenrolled_courses = array();
		foreach ( $course_ids as $course_id ) {
			if ( $this->unenroll_user_from_course( $user_id, $course_id, $args ) ) {
				$unenrolled_courses[] = $course_id;
			}
		}

		if ( ! empty( $unenrolled_courses ) ) {
			// Store enrollment record
			$this->store_enrollment_record( $user_id, $product_id, $unenrolled_courses, 'unenrolled' );
			
			// Trigger action for other plugins
			do_action( 'slbp_user_unenrolled_from_product', $user_id, $product_id, $unenrolled_courses, $args );
			
			$this->log( sprintf( 'User %d unenrolled from %d courses for product %s', $user_id, count( $unenrolled_courses ), $product_id ), 'info' );
			return true;
		}

		return false;
	}

	/**
	 * Enroll user in a specific course.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id     WordPress user ID.
	 * @param    int       $course_id   Course ID.
	 * @param    array     $args        Additional enrollment arguments.
	 * @return   bool                   True if enrollment successful, false otherwise.
	 */
	public function enroll_user_in_course( $user_id, $course_id, $args = array() ) {
		// Check if LearnDash is available
		if ( function_exists( 'ld_update_course_access' ) ) {
			// Check if user is already enrolled
			if ( sfwd_lms_has_access( $course_id, $user_id ) ) {
				$this->log( sprintf( 'User %d already enrolled in course %d', $user_id, $course_id ), 'info' );
				return true;
			}

			// Enroll user in course
			ld_update_course_access( $user_id, $course_id );
			
			// Verify enrollment
			if ( sfwd_lms_has_access( $course_id, $user_id ) ) {
				$this->log( sprintf( 'User %d successfully enrolled in course %d', $user_id, $course_id ), 'info' );
				
				// Trigger LearnDash hook
				do_action( 'learndash_update_course_access', $user_id, $course_id, true );
				
				return true;
			} else {
				$this->log( sprintf( 'Failed to enroll user %d in course %d', $user_id, $course_id ), 'error' );
				return false;
			}
		} else {
			$this->log( 'LearnDash not available for course enrollment', 'warning' );
			
			// Allow other LMS integrations via action hook
			do_action( 'slbp_enroll_user_in_course', $user_id, $course_id, $args );
			
			return false;
		}
	}

	/**
	 * Unenroll user from a specific course.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id     WordPress user ID.
	 * @param    int       $course_id   Course ID.
	 * @param    array     $args        Additional unenrollment arguments.
	 * @return   bool                   True if unenrollment successful, false otherwise.
	 */
	public function unenroll_user_from_course( $user_id, $course_id, $args = array() ) {
		// Check if LearnDash is available
		if ( function_exists( 'ld_update_course_access' ) ) {
			// Check if user is enrolled
			if ( ! sfwd_lms_has_access( $course_id, $user_id ) ) {
				$this->log( sprintf( 'User %d not enrolled in course %d', $user_id, $course_id ), 'info' );
				return true;
			}

			// Unenroll user from course
			ld_update_course_access( $user_id, $course_id, $remove = true );
			
			// Verify unenrollment
			if ( ! sfwd_lms_has_access( $course_id, $user_id ) ) {
				$this->log( sprintf( 'User %d successfully unenrolled from course %d', $user_id, $course_id ), 'info' );
				
				// Trigger LearnDash hook
				do_action( 'learndash_update_course_access', $user_id, $course_id, false );
				
				return true;
			} else {
				$this->log( sprintf( 'Failed to unenroll user %d from course %d', $user_id, $course_id ), 'error' );
				return false;
			}
		} else {
			$this->log( 'LearnDash not available for course unenrollment', 'warning' );
			
			// Allow other LMS integrations via action hook
			do_action( 'slbp_unenroll_user_from_course', $user_id, $course_id, $args );
			
			return false;
		}
	}

	/**
	 * Store enrollment record.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id     WordPress user ID.
	 * @param    string    $product_id  Product ID.
	 * @param    array     $course_ids  Array of course IDs.
	 * @param    string    $action      Action performed (enrolled/unenrolled).
	 */
	private function store_enrollment_record( $user_id, $product_id, $course_ids, $action ) {
		$record = array(
			'user_id'    => $user_id,
			'product_id' => $product_id,
			'course_ids' => $course_ids,
			'action'     => $action,
			'timestamp'  => current_time( 'timestamp' ),
			'date'       => current_time( 'Y-m-d H:i:s' ),
		);

		// Store in user meta
		$enrollment_history = get_user_meta( $user_id, 'slbp_enrollment_history', true ) ?: array();
		$enrollment_history[] = $record;
		
		// Keep only last 50 records per user
		if ( count( $enrollment_history ) > 50 ) {
			$enrollment_history = array_slice( $enrollment_history, -50 );
		}
		
		update_user_meta( $user_id, 'slbp_enrollment_history', $enrollment_history );

		// Store global enrollment record
		$global_records = get_option( 'slbp_enrollment_records', array() );
		$global_records[] = $record;
		
		// Keep only last 1000 records globally
		if ( count( $global_records ) > 1000 ) {
			$global_records = array_slice( $global_records, -1000 );
		}
		
		update_option( 'slbp_enrollment_records', $global_records );
	}

	/**
	 * Get user enrollment history.
	 *
	 * @since    1.0.0
	 * @param    int       $user_id    WordPress user ID.
	 * @param    int       $limit      Number of records to retrieve.
	 * @return   array                 Array of enrollment records.
	 */
	public function get_user_enrollment_history( $user_id, $limit = 20 ) {
		$history = get_user_meta( $user_id, 'slbp_enrollment_history', true ) ?: array();
		
		// Sort by timestamp descending
		usort( $history, function( $a, $b ) {
			return $b['timestamp'] - $a['timestamp'];
		} );

		return array_slice( $history, 0, $limit );
	}

	/**
	 * Get user's current course enrollments from billing.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID.
	 * @return   array              Array of enrolled course IDs.
	 */
	public function get_user_enrolled_courses( $user_id ) {
		$enrolled_courses = array();
		
		// Get user's active subscriptions
		$subscriptions = get_user_meta( $user_id, 'slbp_subscriptions', true ) ?: array();
		
		foreach ( $subscriptions as $subscription_id ) {
			$subscription = get_option( 'slbp_subscription_' . $subscription_id );
			if ( $subscription && in_array( $subscription['status'], array( 'active', 'trialing' ) ) ) {
				$course_ids = $this->get_courses_for_product( $subscription['product_id'] );
				$enrolled_courses = array_merge( $enrolled_courses, $course_ids );
			}
		}

		// Get user's orders (for one-time purchases)
		$orders = get_user_meta( $user_id, 'slbp_orders', true ) ?: array();
		
		foreach ( $orders as $order_id ) {
			$order = get_option( 'slbp_order_' . $order_id );
			if ( $order && $order['status'] === 'paid' && ! $order['refunded'] ) {
				$course_ids = $this->get_courses_for_product( $order['product_id'] );
				$enrolled_courses = array_merge( $enrolled_courses, $course_ids );
			}
		}

		return array_unique( $enrolled_courses );
	}

	/**
	 * Check if user has access to a course through billing.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id     WordPress user ID.
	 * @param    int    $course_id   Course ID.
	 * @return   bool                True if user has access, false otherwise.
	 */
	public function user_has_course_access( $user_id, $course_id ) {
		$enrolled_courses = $this->get_user_enrolled_courses( $user_id );
		return in_array( $course_id, $enrolled_courses );
	}

	/**
	 * Sync user enrollments with payment status.
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    WordPress user ID.
	 * @return   bool               True if sync successful, false otherwise.
	 */
	public function sync_user_enrollments( $user_id ) {
		$this->log( sprintf( 'Syncing enrollments for user %d', $user_id ), 'info' );
		
		// Get courses user should be enrolled in based on payments
		$expected_courses = $this->get_user_enrolled_courses( $user_id );
		
		// Get courses user is currently enrolled in (via LearnDash)
		$current_courses = array();
		if ( function_exists( 'learndash_user_get_enrolled_courses' ) ) {
			$current_courses = learndash_user_get_enrolled_courses( $user_id );
		}

		$enrolled_count = 0;
		$unenrolled_count = 0;

		// Enroll user in courses they should have access to
		foreach ( $expected_courses as $course_id ) {
			if ( ! in_array( $course_id, $current_courses ) ) {
				if ( $this->enroll_user_in_course( $user_id, $course_id ) ) {
					$enrolled_count++;
				}
			}
		}

		// Note: We don't automatically unenroll from courses as they might have
		// access through other means (manual enrollment, other plugins, etc.)
		// This is a design decision to prevent accidental data loss

		$this->log( sprintf( 'Sync complete for user %d: %d enrollments added', $user_id, $enrolled_count ), 'info' );
		
		return true;
	}

	/**
	 * Log a message.
	 *
	 * @since    1.0.0
	 * @param    string    $message    Log message.
	 * @param    string    $level      Log level.
	 * @param    array     $context    Additional context.
	 */
	private function log( $message, $level = 'info', $context = array() ) {
		if ( $this->logger ) {
			$this->logger->log( $level, $message, $context );
		}
	}
}