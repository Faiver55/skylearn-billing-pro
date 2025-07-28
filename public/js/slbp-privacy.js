/**
 * Privacy management JavaScript functionality
 */
(function($) {
    'use strict';

    var SLBP_Privacy = {
        init: function() {
            this.bindEvents();
            this.checkConsentStatus();
            this.initCookieBanner();
        },

        bindEvents: function() {
            // Cookie consent banner events
            $(document).on('click', '#slbp-accept-all-cookies', this.acceptAllCookies);
            $(document).on('click', '#slbp-reject-all-cookies', this.rejectAllCookies);
            $(document).on('click', '#slbp-manage-cookies', this.showCookiePreferences);
            $(document).on('click', '#slbp-save-cookie-preferences', this.saveCookiePreferences);

            // Privacy dashboard events
            $(document).on('submit', '#slbp-user-cookie-preferences', this.updateUserConsent);
            $(document).on('click', '#slbp-request-export', this.requestDataExport);
            $(document).on('click', '#slbp-request-deletion', this.requestDataDeletion);
            $(document).on('click', '#slbp-submit-data-request', this.submitDataRequest);

            // Modal events
            $(document).on('click', '.slbp-modal-close', this.closeModal);
            $(document).on('click', '.slbp-modal', function(e) {
                if (e.target === this) {
                    SLBP_Privacy.closeModal();
                }
            });
        },

        checkConsentStatus: function() {
            var consent = this.getUserConsent();
            
            // If no consent recorded, show banner after delay
            if (!consent.timestamp) {
                setTimeout(function() {
                    $('#slbp-cookie-consent').slideDown();
                }, 1000);
            } else {
                this.applyCookieConsent(consent);
            }
        },

        initCookieBanner: function() {
            // Set up cookie banner based on settings
            var settings = slbp_privacy.settings;
            
            if (settings.cookie_consent_style === 'modal') {
                $('#slbp-cookie-consent').addClass('slbp-cookie-modal');
            }
        },

        acceptAllCookies: function(e) {
            e.preventDefault();
            
            var consent = {};
            $.each(slbp_privacy.settings.consent_categories, function(category, config) {
                consent[category] = true;
            });
            
            SLBP_Privacy.saveConsent(consent);
        },

        rejectAllCookies: function(e) {
            e.preventDefault();
            
            var consent = {};
            $.each(slbp_privacy.settings.consent_categories, function(category, config) {
                consent[category] = config.required || false;
            });
            
            SLBP_Privacy.saveConsent(consent);
        },

        showCookiePreferences: function(e) {
            e.preventDefault();
            
            // Populate current preferences
            var consent = SLBP_Privacy.getUserConsent();
            $.each(consent, function(category, value) {
                $('input[name="consent[' + category + ']"]').prop('checked', value);
            });
            
            $('#slbp-cookie-preferences-modal').fadeIn();
        },

        saveCookiePreferences: function(e) {
            e.preventDefault();
            
            var consent = {};
            $('#slbp-cookie-preferences-form input[type="checkbox"]').each(function() {
                var name = $(this).attr('name');
                var category = name.match(/consent\[([^\]]+)\]/)[1];
                consent[category] = $(this).is(':checked');
            });
            
            SLBP_Privacy.saveConsent(consent);
            SLBP_Privacy.closeModal();
        },

        saveConsent: function(consent) {
            $.ajax({
                url: slbp_privacy.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_update_cookie_consent',
                    nonce: slbp_privacy.nonce,
                    consent: consent
                },
                success: function(response) {
                    if (response.success) {
                        $('#slbp-cookie-consent').slideUp();
                        SLBP_Privacy.applyCookieConsent(consent);
                        SLBP_Privacy.showNotification(response.data.message, 'success');
                    } else {
                        SLBP_Privacy.showNotification('Failed to save preferences', 'error');
                    }
                },
                error: function() {
                    SLBP_Privacy.showNotification('Failed to save preferences', 'error');
                }
            });
        },

        updateUserConsent: function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: slbp_privacy.ajax_url,
                type: 'POST',
                data: formData + '&action=slbp_update_cookie_consent',
                success: function(response) {
                    if (response.success) {
                        SLBP_Privacy.showNotification(response.data, 'success');
                    } else {
                        SLBP_Privacy.showNotification('Failed to update preferences', 'error');
                    }
                },
                error: function() {
                    SLBP_Privacy.showNotification('Failed to update preferences', 'error');
                }
            });
        },

        requestDataExport: function(e) {
            e.preventDefault();
            
            $('#slbp-data-request-title').text('Request Data Export');
            $('#slbp-request-type').val('export');
            $('#slbp-data-request-modal').fadeIn();
        },

        requestDataDeletion: function(e) {
            e.preventDefault();
            
            $('#slbp-data-request-title').text('Request Data Deletion');
            $('#slbp-request-type').val('deletion');
            $('#slbp-data-request-modal').fadeIn();
        },

        submitDataRequest: function(e) {
            e.preventDefault();
            
            var requestType = $('#slbp-request-type').val();
            var reason = $('#slbp-request-reason').val();
            
            // Show confirmation for deletion requests
            if (requestType === 'deletion') {
                if (!confirm('Are you sure you want to request deletion of your data? This action cannot be undone.')) {
                    return;
                }
            }
            
            $.ajax({
                url: slbp_privacy.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_request_data_' + requestType,
                    nonce: slbp_privacy.nonce,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        SLBP_Privacy.closeModal();
                        if (requestType === 'export' && response.data.download_url) {
                            SLBP_Privacy.showNotification('Data export ready. <a href="' + response.data.download_url + '" target="_blank">Download</a>', 'success');
                        } else {
                            SLBP_Privacy.showNotification('Request submitted successfully', 'success');
                        }
                    } else {
                        SLBP_Privacy.showNotification('Request failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    SLBP_Privacy.showNotification('Request failed', 'error');
                }
            });
        },

        closeModal: function() {
            $('.slbp-modal').fadeOut();
        },

        getUserConsent: function() {
            // Get consent from local storage or user data
            if (slbp_privacy.user_consent) {
                return slbp_privacy.user_consent;
            }
            
            var consent = localStorage.getItem('slbp_cookie_consent');
            return consent ? JSON.parse(consent) : {};
        },

        applyCookieConsent: function(consent) {
            // Apply consent settings to tracking scripts
            
            // Google Analytics
            if (consent.analytics) {
                this.enableGoogleAnalytics();
            } else {
                this.disableGoogleAnalytics();
            }
            
            // Marketing cookies
            if (consent.marketing) {
                this.enableMarketingCookies();
            } else {
                this.disableMarketingCookies();
            }
            
            // Store consent locally
            localStorage.setItem('slbp_cookie_consent', JSON.stringify(consent));
        },

        enableGoogleAnalytics: function() {
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
            }
        },

        disableGoogleAnalytics: function() {
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'analytics_storage': 'denied'
                });
            }
        },

        enableMarketingCookies: function() {
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'ad_storage': 'granted'
                });
            }
        },

        disableMarketingCookies: function() {
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'ad_storage': 'denied'
                });
            }
        },

        showNotification: function(message, type) {
            var notification = $('<div class="slbp-notification slbp-notification-' + type + '">' + message + '</div>');
            $('body').append(notification);
            
            notification.fadeIn().delay(3000).fadeOut(function() {
                $(this).remove();
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SLBP_Privacy.init();
    });

    // Make available globally
    window.SLBP_Privacy = SLBP_Privacy;

})(jQuery);