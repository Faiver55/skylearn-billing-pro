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
		return '<div class="slbp-guide-content"><p>' . __( 'User dashboard guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_enrollment_guide() {
		return '<div class="slbp-guide-content"><p>' . __( 'Course enrollment guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_billing_history_guide() {
		return '<div class="slbp-guide-content"><p>' . __( 'Billing history guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_payment_setup_guide() {
		return '<div class="slbp-guide-content"><p>' . __( 'Payment gateway setup guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_learndash_integration_guide() {
		return '<div class="slbp-guide-content"><p>' . __( 'LearnDash integration guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_subscription_management_guide() {
		return '<div class="slbp-guide-content"><p>' . __( 'Subscription management guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_analytics_reporting_guide() {
		return '<div class="slbp-guide-content"><p>' . __( 'Analytics and reporting guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_common_issues_guide() {
		return '<div class="slbp-guide-content"><p>' . __( 'Common issues troubleshooting guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_payment_problems_guide() {
		return '<div class="slbp-guide-content"><p>' . __( 'Payment problems troubleshooting guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_enrollment_issues_guide() {
		return '<div class="slbp-guide-content"><p>' . __( 'Enrollment issues troubleshooting guide content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_general_faq() {
		return '<div class="slbp-guide-content"><p>' . __( 'General FAQ content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_billing_faq() {
		return '<div class="slbp-guide-content"><p>' . __( 'Billing FAQ content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}

	private function get_technical_faq() {
		return '<div class="slbp-guide-content"><p>' . __( 'Technical FAQ content goes here...', 'skylearn-billing-pro' ) . '</p></div>';
	}
}