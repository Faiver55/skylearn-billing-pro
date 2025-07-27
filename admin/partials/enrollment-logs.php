<?php
/**
 * Enrollment Logs Admin Page
 *
 * This file is used to display the enrollment logs page in the admin area.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access is forbidden.' );
}

/**
 * Enrollment Logs List Table Class
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SLBP_Enrollment_Logs_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'enrollment_log',
			'plural'   => 'enrollment_logs',
			'ajax'     => false,
		) );
	}

	/**
	 * Get table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'             => '<input type="checkbox" />',
			'created_at'     => __( 'Date', 'skylearn-billing-pro' ),
			'user'           => __( 'User', 'skylearn-billing-pro' ),
			'course'         => __( 'Course', 'skylearn-billing-pro' ),
			'action'         => __( 'Action', 'skylearn-billing-pro' ),
			'status'         => __( 'Status', 'skylearn-billing-pro' ),
			'transaction_id' => __( 'Transaction ID', 'skylearn-billing-pro' ),
			'lms'            => __( 'LMS', 'skylearn-billing-pro' ),
			'notes'          => __( 'Notes', 'skylearn-billing-pro' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'created_at' => array( 'created_at', true ),
			'user'       => array( 'user_id', false ),
			'course'     => array( 'course_id', false ),
			'action'     => array( 'action', false ),
			'status'     => array( 'status', false ),
			'lms'        => array( 'lms', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'skylearn-billing-pro' ),
		);
	}

	/**
	 * Column checkbox.
	 *
	 * @param object $item
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="log_id[]" value="%s" />', $item->id );
	}

	/**
	 * Column created_at.
	 *
	 * @param object $item
	 * @return string
	 */
	public function column_created_at( $item ) {
		$date = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->created_at );
		return esc_html( $date );
	}

	/**
	 * Column user.
	 *
	 * @param object $item
	 * @return string
	 */
	public function column_user( $item ) {
		$user = get_user_by( 'id', $item->user_id );
		if ( $user ) {
			$edit_link = get_edit_user_link( $user->ID );
			return sprintf( '<a href="%s">%s (%s)</a>', $edit_link, esc_html( $user->display_name ), esc_html( $user->user_email ) );
		}
		return sprintf( __( 'User ID: %d (deleted)', 'skylearn-billing-pro' ), $item->user_id );
	}

	/**
	 * Column course.
	 *
	 * @param object $item
	 * @return string
	 */
	public function column_course( $item ) {
		$course = get_post( $item->course_id );
		if ( $course ) {
			$edit_link = get_edit_post_link( $course->ID );
			return sprintf( '<a href="%s">%s</a>', $edit_link, esc_html( $course->post_title ) );
		}
		return sprintf( __( 'Course ID: %d (deleted)', 'skylearn-billing-pro' ), $item->course_id );
	}

	/**
	 * Column action.
	 *
	 * @param object $item
	 * @return string
	 */
	public function column_action( $item ) {
		$actions = array(
			'enrolled'   => __( 'Enrolled', 'skylearn-billing-pro' ),
			'unenrolled' => __( 'Unenrolled', 'skylearn-billing-pro' ),
		);

		$action_label = isset( $actions[ $item->action ] ) ? $actions[ $item->action ] : $item->action;
		$class = $item->action === 'enrolled' ? 'slbp-action-enrolled' : 'slbp-action-unenrolled';

		return sprintf( '<span class="%s">%s</span>', $class, esc_html( $action_label ) );
	}

	/**
	 * Column status.
	 *
	 * @param object $item
	 * @return string
	 */
	public function column_status( $item ) {
		$class = $item->status === 'success' ? 'slbp-status-success' : 'slbp-status-failed';
		$icon = $item->status === 'success' ? '✓' : '✗';

		return sprintf( '<span class="%s">%s %s</span>', $class, $icon, esc_html( ucfirst( $item->status ) ) );
	}

	/**
	 * Column transaction_id.
	 *
	 * @param object $item
	 * @return string
	 */
	public function column_transaction_id( $item ) {
		if ( ! empty( $item->transaction_id ) ) {
			return '<code>' . esc_html( $item->transaction_id ) . '</code>';
		}
		return '—';
	}

	/**
	 * Column lms.
	 *
	 * @param object $item
	 * @return string
	 */
	public function column_lms( $item ) {
		$lms_names = array(
			'learndash' => 'LearnDash',
		);

		$lms_name = isset( $lms_names[ $item->lms ] ) ? $lms_names[ $item->lms ] : $item->lms;
		return esc_html( $lms_name );
	}

	/**
	 * Column notes.
	 *
	 * @param object $item
	 * @return string
	 */
	public function column_notes( $item ) {
		if ( ! empty( $item->notes ) ) {
			return esc_html( wp_trim_words( $item->notes, 10 ) );
		}
		return '—';
	}

	/**
	 * Prepare items for display.
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = 20;
		$current_page = $this->get_pagenum();
		$table_name = $wpdb->prefix . 'slbp_enrollment_logs';

		// Handle search
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';

		// Handle filters
		$action_filter = isset( $_REQUEST['action_filter'] ) ? sanitize_text_field( $_REQUEST['action_filter'] ) : '';
		$status_filter = isset( $_REQUEST['status_filter'] ) ? sanitize_text_field( $_REQUEST['status_filter'] ) : '';
		$lms_filter = isset( $_REQUEST['lms_filter'] ) ? sanitize_text_field( $_REQUEST['lms_filter'] ) : '';

		// Build WHERE clause
		$where_clauses = array();
		$prepare_values = array();

		if ( ! empty( $search ) ) {
			$where_clauses[] = "(u.user_login LIKE %s OR u.user_email LIKE %s OR p.post_title LIKE %s OR l.transaction_id LIKE %s)";
			$search_term = '%' . $wpdb->esc_like( $search ) . '%';
			$prepare_values[] = $search_term;
			$prepare_values[] = $search_term;
			$prepare_values[] = $search_term;
			$prepare_values[] = $search_term;
		}

		if ( ! empty( $action_filter ) ) {
			$where_clauses[] = "l.action = %s";
			$prepare_values[] = $action_filter;
		}

		if ( ! empty( $status_filter ) ) {
			$where_clauses[] = "l.status = %s";
			$prepare_values[] = $status_filter;
		}

		if ( ! empty( $lms_filter ) ) {
			$where_clauses[] = "l.lms = %s";
			$prepare_values[] = $lms_filter;
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		// Handle sorting
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'l.created_at';
		$order = isset( $_REQUEST['order'] ) && $_REQUEST['order'] === 'asc' ? 'ASC' : 'DESC';

		// Count total items
		$total_query = "SELECT COUNT(*) FROM {$table_name} l 
						LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
						LEFT JOIN {$wpdb->posts} p ON l.course_id = p.ID
						{$where_sql}";

		if ( ! empty( $prepare_values ) ) {
			$total_items = $wpdb->get_var( $wpdb->prepare( $total_query, $prepare_values ) );
		} else {
			$total_items = $wpdb->get_var( $total_query );
		}

		// Get items for current page
		$offset = ( $current_page - 1 ) * $per_page;
		$items_query = "SELECT l.*, u.user_login, u.user_email, u.display_name, p.post_title 
						FROM {$table_name} l 
						LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
						LEFT JOIN {$wpdb->posts} p ON l.course_id = p.ID
						{$where_sql}
						ORDER BY {$orderby} {$order}
						LIMIT %d OFFSET %d";

		$prepare_values[] = $per_page;
		$prepare_values[] = $offset;

		$this->items = $wpdb->get_results( $wpdb->prepare( $items_query, $prepare_values ) );

		// Set pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );

		// Set columns
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}
}

// Handle bulk actions
if ( isset( $_POST['action'] ) && $_POST['action'] === 'delete' && isset( $_POST['log_id'] ) ) {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-enrollment_logs' ) ) {
		wp_die( 'Security check failed' );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'slbp_enrollment_logs';
	$log_ids = array_map( 'intval', $_POST['log_id'] );
	
	if ( ! empty( $log_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $log_ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE id IN ({$placeholders})", $log_ids ) );
		
		echo '<div class="notice notice-success"><p>' . sprintf( 
			_n( '%d log entry deleted.', '%d log entries deleted.', count( $log_ids ), 'skylearn-billing-pro' ), 
			count( $log_ids ) 
		) . '</p></div>';
	}
}

// Handle manual enrollment/unenrollment
if ( isset( $_POST['manual_action'] ) && isset( $_POST['user_id'] ) && isset( $_POST['course_id'] ) ) {
	if ( ! wp_verify_nonce( $_POST['manual_nonce'], 'slbp_manual_enrollment' ) ) {
		wp_die( 'Security check failed' );
	}

	$user_id = intval( $_POST['user_id'] );
	$course_id = intval( $_POST['course_id'] );
	$action = sanitize_text_field( $_POST['manual_action'] );

	$plugin = SLBP_Plugin::get_instance();
	$learndash = $plugin->get_lms_integration( 'learndash' );

	if ( $learndash && $learndash->is_available() ) {
		if ( $action === 'enroll' ) {
			$result = $learndash->enroll_user( $user_id, $course_id, array( 'transaction_id' => 'manual_' . time() ) );
		} else {
			$result = $learndash->unenroll_user( $user_id, $course_id );
		}

		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . sprintf( 
				__( 'User successfully %s.', 'skylearn-billing-pro' ), 
				$action === 'enroll' ? __( 'enrolled', 'skylearn-billing-pro' ) : __( 'unenrolled', 'skylearn-billing-pro' )
			) . '</p></div>';
		}
	} else {
		echo '<div class="notice notice-error"><p>' . __( 'LearnDash integration is not available.', 'skylearn-billing-pro' ) . '</p></div>';
	}
}

// Create list table instance
$list_table = new SLBP_Enrollment_Logs_List_Table();
$list_table->prepare_items();

?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Filters -->
	<div class="slbp-filters" style="margin: 20px 0;">
		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
			
			<select name="action_filter">
				<option value=""><?php _e( 'All Actions', 'skylearn-billing-pro' ); ?></option>
				<option value="enrolled" <?php selected( $_REQUEST['action_filter'] ?? '', 'enrolled' ); ?>><?php _e( 'Enrolled', 'skylearn-billing-pro' ); ?></option>
				<option value="unenrolled" <?php selected( $_REQUEST['action_filter'] ?? '', 'unenrolled' ); ?>><?php _e( 'Unenrolled', 'skylearn-billing-pro' ); ?></option>
			</select>

			<select name="status_filter">
				<option value=""><?php _e( 'All Statuses', 'skylearn-billing-pro' ); ?></option>
				<option value="success" <?php selected( $_REQUEST['status_filter'] ?? '', 'success' ); ?>><?php _e( 'Success', 'skylearn-billing-pro' ); ?></option>
				<option value="failed" <?php selected( $_REQUEST['status_filter'] ?? '', 'failed' ); ?>><?php _e( 'Failed', 'skylearn-billing-pro' ); ?></option>
			</select>

			<select name="lms_filter">
				<option value=""><?php _e( 'All LMS', 'skylearn-billing-pro' ); ?></option>
				<option value="learndash" <?php selected( $_REQUEST['lms_filter'] ?? '', 'learndash' ); ?>><?php _e( 'LearnDash', 'skylearn-billing-pro' ); ?></option>
			</select>

			<?php submit_button( __( 'Filter', 'skylearn-billing-pro' ), 'secondary', 'filter_action', false ); ?>
			
			<?php if ( ! empty( $_REQUEST['action_filter'] ) || ! empty( $_REQUEST['status_filter'] ) || ! empty( $_REQUEST['lms_filter'] ) ): ?>
				<a href="<?php echo admin_url( 'admin.php?page=slbp-enrollment-logs' ); ?>" class="button"><?php _e( 'Clear Filters', 'skylearn-billing-pro' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<!-- Manual Enrollment/Unenrollment -->
	<div class="slbp-manual-enrollment" style="background: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #0073aa;">
		<h3><?php _e( 'Manual Enrollment/Unenrollment', 'skylearn-billing-pro' ); ?></h3>
		<form method="post" style="display: flex; align-items: center; gap: 10px;">
			<?php wp_nonce_field( 'slbp_manual_enrollment', 'manual_nonce' ); ?>
			
			<label for="user_id"><?php _e( 'User ID:', 'skylearn-billing-pro' ); ?></label>
			<input type="number" name="user_id" id="user_id" min="1" required style="width: 80px;" />
			
			<label for="course_id"><?php _e( 'Course ID:', 'skylearn-billing-pro' ); ?></label>
			<input type="number" name="course_id" id="course_id" min="1" required style="width: 80px;" />
			
			<select name="manual_action" required>
				<option value="enroll"><?php _e( 'Enroll', 'skylearn-billing-pro' ); ?></option>
				<option value="unenroll"><?php _e( 'Unenroll', 'skylearn-billing-pro' ); ?></option>
			</select>
			
			<?php submit_button( __( 'Execute', 'skylearn-billing-pro' ), 'primary', 'submit', false ); ?>
		</form>
		<p class="description"><?php _e( 'Use this tool to manually enroll or unenroll users. Enter the WordPress User ID and Course ID.', 'skylearn-billing-pro' ); ?></p>
	</div>

	<!-- List Table -->
	<form method="post">
		<?php 
		$list_table->search_box( __( 'Search logs', 'skylearn-billing-pro' ), 'search_logs' );
		$list_table->display(); 
		?>
	</form>
</div>

<style>
.slbp-action-enrolled {
	color: #007cba;
	font-weight: 500;
}

.slbp-action-unenrolled {
	color: #d63638;
	font-weight: 500;
}

.slbp-status-success {
	color: #00a32a;
	font-weight: 500;
}

.slbp-status-failed {
	color: #d63638;
	font-weight: 500;
}

.slbp-filters form {
	display: flex;
	align-items: center;
	gap: 10px;
}

.slbp-filters select {
	min-width: 120px;
}
</style>