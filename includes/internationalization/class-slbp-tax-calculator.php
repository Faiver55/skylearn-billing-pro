<?php
/**
 * Tax Calculation and Management
 *
 * Handles VAT, GST, and other regional tax calculations.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 */

/**
 * Tax Calculation and Management class.
 *
 * This class handles tax calculations based on regional rules,
 * VAT validation, and tax compliance features.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/internationalization
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Tax_Calculator {

	/**
	 * Tax rules cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $tax_rules    Tax rules cache.
	 */
	private $tax_rules = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Constructor can be expanded as needed
	}

	/**
	 * Calculate tax for a given amount and location.
	 *
	 * @since    1.0.0
	 * @param    float     $amount         The amount to calculate tax for.
	 * @param    string    $country_code   The country code.
	 * @param    string    $region_code    Optional. The region/state code.
	 * @param    string    $tax_id         Optional. Tax ID for validation.
	 * @return   array     Tax calculation result.
	 */
	public function calculate_tax( $amount, $country_code, $region_code = '', $tax_id = '' ) {
		$tax_rules = $this->get_applicable_tax_rules( $country_code, $region_code );
		
		$result = array(
			'tax_amount' => 0,
			'tax_rate' => 0,
			'tax_type' => '',
			'tax_inclusive' => false,
			'net_amount' => $amount,
			'gross_amount' => $amount,
			'rules_applied' => array(),
			'tax_id_valid' => true,
		);

		// Check if tax calculation is enabled
		$settings = get_option( 'slbp_settings', array() );
		$i18n_settings = isset( $settings['internationalization'] ) ? $settings['internationalization'] : array();
		
		if ( ! isset( $i18n_settings['tax_calculation_enabled'] ) || ! $i18n_settings['tax_calculation_enabled'] ) {
			return $result;
		}

		if ( empty( $tax_rules ) ) {
			return $result;
		}

		// Sort rules by priority (lower number = higher priority)
		usort( $tax_rules, function( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );

		$total_tax_rate = 0;
		$applied_rules = array();

		foreach ( $tax_rules as $rule ) {
			// Validate tax ID if required
			if ( $rule['tax_id_required'] && ! empty( $tax_id ) ) {
				if ( ! $this->validate_tax_id( $tax_id, $rule['tax_id_pattern'] ) ) {
					$result['tax_id_valid'] = false;
					continue;
				}
			} elseif ( $rule['tax_id_required'] && empty( $tax_id ) ) {
				// Tax ID required but not provided
				continue;
			}

			$total_tax_rate += $rule['tax_rate'];
			$applied_rules[] = array(
				'rule_name' => $rule['rule_name'],
				'tax_type' => $rule['tax_type'],
				'tax_rate' => $rule['tax_rate'],
			);

			// For now, we'll use the first applicable rule's type
			if ( empty( $result['tax_type'] ) ) {
				$result['tax_type'] = $rule['tax_type'];
			}
		}

		$result['tax_rate'] = $total_tax_rate;
		$result['rules_applied'] = $applied_rules;

		// Determine if tax is inclusive or exclusive
		$result['tax_inclusive'] = isset( $i18n_settings['display_tax_inclusive'] ) ? 
			$i18n_settings['display_tax_inclusive'] : false;

		if ( $result['tax_inclusive'] ) {
			// Amount includes tax, calculate net amount
			$result['gross_amount'] = $amount;
			$result['net_amount'] = $amount / ( 1 + $total_tax_rate );
			$result['tax_amount'] = $result['gross_amount'] - $result['net_amount'];
		} else {
			// Amount excludes tax, calculate gross amount
			$result['net_amount'] = $amount;
			$result['tax_amount'] = $amount * $total_tax_rate;
			$result['gross_amount'] = $result['net_amount'] + $result['tax_amount'];
		}

		return $result;
	}

	/**
	 * Get applicable tax rules for a location.
	 *
	 * @since    1.0.0
	 * @param    string    $country_code   The country code.
	 * @param    string    $region_code    Optional. The region/state code.
	 * @return   array     Array of applicable tax rules.
	 */
	private function get_applicable_tax_rules( $country_code, $region_code = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_tax_rules';

		$where_clauses = array();
		$where_values = array();

		$where_clauses[] = 'is_active = 1';
		$where_clauses[] = 'country_code = %s';
		$where_values[] = $country_code;

		if ( ! empty( $region_code ) ) {
			$where_clauses[] = '(region_code = %s OR region_code = "")';
			$where_values[] = $region_code;
		} else {
			$where_clauses[] = 'region_code = ""';
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "SELECT * FROM $table_name WHERE $where_sql ORDER BY priority ASC";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Validate tax ID against pattern.
	 *
	 * @since    1.0.0
	 * @param    string    $tax_id     The tax ID to validate.
	 * @param    string    $pattern    The validation pattern.
	 * @return   bool      True if valid, false otherwise.
	 */
	private function validate_tax_id( $tax_id, $pattern ) {
		if ( empty( $pattern ) ) {
			return true; // No pattern means any format is valid
		}

		// Remove whitespace and convert to uppercase
		$clean_tax_id = strtoupper( str_replace( ' ', '', $tax_id ) );

		// Basic regex validation
		return preg_match( '/' . $pattern . '/', $clean_tax_id );
	}

	/**
	 * Get tax rules for management.
	 *
	 * @since    1.0.0
	 * @param    array    $filters    Optional filters.
	 * @return   array    Array of tax rules.
	 */
	public function get_tax_rules( $filters = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_tax_rules';

		$where_clauses = array();
		$where_values = array();

		if ( isset( $filters['country_code'] ) && ! empty( $filters['country_code'] ) ) {
			$where_clauses[] = 'country_code = %s';
			$where_values[] = $filters['country_code'];
		}

		if ( isset( $filters['tax_type'] ) && ! empty( $filters['tax_type'] ) ) {
			$where_clauses[] = 'tax_type = %s';
			$where_values[] = $filters['tax_type'];
		}

		if ( isset( $filters['is_active'] ) ) {
			$where_clauses[] = 'is_active = %d';
			$where_values[] = $filters['is_active'] ? 1 : 0;
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		$sql = "SELECT * FROM $table_name $where_sql ORDER BY country_code, priority ASC";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Add or update a tax rule.
	 *
	 * @since    1.0.0
	 * @param    array    $rule_data    The tax rule data.
	 * @param    int      $rule_id      Optional. Rule ID for update.
	 * @return   int|false Rule ID on success, false on failure.
	 */
	public function save_tax_rule( $rule_data, $rule_id = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_tax_rules';

		$data = array(
			'rule_name' => sanitize_text_field( $rule_data['rule_name'] ),
			'country_code' => sanitize_text_field( $rule_data['country_code'] ),
			'region_code' => sanitize_text_field( $rule_data['region_code'] ?? '' ),
			'tax_type' => sanitize_text_field( $rule_data['tax_type'] ),
			'tax_rate' => floatval( $rule_data['tax_rate'] ),
			'is_active' => intval( $rule_data['is_active'] ?? 1 ),
			'priority' => intval( $rule_data['priority'] ?? 10 ),
			'tax_id_required' => intval( $rule_data['tax_id_required'] ?? 0 ),
			'tax_id_pattern' => sanitize_text_field( $rule_data['tax_id_pattern'] ?? '' ),
		);

		if ( $rule_id ) {
			// Update existing rule
			$result = $wpdb->update( $table_name, $data, array( 'id' => $rule_id ) );
			return $result !== false ? $rule_id : false;
		} else {
			// Insert new rule
			$result = $wpdb->insert( $table_name, $data );
			return $result ? $wpdb->insert_id : false;
		}
	}

	/**
	 * Delete a tax rule.
	 *
	 * @since    1.0.0
	 * @param    int      $rule_id    The rule ID to delete.
	 * @return   bool     True on success, false on failure.
	 */
	public function delete_tax_rule( $rule_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'slbp_tax_rules';

		$result = $wpdb->delete( $table_name, array( 'id' => $rule_id ), array( '%d' ) );
		return $result !== false;
	}

	/**
	 * Get available tax types.
	 *
	 * @since    1.0.0
	 * @return   array    Array of tax types.
	 */
	public function get_tax_types() {
		return array(
			'VAT' => __( 'Value Added Tax (VAT)', 'skylearn-billing-pro' ),
			'GST' => __( 'Goods and Services Tax (GST)', 'skylearn-billing-pro' ),
			'HST' => __( 'Harmonized Sales Tax (HST)', 'skylearn-billing-pro' ),
			'PST' => __( 'Provincial Sales Tax (PST)', 'skylearn-billing-pro' ),
			'QST' => __( 'Quebec Sales Tax (QST)', 'skylearn-billing-pro' ),
			'sales_tax' => __( 'Sales Tax', 'skylearn-billing-pro' ),
			'service_tax' => __( 'Service Tax', 'skylearn-billing-pro' ),
			'other' => __( 'Other', 'skylearn-billing-pro' ),
		);
	}

	/**
	 * Get common tax ID patterns for different countries.
	 *
	 * @since    1.0.0
	 * @return   array    Array of tax ID patterns by country.
	 */
	public function get_tax_id_patterns() {
		return array(
			'US' => array(
				'pattern' => '^[0-9]{2}-[0-9]{7}$',
				'description' => __( 'US Federal Tax ID (XX-XXXXXXX)', 'skylearn-billing-pro' ),
			),
			'GB' => array(
				'pattern' => '^GB[0-9]{9}$|^GB[0-9]{12}$',
				'description' => __( 'UK VAT Number (GB123456789 or GB123456789012)', 'skylearn-billing-pro' ),
			),
			'DE' => array(
				'pattern' => '^DE[0-9]{9}$',
				'description' => __( 'German VAT Number (DE123456789)', 'skylearn-billing-pro' ),
			),
			'FR' => array(
				'pattern' => '^FR[A-Z0-9]{2}[0-9]{9}$',
				'description' => __( 'French VAT Number (FRXX123456789)', 'skylearn-billing-pro' ),
			),
			'ES' => array(
				'pattern' => '^ES[A-Z][0-9]{7}[A-Z0-9]$',
				'description' => __( 'Spanish VAT Number (ESX1234567X)', 'skylearn-billing-pro' ),
			),
			'CA' => array(
				'pattern' => '^[0-9]{9}RT[0-9]{4}$',
				'description' => __( 'Canadian GST/HST Number (123456789RT0001)', 'skylearn-billing-pro' ),
			),
			'AU' => array(
				'pattern' => '^[0-9]{11}$',
				'description' => __( 'Australian ABN (12345678901)', 'skylearn-billing-pro' ),
			),
		);
	}
}