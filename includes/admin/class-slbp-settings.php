<?php
/**
 * The settings functionality of the plugin.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 */

/**
 * The settings functionality of the plugin.
 *
 * Handles settings registration, validation, and management.
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/admin
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Settings {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Settings groups and their options.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings_groups    The settings groups.
	 */
	private $settings_groups;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->define_settings_groups();
	}

	/**
	 * Define settings groups and their structure.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_settings_groups() {
		$this->settings_groups = array(
			'slbp_general_settings' => array(
				'title'   => esc_html__( 'General Settings', 'skylearn-billing-pro' ),
				'options' => array(
					'plugin_enabled',
					'debug_mode',
					'log_level',
					'auto_enrollment',
				),
			),
			'slbp_payment_settings' => array(
				'title'   => esc_html__( 'Payment Gateway Settings', 'skylearn-billing-pro' ),
				'options' => array(
					'lemon_squeezy_api_key',
					'lemon_squeezy_store_id',
					'lemon_squeezy_test_mode',
					'webhook_secret',
				),
			),
			'slbp_lms_settings' => array(
				'title'   => esc_html__( 'LMS Integration Settings', 'skylearn-billing-pro' ),
				'options' => array(
					'learndash_enabled',
					'auto_group_assignment',
					'course_access_type',
				),
			),
			'slbp_product_settings' => array(
				'title'   => esc_html__( 'Product Mapping Settings', 'skylearn-billing-pro' ),
				'options' => array(
					'product_mappings',
				),
			),
			'slbp_email_settings' => array(
				'title'   => esc_html__( 'Email Settings', 'skylearn-billing-pro' ),
				'options' => array(
					'email_notifications_enabled',
					'admin_email_notifications',
					'customer_email_notifications',
					'smtp_enabled',
					'smtp_host',
					'smtp_port',
					'smtp_username',
					'smtp_password',
				),
			),
			'slbp_advanced_settings' => array(
				'title'   => esc_html__( 'Advanced Settings', 'skylearn-billing-pro' ),
				'options' => array(
					'webhook_url',
					'webhook_timeout',
					'cache_enabled',
					'cache_duration',
				),
			),
		);
	}

	/**
	 * Register all settings.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		foreach ( $this->settings_groups as $group_name => $group_data ) {
			register_setting(
				$group_name,
				$group_name,
				array(
					'sanitize_callback' => array( $this, 'sanitize_settings' ),
				)
			);

			foreach ( $group_data['options'] as $option_name ) {
				$this->register_individual_setting( $group_name, $option_name );
			}
		}
	}

	/**
	 * Register an individual setting.
	 *
	 * @since    1.0.0
	 * @param    string    $group_name    The settings group name.
	 * @param    string    $option_name   The option name.
	 */
	private function register_individual_setting( $group_name, $option_name ) {
		$full_option_name = $group_name . '[' . $option_name . ']';
		
		add_settings_field(
			$option_name,
			$this->get_option_label( $option_name ),
			array( $this, 'render_field' ),
			$group_name,
			'default',
			array(
				'group_name'  => $group_name,
				'option_name' => $option_name,
				'field_name'  => $full_option_name,
			)
		);
	}

	/**
	 * Get option label.
	 *
	 * @since    1.0.0
	 * @param    string    $option_name    The option name.
	 * @return   string                    The option label.
	 */
	private function get_option_label( $option_name ) {
		$labels = array(
			'plugin_enabled'                  => esc_html__( 'Enable Plugin', 'skylearn-billing-pro' ),
			'debug_mode'                      => esc_html__( 'Debug Mode', 'skylearn-billing-pro' ),
			'log_level'                       => esc_html__( 'Log Level', 'skylearn-billing-pro' ),
			'auto_enrollment'                 => esc_html__( 'Auto Enrollment', 'skylearn-billing-pro' ),
			'lemon_squeezy_api_key'           => esc_html__( 'Lemon Squeezy API Key', 'skylearn-billing-pro' ),
			'lemon_squeezy_store_id'          => esc_html__( 'Lemon Squeezy Store ID', 'skylearn-billing-pro' ),
			'lemon_squeezy_test_mode'         => esc_html__( 'Test Mode', 'skylearn-billing-pro' ),
			'webhook_secret'                  => esc_html__( 'Webhook Secret', 'skylearn-billing-pro' ),
			'learndash_enabled'               => esc_html__( 'Enable LearnDash Integration', 'skylearn-billing-pro' ),
			'auto_group_assignment'           => esc_html__( 'Auto Group Assignment', 'skylearn-billing-pro' ),
			'course_access_type'              => esc_html__( 'Course Access Type', 'skylearn-billing-pro' ),
			'product_mappings'                => esc_html__( 'Product Mappings', 'skylearn-billing-pro' ),
			'email_notifications_enabled'     => esc_html__( 'Enable Email Notifications', 'skylearn-billing-pro' ),
			'admin_email_notifications'       => esc_html__( 'Admin Email Notifications', 'skylearn-billing-pro' ),
			'customer_email_notifications'    => esc_html__( 'Customer Email Notifications', 'skylearn-billing-pro' ),
			'smtp_enabled'                    => esc_html__( 'Enable SMTP', 'skylearn-billing-pro' ),
			'smtp_host'                       => esc_html__( 'SMTP Host', 'skylearn-billing-pro' ),
			'smtp_port'                       => esc_html__( 'SMTP Port', 'skylearn-billing-pro' ),
			'smtp_username'                   => esc_html__( 'SMTP Username', 'skylearn-billing-pro' ),
			'smtp_password'                   => esc_html__( 'SMTP Password', 'skylearn-billing-pro' ),
			'webhook_url'                     => esc_html__( 'Webhook URL', 'skylearn-billing-pro' ),
			'webhook_timeout'                 => esc_html__( 'Webhook Timeout (seconds)', 'skylearn-billing-pro' ),
			'cache_enabled'                   => esc_html__( 'Enable Caching', 'skylearn-billing-pro' ),
			'cache_duration'                  => esc_html__( 'Cache Duration (minutes)', 'skylearn-billing-pro' ),
		);

		return isset( $labels[ $option_name ] ) ? $labels[ $option_name ] : ucwords( str_replace( '_', ' ', $option_name ) );
	}

	/**
	 * Render a settings field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Field arguments.
	 */
	public function render_field( $args ) {
		$group_name  = $args['group_name'];
		$option_name = $args['option_name'];
		$field_name  = $args['field_name'];
		
		$options = get_option( $group_name, array() );
		$value   = isset( $options[ $option_name ] ) ? $options[ $option_name ] : '';

		switch ( $option_name ) {
			case 'plugin_enabled':
			case 'debug_mode':
			case 'auto_enrollment':
			case 'lemon_squeezy_test_mode':
			case 'learndash_enabled':
			case 'auto_group_assignment':
			case 'email_notifications_enabled':
			case 'admin_email_notifications':
			case 'customer_email_notifications':
			case 'smtp_enabled':
			case 'cache_enabled':
				$this->render_checkbox( $field_name, $value );
				break;

			case 'log_level':
				$this->render_select( $field_name, $value, array(
					'error' => esc_html__( 'Error', 'skylearn-billing-pro' ),
					'warning' => esc_html__( 'Warning', 'skylearn-billing-pro' ),
					'info' => esc_html__( 'Info', 'skylearn-billing-pro' ),
					'debug' => esc_html__( 'Debug', 'skylearn-billing-pro' ),
				) );
				break;

			case 'course_access_type':
				$this->render_select( $field_name, $value, array(
					'open' => esc_html__( 'Open', 'skylearn-billing-pro' ),
					'free' => esc_html__( 'Free', 'skylearn-billing-pro' ),
					'paynow' => esc_html__( 'Buy Now', 'skylearn-billing-pro' ),
					'subscribe' => esc_html__( 'Recurring', 'skylearn-billing-pro' ),
					'closed' => esc_html__( 'Closed', 'skylearn-billing-pro' ),
				) );
				break;

			case 'lemon_squeezy_api_key':
			case 'webhook_secret':
			case 'smtp_password':
				$this->render_password( $field_name, $value );
				break;

			case 'product_mappings':
				$this->render_product_mappings( $field_name, $value );
				break;

			case 'webhook_url':
				$webhook_url = site_url( '/wp-json/slbp/v1/webhook' );
				echo '<input type="text" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $webhook_url ) . '" class="regular-text" readonly />';
				echo '<p class="description">' . esc_html__( 'This is your webhook URL for payment gateway notifications.', 'skylearn-billing-pro' ) . '</p>';
				break;

			case 'smtp_port':
			case 'webhook_timeout':
			case 'cache_duration':
				$this->render_number( $field_name, $value );
				break;

			default:
				$this->render_text( $field_name, $value );
				break;
		}

		// Add description if available
		$description = $this->get_option_description( $option_name );
		if ( $description ) {
			echo '<p class="description">' . wp_kses_post( $description ) . '</p>';
		}
	}

	/**
	 * Render a text field.
	 *
	 * @since    1.0.0
	 * @param    string    $field_name    The field name.
	 * @param    string    $value         The field value.
	 */
	private function render_text( $field_name, $value ) {
		echo '<input type="text" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	/**
	 * Render a password field.
	 *
	 * @since    1.0.0
	 * @param    string    $field_name    The field name.
	 * @param    string    $value         The field value.
	 */
	private function render_password( $field_name, $value ) {
		echo '<input type="password" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	/**
	 * Render a number field.
	 *
	 * @since    1.0.0
	 * @param    string    $field_name    The field name.
	 * @param    string    $value         The field value.
	 */
	private function render_number( $field_name, $value ) {
		echo '<input type="number" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	/**
	 * Render a checkbox field.
	 *
	 * @since    1.0.0
	 * @param    string    $field_name    The field name.
	 * @param    string    $value         The field value.
	 */
	private function render_checkbox( $field_name, $value ) {
		echo '<input type="checkbox" name="' . esc_attr( $field_name ) . '" value="1" ' . checked( $value, 1, false ) . ' />';
	}

	/**
	 * Render a select field.
	 *
	 * @since    1.0.0
	 * @param    string    $field_name    The field name.
	 * @param    string    $value         The field value.
	 * @param    array     $options       The select options.
	 */
	private function render_select( $field_name, $value, $options ) {
		echo '<select name="' . esc_attr( $field_name ) . '">';
		foreach ( $options as $option_value => $option_label ) {
			echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>';
			echo esc_html( $option_label );
			echo '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render product mappings field.
	 *
	 * @since    1.0.0
	 * @param    string    $field_name    The field name.
	 * @param    mixed     $value         The field value.
	 */
	private function render_product_mappings( $field_name, $value ) {
		$mappings = is_array( $value ) ? $value : array();
		
		echo '<div id="slbp-product-mappings">';
		echo '<div class="slbp-mappings-container">';
		
		if ( empty( $mappings ) ) {
			$mappings = array( array( 'product_id' => '', 'product_name' => '', 'course_id' => '' ) );
		}

		foreach ( $mappings as $index => $mapping ) {
			$this->render_product_mapping_row( $field_name, $index, $mapping );
		}
		
		echo '</div>';
		echo '<button type="button" class="button slbp-add-mapping">' . esc_html__( 'Add Product Mapping', 'skylearn-billing-pro' ) . '</button>';
		echo '</div>';
	}

	/**
	 * Render a single product mapping row.
	 *
	 * @since    1.0.0
	 * @param    string    $field_name    The field name.
	 * @param    int       $index         The row index.
	 * @param    array     $mapping       The mapping data.
	 */
	private function render_product_mapping_row( $field_name, $index, $mapping ) {
		echo '<div class="slbp-mapping-row">';
		
		echo '<input type="text" name="' . esc_attr( $field_name ) . '[' . $index . '][product_id]" ';
		echo 'value="' . esc_attr( $mapping['product_id'] ?? '' ) . '" ';
		echo 'placeholder="' . esc_attr__( 'Product ID', 'skylearn-billing-pro' ) . '" class="regular-text" />';
		
		echo '<input type="text" name="' . esc_attr( $field_name ) . '[' . $index . '][product_name]" ';
		echo 'value="' . esc_attr( $mapping['product_name'] ?? '' ) . '" ';
		echo 'placeholder="' . esc_attr__( 'Product Name', 'skylearn-billing-pro' ) . '" class="regular-text" />';
		
		// Course selection dropdown
		echo '<select name="' . esc_attr( $field_name ) . '[' . $index . '][course_id]">';
		echo '<option value="">' . esc_html__( 'Select Course', 'skylearn-billing-pro' ) . '</option>';
		
		$courses = $this->get_learndash_courses();
		foreach ( $courses as $course ) {
			$selected = selected( $mapping['course_id'] ?? '', $course->ID, false );
			echo '<option value="' . esc_attr( $course->ID ) . '" ' . $selected . '>';
			echo esc_html( $course->post_title );
			echo '</option>';
		}
		
		echo '</select>';
		
		echo '<button type="button" class="button slbp-remove-mapping">' . esc_html__( 'Remove', 'skylearn-billing-pro' ) . '</button>';
		echo '</div>';
	}

	/**
	 * Get LearnDash courses.
	 *
	 * @since    1.0.0
	 * @return   array    Array of course objects.
	 */
	private function get_learndash_courses() {
		if ( ! post_type_exists( 'sfwd-courses' ) ) {
			return array();
		}

		return get_posts( array(
			'post_type'      => 'sfwd-courses',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
	}

	/**
	 * Get option description.
	 *
	 * @since    1.0.0
	 * @param    string    $option_name    The option name.
	 * @return   string                    The option description.
	 */
	private function get_option_description( $option_name ) {
		$descriptions = array(
			'plugin_enabled'                  => esc_html__( 'Enable or disable the SkyLearn Billing Pro functionality.', 'skylearn-billing-pro' ),
			'debug_mode'                      => esc_html__( 'Enable debug mode for troubleshooting. Should be disabled in production.', 'skylearn-billing-pro' ),
			'lemon_squeezy_api_key'           => esc_html__( 'Your Lemon Squeezy API key. You can find this in your Lemon Squeezy dashboard.', 'skylearn-billing-pro' ),
			'lemon_squeezy_store_id'          => esc_html__( 'Your Lemon Squeezy store ID.', 'skylearn-billing-pro' ),
			'lemon_squeezy_test_mode'         => esc_html__( 'Enable test mode for Lemon Squeezy integration.', 'skylearn-billing-pro' ),
			'webhook_secret'                  => esc_html__( 'Secret key for webhook verification. Generate a secure random string.', 'skylearn-billing-pro' ),
			'learndash_enabled'               => esc_html__( 'Enable integration with LearnDash LMS.', 'skylearn-billing-pro' ),
			'product_mappings'                => esc_html__( 'Map your payment gateway products to LearnDash courses.', 'skylearn-billing-pro' ),
			'smtp_enabled'                    => esc_html__( 'Use SMTP for sending emails instead of the default WordPress mail function.', 'skylearn-billing-pro' ),
		);

		return isset( $descriptions[ $option_name ] ) ? $descriptions[ $option_name ] : '';
	}

	/**
	 * Sanitize settings.
	 *
	 * @since    1.0.0
	 * @param    array    $input    The input data.
	 * @return   array              The sanitized data.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( ! is_array( $input ) ) {
			return $sanitized;
		}

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'lemon_squeezy_api_key':
				case 'webhook_secret':
				case 'smtp_password':
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;

				case 'smtp_port':
				case 'webhook_timeout':
				case 'cache_duration':
					$sanitized[ $key ] = absint( $value );
					break;

				case 'plugin_enabled':
				case 'debug_mode':
				case 'auto_enrollment':
				case 'lemon_squeezy_test_mode':
				case 'learndash_enabled':
				case 'auto_group_assignment':
				case 'email_notifications_enabled':
				case 'admin_email_notifications':
				case 'customer_email_notifications':
				case 'smtp_enabled':
				case 'cache_enabled':
					$sanitized[ $key ] = $value ? 1 : 0;
					break;

				case 'product_mappings':
					$sanitized[ $key ] = $this->sanitize_product_mappings( $value );
					break;

				default:
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize product mappings.
	 *
	 * @since    1.0.0
	 * @param    mixed    $mappings    The product mappings.
	 * @return   array                 The sanitized mappings.
	 */
	private function sanitize_product_mappings( $mappings ) {
		if ( ! is_array( $mappings ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $mappings as $mapping ) {
			if ( ! is_array( $mapping ) ) {
				continue;
			}

			$product_id   = sanitize_text_field( $mapping['product_id'] ?? '' );
			$product_name = sanitize_text_field( $mapping['product_name'] ?? '' );
			$course_id    = absint( $mapping['course_id'] ?? 0 );

			// Only add non-empty mappings
			if ( ! empty( $product_id ) && ! empty( $product_name ) && $course_id > 0 ) {
				$sanitized[] = array(
					'product_id'   => $product_id,
					'product_name' => $product_name,
					'course_id'    => $course_id,
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Get a setting value.
	 *
	 * @since    1.0.0
	 * @param    string    $group_name     The settings group name.
	 * @param    string    $option_name    The option name.
	 * @param    mixed     $default        The default value.
	 * @return   mixed                     The setting value.
	 */
	public function get_setting( $group_name, $option_name, $default = '' ) {
		$options = get_option( $group_name, array() );
		return isset( $options[ $option_name ] ) ? $options[ $option_name ] : $default;
	}

	/**
	 * Update a setting value.
	 *
	 * @since    1.0.0
	 * @param    string    $group_name     The settings group name.
	 * @param    string    $option_name    The option name.
	 * @param    mixed     $value          The value to set.
	 * @return   bool                      True if successful, false otherwise.
	 */
	public function update_setting( $group_name, $option_name, $value ) {
		$options = get_option( $group_name, array() );
		$options[ $option_name ] = $value;
		return update_option( $group_name, $options );
	}

	/**
	 * Get all settings groups.
	 *
	 * @since    1.0.0
	 * @return   array    The settings groups.
	 */
	public function get_settings_groups() {
		return $this->settings_groups;
	}
}