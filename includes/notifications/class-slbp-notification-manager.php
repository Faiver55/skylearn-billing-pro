<?php
/**
 * The notification manager for handling all notification types.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/notifications
 */

/**
 * The notification manager class.
 *
 * Handles registration, sending, and management of all notification types.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/notifications
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Notification_Manager {

	/**
	 * Registered notification types.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $notification_types    Array of registered notification types.
	 */
	private $notification_types = array();

	/**
	 * Initialize the notification manager.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->register_default_notifications();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Hook into payment events
		add_action( 'slbp_payment_success', array( $this, 'handle_payment_success' ), 10, 2 );
		add_action( 'slbp_payment_failed', array( $this, 'handle_payment_failed' ), 10, 2 );
		add_action( 'slbp_subscription_created', array( $this, 'handle_subscription_created' ), 10, 2 );
		add_action( 'slbp_subscription_cancelled', array( $this, 'handle_subscription_cancelled' ), 10, 2 );
		add_action( 'slbp_subscription_renewed', array( $this, 'handle_subscription_renewed' ), 10, 2 );
		add_action( 'slbp_enrollment_created', array( $this, 'handle_enrollment_created' ), 10, 2 );
		add_action( 'slbp_refund_processed', array( $this, 'handle_refund_processed' ), 10, 2 );

		// Schedule expiry warnings
		add_action( 'slbp_daily_cron', array( $this, 'check_upcoming_expiries' ) );
	}

	/**
	 * Register default notification types.
	 *
	 * @since    1.0.0
	 */
	private function register_default_notifications() {
		$this->register_notification_type( 'payment_success', array(
			'name'        => __( 'Payment Success', 'skylearn-billing-pro' ),
			'description' => __( 'Sent when a payment is successfully processed', 'skylearn-billing-pro' ),
			'template'    => 'payment-success',
			'channels'    => array( 'email', 'in_app' ),
		) );

		$this->register_notification_type( 'payment_failed', array(
			'name'        => __( 'Payment Failed', 'skylearn-billing-pro' ),
			'description' => __( 'Sent when a payment fails', 'skylearn-billing-pro' ),
			'template'    => 'payment-failed',
			'channels'    => array( 'email', 'in_app' ),
		) );

		$this->register_notification_type( 'subscription_created', array(
			'name'        => __( 'Subscription Created', 'skylearn-billing-pro' ),
			'description' => __( 'Sent when a new subscription is created', 'skylearn-billing-pro' ),
			'template'    => 'subscription-created',
			'channels'    => array( 'email', 'in_app' ),
		) );

		$this->register_notification_type( 'subscription_cancelled', array(
			'name'        => __( 'Subscription Cancelled', 'skylearn-billing-pro' ),
			'description' => __( 'Sent when a subscription is cancelled', 'skylearn-billing-pro' ),
			'template'    => 'subscription-cancelled',
			'channels'    => array( 'email', 'in_app' ),
		) );

		$this->register_notification_type( 'subscription_renewed', array(
			'name'        => __( 'Subscription Renewed', 'skylearn-billing-pro' ),
			'description' => __( 'Sent when a subscription is renewed', 'skylearn-billing-pro' ),
			'template'    => 'subscription-renewed',
			'channels'    => array( 'email', 'in_app' ),
		) );

		$this->register_notification_type( 'enrollment_created', array(
			'name'        => __( 'Course Enrollment', 'skylearn-billing-pro' ),
			'description' => __( 'Sent when a user is enrolled in a course', 'skylearn-billing-pro' ),
			'template'    => 'enrollment-created',
			'channels'    => array( 'email', 'in_app' ),
		) );

		$this->register_notification_type( 'subscription_expiring', array(
			'name'        => __( 'Subscription Expiring', 'skylearn-billing-pro' ),
			'description' => __( 'Sent when a subscription is about to expire', 'skylearn-billing-pro' ),
			'template'    => 'subscription-expiring',
			'channels'    => array( 'email', 'in_app' ),
		) );

		$this->register_notification_type( 'refund_processed', array(
			'name'        => __( 'Refund Processed', 'skylearn-billing-pro' ),
			'description' => __( 'Sent when a refund is processed', 'skylearn-billing-pro' ),
			'template'    => 'refund-processed',
			'channels'    => array( 'email', 'in_app' ),
		) );
	}

	/**
	 * Register a notification type.
	 *
	 * @since    1.0.0
	 * @param    string $type    The notification type ID.
	 * @param    array  $args    The notification type arguments.
	 */
	public function register_notification_type( $type, $args ) {
		$defaults = array(
			'name'        => '',
			'description' => '',
			'template'    => '',
			'channels'    => array( 'email' ),
		);

		$this->notification_types[ $type ] = wp_parse_args( $args, $defaults );

		/**
		 * Fires after a notification type is registered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $type The notification type ID.
		 * @param array  $args The notification type arguments.
		 */
		do_action( 'slbp_notification_type_registered', $type, $args );
	}

	/**
	 * Send a notification.
	 *
	 * @since    1.0.0
	 * @param    string $type       The notification type.
	 * @param    int    $user_id    The user ID to send to.
	 * @param    array  $data       Additional data for the notification.
	 * @param    array  $channels   Override default channels.
	 * @return   bool               True if notification was sent successfully.
	 */
	public function send_notification( $type, $user_id, $data = array(), $channels = null ) {
		if ( ! isset( $this->notification_types[ $type ] ) ) {
			return false;
		}

		$notification_config = $this->notification_types[ $type ];
		$channels = $channels ?: $notification_config['channels'];

		// Check user preferences
		$user_preferences = $this->get_user_notification_preferences( $user_id );
		if ( ! $this->should_send_notification( $type, $user_id, $user_preferences ) ) {
			return false;
		}

		$success = true;

		foreach ( $channels as $channel ) {
			$result = $this->send_via_channel( $channel, $type, $user_id, $data );
			if ( ! $result ) {
				$success = false;
			}
		}

		/**
		 * Fires after a notification is sent.
		 *
		 * @since 1.0.0
		 *
		 * @param string $type    The notification type.
		 * @param int    $user_id The user ID.
		 * @param array  $data    The notification data.
		 * @param bool   $success Whether the notification was sent successfully.
		 */
		do_action( 'slbp_notification_sent', $type, $user_id, $data, $success );

		return $success;
	}

	/**
	 * Send notification via specific channel.
	 *
	 * @since    1.0.0
	 * @param    string $channel   The notification channel.
	 * @param    string $type      The notification type.
	 * @param    int    $user_id   The user ID.
	 * @param    array  $data      The notification data.
	 * @return   bool              True if sent successfully.
	 */
	private function send_via_channel( $channel, $type, $user_id, $data ) {
		switch ( $channel ) {
			case 'email':
				return $this->send_email_notification( $type, $user_id, $data );
			case 'in_app':
				return $this->send_in_app_notification( $type, $user_id, $data );
			default:
				/**
				 * Allow custom notification channels.
				 *
				 * @since 1.0.0
				 *
				 * @param bool   $result  The result of sending the notification.
				 * @param string $channel The notification channel.
				 * @param string $type    The notification type.
				 * @param int    $user_id The user ID.
				 * @param array  $data    The notification data.
				 */
				return apply_filters( 'slbp_send_notification_channel', false, $channel, $type, $user_id, $data );
		}
	}

	/**
	 * Send email notification.
	 *
	 * @since    1.0.0
	 * @param    string $type      The notification type.
	 * @param    int    $user_id   The user ID.
	 * @param    array  $data      The notification data.
	 * @return   bool              True if sent successfully.
	 */
	private function send_email_notification( $type, $user_id, $data ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		$template_manager = new SLBP_Email_Template_Manager();
		$email_content = $template_manager->get_email_content( $type, $data );

		if ( ! $email_content ) {
			return false;
		}

		$subject = $email_content['subject'];
		$message = $email_content['message'];
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		return wp_mail( $user->user_email, $subject, $message, $headers );
	}

	/**
	 * Send in-app notification.
	 *
	 * @since    1.0.0
	 * @param    string $type      The notification type.
	 * @param    int    $user_id   The user ID.
	 * @param    array  $data      The notification data.
	 * @return   bool              True if sent successfully.
	 */
	private function send_in_app_notification( $type, $user_id, $data ) {
		global $wpdb;

		$notification_config = $this->notification_types[ $type ];
		$template_manager = new SLBP_Email_Template_Manager();
		$content = $template_manager->get_notification_content( $type, $data );

		$table_name = $wpdb->prefix . 'slbp_notifications';

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'     => $user_id,
				'type'        => $type,
				'title'       => $content['title'] ?? $notification_config['name'],
				'message'     => $content['message'] ?? '',
				'data'        => maybe_serialize( $data ),
				'is_read'     => 0,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Get user notification preferences.
	 *
	 * @since    1.0.0
	 * @param    int $user_id The user ID.
	 * @return   array        The user preferences.
	 */
	public function get_user_notification_preferences( $user_id ) {
		$defaults = array();
		foreach ( $this->notification_types as $type => $config ) {
			$defaults[ $type ] = array(
				'email'  => true,
				'in_app' => true,
			);
		}

		$preferences = get_user_meta( $user_id, 'slbp_notification_preferences', true );
		return wp_parse_args( $preferences, $defaults );
	}

	/**
	 * Update user notification preferences.
	 *
	 * @since    1.0.0
	 * @param    int   $user_id     The user ID.
	 * @param    array $preferences The preferences to update.
	 * @return   bool               True if updated successfully.
	 */
	public function update_user_notification_preferences( $user_id, $preferences ) {
		return update_user_meta( $user_id, 'slbp_notification_preferences', $preferences );
	}

	/**
	 * Check if notification should be sent based on user preferences.
	 *
	 * @since    1.0.0
	 * @param    string $type        The notification type.
	 * @param    int    $user_id     The user ID.
	 * @param    array  $preferences The user preferences.
	 * @return   bool                True if notification should be sent.
	 */
	private function should_send_notification( $type, $user_id, $preferences ) {
		if ( ! isset( $preferences[ $type ] ) ) {
			return true; // Default to sending if no preference set
		}

		$type_prefs = $preferences[ $type ];
		
		// Check if at least one channel is enabled
		return ( $type_prefs['email'] ?? true ) || ( $type_prefs['in_app'] ?? true );
	}

	/**
	 * Event handlers for different events.
	 */

	/**
	 * Handle payment success event.
	 *
	 * @since    1.0.0
	 * @param    int   $user_id The user ID.
	 * @param    array $data    Payment data.
	 */
	public function handle_payment_success( $user_id, $data ) {
		$this->send_notification( 'payment_success', $user_id, $data );
	}

	/**
	 * Handle payment failed event.
	 *
	 * @since    1.0.0
	 * @param    int   $user_id The user ID.
	 * @param    array $data    Payment data.
	 */
	public function handle_payment_failed( $user_id, $data ) {
		$this->send_notification( 'payment_failed', $user_id, $data );
	}

	/**
	 * Handle subscription created event.
	 *
	 * @since    1.0.0
	 * @param    int   $user_id The user ID.
	 * @param    array $data    Subscription data.
	 */
	public function handle_subscription_created( $user_id, $data ) {
		$this->send_notification( 'subscription_created', $user_id, $data );
	}

	/**
	 * Handle subscription cancelled event.
	 *
	 * @since    1.0.0
	 * @param    int   $user_id The user ID.
	 * @param    array $data    Subscription data.
	 */
	public function handle_subscription_cancelled( $user_id, $data ) {
		$this->send_notification( 'subscription_cancelled', $user_id, $data );
	}

	/**
	 * Handle subscription renewed event.
	 *
	 * @since    1.0.0
	 * @param    int   $user_id The user ID.
	 * @param    array $data    Subscription data.
	 */
	public function handle_subscription_renewed( $user_id, $data ) {
		$this->send_notification( 'subscription_renewed', $user_id, $data );
	}

	/**
	 * Handle enrollment created event.
	 *
	 * @since    1.0.0
	 * @param    int   $user_id The user ID.
	 * @param    array $data    Enrollment data.
	 */
	public function handle_enrollment_created( $user_id, $data ) {
		$this->send_notification( 'enrollment_created', $user_id, $data );
	}

	/**
	 * Handle refund processed event.
	 *
	 * @since    1.0.0
	 * @param    int   $user_id The user ID.
	 * @param    array $data    Refund data.
	 */
	public function handle_refund_processed( $user_id, $data ) {
		$this->send_notification( 'refund_processed', $user_id, $data );
	}

	/**
	 * Check for upcoming subscription expiries.
	 *
	 * @since    1.0.0
	 */
	public function check_upcoming_expiries() {
		global $wpdb;

		// Get subscriptions expiring in the next 7 days
		$expiry_date = date( 'Y-m-d', strtotime( '+7 days' ) );
		
		$table_name = $wpdb->prefix . 'slbp_subscriptions';
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT user_id, subscription_id, expires_at FROM {$table_name} 
			 WHERE expires_at <= %s AND status = 'active' AND expiry_notified = 0",
			$expiry_date
		) );

		foreach ( $results as $subscription ) {
			$this->send_notification( 'subscription_expiring', $subscription->user_id, array(
				'subscription_id' => $subscription->subscription_id,
				'expires_at'      => $subscription->expires_at,
			) );

			// Mark as notified
			$wpdb->update(
				$table_name,
				array( 'expiry_notified' => 1 ),
				array( 'subscription_id' => $subscription->subscription_id ),
				array( '%d' ),
				array( '%s' )
			);
		}
	}

	/**
	 * Get all notification types.
	 *
	 * @since    1.0.0
	 * @return   array The registered notification types.
	 */
	public function get_notification_types() {
		return $this->notification_types;
	}

	/**
	 * Get in-app notifications for a user.
	 *
	 * @since    1.0.0
	 * @param    int  $user_id The user ID.
	 * @param    int  $limit   The number of notifications to retrieve.
	 * @param    bool $unread_only Whether to only get unread notifications.
	 * @return   array          The notifications.
	 */
	public function get_user_notifications( $user_id, $limit = 10, $unread_only = false ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_notifications';
		$where_clause = $wpdb->prepare( 'WHERE user_id = %d', $user_id );
		
		if ( $unread_only ) {
			$where_clause .= ' AND is_read = 0';
		}

		$notifications = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC LIMIT %d",
			$limit
		) );

		// Unserialize data field
		foreach ( $notifications as &$notification ) {
			$notification->data = maybe_unserialize( $notification->data );
		}

		return $notifications;
	}

	/**
	 * Mark notification as read.
	 *
	 * @since    1.0.0
	 * @param    int $notification_id The notification ID.
	 * @param    int $user_id         The user ID (for security).
	 * @return   bool                 True if updated successfully.
	 */
	public function mark_notification_read( $notification_id, $user_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_notifications';
		$result = $wpdb->update(
			$table_name,
			array( 'is_read' => 1 ),
			array( 
				'id' => $notification_id,
				'user_id' => $user_id 
			),
			array( '%d' ),
			array( '%d', '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get unread notification count for a user.
	 *
	 * @since    1.0.0
	 * @param    int $user_id The user ID.
	 * @return   int          The count of unread notifications.
	 */
	public function get_unread_count( $user_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'slbp_notifications';
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND is_read = 0",
			$user_id
		) );

		return (int) $count;
	}
}