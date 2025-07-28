<?php
/**
 * The training documentation manager.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/training
 */

/**
 * Training Documentation Manager Class
 *
 * Manages all training materials, tutorials, and documentation.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/training
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Training_Manager {

	/**
	 * The documentation content structure.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $documentation    Documentation structure.
	 */
	private $documentation;

	/**
	 * The video tutorials configuration.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $videos    Video tutorials configuration.
	 */
	private $videos;

	/**
	 * Initialize the training manager.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_documentation_structure();
		$this->init_video_tutorials();
	}

	/**
	 * Initialize the documentation structure.
	 *
	 * @since    1.0.0
	 */
	private function init_documentation_structure() {
		$this->documentation = array(
			'getting_started' => array(
				'title' => __( 'Getting Started', 'skylearn-billing-pro' ),
				'icon'  => 'admin-home',
				'sections' => array(
					'installation' => array(
						'title' => __( 'Installation & Setup', 'skylearn-billing-pro' ),
						'content' => $this->get_installation_guide(),
					),
					'quick_start' => array(
						'title' => __( 'Quick Start Guide', 'skylearn-billing-pro' ),
						'content' => $this->get_quick_start_guide(),
					),
					'first_steps' => array(
						'title' => __( 'First Steps Checklist', 'skylearn-billing-pro' ),
						'content' => $this->get_first_steps_checklist(),
					),
				),
			),
			'user_guides' => array(
				'title' => __( 'User Guides', 'skylearn-billing-pro' ),
				'icon'  => 'admin-users',
				'sections' => array(
					'user_dashboard' => array(
						'title' => __( 'Using the User Dashboard', 'skylearn-billing-pro' ),
						'content' => $this->get_user_dashboard_guide(),
					),
					'enrollment' => array(
						'title' => __( 'Course Enrollment Process', 'skylearn-billing-pro' ),
						'content' => $this->get_enrollment_guide(),
					),
					'billing_history' => array(
						'title' => __( 'Viewing Billing History', 'skylearn-billing-pro' ),
						'content' => $this->get_billing_history_guide(),
					),
				),
			),
			'admin_guides' => array(
				'title' => __( 'Administrator Guides', 'skylearn-billing-pro' ),
				'icon'  => 'admin-settings',
				'sections' => array(
					'payment_setup' => array(
						'title' => __( 'Payment Gateway Setup', 'skylearn-billing-pro' ),
						'content' => $this->get_payment_setup_guide(),
					),
					'learndash_integration' => array(
						'title' => __( 'LearnDash Integration', 'skylearn-billing-pro' ),
						'content' => $this->get_learndash_integration_guide(),
					),
					'subscription_management' => array(
						'title' => __( 'Subscription Management', 'skylearn-billing-pro' ),
						'content' => $this->get_subscription_management_guide(),
					),
					'analytics_reporting' => array(
						'title' => __( 'Analytics & Reporting', 'skylearn-billing-pro' ),
						'content' => $this->get_analytics_reporting_guide(),
					),
				),
			),
			'troubleshooting' => array(
				'title' => __( 'Troubleshooting', 'skylearn-billing-pro' ),
				'icon'  => 'admin-tools',
				'sections' => array(
					'common_issues' => array(
						'title' => __( 'Common Issues', 'skylearn-billing-pro' ),
						'content' => $this->get_common_issues_guide(),
					),
					'payment_problems' => array(
						'title' => __( 'Payment Problems', 'skylearn-billing-pro' ),
						'content' => $this->get_payment_problems_guide(),
					),
					'enrollment_issues' => array(
						'title' => __( 'Enrollment Issues', 'skylearn-billing-pro' ),
						'content' => $this->get_enrollment_issues_guide(),
					),
				),
			),
			'faq' => array(
				'title' => __( 'Frequently Asked Questions', 'skylearn-billing-pro' ),
				'icon'  => 'editor-help',
				'sections' => array(
					'general' => array(
						'title' => __( 'General Questions', 'skylearn-billing-pro' ),
						'content' => $this->get_general_faq(),
					),
					'billing' => array(
						'title' => __( 'Billing Questions', 'skylearn-billing-pro' ),
						'content' => $this->get_billing_faq(),
					),
					'technical' => array(
						'title' => __( 'Technical Questions', 'skylearn-billing-pro' ),
						'content' => $this->get_technical_faq(),
					),
				),
			),
		);
	}

	/**
	 * Initialize video tutorials configuration.
	 *
	 * @since    1.0.0
	 */
	private function init_video_tutorials() {
		$this->videos = array(
			'getting_started_video' => array(
				'title' => __( 'Getting Started with SkyLearn Billing Pro', 'skylearn-billing-pro' ),
				'description' => __( 'A complete walkthrough of setting up your billing system', 'skylearn-billing-pro' ),
				'duration' => '8:45',
				'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', // Placeholder
				'thumbnail' => SLBP_PLUGIN_URL . 'assets/images/video-thumbnails/getting-started.jpg',
			),
			'payment_gateway_setup' => array(
				'title' => __( 'Payment Gateway Configuration', 'skylearn-billing-pro' ),
				'description' => __( 'Step-by-step guide to configure Lemon Squeezy and other gateways', 'skylearn-billing-pro' ),
				'duration' => '12:30',
				'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', // Placeholder
				'thumbnail' => SLBP_PLUGIN_URL . 'assets/images/video-thumbnails/payment-setup.jpg',
			),
			'learndash_integration' => array(
				'title' => __( 'LearnDash Integration Setup', 'skylearn-billing-pro' ),
				'description' => __( 'Connect your courses with the billing system', 'skylearn-billing-pro' ),
				'duration' => '15:20',
				'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', // Placeholder
				'thumbnail' => SLBP_PLUGIN_URL . 'assets/images/video-thumbnails/learndash-integration.jpg',
			),
			'user_dashboard_tour' => array(
				'title' => __( 'User Dashboard Tour', 'skylearn-billing-pro' ),
				'description' => __( 'Overview of the user-facing dashboard features', 'skylearn-billing-pro' ),
				'duration' => '6:15',
				'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', // Placeholder
				'thumbnail' => SLBP_PLUGIN_URL . 'assets/images/video-thumbnails/user-dashboard.jpg',
			),
			'analytics_reporting' => array(
				'title' => __( 'Analytics & Reporting', 'skylearn-billing-pro' ),
				'description' => __( 'Understanding your billing analytics and generating reports', 'skylearn-billing-pro' ),
				'duration' => '10:45',
				'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', // Placeholder
				'thumbnail' => SLBP_PLUGIN_URL . 'assets/images/video-thumbnails/analytics.jpg',
			),
		);
	}

	/**
	 * Get documentation structure.
	 *
	 * @since    1.0.0
	 * @return   array    Documentation structure.
	 */
	public function get_documentation() {
		return $this->documentation;
	}

	/**
	 * Get video tutorials.
	 *
	 * @since    1.0.0
	 * @return   array    Video tutorials.
	 */
	public function get_videos() {
		return $this->videos;
	}

	/**
	 * Get a specific documentation section.
	 *
	 * @since    1.0.0
	 * @param    string    $category    Documentation category.
	 * @param    string    $section     Documentation section.
	 * @return   array|null              Section content or null if not found.
	 */
	public function get_section( $category, $section = null ) {
		if ( ! isset( $this->documentation[ $category ] ) ) {
			return null;
		}

		if ( $section && isset( $this->documentation[ $category ]['sections'][ $section ] ) ) {
			return $this->documentation[ $category ]['sections'][ $section ];
		}

		return $this->documentation[ $category ];
	}

	/**
	 * Search documentation content.
	 *
	 * @since    1.0.0
	 * @param    string    $query    Search query.
	 * @return   array              Search results.
	 */
	public function search_documentation( $query ) {
		$results = array();
		$query = strtolower( trim( $query ) );

		if ( empty( $query ) ) {
			return $results;
		}

		foreach ( $this->documentation as $category_key => $category ) {
			foreach ( $category['sections'] as $section_key => $section ) {
				$content = strtolower( $section['title'] . ' ' . $section['content'] );
				if ( strpos( $content, $query ) !== false ) {
					$results[] = array(
						'category' => $category_key,
						'section' => $section_key,
						'title' => $section['title'],
						'excerpt' => $this->get_excerpt( $section['content'], $query ),
					);
				}
			}
		}

		return $results;
	}

	/**
	 * Get content excerpt with highlighted search term.
	 *
	 * @since    1.0.0
	 * @param    string    $content    Content to excerpt.
	 * @param    string    $query      Search query.
	 * @return   string               Content excerpt.
	 */
	private function get_excerpt( $content, $query ) {
		$content = wp_strip_all_tags( $content );
		$pos = strpos( strtolower( $content ), strtolower( $query ) );
		
		if ( $pos === false ) {
			return substr( $content, 0, 150 ) . '...';
		}

		$start = max( 0, $pos - 75 );
		$excerpt = substr( $content, $start, 150 );
		
		if ( $start > 0 ) {
			$excerpt = '...' . $excerpt;
		}
		
		if ( strlen( $content ) > $start + 150 ) {
			$excerpt .= '...';
		}

		// Highlight the search term
		$excerpt = str_ireplace( $query, '<strong>' . $query . '</strong>', $excerpt );

		return $excerpt;
	}

	// Content generation methods (these would contain the actual documentation content)

	/**
	 * Get installation guide content.
	 *
	 * @since    1.0.0
	 * @return   string    Installation guide content.
	 */
	private function get_installation_guide() {
		return '
		<div class="slbp-guide-content">
			<h3>' . __( 'System Requirements', 'skylearn-billing-pro' ) . '</h3>
			<ul>
				<li>' . __( 'WordPress 5.0 or higher', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'PHP 7.4 or higher', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'LearnDash LMS plugin', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Valid SSL certificate (required for payment processing)', 'skylearn-billing-pro' ) . '</li>
			</ul>

			<h3>' . __( 'Installation Steps', 'skylearn-billing-pro' ) . '</h3>
			<ol>
				<li>' . __( 'Download the plugin package from your account', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Go to WordPress Admin → Plugins → Add New → Upload Plugin', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Choose the plugin ZIP file and click "Install Now"', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Activate the plugin after installation', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Enter your license key in the License tab', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Complete the setup wizard that appears', 'skylearn-billing-pro' ) . '</li>
			</ol>

			<div class="slbp-notice slbp-notice-info">
				<p><strong>' . __( 'Need Help?', 'skylearn-billing-pro' ) . '</strong> ' . __( 'If you encounter any issues during installation, please contact our support team.', 'skylearn-billing-pro' ) . '</p>
			</div>
		</div>';
	}

	/**
	 * Get quick start guide content.
	 *
	 * @since    1.0.0
	 * @return   string    Quick start guide content.
	 */
	private function get_quick_start_guide() {
		return '
		<div class="slbp-guide-content">
			<p>' . __( 'Follow these essential steps to get your billing system up and running:', 'skylearn-billing-pro' ) . '</p>
			
			<div class="slbp-quick-start-steps">
				<div class="slbp-step">
					<div class="slbp-step-number">1</div>
					<div class="slbp-step-content">
						<h4>' . __( 'Configure Payment Gateway', 'skylearn-billing-pro' ) . '</h4>
						<p>' . __( 'Set up your Lemon Squeezy or other payment gateway credentials in the Settings page.', 'skylearn-billing-pro' ) . '</p>
						<a href="' . admin_url( 'admin.php?page=slbp-settings&tab=payment' ) . '" class="button button-primary">' . __( 'Configure Now', 'skylearn-billing-pro' ) . '</a>
					</div>
				</div>

				<div class="slbp-step">
					<div class="slbp-step-number">2</div>
					<div class="slbp-step-content">
						<h4>' . __( 'Connect LearnDash', 'skylearn-billing-pro' ) . '</h4>
						<p>' . __( 'Enable LearnDash integration to automatically enroll students in courses.', 'skylearn-billing-pro' ) . '</p>
						<a href="' . admin_url( 'admin.php?page=slbp-settings&tab=lms' ) . '" class="button button-primary">' . __( 'Setup Integration', 'skylearn-billing-pro' ) . '</a>
					</div>
				</div>

				<div class="slbp-step">
					<div class="slbp-step-number">3</div>
					<div class="slbp-step-content">
						<h4>' . __( 'Map Products to Courses', 'skylearn-billing-pro' ) . '</h4>
						<p>' . __( 'Create the connection between your payment products and LearnDash courses.', 'skylearn-billing-pro' ) . '</p>
						<a href="' . admin_url( 'admin.php?page=slbp-settings&tab=products' ) . '" class="button button-primary">' . __( 'Map Products', 'skylearn-billing-pro' ) . '</a>
					</div>
				</div>

				<div class="slbp-step">
					<div class="slbp-step-number">4</div>
					<div class="slbp-step-content">
						<h4>' . __( 'Test Your Setup', 'skylearn-billing-pro' ) . '</h4>
						<p>' . __( 'Make a test purchase to ensure everything is working correctly.', 'skylearn-billing-pro' ) . '</p>
						<a href="' . admin_url( 'admin.php?page=slbp-settings&tab=testing' ) . '" class="button button-primary">' . __( 'Run Test', 'skylearn-billing-pro' ) . '</a>
					</div>
				</div>
			</div>
		</div>';
	}

	/**
	 * Get first steps checklist content.
	 *
	 * @since    1.0.0
	 * @return   string    First steps checklist content.
	 */
	private function get_first_steps_checklist() {
		// Get current setup status
		$payment_settings = get_option( 'slbp_payment_settings', array() );
		$lms_settings = get_option( 'slbp_lms_settings', array() );
		$product_settings = get_option( 'slbp_product_settings', array() );

		$payment_configured = ! empty( $payment_settings['lemon_squeezy_api_key'] );
		$lms_configured = ! empty( $lms_settings['learndash_enabled'] );
		$products_mapped = ! empty( $product_settings['mappings'] );

		return '
		<div class="slbp-guide-content">
			<p>' . __( 'Complete these essential tasks to get started with SkyLearn Billing Pro:', 'skylearn-billing-pro' ) . '</p>
			
			<div class="slbp-checklist">
				<div class="slbp-checklist-item ' . ( $payment_configured ? 'completed' : '' ) . '">
					<span class="slbp-checklist-icon">' . ( $payment_configured ? '✓' : '○' ) . '</span>
					<span class="slbp-checklist-text">' . __( 'Configure payment gateway credentials', 'skylearn-billing-pro' ) . '</span>
				</div>
				
				<div class="slbp-checklist-item ' . ( $lms_configured ? 'completed' : '' ) . '">
					<span class="slbp-checklist-icon">' . ( $lms_configured ? '✓' : '○' ) . '</span>
					<span class="slbp-checklist-text">' . __( 'Enable LearnDash integration', 'skylearn-billing-pro' ) . '</span>
				</div>
				
				<div class="slbp-checklist-item ' . ( $products_mapped ? 'completed' : '' ) . '">
					<span class="slbp-checklist-icon">' . ( $products_mapped ? '✓' : '○' ) . '</span>
					<span class="slbp-checklist-text">' . __( 'Map at least one product to a course', 'skylearn-billing-pro' ) . '</span>
				</div>
				
				<div class="slbp-checklist-item">
					<span class="slbp-checklist-icon">○</span>
					<span class="slbp-checklist-text">' . __( 'Configure email notification templates', 'skylearn-billing-pro' ) . '</span>
				</div>
				
				<div class="slbp-checklist-item">
					<span class="slbp-checklist-icon">○</span>
					<span class="slbp-checklist-text">' . __( 'Set up webhook endpoints', 'skylearn-billing-pro' ) . '</span>
				</div>
				
				<div class="slbp-checklist-item">
					<span class="slbp-checklist-icon">○</span>
					<span class="slbp-checklist-text">' . __( 'Test payment flow with sandbox mode', 'skylearn-billing-pro' ) . '</span>
				</div>
				
				<div class="slbp-checklist-item">
					<span class="slbp-checklist-icon">○</span>
					<span class="slbp-checklist-text">' . __( 'Review security and compliance settings', 'skylearn-billing-pro' ) . '</span>
				</div>
			</div>

			<div class="slbp-notice slbp-notice-success">
				<p>' . __( 'Once all items are checked, your billing system will be ready for production use!', 'skylearn-billing-pro' ) . '</p>
			</div>
		</div>';
	}

	// Additional content methods would continue here...
	// For brevity, I\'ll add placeholder methods for the other guides

	private function get_user_dashboard_guide() {
		return '
		<div class="slbp-guide-content">
			<h3>' . __( 'Navigating Your User Dashboard', 'skylearn-billing-pro' ) . '</h3>
			<p>' . __( 'The user dashboard provides a centralized location for students to manage their course access, billing, and account information.', 'skylearn-billing-pro' ) . '</p>
			
			<h4>' . __( 'Dashboard Sections', 'skylearn-billing-pro' ) . '</h4>
			<ul>
				<li><strong>' . __( 'Course Access:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'View all courses you have access to and track your progress.', 'skylearn-billing-pro' ) . '</li>
				<li><strong>' . __( 'Subscription Status:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Monitor your active subscriptions and renewal dates.', 'skylearn-billing-pro' ) . '</li>
				<li><strong>' . __( 'Billing History:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Review past transactions and download invoices.', 'skylearn-billing-pro' ) . '</li>
				<li><strong>' . __( 'Account Settings:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Update your profile and payment methods.', 'skylearn-billing-pro' ) . '</li>
			</ul>

			<h4>' . __( 'Managing Your Courses', 'skylearn-billing-pro' ) . '</h4>
			<ol>
				<li>' . __( 'Log into your WordPress account', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Navigate to your user dashboard (usually at /my-courses or /dashboard)', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Click on any course to access the content', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Use the progress tracker to monitor your completion status', 'skylearn-billing-pro' ) . '</li>
			</ol>

			<div class="slbp-notice slbp-notice-info">
				<p><strong>' . __( 'Pro Tip:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Bookmark your dashboard for quick access to all your courses and account information.', 'skylearn-billing-pro' ) . '</p>
			</div>
		</div>';
	}

	private function get_enrollment_guide() {
		return '
		<div class="slbp-guide-content">
			<h3>' . __( 'Course Enrollment Process', 'skylearn-billing-pro' ) . '</h3>
			<p>' . __( 'Learn how to purchase and get enrolled in courses through our automated billing system.', 'skylearn-billing-pro' ) . '</p>
			
			<h4>' . __( 'Purchasing a Course', 'skylearn-billing-pro' ) . '</h4>
			<ol>
				<li>' . __( 'Browse available courses on the website', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Click "Enroll Now" or "Purchase" on your desired course', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Complete the secure checkout process', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Receive confirmation email with access details', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Access begins immediately upon successful payment', 'skylearn-billing-pro' ) . '</li>
			</ol>

			<h4>' . __( 'Automatic Enrollment', 'skylearn-billing-pro' ) . '</h4>
			<p>' . __( 'Our system automatically enrolls you in courses after successful payment. This typically happens within 1-2 minutes of payment confirmation.', 'skylearn-billing-pro' ) . '</p>

			<h4>' . __( 'Troubleshooting Enrollment Issues', 'skylearn-billing-pro' ) . '</h4>
			<ul>
				<li>' . __( 'Check your email for confirmation messages', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Verify payment was completed successfully', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Log out and log back into your account', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Contact support if access is not granted within 10 minutes', 'skylearn-billing-pro' ) . '</li>
			</ul>

			<div class="slbp-notice slbp-notice-warning">
				<p><strong>' . __( 'Note:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Make sure you are logged into your account before making a purchase to ensure proper enrollment.', 'skylearn-billing-pro' ) . '</p>
			</div>
		</div>';
	}

	private function get_payment_setup_guide() {
		return '
		<div class="slbp-guide-content">
			<h3>' . __( 'Payment Gateway Configuration', 'skylearn-billing-pro' ) . '</h3>
			<p>' . __( 'Configure your payment processing to start accepting payments from students.', 'skylearn-billing-pro' ) . '</p>
			
			<h4>' . __( 'Lemon Squeezy Setup', 'skylearn-billing-pro' ) . '</h4>
			<ol>
				<li>' . __( 'Create an account at lemonsqueezy.com', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Complete your store setup and verification', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Navigate to Settings → API in your Lemon Squeezy dashboard', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Generate a new API key with the necessary permissions', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Copy your Store ID from the dashboard', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Enter these credentials in your plugin settings', 'skylearn-billing-pro' ) . '</li>
			</ol>

			<h4>' . __( 'Webhook Configuration', 'skylearn-billing-pro' ) . '</h4>
			<p>' . __( 'Webhooks ensure real-time communication between Lemon Squeezy and your WordPress site:', 'skylearn-billing-pro' ) . '</p>
			<ol>
				<li>' . __( 'In Lemon Squeezy, go to Settings → Webhooks', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Add a new webhook with URL:', 'skylearn-billing-pro' ) . ' <code>' . get_site_url() . '/wp-json/skylearn-billing-pro/v1/webhook/lemon-squeezy</code></li>
				<li>' . __( 'Select relevant events: order_created, subscription_created, subscription_updated', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Save and test the webhook', 'skylearn-billing-pro' ) . '</li>
			</ol>

			<h4>' . __( 'Testing Your Setup', 'skylearn-billing-pro' ) . '</h4>
			<ul>
				<li>' . __( 'Use Lemon Squeezy\'s test mode for initial testing', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Create a test product and make a test purchase', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Verify enrollment happens automatically', 'skylearn-billing-pro' ) . '</li>
				<li>' . __( 'Check webhook logs for any errors', 'skylearn-billing-pro' ) . '</li>
			</ul>

			<div class="slbp-notice slbp-notice-success">
				<p><strong>' . __( 'Security Note:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Your API keys are encrypted and stored securely. Never share them with unauthorized parties.', 'skylearn-billing-pro' ) . '</p>
			</div>
		</div>';
	}

	private function get_common_issues_guide() {
		return '
		<div class="slbp-guide-content">
			<h3>' . __( 'Common Issues & Solutions', 'skylearn-billing-pro' ) . '</h3>
			<p>' . __( 'Quick solutions to the most frequently encountered problems.', 'skylearn-billing-pro' ) . '</p>
			
			<div class="slbp-troubleshooting-item">
				<h4>' . __( 'Students Not Getting Course Access', 'skylearn-billing-pro' ) . '</h4>
				<p><strong>' . __( 'Symptoms:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Payment completed but no course access granted.', 'skylearn-billing-pro' ) . '</p>
				<p><strong>' . __( 'Solutions:', 'skylearn-billing-pro' ) . '</strong></p>
				<ul>
					<li>' . __( 'Check webhook configuration in payment gateway', 'skylearn-billing-pro' ) . '</li>
					<li>' . __( 'Verify product mapping is correctly configured', 'skylearn-billing-pro' ) . '</li>
					<li>' . __( 'Check LearnDash integration is enabled', 'skylearn-billing-pro' ) . '</li>
					<li>' . __( 'Review enrollment logs for error messages', 'skylearn-billing-pro' ) . '</li>
				</ul>
			</div>

			<div class="slbp-troubleshooting-item">
				<h4>' . __( 'Payment Gateway Connection Issues', 'skylearn-billing-pro' ) . '</h4>
				<p><strong>' . __( 'Symptoms:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Unable to connect to payment processor or API errors.', 'skylearn-billing-pro' ) . '</p>
				<p><strong>' . __( 'Solutions:', 'skylearn-billing-pro' ) . '</strong></p>
				<ul>
					<li>' . __( 'Verify API credentials are correct and active', 'skylearn-billing-pro' ) . '</li>
					<li>' . __( 'Check if payment gateway is in test/sandbox mode', 'skylearn-billing-pro' ) . '</li>
					<li>' . __( 'Ensure SSL certificate is valid and active', 'skylearn-billing-pro' ) . '</li>
					<li>' . __( 'Test connection using the built-in test tool', 'skylearn-billing-pro' ) . '</li>
				</ul>
			</div>

			<div class="slbp-troubleshooting-item">
				<h4>' . __( 'Dashboard Not Loading', 'skylearn-billing-pro' ) . '</h4>
				<p><strong>' . __( 'Symptoms:', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Admin dashboard shows errors or won\'t load.', 'skylearn-billing-pro' ) . '</p>
				<p><strong>' . __( 'Solutions:', 'skylearn-billing-pro' ) . '</strong></p>
				<ul>
					<li>' . __( 'Clear browser cache and cookies', 'skylearn-billing-pro' ) . '</li>
					<li>' . __( 'Deactivate other plugins temporarily to check for conflicts', 'skylearn-billing-pro' ) . '</li>
					<li>' . __( 'Check WordPress debug logs for PHP errors', 'skylearn-billing-pro' ) . '</li>
					<li>' . __( 'Ensure minimum WordPress and PHP requirements are met', 'skylearn-billing-pro' ) . '</li>
				</ul>
			</div>

			<div class="slbp-notice slbp-notice-info">
				<p><strong>' . __( 'Still Having Issues?', 'skylearn-billing-pro' ) . '</strong> ' . __( 'Contact our support team with specific error messages and steps to reproduce the problem.', 'skylearn-billing-pro' ) . '</p>
			</div>
		</div>';
	}

	private function get_general_faq() {
		return '
		<div class="slbp-guide-content">
			<h3>' . __( 'Frequently Asked Questions', 'skylearn-billing-pro' ) . '</h3>
			
			<div class="slbp-faq-item">
				<h4>' . __( 'What payment methods are supported?', 'skylearn-billing-pro' ) . '</h4>
				<p>' . __( 'SkyLearn Billing Pro supports all payment methods available through your configured payment gateway, including credit cards, PayPal, and other payment options.', 'skylearn-billing-pro' ) . '</p>
			</div>

			<div class="slbp-faq-item">
				<h4>' . __( 'Can I offer free courses alongside paid ones?', 'skylearn-billing-pro' ) . '</h4>
				<p>' . __( 'Yes! You can configure some courses to be freely accessible while others require payment. Simply don\'t map free courses to any payment products.', 'skylearn-billing-pro' ) . '</p>
			</div>

			<div class="slbp-faq-item">
				<h4>' . __( 'How do subscriptions work?', 'skylearn-billing-pro' ) . '</h4>
				<p>' . __( 'Subscriptions provide recurring access to courses. Students are automatically charged at regular intervals, and their course access continues as long as the subscription is active.', 'skylearn-billing-pro' ) . '</p>
			</div>

			<div class="slbp-faq-item">
				<h4>' . __( 'Can students access courses on mobile devices?', 'skylearn-billing-pro' ) . '</h4>
				<p>' . __( 'Yes! The system works with LearnDash\'s mobile-responsive design and mobile apps, allowing students to access their courses on any device.', 'skylearn-billing-pro' ) . '</p>
			</div>

			<div class="slbp-faq-item">
				<h4>' . __( 'What happens if a payment fails?', 'skylearn-billing-pro' ) . '</h4>
				<p>' . __( 'Failed payments are handled according to your payment gateway\'s retry logic. Students receive notifications about failed payments and can update their payment methods.', 'skylearn-billing-pro' ) . '</p>
			</div>

			<div class="slbp-faq-item">
				<h4>' . __( 'Is my data secure?', 'skylearn-billing-pro' ) . '</h4>
				<p>' . __( 'Absolutely! All payment processing is handled by certified payment processors. We never store sensitive payment information on your WordPress site.', 'skylearn-billing-pro' ) . '</p>
			</div>
		</div>';
	}

	/**
	 * Get billing history guide content.
	 *
	 * @since    1.0.0
	 * @return   string    Billing history guide content.
	 */
	private function get_billing_history_guide() {
		// TODO: Implement billing history guide content
		return '';
	}

	/**
	 * Get LearnDash integration guide content.
	 *
	 * @since    1.0.0
	 * @return   string    LearnDash integration guide content.
	 */
	private function get_learndash_integration_guide() {
		// TODO: Implement LearnDash integration guide content
		return '';
	}

	/**
	 * Get subscription management guide content.
	 *
	 * @since    1.0.0
	 * @return   string    Subscription management guide content.
	 */
	private function get_subscription_management_guide() {
		// TODO: Implement subscription management guide content
		return '';
	}

	/**
	 * Get analytics reporting guide content.
	 *
	 * @since    1.0.0
	 * @return   string    Analytics reporting guide content.
	 */
	private function get_analytics_reporting_guide() {
		// TODO: Implement analytics reporting guide content
		return '';
	}

	/**
	 * Get payment problems guide content.
	 *
	 * @since    1.0.0
	 * @return   string    Payment problems guide content.
	 */
	private function get_payment_problems_guide() {
		// TODO: Implement payment problems guide content
		return '';
	}

	/**
	 * Get enrollment issues guide content.
	 *
	 * @since    1.0.0
	 * @return   string    Enrollment issues guide content.
	 */
	private function get_enrollment_issues_guide() {
		// TODO: Implement enrollment issues guide content
		return '';
	}

	/**
	 * Get billing FAQ content.
	 *
	 * @since    1.0.0
	 * @return   string    Billing FAQ content.
	 */
	private function get_billing_faq() {
		// TODO: Implement billing FAQ content
		return '';
	}

	/**
	 * Get technical FAQ content.
	 *
	 * @since    1.0.0
	 * @return   string    Technical FAQ content.
	 */
	private function get_technical_faq() {
		// TODO: Implement technical FAQ content
		return '';
	}

	/**
	 * Get payment gateway guide content.
	 *
	 * @since    1.0.0
	 * @return   string    Payment gateway guide content.
	 */
	private function get_payment_gateway_guide() {
		// TODO: Implement payment gateway guide content
		return '';
	}
}