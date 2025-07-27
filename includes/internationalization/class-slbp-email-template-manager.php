<?php
/**
 * Localized Email Template Manager
 *
 * Handles localized email templates for different languages.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 */

/**
 * Localized Email Template Manager class.
 *
 * This class handles the management and rendering of email templates
 * in different languages with proper fallback mechanisms.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Email_Template_Manager {

	/**
	 * The i18n manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_I18n    $i18n    The i18n manager instance.
	 */
	private $i18n;

	/**
	 * Template cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $template_cache    Template cache.
	 */
	private $template_cache = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    SLBP_I18n    $i18n    The i18n manager instance.
	 */
	public function __construct( $i18n ) {
		$this->i18n = $i18n;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Hook into email sending to use localized templates
		add_filter( 'slbp_email_template', array( $this, 'get_localized_template' ), 10, 3 );
		add_filter( 'slbp_email_subject', array( $this, 'get_localized_subject' ), 10, 3 );
	}

	/**
	 * Get localized email template.
	 *
	 * @since    1.0.0
	 * @param    string    $template     The default template content.
	 * @param    string    $template_id  The template identifier.
	 * @param    array     $args         Template arguments.
	 * @return   string    The localized template content.
	 */
	public function get_localized_template( $template, $template_id, $args = array() ) {
		$language = $this->get_user_language( $args );
		$localized_template = $this->load_template( $template_id, $language );

		return $localized_template ? $localized_template : $template;
	}

	/**
	 * Get localized email subject.
	 *
	 * @since    1.0.0
	 * @param    string    $subject      The default subject.
	 * @param    string    $template_id  The template identifier.
	 * @param    array     $args         Template arguments.
	 * @return   string    The localized subject.
	 */
	public function get_localized_subject( $subject, $template_id, $args = array() ) {
		$language = $this->get_user_language( $args );
		$localized_subject = $this->load_subject( $template_id, $language );

		return $localized_subject ? $localized_subject : $subject;
	}

	/**
	 * Load email template for specific language.
	 *
	 * @since    1.0.0
	 * @param    string    $template_id  The template identifier.
	 * @param    string    $language     The language code.
	 * @return   string|false    The template content or false if not found.
	 */
	private function load_template( $template_id, $language ) {
		$cache_key = $template_id . '_' . $language;

		if ( isset( $this->template_cache[ $cache_key ] ) ) {
			return $this->template_cache[ $cache_key ];
		}

		// Try to load from database first
		$template = $this->get_template_from_database( $template_id, $language );

		if ( ! $template ) {
			// Try to load from file
			$template = $this->get_template_from_file( $template_id, $language );
		}

		// If still not found, try fallback language
		if ( ! $template && $language !== 'en_US' ) {
			$template = $this->load_template( $template_id, 'en_US' );
		}

		$this->template_cache[ $cache_key ] = $template;
		return $template;
	}

	/**
	 * Load email subject for specific language.
	 *
	 * @since    1.0.0
	 * @param    string    $template_id  The template identifier.
	 * @param    string    $language     The language code.
	 * @return   string|false    The subject or false if not found.
	 */
	private function load_subject( $template_id, $language ) {
		$cache_key = $template_id . '_subject_' . $language;

		if ( isset( $this->template_cache[ $cache_key ] ) ) {
			return $this->template_cache[ $cache_key ];
		}

		// Try to load from database first
		$subject = $this->get_subject_from_database( $template_id, $language );

		if ( ! $subject ) {
			// Try to load from translation strings
			$subject = $this->get_subject_from_translation( $template_id, $language );
		}

		// If still not found, try fallback language
		if ( ! $subject && $language !== 'en_US' ) {
			$subject = $this->load_subject( $template_id, 'en_US' );
		}

		$this->template_cache[ $cache_key ] = $subject;
		return $subject;
	}

	/**
	 * Get template from database.
	 *
	 * @since    1.0.0
	 * @param    string    $template_id  The template identifier.
	 * @param    string    $language     The language code.
	 * @return   string|false    The template content or false if not found.
	 */
	private function get_template_from_database( $template_id, $language ) {
		$option_name = 'slbp_email_template_' . $template_id . '_' . $language;
		return get_option( $option_name, false );
	}

	/**
	 * Get subject from database.
	 *
	 * @since    1.0.0
	 * @param    string    $template_id  The template identifier.
	 * @param    string    $language     The language code.
	 * @return   string|false    The subject or false if not found.
	 */
	private function get_subject_from_database( $template_id, $language ) {
		$option_name = 'slbp_email_subject_' . $template_id . '_' . $language;
		return get_option( $option_name, false );
	}

	/**
	 * Get template from file.
	 *
	 * @since    1.0.0
	 * @param    string    $template_id  The template identifier.
	 * @param    string    $language     The language code.
	 * @return   string|false    The template content or false if not found.
	 */
	private function get_template_from_file( $template_id, $language ) {
		$template_path = SLBP_PLUGIN_PATH . 'templates/emails/' . $language . '/' . $template_id . '.html';

		if ( file_exists( $template_path ) ) {
			return file_get_contents( $template_path );
		}

		return false;
	}

	/**
	 * Get subject from translation strings.
	 *
	 * @since    1.0.0
	 * @param    string    $template_id  The template identifier.
	 * @param    string    $language     The language code.
	 * @return   string|false    The subject or false if not found.
	 */
	private function get_subject_from_translation( $template_id, $language ) {
		$subjects = array(
			'payment_success' => __( 'Payment Successful', 'skylearn-billing-pro' ),
			'payment_failed' => __( 'Payment Failed', 'skylearn-billing-pro' ),
			'subscription_created' => __( 'Subscription Created', 'skylearn-billing-pro' ),
			'subscription_cancelled' => __( 'Subscription Cancelled', 'skylearn-billing-pro' ),
			'subscription_renewed' => __( 'Subscription Renewed', 'skylearn-billing-pro' ),
			'course_enrollment' => __( 'Course Enrollment Confirmed', 'skylearn-billing-pro' ),
			'course_access_granted' => __( 'Course Access Granted', 'skylearn-billing-pro' ),
			'course_access_revoked' => __( 'Course Access Revoked', 'skylearn-billing-pro' ),
		);

		return isset( $subjects[ $template_id ] ) ? $subjects[ $template_id ] : false;
	}

	/**
	 * Get user language from arguments.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Template arguments.
	 * @return   string   The user language code.
	 */
	private function get_user_language( $args ) {
		// Check if language is provided in args
		if ( isset( $args['language'] ) ) {
			return $args['language'];
		}

		// Check if user ID is provided
		if ( isset( $args['user_id'] ) ) {
			$user_language = get_user_meta( $args['user_id'], 'slbp_language', true );
			if ( $user_language ) {
				return $user_language;
			}
		}

		// Fall back to current language
		return $this->i18n->get_current_language();
	}

	/**
	 * Save email template for specific language.
	 *
	 * @since    1.0.0
	 * @param    string    $template_id  The template identifier.
	 * @param    string    $language     The language code.
	 * @param    string    $content      The template content.
	 * @return   bool      True on success, false on failure.
	 */
	public function save_template( $template_id, $language, $content ) {
		$option_name = 'slbp_email_template_' . $template_id . '_' . $language;
		$result = update_option( $option_name, $content );

		// Clear cache
		$cache_key = $template_id . '_' . $language;
		unset( $this->template_cache[ $cache_key ] );

		return $result;
	}

	/**
	 * Save email subject for specific language.
	 *
	 * @since    1.0.0
	 * @param    string    $template_id  The template identifier.
	 * @param    string    $language     The language code.
	 * @param    string    $subject      The subject.
	 * @return   bool      True on success, false on failure.
	 */
	public function save_subject( $template_id, $language, $subject ) {
		$option_name = 'slbp_email_subject_' . $template_id . '_' . $language;
		$result = update_option( $option_name, $subject );

		// Clear cache
		$cache_key = $template_id . '_subject_' . $language;
		unset( $this->template_cache[ $cache_key ] );

		return $result;
	}

	/**
	 * Get available email templates.
	 *
	 * @since    1.0.0
	 * @return   array    Array of available template IDs and names.
	 */
	public function get_available_templates() {
		return array(
			'payment_success' => __( 'Payment Success', 'skylearn-billing-pro' ),
			'payment_failed' => __( 'Payment Failed', 'skylearn-billing-pro' ),
			'subscription_created' => __( 'Subscription Created', 'skylearn-billing-pro' ),
			'subscription_cancelled' => __( 'Subscription Cancelled', 'skylearn-billing-pro' ),
			'subscription_renewed' => __( 'Subscription Renewed', 'skylearn-billing-pro' ),
			'course_enrollment' => __( 'Course Enrollment', 'skylearn-billing-pro' ),
			'course_access_granted' => __( 'Course Access Granted', 'skylearn-billing-pro' ),
			'course_access_revoked' => __( 'Course Access Revoked', 'skylearn-billing-pro' ),
		);
	}

	/**
	 * Render email template with variables.
	 *
	 * @since    1.0.0
	 * @param    string    $template_id  The template identifier.
	 * @param    array     $variables    Variables to replace in template.
	 * @param    string    $language     Optional. Language code.
	 * @return   array     Array with 'subject' and 'content' keys.
	 */
	public function render_email( $template_id, $variables = array(), $language = null ) {
		if ( ! $language ) {
			$language = $this->i18n->get_current_language();
		}

		$subject = $this->load_subject( $template_id, $language );
		$content = $this->load_template( $template_id, $language );

		// Replace variables in subject and content
		if ( $subject ) {
			$subject = $this->replace_variables( $subject, $variables );
		}

		if ( $content ) {
			$content = $this->replace_variables( $content, $variables );
		}

		return array(
			'subject' => $subject,
			'content' => $content,
		);
	}

	/**
	 * Replace variables in template content.
	 *
	 * @since    1.0.0
	 * @param    string    $content     The template content.
	 * @param    array     $variables   Variables to replace.
	 * @return   string    The content with variables replaced.
	 */
	private function replace_variables( $content, $variables ) {
		foreach ( $variables as $key => $value ) {
			$content = str_replace( '{{' . $key . '}}', $value, $content );
		}

		return $content;
	}

	/**
	 * Create default email templates for a language.
	 *
	 * @since    1.0.0
	 * @param    string    $language    The language code.
	 * @return   bool      True on success, false on failure.
	 */
	public function create_default_templates( $language ) {
		$templates = $this->get_default_template_content( $language );
		$success = true;

		foreach ( $templates as $template_id => $template_data ) {
			$subject_saved = $this->save_subject( $template_id, $language, $template_data['subject'] );
			$content_saved = $this->save_template( $template_id, $language, $template_data['content'] );

			if ( ! $subject_saved || ! $content_saved ) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Get default template content for a language.
	 *
	 * @since    1.0.0
	 * @param    string    $language    The language code.
	 * @return   array     Array of default template content.
	 */
	private function get_default_template_content( $language ) {
		// For now, return English defaults
		// In a full implementation, these would be properly translated
		return array(
			'payment_success' => array(
				'subject' => __( 'Payment Successful - {{course_name}}', 'skylearn-billing-pro' ),
				'content' => __( 'Dear {{user_name}},<br><br>Your payment for {{course_name}} has been processed successfully.<br><br>Amount: {{amount}}<br>Transaction ID: {{transaction_id}}<br><br>You can now access your course.<br><br>Thank you!', 'skylearn-billing-pro' ),
			),
			'payment_failed' => array(
				'subject' => __( 'Payment Failed - {{course_name}}', 'skylearn-billing-pro' ),
				'content' => __( 'Dear {{user_name}},<br><br>Unfortunately, your payment for {{course_name}} could not be processed.<br><br>Please try again or contact support.<br><br>Error: {{error_message}}', 'skylearn-billing-pro' ),
			),
			'course_enrollment' => array(
				'subject' => __( 'Welcome to {{course_name}}', 'skylearn-billing-pro' ),
				'content' => __( 'Dear {{user_name}},<br><br>You have been successfully enrolled in {{course_name}}.<br><br>You can start learning immediately by visiting your dashboard.<br><br>Happy learning!', 'skylearn-billing-pro' ),
			),
		);
	}
}