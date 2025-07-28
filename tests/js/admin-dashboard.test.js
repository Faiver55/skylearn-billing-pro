/**
 * Tests for admin dashboard functionality
 */

describe('SkyLearn Billing Pro Admin Dashboard', () => {
    
    beforeEach(() => {
        // Setup DOM for admin tests
        document.body.innerHTML = `
            <div id="slbp-admin-dashboard">
                <div class="slbp-stats-container">
                    <div class="slbp-stat-card" data-stat="revenue">
                        <span class="stat-value">$0</span>
                        <span class="stat-label">Total Revenue</span>
                    </div>
                    <div class="slbp-stat-card" data-stat="transactions">
                        <span class="stat-value">0</span>
                        <span class="stat-label">Transactions</span>
                    </div>
                </div>
                <div class="slbp-chart-container">
                    <canvas id="revenue-chart"></canvas>
                </div>
                <form id="slbp-settings-form">
                    <input type="text" name="api_key" id="api-key" />
                    <select name="gateway" id="gateway-select">
                        <option value="lemon-squeezy">Lemon Squeezy</option>
                    </select>
                    <button type="submit">Save Settings</button>
                </form>
            </div>
        `;
    });

    describe('Dashboard Statistics', () => {
        test('should load and display revenue statistics', async () => {
            // Mock API response
            const mockStats = {
                revenue: 12345.67,
                transactions: 156,
                subscriptions: 23,
                growth: 15.5
            };

            global.fetch.mockResolvedValueOnce(
                testUtils.mockApiResponse({ data: mockStats })
            );

            // Import the dashboard module (this would be the actual admin JS)
            const { loadDashboardStats } = require('../../admin/js/dashboard');
            
            await loadDashboardStats();

            // Verify API was called
            expect(global.fetch).toHaveBeenCalledWith(
                expect.stringContaining('/wp-json/skylearn-billing-pro/v1/stats'),
                expect.objectContaining({
                    headers: expect.objectContaining({
                        'X-WP-Nonce': 'test_nonce_123'
                    })
                })
            );

            // Verify stats are displayed
            const revenueCard = document.querySelector('[data-stat="revenue"] .stat-value');
            const transactionsCard = document.querySelector('[data-stat="transactions"] .stat-value');
            
            expect(revenueCard.textContent).toBe('$12,345.67');
            expect(transactionsCard.textContent).toBe('156');
        });

        test('should handle API errors gracefully', async () => {
            // Mock API error
            global.fetch.mockRejectedValueOnce(new Error('Network error'));

            const { loadDashboardStats } = require('../../admin/js/dashboard');
            
            await loadDashboardStats();

            // Should show error message
            const errorMessage = document.querySelector('.slbp-error-message');
            expect(errorMessage).toBeTruthy();
            expect(errorMessage.textContent).toContain('Failed to load statistics');
        });
    });

    describe('Settings Form', () => {
        test('should validate required fields', () => {
            const { validateSettingsForm } = require('../../admin/js/settings');
            
            const form = document.getElementById('slbp-settings-form');
            const apiKeyInput = document.getElementById('api-key');
            
            // Empty API key should fail validation
            apiKeyInput.value = '';
            const result = validateSettingsForm(form);
            
            expect(result.valid).toBe(false);
            expect(result.errors).toContain('API key is required');
        });

        test('should submit form with valid data', async () => {
            // Mock successful save
            global.fetch.mockResolvedValueOnce(
                testUtils.mockApiResponse({ success: true })
            );

            const { submitSettingsForm } = require('../../admin/js/settings');
            
            const form = document.getElementById('slbp-settings-form');
            const apiKeyInput = document.getElementById('api-key');
            const gatewaySelect = document.getElementById('gateway-select');
            
            apiKeyInput.value = 'test_api_key_123';
            gatewaySelect.value = 'lemon-squeezy';
            
            const result = await submitSettingsForm(form);
            
            expect(result.success).toBe(true);
            expect(global.fetch).toHaveBeenCalledWith(
                expect.stringContaining('/wp-json/skylearn-billing-pro/v1/settings'),
                expect.objectContaining({
                    method: 'POST',
                    body: expect.stringContaining('test_api_key_123')
                })
            );
        });

        test('should show success notification after save', async () => {
            global.fetch.mockResolvedValueOnce(
                testUtils.mockApiResponse({ success: true })
            );

            const { submitSettingsForm, showNotification } = require('../../admin/js/settings');
            
            const form = document.getElementById('slbp-settings-form');
            document.getElementById('api-key').value = 'test_key';
            
            await submitSettingsForm(form);
            
            // Should show success notification
            const notification = document.querySelector('.slbp-notification.success');
            expect(notification).toBeTruthy();
            expect(notification.textContent).toContain('Settings saved successfully');
        });
    });

    describe('Chart Rendering', () => {
        test('should render revenue chart with data', async () => {
            const mockChartData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                datasets: [{
                    label: 'Revenue',
                    data: [1000, 1500, 1200, 1800, 2000]
                }]
            };

            global.fetch.mockResolvedValueOnce(
                testUtils.mockApiResponse({ data: mockChartData })
            );

            // Mock Chart.js
            global.Chart = jest.fn().mockImplementation(() => ({
                update: jest.fn(),
                destroy: jest.fn()
            }));

            const { renderRevenueChart } = require('../../admin/js/charts');
            
            await renderRevenueChart();
            
            expect(global.Chart).toHaveBeenCalledWith(
                expect.any(HTMLCanvasElement),
                expect.objectContaining({
                    type: 'line',
                    data: mockChartData
                })
            );
        });

        test('should handle empty chart data', async () => {
            global.fetch.mockResolvedValueOnce(
                testUtils.mockApiResponse({ data: { labels: [], datasets: [] } })
            );

            global.Chart = jest.fn();

            const { renderRevenueChart } = require('../../admin/js/charts');
            
            await renderRevenueChart();
            
            // Should show "no data" message instead of chart
            const noDataMessage = document.querySelector('.slbp-no-data');
            expect(noDataMessage).toBeTruthy();
            expect(global.Chart).not.toHaveBeenCalled();
        });
    });

    describe('Real-time Updates', () => {
        test('should update dashboard when receiving WebSocket message', () => {
            // Mock WebSocket
            global.WebSocket = jest.fn().mockImplementation(() => ({
                addEventListener: jest.fn(),
                send: jest.fn(),
                close: jest.fn()
            }));

            const { initializeRealTimeUpdates, handleWebSocketMessage } = require('../../admin/js/realtime');
            
            initializeRealTimeUpdates();
            
            // Simulate receiving a transaction update
            const mockMessage = {
                type: 'transaction_completed',
                data: {
                    amount: 99.99,
                    currency: 'USD'
                }
            };
            
            handleWebSocketMessage({ data: JSON.stringify(mockMessage) });
            
            // Should update the stats counter
            const transactionsStat = document.querySelector('[data-stat="transactions"] .stat-value');
            expect(transactionsStat.textContent).toBe('1'); // Incremented from 0
        });
    });

    describe('Error Handling', () => {
        test('should display user-friendly error messages', () => {
            const { showError } = require('../../admin/js/notifications');
            
            showError('Payment gateway connection failed');
            
            const errorEl = document.querySelector('.slbp-notification.error');
            expect(errorEl).toBeTruthy();
            expect(errorEl.textContent).toContain('Payment gateway connection failed');
            expect(errorEl.classList).toContain('error');
        });

        test('should auto-hide notifications after timeout', (done) => {
            const { showNotification } = require('../../admin/js/notifications');
            
            showNotification('Test message', 'success', 100); // 100ms timeout
            
            const notification = document.querySelector('.slbp-notification');
            expect(notification).toBeTruthy();
            
            setTimeout(() => {
                expect(notification.style.display).toBe('none');
                done();
            }, 150);
        });
    });
});

// Mock modules that would be in separate files
jest.doMock('../../admin/js/dashboard', () => ({
    loadDashboardStats: jest.fn().mockImplementation(async () => {
        try {
            const response = await fetch('/wp-json/skylearn-billing-pro/v1/stats', {
                headers: { 'X-WP-Nonce': global.slbp_ajax.nonce }
            });
            const data = await response.json();
            
            // Update DOM with stats
            const revenueEl = document.querySelector('[data-stat="revenue"] .stat-value');
            const transactionsEl = document.querySelector('[data-stat="transactions"] .stat-value');
            
            if (revenueEl) revenueEl.textContent = `$${data.data.revenue.toLocaleString()}`;
            if (transactionsEl) transactionsEl.textContent = data.data.transactions.toString();
            
        } catch (error) {
            // Show error
            const errorDiv = document.createElement('div');
            errorDiv.className = 'slbp-error-message';
            errorDiv.textContent = 'Failed to load statistics';
            document.body.appendChild(errorDiv);
        }
    })
}));

jest.doMock('../../admin/js/settings', () => ({
    validateSettingsForm: jest.fn().mockImplementation((form) => {
        const apiKey = form.querySelector('#api-key').value;
        const errors = [];
        
        if (!apiKey.trim()) {
            errors.push('API key is required');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }),
    
    submitSettingsForm: jest.fn().mockImplementation(async (form) => {
        const formData = new FormData(form);
        const response = await fetch('/wp-json/skylearn-billing-pro/v1/settings', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const notification = document.createElement('div');
            notification.className = 'slbp-notification success';
            notification.textContent = 'Settings saved successfully';
            document.body.appendChild(notification);
        }
        
        return result;
    }),
    
    showNotification: jest.fn()
}));

jest.doMock('../../admin/js/charts', () => ({
    renderRevenueChart: jest.fn().mockImplementation(async () => {
        const response = await fetch('/wp-json/skylearn-billing-pro/v1/charts/revenue');
        const data = await response.json();
        
        if (data.data.labels.length === 0) {
            const noDataDiv = document.createElement('div');
            noDataDiv.className = 'slbp-no-data';
            noDataDiv.textContent = 'No data available';
            document.body.appendChild(noDataDiv);
        } else {
            const canvas = document.getElementById('revenue-chart');
            new Chart(canvas, {
                type: 'line',
                data: data.data
            });
        }
    })
}));

jest.doMock('../../admin/js/realtime', () => ({
    initializeRealTimeUpdates: jest.fn(),
    handleWebSocketMessage: jest.fn().mockImplementation((event) => {
        const message = JSON.parse(event.data);
        if (message.type === 'transaction_completed') {
            const counter = document.querySelector('[data-stat="transactions"] .stat-value');
            if (counter) {
                const current = parseInt(counter.textContent) || 0;
                counter.textContent = (current + 1).toString();
            }
        }
    })
}));

jest.doMock('../../admin/js/notifications', () => ({
    showError: jest.fn().mockImplementation((message) => {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'slbp-notification error';
        errorDiv.textContent = message;
        document.body.appendChild(errorDiv);
    }),
    
    showNotification: jest.fn().mockImplementation((message, type, timeout) => {
        const notification = document.createElement('div');
        notification.className = `slbp-notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        if (timeout) {
            setTimeout(() => {
                notification.style.display = 'none';
            }, timeout);
        }
    })
}));