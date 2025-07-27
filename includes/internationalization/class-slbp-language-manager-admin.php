<?php
/**
 * Language Management Admin Interface
 *
 * Admin interface for managing supported languages and their settings.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 */

/**
 * Language Management Admin class.
 *
 * This class handles the admin interface for managing languages,
 * including adding, editing, and removing supported languages.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Language_Manager_Admin {

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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_post_slbp_save_language', array( $this, 'handle_save_language' ) );
		add_action( 'admin_post_slbp_delete_language', array( $this, 'handle_delete_language' ) );
		add_action( 'wp_ajax_slbp_get_language_data', array( $this, 'ajax_get_language_data' ) );
	}

	/**
	 * Add admin menu items.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		// Add as submenu under SkyLearn Billing Pro
		add_submenu_page(
			'slbp-dashboard',
			__( 'Language Manager', 'skylearn-billing-pro' ),
			__( 'Languages', 'skylearn-billing-pro' ),
			'manage_options',
			'slbp-language-manager',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @since    1.0.0
	 */
	public function render_admin_page() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		$language_id = isset( $_GET['language_id'] ) ? intval( $_GET['language_id'] ) : 0;

		switch ( $action ) {
			case 'add':
				$this->render_add_language_form();
				break;
			case 'edit':
				$this->render_edit_language_form( $language_id );
				break;
			default:
				$this->render_languages_list();
				break;
		}
	}

	/**
	 * Render the languages list.
	 *
	 * @since    1.0.0
	 */
	private function render_languages_list() {
		$languages = $this->get_all_languages();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Language Manager', 'skylearn-billing-pro' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-language-manager&action=add' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New Language', 'skylearn-billing-pro' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['message'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $this->get_admin_message( $_GET['message'] ) ); ?></p>
				</div>
			<?php endif; ?>

			<div class="slbp-language-manager">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Language', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'Native Name', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'Code', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'Flag', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'Status', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'Default', 'skylearn-billing-pro' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'skylearn-billing-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $languages ) ) : ?>
							<tr>
								<td colspan="7">
									<p><?php esc_html_e( 'No languages found. Add your first language to get started.', 'skylearn-billing-pro' ); ?></p>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $languages as $language ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $language['language_name'] ); ?></strong></td>
									<td><?php echo esc_html( $language['native_name'] ); ?></td>
									<td><code><?php echo esc_html( $language['language_code'] ); ?></code></td>
									<td>
										<?php if ( ! empty( $language['flag_icon'] ) ) : ?>
											<span class="slbp-flag slbp-flag-<?php echo esc_attr( $language['flag_icon'] ); ?>"></span>
										<?php else : ?>
											—
										<?php endif; ?>
									</td>
									<td>
										<?php if ( $language['is_active'] ) : ?>
											<span class="slbp-status-active"><?php esc_html_e( 'Active', 'skylearn-billing-pro' ); ?></span>
										<?php else : ?>
											<span class="slbp-status-inactive"><?php esc_html_e( 'Inactive', 'skylearn-billing-pro' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( $language['is_default'] ) : ?>
											<span class="slbp-default-indicator">✓ <?php esc_html_e( 'Default', 'skylearn-billing-pro' ); ?></span>
										<?php else : ?>
											—
										<?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-language-manager&action=edit&language_id=' . $language['id'] ) ); ?>">
											<?php esc_html_e( 'Edit', 'skylearn-billing-pro' ); ?>
										</a>
										<?php if ( ! $language['is_default'] ) : ?>
											|
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=slbp_delete_language&language_id=' . $language['id'] ), 'slbp_delete_language_' . $language['id'] ) ); ?>" 
											   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this language?', 'skylearn-billing-pro' ); ?>')">
												<?php esc_html_e( 'Delete', 'skylearn-billing-pro' ); ?>
											</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render add language form.
	 *
	 * @since    1.0.0
	 */
	private function render_add_language_form() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Add New Language', 'skylearn-billing-pro' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-language-manager' ) ); ?>" class="page-title-action">
				<?php esc_html_e( '← Back to Languages', 'skylearn-billing-pro' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php $this->render_language_form(); ?>
		</div>
		<?php
	}

	/**
	 * Render edit language form.
	 *
	 * @since    1.0.0
	 * @param    int    $language_id    The language ID to edit.
	 */
	private function render_edit_language_form( $language_id ) {
		$language = $this->get_language_by_id( $language_id );

		if ( ! $language ) {
			wp_die( __( 'Language not found.', 'skylearn-billing-pro' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Edit Language', 'skylearn-billing-pro' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-language-manager' ) ); ?>" class="page-title-action">
				<?php esc_html_e( '← Back to Languages', 'skylearn-billing-pro' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php $this->render_language_form( $language ); ?>
		</div>
		<?php
	}

	/**
	 * Render language form.
	 *
	 * @since    1.0.0
	 * @param    array    $language    Optional. Language data for editing.
	 */
	private function render_language_form( $language = null ) {
		$is_edit = ! empty( $language );
		$language_id = $is_edit ? $language['id'] : 0;
		
		// Default values
		$defaults = array(
			'language_code' => '',
			'language_name' => '',
			'native_name' => '',
			'flag_icon' => '',
			'is_active' => 1,
			'is_default' => 0,
			'rtl' => 0,
			'date_format' => 'Y-m-d',
			'time_format' => 'H:i:s',
			'decimal_separator' => '.',
			'thousand_separator' => ',',
			'currency_position' => 'before',
		);

		$data = $is_edit ? wp_parse_args( $language, $defaults ) : $defaults;
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'slbp_save_language', 'slbp_language_nonce' ); ?>
			<input type="hidden" name="action" value="slbp_save_language">
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="language_id" value="<?php echo esc_attr( $language_id ); ?>">
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="language_code"><?php esc_html_e( 'Language Code', 'skylearn-billing-pro' ); ?></label>
						</th>
						<td>
							<input type="text" id="language_code" name="language_code" value="<?php echo esc_attr( $data['language_code'] ); ?>" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'Language code in WordPress format (e.g., en_US, es_ES, fr_FR)', 'skylearn-billing-pro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="language_name"><?php esc_html_e( 'Language Name', 'skylearn-billing-pro' ); ?></label>
						</th>
						<td>
							<input type="text" id="language_name" name="language_name" value="<?php echo esc_attr( $data['language_name'] ); ?>" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'Language name in English (e.g., English (United States), Spanish (Spain))', 'skylearn-billing-pro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="native_name"><?php esc_html_e( 'Native Name', 'skylearn-billing-pro' ); ?></label>
						</th>
						<td>
							<input type="text" id="native_name" name="native_name" value="<?php echo esc_attr( $data['native_name'] ); ?>" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'Language name in its native script (e.g., English, Español, Français)', 'skylearn-billing-pro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="flag_icon"><?php esc_html_e( 'Flag Icon', 'skylearn-billing-pro' ); ?></label>
						</th>
						<td>
							<input type="text" id="flag_icon" name="flag_icon" value="<?php echo esc_attr( $data['flag_icon'] ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Flag icon identifier (e.g., us, es, fr, de)', 'skylearn-billing-pro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'skylearn-billing-pro' ); ?></th>
						<td>
							<fieldset>
								<label for="is_active">
									<input type="checkbox" id="is_active" name="is_active" value="1" <?php checked( $data['is_active'], 1 ); ?>>
									<?php esc_html_e( 'Active', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Whether this language is available for selection', 'skylearn-billing-pro' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Language', 'skylearn-billing-pro' ); ?></th>
						<td>
							<fieldset>
								<label for="is_default">
									<input type="checkbox" id="is_default" name="is_default" value="1" <?php checked( $data['is_default'], 1 ); ?>>
									<?php esc_html_e( 'Set as default language', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'The default language for new users and guests', 'skylearn-billing-pro' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Text Direction', 'skylearn-billing-pro' ); ?></th>
						<td>
							<fieldset>
								<label for="rtl">
									<input type="checkbox" id="rtl" name="rtl" value="1" <?php checked( $data['rtl'], 1 ); ?>>
									<?php esc_html_e( 'Right-to-left (RTL)', 'skylearn-billing-pro' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Check if this language uses right-to-left text direction', 'skylearn-billing-pro' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="date_format"><?php esc_html_e( 'Date Format', 'skylearn-billing-pro' ); ?></label>
						</th>
						<td>
							<input type="text" id="date_format" name="date_format" value="<?php echo esc_attr( $data['date_format'] ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'PHP date format for this language (e.g., Y-m-d, d/m/Y, m/d/Y)', 'skylearn-billing-pro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="time_format"><?php esc_html_e( 'Time Format', 'skylearn-billing-pro' ); ?></label>
						</th>
						<td>
							<input type="text" id="time_format" name="time_format" value="<?php echo esc_attr( $data['time_format'] ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'PHP time format for this language (e.g., H:i:s, g:i A)', 'skylearn-billing-pro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="decimal_separator"><?php esc_html_e( 'Decimal Separator', 'skylearn-billing-pro' ); ?></label>
						</th>
						<td>
							<input type="text" id="decimal_separator" name="decimal_separator" value="<?php echo esc_attr( $data['decimal_separator'] ); ?>" class="small-text">
							<p class="description"><?php esc_html_e( 'Character used to separate decimal places (e.g., . or ,)', 'skylearn-billing-pro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="thousand_separator"><?php esc_html_e( 'Thousand Separator', 'skylearn-billing-pro' ); ?></label>
						</th>
						<td>
							<input type="text" id="thousand_separator" name="thousand_separator" value="<?php echo esc_attr( $data['thousand_separator'] ); ?>" class="small-text">
							<p class="description"><?php esc_html_e( 'Character used to separate thousands (e.g., , or . or space)', 'skylearn-billing-pro' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="currency_position"><?php esc_html_e( 'Currency Position', 'skylearn-billing-pro' ); ?></label>
						</th>
						<td>
							<select id="currency_position" name="currency_position">
								<option value="before" <?php selected( $data['currency_position'], 'before' ); ?>><?php esc_html_e( 'Before amount ($100)', 'skylearn-billing-pro' ); ?></option>
								<option value="after" <?php selected( $data['currency_position'], 'after' ); ?>><?php esc_html_e( 'After amount (100$)', 'skylearn-billing-pro' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Position of currency symbol relative to the amount', 'skylearn-billing-pro' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php echo $is_edit ? esc_attr__( 'Update Language', 'skylearn-billing-pro' ) : esc_attr__( 'Add Language', 'skylearn-billing-pro' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=slbp-language-manager' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Cancel', 'skylearn-billing-pro' ); ?>
				</a>
			</p>
		</form>
		<?php
	}

	/**
	 * Handle saving language data.
	 *
	 * @since    1.0.0
	 */
	public function handle_save_language() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['slbp_language_nonce'], 'slbp_save_language' ) ) {
			wp_die( __( 'Security check failed', 'skylearn-billing-pro' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'skylearn-billing-pro' ) );
		}

		$language_id = isset( $_POST['language_id'] ) ? intval( $_POST['language_id'] ) : 0;
		$is_edit = $language_id > 0;

		$language_data = array(
			'language_code' => sanitize_text_field( $_POST['language_code'] ),
			'language_name' => sanitize_text_field( $_POST['language_name'] ),
			'native_name' => sanitize_text_field( $_POST['native_name'] ),
			'flag_icon' => sanitize_text_field( $_POST['flag_icon'] ),
			'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
			'is_default' => isset( $_POST['is_default'] ) ? 1 : 0,
			'rtl' => isset( $_POST['rtl'] ) ? 1 : 0,
			'date_format' => sanitize_text_field( $_POST['date_format'] ),
			'time_format' => sanitize_text_field( $_POST['time_format'] ),
			'decimal_separator' => sanitize_text_field( $_POST['decimal_separator'] ),
			'thousand_separator' => sanitize_text_field( $_POST['thousand_separator'] ),
			'currency_position' => sanitize_text_field( $_POST['currency_position'] ),
		);

		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_languages';

		// If setting as default, unset other defaults first
		if ( $language_data['is_default'] ) {
			$wpdb->update( $table_name, array( 'is_default' => 0 ), array(), array( '%d' ), array() );
		}

		if ( $is_edit ) {
			// Update existing language
			$result = $wpdb->update( $table_name, $language_data, array( 'id' => $language_id ) );
			$message = $result !== false ? 'language_updated' : 'language_update_failed';
		} else {
			// Insert new language
			$result = $wpdb->insert( $table_name, $language_data );
			$message = $result ? 'language_added' : 'language_add_failed';
		}

		// Clear languages cache
		wp_cache_delete( 'slbp_languages', 'slbp' );

		// Redirect back to language list
		$redirect_url = add_query_arg( array( 'message' => $message ), admin_url( 'admin.php?page=slbp-language-manager' ) );
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle deleting language.
	 *
	 * @since    1.0.0
	 */
	public function handle_delete_language() {
		$language_id = isset( $_GET['language_id'] ) ? intval( $_GET['language_id'] ) : 0;

		// Verify nonce
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'slbp_delete_language_' . $language_id ) ) {
			wp_die( __( 'Security check failed', 'skylearn-billing-pro' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'skylearn-billing-pro' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_languages';

		// Check if it's the default language
		$language = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $language_id ), ARRAY_A );
		
		if ( $language && $language['is_default'] ) {
			wp_die( __( 'Cannot delete the default language.', 'skylearn-billing-pro' ) );
		}

		// Delete the language
		$result = $wpdb->delete( $table_name, array( 'id' => $language_id ), array( '%d' ) );
		$message = $result !== false ? 'language_deleted' : 'language_delete_failed';

		// Clear languages cache
		wp_cache_delete( 'slbp_languages', 'slbp' );

		// Redirect back to language list
		$redirect_url = add_query_arg( array( 'message' => $message ), admin_url( 'admin.php?page=slbp-language-manager' ) );
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Get all languages from database.
	 *
	 * @since    1.0.0
	 * @return   array    Array of languages.
	 */
	private function get_all_languages() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_languages';
		
		return $wpdb->get_results( 
			"SELECT * FROM $table_name ORDER BY is_default DESC, language_name ASC", 
			ARRAY_A 
		);
	}

	/**
	 * Get language by ID.
	 *
	 * @since    1.0.0
	 * @param    int      $language_id    The language ID.
	 * @return   array|null    Language data or null if not found.
	 */
	private function get_language_by_id( $language_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_languages';
		
		return $wpdb->get_row( 
			$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $language_id ), 
			ARRAY_A 
		);
	}

	/**
	 * Get admin message text.
	 *
	 * @since    1.0.0
	 * @param    string    $message_code    The message code.
	 * @return   string    The message text.
	 */
	private function get_admin_message( $message_code ) {
		$messages = array(
			'language_added' => __( 'Language added successfully.', 'skylearn-billing-pro' ),
			'language_updated' => __( 'Language updated successfully.', 'skylearn-billing-pro' ),
			'language_deleted' => __( 'Language deleted successfully.', 'skylearn-billing-pro' ),
			'language_add_failed' => __( 'Failed to add language.', 'skylearn-billing-pro' ),
			'language_update_failed' => __( 'Failed to update language.', 'skylearn-billing-pro' ),
			'language_delete_failed' => __( 'Failed to delete language.', 'skylearn-billing-pro' ),
		);

		return isset( $messages[ $message_code ] ) ? $messages[ $message_code ] : '';
	}

	/**
	 * AJAX handler to get language data.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_language_data() {
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'skylearn-billing-pro' ) ) );
		}

		$language_id = isset( $_POST['language_id'] ) ? intval( $_POST['language_id'] ) : 0;
		$language = $this->get_language_by_id( $language_id );

		if ( $language ) {
			wp_send_json_success( $language );
		} else {
			wp_send_json_error( array( 'message' => __( 'Language not found', 'skylearn-billing-pro' ) ) );
		}
	}
}