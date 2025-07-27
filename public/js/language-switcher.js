/**
 * Language Switcher JavaScript
 *
 * Handles the frontend functionality for language and currency switching.
 *
 * @package SkyLearnBillingPro
 * @since   1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initLanguageSwitcher();
        initCurrencySwitcher();
        initAdminBarSwitcher();
    });

    /**
     * Initialize language switcher functionality
     */
    function initLanguageSwitcher() {
        // Handle language option clicks
        $(document).on('click', '.slbp-language-option', function(e) {
            e.preventDefault();
            
            var languageCode = $(this).data('language');
            if (!languageCode) return;
            
            switchLanguage(languageCode, $(this));
        });

        // Handle language dropdown changes
        $(document).on('change', '.slbp-language-selector', function() {
            var languageCode = $(this).val();
            if (!languageCode) return;
            
            switchLanguage(languageCode, $(this));
        });
    }

    /**
     * Initialize currency switcher functionality
     */
    function initCurrencySwitcher() {
        // Handle currency option clicks
        $(document).on('click', '.slbp-currency-option', function(e) {
            e.preventDefault();
            
            var currencyCode = $(this).data('currency');
            if (!currencyCode) return;
            
            switchCurrency(currencyCode, $(this));
        });

        // Handle currency dropdown changes
        $(document).on('change', '.slbp-currency-selector', function() {
            var currencyCode = $(this).val();
            if (!currencyCode) return;
            
            switchCurrency(currencyCode, $(this));
        });
    }

    /**
     * Initialize admin bar switcher functionality
     */
    function initAdminBarSwitcher() {
        // Handle admin bar language switching
        $(document).on('click', '#wp-admin-bar-slbp-language-switcher .slbp-language-option', function(e) {
            e.preventDefault();
            
            var languageCode = $(this).closest('li').attr('id').replace('wp-admin-bar-slbp-lang-', '');
            if (!languageCode) return;
            
            switchLanguage(languageCode, $(this));
        });

        // Handle admin bar currency switching
        $(document).on('click', '#wp-admin-bar-slbp-currency-switcher .slbp-currency-option', function(e) {
            e.preventDefault();
            
            var currencyCode = $(this).closest('li').attr('id').replace('wp-admin-bar-slbp-curr-', '');
            if (!currencyCode) return;
            
            switchCurrency(currencyCode, $(this));
        });
    }

    /**
     * Switch language via AJAX
     */
    function switchLanguage(languageCode, $element) {
        // Show loading state
        showLoadingState($element, 'language');
        
        $.ajax({
            url: slbp_switcher.ajax_url,
            type: 'POST',
            data: {
                action: 'slbp_switch_language',
                language: languageCode,
                nonce: slbp_switcher.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update active states
                    updateActiveStates('.slbp-language-option', languageCode, 'language');
                    updateActiveStates('.slbp-language-selector', languageCode, 'value');
                    
                    // Show success message
                    showMessage(response.data.message, 'success');
                    
                    // Reload page after a short delay to apply language changes
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('Failed to switch language. Please try again.', 'error');
            },
            complete: function() {
                hideLoadingState($element, 'language');
            }
        });
    }

    /**
     * Switch currency via AJAX
     */
    function switchCurrency(currencyCode, $element) {
        // Show loading state
        showLoadingState($element, 'currency');
        
        $.ajax({
            url: slbp_switcher.ajax_url,
            type: 'POST',
            data: {
                action: 'slbp_switch_currency',
                currency: currencyCode,
                nonce: slbp_switcher.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update active states
                    updateActiveStates('.slbp-currency-option', currencyCode, 'currency');
                    updateActiveStates('.slbp-currency-selector', currencyCode, 'value');
                    
                    // Show success message
                    showMessage(response.data.message, 'success');
                    
                    // Update prices on page if they exist
                    updatePricesDisplay();
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('Failed to switch currency. Please try again.', 'error');
            },
            complete: function() {
                hideLoadingState($element, 'currency');
            }
        });
    }

    /**
     * Show loading state on element
     */
    function showLoadingState($element, type) {
        if ($element.hasClass('slbp-' + type + '-option')) {
            $element.addClass('loading').append('<span class="slbp-loading-spinner"></span>');
        } else if ($element.hasClass('slbp-' + type + '-selector')) {
            $element.prop('disabled', true).closest('.slbp-' + type + '-switcher').addClass('loading');
        }
    }

    /**
     * Hide loading state on element
     */
    function hideLoadingState($element, type) {
        if ($element.hasClass('slbp-' + type + '-option')) {
            $element.removeClass('loading').find('.slbp-loading-spinner').remove();
        } else if ($element.hasClass('slbp-' + type + '-selector')) {
            $element.prop('disabled', false).closest('.slbp-' + type + '-switcher').removeClass('loading');
        }
    }

    /**
     * Update active states for switcher elements
     */
    function updateActiveStates(selector, newValue, dataAttribute) {
        $(selector).each(function() {
            var $this = $(this);
            var elementValue;
            
            if (dataAttribute === 'value') {
                elementValue = $this.val();
            } else {
                elementValue = $this.data(dataAttribute);
            }
            
            if (elementValue === newValue) {
                $this.addClass('active');
                if (dataAttribute === 'value') {
                    $this.prop('selected', true);
                }
            } else {
                $this.removeClass('active');
                if (dataAttribute === 'value') {
                    $this.prop('selected', false);
                }
            }
        });
    }

    /**
     * Update prices display after currency change
     */
    function updatePricesDisplay() {
        // Look for elements with price data and update them
        $('.slbp-price[data-amount]').each(function() {
            var $price = $(this);
            var amount = parseFloat($price.data('amount'));
            var fromCurrency = $price.data('currency') || 'USD';
            
            // This would typically make an AJAX call to get converted price
            // For now, we'll just trigger a refresh indication
            $price.addClass('price-updating');
            
            setTimeout(function() {
                $price.removeClass('price-updating');
            }, 1000);
        });
    }

    /**
     * Show message to user
     */
    function showMessage(message, type) {
        // Remove existing messages
        $('.slbp-switcher-message').remove();
        
        // Create and show new message
        var $message = $('<div class="slbp-switcher-message slbp-message-' + type + '">' + message + '</div>');
        
        // Try to find a good place to insert the message
        var $container = $('.slbp-language-switcher, .slbp-currency-switcher').first();
        if ($container.length) {
            $container.before($message);
        } else {
            $('body').append($message);
        }
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            $message.fadeOut(function() {
                $message.remove();
            });
        }, 3000);
    }

    /**
     * Shortcode and widget support
     */
    window.slbpRenderLanguageSwitcher = function(containerId, options) {
        var defaults = {
            showFlags: true,
            showNativeNames: true,
            dropdownStyle: 'inline',
            includeCurrency: false
        };
        
        options = $.extend(defaults, options || {});
        
        // This would make an AJAX call to get the switcher HTML
        // For now, just add a placeholder
        $('#' + containerId).html('<div class="slbp-language-switcher-placeholder">Language switcher loading...</div>');
    };

})(jQuery);