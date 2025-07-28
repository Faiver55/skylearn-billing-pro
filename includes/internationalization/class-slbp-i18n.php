<?php
/**
 * Basic Internationalization Manager
 *
 * Handles basic WordPress localization only.
 * Advanced language/region features removed in Phase 3 refactor.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 */

/**
 * Basic Internationalization Manager class.
 *
 * This class handles only basic WordPress localization.
 * Language switching, currency conversion, and regional settings
 * have been removed in Phase 3 refactor.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_I18n {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load plugin textdomain for basic WordPress localization.
	 *
	 * @since    1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'skylearn-billing-pro',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}