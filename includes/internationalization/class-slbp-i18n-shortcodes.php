<?php
/**
 * Language Switcher Shortcodes
 *
 * Provides shortcodes for language and currency switching.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 */

/**
 * Language Switcher Shortcodes class.
 *
 * This class handles the registration and rendering of shortcodes
 * for language and currency switching functionality.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_I18n_Shortcodes {

	/**
	 * The language switcher instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_Language_Switcher    $language_switcher    The language switcher instance.
	 */
	private $language_switcher;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    SLBP_Language_Switcher    $language_switcher    The language switcher instance.
	 */
	public function __construct( $language_switcher ) {
		$this->language_switcher = $language_switcher;
		$this->register_shortcodes();
	}

	/**
	 * Register all shortcodes.
	 *
	 * @since    1.0.0
	 */
	private function register_shortcodes() {
		add_shortcode( 'slbp_language_switcher', array( $this, 'language_switcher_shortcode' ) );
		add_shortcode( 'slbp_currency_switcher', array( $this, 'currency_switcher_shortcode' ) );
		add_shortcode( 'slbp_i18n_indicator', array( $this, 'i18n_indicator_shortcode' ) );
	}

	/**
	 * Language switcher shortcode.
	 *
	 * Usage: [slbp_language_switcher style="dropdown" show_flags="1" show_native_names="1"]
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string   The shortcode output.
	 */
	public function language_switcher_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'style'             => 'inline', // inline, dropdown, minimal
			'show_flags'        => '1',
			'show_native_names' => '1',
			'include_currency'  => '0',
		), $atts );

		$args = array(
			'show_flags'        => filter_var( $atts['show_flags'], FILTER_VALIDATE_BOOLEAN ),
			'show_native_names' => filter_var( $atts['show_native_names'], FILTER_VALIDATE_BOOLEAN ),
			'dropdown_style'    => sanitize_text_field( $atts['style'] ),
			'include_currency'  => filter_var( $atts['include_currency'], FILTER_VALIDATE_BOOLEAN ),
		);

		$output = '<div class="slbp-i18n-shortcode">';
		$output .= $this->language_switcher->render_language_switcher( $args );
		$output .= '</div>';

		return $output;
	}

	/**
	 * Currency switcher shortcode.
	 *
	 * Usage: [slbp_currency_switcher style="dropdown" show_symbols="1"]
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string   The shortcode output.
	 */
	public function currency_switcher_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'style'        => 'dropdown', // inline, dropdown, minimal
			'show_symbols' => '1',
		), $atts );

		$args = array(
			'dropdown_style' => sanitize_text_field( $atts['style'] ),
			'show_symbols'   => filter_var( $atts['show_symbols'], FILTER_VALIDATE_BOOLEAN ),
		);

		$output = '<div class="slbp-i18n-shortcode">';
		$output .= $this->language_switcher->render_currency_switcher( $args );
		$output .= '</div>';

		return $output;
	}

	/**
	 * I18n indicator shortcode.
	 *
	 * Usage: [slbp_i18n_indicator]
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string   The shortcode output.
	 */
	public function i18n_indicator_shortcode( $atts ) {
		$output = '<div class="slbp-i18n-shortcode">';
		$output .= $this->language_switcher->render_minimal_indicator();
		$output .= '</div>';

		return $output;
	}
}