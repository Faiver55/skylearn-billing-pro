<?php
/**
 * Language Switcher Widget
 *
 * Widget for displaying language and currency switchers in sidebars.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 */

/**
 * Language Switcher Widget class.
 *
 * This class creates a widget for displaying language and currency
 * switchers in WordPress widget areas.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Language_Switcher_Widget extends WP_Widget {

	/**
	 * The language switcher instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Language_Switcher    $language_switcher    The language switcher instance.
	 */
	private $language_switcher;

	/**
	 * Sets up the widgets name etc
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'slbp_language_switcher_widget',
			'description'                 => __( 'Display language and currency switchers.', 'skylearn-billing-pro' ),
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'slbp_language_switcher', __( 'SkyLearn Language Switcher', 'skylearn-billing-pro' ), $widget_ops );

		// Get language switcher instance from main plugin
		$plugin = SLBP_Plugin::get_instance();
		$this->language_switcher = $plugin->get_from_container( 'language_switcher' );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @since    1.0.0
	 * @param    array    $args      Widget arguments.
	 * @param    array    $instance  Widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( ! $this->language_switcher ) {
			return;
		}

		// Extract widget arguments
		echo $args['before_widget'];

		// Display title if set
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		// Widget content
		echo '<div class="slbp-widget-content">';

		// Display language switcher if enabled
		if ( ! empty( $instance['show_languages'] ) ) {
			$language_args = array(
				'show_flags'        => ! empty( $instance['show_flags'] ),
				'show_native_names' => ! empty( $instance['show_native_names'] ),
				'dropdown_style'    => ! empty( $instance['language_style'] ) ? $instance['language_style'] : 'dropdown',
			);

			if ( ! empty( $instance['language_title'] ) ) {
				echo '<h4 class="slbp-widget-section-title">' . esc_html( $instance['language_title'] ) . '</h4>';
			}

			echo $this->language_switcher->render_language_switcher( $language_args );
		}

		// Display currency switcher if enabled
		if ( ! empty( $instance['show_currencies'] ) ) {
			$currency_args = array(
				'dropdown_style' => ! empty( $instance['currency_style'] ) ? $instance['currency_style'] : 'dropdown',
				'show_symbols'   => ! empty( $instance['show_symbols'] ),
			);

			if ( ! empty( $instance['currency_title'] ) ) {
				echo '<h4 class="slbp-widget-section-title">' . esc_html( $instance['currency_title'] ) . '</h4>';
			}

			echo $this->language_switcher->render_currency_switcher( $currency_args );
		}

		// Display minimal indicator if enabled
		if ( ! empty( $instance['show_indicator'] ) ) {
			if ( ! empty( $instance['indicator_title'] ) ) {
				echo '<h4 class="slbp-widget-section-title">' . esc_html( $instance['indicator_title'] ) . '</h4>';
			}

			echo $this->language_switcher->render_minimal_indicator();
		}

		echo '</div>';

		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @since    1.0.0
	 * @param    array    $instance  The widget options.
	 */
	public function form( $instance ) {
		// Set defaults
		$defaults = array(
			'title'              => __( 'Language & Currency', 'skylearn-billing-pro' ),
			'show_languages'     => true,
			'show_currencies'    => true,
			'show_indicator'     => false,
			'language_title'     => __( 'Language', 'skylearn-billing-pro' ),
			'currency_title'     => __( 'Currency', 'skylearn-billing-pro' ),
			'indicator_title'    => __( 'Current Settings', 'skylearn-billing-pro' ),
			'language_style'     => 'dropdown',
			'currency_style'     => 'dropdown',
			'show_flags'         => true,
			'show_native_names'  => true,
			'show_symbols'       => true,
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'skylearn-billing-pro' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>">
		</p>

		<p>
			<strong><?php esc_html_e( 'Display Options:', 'skylearn-billing-pro' ); ?></strong>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_languages'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_languages' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_languages' ) ); ?>" value="1">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_languages' ) ); ?>">
				<?php esc_html_e( 'Show language switcher', 'skylearn-billing-pro' ); ?>
			</label>
		</p>

		<p style="margin-left: 20px;">
			<label for="<?php echo esc_attr( $this->get_field_id( 'language_title' ) ); ?>">
				<?php esc_html_e( 'Language section title:', 'skylearn-billing-pro' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'language_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'language_title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['language_title'] ); ?>">
		</p>

		<p style="margin-left: 20px;">
			<label for="<?php echo esc_attr( $this->get_field_id( 'language_style' ) ); ?>">
				<?php esc_html_e( 'Language display style:', 'skylearn-billing-pro' ); ?>
			</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'language_style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'language_style' ) ); ?>">
				<option value="dropdown" <?php selected( $instance['language_style'], 'dropdown' ); ?>><?php esc_html_e( 'Dropdown', 'skylearn-billing-pro' ); ?></option>
				<option value="inline" <?php selected( $instance['language_style'], 'inline' ); ?>><?php esc_html_e( 'Inline buttons', 'skylearn-billing-pro' ); ?></option>
			</select>
		</p>

		<p style="margin-left: 20px;">
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_flags'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_flags' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_flags' ) ); ?>" value="1">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_flags' ) ); ?>">
				<?php esc_html_e( 'Show flag icons', 'skylearn-billing-pro' ); ?>
			</label>
		</p>

		<p style="margin-left: 20px;">
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_native_names'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_native_names' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_native_names' ) ); ?>" value="1">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_native_names' ) ); ?>">
				<?php esc_html_e( 'Show native language names', 'skylearn-billing-pro' ); ?>
			</label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_currencies'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_currencies' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_currencies' ) ); ?>" value="1">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_currencies' ) ); ?>">
				<?php esc_html_e( 'Show currency switcher', 'skylearn-billing-pro' ); ?>
			</label>
		</p>

		<p style="margin-left: 20px;">
			<label for="<?php echo esc_attr( $this->get_field_id( 'currency_title' ) ); ?>">
				<?php esc_html_e( 'Currency section title:', 'skylearn-billing-pro' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'currency_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'currency_title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['currency_title'] ); ?>">
		</p>

		<p style="margin-left: 20px;">
			<label for="<?php echo esc_attr( $this->get_field_id( 'currency_style' ) ); ?>">
				<?php esc_html_e( 'Currency display style:', 'skylearn-billing-pro' ); ?>
			</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'currency_style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'currency_style' ) ); ?>">
				<option value="dropdown" <?php selected( $instance['currency_style'], 'dropdown' ); ?>><?php esc_html_e( 'Dropdown', 'skylearn-billing-pro' ); ?></option>
				<option value="inline" <?php selected( $instance['currency_style'], 'inline' ); ?>><?php esc_html_e( 'Inline buttons', 'skylearn-billing-pro' ); ?></option>
			</select>
		</p>

		<p style="margin-left: 20px;">
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_symbols'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_symbols' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_symbols' ) ); ?>" value="1">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_symbols' ) ); ?>">
				<?php esc_html_e( 'Show currency symbols', 'skylearn-billing-pro' ); ?>
			</label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_indicator'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_indicator' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_indicator' ) ); ?>" value="1">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_indicator' ) ); ?>">
				<?php esc_html_e( 'Show current language/currency indicator', 'skylearn-billing-pro' ); ?>
			</label>
		</p>

		<p style="margin-left: 20px;">
			<label for="<?php echo esc_attr( $this->get_field_id( 'indicator_title' ) ); ?>">
				<?php esc_html_e( 'Indicator section title:', 'skylearn-billing-pro' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'indicator_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'indicator_title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['indicator_title'] ); ?>">
		</p>

		<p>
			<em><?php esc_html_e( 'Note: This widget requires the SkyLearn Billing Pro plugin to be active and configured.', 'skylearn-billing-pro' ); ?></em>
		</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @since    1.0.0
	 * @param    array    $new_instance  The new options.
	 * @param    array    $old_instance  The previous options.
	 * @return   array    Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title']              = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['show_languages']     = ! empty( $new_instance['show_languages'] );
		$instance['show_currencies']    = ! empty( $new_instance['show_currencies'] );
		$instance['show_indicator']     = ! empty( $new_instance['show_indicator'] );
		$instance['language_title']     = ( ! empty( $new_instance['language_title'] ) ) ? sanitize_text_field( $new_instance['language_title'] ) : '';
		$instance['currency_title']     = ( ! empty( $new_instance['currency_title'] ) ) ? sanitize_text_field( $new_instance['currency_title'] ) : '';
		$instance['indicator_title']    = ( ! empty( $new_instance['indicator_title'] ) ) ? sanitize_text_field( $new_instance['indicator_title'] ) : '';
		$instance['language_style']     = ( ! empty( $new_instance['language_style'] ) ) ? sanitize_text_field( $new_instance['language_style'] ) : 'dropdown';
		$instance['currency_style']     = ( ! empty( $new_instance['currency_style'] ) ) ? sanitize_text_field( $new_instance['currency_style'] ) : 'dropdown';
		$instance['show_flags']         = ! empty( $new_instance['show_flags'] );
		$instance['show_native_names']  = ! empty( $new_instance['show_native_names'] );
		$instance['show_symbols']       = ! empty( $new_instance['show_symbols'] );

		return $instance;
	}
}

/**
 * Register the widget.
 *
 * @since    1.0.0
 */
function slbp_register_language_switcher_widget() {
	register_widget( 'SLBP_Language_Switcher_Widget' );
}
add_action( 'widgets_init', 'slbp_register_language_switcher_widget' );