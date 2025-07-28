<?php
/**
 * Custom KPI management for SkyLearn Billing Pro.
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/admin/partials
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize KPI manager
if ( ! class_exists( 'SLBP_KPI_Manager' ) ) {
	require_once SLBP_PLUGIN_PATH . 'includes/analytics/class-slbp-kpi-manager.php';
}

$kpi_manager = new SLBP_KPI_Manager();
$custom_kpis = $kpi_manager->get_custom_kpis();
$available_metrics = $kpi_manager->get_available_metrics();
?>

<div class="wrap slbp-kpi-management">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Custom KPI Management', 'skylearn-billing-pro' ); ?></h1>
	<a href="#" class="page-title-action" id="slbp-add-kpi-btn"><?php esc_html_e( 'Add New KPI', 'skylearn-billing-pro' ); ?></a>

	<div class="slbp-kpi-overview">
		<div class="slbp-overview-card">
			<h3><?php esc_html_e( 'Active KPIs', 'skylearn-billing-pro' ); ?></h3>
			<div class="slbp-overview-number"><?php echo count( $custom_kpis ); ?></div>
		</div>
		<div class="slbp-overview-card">
			<h3><?php esc_html_e( 'Available Metrics', 'skylearn-billing-pro' ); ?></h3>
			<div class="slbp-overview-number"><?php echo count( $available_metrics ); ?></div>
		</div>
		<div class="slbp-overview-card">
			<h3><?php esc_html_e( 'Alert Thresholds', 'skylearn-billing-pro' ); ?></h3>
			<div class="slbp-overview-number"><?php echo array_sum( array_map( function( $kpi ) { return isset( $kpi['threshold'] ) ? 1 : 0; }, $custom_kpis ) ); ?></div>
		</div>
	</div>

	<!-- Custom KPIs Table -->
	<div class="slbp-kpis-table-container">
		<h2><?php esc_html_e( 'Custom KPIs', 'skylearn-billing-pro' ); ?></h2>
		
		<?php if ( ! empty( $custom_kpis ) ) : ?>
		<table class="wp-list-table widefat fixed striped slbp-kpis-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Name', 'skylearn-billing-pro' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Description', 'skylearn-billing-pro' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Current Value', 'skylearn-billing-pro' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Threshold', 'skylearn-billing-pro' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'skylearn-billing-pro' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'skylearn-billing-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $custom_kpis as $kpi_id => $kpi ) : 
					$current_value = $kpi_manager->calculate_kpi_value( $kpi_id );
					$status = $kpi_manager->get_kpi_status( $kpi_id, $current_value );
				?>
				<tr data-kpi-id="<?php echo esc_attr( $kpi_id ); ?>">
					<td class="slbp-kpi-name">
						<strong><?php echo esc_html( $kpi['name'] ); ?></strong>
						<?php if ( ! $kpi['active'] ) : ?>
							<span class="slbp-kpi-inactive"><?php esc_html_e( '(Inactive)', 'skylearn-billing-pro' ); ?></span>
						<?php endif; ?>
					</td>
					<td class="slbp-kpi-description">
						<?php echo esc_html( $kpi['description'] ); ?>
					</td>
					<td class="slbp-kpi-value">
						<span class="slbp-value-display" data-value="<?php echo esc_attr( $current_value ); ?>">
							<?php echo esc_html( $kpi_manager->format_kpi_value( $current_value, $kpi ) ); ?>
						</span>
					</td>
					<td class="slbp-kpi-threshold">
						<?php if ( isset( $kpi['threshold'] ) && ! empty( $kpi['threshold'] ) ) : ?>
							<div class="slbp-threshold-info">
								<?php if ( isset( $kpi['threshold']['warning'] ) ) : ?>
									<span class="slbp-threshold-warning">⚠️ <?php echo esc_html( $kpi['threshold']['warning'] ); ?></span>
								<?php endif; ?>
								<?php if ( isset( $kpi['threshold']['critical'] ) ) : ?>
									<span class="slbp-threshold-critical">🚨 <?php echo esc_html( $kpi['threshold']['critical'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php else : ?>
							<span class="slbp-no-threshold"><?php esc_html_e( 'No threshold set', 'skylearn-billing-pro' ); ?></span>
						<?php endif; ?>
					</td>
					<td class="slbp-kpi-status">
						<span class="slbp-status-indicator slbp-status-<?php echo esc_attr( $status ); ?>">
							<?php echo esc_html( ucfirst( $status ) ); ?>
						</span>
					</td>
					<td class="slbp-kpi-actions">
						<button type="button" class="button button-small slbp-edit-kpi" data-kpi-id="<?php echo esc_attr( $kpi_id ); ?>">
							<?php esc_html_e( 'Edit', 'skylearn-billing-pro' ); ?>
						</button>
						<button type="button" class="button button-small slbp-view-kpi-chart" data-kpi-id="<?php echo esc_attr( $kpi_id ); ?>">
							<?php esc_html_e( 'Chart', 'skylearn-billing-pro' ); ?>
						</button>
						<button type="button" class="button button-small slbp-delete-kpi" data-kpi-id="<?php echo esc_attr( $kpi_id ); ?>">
							<?php esc_html_e( 'Delete', 'skylearn-billing-pro' ); ?>
						</button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
		<div class="slbp-no-kpis">
			<p><?php esc_html_e( 'No custom KPIs have been created yet.', 'skylearn-billing-pro' ); ?></p>
			<button type="button" class="button button-primary" id="slbp-create-first-kpi">
				<?php esc_html_e( 'Create Your First KPI', 'skylearn-billing-pro' ); ?>
			</button>
		</div>
		<?php endif; ?>
	</div>

	<!-- KPI Templates -->
	<div class="slbp-kpi-templates">
		<h2><?php esc_html_e( 'KPI Templates', 'skylearn-billing-pro' ); ?></h2>
		<p><?php esc_html_e( 'Quick start with pre-built KPI templates:', 'skylearn-billing-pro' ); ?></p>
		
		<div class="slbp-templates-grid">
			<div class="slbp-template-card" data-template="customer-lifetime-value">
				<h4><?php esc_html_e( 'Customer Lifetime Value', 'skylearn-billing-pro' ); ?></h4>
				<p><?php esc_html_e( 'Track the average revenue per customer over their lifetime.', 'skylearn-billing-pro' ); ?></p>
				<button type="button" class="button slbp-use-template" data-template="customer-lifetime-value">
					<?php esc_html_e( 'Use Template', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
			
			<div class="slbp-template-card" data-template="monthly-growth-rate">
				<h4><?php esc_html_e( 'Monthly Growth Rate', 'skylearn-billing-pro' ); ?></h4>
				<p><?php esc_html_e( 'Monitor month-over-month growth in revenue or users.', 'skylearn-billing-pro' ); ?></p>
				<button type="button" class="button slbp-use-template" data-template="monthly-growth-rate">
					<?php esc_html_e( 'Use Template', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
			
			<div class="slbp-template-card" data-template="course-completion-rate">
				<h4><?php esc_html_e( 'Course Completion Rate', 'skylearn-billing-pro' ); ?></h4>
				<p><?php esc_html_e( 'Track the percentage of students completing courses.', 'skylearn-billing-pro' ); ?></p>
				<button type="button" class="button slbp-use-template" data-template="course-completion-rate">
					<?php esc_html_e( 'Use Template', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
			
			<div class="slbp-template-card" data-template="revenue-per-course">
				<h4><?php esc_html_e( 'Revenue per Course', 'skylearn-billing-pro' ); ?></h4>
				<p><?php esc_html_e( 'Monitor average revenue generated per course.', 'skylearn-billing-pro' ); ?></p>
				<button type="button" class="button slbp-use-template" data-template="revenue-per-course">
					<?php esc_html_e( 'Use Template', 'skylearn-billing-pro' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<!-- KPI Modal -->
<div id="slbp-kpi-modal" class="slbp-modal" style="display: none;">
	<div class="slbp-modal-content">
		<div class="slbp-modal-header">
			<h2 id="slbp-modal-title"><?php esc_html_e( 'Add New KPI', 'skylearn-billing-pro' ); ?></h2>
			<button type="button" class="slbp-modal-close">&times;</button>
		</div>
		
		<form id="slbp-kpi-form">
			<input type="hidden" id="slbp-kpi-id" name="kpi_id" value="">
			
			<div class="slbp-form-group">
				<label for="slbp-kpi-name"><?php esc_html_e( 'KPI Name', 'skylearn-billing-pro' ); ?> <span class="required">*</span></label>
				<input type="text" id="slbp-kpi-name" name="name" required>
			</div>
			
			<div class="slbp-form-group">
				<label for="slbp-kpi-description"><?php esc_html_e( 'Description', 'skylearn-billing-pro' ); ?></label>
				<textarea id="slbp-kpi-description" name="description" rows="3"></textarea>
			</div>
			
			<div class="slbp-form-group">
				<label for="slbp-kpi-calculation-type"><?php esc_html_e( 'Calculation Type', 'skylearn-billing-pro' ); ?> <span class="required">*</span></label>
				<select id="slbp-kpi-calculation-type" name="calculation_type" required>
					<option value=""><?php esc_html_e( 'Select calculation type', 'skylearn-billing-pro' ); ?></option>
					<option value="simple"><?php esc_html_e( 'Simple Metric', 'skylearn-billing-pro' ); ?></option>
					<option value="ratio"><?php esc_html_e( 'Ratio/Percentage', 'skylearn-billing-pro' ); ?></option>
					<option value="growth"><?php esc_html_e( 'Growth Rate', 'skylearn-billing-pro' ); ?></option>
					<option value="average"><?php esc_html_e( 'Average Value', 'skylearn-billing-pro' ); ?></option>
					<option value="custom"><?php esc_html_e( 'Custom Formula', 'skylearn-billing-pro' ); ?></option>
				</select>
			</div>
			
			<div class="slbp-form-group" id="slbp-simple-metric-group" style="display: none;">
				<label for="slbp-simple-metric"><?php esc_html_e( 'Metric', 'skylearn-billing-pro' ); ?></label>
				<select id="slbp-simple-metric" name="simple_metric">
					<option value=""><?php esc_html_e( 'Select metric', 'skylearn-billing-pro' ); ?></option>
					<?php foreach ( $available_metrics as $metric_id => $metric ) : ?>
						<option value="<?php echo esc_attr( $metric_id ); ?>"><?php echo esc_html( $metric['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			
			<div class="slbp-form-group" id="slbp-ratio-metrics-group" style="display: none;">
				<div class="slbp-ratio-inputs">
					<div class="slbp-ratio-numerator">
						<label for="slbp-ratio-numerator"><?php esc_html_e( 'Numerator', 'skylearn-billing-pro' ); ?></label>
						<select id="slbp-ratio-numerator" name="ratio_numerator">
							<option value=""><?php esc_html_e( 'Select numerator', 'skylearn-billing-pro' ); ?></option>
							<?php foreach ( $available_metrics as $metric_id => $metric ) : ?>
								<option value="<?php echo esc_attr( $metric_id ); ?>"><?php echo esc_html( $metric['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="slbp-ratio-denominator">
						<label for="slbp-ratio-denominator"><?php esc_html_e( 'Denominator', 'skylearn-billing-pro' ); ?></label>
						<select id="slbp-ratio-denominator" name="ratio_denominator">
							<option value=""><?php esc_html_e( 'Select denominator', 'skylearn-billing-pro' ); ?></option>
							<?php foreach ( $available_metrics as $metric_id => $metric ) : ?>
								<option value="<?php echo esc_attr( $metric_id ); ?>"><?php echo esc_html( $metric['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
			
			<div class="slbp-form-group" id="slbp-custom-formula-group" style="display: none;">
				<label for="slbp-custom-formula"><?php esc_html_e( 'Custom Formula', 'skylearn-billing-pro' ); ?></label>
				<textarea id="slbp-custom-formula" name="custom_formula" rows="3" placeholder="e.g., (total_revenue - total_costs) / total_revenue * 100"></textarea>
				<p class="description"><?php esc_html_e( 'Use metric names in curly braces, e.g., {total_revenue}, {active_users}', 'skylearn-billing-pro' ); ?></p>
			</div>
			
			<div class="slbp-form-group">
				<label for="slbp-kpi-unit"><?php esc_html_e( 'Unit', 'skylearn-billing-pro' ); ?></label>
				<select id="slbp-kpi-unit" name="unit">
					<option value="number"><?php esc_html_e( 'Number', 'skylearn-billing-pro' ); ?></option>
					<option value="currency"><?php esc_html_e( 'Currency', 'skylearn-billing-pro' ); ?></option>
					<option value="percentage"><?php esc_html_e( 'Percentage', 'skylearn-billing-pro' ); ?></option>
					<option value="time"><?php esc_html_e( 'Time', 'skylearn-billing-pro' ); ?></option>
				</select>
			</div>
			
			<div class="slbp-form-group">
				<label><?php esc_html_e( 'Thresholds', 'skylearn-billing-pro' ); ?></label>
				<div class="slbp-threshold-inputs">
					<div class="slbp-threshold-input">
						<label for="slbp-threshold-warning"><?php esc_html_e( 'Warning Threshold', 'skylearn-billing-pro' ); ?></label>
						<input type="number" id="slbp-threshold-warning" name="threshold_warning" step="0.01">
					</div>
					<div class="slbp-threshold-input">
						<label for="slbp-threshold-critical"><?php esc_html_e( 'Critical Threshold', 'skylearn-billing-pro' ); ?></label>
						<input type="number" id="slbp-threshold-critical" name="threshold_critical" step="0.01">
					</div>
				</div>
			</div>
			
			<div class="slbp-form-group">
				<label>
					<input type="checkbox" id="slbp-kpi-active" name="active" checked>
					<?php esc_html_e( 'Active', 'skylearn-billing-pro' ); ?>
				</label>
			</div>
			
			<div class="slbp-modal-footer">
				<button type="button" class="button" id="slbp-cancel-kpi"><?php esc_html_e( 'Cancel', 'skylearn-billing-pro' ); ?></button>
				<button type="submit" class="button button-primary" id="slbp-save-kpi"><?php esc_html_e( 'Save KPI', 'skylearn-billing-pro' ); ?></button>
			</div>
		</form>
	</div>
</div>

<!-- Chart Modal -->
<div id="slbp-chart-modal" class="slbp-modal" style="display: none;">
	<div class="slbp-modal-content slbp-chart-modal-content">
		<div class="slbp-modal-header">
			<h2 id="slbp-chart-title"><?php esc_html_e( 'KPI Chart', 'skylearn-billing-pro' ); ?></h2>
			<button type="button" class="slbp-modal-close">&times;</button>
		</div>
		
		<div class="slbp-chart-controls">
			<select id="slbp-chart-timeframe">
				<option value="last_7_days"><?php esc_html_e( 'Last 7 Days', 'skylearn-billing-pro' ); ?></option>
				<option value="last_30_days" selected><?php esc_html_e( 'Last 30 Days', 'skylearn-billing-pro' ); ?></option>
				<option value="last_90_days"><?php esc_html_e( 'Last 90 Days', 'skylearn-billing-pro' ); ?></option>
			</select>
		</div>
		
		<div class="slbp-chart-container">
			<canvas id="slbp-kpi-chart"></canvas>
		</div>
	</div>
</div>

<style>
.slbp-kpi-management .slbp-kpi-overview {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.slbp-overview-card {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 20px;
	text-align: center;
}

.slbp-overview-card h3 {
	margin: 0 0 10px 0;
	font-size: 14px;
	color: #666;
}

.slbp-overview-number {
	font-size: 32px;
	font-weight: bold;
	color: #0073aa;
}

.slbp-templates-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.slbp-template-card {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 20px;
}

.slbp-template-card h4 {
	margin: 0 0 10px 0;
	color: #0073aa;
}

.slbp-status-indicator {
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: bold;
}

.slbp-status-good { background: #d4edda; color: #155724; }
.slbp-status-warning { background: #fff3cd; color: #856404; }
.slbp-status-critical { background: #f8d7da; color: #721c24; }

.slbp-modal {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0,0,0,0.5);
	z-index: 100000;
}

.slbp-modal-content {
	background: #fff;
	margin: 50px auto;
	padding: 0;
	width: 90%;
	max-width: 600px;
	border-radius: 4px;
	max-height: 90vh;
	overflow-y: auto;
}

.slbp-modal-header {
	padding: 20px;
	border-bottom: 1px solid #ddd;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.slbp-modal-header h2 {
	margin: 0;
}

.slbp-modal-close {
	background: none;
	border: none;
	font-size: 24px;
	cursor: pointer;
}

.slbp-kpi-form {
	padding: 20px;
}

.slbp-form-group {
	margin-bottom: 20px;
}

.slbp-form-group label {
	display: block;
	margin-bottom: 5px;
	font-weight: bold;
}

.slbp-form-group input,
.slbp-form-group select,
.slbp-form-group textarea {
	width: 100%;
	padding: 8px;
	border: 1px solid #ddd;
	border-radius: 3px;
}

.slbp-ratio-inputs {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 15px;
}

.slbp-threshold-inputs {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 15px;
}

.slbp-modal-footer {
	padding: 20px;
	border-top: 1px solid #ddd;
	text-align: right;
}

.slbp-modal-footer .button {
	margin-left: 10px;
}

.required {
	color: #d63384;
}

.slbp-no-kpis {
	text-align: center;
	padding: 40px;
	background: #f9f9f9;
	border-radius: 4px;
}

.slbp-chart-modal-content {
	max-width: 800px;
}

.slbp-chart-controls {
	padding: 20px;
	border-bottom: 1px solid #ddd;
}

.slbp-chart-container {
	padding: 20px;
	height: 400px;
}
</style>

<!-- JavaScript variables for AJAX -->
<script type="text/javascript">
	var slbpKPI = {
		ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
		nonce: '<?php echo esc_js( wp_create_nonce( 'slbp_kpi_nonce' ) ); ?>',
		availableMetrics: <?php echo wp_json_encode( $available_metrics ); ?>,
		templates: {
			'customer-lifetime-value': {
				name: '<?php esc_js( __( 'Customer Lifetime Value', 'skylearn-billing-pro' ) ); ?>',
				description: '<?php esc_js( __( 'Average revenue per customer over their lifetime', 'skylearn-billing-pro' ) ); ?>',
				calculation_type: 'custom',
				custom_formula: '{total_revenue} / {total_customers}',
				unit: 'currency'
			},
			'monthly-growth-rate': {
				name: '<?php esc_js( __( 'Monthly Growth Rate', 'skylearn-billing-pro' ) ); ?>',
				description: '<?php esc_js( __( 'Month-over-month growth percentage', 'skylearn-billing-pro' ) ); ?>',
				calculation_type: 'growth',
				simple_metric: 'total_revenue',
				unit: 'percentage'
			}
		},
		strings: {
			confirmDelete: '<?php echo esc_js( __( 'Are you sure you want to delete this KPI?', 'skylearn-billing-pro' ) ); ?>',
			saveSuccess: '<?php echo esc_js( __( 'KPI saved successfully!', 'skylearn-billing-pro' ) ); ?>',
			deleteSuccess: '<?php echo esc_js( __( 'KPI deleted successfully!', 'skylearn-billing-pro' ) ); ?>',
			errorOccurred: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'skylearn-billing-pro' ) ); ?>'
		}
	};
</script>

<script src="<?php echo esc_url( SLBP_PLUGIN_URL . 'admin/js/kpi-management.js' ); ?>"></script>