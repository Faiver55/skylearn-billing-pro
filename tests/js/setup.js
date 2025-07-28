/**
 * Jest setup file for SkyLearn Billing Pro
 */

// Add custom matchers
import '@testing-library/jest-dom';

// Mock WordPress globals
global.wp = {
    i18n: {
        __: (text) => text,
        _x: (text) => text,
        _n: (single, plural, number) => number === 1 ? single : plural,
        sprintf: (format, ...args) => format.replace(/%s/g, () => args.shift()),
    },
    hooks: {
        addAction: jest.fn(),
        addFilter: jest.fn(),
        doAction: jest.fn(),
        applyFilters: jest.fn(),
    },
    ajax: {
        post: jest.fn(),
    },
    data: {
        select: jest.fn(),
        dispatch: jest.fn(),
    },
    element: {
        createElement: jest.fn(),
        render: jest.fn(),
    },
};

global.jQuery = global.$ = {
    fn: {},
    extend: jest.fn(),
    ajax: jest.fn(),
    post: jest.fn(),
    get: jest.fn(),
    ready: jest.fn(),
    on: jest.fn(),
    off: jest.fn(),
    trigger: jest.fn(),
    each: jest.fn(),
    map: jest.fn(),
    filter: jest.fn(),
    find: jest.fn(),
    closest: jest.fn(),
    attr: jest.fn(),
    data: jest.fn(),
    val: jest.fn(),
    text: jest.fn(),
    html: jest.fn(),
    addClass: jest.fn(),
    removeClass: jest.fn(),
    hasClass: jest.fn(),
    show: jest.fn(),
    hide: jest.fn(),
    fadeIn: jest.fn(),
    fadeOut: jest.fn(),
    slideUp: jest.fn(),
    slideDown: jest.fn(),
};

// Mock WordPress AJAX
global.ajaxurl = '/wp-admin/admin-ajax.php';

// Mock SkyLearn Billing Pro globals
global.slbp_ajax = {
    url: '/wp-admin/admin-ajax.php',
    nonce: 'test_nonce_123',
    user_id: 1,
};

global.slbp_config = {
    api_base: '/wp-json/skylearn-billing-pro/v1',
    currency: 'USD',
    test_mode: true,
    debug: true,
};

// Console helpers for tests
global.console = {
    ...console,
    // Uncomment to ignore console.log output during tests
    // log: jest.fn(),
    // warn: jest.fn(),
    // error: jest.fn(),
};

// Mock fetch for API calls
global.fetch = jest.fn();

// Setup fake timers if needed
// jest.useFakeTimers();

// Custom test utilities
global.testUtils = {
    /**
     * Create a mock DOM element
     */
    createMockElement: (tag = 'div', attributes = {}) => {
        const element = document.createElement(tag);
        Object.keys(attributes).forEach(key => {
            element.setAttribute(key, attributes[key]);
        });
        return element;
    },

    /**
     * Create a mock jQuery object
     */
    createMockJQuery: (selector) => {
        const elements = typeof selector === 'string' 
            ? [document.querySelector(selector)] 
            : [selector];
        
        return {
            length: elements.length,
            get: (index) => elements[index],
            eq: (index) => global.testUtils.createMockJQuery(elements[index]),
            find: jest.fn().mockReturnThis(),
            closest: jest.fn().mockReturnThis(),
            on: jest.fn().mockReturnThis(),
            off: jest.fn().mockReturnThis(),
            trigger: jest.fn().mockReturnThis(),
            val: jest.fn().mockReturnThis(),
            text: jest.fn().mockReturnThis(),
            html: jest.fn().mockReturnThis(),
            attr: jest.fn().mockReturnThis(),
            data: jest.fn().mockReturnThis(),
            addClass: jest.fn().mockReturnThis(),
            removeClass: jest.fn().mockReturnThis(),
            hasClass: jest.fn(() => false),
            show: jest.fn().mockReturnThis(),
            hide: jest.fn().mockReturnThis(),
            fadeIn: jest.fn().mockReturnThis(),
            fadeOut: jest.fn().mockReturnThis(),
        };
    },

    /**
     * Mock successful AJAX response
     */
    mockAjaxSuccess: (data = {}) => ({
        success: true,
        data: data,
    }),

    /**
     * Mock failed AJAX response
     */
    mockAjaxError: (message = 'Test error') => ({
        success: false,
        data: {
            message: message,
        },
    }),

    /**
     * Mock API response
     */
    mockApiResponse: (data, status = 200) => ({
        ok: status >= 200 && status < 300,
        status: status,
        json: jest.fn().mockResolvedValue(data),
        text: jest.fn().mockResolvedValue(JSON.stringify(data)),
    }),

    /**
     * Wait for promises to resolve
     */
    flushPromises: () => new Promise(resolve => setImmediate(resolve)),
};

// Cleanup after each test
afterEach(() => {
    // Reset all mocks
    jest.clearAllMocks();
    
    // Reset fetch mock
    if (global.fetch.mockClear) {
        global.fetch.mockClear();
    }
    
    // Clear DOM
    document.body.innerHTML = '';
    
    // Reset WordPress globals
    global.wp.hooks.addAction.mockClear?.();
    global.wp.hooks.addFilter.mockClear?.();
    global.wp.hooks.doAction.mockClear?.();
    global.wp.ajax.post.mockClear?.();
});

// Global error handler for unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
    console.error('Unhandled Rejection at:', promise, 'reason:', reason);
});