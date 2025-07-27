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
            this.initMobileSupport();
            this.initPWA();
            this.initAccessibility();
            this.initTouchSupport();
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

            // Initialize webhook URL copying
            $(document).on('click', '.slbp-copy-webhook-url', function(e) {
                e.preventDefault();
                self.copyWebhookUrl($(this));
            });
        },

        /**
         * Copy webhook URL to clipboard
         */
        copyWebhookUrl: function($button) {
            const url = $button.data('url');
            
            // Create temporary input element
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(url).select();
            
            try {
                document.execCommand('copy');
                $button.text('Copied!').addClass('copied');
                
                setTimeout(function() {
                    $button.text('Copy URL').removeClass('copied');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy webhook URL:', err);
                alert('Failed to copy URL. Please copy it manually: ' + url);
            }
            
            $temp.remove();
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
        },

        /**
         * Initialize mobile-specific functionality
         */
        initMobileSupport: function() {
            this.initMobileNavigation();
            this.initTouchGestures();
            this.initViewportHandling();
            this.initOrientationHandling();
        },

        /**
         * Initialize mobile navigation
         */
        initMobileNavigation: function() {
            // Create mobile navigation toggle button
            if (!$('.slbp-mobile-nav-toggle').length && $(window).width() <= 768) {
                $('body').append(`
                    <button class="slbp-mobile-nav-toggle" aria-label="Toggle navigation menu">
                        <span class="slbp-sr-only">Menu</span>
                        ☰
                    </button>
                    <div class="slbp-mobile-overlay"></div>
                `);
            }

            // Handle mobile navigation toggle
            $(document).on('click', '.slbp-mobile-nav-toggle', this.toggleMobileNav.bind(this));
            $(document).on('click', '.slbp-mobile-overlay', this.closeMobileNav.bind(this));

            // Close mobile nav on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    SLBPAdmin.closeMobileNav();
                }
            });

            // Handle window resize
            $(window).on('resize', this.debounce(this.handleResize.bind(this), 250));
        },

        /**
         * Toggle mobile navigation
         */
        toggleMobileNav: function() {
            const $nav = $('.slbp-tab-nav');
            const $overlay = $('.slbp-mobile-overlay');
            const $toggle = $('.slbp-mobile-nav-toggle');

            if ($nav.hasClass('mobile-open')) {
                this.closeMobileNav();
            } else {
                $nav.addClass('mobile-open');
                $overlay.addClass('active');
                $toggle.addClass('active').attr('aria-expanded', 'true');
                
                // Focus first nav item for accessibility
                $nav.find('.slbp-tab-link').first().focus();
                
                // Prevent body scroll
                $('body').addClass('no-scroll');
            }
        },

        /**
         * Close mobile navigation
         */
        closeMobileNav: function() {
            $('.slbp-tab-nav').removeClass('mobile-open');
            $('.slbp-mobile-overlay').removeClass('active');
            $('.slbp-mobile-nav-toggle').removeClass('active').attr('aria-expanded', 'false');
            $('body').removeClass('no-scroll');
        },

        /**
         * Handle window resize
         */
        handleResize: function() {
            const windowWidth = $(window).width();
            
            if (windowWidth > 768) {
                this.closeMobileNav();
                $('.slbp-mobile-nav-toggle, .slbp-mobile-overlay').remove();
            } else if (!$('.slbp-mobile-nav-toggle').length) {
                $('body').append(`
                    <button class="slbp-mobile-nav-toggle" aria-label="Toggle navigation menu">
                        <span class="slbp-sr-only">Menu</span>
                        ☰
                    </button>
                    <div class="slbp-mobile-overlay"></div>
                `);
            }
        },

        /**
         * Initialize touch gesture support
         */
        initTouchGestures: function() {
            let startX, startY, startTime;

            // Swipe detection for mobile navigation
            $(document).on('touchstart', '.slbp-tab-nav.mobile-open', function(e) {
                const touch = e.originalEvent.touches[0];
                startX = touch.clientX;
                startY = touch.clientY;
                startTime = Date.now();
            });

            $(document).on('touchend', '.slbp-tab-nav.mobile-open', function(e) {
                if (!startX || !startY) return;

                const touch = e.originalEvent.changedTouches[0];
                const diffX = startX - touch.clientX;
                const diffY = startY - touch.clientY;
                const diffTime = Date.now() - startTime;

                // Check if it's a swipe gesture (horizontal movement > vertical, fast enough)
                if (Math.abs(diffX) > Math.abs(diffY) && diffTime < 300 && Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        // Swipe left - close navigation
                        SLBPAdmin.closeMobileNav();
                    }
                }

                startX = startY = startTime = null;
            });
        },

        /**
         * Initialize viewport handling for mobile devices
         */
        initViewportHandling: function() {
            // Add viewport meta tag if not present
            if (!$('meta[name="viewport"]').length) {
                $('head').append('<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">');
            }

            // Handle iOS viewport issues
            if (this.isIOS()) {
                this.handleIOSViewport();
            }
        },

        /**
         * Handle iOS-specific viewport issues
         */
        handleIOSViewport: function() {
            // Fix iOS viewport zoom on input focus
            $(document).on('focus', 'input, select, textarea', function() {
                const viewport = $('meta[name="viewport"]');
                viewport.attr('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
            });

            $(document).on('blur', 'input, select, textarea', function() {
                const viewport = $('meta[name="viewport"]');
                viewport.attr('content', 'width=device-width, initial-scale=1.0, user-scalable=yes');
            });
        },

        /**
         * Initialize orientation change handling
         */
        initOrientationHandling: function() {
            $(window).on('orientationchange', this.debounce(function() {
                // Close mobile navigation on orientation change
                SLBPAdmin.closeMobileNav();
                
                // Trigger resize after orientation change
                setTimeout(function() {
                    $(window).trigger('resize');
                }, 500);
            }, 100));
        },

        /**
         * Initialize PWA functionality
         */
        initPWA: function() {
            this.registerServiceWorker();
            this.initInstallPrompt();
            this.initOfflineHandling();
            this.initPushNotifications();
        },

        /**
         * Register service worker for PWA functionality
         */
        registerServiceWorker: function() {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/wp-content/plugins/skylearn-billing-pro/public/sw.js')
                        .then(function(registration) {
                            console.log('SkyLearn Billing Pro Service Worker registered:', registration.scope);
                            
                            // Handle service worker updates
                            registration.addEventListener('updatefound', function() {
                                const newWorker = registration.installing;
                                newWorker.addEventListener('statechange', function() {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        SLBPAdmin.showUpdateAvailable();
                                    }
                                });
                            });
                        })
                        .catch(function(error) {
                            console.log('Service Worker registration failed:', error);
                        });
                });
            }
        },

        /**
         * Initialize install prompt for PWA
         */
        initInstallPrompt: function() {
            let deferredPrompt;

            window.addEventListener('beforeinstallprompt', function(e) {
                e.preventDefault();
                deferredPrompt = e;
                SLBPAdmin.showInstallButton();
            });

            $(document).on('click', '.slbp-install-app', function() {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(function(choiceResult) {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                        }
                        deferredPrompt = null;
                        $('.slbp-install-app').remove();
                    });
                }
            });
        },

        /**
         * Show install button for PWA
         */
        showInstallButton: function() {
            if (!$('.slbp-install-app').length) {
                $('.slbp-quick-actions').prepend(`
                    <button class="slbp-install-app slbp-action-button">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7,10 12,15 17,10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Install App
                    </button>
                `);
            }
        },

        /**
         * Show update available notification
         */
        showUpdateAvailable: function() {
            const $notification = $(`
                <div class="slbp-update-notification">
                    <p>A new version is available!</p>
                    <button class="slbp-update-now button button-primary">Update Now</button>
                    <button class="slbp-update-later button">Later</button>
                </div>
            `);

            $('body').append($notification);

            $(document).on('click', '.slbp-update-now', function() {
                window.location.reload();
            });

            $(document).on('click', '.slbp-update-later', function() {
                $notification.remove();
            });
        },

        /**
         * Initialize offline handling
         */
        initOfflineHandling: function() {
            // Monitor online/offline status
            window.addEventListener('online', this.handleOnline.bind(this));
            window.addEventListener('offline', this.handleOffline.bind(this));

            // Check initial state
            if (!navigator.onLine) {
                this.handleOffline();
            }
        },

        /**
         * Handle online state
         */
        handleOnline: function() {
            $('.slbp-offline-indicator').remove();
            this.syncPendingData();
        },

        /**
         * Handle offline state
         */
        handleOffline: function() {
            if (!$('.slbp-offline-indicator').length) {
                $('body').append(`
                    <div class="slbp-offline-indicator">
                        <span>📡 You're offline. Some features may not be available.</span>
                        <button class="slbp-offline-close">×</button>
                    </div>
                `);
            }

            $(document).on('click', '.slbp-offline-close', function() {
                $('.slbp-offline-indicator').remove();
            });
        },

        /**
         * Sync pending data when back online
         */
        syncPendingData: function() {
            // This would sync any pending form submissions or data changes
            // Implementation would depend on specific requirements
            console.log('Syncing pending data...');
        },

        /**
         * Initialize push notifications (placeholder for future implementation)
         */
        initPushNotifications: function() {
            if ('Notification' in window && 'serviceWorker' in navigator) {
                // Request permission for notifications
                if (Notification.permission === 'default') {
                    // Don't request immediately, wait for user interaction
                    $(document).one('click', function() {
                        Notification.requestPermission();
                    });
                }
            }
        },

        /**
         * Initialize accessibility enhancements
         */
        initAccessibility: function() {
            this.addSkipLinks();
            this.enhanceKeyboardNavigation();
            this.addAriaLabels();
            this.initFocusManagement();
        },

        /**
         * Add skip navigation links for screen readers
         */
        addSkipLinks: function() {
            if (!$('.slbp-skip-links').length) {
                $('body').prepend(`
                    <a href="#slbp-main-content" class="slbp-skip-links">
                        Skip to main content
                    </a>
                `);
            }

            // Add main content landmark if not present
            if (!$('#slbp-main-content').length) {
                $('.slbp-dashboard, .slbp-settings').attr('id', 'slbp-main-content');
            }
        },

        /**
         * Enhance keyboard navigation
         */
        enhanceKeyboardNavigation: function() {
            // Tab navigation through action buttons
            $('.slbp-action-button').attr('tabindex', '0');

            // Arrow key navigation for tab links
            $(document).on('keydown', '.slbp-tab-link', function(e) {
                const $current = $(this);
                const $tabs = $('.slbp-tab-link');
                const currentIndex = $tabs.index($current);

                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextIndex = (currentIndex + 1) % $tabs.length;
                    $tabs.eq(nextIndex).focus();
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevIndex = currentIndex === 0 ? $tabs.length - 1 : currentIndex - 1;
                    $tabs.eq(prevIndex).focus();
                }
            });
        },

        /**
         * Add appropriate ARIA labels and attributes
         */
        addAriaLabels: function() {
            // Add ARIA labels to navigation
            $('.slbp-tab-nav').attr('role', 'navigation').attr('aria-label', 'Settings navigation');
            $('.slbp-tab-link').attr('role', 'tab');

            // Add ARIA labels to form sections
            $('.slbp-form-table').each(function() {
                const $table = $(this);
                const heading = $table.prev('h3').text() || 'Settings';
                $table.attr('aria-label', heading + ' settings');
            });

            // Add live region for dynamic content updates
            if (!$('.slbp-live-region').length) {
                $('body').append('<div class="slbp-live-region" aria-live="polite" aria-atomic="true"></div>');
            }
        },

        /**
         * Initialize focus management
         */
        initFocusManagement: function() {
            // Trap focus in mobile navigation
            $(document).on('keydown', '.slbp-tab-nav.mobile-open', function(e) {
                if (e.key === 'Tab') {
                    const $focusable = $(this).find('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
                    const $first = $focusable.first();
                    const $last = $focusable.last();

                    if (e.shiftKey && $(e.target).is($first)) {
                        e.preventDefault();
                        $last.focus();
                    } else if (!e.shiftKey && $(e.target).is($last)) {
                        e.preventDefault();
                        $first.focus();
                    }
                }
            });
        },

        /**
         * Initialize touch-specific support
         */
        initTouchSupport: function() {
            // Add touch classes for styling
            if (this.isTouchDevice()) {
                $('body').addClass('touch-device');
            }

            // Improve touch targets
            this.improveTouchTargets();

            // Handle touch-specific interactions
            this.initTouchInteractions();
        },

        /**
         * Improve touch targets for better usability
         */
        improveTouchTargets: function() {
            // Ensure minimum touch target size (44px x 44px)
            $('.slbp-action-button, .slbp-tab-link, .slbp-save-button').each(function() {
                const $element = $(this);
                const width = $element.outerWidth();
                const height = $element.outerHeight();

                if (width < 44 || height < 44) {
                    $element.css({
                        'min-width': '44px',
                        'min-height': '44px',
                        'padding': '10px 15px'
                    });
                }
            });
        },

        /**
         * Initialize touch-specific interactions
         */
        initTouchInteractions: function() {
            // Prevent double-tap zoom on buttons
            $('.slbp-action-button, .slbp-save-button, .slbp-test-connection').on('touchend', function(e) {
                e.preventDefault();
                $(this).click();
            });

            // Add touch feedback
            $('.slbp-action-button, .slbp-tab-link').on('touchstart', function() {
                $(this).addClass('touch-active');
            }).on('touchend touchcancel', function() {
                $(this).removeClass('touch-active');
            });
        },

        /**
         * Utility function to check if device supports touch
         */
        isTouchDevice: function() {
            return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        },

        /**
         * Utility function to check if device is iOS
         */
        isIOS: function() {
            return /iPad|iPhone|iPod/.test(navigator.userAgent);
        },

        /**
         * Announce changes to screen readers
         */
        announceToScreenReader: function(message) {
            $('.slbp-live-region').text(message);
            setTimeout(function() {
                $('.slbp-live-region').empty();
            }, 1000);
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