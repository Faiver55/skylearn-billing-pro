/**
 * In-App Help System JavaScript
 *
 * @package SkyLearnBillingPro
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * SkyLearn Billing Pro Help System
     */
    window.SLBPHelpSystem = {
        
        /**
         * Initialize the help system
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initKeyboardShortcuts();
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Help button clicks
            $(document).on('click', '.slbp-help-button', this.handleHelpButtonClick.bind(this));
            
            // Modal events
            $(document).on('click', '.slbp-modal-close, .slbp-modal-overlay', this.closeModal.bind(this));
            
            // Feedback form
            $(document).on('submit', '#slbp-feedback-form', this.handleFeedbackSubmit.bind(this));
            
            // Prevent modal content clicks from closing modal
            $(document).on('click', '.slbp-modal-content', function(e) {
                e.stopPropagation();
            });
            
            // Escape key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // ESC key
                    this.closeModal();
                }
            }.bind(this));
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Enhanced tooltips with better positioning
            $('.slbp-help-tooltip').each(function() {
                var $tooltip = $(this);
                var content = $tooltip.data('tooltip');
                var position = $tooltip.data('position') || 'top';
                
                if (content) {
                    $tooltip.hover(
                        function() {
                            this.showTooltip($tooltip, content, position);
                        }.bind(this),
                        function() {
                            this.hideTooltip();
                        }.bind(this)
                    );
                }
            }.bind(this));
        },

        /**
         * Initialize keyboard shortcuts
         */
        initKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + ? to open help
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.keyCode === 191) {
                    e.preventDefault();
                    this.showContextualHelp();
                }
            }.bind(this));
        },

        /**
         * Handle help button clicks
         */
        handleHelpButtonClick: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var context = $button.data('context');
            var section = $button.data('section');
            
            if (context) {
                this.showHelpModal(context, section);
            } else {
                this.showContextualHelp();
            }
        },

        /**
         * Show help modal with specific content
         */
        showHelpModal: function(context, section) {
            var $modal = $('#slbp-help-modal');
            var $content = $('#slbp-help-modal-content');
            var $title = $('#slbp-help-modal-title');
            
            // Show loading state
            $content.html('<div class="slbp-loading"><span class="dashicons dashicons-update-alt"></span>' + 
                         slbpHelpAjax.strings.loading + '</div>');
            $title.text(slbpHelpAjax.strings.loading);
            $modal.show();
            
            // Load help content via AJAX
            $.ajax({
                url: slbpHelpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'slbp_get_help_content',
                    nonce: slbpHelpAjax.nonce,
                    context: context,
                    section: section
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var data = response.data;
                        $title.text(data.title || 'Help');
                        
                        var contentHtml = '';
                        if (data.sections) {
                            // Multiple sections
                            $.each(data.sections, function(key, section) {
                                contentHtml += '<div class="slbp-help-section">';
                                contentHtml += '<h3>' + section.title + '</h3>';
                                contentHtml += '<div>' + section.content + '</div>';
                                contentHtml += '</div>';
                            });
                        } else {
                            // Single section
                            contentHtml = data.content || 'No content available.';
                        }
                        
                        $content.html(contentHtml);
                    } else {
                        $content.html('<div class="notice notice-error"><p>' + 
                                     (response.data || slbpHelpAjax.strings.error) + '</p></div>');
                    }
                },
                error: function() {
                    $content.html('<div class="notice notice-error"><p>' + 
                                 slbpHelpAjax.strings.error + '</p></div>');
                }
            });
        },

        /**
         * Show contextual help based on current page
         */
        showContextualHelp: function() {
            var context = this.getCurrentContext();
            if (context) {
                this.showHelpModal(context);
            } else {
                // Fallback to general help page
                window.location.href = adminUrl + 'admin.php?page=slbp-help';
            }
        },

        /**
         * Get current page context for help
         */
        getCurrentContext: function() {
            var page = new URLSearchParams(window.location.search).get('page');
            
            var contextMap = {
                'skylearn-billing-pro': 'dashboard',
                'slbp-settings': 'settings',
                'slbp-analytics': 'analytics',
                'slbp-integrations': 'integrations',
                'slbp-notifications': 'notifications'
            };
            
            return contextMap[page] || '';
        },

        /**
         * Show feedback modal
         */
        showFeedbackModal: function() {
            $('#slbp-feedback-modal').show();
        },

        /**
         * Close modal
         */
        closeModal: function(e) {
            if (e && $(e.target).hasClass('slbp-modal-content')) {
                return; // Don't close if clicking inside modal content
            }
            
            $('.slbp-modal').hide();
        },

        /**
         * Handle feedback form submission
         */
        handleFeedbackSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(e.target);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Show loading state
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            var formData = {
                action: 'slbp_submit_help_feedback',
                nonce: slbpHelpAjax.nonce,
                rating: $form.find('[name="rating"]').val(),
                comments: $form.find('[name="comments"]').val(),
                contact_me: $form.find('[name="contact_me"]').is(':checked') ? 1 : 0,
                context: this.getCurrentContext(),
                url: window.location.href
            };
            
            $.ajax({
                url: slbpHelpAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $form.html('<div class="notice notice-success"><p>Thank you for your feedback!</p></div>');
                        
                        // Close modal after delay
                        setTimeout(function() {
                            this.closeModal();
                            $form[0].reset();
                            $form.html($form.data('original-html') || '');
                        }.bind(this), 2000);
                    } else {
                        this.showFormError($form, response.data || 'Submission failed. Please try again.');
                    }
                }.bind(this),
                error: function() {
                    this.showFormError($form, 'Network error. Please try again.');
                }.bind(this),
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Show form error message
         */
        showFormError: function($form, message) {
            var $error = $form.find('.slbp-form-error');
            if ($error.length === 0) {
                $error = $('<div class="slbp-form-error notice notice-error"><p></p></div>');
                $form.prepend($error);
            }
            $error.find('p').text(message);
        },

        /**
         * Show tooltip
         */
        showTooltip: function($element, content, position) {
            var $tooltip = $('<div class="slbp-tooltip-popup">' + content + '</div>');
            $('body').append($tooltip);
            
            var offset = $element.offset();
            var elementWidth = $element.outerWidth();
            var elementHeight = $element.outerHeight();
            var tooltipWidth = $tooltip.outerWidth();
            var tooltipHeight = $tooltip.outerHeight();
            
            var left, top;
            
            switch (position) {
                case 'top':
                    left = offset.left + (elementWidth / 2) - (tooltipWidth / 2);
                    top = offset.top - tooltipHeight - 8;
                    break;
                case 'bottom':
                    left = offset.left + (elementWidth / 2) - (tooltipWidth / 2);
                    top = offset.top + elementHeight + 8;
                    break;
                case 'left':
                    left = offset.left - tooltipWidth - 8;
                    top = offset.top + (elementHeight / 2) - (tooltipHeight / 2);
                    break;
                case 'right':
                    left = offset.left + elementWidth + 8;
                    top = offset.top + (elementHeight / 2) - (tooltipHeight / 2);
                    break;
                default:
                    left = offset.left + (elementWidth / 2) - (tooltipWidth / 2);
                    top = offset.top - tooltipHeight - 8;
            }
            
            // Keep tooltip within viewport
            var windowWidth = $(window).width();
            var windowHeight = $(window).height();
            var scrollLeft = $(window).scrollLeft();
            var scrollTop = $(window).scrollTop();
            
            if (left < scrollLeft) {
                left = scrollLeft + 8;
            } else if (left + tooltipWidth > scrollLeft + windowWidth) {
                left = scrollLeft + windowWidth - tooltipWidth - 8;
            }
            
            if (top < scrollTop) {
                top = scrollTop + 8;
            } else if (top + tooltipHeight > scrollTop + windowHeight) {
                top = scrollTop + windowHeight - tooltipHeight - 8;
            }
            
            $tooltip.css({ left: left, top: top }).fadeIn(200);
        },

        /**
         * Hide tooltip
         */
        hideTooltip: function() {
            $('.slbp-tooltip-popup').fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Add help button to page element
         */
        addHelpButton: function(selector, context, section, options) {
            var defaults = {
                position: 'after',
                text: 'Help',
                icon: 'editor-help',
                class: 'slbp-help-button'
            };
            
            options = $.extend(defaults, options);
            
            var $button = $('<button type="button" class="' + options.class + 
                           '" data-context="' + context + '"' +
                           (section ? ' data-section="' + section + '"' : '') + '>' +
                           '<span class="dashicons dashicons-' + options.icon + '"></span>' +
                           '<span class="slbp-help-text">' + options.text + '</span>' +
                           '</button>');
            
            $(selector).each(function() {
                var $element = $(this);
                if (options.position === 'after') {
                    $element.after($button.clone());
                } else if (options.position === 'before') {
                    $element.before($button.clone());
                } else if (options.position === 'append') {
                    $element.append($button.clone());
                } else if (options.position === 'prepend') {
                    $element.prepend($button.clone());
                }
            });
        },

        /**
         * Add help tooltip to form field
         */
        addFieldHelp: function(selector, content, options) {
            var defaults = {
                position: 'top',
                icon: 'editor-help'
            };
            
            options = $.extend(defaults, options);
            
            var $tooltip = $('<span class="slbp-help-tooltip" data-tooltip="' + content + 
                           '" data-position="' + options.position + '">' +
                           '<span class="dashicons dashicons-' + options.icon + '"></span>' +
                           '</span>');
            
            $(selector).each(function() {
                var $field = $(this);
                var $label = $field.siblings('label').first();
                
                if ($label.length) {
                    $label.append($tooltip.clone());
                } else {
                    $field.after($tooltip.clone());
                }
            });
            
            // Re-initialize tooltips
            this.initTooltips();
        }
    };

    // Auto-initialize on document ready
    $(document).ready(function() {
        window.SLBPHelpSystem.init();
    });

})(jQuery);

// CSS for dynamic tooltips
jQuery(document).ready(function($) {
    if (!$('#slbp-tooltip-styles').length) {
        $('<style id="slbp-tooltip-styles">' +
          '.slbp-tooltip-popup {' +
          '  position: absolute;' +
          '  background: #1d2327;' +
          '  color: white;' +
          '  padding: 8px 12px;' +
          '  border-radius: 4px;' +
          '  font-size: 12px;' +
          '  line-height: 1.4;' +
          '  z-index: 9999;' +
          '  max-width: 250px;' +
          '  word-wrap: break-word;' +
          '  display: none;' +
          '}' +
          '</style>').appendTo('head');
    }
});