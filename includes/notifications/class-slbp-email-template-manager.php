<?php
/**
 * The email template manager for handling notification templates.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/notifications
 */

/**
 * The email template manager class.
 *
 * Handles email template loading, parsing, and rendering for notifications.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/notifications
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Email_Template_Manager {

	/**
	 * Template directory path.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $template_dir    Path to email templates.
	 */
	private $template_dir;

	/**
	 * Initialize the template manager.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->template_dir = SLBP_PLUGIN_PATH . 'admin/partials/email-templates/';
	}

	/**
	 * Get email content for a notification type.
	 *
	 * @since    1.0.0
	 * @param    string $type The notification type.
	 * @param    array  $data The notification data.
	 * @return   array|false  Array with subject and message keys, or false on failure.
	 */
	public function get_email_content( $type, $data = array() ) {
		$template_data = $this->load_template( $type );
		if ( ! $template_data ) {
			return false;
		}

		$subject = $this->parse_template( $template_data['subject'], $data );
		$message = $this->parse_template( $template_data['message'], $data );

		// Wrap message in email layout
		$message = $this->wrap_in_layout( $message, $subject );

		return array(
			'subject' => $subject,
			'message' => $message,
		);
	}

	/**
	 * Get notification content (for in-app notifications).
	 *
	 * @since    1.0.0
	 * @param    string $type The notification type.
	 * @param    array  $data The notification data.
	 * @return   array|false  Array with title and message keys, or false on failure.
	 */
	public function get_notification_content( $type, $data = array() ) {
		$template_data = $this->load_template( $type );
		if ( ! $template_data ) {
			return false;
		}

		$title = $this->parse_template( $template_data['title'] ?? $template_data['subject'], $data );
		$message = $this->parse_template( $template_data['notification'] ?? $template_data['message'], $data );

		return array(
			'title'   => $title,
			'message' => wp_strip_all_tags( $message ),
		);
	}

	/**
	 * Load template data for a notification type.
	 *
	 * @since    1.0.0
	 * @param    string $type The notification type.
	 * @return   array|false  Template data or false on failure.
	 */
	private function load_template( $type ) {
		// First try to load from custom template file
		$template_file = $this->template_dir . $type . '.php';
		if ( file_exists( $template_file ) ) {
			return include $template_file;
		}

		// Fall back to default templates
		return $this->get_default_template( $type );
	}

	/**
	 * Get default template data for a notification type.
	 *
	 * @since    1.0.0
	 * @param    string $type The notification type.
	 * @return   array|false  Template data or false on failure.
	 */
	private function get_default_template( $type ) {
		$templates = array(
			'payment_success' => array(
				'subject'      => __( 'Payment Successful - {{course_name}}', 'skylearn-billing-pro' ),
				'title'        => __( 'Payment Successful', 'skylearn-billing-pro' ),
				'message'      => $this->get_payment_success_template(),
				'notification' => __( 'Your payment for {{course_name}} was successful. Amount: {{amount}}', 'skylearn-billing-pro' ),
			),
			'payment_failed' => array(
				'subject'      => __( 'Payment Failed - {{course_name}}', 'skylearn-billing-pro' ),
				'title'        => __( 'Payment Failed', 'skylearn-billing-pro' ),
				'message'      => $this->get_payment_failed_template(),
				'notification' => __( 'Your payment for {{course_name}} failed. Please update your payment method.', 'skylearn-billing-pro' ),
			),
			'subscription_created' => array(
				'subject'      => __( 'Subscription Activated - {{course_name}}', 'skylearn-billing-pro' ),
				'title'        => __( 'Subscription Activated', 'skylearn-billing-pro' ),
				'message'      => $this->get_subscription_created_template(),
				'notification' => __( 'Your subscription for {{course_name}} is now active.', 'skylearn-billing-pro' ),
			),
			'subscription_cancelled' => array(
				'subject'      => __( 'Subscription Cancelled - {{course_name}}', 'skylearn-billing-pro' ),
				'title'        => __( 'Subscription Cancelled', 'skylearn-billing-pro' ),
				'message'      => $this->get_subscription_cancelled_template(),
				'notification' => __( 'Your subscription for {{course_name}} has been cancelled.', 'skylearn-billing-pro' ),
			),
			'subscription_renewed' => array(
				'subject'      => __( 'Subscription Renewed - {{course_name}}', 'skylearn-billing-pro' ),
				'title'        => __( 'Subscription Renewed', 'skylearn-billing-pro' ),
				'message'      => $this->get_subscription_renewed_template(),
				'notification' => __( 'Your subscription for {{course_name}} has been renewed.', 'skylearn-billing-pro' ),
			),
			'enrollment_created' => array(
				'subject'      => __( 'Course Enrollment Confirmed - {{course_name}}', 'skylearn-billing-pro' ),
				'title'        => __( 'Course Enrollment Confirmed', 'skylearn-billing-pro' ),
				'message'      => $this->get_enrollment_created_template(),
				'notification' => __( 'You have been enrolled in {{course_name}}. Start learning now!', 'skylearn-billing-pro' ),
			),
			'subscription_expiring' => array(
				'subject'      => __( 'Subscription Expiring Soon - {{course_name}}', 'skylearn-billing-pro' ),
				'title'        => __( 'Subscription Expiring Soon', 'skylearn-billing-pro' ),
				'message'      => $this->get_subscription_expiring_template(),
				'notification' => __( 'Your subscription for {{course_name}} expires on {{expires_at}}.', 'skylearn-billing-pro' ),
			),
			'refund_processed' => array(
				'subject'      => __( 'Refund Processed - {{course_name}}', 'skylearn-billing-pro' ),
				'title'        => __( 'Refund Processed', 'skylearn-billing-pro' ),
				'message'      => $this->get_refund_processed_template(),
				'notification' => __( 'Your refund for {{course_name}} has been processed. Amount: {{amount}}', 'skylearn-billing-pro' ),
			),
		);

		return $templates[ $type ] ?? false;
	}

	/**
	 * Parse template with data placeholders.
	 *
	 * @since    1.0.0
	 * @param    string $template The template string.
	 * @param    array  $data     The data to replace placeholders.
	 * @return   string           The parsed template.
	 */
	private function parse_template( $template, $data = array() ) {
		// Default data
		$default_data = array(
			'site_name'    => get_bloginfo( 'name' ),
			'site_url'     => home_url(),
			'user_name'    => '',
			'user_email'   => '',
			'course_name'  => '',
			'amount'       => '',
			'currency'     => '',
			'date'         => current_time( 'F j, Y' ),
			'time'         => current_time( 'g:i A' ),
		);

		$data = wp_parse_args( $data, $default_data );

		// Add user data if user_id is provided
		if ( isset( $data['user_id'] ) ) {
			$user = get_user_by( 'id', $data['user_id'] );
			if ( $user ) {
				$data['user_name'] = $user->display_name;
				$data['user_email'] = $user->user_email;
			}
		}

		// Replace placeholders
		foreach ( $data as $key => $value ) {
			$template = str_replace( '{{' . $key . '}}', $value, $template );
		}

		return $template;
	}

	/**
	 * Wrap message content in email layout.
	 *
	 * @since    1.0.0
	 * @param    string $content The message content.
	 * @param    string $subject The email subject.
	 * @return   string          The wrapped content.
	 */
	private function wrap_in_layout( $content, $subject ) {
		$layout_file = $this->template_dir . 'layout.php';
		
		if ( file_exists( $layout_file ) ) {
			ob_start();
			include $layout_file;
			return ob_get_clean();
		}

		// Default layout
		return $this->get_default_layout( $content, $subject );
	}

	/**
	 * Get default email layout.
	 *
	 * @since    1.0.0
	 * @param    string $content The message content.
	 * @param    string $subject The email subject.
	 * @return   string          The wrapped content.
	 */
	private function get_default_layout( $content, $subject ) {
		$site_name = get_bloginfo( 'name' );
		$site_url = home_url();

		return "
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset='UTF-8'>
			<meta name='viewport' content='width=device-width, initial-scale=1.0'>
			<title>{$subject}</title>
			<style>
				body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
				.email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
				.header { background: #0073aa; color: white; padding: 20px; text-align: center; }
				.content { padding: 20px; background: #f9f9f9; }
				.footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
				.btn { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; }
			</style>
		</head>
		<body>
			<div class='email-container'>
				<div class='header'>
					<h1>{$site_name}</h1>
				</div>
				<div class='content'>
					{$content}
				</div>
				<div class='footer'>
					<p>&copy; " . date( 'Y' ) . " {$site_name}. " . __( 'All rights reserved.', 'skylearn-billing-pro' ) . "</p>
					<p><a href='{$site_url}'>" . __( 'Visit our website', 'skylearn-billing-pro' ) . "</a></p>
				</div>
			</div>
		</body>
		</html>";
	}

	/**
	 * Default template methods.
	 */

	private function get_payment_success_template() {
		return "
		<h2>" . __( 'Payment Successful!', 'skylearn-billing-pro' ) . "</h2>
		<p>" . __( 'Dear {{user_name}},', 'skylearn-billing-pro' ) . "</p>
		<p>" . __( 'Your payment has been successfully processed. Here are the details:', 'skylearn-billing-pro' ) . "</p>
		<ul>
			<li><strong>" . __( 'Course:', 'skylearn-billing-pro' ) . "</strong> {{course_name}}</li>
			<li><strong>" . __( 'Amount:', 'skylearn-billing-pro' ) . "</strong> {{currency}}{{amount}}</li>
			<li><strong>" . __( 'Date:', 'skylearn-billing-pro' ) . "</strong> {{date}}</li>
		</ul>
		<p>" . __( 'You can now access your course content.', 'skylearn-billing-pro' ) . "</p>
		<p><a href='{{course_url}}' class='btn'>" . __( 'Start Learning', 'skylearn-billing-pro' ) . "</a></p>
		";
	}

	private function get_payment_failed_template() {
		return "
		<h2>" . __( 'Payment Failed', 'skylearn-billing-pro' ) . "</h2>
		<p>" . __( 'Dear {{user_name}},', 'skylearn-billing-pro' ) . "</p>
		<p>" . __( 'Unfortunately, your payment could not be processed. Please check your payment method and try again.', 'skylearn-billing-pro' ) . "</p>
		<ul>
			<li><strong>" . __( 'Course:', 'skylearn-billing-pro' ) . "</strong> {{course_name}}</li>
			<li><strong>" . __( 'Amount:', 'skylearn-billing-pro' ) . "</strong> {{currency}}{{amount}}</li>
			<li><strong>" . __( 'Date:', 'skylearn-billing-pro' ) . "</strong> {{date}}</li>
		</ul>
		<p><a href='{{retry_url}}' class='btn'>" . __( 'Retry Payment', 'skylearn-billing-pro' ) . "</a></p>
		";
	}

	private function get_subscription_created_template() {
		return "
		<h2>" . __( 'Subscription Activated!', 'skylearn-billing-pro' ) . "</h2>
		<p>" . __( 'Dear {{user_name}},', 'skylearn-billing-pro' ) . "</p>
		<p>" . __( 'Your subscription has been successfully activated. Welcome aboard!', 'skylearn-billing-pro' ) . "</p>
		<ul>
			<li><strong>" . __( 'Course:', 'skylearn-billing-pro' ) . "</strong> {{course_name}}</li>
			<li><strong>" . __( 'Plan:', 'skylearn-billing-pro' ) . "</strong> {{plan_name}}</li>
			<li><strong>" . __( 'Next Billing:', 'skylearn-billing-pro' ) . "</strong> {{next_billing_date}}</li>
		</ul>
		<p><a href='{{course_url}}' class='btn'>" . __( 'Access Your Course', 'skylearn-billing-pro' ) . "</a></p>
		";
	}

	private function get_subscription_cancelled_template() {
		return "
		<h2>" . __( 'Subscription Cancelled', 'skylearn-billing-pro' ) . "</h2>
		<p>" . __( 'Dear {{user_name}},', 'skylearn-billing-pro' ) . "</p>
		<p>" . __( 'Your subscription has been cancelled as requested.', 'skylearn-billing-pro' ) . "</p>
		<ul>
			<li><strong>" . __( 'Course:', 'skylearn-billing-pro' ) . "</strong> {{course_name}}</li>
			<li><strong>" . __( 'Access Until:', 'skylearn-billing-pro' ) . "</strong> {{access_until}}</li>
		</ul>
		<p>" . __( "We're sorry to see you go. If you change your mind, you can resubscribe anytime.", 'skylearn-billing-pro' ) . "</p>
		";
	}

	private function get_subscription_renewed_template() {
		return "
		<h2>" . __( 'Subscription Renewed!', 'skylearn-billing-pro' ) . "</h2>
		<p>" . __( 'Dear {{user_name}},', 'skylearn-billing-pro' ) . "</p>
		<p>" . __( 'Your subscription has been successfully renewed.', 'skylearn-billing-pro' ) . "</p>
		<ul>
			<li><strong>" . __( 'Course:', 'skylearn-billing-pro' ) . "</strong> {{course_name}}</li>
			<li><strong>" . __( 'Amount:', 'skylearn-billing-pro' ) . "</strong> {{currency}}{{amount}}</li>
			<li><strong>" . __( 'Next Billing:', 'skylearn-billing-pro' ) . "</strong> {{next_billing_date}}</li>
		</ul>
		<p>" . __( 'Thank you for your continued subscription!', 'skylearn-billing-pro' ) . "</p>
		";
	}

	private function get_enrollment_created_template() {
		return "
		<h2>" . __( 'Welcome to Your Course!', 'skylearn-billing-pro' ) . "</h2>
		<p>" . __( 'Dear {{user_name}},', 'skylearn-billing-pro' ) . "</p>
		<p>" . __( 'You have been successfully enrolled in the following course:', 'skylearn-billing-pro' ) . "</p>
		<ul>
			<li><strong>" . __( 'Course:', 'skylearn-billing-pro' ) . "</strong> {{course_name}}</li>
			<li><strong>" . __( 'Enrollment Date:', 'skylearn-billing-pro' ) . "</strong> {{date}}</li>
		</ul>
		<p>" . __( 'You can now start your learning journey!', 'skylearn-billing-pro' ) . "</p>
		<p><a href='{{course_url}}' class='btn'>" . __( 'Start Learning', 'skylearn-billing-pro' ) . "</a></p>
		";
	}

	private function get_subscription_expiring_template() {
		return "
		<h2>" . __( 'Subscription Expiring Soon', 'skylearn-billing-pro' ) . "</h2>
		<p>" . __( 'Dear {{user_name}},', 'skylearn-billing-pro' ) . "</p>
		<p>" . __( 'Your subscription is expiring soon. Renew now to continue your learning journey.', 'skylearn-billing-pro' ) . "</p>
		<ul>
			<li><strong>" . __( 'Course:', 'skylearn-billing-pro' ) . "</strong> {{course_name}}</li>
			<li><strong>" . __( 'Expires On:', 'skylearn-billing-pro' ) . "</strong> {{expires_at}}</li>
		</ul>
		<p><a href='{{renew_url}}' class='btn'>" . __( 'Renew Subscription', 'skylearn-billing-pro' ) . "</a></p>
		";
	}

	private function get_refund_processed_template() {
		return "
		<h2>" . __( 'Refund Processed', 'skylearn-billing-pro' ) . "</h2>
		<p>" . __( 'Dear {{user_name}},', 'skylearn-billing-pro' ) . "</p>
		<p>" . __( 'Your refund has been successfully processed.', 'skylearn-billing-pro' ) . "</p>
		<ul>
			<li><strong>" . __( 'Course:', 'skylearn-billing-pro' ) . "</strong> {{course_name}}</li>
			<li><strong>" . __( 'Refund Amount:', 'skylearn-billing-pro' ) . "</strong> {{currency}}{{amount}}</li>
			<li><strong>" . __( 'Processing Date:', 'skylearn-billing-pro' ) . "</strong> {{date}}</li>
		</ul>
		<p>" . __( 'The refund should appear in your account within 3-5 business days.', 'skylearn-billing-pro' ) . "</p>
		";
	}
}