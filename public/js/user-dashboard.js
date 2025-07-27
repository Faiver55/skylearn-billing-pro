/**
 * SkyLearn Billing Pro - User Dashboard JavaScript
 */

(function($) {
    'use strict';

    /**
     * User Dashboard functionality
     */
    var SLBPUserDashboard = {
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.bindEvents();
            this.loadInitialData();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Tab switching (if using AJAX-based tabs)
            $(document).on('click', '.nav-tab[data-tab]', this.switchTab.bind(this));
            
            // Load data when tab becomes visible
            $(document).on('click', '.nav-tab', this.handleTabClick.bind(this));
            
            // Transaction filters
            $('#apply-transaction-filters').on('click', this.applyTransactionFilters.bind(this));
            
            // Subscription cancellation
            $(document).on('click', '.cancel-subscription', this.cancelSubscription.bind(this));
            
            // Invoice download
            $(document).on('click', '.download-invoice', this.downloadInvoice.bind(this));
            
            // Preferences form
            $('#preferences-form').on('submit', this.savePreferences.bind(this));
            
            // Refresh buttons
            $(document).on('click', '.refresh-data', this.refreshCurrentTab.bind(this));
        },

        /**
         * Load initial data based on current tab
         */
        loadInitialData: function() {
            var currentTab = this.getCurrentTab();
            this.loadTabData(currentTab);
        },

        /**
         * Get current active tab
         */
        getCurrentTab: function() {
            var $activeTab = $('.nav-tab-active');
            if ($activeTab.length && $activeTab.data('tab')) {
                return $activeTab.data('tab');
            }
            
            // Fallback to URL parameter or default
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tab') || 'overview';
        },

        /**
         * Handle tab click
         */
        handleTabClick: function(e) {
            var $tab = $(e.currentTarget);
            var tabName = $tab.attr('href');
            
            if (tabName && tabName.includes('tab=')) {
                var tab = new URLSearchParams(tabName.split('?')[1]).get('tab');
                if (tab) {
                    setTimeout(function() {
                        this.loadTabData(tab);
                    }.bind(this), 100);
                }
            }
        },

        /**
         * Switch tab (for AJAX-based tabs)
         */
        switchTab: function(e) {
            e.preventDefault();
            
            var $tab = $(e.currentTarget);
            var tabName = $tab.data('tab');
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            // Hide all tab content
            $('.tab-content > div').hide();
            
            // Show target tab content
            $('#' + tabName + '-tab').show();
            
            // Load tab data
            this.loadTabData(tabName);
            
            // Update URL
            var url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        },

        /**
         * Load data for specific tab
         */
        loadTabData: function(tabName) {
            switch (tabName) {
                case 'subscriptions':
                    this.loadSubscriptions();
                    break;
                case 'transactions':
                    this.loadTransactions();
                    break;
                case 'courses':
                    this.loadCourses();
                    break;
                case 'overview':
                    // Overview data is typically loaded on page load
                    break;
            }
        },

        /**
         * Load user subscriptions
         */
        loadSubscriptions: function() {
            var $container = $('#subscriptions-list');
            if (!$container.length) return;
            
            $container.html('<div class="loading-placeholder">' + slbp_dashboard.strings.loading + '</div>');
            
            $.ajax({
                url: slbp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_get_user_subscriptions',
                    nonce: slbp_dashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.renderSubscriptions(response.data);
                    } else {
                        this.showError($container, response.data || slbp_dashboard.strings.error);
                    }
                }.bind(this),
                error: function() {
                    this.showError($container, slbp_dashboard.strings.error);
                }.bind(this)
            });
        },

        /**
         * Render subscriptions list
         */
        renderSubscriptions: function(subscriptions) {
            var $container = $('#subscriptions-list');
            var html = '';
            
            if (subscriptions.length === 0) {
                html = '<div class="no-activity">No subscriptions found.</div>';
            } else {
                subscriptions.forEach(function(subscription) {
                    html += this.renderSubscriptionItem(subscription);
                }.bind(this));
            }
            
            $container.html(html);
        },

        /**
         * Render individual subscription item
         */
        renderSubscriptionItem: function(subscription) {
            var statusClass = 'status-' + subscription.status;
            var html = '';
            
            html += '<div class="subscription-item">';
            html += '<div class="item-header">';
            html += '<div class="item-title">Subscription #' + this.escapeHtml(subscription.subscription_id) + '</div>';
            html += '<div class="item-status ' + statusClass + '">' + this.escapeHtml(subscription.status) + '</div>';
            html += '</div>';
            
            html += '<div class="item-details">';
            html += '<div class="detail-item">';
            html += '<div class="detail-label">Plan</div>';
            html += '<div class="detail-value">' + this.escapeHtml(subscription.plan_id) + '</div>';
            html += '</div>';
            html += '<div class="detail-item">';
            html += '<div class="detail-label">Amount</div>';
            html += '<div class="detail-value">' + subscription.currency + ' ' + subscription.amount + '</div>';
            html += '</div>';
            html += '<div class="detail-item">';
            html += '<div class="detail-label">Billing Cycle</div>';
            html += '<div class="detail-value">' + this.escapeHtml(subscription.billing_cycle) + '</div>';
            html += '</div>';
            if (subscription.next_billing_date) {
                html += '<div class="detail-item">';
                html += '<div class="detail-label">Next Billing</div>';
                html += '<div class="detail-value">' + this.escapeHtml(subscription.next_billing_date) + '</div>';
                html += '</div>';
            }
            html += '</div>';
            
            html += '<div class="item-actions">';
            if (subscription.status === 'active') {
                html += '<button type="button" class="button button-small button-danger cancel-subscription" ';
                html += 'data-subscription-id="' + this.escapeHtml(subscription.subscription_id) + '">';
                html += 'Cancel Subscription</button>';
            }
            html += '</div>';
            
            html += '</div>';
            
            return html;
        },

        /**
         * Load user transactions
         */
        loadTransactions: function(filters) {
            var $container = $('#transactions-list');
            if (!$container.length) return;
            
            $container.html('<div class="loading-placeholder">' + slbp_dashboard.strings.loading + '</div>');
            
            var data = {
                action: 'slbp_get_user_transactions',
                nonce: slbp_dashboard.nonce
            };
            
            if (filters) {
                $.extend(data, filters);
            }
            
            $.ajax({
                url: slbp_dashboard.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        this.renderTransactions(response.data);
                    } else {
                        this.showError($container, response.data || slbp_dashboard.strings.error);
                    }
                }.bind(this),
                error: function() {
                    this.showError($container, slbp_dashboard.strings.error);
                }.bind(this)
            });
        },

        /**
         * Render transactions list
         */
        renderTransactions: function(transactions) {
            var $container = $('#transactions-list');
            var html = '';
            
            if (transactions.length === 0) {
                html = '<div class="no-activity">No transactions found.</div>';
            } else {
                transactions.forEach(function(transaction) {
                    html += this.renderTransactionItem(transaction);
                }.bind(this));
            }
            
            $container.html(html);
        },

        /**
         * Render individual transaction item
         */
        renderTransactionItem: function(transaction) {
            var statusClass = 'status-' + transaction.status;
            var html = '';
            
            html += '<div class="transaction-item">';
            html += '<div class="item-header">';
            html += '<div class="item-title">Transaction #' + this.escapeHtml(transaction.transaction_id) + '</div>';
            html += '<div class="item-status ' + statusClass + '">' + this.escapeHtml(transaction.status) + '</div>';
            html += '</div>';
            
            html += '<div class="item-details">';
            html += '<div class="detail-item">';
            html += '<div class="detail-label">Amount</div>';
            html += '<div class="detail-value">' + transaction.currency + ' ' + transaction.amount + '</div>';
            html += '</div>';
            html += '<div class="detail-item">';
            html += '<div class="detail-label">Payment Method</div>';
            html += '<div class="detail-value">' + this.escapeHtml(transaction.payment_gateway) + '</div>';
            html += '</div>';
            html += '<div class="detail-item">';
            html += '<div class="detail-label">Date</div>';
            html += '<div class="detail-value">' + this.escapeHtml(transaction.created_at) + '</div>';
            html += '</div>';
            html += '<div class="detail-item">';
            html += '<div class="detail-label">Order ID</div>';
            html += '<div class="detail-value">' + this.escapeHtml(transaction.order_id) + '</div>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="item-actions">';
            if (transaction.status === 'completed') {
                html += '<a href="' + transaction.download_url + '" class="button button-small download-invoice">';
                html += 'Download Receipt</a>';
            }
            html += '</div>';
            
            html += '</div>';
            
            return html;
        },

        /**
         * Load user courses
         */
        loadCourses: function() {
            var $container = $('#courses-list');
            if (!$container.length) return;
            
            $container.html('<div class="loading-placeholder">' + slbp_dashboard.strings.loading + '</div>');
            
            $.ajax({
                url: slbp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_get_user_enrollments',
                    nonce: slbp_dashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.renderCourses(response.data);
                    } else {
                        this.showError($container, response.data || slbp_dashboard.strings.error);
                    }
                }.bind(this),
                error: function() {
                    this.showError($container, slbp_dashboard.strings.error);
                }.bind(this)
            });
        },

        /**
         * Render courses list
         */
        renderCourses: function(courses) {
            var $container = $('#courses-list');
            var html = '';
            
            if (courses.length === 0) {
                html = '<div class="no-activity">No enrolled courses found.</div>';
            } else {
                courses.forEach(function(course) {
                    html += this.renderCourseItem(course);
                }.bind(this));
            }
            
            $container.html(html);
        },

        /**
         * Render individual course item
         */
        renderCourseItem: function(course) {
            var html = '';
            
            html += '<div class="course-item">';
            html += '<div class="item-header">';
            html += '<div class="item-title">' + this.escapeHtml(course.title) + '</div>';
            html += '<div class="progress-info">' + course.progress + '% Complete</div>';
            html += '</div>';
            
            html += '<div class="progress-bar">';
            html += '<div class="progress-fill" style="width: ' + course.progress + '%"></div>';
            html += '</div>';
            
            html += '<div class="item-actions">';
            html += '<a href="' + course.url + '" class="button button-primary button-small">Continue Learning</a>';
            html += '</div>';
            
            html += '</div>';
            
            return html;
        },

        /**
         * Apply transaction filters
         */
        applyTransactionFilters: function(e) {
            e.preventDefault();
            
            var filters = {
                status: $('#transaction-status-filter').val(),
                date_from: $('#transaction-date-from').val(),
                date_to: $('#transaction-date-to').val()
            };
            
            this.loadTransactions(filters);
        },

        /**
         * Cancel subscription
         */
        cancelSubscription: function(e) {
            e.preventDefault();
            
            if (!confirm(slbp_dashboard.strings.confirm_cancel)) {
                return;
            }
            
            var $button = $(e.currentTarget);
            var subscriptionId = $button.data('subscription-id');
            
            $button.prop('disabled', true).text('Cancelling...');
            
            $.ajax({
                url: slbp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_cancel_subscription',
                    nonce: slbp_dashboard.nonce,
                    subscription_id: subscriptionId
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotice('Subscription cancelled successfully.', 'success');
                        this.loadSubscriptions(); // Reload subscriptions
                    } else {
                        this.showNotice(response.data || 'Failed to cancel subscription.', 'error');
                        $button.prop('disabled', false).text('Cancel Subscription');
                    }
                }.bind(this),
                error: function() {
                    this.showNotice('An error occurred while cancelling subscription.', 'error');
                    $button.prop('disabled', false).text('Cancel Subscription');
                }.bind(this)
            });
        },

        /**
         * Download invoice
         */
        downloadInvoice: function(e) {
            // Let the browser handle the download naturally
            // We could add analytics tracking here if needed
        },

        /**
         * Save preferences
         */
        savePreferences: function(e) {
            e.preventDefault();
            
            var $form = $(e.currentTarget);
            var formData = $form.serializeArray();
            var preferences = {};
            
            // Parse form data
            formData.forEach(function(field) {
                var matches = field.name.match(/notifications\[([^\]]+)\]\[([^\]]+)\]/);
                if (matches) {
                    var type = matches[1];
                    var channel = matches[2];
                    
                    if (!preferences[type]) {
                        preferences[type] = {};
                    }
                    preferences[type][channel] = field.value === '1';
                }
            });
            
            $.ajax({
                url: slbp_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_update_notification_preferences',
                    nonce: slbp_dashboard.nonce,
                    preferences: preferences
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotice('Preferences saved successfully!', 'success');
                    } else {
                        this.showNotice(response.data || 'Failed to save preferences.', 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotice('An error occurred while saving preferences.', 'error');
                }.bind(this)
            });
        },

        /**
         * Refresh current tab data
         */
        refreshCurrentTab: function(e) {
            e.preventDefault();
            
            var currentTab = this.getCurrentTab();
            this.loadTabData(currentTab);
        },

        /**
         * Show error message
         */
        showError: function($container, message) {
            $container.html('<div class="no-activity">Error: ' + this.escapeHtml(message) + '</div>');
        },

        /**
         * Show notice message
         */
        showNotice: function(message, type) {
            var $notice = $('<div class="dashboard-notice notice-' + type + '">' + message + '</div>');
            
            // Insert notice at the top of dashboard content
            $('.dashboard-content').prepend($notice);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.slbp-user-dashboard').length) {
            SLBPUserDashboard.init();
        }
    });

})(jQuery);