<?php
/**
 * Internationalization and Localization Manager
 *
 * Handles language management, currency conversion, and regional settings.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 */

/**
 * Internationalization and Localization Manager class.
 *
 * This class handles all internationalization features including
 * language switching, currency conversion, and regional formatting.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_I18n {

	/**
	 * The current language code.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $current_language    The current language code.
	 */
	private $current_language;

	/**
	 * The current currency code.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $current_currency    The current currency code.
	 */
	private $current_currency;

	/**
	 * Supported languages cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $languages    Supported languages cache.
	 */
	private $languages = null;

	/**
	 * Supported currencies cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $currencies    Supported currencies cache.
	 */
	private $currencies = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Defer user-dependent initialization if WordPress user functions aren't ready
		if ( ! did_action( 'init' ) && ! function_exists( 'is_user_logged_in' ) ) {
			add_action( 'init', array( $this, 'init' ), 1 );
		} else {
			$this->init();
		}
	}

	/**
	 * Initialize internationalization features.
	 *
	 * @since    1.0.0
	 */
	public function init() {
		// Skip if already initialized
		static $initialized = false;
		if ( $initialized ) {
			return;
		}
		$initialized = true;
		
		$this->current_language = $this->detect_language();
		$this->current_currency = $this->detect_currency();

		// Hook into WordPress locale filter
		add_filter( 'locale', array( $this, 'set_locale' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js_translations' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_js_translations' ) );
	}

	/**
	 * Detect the current language.
	 *
	 * @since    1.0.0
	 * @return   string    The detected language code.
	 */
	private function detect_language() {
		$settings = get_option( 'slbp_settings', array() );
		$i18n_settings = isset( $settings['internationalization'] ) ? $settings['internationalization'] : array();

		// Check for user preference (only if user functions are available)
		if ( function_exists('is_user_logged_in') && function_exists('get_current_user_id') && is_user_logged_in() ) {
			$user_language = get_user_meta( get_current_user_id(), 'slbp_language', true );
			if ( $user_language && $this->is_language_supported( $user_language ) ) {
				return $user_language;
			}
		}

		// Check for session language
		if ( isset( $_SESSION['slbp_language'] ) && $this->is_language_supported( $_SESSION['slbp_language'] ) ) {
			return $_SESSION['slbp_language'];
		}

		// Auto-detect from browser if enabled
		if ( isset( $i18n_settings['auto_detect_language'] ) && $i18n_settings['auto_detect_language'] ) {
			$browser_language = $this->detect_browser_language();
			if ( $browser_language && $this->is_language_supported( $browser_language ) ) {
				return $browser_language;
			}
		}

		// Fall back to default language
		return isset( $i18n_settings['default_language'] ) ? $i18n_settings['default_language'] : 'en_US';
	}

	/**
	 * Detect the current currency.
	 *
	 * @since    1.0.0
	 * @return   string    The detected currency code.
	 */
	private function detect_currency() {
		$settings = get_option( 'slbp_settings', array() );
		$i18n_settings = isset( $settings['internationalization'] ) ? $settings['internationalization'] : array();

		// Check for user preference (only if user functions are available)
		if ( function_exists('is_user_logged_in') && function_exists('get_current_user_id') && is_user_logged_in() ) {
			$user_currency = get_user_meta( get_current_user_id(), 'slbp_currency', true );
			if ( $user_currency && $this->is_currency_supported( $user_currency ) ) {
				return $user_currency;
			}
		}

		// Check for session currency
		if ( isset( $_SESSION['slbp_currency'] ) && $this->is_currency_supported( $_SESSION['slbp_currency'] ) ) {
			return $_SESSION['slbp_currency'];
		}

		// Auto-detect from location if enabled
		if ( isset( $i18n_settings['auto_detect_currency'] ) && $i18n_settings['auto_detect_currency'] ) {
			$location_currency = $this->detect_location_currency();
			if ( $location_currency && $this->is_currency_supported( $location_currency ) ) {
				return $location_currency;
			}
		}

		// Fall back to default currency
		return isset( $i18n_settings['default_currency'] ) ? $i18n_settings['default_currency'] : 'USD';
	}

	/**
	 * Detect browser language from Accept-Language header.
	 *
	 * @since    1.0.0
	 * @return   string|false    The detected language code or false.
	 */
	private function detect_browser_language() {
		if ( ! isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			return false;
		}

		$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$languages = explode( ',', $accept_language );

		foreach ( $languages as $language ) {
			$parts = explode( ';', trim( $language ) );
			$lang_code = str_replace( '-', '_', $parts[0] );
			
			// Try exact match first
			if ( $this->is_language_supported( $lang_code ) ) {
				return $lang_code;
			}

			// Try language code without region
			$lang_parts = explode( '_', $lang_code );
			if ( count( $lang_parts ) > 1 ) {
				$base_lang = $lang_parts[0];
				$supported_languages = $this->get_supported_languages();
				
				foreach ( $supported_languages as $supported ) {
					if ( strpos( $supported['language_code'], $base_lang . '_' ) === 0 ) {
						return $supported['language_code'];
					}
				}
			}
		}

		return false;
	}

	/**
	 * Detect currency based on user location.
	 *
	 * @since    1.0.0
	 * @return   string|false    The detected currency code or false.
	 */
	private function detect_location_currency() {
		// This would integrate with a geolocation service
		// For now, return false to use default
		return false;
	}

	/**
	 * Check if a language is supported.
	 *
	 * @since    1.0.0
	 * @param    string    $language_code    The language code to check.
	 * @return   bool      True if supported, false otherwise.
	 */
	private function is_language_supported( $language_code ) {
		$languages = $this->get_supported_languages();
		foreach ( $languages as $language ) {
			if ( $language['language_code'] === $language_code && $language['is_active'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a currency is supported.
	 *
	 * @since    1.0.0
	 * @param    string    $currency_code    The currency code to check.
	 * @return   bool      True if supported, false otherwise.
	 */
	private function is_currency_supported( $currency_code ) {
		$currencies = $this->get_supported_currencies();
		foreach ( $currencies as $currency ) {
			if ( $currency['currency_code'] === $currency_code && $currency['is_active'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get supported languages from database.
	 *
	 * @since    1.0.0
	 * @return   array    Array of supported languages.
	 */
	public function get_supported_languages() {
		if ( $this->languages === null ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'slbp_languages';
			$this->languages = $wpdb->get_results( 
				"SELECT * FROM $table_name WHERE is_active = 1 ORDER BY is_default DESC, language_name ASC", 
				ARRAY_A 
			);
		}
		return $this->languages;
	}

	/**
	 * Get supported currencies from database.
	 *
	 * @since    1.0.0
	 * @return   array    Array of supported currencies.
	 */
	public function get_supported_currencies() {
		if ( $this->currencies === null ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'slbp_currencies';
			$this->currencies = $wpdb->get_results( 
				"SELECT * FROM $table_name WHERE is_active = 1 ORDER BY is_default DESC, currency_name ASC", 
				ARRAY_A 
			);
		}
		return $this->currencies;
	}

	/**
	 * Set WordPress locale based on current language.
	 *
	 * @since    1.0.0
	 * @param    string    $locale    The current locale.
	 * @return   string    The modified locale.
	 */
	public function set_locale( $locale ) {
		return $this->current_language;
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since    1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'skylearn-billing-pro',
			false,
			dirname( plugin_basename( SLBP_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Enqueue JavaScript translations.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_js_translations() {
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'slbp-admin-script', 'skylearn-billing-pro' );
			wp_set_script_translations( 'slbp-public-script', 'skylearn-billing-pro' );
		}
	}

	/**
	 * Switch user language.
	 *
	 * @since    1.0.0
	 * @param    string    $language_code    The language code to switch to.
	 * @return   bool      True on success, false on failure.
	 */
	public function switch_language( $language_code ) {
		if ( ! $this->is_language_supported( $language_code ) ) {
			return false;
		}

		$this->current_language = $language_code;

		// Save to user meta if logged in
		if ( function_exists('is_user_logged_in') && function_exists('get_current_user_id') && is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'slbp_language', $language_code );
		}

		// Save to session
		if ( ! session_id() ) {
			session_start();
		}
		$_SESSION['slbp_language'] = $language_code;

		return true;
	}

	/**
	 * Switch user currency.
	 *
	 * @since    1.0.0
	 * @param    string    $currency_code    The currency code to switch to.
	 * @return   bool      True on success, false on failure.
	 */
	public function switch_currency( $currency_code ) {
		if ( ! $this->is_currency_supported( $currency_code ) ) {
			return false;
		}

		$this->current_currency = $currency_code;

		// Save to user meta if logged in
		if ( function_exists('is_user_logged_in') && function_exists('get_current_user_id') && is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'slbp_currency', $currency_code );
		}

		// Save to session
		if ( ! session_id() ) {
			session_start();
		}
		$_SESSION['slbp_currency'] = $currency_code;

		return true;
	}

	/**
	 * Get current language code.
	 *
	 * @since    1.0.0
	 * @return   string    The current language code.
	 */
	public function get_current_language() {
		return $this->current_language;
	}

	/**
	 * Get current currency code.
	 *
	 * @since    1.0.0
	 * @return   string    The current currency code.
	 */
	public function get_current_currency() {
		return $this->current_currency;
	}

	/**
	 * Format price according to current currency and regional settings.
	 *
	 * @since    1.0.0
	 * @param    float     $amount           The amount to format.
	 * @param    string    $currency_code    Optional. Currency code to use.
	 * @return   string    The formatted price.
	 */
	public function format_price( $amount, $currency_code = null ) {
		if ( $currency_code === null ) {
			$currency_code = $this->current_currency;
		}

		$currencies = $this->get_supported_currencies();
		$currency_data = null;

		foreach ( $currencies as $currency ) {
			if ( $currency['currency_code'] === $currency_code ) {
				$currency_data = $currency;
				break;
			}
		}

		if ( ! $currency_data ) {
			return $amount; // Fallback to raw amount
		}

		// Get language-specific formatting
		$languages = $this->get_supported_languages();
		$language_data = null;

		foreach ( $languages as $language ) {
			if ( $language['language_code'] === $this->current_language ) {
				$language_data = $language;
				break;
			}
		}

		$decimal_places = intval( $currency_data['decimal_places'] );
		$decimal_separator = $language_data ? $language_data['decimal_separator'] : '.';
		$thousand_separator = $language_data ? $language_data['thousand_separator'] : ',';
		$currency_position = $language_data ? $language_data['currency_position'] : 'before';

		$formatted_amount = number_format( $amount, $decimal_places, $decimal_separator, $thousand_separator );
		$symbol = $currency_data['currency_symbol'];

		if ( $currency_position === 'after' ) {
			return $formatted_amount . ' ' . $symbol;
		} else {
			return $symbol . $formatted_amount;
		}
	}

	/**
	 * Convert amount between currencies.
	 *
	 * @since    1.0.0
	 * @param    float     $amount        The amount to convert.
	 * @param    string    $from_currency The source currency code.
	 * @param    string    $to_currency   The target currency code.
	 * @return   float     The converted amount.
	 */
	public function convert_currency( $amount, $from_currency, $to_currency ) {
		if ( $from_currency === $to_currency ) {
			return $amount;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_currencies';

		$from_rate = $wpdb->get_var( $wpdb->prepare(
			"SELECT exchange_rate FROM $table_name WHERE currency_code = %s AND is_active = 1",
			$from_currency
		) );

		$to_rate = $wpdb->get_var( $wpdb->prepare(
			"SELECT exchange_rate FROM $table_name WHERE currency_code = %s AND is_active = 1",
			$to_currency
		) );

		if ( ! $from_rate || ! $to_rate ) {
			return $amount; // Cannot convert
		}

		// Convert to base currency first, then to target currency
		$base_amount = $amount / $from_rate;
		return $base_amount * $to_rate;
	}
}