/**
 * KPI Management JavaScript
 *
 * Handles the interactive functionality for custom KPI management.
 *
 * @package    SkyLearnBillingPro
 * @author     Skyian LLC
 * @version    1.0.0
 */

(function($) {
    'use strict';

    /**
     * KPI Management Object
     */
    var SLBPKPIManager = {
        
        // Chart instance for KPI charts
        kpiChart: null,

        /**
         * Initialize the KPI management interface
         */
        init: function() {
            this.bindEvents();
            this.initFormValidation();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Modal controls
            $('#slbp-add-kpi-btn, #slbp-create-first-kpi').on('click', function(e) {
                e.preventDefault();
                self.showKPIModal();
            });
            
            $('.slbp-modal-close, #slbp-cancel-kpi').on('click', function(e) {
                e.preventDefault();
                self.hideModals();
            });
            
            // Click outside modal to close
            $('.slbp-modal').on('click', function(e) {
                if (e.target === this) {
                    self.hideModals();
                }
            });
            
            // KPI actions
            $('.slbp-edit-kpi').on('click', function(e) {
                e.preventDefault();
                var kpiId = $(this).data('kpi-id');
                self.editKPI(kpiId);
            });
            
            $('.slbp-delete-kpi').on('click', function(e) {
                e.preventDefault();
                var kpiId = $(this).data('kpi-id');
                self.deleteKPI(kpiId);
            });
            
            $('.slbp-view-kpi-chart').on('click', function(e) {
                e.preventDefault();
                var kpiId = $(this).data('kpi-id');
                self.showKPIChart(kpiId);
            });
            
            // Template usage
            $('.slbp-use-template').on('click', function(e) {
                e.preventDefault();
                var templateId = $(this).data('template');
                self.useTemplate(templateId);
            });
            
            // Form submission
            $('#slbp-kpi-form').on('submit', function(e) {
                e.preventDefault();
                self.saveKPI();
            });
            
            // Calculation type change
            $('#slbp-kpi-calculation-type').on('change', function() {
                self.toggleCalculationFields($(this).val());
            });
            
            // Chart timeframe change
            $('#slbp-chart-timeframe').on('change', function() {
                if (self.currentKPIId) {
                    self.loadChartData(self.currentKPIId, $(this).val());
                }
            });
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            // Add real-time validation if needed
        },

        /**
         * Show KPI modal for adding new KPI
         */
        showKPIModal: function(kpiData) {
            $('#slbp-modal-title').text(kpiData ? 'Edit KPI' : 'Add New KPI');
            
            if (kpiData) {
                this.populateForm(kpiData);
            } else {
                this.clearForm();
            }
            
            $('#slbp-kpi-modal').show();
        },

        /**
         * Hide all modals
         */
        hideModals: function() {
            $('.slbp-modal').hide();
            this.clearForm();
        },

        /**
         * Clear the KPI form
         */
        clearForm: function() {
            $('#slbp-kpi-form')[0].reset();
            $('#slbp-kpi-id').val('');
            this.toggleCalculationFields('');
        },

        /**
         * Populate form with KPI data
         */
        populateForm: function(kpiData) {
            $('#slbp-kpi-id').val(kpiData.id);
            $('#slbp-kpi-name').val(kpiData.name);
            $('#slbp-kpi-description').val(kpiData.description);
            $('#slbp-kpi-calculation-type').val(kpiData.calculation.type);
            $('#slbp-kpi-unit').val(kpiData.unit);
            $('#slbp-kpi-active').prop('checked', kpiData.active);
            
            this.toggleCalculationFields(kpiData.calculation.type);
            
            // Populate calculation-specific fields
            switch (kpiData.calculation.type) {
                case 'simple':
                case 'growth':
                case 'average':
                    $('#slbp-simple-metric').val(kpiData.calculation.metric);
                    break;
                case 'ratio':
                    $('#slbp-ratio-numerator').val(kpiData.calculation.numerator);
                    $('#slbp-ratio-denominator').val(kpiData.calculation.denominator);
                    break;
                case 'custom':
                    $('#slbp-custom-formula').val(kpiData.calculation.formula);
                    break;
            }
            
            // Populate thresholds
            if (kpiData.threshold) {
                $('#slbp-threshold-warning').val(kpiData.threshold.warning);
                $('#slbp-threshold-critical').val(kpiData.threshold.critical);
            }
        },

        /**
         * Toggle calculation fields based on type
         */
        toggleCalculationFields: function(calculationType) {
            // Hide all calculation groups
            $('#slbp-simple-metric-group, #slbp-ratio-metrics-group, #slbp-custom-formula-group').hide();
            
            // Show relevant group
            switch (calculationType) {
                case 'simple':
                case 'growth':
                case 'average':
                    $('#slbp-simple-metric-group').show();
                    break;
                case 'ratio':
                    $('#slbp-ratio-metrics-group').show();
                    break;
                case 'custom':
                    $('#slbp-custom-formula-group').show();
                    break;
            }
        },

        /**
         * Use a KPI template
         */
        useTemplate: function(templateId) {
            if (slbpKPI.templates[templateId]) {
                var template = slbpKPI.templates[templateId];
                this.showKPIModal(template);
            }
        },

        /**
         * Save KPI
         */
        saveKPI: function() {
            var self = this;
            var formData = this.getFormData();
            
            if (!this.validateForm(formData)) {
                return;
            }
            
            $('#slbp-save-kpi').prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: slbpKPI.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'slbp_save_kpi',
                    nonce: slbpKPI.nonce,
                    kpi_data: formData
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(slbpKPI.strings.saveSuccess, 'success');
                        self.hideModals();
                        location.reload(); // Reload to show updated KPI list
                    } else {
                        self.showNotice(response.data || slbpKPI.strings.errorOccurred, 'error');
                    }
                },
                error: function() {
                    self.showNotice(slbpKPI.strings.errorOccurred, 'error');
                },
                complete: function() {
                    $('#slbp-save-kpi').prop('disabled', false).text('Save KPI');
                }
            });
        },

        /**
         * Get form data
         */
        getFormData: function() {
            return {
                kpi_id: $('#slbp-kpi-id').val(),
                name: $('#slbp-kpi-name').val(),
                description: $('#slbp-kpi-description').val(),
                calculation_type: $('#slbp-kpi-calculation-type').val(),
                simple_metric: $('#slbp-simple-metric').val(),
                ratio_numerator: $('#slbp-ratio-numerator').val(),
                ratio_denominator: $('#slbp-ratio-denominator').val(),
                custom_formula: $('#slbp-custom-formula').val(),
                unit: $('#slbp-kpi-unit').val(),
                threshold_warning: $('#slbp-threshold-warning').val(),
                threshold_critical: $('#slbp-threshold-critical').val(),
                active: $('#slbp-kpi-active').is(':checked')
            };
        },

        /**
         * Validate form data
         */
        validateForm: function(formData) {
            if (!formData.name.trim()) {
                this.showNotice('KPI name is required.', 'error');
                return false;
            }
            
            if (!formData.calculation_type) {
                this.showNotice('Calculation type is required.', 'error');
                return false;
            }
            
            // Validate calculation-specific fields
            switch (formData.calculation_type) {
                case 'simple':
                case 'growth':
                case 'average':
                    if (!formData.simple_metric) {
                        this.showNotice('Please select a metric.', 'error');
                        return false;
                    }
                    break;
                case 'ratio':
                    if (!formData.ratio_numerator || !formData.ratio_denominator) {
                        this.showNotice('Both numerator and denominator are required for ratio calculations.', 'error');
                        return false;
                    }
                    break;
                case 'custom':
                    if (!formData.custom_formula.trim()) {
                        this.showNotice('Custom formula is required.', 'error');
                        return false;
                    }
                    break;
            }
            
            return true;
        },

        /**
         * Edit KPI
         */
        editKPI: function(kpiId) {
            // In a real implementation, you'd fetch KPI data via AJAX
            // For now, we'll show an empty form
            this.showKPIModal();
        },

        /**
         * Delete KPI
         */
        deleteKPI: function(kpiId) {
            var self = this;
            
            if (!confirm(slbpKPI.strings.confirmDelete)) {
                return;
            }
            
            $.ajax({
                url: slbpKPI.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'slbp_delete_kpi',
                    nonce: slbpKPI.nonce,
                    kpi_id: kpiId
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice(slbpKPI.strings.deleteSuccess, 'success');
                        $('tr[data-kpi-id="' + kpiId + '"]').fadeOut();
                    } else {
                        self.showNotice(response.data || slbpKPI.strings.errorOccurred, 'error');
                    }
                },
                error: function() {
                    self.showNotice(slbpKPI.strings.errorOccurred, 'error');
                }
            });
        },

        /**
         * Show KPI chart
         */
        showKPIChart: function(kpiId) {
            this.currentKPIId = kpiId;
            $('#slbp-chart-modal').show();
            this.loadChartData(kpiId, $('#slbp-chart-timeframe').val());
        },

        /**
         * Load chart data for KPI
         */
        loadChartData: function(kpiId, timeframe) {
            var self = this;
            
            $.ajax({
                url: slbpKPI.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'slbp_get_kpi_chart_data',
                    nonce: slbpKPI.nonce,
                    kpi_id: kpiId,
                    timeframe: timeframe
                },
                success: function(response) {
                    if (response.success) {
                        self.renderChart(response.data);
                    }
                }
            });
        },

        /**
         * Render chart with data
         */
        renderChart: function(chartData) {
            if (!Chart) {
                console.error('Chart.js not loaded');
                return;
            }
            
            var ctx = document.getElementById('slbp-kpi-chart');
            
            if (this.kpiChart) {
                this.kpiChart.destroy();
            }
            
            this.kpiChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        /**
         * Show notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.slbp-kpi-management').prepend($notice);
            
            // Auto-dismiss after 3 seconds
            setTimeout(function() {
                $notice.fadeOut();
            }, 3000);
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        if ($('.slbp-kpi-management').length > 0) {
            SLBPKPIManager.init();
        }
    });

})(jQuery);