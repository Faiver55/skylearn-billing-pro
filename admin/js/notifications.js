/**
 * SkyLearn Billing Pro - Admin Notifications JavaScript
 */

(function($) {
    'use strict';

    /**
     * Notification Center functionality
     */
    var SLBPNotifications = {
        
        /**
         * Initialize notifications
         */
        init: function() {
            this.bindEvents();
            this.setupAdminBar();
            this.autoRefresh();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Admin bar notification toggle
            $(document).on('click', '.slbp-notifications-toggle', this.toggleDropdown.bind(this));
            
            // Mark notification as read
            $(document).on('click', '.mark-read', this.markAsRead.bind(this));
            
            // Notification item click
            $(document).on('click', '.slbp-notification-item', this.handleNotificationClick.bind(this));
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#wp-admin-bar-slbp-notifications').length) {
                    $('.slbp-notifications-dropdown').removeClass('show');
                }
            });

            // Preferences form
            $('#slbp-notification-preferences-form').on('submit', this.savePreferences.bind(this));

            // Bulk actions
            $(document).on('click', '.action', this.handleBulkAction.bind(this));
        },

        /**
         * Setup admin bar notification dropdown
         */
        setupAdminBar: function() {
            var $adminBarItem = $('#wp-admin-bar-slbp-notifications');
            if ($adminBarItem.length) {
                // Create dropdown container
                var $dropdown = $('<div class="slbp-notifications-dropdown"></div>');
                $adminBarItem.append($dropdown);
                
                // Load initial notifications
                this.loadNotifications();
            }
        },

        /**
         * Toggle notification dropdown
         */
        toggleDropdown: function(e) {
            e.preventDefault();
            var $dropdown = $('.slbp-notifications-dropdown');
            
            if ($dropdown.hasClass('show')) {
                $dropdown.removeClass('show');
            } else {
                $dropdown.addClass('show');
                this.loadNotifications();
            }
        },

        /**
         * Load notifications via AJAX
         */
        loadNotifications: function() {
            var $dropdown = $('.slbp-notifications-dropdown');
            
            $dropdown.html('<div class="slbp-notifications-loading"></div>');
            
            $.ajax({
                url: slbp_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_get_notifications',
                    nonce: slbp_notifications.nonce,
                    per_page: 10
                },
                success: function(response) {
                    if (response.success) {
                        this.renderNotifications(response.data.notifications, response.data.unread_count);
                        this.updateUnreadCount(response.data.unread_count);
                    } else {
                        $dropdown.html('<div class="slbp-notifications-empty">' + 
                                     (response.data || 'Error loading notifications') + '</div>');
                    }
                }.bind(this),
                error: function() {
                    $dropdown.html('<div class="slbp-notifications-empty">Error loading notifications</div>');
                }
            });
        },

        /**
         * Render notifications in dropdown
         */
        renderNotifications: function(notifications, unreadCount) {
            var $dropdown = $('.slbp-notifications-dropdown');
            var html = '';
            
            // Header
            html += '<div class="slbp-notifications-header">';
            html += '<span>' + slbp_notifications.strings.no_notifications + '</span>';
            if (unreadCount > 0) {
                html = html.replace(slbp_notifications.strings.no_notifications, 
                                  unreadCount + ' new notification' + (unreadCount > 1 ? 's' : ''));
            }
            html += '</div>';
            
            // Notifications list
            html += '<div class="slbp-notifications-list">';
            
            if (notifications.length === 0) {
                html += '<div class="slbp-notifications-empty">' + slbp_notifications.strings.no_notifications + '</div>';
            } else {
                notifications.forEach(function(notification) {
                    var itemClass = notification.is_read ? 'read' : 'unread';
                    html += '<div class="slbp-notification-item ' + itemClass + '" data-id="' + notification.id + '">';
                    html += '<div class="slbp-notification-item-title">' + this.escapeHtml(notification.title) + '</div>';
                    html += '<div class="slbp-notification-item-message">' + this.escapeHtml(notification.message) + '</div>';
                    html += '<div class="slbp-notification-item-time">' + notification.time_ago + ' ago</div>';
                    html += '</div>';
                }.bind(this));
            }
            
            html += '</div>';
            
            // Footer
            html += '<div class="slbp-notifications-footer">';
            html += '<a href="' + this.getNotificationsUrl() + '">' + slbp_notifications.strings.view_all + '</a>';
            html += '</div>';
            
            $dropdown.html(html);
        },

        /**
         * Handle notification item click
         */
        handleNotificationClick: function(e) {
            var $item = $(e.currentTarget);
            var notificationId = $item.data('id');
            
            if (!$item.hasClass('read')) {
                this.markNotificationRead(notificationId, $item);
            }
        },

        /**
         * Mark notification as read
         */
        markAsRead: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $button = $(e.currentTarget);
            var notificationId = $button.data('notification-id');
            var $row = $button.closest('tr');
            
            this.markNotificationRead(notificationId, $row);
        },

        /**
         * Mark notification as read via AJAX
         */
        markNotificationRead: function(notificationId, $element) {
            $.ajax({
                url: slbp_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_mark_notification_read',
                    nonce: slbp_notifications.nonce,
                    notification_id: notificationId
                },
                success: function(response) {
                    if (response.success) {
                        $element.removeClass('unread').addClass('read');
                        this.updateUnreadCount(response.data.unread_count);
                        
                        // Update button in admin table
                        var $button = $element.find('.mark-read');
                        if ($button.length) {
                            $button.replaceWith('<span class="read-status">Read</span>');
                        }
                    }
                }.bind(this)
            });
        },

        /**
         * Update unread count in admin bar
         */
        updateUnreadCount: function(count) {
            var $countElement = $('.slbp-notification-count');
            var $adminBarItem = $('#wp-admin-bar-slbp-notifications .ab-item');
            
            if (count > 0) {
                if ($countElement.length) {
                    $countElement.text(count);
                } else {
                    $adminBarItem.append('<span class="slbp-notification-count">' + count + '</span>');
                }
            } else {
                $countElement.remove();
            }
        },

        /**
         * Save notification preferences
         */
        savePreferences: function(e) {
            e.preventDefault();
            
            var $form = $(e.currentTarget);
            var formData = $form.serializeArray();
            var preferences = {};
            
            // Parse form data into preferences object
            formData.forEach(function(field) {
                var matches = field.name.match(/preferences\[([^\]]+)\]\[([^\]]+)\]/);
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
                url: slbp_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_update_notification_preferences',
                    nonce: slbp_notifications.nonce,
                    preferences: preferences
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotice('Preferences saved successfully!', 'success');
                    } else {
                        this.showNotice(response.data || 'Error saving preferences', 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotice('Error saving preferences', 'error');
                }.bind(this)
            });
        },

        /**
         * Handle bulk actions
         */
        handleBulkAction: function(e) {
            var $button = $(e.currentTarget);
            var action = $button.siblings('select').val();
            
            if (action === 'mark_read') {
                var selectedIds = [];
                $('input[name="notification[]"]:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                
                if (selectedIds.length === 0) {
                    alert('Please select notifications to mark as read.');
                    return;
                }
                
                // Mark each notification as read
                selectedIds.forEach(function(id) {
                    var $row = $('input[name="notification[]"][value="' + id + '"]').closest('tr');
                    this.markNotificationRead(id, $row);
                }.bind(this));
            }
        },

        /**
         * Auto-refresh notifications periodically
         */
        autoRefresh: function() {
            setInterval(function() {
                if ($('.slbp-notifications-dropdown.show').length) {
                    this.loadNotifications();
                } else {
                    // Just update the count
                    this.updateNotificationCount();
                }
            }.bind(this), 30000); // Every 30 seconds
        },

        /**
         * Update notification count only
         */
        updateNotificationCount: function() {
            $.ajax({
                url: slbp_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_get_notifications',
                    nonce: slbp_notifications.nonce,
                    per_page: 1,
                    unread_only: true
                },
                success: function(response) {
                    if (response.success) {
                        this.updateUnreadCount(response.data.unread_count);
                    }
                }.bind(this)
            });
        },

        /**
         * Helper functions
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        getNotificationsUrl: function() {
            return window.ajaxurl.replace('admin-ajax.php', 'admin.php?page=slbp-notifications');
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut();
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SLBPNotifications.init();
    });

})(jQuery);