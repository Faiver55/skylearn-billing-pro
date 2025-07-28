<?php
/**
 * Enrollment Management Admin Class
 *
 * Handles admin-specific enrollment management functionality including
 * AJAX operations, bulk actions, and enrollment reporting.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 */

/**
 * Enrollment Management Admin Class
 *
 * Provides admin functionality for managing user enrollments,
 * viewing logs, and performing bulk operations.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Enrollment_Admin {

	/**
	 * Plugin instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Plugin    $plugin    Plugin instance.
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 * @param    SLBP_Plugin    $plugin    Plugin instance.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// AJAX handlers
		add_action( 'wp_ajax_slbp_search_users', array( $this, 'ajax_search_users' ) );
		add_action( 'wp_ajax_slbp_search_courses', array( $this, 'ajax_search_courses' ) );
		add_action( 'wp_ajax_slbp_get_user_enrollments', array( $this, 'ajax_get_user_enrollments' ) );

		// Admin notices for enrollment actions
		add_action( 'admin_notices', array( $this, 'display_enrollment_notices' ) );

		// Add meta boxes to user profile
		add_action( 'show_user_profile', array( $this, 'add_user_enrollment_meta_box' ) );
		add_action( 'edit_user_profile', array( $this, 'add_user_enrollment_meta_box' ) );
	}

	/**
	 * AJAX handler for searching users.
	 *
	 * @since    1.0.0
	 */
	public function ajax_search_users() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$search_term = sanitize_text_field( $_POST['search'] ?? '' );
		$users = array();

		if ( strlen( $search_term ) >= 2 ) {
			$user_query = new WP_User_Query( array(
				'search'         => '*' . $search_term . '*',
				'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
				'number'         => 20,
				'fields'         => array( 'ID', 'user_login', 'user_email', 'display_name' ),
			) );

			foreach ( $user_query->get_results() as $user ) {
				$users[] = array(
					'id'           => $user->ID,
					'login'        => $user->user_login,
					'email'        => $user->user_email,
					'display_name' => $user->display_name,
					'label'        => sprintf( '%s (%s)', $user->display_name, $user->user_email ),
				);
			}
		}

		wp_send_json_success( $users );
	}

	/**
	 * AJAX handler for searching courses.
	 *
	 * @since    1.0.0
	 */
	public function ajax_search_courses() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$search_term = sanitize_text_field( $_POST['search'] ?? '' );
		$courses = array();

		$course_query = new WP_Query( array(
			'post_type'      => 'sfwd-courses',
			'post_status'    => 'publish',
			's'              => $search_term,
			'posts_per_page' => 20,
		) );

		if ( $course_query->have_posts() ) {
			while ( $course_query->have_posts() ) {
				$course_query->the_post();
				$courses[] = array(
					'id'    => get_the_ID(),
					'title' => get_the_title(),
					'label' => sprintf( '%s (ID: %d)', get_the_title(), get_the_ID() ),
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( $courses );
	}

	/**
	 * AJAX handler for getting user enrollments.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_user_enrollments() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$user_id = intval( $_POST['user_id'] ?? 0 );
		$enrollments = array();

		if ( $user_id > 0 ) {
			$learndash = $this->plugin->get_lms_integration( 'learndash' );
			
			if ( $learndash && $learndash->is_available() ) {
				$course_ids = $learndash->get_user_courses( $user_id );
				
				foreach ( $course_ids as $course_id ) {
					$course = get_post( $course_id );
					if ( $course ) {
						$metadata = $learndash->get_enrollment_metadata( $user_id, $course_id );
						$enrollments[] = array(
							'course_id'      => $course_id,
							'course_title'   => $course->post_title,
							'enrollment_date' => $metadata['enrolled_date'] ?? '',
							'transaction_id' => $metadata['transaction_id'] ?? '',
							'source'         => $metadata['source'] ?? 'unknown',
						);
					}
				}
			}
		}

		wp_send_json_success( $enrollments );
	}

	/**
	 * Display enrollment-related admin notices.
	 *
	 * @since    1.0.0
	 */
	public function display_enrollment_notices() {
		// Check if we're on an SLBP admin page
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'slbp' ) === false ) {
			return;
		}

		// Display any enrollment messages from session
		if ( isset( $_SESSION['slbp_enrollment_message'] ) ) {
			$message = $_SESSION['slbp_enrollment_message'];
			$type = $_SESSION['slbp_enrollment_message_type'] ?? 'success';
			
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible">';
			echo '<p>' . esc_html( $message ) . '</p>';
			echo '</div>';
			
			unset( $_SESSION['slbp_enrollment_message'] );
			unset( $_SESSION['slbp_enrollment_message_type'] );
		}
	}

	/**
	 * Add enrollment meta box to user profile.
	 *
	 * @since    1.0.0
	 * @param    WP_User    $user    User object.
	 */
	public function add_user_enrollment_meta_box( $user ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$learndash = $this->plugin->get_lms_integration( 'learndash' );
		
		if ( ! $learndash || ! $learndash->is_available() ) {
			return;
		}

		?>
		<h3><?php _e( 'SkyLearn Billing Pro - Course Enrollments', 'skylearn-billing-pro' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Enrolled Courses', 'skylearn-billing-pro' ); ?></th>
				<td>
					<div id="slbp-user-enrollments" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
						<p><?php _e( 'Loading enrollments...', 'skylearn-billing-pro' ); ?></p>
					</div>
				</td>
			</tr>
		</table>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			const userId = $('#slbp-user-enrollments').data('user-id');
			
			$.post(ajaxurl, {
				action: 'slbp_get_user_enrollments',
				user_id: userId,
				nonce: '<?php echo wp_create_nonce( 'slbp_admin_nonce' ); ?>'
			}, function(response) {
				if (response.success) {
					let html = '';
					if (response.data.length > 0) {
						html = '<table class="widefat"><thead><tr><th>Course</th><th>Enrolled Date</th><th>Transaction ID</th><th>Source</th></tr></thead><tbody>';
						response.data.forEach(function(enrollment) {
							html += '<tr>';
							html += '<td>' + enrollment.course_title + '</td>';
							html += '<td>' + (enrollment.enrollment_date || '—') + '</td>';
							html += '<td>' + (enrollment.transaction_id || '—') + '</td>';
							html += '<td>' + enrollment.source + '</td>';
							html += '</tr>';
						});
						html += '</tbody></table>';
					} else {
						html = '<p>' + '<?php _e( 'No course enrollments found.', 'skylearn-billing-pro' ); ?>' + '</p>';
					}
					$('#slbp-user-enrollments').html(html);
				} else {
					$('#slbp-user-enrollments').html('<p>Error loading enrollments: ' + (response.data || 'Unknown error') + '</p>');
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Get enrollment statistics.
	 *
	 * @since    1.0.0
	 * @return   array    Enrollment statistics.
	 */
	public function get_enrollment_stats() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_enrollment_logs';
		
		$stats = array(
			'total_enrollments'   => 0,
			'successful_enrollments' => 0,
			'failed_enrollments'  => 0,
			'total_unenrollments' => 0,
			'recent_activity'     => 0,
		);

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
			$stats['total_enrollments'] = $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$table_name} WHERE action = 'enrolled'" 
			);
			
			$stats['successful_enrollments'] = $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$table_name} WHERE action = 'enrolled' AND status = 'success'" 
			);
			
			$stats['failed_enrollments'] = $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$table_name} WHERE action = 'enrolled' AND status = 'failed'" 
			);
			
			$stats['total_unenrollments'] = $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$table_name} WHERE action = 'unenrolled'" 
			);
			
			$stats['recent_activity'] = $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$table_name} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" 
			);
		}

		return $stats;
	}

	/**
	 * Export enrollment logs to CSV.
	 *
	 * @since    1.0.0
	 * @param    array     $filters    Optional filters for export.
	 */
	public function export_enrollment_logs( $filters = array() ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		global $wpdb;
		
		$table_name = $wpdb->prefix . 'slbp_enrollment_logs';
		
		// Build query with filters
		$where_clauses = array();
		$prepare_values = array();

		if ( ! empty( $filters['action'] ) ) {
			$where_clauses[] = "l.action = %s";
			$prepare_values[] = $filters['action'];
		}

		if ( ! empty( $filters['status'] ) ) {
			$where_clauses[] = "l.status = %s";
			$prepare_values[] = $filters['status'];
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where_clauses[] = "l.created_at >= %s";
			$prepare_values[] = $filters['date_from'];
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where_clauses[] = "l.created_at <= %s";
			$prepare_values[] = $filters['date_to'];
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		$query = "SELECT l.*, u.user_login, u.user_email, u.display_name, p.post_title 
				  FROM {$table_name} l 
				  LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
				  LEFT JOIN {$wpdb->posts} p ON l.course_id = p.ID
				  {$where_sql}
				  ORDER BY l.created_at DESC";

		if ( ! empty( $prepare_values ) ) {
			$results = $wpdb->get_results( $wpdb->prepare( $query, $prepare_values ) );
		} else {
			$results = $wpdb->get_results( $query );
		}

		// Set headers for CSV download
		$filename = 'enrollment-logs-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		// Create CSV output
		$output = fopen( 'php://output', 'w' );

		// CSV headers
		fputcsv( $output, array(
			'Date',
			'User Login',
			'User Email',
			'User Display Name',
			'Course Title',
			'Action',
			'Status',
			'Transaction ID',
			'LMS',
			'Notes',
		) );

		// CSV data
		foreach ( $results as $row ) {
			fputcsv( $output, array(
				$row->created_at,
				$row->user_login,
				$row->user_email,
				$row->display_name,
				$row->post_title,
				$row->action,
				$row->status,
				$row->transaction_id,
				$row->lms,
				$row->notes,
			) );
		}

		fclose( $output );
		exit;
	}
}