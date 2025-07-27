/**
 * SkyLearn Billing Pro - Setup Wizard JavaScript
 */

(function($) {
    'use strict';

    /**
     * Setup Wizard functionality
     */
    var SLBPSetupWizard = {
        
        /**
         * Initialize wizard
         */
        init: function() {
            this.bindEvents();
            this.initConditionalFields();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Save and continue
            $('.save-and-continue').on('click', this.saveAndContinue.bind(this));
            
            // Skip step
            $('.skip-step').on('click', this.skipStep.bind(this));
            
            // Finish setup
            $('.finish-setup').on('click', this.finishSetup.bind(this));
            
            // Test connection
            $('.test-connection').on('click', this.testConnection.bind(this));
            
            // Gateway selection
            $('input[name="gateway"]').on('change', this.toggleGatewaySettings.bind(this));
            
            // Integration toggles
            $('input[name$="_enabled"]').on('change', this.toggleIntegrationSettings.bind(this));
            
            // Form validation
            $('.wizard-form input, .wizard-form select').on('blur', this.validateField.bind(this));
        },

        /**
         * Initialize conditional fields
         */
        initConditionalFields: function() {
            // Show/hide gateway settings based on selection
            this.toggleGatewaySettings();
            
            // Show/hide integration settings based on enabled status
            this.toggleIntegrationSettings();
        },

        /**
         * Save and continue to next step
         */
        saveAndContinue: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var currentStep = $button.data('current-step');
            var nextStep = $button.data('next-step');
            
            if (!this.validateCurrentStep(currentStep)) {
                return;
            }
            
            var formData = this.getStepFormData(currentStep);
            
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: slbp_setup_wizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_setup_wizard_save_step',
                    nonce: slbp_setup_wizard.nonce,
                    step: currentStep,
                    data: formData
                },
                success: function(response) {
                    if (response.success) {
                        this.goToStep(nextStep);
                    } else {
                        this.showError(response.data || slbp_setup_wizard.strings.error);
                        this.setButtonLoading($button, false);
                    }
                }.bind(this),
                error: function() {
                    this.showError(slbp_setup_wizard.strings.error);
                    this.setButtonLoading($button, false);
                }.bind(this)
            });
        },

        /**
         * Skip current step
         */
        skipStep: function(e) {
            e.preventDefault();
            
            if (!confirm(slbp_setup_wizard.strings.confirm_skip)) {
                return;
            }
            
            var $button = $(e.currentTarget);
            var step = $button.data('step');
            
            $.ajax({
                url: slbp_setup_wizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_setup_wizard_skip_step',
                    nonce: slbp_setup_wizard.nonce,
                    step: step
                },
                success: function(response) {
                    if (response.success) {
                        // Move to next step
                        var nextStepUrl = this.getNextStepUrl();
                        if (nextStepUrl) {
                            window.location.href = nextStepUrl;
                        }
                    }
                }.bind(this)
            });
        },

        /**
         * Finish setup
         */
        finishSetup: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: slbp_setup_wizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_setup_wizard_save_step',
                    nonce: slbp_setup_wizard.nonce,
                    step: 'complete',
                    data: {}
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to dashboard
                        window.location.href = this.getDashboardUrl();
                    } else {
                        this.showError(response.data || slbp_setup_wizard.strings.error);
                        this.setButtonLoading($button, false);
                    }
                }.bind(this),
                error: function() {
                    this.showError(slbp_setup_wizard.strings.error);
                    this.setButtonLoading($button, false);
                }.bind(this)
            });
        },

        /**
         * Test connection for payment gateway
         */
        testConnection: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var gateway = $button.data('gateway');
            var $statusElement = $button.siblings('.connection-status');
            
            // Get form data for testing
            var formData = this.getStepFormData('payment_gateway');
            
            if (!formData.lemon_squeezy_api_key) {
                $statusElement.removeClass('success').addClass('error').text('API key is required');
                return;
            }
            
            this.setButtonLoading($button, true);
            $statusElement.removeClass('success error').text('');
            
            $.ajax({
                url: slbp_setup_wizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_setup_wizard_test_connection',
                    nonce: slbp_setup_wizard.nonce,
                    gateway: gateway,
                    settings: {
                        api_key: formData.lemon_squeezy_api_key
                    }
                },
                success: function(response) {
                    this.setButtonLoading($button, false);
                    
                    if (response.success) {
                        $statusElement.addClass('success').text(slbp_setup_wizard.strings.success);
                    } else {
                        $statusElement.addClass('error').text(response.data || 'Connection failed');
                    }
                }.bind(this),
                error: function() {
                    this.setButtonLoading($button, false);
                    $statusElement.addClass('error').text('Connection test failed');
                }.bind(this)
            });
        },

        /**
         * Toggle gateway settings visibility
         */
        toggleGatewaySettings: function() {
            var selectedGateway = $('input[name="gateway"]:checked').val();
            
            // Hide all gateway settings
            $('.gateway-settings').hide();
            
            // Show settings for selected gateway
            if (selectedGateway) {
                $('.' + selectedGateway + '-settings').show();
            }
        },

        /**
         * Toggle integration settings visibility
         */
        toggleIntegrationSettings: function() {
            $('input[name$="_enabled"]').each(function() {
                var $checkbox = $(this);
                var integrationName = $checkbox.attr('name').replace('_enabled', '');
                var $settings = $('.' + integrationName + '-settings');
                
                if ($checkbox.is(':checked')) {
                    $settings.show();
                } else {
                    $settings.hide();
                }
            });
        },

        /**
         * Validate current step
         */
        validateCurrentStep: function(step) {
            var isValid = true;
            var $form = $('#' + step.replace('_', '-') + '-form');
            
            if (!$form.length) {
                return true; // No form to validate
            }
            
            // Clear previous validation errors
            $form.find('.field-error').remove();
            $form.find('.error').removeClass('error');
            
            // Validate required fields
            $form.find('input[required], select[required]').each(function() {
                var $field = $(this);
                
                if (!$field.val().trim()) {
                    this.showFieldError($field, 'This field is required');
                    isValid = false;
                }
            }.bind(this));
            
            // Custom validation for specific steps
            switch (step) {
                case 'payment_gateway':
                    isValid = this.validatePaymentGateway($form) && isValid;
                    break;
            }
            
            return isValid;
        },

        /**
         * Validate payment gateway step
         */
        validatePaymentGateway: function($form) {
            var selectedGateway = $form.find('input[name="gateway"]:checked').val();
            
            if (!selectedGateway) {
                this.showError('Please select a payment gateway');
                return false;
            }
            
            if (selectedGateway === 'lemon_squeezy') {
                var apiKey = $form.find('#lemon_squeezy_api_key').val();
                var storeId = $form.find('#lemon_squeezy_store_id').val();
                
                if (!apiKey) {
                    this.showFieldError($form.find('#lemon_squeezy_api_key'), 'API key is required');
                    return false;
                }
                
                if (!storeId) {
                    this.showFieldError($form.find('#lemon_squeezy_store_id'), 'Store ID is required');
                    return false;
                }
            }
            
            return true;
        },

        /**
         * Validate individual field
         */
        validateField: function(e) {
            var $field = $(e.target);
            var value = $field.val().trim();
            
            // Remove existing error
            $field.siblings('.field-error').remove();
            $field.removeClass('error');
            
            // Required field validation
            if ($field.attr('required') && !value) {
                this.showFieldError($field, 'This field is required');
                return false;
            }
            
            // Email validation
            if ($field.attr('type') === 'email' && value) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    this.showFieldError($field, 'Please enter a valid email address');
                    return false;
                }
            }
            
            // URL validation
            if ($field.attr('type') === 'url' && value) {
                try {
                    new URL(value);
                } catch {
                    this.showFieldError($field, 'Please enter a valid URL');
                    return false;
                }
            }
            
            return true;
        },

        /**
         * Get form data for a step
         */
        getStepFormData: function(step) {
            var $form = $('#' + step.replace('_', '-') + '-form');
            var data = {};
            
            if ($form.length) {
                $form.serializeArray().forEach(function(field) {
                    data[field.name] = field.value;
                });
                
                // Handle checkboxes
                $form.find('input[type="checkbox"]').each(function() {
                    var $checkbox = $(this);
                    data[$checkbox.attr('name')] = $checkbox.is(':checked') ? '1' : '';
                });
            }
            
            return data;
        },

        /**
         * Go to specific step
         */
        goToStep: function(step) {
            var url = new URL(window.location);
            url.searchParams.set('step', step);
            window.location.href = url.toString();
        },

        /**
         * Get next step URL
         */
        getNextStepUrl: function() {
            var stepOrder = ['welcome', 'payment_gateway', 'lms_integration', 'notifications', 'integrations', 'complete'];
            var currentStep = this.getCurrentStep();
            var currentIndex = stepOrder.indexOf(currentStep);
            
            if (currentIndex >= 0 && currentIndex < stepOrder.length - 1) {
                var nextStep = stepOrder[currentIndex + 1];
                var url = new URL(window.location);
                url.searchParams.set('step', nextStep);
                return url.toString();
            }
            
            return null;
        },

        /**
         * Get current step from URL
         */
        getCurrentStep: function() {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('step') || 'welcome';
        },

        /**
         * Get dashboard URL
         */
        getDashboardUrl: function() {
            return window.location.origin + '/wp-admin/admin.php?page=skylearn-billing-pro';
        },

        /**
         * Set button loading state
         */
        setButtonLoading: function($button, loading) {
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
                $button.data('original-text', $button.text());
                $button.text(slbp_setup_wizard.strings.saving);
            } else {
                $button.removeClass('loading').prop('disabled', false);
                var originalText = $button.data('original-text');
                if (originalText) {
                    $button.text(originalText);
                }
            }
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            $field.addClass('error');
            
            var $error = $('<div class="field-error" style="color: #dc3232; font-size: 12px; margin-top: 5px;">' + 
                          this.escapeHtml(message) + '</div>');
            
            $field.after($error);
        },

        /**
         * Show general error message
         */
        showError: function(message) {
            var $error = $('<div class="notice notice-error" style="margin: 20px 0;"><p>' + 
                          this.escapeHtml(message) + '</p></div>');
            
            $('.wizard-content').prepend($error);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $error.fadeOut(function() {
                    $error.remove();
                });
            }, 5000);
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            var $success = $('<div class="notice notice-success" style="margin: 20px 0;"><p>' + 
                            this.escapeHtml(message) + '</p></div>');
            
            $('.wizard-content').prepend($success);
            
            // Auto-remove after 3 seconds
            setTimeout(function() {
                $success.fadeOut(function() {
                    $success.remove();
                });
            }, 3000);
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
        if ($('.slbp-setup-wizard').length) {
            SLBPSetupWizard.init();
        }
    });

    // Handle setup notice dismiss
    $(document).on('click', '.slbp-dismiss-setup-notice', function() {
        $.post(ajaxurl, {
            action: 'slbp_dismiss_setup_notice',
            nonce: $(this).data('nonce')
        });
        $('.slbp-setup-notice').fadeOut();
    });

})(jQuery);