<?php
/**
 * Language Switcher Component
 *
 * Provides UI components for language and currency switching.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 */

/**
 * Language Switcher Component class.
 *
 * This class handles the rendering and functionality of language
 * and currency switcher components for both admin and public areas.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Language_Switcher {

	/**
	 * The i18n manager instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SLBP_I18n    $i18n    The i18n manager instance.
	 */
	private $i18n;

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
		// AJAX handlers for language/currency switching
		add_action( 'wp_ajax_slbp_switch_language', array( $this, 'ajax_switch_language' ) );
		add_action( 'wp_ajax_nopriv_slbp_switch_language', array( $this, 'ajax_switch_language' ) );
		add_action( 'wp_ajax_slbp_switch_currency', array( $this, 'ajax_switch_currency' ) );
		add_action( 'wp_ajax_nopriv_slbp_switch_currency', array( $this, 'ajax_switch_currency' ) );

		// Add switcher to admin bar
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 100 );

		// Enqueue styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'slbp-language-switcher',
			SLBP_PLUGIN_URL . 'public/js/language-switcher.js',
			array( 'jquery' ),
			SLBP_VERSION,
			true
		);

		wp_localize_script( 'slbp-language-switcher', 'slbp_switcher', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'slbp_switcher_nonce' ),
		) );

		wp_enqueue_style(
			'slbp-language-switcher',
			SLBP_PLUGIN_URL . 'public/css/language-switcher.css',
			array(),
			SLBP_VERSION
		);
	}

	/**
	 * AJAX handler for language switching.
	 *
	 * @since    1.0.0
	 */
	public function ajax_switch_language() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_switcher_nonce' ) ) {
			wp_die( __( 'Security check failed', 'skylearn-billing-pro' ) );
		}

		$language_code = sanitize_text_field( $_POST['language'] );
		
		if ( $this->i18n->switch_language( $language_code ) ) {
			wp_send_json_success( array(
				'message' => __( 'Language switched successfully', 'skylearn-billing-pro' ),
				'language' => $language_code,
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to switch language', 'skylearn-billing-pro' ),
			) );
		}
	}

	/**
	 * AJAX handler for currency switching.
	 *
	 * @since    1.0.0
	 */
	public function ajax_switch_currency() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'slbp_switcher_nonce' ) ) {
			wp_die( __( 'Security check failed', 'skylearn-billing-pro' ) );
		}

		$currency_code = sanitize_text_field( $_POST['currency'] );
		
		if ( $this->i18n->switch_currency( $currency_code ) ) {
			wp_send_json_success( array(
				'message' => __( 'Currency switched successfully', 'skylearn-billing-pro' ),
				'currency' => $currency_code,
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to switch currency', 'skylearn-billing-pro' ),
			) );
		}
	}

	/**
	 * Add language switcher to admin bar.
	 *
	 * @since    1.0.0
	 * @param    WP_Admin_Bar    $wp_admin_bar    WordPress admin bar instance.
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_language = $this->i18n->get_current_language();
		$current_currency = $this->i18n->get_current_currency();
		$languages = $this->i18n->get_supported_languages();
		$currencies = $this->i18n->get_supported_currencies();

		// Find current language display name
		$current_language_name = $current_language;
		foreach ( $languages as $language ) {
			if ( $language['language_code'] === $current_language ) {
				$current_language_name = $language['native_name'];
				break;
			}
		}

		// Add parent menu
		$wp_admin_bar->add_menu( array(
			'id'    => 'slbp-i18n-switcher',
			'title' => sprintf( 
				'<span class="slbp-switcher-current">%s | %s</span>',
				esc_html( $current_language_name ),
				esc_html( $current_currency )
			),
			'href'  => false,
		) );

		// Add language submenu
		$wp_admin_bar->add_menu( array(
			'parent' => 'slbp-i18n-switcher',
			'id'     => 'slbp-language-switcher',
			'title'  => __( 'Language', 'skylearn-billing-pro' ),
			'href'   => false,
		) );

		foreach ( $languages as $language ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'slbp-language-switcher',
				'id'     => 'slbp-lang-' . $language['language_code'],
				'title'  => $language['native_name'] . ( $language['language_code'] === $current_language ? ' ✓' : '' ),
				'href'   => '#',
				'meta'   => array(
					'class' => 'slbp-language-option',
					'data'  => array(
						'language' => $language['language_code'],
					),
				),
			) );
		}

		// Add currency submenu
		$wp_admin_bar->add_menu( array(
			'parent' => 'slbp-i18n-switcher',
			'id'     => 'slbp-currency-switcher',
			'title'  => __( 'Currency', 'skylearn-billing-pro' ),
			'href'   => false,
		) );

		foreach ( $currencies as $currency ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'slbp-currency-switcher',
				'id'     => 'slbp-curr-' . $currency['currency_code'],
				'title'  => $currency['currency_name'] . ' (' . $currency['currency_symbol'] . ')' . 
						   ( $currency['currency_code'] === $current_currency ? ' ✓' : '' ),
				'href'   => '#',
				'meta'   => array(
					'class' => 'slbp-currency-option',
					'data'  => array(
						'currency' => $currency['currency_code'],
					),
				),
			) );
		}
	}

	/**
	 * Render language switcher dropdown.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Optional arguments for customization.
	 * @return   string   HTML output for the language switcher.
	 */
	public function render_language_switcher( $args = array() ) {
		$defaults = array(
			'show_flags' => true,
			'show_native_names' => true,
			'dropdown_style' => 'inline', // inline, dropdown, or minimal
			'include_currency' => false,
		);

		$args = wp_parse_args( $args, $defaults );
		$languages = $this->i18n->get_supported_languages();
		$current_language = $this->i18n->get_current_language();
		$current_currency = $this->i18n->get_current_currency();

		if ( empty( $languages ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="slbp-language-switcher" data-style="<?php echo esc_attr( $args['dropdown_style'] ); ?>">
			<?php if ( $args['dropdown_style'] === 'dropdown' ) : ?>
				<select class="slbp-language-selector" data-action="switch_language">
					<?php foreach ( $languages as $language ) : ?>
						<option value="<?php echo esc_attr( $language['language_code'] ); ?>" 
							<?php selected( $current_language, $language['language_code'] ); ?>>
							<?php
							$display_name = $args['show_native_names'] ? $language['native_name'] : $language['language_name'];
							echo esc_html( $display_name );
							?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<div class="slbp-language-options">
					<?php foreach ( $languages as $language ) : ?>
						<a href="#" class="slbp-language-option <?php echo ( $current_language === $language['language_code'] ) ? 'active' : ''; ?>" 
						   data-language="<?php echo esc_attr( $language['language_code'] ); ?>">
							<?php if ( $args['show_flags'] && ! empty( $language['flag_icon'] ) ) : ?>
								<span class="slbp-flag slbp-flag-<?php echo esc_attr( $language['flag_icon'] ); ?>"></span>
							<?php endif; ?>
							<span class="slbp-language-name">
								<?php
								$display_name = $args['show_native_names'] ? $language['native_name'] : $language['language_name'];
								echo esc_html( $display_name );
								?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $args['include_currency'] ) : ?>
				<?php echo $this->render_currency_switcher( array( 'dropdown_style' => $args['dropdown_style'] ) ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render currency switcher dropdown.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Optional arguments for customization.
	 * @return   string   HTML output for the currency switcher.
	 */
	public function render_currency_switcher( $args = array() ) {
		$defaults = array(
			'dropdown_style' => 'dropdown', // inline, dropdown, or minimal
			'show_symbols' => true,
		);

		$args = wp_parse_args( $args, $defaults );
		$currencies = $this->i18n->get_supported_currencies();
		$current_currency = $this->i18n->get_current_currency();

		if ( empty( $currencies ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="slbp-currency-switcher" data-style="<?php echo esc_attr( $args['dropdown_style'] ); ?>">
			<?php if ( $args['dropdown_style'] === 'dropdown' ) : ?>
				<select class="slbp-currency-selector" data-action="switch_currency">
					<?php foreach ( $currencies as $currency ) : ?>
						<option value="<?php echo esc_attr( $currency['currency_code'] ); ?>" 
							<?php selected( $current_currency, $currency['currency_code'] ); ?>>
							<?php
							$display_name = $currency['currency_name'];
							if ( $args['show_symbols'] ) {
								$display_name .= ' (' . $currency['currency_symbol'] . ')';
							}
							echo esc_html( $display_name );
							?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<div class="slbp-currency-options">
					<?php foreach ( $currencies as $currency ) : ?>
						<a href="#" class="slbp-currency-option <?php echo ( $current_currency === $currency['currency_code'] ) ? 'active' : ''; ?>" 
						   data-currency="<?php echo esc_attr( $currency['currency_code'] ); ?>">
							<span class="slbp-currency-code"><?php echo esc_html( $currency['currency_code'] ); ?></span>
							<?php if ( $args['show_symbols'] ) : ?>
								<span class="slbp-currency-symbol"><?php echo esc_html( $currency['currency_symbol'] ); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render minimal language/currency indicator.
	 *
	 * @since    1.0.0
	 * @return   string   HTML output for the minimal indicator.
	 */
	public function render_minimal_indicator() {
		$current_language = $this->i18n->get_current_language();
		$current_currency = $this->i18n->get_current_currency();
		$languages = $this->i18n->get_supported_languages();

		// Find current language display info
		$language_display = $current_language;
		$flag_icon = '';
		
		foreach ( $languages as $language ) {
			if ( $language['language_code'] === $current_language ) {
				$language_display = $language['native_name'];
				$flag_icon = $language['flag_icon'];
				break;
			}
		}

		ob_start();
		?>
		<div class="slbp-i18n-indicator">
			<?php if ( $flag_icon ) : ?>
				<span class="slbp-flag slbp-flag-<?php echo esc_attr( $flag_icon ); ?>"></span>
			<?php endif; ?>
			<span class="slbp-current-language"><?php echo esc_html( $language_display ); ?></span>
			<span class="slbp-separator">|</span>
			<span class="slbp-current-currency"><?php echo esc_html( $current_currency ); ?></span>
		</div>
		<?php
		return ob_get_clean();
	}
}