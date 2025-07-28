/**
 * Security Admin Dashboard JavaScript
 */
(function($) {
    'use strict';

    var SLBP_Security_Admin = {
        init: function() {
            this.bindEvents();
            this.loadDashboardData();
            this.initCharts();
        },

        bindEvents: function() {
            // Dashboard actions
            $(document).on('click', '#slbp-run-security-scan', this.runSecurityScan);
            $(document).on('click', '#slbp-export-logs', this.exportAuditLogs);
            $(document).on('click', '#slbp-run-pci-assessment', this.runPCIAssessment);
            $(document).on('click', '#slbp-generate-gdpr-report', this.generateGDPRReport);
            $(document).on('click', '#slbp-generate-pci-report', this.generatePCIReport);

            // Auto refresh dashboard every 5 minutes
            setInterval(this.refreshDashboard, 300000);
        },

        loadDashboardData: function() {
            if ($('#slbp-security-score').length) {
                this.loadSecurityOverview();
            }
            if ($('#slbp-gdpr-status').length) {
                this.loadComplianceStatus();
            }
        },

        loadSecurityOverview: function() {
            $.ajax({
                url: slbp_security_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_get_security_overview',
                    nonce: slbp_security_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SLBP_Security_Admin.updateSecurityScoreDisplay(response.data.security_score);
                        SLBP_Security_Admin.updatePCIIndicators(response.data.pci_score);
                        SLBP_Security_Admin.updateRecentEvents(response.data.recent_events);
                        SLBP_Security_Admin.updateRecommendations(response.data.recommendations);
                        SLBP_Security_Admin.updateThreatStats(response.data.threat_stats);
                    }
                },
                error: function() {
                    SLBP_Security_Admin.showNotification('Failed to load security overview', 'error');
                }
            });
        },

        loadComplianceStatus: function() {
            $.ajax({
                url: slbp_security_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_get_compliance_status',
                    nonce: slbp_security_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SLBP_Security_Admin.updatePrivacyStats(response.data);
                    }
                },
                error: function() {
                    SLBP_Security_Admin.showNotification('Failed to load compliance status', 'error');
                }
            });
        },

        updateSecurityScoreDisplay: function(score) {
            $('#slbp-security-score').text(score);
            
            var $circle = $('#slbp-security-score-circle');
            var $status = $('#slbp-security-status');
            
            // Update circle color and status
            $circle.removeClass('slbp-score-low slbp-score-medium slbp-score-high');
            
            if (score >= 80) {
                $circle.addClass('slbp-score-high');
                $status.html('<span class="slbp-status-good">Excellent Security</span>');
            } else if (score >= 60) {
                $circle.addClass('slbp-score-medium');
                $status.html('<span class="slbp-status-warning">Good Security</span>');
            } else {
                $circle.addClass('slbp-score-low');
                $status.html('<span class="slbp-status-critical">Needs Improvement</span>');
            }

            // Animate score circle
            this.animateScoreCircle($circle, score);
        },

        animateScoreCircle: function($circle, score) {
            var circumference = 2 * Math.PI * 45; // radius = 45
            var offset = circumference - (score / 100) * circumference;
            
            $circle.find('.slbp-score-circle-progress').css({
                'stroke-dasharray': circumference,
                'stroke-dashoffset': offset
            });
        },

        updatePCIIndicators: function(pciScore) {
            var $indicators = $('#slbp-pci-indicators .slbp-indicator-status');
            
            $indicators.removeClass('slbp-status-loading slbp-status-pass slbp-status-fail');
            
            if (pciScore >= 80) {
                $indicators.addClass('slbp-status-pass').text('Pass');
            } else {
                $indicators.addClass('slbp-status-fail').text('Fail');
            }
        },

        updateRecentEvents: function(events) {
            var $container = $('#slbp-recent-events');
            $container.empty();
            
            if (events.length === 0) {
                $container.html('<div class="slbp-no-events">No recent security events</div>');
                return;
            }
            
            var html = '<ul class="slbp-events-list">';
            events.forEach(function(event) {
                var severityClass = 'slbp-severity-' + event.severity;
                var timeAgo = SLBP_Security_Admin.timeAgo(event.created_at);
                
                html += '<li class="slbp-event-item ' + severityClass + '">';
                html += '<div class="slbp-event-action">' + event.action + '</div>';
                html += '<div class="slbp-event-time">' + timeAgo + '</div>';
                html += '</li>';
            });
            html += '</ul>';
            
            $container.html(html);
        },

        updateRecommendations: function(recommendations) {
            var $container = $('#slbp-security-recommendations');
            $container.empty();
            
            if (recommendations.length === 0) {
                $container.html('<div class="slbp-no-recommendations">No security recommendations at this time</div>');
                return;
            }
            
            var html = '<ul class="slbp-recommendations-list">';
            recommendations.forEach(function(rec) {
                var priorityClass = 'slbp-priority-' + rec.priority;
                
                html += '<li class="slbp-recommendation-item ' + priorityClass + '">';
                html += '<div class="slbp-rec-title">' + rec.title + '</div>';
                html += '<div class="slbp-rec-description">' + rec.description + '</div>';
                html += '</li>';
            });
            html += '</ul>';
            
            $container.html(html);
        },

        updateThreatStats: function(stats) {
            $('#slbp-blocked-attempts').text(stats.blocked_attempts);
            $('#slbp-threat-level').text(stats.threat_level);
            
            if (stats.chart_data) {
                this.updateThreatChart(stats.chart_data);
            }
        },

        updatePrivacyStats: function(data) {
            $('#slbp-data-requests').text(data.data_requests);
            $('#slbp-consent-rate').text(data.consent_rate + '%');
        },

        initCharts: function() {
            // Initialize threat chart if canvas exists
            if ($('#slbp-threat-chart').length) {
                this.threatChart = new Chart($('#slbp-threat-chart')[0].getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Security Threats',
                            data: [],
                            borderColor: '#dc3232',
                            backgroundColor: 'rgba(220, 50, 50, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        },

        updateThreatChart: function(chartData) {
            if (!this.threatChart) return;
            
            var labels = chartData.map(function(item) {
                return new Date(item.date).toLocaleDateString();
            });
            
            var data = chartData.map(function(item) {
                return item.threats;
            });
            
            this.threatChart.data.labels = labels;
            this.threatChart.data.datasets[0].data = data;
            this.threatChart.update();
        },

        runSecurityScan: function(e) {
            e.preventDefault();
            
            if (!confirm(slbp_security_admin.strings.confirm_scan)) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text('Running Scan...');
            
            $.ajax({
                url: slbp_security_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_run_security_audit',
                    nonce: slbp_security_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SLBP_Security_Admin.updateSecurityScoreDisplay(response.data.security_score);
                        SLBP_Security_Admin.showNotification('Security scan completed', 'success');
                    } else {
                        SLBP_Security_Admin.showNotification('Security scan failed', 'error');
                    }
                },
                error: function() {
                    SLBP_Security_Admin.showNotification('Security scan failed', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Run Security Scan');
                }
            });
        },

        runPCIAssessment: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true).text('Running Assessment...');
            
            $.ajax({
                url: slbp_security_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_run_pci_assessment',
                    nonce: slbp_security_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SLBP_Security_Admin.displayPCIResults(response.data);
                        SLBP_Security_Admin.showNotification('PCI assessment completed', 'success');
                    } else {
                        SLBP_Security_Admin.showNotification('PCI assessment failed', 'error');
                    }
                },
                error: function() {
                    SLBP_Security_Admin.showNotification('PCI assessment failed', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Run PCI Assessment');
                }
            });
        },

        displayPCIResults: function(assessment) {
            var $container = $('#slbp-pci-status-detailed');
            
            var html = '<div class="slbp-pci-results">';
            html += '<div class="slbp-pci-score">Score: ' + assessment.score + '/100</div>';
            html += '<div class="slbp-pci-requirements">';
            
            Object.keys(assessment.requirements).forEach(function(key) {
                var req = assessment.requirements[key];
                var statusClass = 'slbp-req-' + req.status;
                
                html += '<div class="slbp-requirement-item ' + statusClass + '">';
                html += '<div class="slbp-req-title">' + req.title + '</div>';
                html += '<div class="slbp-req-status">' + req.status.toUpperCase() + '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            if (assessment.recommendations.length > 0) {
                html += '<div class="slbp-pci-recommendations">';
                html += '<h4>Recommendations:</h4>';
                html += '<ul>';
                assessment.recommendations.forEach(function(rec) {
                    html += '<li>' + rec + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }
            
            html += '</div>';
            
            $container.html(html);
        },

        exportAuditLogs: function(e) {
            e.preventDefault();
            
            var filters = SLBP_Security_Admin.getLogFilters();
            
            $.ajax({
                url: slbp_security_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_export_audit_logs',
                    nonce: slbp_security_admin.nonce,
                    filters: filters
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        // Create a temporary download link
                        var link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = 'slbp-audit-logs.csv';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        SLBP_Security_Admin.showNotification('Audit logs exported successfully', 'success');
                    } else {
                        SLBP_Security_Admin.showNotification('Failed to export audit logs', 'error');
                    }
                },
                error: function() {
                    SLBP_Security_Admin.showNotification('Failed to export audit logs', 'error');
                }
            });
        },

        getLogFilters: function() {
            return {
                event_type: $('select[name="event_type"]').val() || '',
                severity: $('select[name="severity"]').val() || '',
                start_date: $('input[name="start_date"]').val() || '',
                end_date: $('input[name="end_date"]').val() || '',
                search: $('input[name="search"]').val() || ''
            };
        },

        generateGDPRReport: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true).text('Generating Report...');
            
            $.ajax({
                url: slbp_security_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_generate_gdpr_report',
                    nonce: slbp_security_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.report_url) {
                        window.open(response.data.report_url, '_blank');
                        SLBP_Security_Admin.showNotification('GDPR report generated successfully', 'success');
                    } else {
                        SLBP_Security_Admin.showNotification('Failed to generate GDPR report', 'error');
                    }
                },
                error: function() {
                    SLBP_Security_Admin.showNotification('Failed to generate GDPR report', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Generate GDPR Report');
                }
            });
        },

        generatePCIReport: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true).text('Generating Report...');
            
            $.ajax({
                url: slbp_security_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'slbp_generate_compliance_report',
                    nonce: slbp_security_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.report_url) {
                        window.open(response.data.report_url, '_blank');
                        SLBP_Security_Admin.showNotification('PCI compliance report generated successfully', 'success');
                    } else {
                        SLBP_Security_Admin.showNotification('Failed to generate PCI report', 'error');
                    }
                },
                error: function() {
                    SLBP_Security_Admin.showNotification('Failed to generate PCI report', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Generate PCI Report');
                }
            });
        },

        refreshDashboard: function() {
            if ($('#slbp-security-score').length) {
                SLBP_Security_Admin.loadSecurityOverview();
            }
        },

        timeAgo: function(dateString) {
            var date = new Date(dateString);
            var now = new Date();
            var diff = now - date;
            
            var seconds = Math.floor(diff / 1000);
            var minutes = Math.floor(seconds / 60);
            var hours = Math.floor(minutes / 60);
            var days = Math.floor(hours / 24);
            
            if (days > 0) {
                return days + ' day' + (days > 1 ? 's' : '') + ' ago';
            } else if (hours > 0) {
                return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
            } else if (minutes > 0) {
                return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
            } else {
                return 'Just now';
            }
        },

        showNotification: function(message, type) {
            var notification = $('<div class="slbp-admin-notification slbp-notification-' + type + '">' + message + '</div>');
            $('body').append(notification);
            
            notification.fadeIn().delay(3000).fadeOut(function() {
                $(this).remove();
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SLBP_Security_Admin.init();
    });

    // Make available globally
    window.SLBP_Security_Admin = SLBP_Security_Admin;

})(jQuery);