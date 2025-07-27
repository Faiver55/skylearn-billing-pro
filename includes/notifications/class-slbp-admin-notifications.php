<?php
/**
 * The admin notifications functionality.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/notifications
 */

/**
 * The admin notifications class.
 *
 * Handles admin notification center, dashboard widget, and notification management.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/notifications
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Admin_Notifications {

	/**
	 * The notification manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Notification_Manager    $notification_manager    The notification manager.
	 */
	private $notification_manager;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    SLBP_Notification_Manager $notification_manager The notification manager instance.
	 */
	public function __construct( $notification_manager ) {
		$this->notification_manager = $notification_manager;
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Add dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		
		// Add admin bar notification icon
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_notification' ), 999 );
		
		// AJAX handlers
		add_action( 'wp_ajax_slbp_get_notifications', array( $this, 'ajax_get_notifications' ) );
		add_action( 'wp_ajax_slbp_mark_notification_read', array( $this, 'ajax_mark_notification_read' ) );
		add_action( 'wp_ajax_slbp_update_notification_preferences', array( $this, 'ajax_update_notification_preferences' ) );
		
		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add dashboard widget for recent notifications.
	 *
	 * @since    1.0.0
	 */
	public function add_dashboard_widget() {
		if ( current_user_can( 'manage_options' ) ) {
			wp_add_dashboard_widget(
				'slbp_notifications_widget',
				__( 'SkyLearn Billing - Recent Activity', 'skylearn-billing-pro' ),
				array( $this, 'render_dashboard_widget' )
			);
		}
	}

	/**
	 * Add notification icon to admin bar.
	 *
	 * @since    1.0.0
	 * @param    WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 */
	public function add_admin_bar_notification( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$unread_count = $this->notification_manager->get_unread_count( get_current_user_id() );
		
		$title = '<span class="ab-icon dashicons dashicons-bell"></span>';
		if ( $unread_count > 0 ) {
			$title .= '<span class="slbp-notification-count">' . $unread_count . '</span>';
		}

		$wp_admin_bar->add_node( array(
			'id'    => 'slbp-notifications',
			'title' => $title,
			'href'  => '#',
			'meta'  => array(
				'class' => 'slbp-notifications-toggle',
			),
		) );
	}

	/**
	 * Render dashboard widget content.
	 *
	 * @since    1.0.0
	 */
	public function render_dashboard_widget() {
		$notifications = $this->notification_manager->get_user_notifications( get_current_user_id(), 5 );
		
		if ( empty( $notifications ) ) {
			echo '<p>' . esc_html__( 'No recent activity.', 'skylearn-billing-pro' ) . '</p>';
			return;
		}

		echo '<div class="slbp-dashboard-notifications">';
		foreach ( $notifications as $notification ) {
			$class = $notification->is_read ? 'read' : 'unread';
			echo '<div class="notification-item ' . esc_attr( $class ) . '">';
			echo '<strong>' . esc_html( $notification->title ) . '</strong>';
			echo '<span class="notification-time">' . esc_html( human_time_diff( strtotime( $notification->created_at ) ) ) . ' ' . __( 'ago', 'skylearn-billing-pro' ) . '</span>';
			echo '<p>' . esc_html( wp_trim_words( $notification->message, 15 ) ) . '</p>';
			echo '</div>';
		}
		echo '</div>';

		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=slbp-notifications' ) ) . '">' . 
			 esc_html__( 'View all notifications', 'skylearn-billing-pro' ) . '</a></p>';
	}

	/**
	 * Enqueue admin assets for notifications.
	 *
	 * @since    1.0.0
	 * @param    string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Enqueue on all admin pages for admin bar notifications
		wp_enqueue_style( 
			'slbp-admin-notifications', 
			SLBP_PLUGIN_URL . 'admin/css/notifications.css', 
			array(), 
			SLBP_VERSION 
		);

		wp_enqueue_script( 
			'slbp-admin-notifications', 
			SLBP_PLUGIN_URL . 'admin/js/notifications.js', 
			array( 'jquery' ), 
			SLBP_VERSION, 
			true 
		);

		wp_localize_script( 'slbp-admin-notifications', 'slbp_notifications', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'slbp_notifications_nonce' ),
			'strings'  => array(
				'mark_read'   => __( 'Mark as read', 'skylearn-billing-pro' ),
				'view_all'    => __( 'View all', 'skylearn-billing-pro' ),
				'no_notifications' => __( 'No new notifications', 'skylearn-billing-pro' ),
			),
		) );
	}

	/**
	 * AJAX handler to get notifications.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_notifications() {
		check_ajax_referer( 'slbp_notifications_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions.', 'skylearn-billing-pro' ) );
		}

		$page = intval( $_POST['page'] ?? 1 );
		$per_page = intval( $_POST['per_page'] ?? 10 );
		$unread_only = isset( $_POST['unread_only'] ) && $_POST['unread_only'] === 'true';

		$notifications = $this->notification_manager->get_user_notifications( 
			get_current_user_id(), 
			$per_page, 
			$unread_only 
		);

		$formatted_notifications = array();
		foreach ( $notifications as $notification ) {
			$formatted_notifications[] = array(
				'id'         => $notification->id,
				'type'       => $notification->type,
				'title'      => $notification->title,
				'message'    => $notification->message,
				'is_read'    => (bool) $notification->is_read,
				'created_at' => $notification->created_at,
				'time_ago'   => human_time_diff( strtotime( $notification->created_at ) ),
			);
		}

		wp_send_json_success( array(
			'notifications' => $formatted_notifications,
			'unread_count'  => $this->notification_manager->get_unread_count( get_current_user_id() ),
		) );
	}

	/**
	 * AJAX handler to mark notification as read.
	 *
	 * @since    1.0.0
	 */
	public function ajax_mark_notification_read() {
		check_ajax_referer( 'slbp_notifications_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions.', 'skylearn-billing-pro' ) );
		}

		$notification_id = intval( $_POST['notification_id'] ?? 0 );
		if ( ! $notification_id ) {
			wp_send_json_error( __( 'Invalid notification ID.', 'skylearn-billing-pro' ) );
		}

		$result = $this->notification_manager->mark_notification_read( $notification_id, get_current_user_id() );
		
		if ( $result ) {
			wp_send_json_success( array(
				'unread_count' => $this->notification_manager->get_unread_count( get_current_user_id() ),
			) );
		} else {
			wp_send_json_error( __( 'Failed to mark notification as read.', 'skylearn-billing-pro' ) );
		}
	}

	/**
	 * AJAX handler to update notification preferences.
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_notification_preferences() {
		check_ajax_referer( 'slbp_notifications_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'User not logged in.', 'skylearn-billing-pro' ) );
		}

		$preferences = $_POST['preferences'] ?? array();
		
		// Sanitize preferences
		$clean_preferences = array();
		foreach ( $preferences as $type => $channels ) {
			$clean_preferences[ sanitize_key( $type ) ] = array(
				'email'  => ! empty( $channels['email'] ),
				'in_app' => ! empty( $channels['in_app'] ),
			);
		}

		$result = $this->notification_manager->update_user_notification_preferences( $user_id, $clean_preferences );
		
		if ( $result ) {
			wp_send_json_success( __( 'Preferences updated successfully.', 'skylearn-billing-pro' ) );
		} else {
			wp_send_json_error( __( 'Failed to update preferences.', 'skylearn-billing-pro' ) );
		}
	}

	/**
	 * Render the notifications admin page.
	 *
	 * @since    1.0.0
	 */
	public function render_notifications_page() {
		$active_tab = $_GET['tab'] ?? 'notifications';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'SkyLearn Billing - Notifications', 'skylearn-billing-pro' ); ?></h1>
			
			<nav class="nav-tab-wrapper">
				<a href="?page=slbp-notifications&tab=notifications" 
				   class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'All Notifications', 'skylearn-billing-pro' ); ?>
				</a>
				<a href="?page=slbp-notifications&tab=preferences" 
				   class="nav-tab <?php echo $active_tab === 'preferences' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Preferences', 'skylearn-billing-pro' ); ?>
				</a>
				<a href="?page=slbp-notifications&tab=templates" 
				   class="nav-tab <?php echo $active_tab === 'templates' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Email Templates', 'skylearn-billing-pro' ); ?>
				</a>
			</nav>

			<div class="tab-content">
				<?php
				switch ( $active_tab ) {
					case 'preferences':
						$this->render_preferences_tab();
						break;
					case 'templates':
						$this->render_templates_tab();
						break;
					default:
						$this->render_notifications_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render notifications tab.
	 *
	 * @since    1.0.0
	 */
	private function render_notifications_tab() {
		$notifications = $this->notification_manager->get_user_notifications( get_current_user_id(), 50 );
		?>
		<div class="slbp-notifications-list">
			<div class="tablenav top">
				<div class="alignleft actions">
					<select id="bulk-action-selector-top">
						<option value="-1"><?php esc_html_e( 'Bulk Actions', 'skylearn-billing-pro' ); ?></option>
						<option value="mark_read"><?php esc_html_e( 'Mark as Read', 'skylearn-billing-pro' ); ?></option>
					</select>
					<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'skylearn-billing-pro' ); ?>">
				</div>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column">
							<input type="checkbox" id="cb-select-all-1">
						</td>
						<th class="manage-column"><?php esc_html_e( 'Type', 'skylearn-billing-pro' ); ?></th>
						<th class="manage-column"><?php esc_html_e( 'Title', 'skylearn-billing-pro' ); ?></th>
						<th class="manage-column"><?php esc_html_e( 'Message', 'skylearn-billing-pro' ); ?></th>
						<th class="manage-column"><?php esc_html_e( 'Date', 'skylearn-billing-pro' ); ?></th>
						<th class="manage-column"><?php esc_html_e( 'Status', 'skylearn-billing-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $notifications ) ) : ?>
						<tr>
							<td colspan="6" class="no-items"><?php esc_html_e( 'No notifications found.', 'skylearn-billing-pro' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $notifications as $notification ) : ?>
							<tr class="<?php echo $notification->is_read ? 'read' : 'unread'; ?>">
								<th scope="row" class="check-column">
									<input type="checkbox" name="notification[]" value="<?php echo esc_attr( $notification->id ); ?>">
								</th>
								<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $notification->type ) ) ); ?></td>
								<td><strong><?php echo esc_html( $notification->title ); ?></strong></td>
								<td><?php echo esc_html( wp_trim_words( $notification->message, 10 ) ); ?></td>
								<td><?php echo esc_html( mysql2date( 'M j, Y g:i A', $notification->created_at ) ); ?></td>
								<td>
									<?php if ( $notification->is_read ) : ?>
										<span class="read-status"><?php esc_html_e( 'Read', 'skylearn-billing-pro' ); ?></span>
									<?php else : ?>
										<button type="button" class="button-link mark-read" data-notification-id="<?php echo esc_attr( $notification->id ); ?>">
											<?php esc_html_e( 'Mark as Read', 'skylearn-billing-pro' ); ?>
										</button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render preferences tab.
	 *
	 * @since    1.0.0
	 */
	private function render_preferences_tab() {
		$notification_types = $this->notification_manager->get_notification_types();
		$user_preferences = $this->notification_manager->get_user_notification_preferences( get_current_user_id() );
		?>
		<form method="post" action="" id="slbp-notification-preferences-form">
			<?php wp_nonce_field( 'slbp_notification_preferences', 'slbp_nonce' ); ?>
			
			<table class="form-table">
				<tbody>
					<?php foreach ( $notification_types as $type => $config ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $config['name'] ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php echo esc_html( $config['name'] ); ?></legend>
									<label>
										<input type="checkbox" 
											   name="preferences[<?php echo esc_attr( $type ); ?>][email]" 
											   value="1" 
											   <?php checked( $user_preferences[ $type ]['email'] ?? true ); ?>>
										<?php esc_html_e( 'Email', 'skylearn-billing-pro' ); ?>
									</label><br>
									<label>
										<input type="checkbox" 
											   name="preferences[<?php echo esc_attr( $type ); ?>][in_app]" 
											   value="1" 
											   <?php checked( $user_preferences[ $type ]['in_app'] ?? true ); ?>>
										<?php esc_html_e( 'In-App', 'skylearn-billing-pro' ); ?>
									</label>
									<p class="description"><?php echo esc_html( $config['description'] ); ?></p>
								</fieldset>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			
			<?php submit_button( __( 'Save Preferences', 'skylearn-billing-pro' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Render templates tab.
	 *
	 * @since    1.0.0
	 */
	private function render_templates_tab() {
		?>
		<div class="slbp-email-templates">
			<p><?php esc_html_e( 'Customize email templates for different notification types.', 'skylearn-billing-pro' ); ?></p>
			<p><?php esc_html_e( 'Available placeholders: {{user_name}}, {{course_name}}, {{amount}}, {{currency}}, {{date}}, {{site_name}}', 'skylearn-billing-pro' ); ?></p>
			
			<!-- Template customization interface will be added in future -->
			<div class="notice notice-info">
				<p><?php esc_html_e( 'Email template customization will be available in a future update.', 'skylearn-billing-pro' ); ?></p>
			</div>
		</div>
		<?php
	}
}