/**
 * SkyLearn Billing Pro - Admin JavaScript
 * Interactive functionality for the admin interface
 */

(function($) {
    'use strict';

    /**
     * Main admin object
     */
    const SLBPAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initProductMappings();
            this.initConnectionTest();
            this.initFormValidation();
            this.initTooltips();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).ready(this.onDocumentReady.bind(this));
            $(window).on('load', this.onWindowLoad.bind(this));
        },

        /**
         * Document ready handler
         */
        onDocumentReady: function() {
            this.fadeInCards();
            this.initProgressBars();
        },

        /**
         * Window load handler
         */
        onWindowLoad: function() {
            this.animateStats();
        },

        /**
         * Initialize tab functionality
         */
        initTabs: function() {
            const $tabLinks = $('.slbp-tab-link');
            
            $tabLinks.on('click', function(e) {
                // Let the link navigate naturally for now
                // In future versions, we could implement AJAX tab switching
            });
        },

        /**
         * Initialize product mappings functionality
         */
        initProductMappings: function() {
            const self = this;
            
            // Add new mapping row
            $(document).on('click', '.slbp-add-mapping', function(e) {
                e.preventDefault();
                self.addMappingRow();
            });
            
            // Remove mapping row
            $(document).on('click', '.slbp-remove-mapping', function(e) {
                e.preventDefault();
                self.removeMappingRow($(this));
            });
            
            // Update mapping indices when rows change
            this.updateMappingIndices();
        },

        /**
         * Add a new product mapping row
         */
        addMappingRow: function() {
            const $container = $('.slbp-mappings-container');
            const $lastRow = $container.find('.slbp-mapping-row').last();
            const newIndex = $container.find('.slbp-mapping-row').length;
            
            // Clone the last row
            const $newRow = $lastRow.clone();
            
            // Clear input values
            $newRow.find('input').val('');
            $newRow.find('select').prop('selectedIndex', 0);
            
            // Update field names with new index
            $newRow.find('input, select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                    $field.attr('name', newName);
                }
            });
            
            // Add animation
            $newRow.hide().appendTo($container).fadeIn(300);
            
            // Update indices
            this.updateMappingIndices();
        },

        /**
         * Remove a product mapping row
         */
        removeMappingRow: function($button) {
            const $row = $button.closest('.slbp-mapping-row');
            const $container = $('.slbp-mappings-container');
            
            // Don't remove if it's the only row
            if ($container.find('.slbp-mapping-row').length <= 1) {
                this.showNotice('error', 'At least one mapping row is required.');
                return;
            }
            
            // Remove with animation
            $row.fadeOut(300, function() {
                $row.remove();
                SLBPAdmin.updateMappingIndices();
            });
        },

        /**
         * Update mapping row indices
         */
        updateMappingIndices: function() {
            $('.slbp-mapping-row').each(function(index) {
                $(this).find('input, select').each(function() {
                    const $field = $(this);
                    const name = $field.attr('name');
                    if (name) {
                        const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $field.attr('name', newName);
                    }
                });
            });
        },

        /**
         * Initialize connection testing
         */
        initConnectionTest: function() {
            const self = this;
            
            $(document).on('click', '.slbp-test-connection', function(e) {
                e.preventDefault();
                self.testConnection($(this));
            });
        },

        /**
         * Test payment gateway connection
         */
        testConnection: function($button) {
            const gateway = $button.data('gateway');
            const $result = $('.slbp-test-result');
            
            // Get form data
            const apiKey = $('input[name="slbp_payment_settings[lemon_squeezy_api_key]"]').val();
            const storeId = $('input[name="slbp_payment_settings[lemon_squeezy_store_id]"]').val();
            
            // Validate required fields
            if (!apiKey) {
                this.showTestResult('error', slbp_admin.strings.error + ' API key is required.');
                return;
            }
            
            if (gateway === 'lemon_squeezy' && !storeId) {
                this.showTestResult('error', slbp_admin.strings.error + ' Store ID is required.');
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true).text(slbp_admin.strings.test_connection);
            this.showTestResult('loading', slbp_admin.strings.test_connection);
            
            // AJAX request
            $.ajax({
                url: slbp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_test_connection',
                    nonce: slbp_admin.nonce,
                    gateway: gateway,
                    api_key: apiKey,
                    store_id: storeId
                },
                success: function(response) {
                    if (response.success) {
                        SLBPAdmin.showTestResult('success', response.data.message || slbp_admin.strings.connection_valid);
                    } else {
                        SLBPAdmin.showTestResult('error', response.data.message || slbp_admin.strings.connection_error);
                    }
                },
                error: function() {
                    SLBPAdmin.showTestResult('error', slbp_admin.strings.connection_error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },

        /**
         * Show connection test result
         */
        showTestResult: function(type, message) {
            const $result = $('.slbp-test-result');
            
            $result.removeClass('success error loading')
                   .addClass(type)
                   .text(message)
                   .fadeIn(300);
            
            if (type !== 'loading') {
                setTimeout(function() {
                    $result.fadeOut(300);
                }, 5000);
            }
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            const self = this;
            
            // Form submission handler
            $('.slbp-settings-form').on('submit', function(e) {
                if (!self.validateForm($(this))) {
                    e.preventDefault();
                    return false;
                }
                
                // Show saving indicator
                self.showSavingIndicator();
            });
            
            // Real-time validation
            $('.slbp-form-table input, .slbp-form-table select').on('blur', function() {
                self.validateField($(this));
            });
        },

        /**
         * Validate form before submission
         */
        validateForm: function($form) {
            let isValid = true;
            
            // Validate required fields
            $form.find('input[required], select[required]').each(function() {
                if (!this.value) {
                    SLBPAdmin.showFieldError($(this), 'This field is required.');
                    isValid = false;
                } else {
                    SLBPAdmin.clearFieldError($(this));
                }
            });
            
            // Validate email fields
            $form.find('input[type="email"]').each(function() {
                if (this.value && !SLBPAdmin.isValidEmail(this.value)) {
                    SLBPAdmin.showFieldError($(this), 'Please enter a valid email address.');
                    isValid = false;
                }
            });
            
            // Validate URL fields
            $form.find('input[type="url"]').each(function() {
                if (this.value && !SLBPAdmin.isValidUrl(this.value)) {
                    SLBPAdmin.showFieldError($(this), 'Please enter a valid URL.');
                    isValid = false;
                }
            });
            
            return isValid;
        },

        /**
         * Validate individual field
         */
        validateField: function($field) {
            const value = $field.val();
            const type = $field.attr('type');
            
            // Clear previous errors
            this.clearFieldError($field);
            
            // Required field validation
            if ($field.prop('required') && !value) {
                this.showFieldError($field, 'This field is required.');
                return false;
            }
            
            // Email validation
            if (type === 'email' && value && !this.isValidEmail(value)) {
                this.showFieldError($field, 'Please enter a valid email address.');
                return false;
            }
            
            // URL validation
            if (type === 'url' && value && !this.isValidUrl(value)) {
                this.showFieldError($field, 'Please enter a valid URL.');
                return false;
            }
            
            return true;
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            $field.addClass('error');
            
            // Remove existing error message
            $field.siblings('.field-error').remove();
            
            // Add error message
            $field.after('<div class="field-error" style="color: #ef4444; font-size: 0.875rem; margin-top: 4px;">' + message + '</div>');
        },

        /**
         * Clear field error
         */
        clearFieldError: function($field) {
            $field.removeClass('error');
            $field.siblings('.field-error').remove();
        },

        /**
         * Show saving indicator
         */
        showSavingIndicator: function() {
            const $button = $('.slbp-save-button');
            const originalText = $button.text();
            
            $button.prop('disabled', true)
                   .text(slbp_admin.strings.saving)
                   .addClass('slbp-loading');
            
            // Re-enable after form submission
            setTimeout(function() {
                $button.prop('disabled', false)
                       .text(originalText)
                       .removeClass('slbp-loading');
            }, 2000);
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Add tooltips to help icons
            $('[data-tooltip]').each(function() {
                const $element = $(this);
                const tooltip = $element.data('tooltip');
                
                $element.on('mouseenter', function() {
                    SLBPAdmin.showTooltip($element, tooltip);
                });
                
                $element.on('mouseleave', function() {
                    SLBPAdmin.hideTooltip();
                });
            });
        },

        /**
         * Show tooltip
         */
        showTooltip: function($element, text) {
            const $tooltip = $('<div class="slbp-tooltip">' + text + '</div>');
            const offset = $element.offset();
            
            $tooltip.css({
                position: 'absolute',
                top: offset.top - 35,
                left: offset.left,
                background: '#333',
                color: '#fff',
                padding: '8px 12px',
                borderRadius: '4px',
                fontSize: '12px',
                zIndex: 9999,
                whiteSpace: 'nowrap'
            });
            
            $('body').append($tooltip);
        },

        /**
         * Hide tooltip
         */
        hideTooltip: function() {
            $('.slbp-tooltip').remove();
        },

        /**
         * Fade in cards on page load
         */
        fadeInCards: function() {
            $('.slbp-card').each(function(index) {
                $(this).css({
                    opacity: 0,
                    transform: 'translateY(20px)'
                }).delay(index * 100).animate({
                    opacity: 1
                }, 300);
            });
        },

        /**
         * Initialize progress bars
         */
        initProgressBars: function() {
            $('.slbp-progress-fill').each(function() {
                const $bar = $(this);
                const width = $bar.css('width');
                
                $bar.css('width', '0').animate({
                    width: width
                }, 1000);
            });
        },

        /**
         * Animate stats on page load
         */
        animateStats: function() {
            $('.slbp-stat-value').each(function() {
                const $stat = $(this);
                const text = $stat.text();
                const number = parseFloat(text.replace(/[^0-9.]/g, ''));
                
                if (!isNaN(number)) {
                    $stat.text('0');
                    
                    $({ value: 0 }).animate({ value: number }, {
                        duration: 1500,
                        step: function() {
                            if (text.includes('$')) {
                                $stat.text('$' + this.value.toFixed(2));
                            } else {
                                $stat.text(Math.floor(this.value));
                            }
                        },
                        complete: function() {
                            $stat.text(text);
                        }
                    });
                }
            });
        },

        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap').prepend($notice);
            
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $notice.remove();
                });
            }, 5000);
        },

        /**
         * Validate email address
         */
        isValidEmail: function(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        /**
         * Validate URL
         */
        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    /**
     * Auto-save functionality
     */
    const AutoSave = {
        
        timer: null,
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Auto-save on form field changes
            $('.slbp-settings-form input, .slbp-settings-form select, .slbp-settings-form textarea').on('change input', 
                SLBPAdmin.debounce(this.saveData.bind(this), 2000)
            );
        },
        
        saveData: function() {
            const $form = $('.slbp-settings-form');
            const formData = $form.serialize();
            
            // Save to localStorage as backup
            localStorage.setItem('slbp_form_backup', formData);
            
            // Show auto-save indicator
            this.showAutoSaveIndicator();
        },
        
        showAutoSaveIndicator: function() {
            const $indicator = $('.slbp-autosave-indicator');
            
            if ($indicator.length === 0) {
                $('body').append('<div class="slbp-autosave-indicator">Draft saved</div>');
            }
            
            $('.slbp-autosave-indicator').fadeIn(200).delay(2000).fadeOut(200);
        },
        
        restoreData: function() {
            const savedData = localStorage.getItem('slbp_form_backup');
            
            if (savedData) {
                // Parse and restore form data if needed
                // This is a placeholder for future implementation
            }
        }
    };

    /**
     * Initialize everything when document is ready
     */
    $(document).ready(function() {
        SLBPAdmin.init();
        AutoSave.init();
    });

    // Make SLBPAdmin globally available
    window.SLBPAdmin = SLBPAdmin;

})(jQuery);