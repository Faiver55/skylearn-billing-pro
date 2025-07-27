/**
 * Analytics Dashboard JavaScript
 *
 * Handles all interactive functionality for the SkyLearn Billing Pro analytics dashboard.
 *
 * @package    SkyLearnBillingPro
 * @author     Skyian LLC
 * @version    1.0.0
 */

(function($) {
    'use strict';

    /**
     * Analytics Dashboard Object
     */
    const SLBPAnalytics = {
        
        // Chart instances
        revenueChart: null,
        subscriptionChart: null,
        
        // Cache for filters
        currentFilters: {
            date_range: 'last_30_days',
            start_date: '',
            end_date: '',
            grouping: 'daily'
        },

        /**
         * Initialize the analytics dashboard
         */
        init: function() {
            this.bindEvents();
            this.initializeCharts();
            this.loadDashboardData();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Date range filter change
            $('#slbp-date-range').on('change', this.handleDateRangeChange.bind(this));
            
            // Custom date range inputs
            $('#slbp-start-date, #slbp-end-date').on('change', this.handleCustomDateChange.bind(this));
            
            // Chart grouping change
            $('#slbp-chart-grouping').on('change', this.handleChartGroupingChange.bind(this));
            
            // Refresh button
            $('#slbp-refresh-analytics').on('click', this.refreshData.bind(this));
            
            // Export dropdown
            $('#slbp-export-btn').on('click', this.toggleExportMenu.bind(this));
            $('.slbp-export-menu a').on('click', this.handleExport.bind(this));
            
            // Quick reports
            $('.slbp-report-card button').on('click', this.handleQuickReport.bind(this));
            
            // Close export menu when clicking outside
            $(document).on('click', this.closeExportMenu.bind(this));
        },

        /**
         * Handle date range filter change
         */
        handleDateRangeChange: function(e) {
            const dateRange = $(e.target).val();
            this.currentFilters.date_range = dateRange;
            
            if (dateRange === 'custom') {
                $('.slbp-custom-date-range').show();
            } else {
                $('.slbp-custom-date-range').hide();
                this.loadDashboardData();
            }
        },

        /**
         * Handle custom date range change
         */
        handleCustomDateChange: function() {
            if (this.currentFilters.date_range === 'custom') {
                this.currentFilters.start_date = $('#slbp-start-date').val();
                this.currentFilters.end_date = $('#slbp-end-date').val();
                
                if (this.currentFilters.start_date && this.currentFilters.end_date) {
                    this.loadDashboardData();
                }
            }
        },

        /**
         * Handle chart grouping change
         */
        handleChartGroupingChange: function(e) {
            this.currentFilters.grouping = $(e.target).val();
            this.loadRevenueChart();
        },

        /**
         * Refresh all dashboard data
         */
        refreshData: function() {
            this.loadDashboardData();
        },

        /**
         * Toggle export dropdown menu
         */
        toggleExportMenu: function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.slbp-export-dropdown').toggleClass('active');
        },

        /**
         * Close export menu
         */
        closeExportMenu: function(e) {
            if (!$(e.target).closest('.slbp-export-dropdown').length) {
                $('.slbp-export-dropdown').removeClass('active');
            }
        },

        /**
         * Handle data export
         */
        handleExport: function(e) {
            e.preventDefault();
            const exportType = $(e.target).data('export');
            this.exportData(exportType);
            $('.slbp-export-dropdown').removeClass('active');
        },

        /**
         * Handle quick report button clicks
         */
        handleQuickReport: function(e) {
            const reportType = $(e.target).data('report');
            this.showQuickReport(reportType);
        },

        /**
         * Initialize Chart.js charts
         */
        initializeCharts: function() {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded. Please include Chart.js library.');
                return;
            }

            // Initialize revenue chart
            const revenueCtx = document.getElementById('slbp-revenue-chart');
            if (revenueCtx) {
                this.revenueChart = new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: []
                    },
                    options: this.getRevenueChartOptions()
                });
            }

            // Initialize subscription chart
            const subscriptionCtx = document.getElementById('slbp-subscription-chart');
            if (subscriptionCtx) {
                this.subscriptionChart = new Chart(subscriptionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Cancelled', 'Pending'],
                        datasets: [{
                            data: [0, 0, 0],
                            backgroundColor: [
                                '#36A2EB',
                                '#FF6384',
                                '#FFCE56'
                            ]
                        }]
                    },
                    options: this.getSubscriptionChartOptions()
                });
            }
        },

        /**
         * Get revenue chart options
         */
        getRevenueChartOptions: function() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            };
        },

        /**
         * Get subscription chart options
         */
        getSubscriptionChartOptions: function() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                return label + ': ' + value;
                            }
                        }
                    }
                }
            };
        },

        /**
         * Load all dashboard data
         */
        loadDashboardData: function() {
            this.loadMetrics();
            this.loadRevenueChart();
            this.loadSubscriptionChart();
        },

        /**
         * Load dashboard metrics
         */
        loadMetrics: function() {
            // Show loading spinners
            $('.slbp-metric-value').html('<span class="slbp-loading-spinner"></span>');
            $('.slbp-change-indicator').html('');

            $.ajax({
                url: slbpAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'slbp_get_dashboard_metrics',
                    nonce: slbpAnalytics.nonce,
                    ...this.currentFilters
                },
                success: function(response) {
                    if (response.success) {
                        this.updateMetricCards(response.data);
                    } else {
                        this.showError(slbpAnalytics.strings.loadingError);
                    }
                }.bind(this),
                error: function() {
                    this.showError(slbpAnalytics.strings.loadingError);
                }.bind(this)
            });
        },

        /**
         * Load revenue chart data
         */
        loadRevenueChart: function() {
            if (!this.revenueChart) return;

            $('.slbp-chart-loading').removeClass('hidden');

            $.ajax({
                url: slbpAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'slbp_get_revenue_chart',
                    nonce: slbpAnalytics.nonce,
                    ...this.currentFilters
                },
                success: function(response) {
                    if (response.success) {
                        this.updateRevenueChart(response.data);
                    } else {
                        this.showError(slbpAnalytics.strings.loadingError);
                    }
                    $('.slbp-chart-loading').addClass('hidden');
                }.bind(this),
                error: function() {
                    this.showError(slbpAnalytics.strings.loadingError);
                    $('.slbp-chart-loading').addClass('hidden');
                }.bind(this)
            });
        },

        /**
         * Load subscription chart data
         */
        loadSubscriptionChart: function() {
            if (!this.subscriptionChart) return;

            $.ajax({
                url: slbpAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'slbp_get_subscription_analytics',
                    nonce: slbpAnalytics.nonce,
                    ...this.currentFilters
                },
                success: function(response) {
                    if (response.success) {
                        this.updateSubscriptionChart(response.data);
                    }
                }.bind(this)
            });
        },

        /**
         * Update metric cards with new data
         */
        updateMetricCards: function(metrics) {
            Object.keys(metrics).forEach(key => {
                const metric = metrics[key];
                const $card = $('[data-metric="' + key + '"]');
                
                if (key === 'top_products') {
                    this.updateTopProductsList(metric);
                } else {
                    $card.html(metric.formatted);
                    
                    // Update change indicator
                    const $changeIndicator = $card.closest('.slbp-metric-card').find('.slbp-change-indicator');
                    $changeIndicator.text(metric.change);
                    $changeIndicator.removeClass('trend-up trend-down trend-neutral');
                    $changeIndicator.addClass('trend-' + metric.trend);
                }
            });
        },

        /**
         * Update top products list
         */
        updateTopProductsList: function(products) {
            const $container = $('.slbp-top-products-list');
            let html = '';

            if (products && products.length > 0) {
                products.forEach(product => {
                    html += `
                        <div class="slbp-product-item">
                            <div class="slbp-product-name">${product.product_name}</div>
                            <div class="slbp-product-stats">
                                <span>$${parseFloat(product.total_revenue).toLocaleString()}</span>
                                <span>${product.total_sales} sales</span>
                            </div>
                        </div>
                    `;
                });
            } else {
                html = '<p>No product data available for the selected period.</p>';
            }

            $container.html(html);
        },

        /**
         * Update revenue chart
         */
        updateRevenueChart: function(chartData) {
            if (!this.revenueChart) return;

            this.revenueChart.data.labels = chartData.labels;
            this.revenueChart.data.datasets = chartData.datasets;
            this.revenueChart.update();
        },

        /**
         * Update subscription chart
         */
        updateSubscriptionChart: function(data) {
            if (!this.subscriptionChart) return;

            const chartData = [
                data.active_subscriptions || 0,
                data.cancelled_subscriptions || 0,
                data.new_subscriptions || 0
            ];

            this.subscriptionChart.data.datasets[0].data = chartData;
            this.subscriptionChart.update();
        },

        /**
         * Export data
         */
        exportData: function(exportType) {
            // Show loading state
            const $btn = $('#slbp-export-btn');
            const originalText = $btn.text();
            $btn.text('Exporting...').prop('disabled', true);

            $.ajax({
                url: slbpAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'slbp_export_analytics',
                    nonce: slbpAnalytics.nonce,
                    export_type: exportType,
                    ...this.currentFilters
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = '';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        this.showSuccess(slbpAnalytics.strings.exportSuccess);
                    } else {
                        this.showError(response.data || slbpAnalytics.strings.exportError);
                    }
                }.bind(this),
                error: function() {
                    this.showError(slbpAnalytics.strings.exportError);
                }.bind(this),
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Show quick report
         */
        showQuickReport: function(reportType) {
            // Placeholder for quick report functionality
            alert('Quick report: ' + reportType + ' - Coming soon!');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            // Create error notice
            const $notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
            $('.slbp-analytics-dashboard').prepend($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            // Create success notice
            const $notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
            $('.slbp-analytics-dashboard').prepend($notice);
            
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
        // Only initialize on analytics page
        if ($('.slbp-analytics-dashboard').length > 0) {
            SLBPAnalytics.init();
        }
    });

})(jQuery);