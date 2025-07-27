# Developer Guide: Extending Mobile Support

This guide provides comprehensive documentation for developers who want to extend or customize the mobile features of SkyLearn Billing Pro.

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Mobile-First CSS Development](#mobile-first-css-development)
3. [JavaScript Mobile Enhancements](#javascript-mobile-enhancements)
4. [PWA Development](#pwa-development)
5. [Accessibility Implementation](#accessibility-implementation)
6. [API Development for Mobile](#api-development-for-mobile)
7. [Testing Mobile Features](#testing-mobile-features)
8. [Performance Optimization](#performance-optimization)
9. [Custom Mobile Components](#custom-mobile-components)
10. [Advanced Features](#advanced-features)

## Architecture Overview

### Mobile Support Structure
```
skylearn-billing-pro/
├── public/
│   ├── css/
│   │   └── user-dashboard.css      # Mobile-first responsive styles
│   ├── js/
│   │   └── user-dashboard.js       # Mobile enhancements
│   ├── manifest.json               # PWA manifest
│   ├── sw.js                      # Service worker
│   └── offline.html               # Offline fallback page
├── admin/
│   ├── css/
│   │   └── admin-style.css        # Admin mobile styles
│   └── js/
│       └── admin-script.js        # Admin mobile features
├── examples/mobile/               # Mobile integration examples
└── includes/core/
    └── class-slbp-plugin.php      # PWA registration
```

### Key Design Principles
1. **Mobile-First**: CSS and JavaScript written for mobile, enhanced for desktop
2. **Progressive Enhancement**: Base functionality works everywhere, enhanced features layer on top
3. **Touch-First**: All interactions optimized for touch devices
4. **Accessibility-First**: WCAG 2.1 AA compliance built in from the start
5. **Performance-First**: Optimized for slow networks and older devices

## Mobile-First CSS Development

### CSS Custom Properties for Mobile
```css
:root {
    /* Mobile-first spacing scale */
    --space-xs: 0.25rem;   /* 4px */
    --space-sm: 0.5rem;    /* 8px */
    --space-md: 1rem;      /* 16px */
    --space-lg: 1.5rem;    /* 24px */
    --space-xl: 2rem;      /* 32px */
    --space-2xl: 3rem;     /* 48px */
    
    /* Touch-friendly sizing */
    --touch-target-min: 44px;
    --touch-target-recommended: 48px;
    
    /* Mobile-optimized typography */
    --text-xs: clamp(0.75rem, 2vw, 0.875rem);
    --text-sm: clamp(0.875rem, 2.5vw, 1rem);
    --text-base: clamp(1rem, 3vw, 1.125rem);
    --text-lg: clamp(1.125rem, 4vw, 1.25rem);
    --text-xl: clamp(1.25rem, 5vw, 1.5rem);
    
    /* Responsive border radius */
    --radius-sm: clamp(0.25rem, 1vw, 0.375rem);
    --radius-md: clamp(0.375rem, 1.5vw, 0.5rem);
    --radius-lg: clamp(0.5rem, 2vw, 0.75rem);
}
```

### Mobile-First Media Query Strategy
```css
/* Base styles: Mobile first (320px+) */
.component {
    display: flex;
    flex-direction: column;
    padding: var(--space-md);
}

/* Small mobile optimizations (360px+) */
@media (min-width: 360px) {
    .component {
        padding: var(--space-lg);
    }
}

/* Large mobile (480px+) */
@media (min-width: 480px) {
    .component {
        flex-direction: row;
        gap: var(--space-lg);
    }
}

/* Tablet (768px+) */
@media (min-width: 768px) {
    .component {
        padding: var(--space-xl);
    }
}

/* Desktop (1024px+) */
@media (min-width: 1024px) {
    .component {
        max-width: 1200px;
        margin: 0 auto;
    }
}
```

### Touch-Friendly Component Patterns
```css
/* Touch target sizing */
.touch-target {
    min-height: var(--touch-target-min);
    min-width: var(--touch-target-min);
    padding: var(--space-md);
    
    /* Improve touch accuracy */
    position: relative;
}

.touch-target::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    min-height: var(--touch-target-recommended);
    min-width: var(--touch-target-recommended);
    transform: translate(-50%, -50%);
    
    /* Debug: Uncomment to visualize touch areas */
    /* background: rgba(255, 0, 0, 0.2); */
}

/* Touch feedback */
.touch-feedback {
    transition: transform 0.1s ease;
    user-select: none;
}

.touch-feedback:active {
    transform: scale(0.98);
}

/* Mobile-optimized forms */
.mobile-form input,
.mobile-form select,
.mobile-form textarea {
    min-height: var(--touch-target-min);
    font-size: 16px; /* Prevents zoom on iOS */
    border-radius: var(--radius-md);
    padding: var(--space-md);
}
```

## JavaScript Mobile Enhancements

### Mobile Detection and Feature Support
```javascript
const MobileUtils = {
    // Device detection
    isMobile: () => /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
    isIOS: () => /iPad|iPhone|iPod/.test(navigator.userAgent),
    isAndroid: () => /Android/.test(navigator.userAgent),
    
    // Capability detection
    isTouchDevice: () => 'ontouchstart' in window || navigator.maxTouchPoints > 0,
    isStandalone: () => window.matchMedia('(display-mode: standalone)').matches,
    supportsServiceWorker: () => 'serviceWorker' in navigator,
    supportsNotifications: () => 'Notification' in window,
    
    // Viewport utilities
    getViewportWidth: () => Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0),
    getViewportHeight: () => Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0),
    
    // Orientation
    getOrientation: () => {
        if (screen.orientation) {
            return screen.orientation.angle === 0 || screen.orientation.angle === 180 ? 'portrait' : 'landscape';
        }
        return window.innerWidth > window.innerHeight ? 'landscape' : 'portrait';
    }
};
```

### Touch Gesture Handling
```javascript
class TouchGestureHandler {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            swipeThreshold: 50,
            tapTimeout: 300,
            longPressTimeout: 500,
            ...options
        };
        
        this.startX = 0;
        this.startY = 0;
        this.startTime = 0;
        
        this.bindEvents();
    }
    
    bindEvents() {
        this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        this.element.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: true });
    }
    
    handleTouchStart(e) {
        const touch = e.touches[0];
        this.startX = touch.clientX;
        this.startY = touch.clientY;
        this.startTime = Date.now();
        
        // Long press detection
        this.longPressTimer = setTimeout(() => {
            this.triggerEvent('longpress', { x: this.startX, y: this.startY });
        }, this.options.longPressTimeout);
    }
    
    handleTouchMove(e) {
        clearTimeout(this.longPressTimer);
    }
    
    handleTouchEnd(e) {
        clearTimeout(this.longPressTimer);
        
        const touch = e.changedTouches[0];
        const endX = touch.clientX;
        const endY = touch.clientY;
        const endTime = Date.now();
        
        const deltaX = endX - this.startX;
        const deltaY = endY - this.startY;
        const deltaTime = endTime - this.startTime;
        
        // Tap detection
        if (Math.abs(deltaX) < 10 && Math.abs(deltaY) < 10 && deltaTime < this.options.tapTimeout) {
            this.triggerEvent('tap', { x: endX, y: endY });
            return;
        }
        
        // Swipe detection
        if (deltaTime < 300 && (Math.abs(deltaX) > this.options.swipeThreshold || Math.abs(deltaY) > this.options.swipeThreshold)) {
            const direction = Math.abs(deltaX) > Math.abs(deltaY) 
                ? (deltaX > 0 ? 'right' : 'left')
                : (deltaY > 0 ? 'down' : 'up');
                
            this.triggerEvent('swipe', { direction, deltaX, deltaY });
        }
    }
    
    triggerEvent(type, data) {
        const event = new CustomEvent(`gesture${type}`, { detail: data });
        this.element.dispatchEvent(event);
    }
}

// Usage
const dashboard = document.querySelector('.slbp-user-dashboard');
const gestureHandler = new TouchGestureHandler(dashboard);

dashboard.addEventListener('gestureswipe', (e) => {
    if (e.detail.direction === 'left') {
        // Navigate to next section
    }
});
```

### Mobile Navigation Implementation
```javascript
class MobileNavigation {
    constructor() {
        this.nav = document.querySelector('.dashboard-nav');
        this.toggle = document.querySelector('.slbp-mobile-nav-toggle');
        this.overlay = document.querySelector('.slbp-mobile-overlay');
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        this.createMobileElements();
        this.bindEvents();
        this.handleResize();
    }
    
    createMobileElements() {
        if (!this.toggle && window.innerWidth <= 768) {
            // Create mobile toggle button
            this.toggle = document.createElement('button');
            this.toggle.className = 'slbp-mobile-nav-toggle';
            this.toggle.innerHTML = `
                <span class="slbp-sr-only">Toggle navigation menu</span>
                ☰
            `;
            this.toggle.setAttribute('aria-expanded', 'false');
            this.toggle.setAttribute('aria-controls', 'mobile-navigation');
            
            // Create overlay
            this.overlay = document.createElement('div');
            this.overlay.className = 'slbp-mobile-overlay';
            
            document.body.appendChild(this.toggle);
            document.body.appendChild(this.overlay);
        }
    }
    
    bindEvents() {
        if (this.toggle) {
            this.toggle.addEventListener('click', () => this.toggle());
        }
        
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.close());
        }
        
        // Keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
        
        // Window resize
        window.addEventListener('resize', this.debounce(() => this.handleResize(), 250));
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        this.isOpen = true;
        this.nav?.classList.add('mobile-open');
        this.overlay?.classList.add('active');
        this.toggle?.classList.add('active');
        this.toggle?.setAttribute('aria-expanded', 'true');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Focus first nav item
        const firstNavItem = this.nav?.querySelector('a, button');
        firstNavItem?.focus();
        
        // Trap focus
        this.trapFocus();
    }
    
    close() {
        this.isOpen = false;
        this.nav?.classList.remove('mobile-open');
        this.overlay?.classList.remove('active');
        this.toggle?.classList.remove('active');
        this.toggle?.setAttribute('aria-expanded', 'false');
        
        // Restore body scroll
        document.body.style.overflow = '';
        
        // Return focus to toggle
        this.toggle?.focus();
    }
    
    trapFocus() {
        const focusableElements = this.nav?.querySelectorAll(
            'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (!focusableElements?.length) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        const trapFocusHandler = (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        };
        
        document.addEventListener('keydown', trapFocusHandler);
        
        // Remove listener when nav closes
        const removeListener = () => {
            document.removeEventListener('keydown', trapFocusHandler);
        };
        
        this.nav?.addEventListener('transitionend', removeListener, { once: true });
    }
    
    handleResize() {
        const windowWidth = window.innerWidth;
        
        if (windowWidth > 768) {
            this.close();
            this.toggle?.remove();
            this.overlay?.remove();
        } else if (!this.toggle) {
            this.createMobileElements();
        }
    }
    
    debounce(func, wait) {
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
}

// Initialize mobile navigation
document.addEventListener('DOMContentLoaded', () => {
    new MobileNavigation();
});
```

## PWA Development

### Service Worker Implementation
```javascript
// sw.js - Advanced Service Worker Example
const CACHE_NAME = 'slbp-v1.0.0';
const OFFLINE_URL = '/offline.html';

// Cache strategies
const CACHE_STRATEGIES = {
    CACHE_FIRST: 'cache-first',
    NETWORK_FIRST: 'network-first',
    STALE_WHILE_REVALIDATE: 'stale-while-revalidate'
};

// Resource configurations
const RESOURCE_CONFIG = {
    '.css': { strategy: CACHE_STRATEGIES.CACHE_FIRST, maxAge: 86400000 }, // 24 hours
    '.js': { strategy: CACHE_STRATEGIES.CACHE_FIRST, maxAge: 86400000 },
    '.png': { strategy: CACHE_STRATEGIES.CACHE_FIRST, maxAge: 604800000 }, // 7 days
    '.jpg': { strategy: CACHE_STRATEGIES.CACHE_FIRST, maxAge: 604800000 },
    'api/': { strategy: CACHE_STRATEGIES.NETWORK_FIRST, maxAge: 300000 }, // 5 minutes
    'wp-json/': { strategy: CACHE_STRATEGIES.STALE_WHILE_REVALIDATE, maxAge: 600000 } // 10 minutes
};

class ServiceWorkerManager {
    constructor() {
        this.cache = null;
        this.init();
    }
    
    async init() {
        self.addEventListener('install', this.handleInstall.bind(this));
        self.addEventListener('activate', this.handleActivate.bind(this));
        self.addEventListener('fetch', this.handleFetch.bind(this));
        self.addEventListener('sync', this.handleBackgroundSync.bind(this));
        self.addEventListener('push', this.handlePushNotification.bind(this));
    }
    
    async handleInstall(event) {
        event.waitUntil(
            caches.open(CACHE_NAME).then(async (cache) => {
                this.cache = cache;
                
                // Cache essential resources
                const essentialResources = [
                    '/',
                    '/css/user-dashboard.css',
                    '/js/user-dashboard.js',
                    OFFLINE_URL
                ];
                
                return cache.addAll(essentialResources);
            })
        );
        
        self.skipWaiting();
    }
    
    async handleActivate(event) {
        event.waitUntil(
            Promise.all([
                // Clean up old caches
                caches.keys().then((cacheNames) => {
                    return Promise.all(
                        cacheNames
                            .filter(cacheName => cacheName !== CACHE_NAME)
                            .map(cacheName => caches.delete(cacheName))
                    );
                }),
                
                // Take control of all clients
                self.clients.claim()
            ])
        );
    }
    
    async handleFetch(event) {
        if (event.request.method !== 'GET') return;
        
        const url = new URL(event.request.url);
        const strategy = this.getStrategy(url.pathname);
        
        event.respondWith(this.executeStrategy(event.request, strategy));
    }
    
    getStrategy(pathname) {
        for (const [pattern, config] of Object.entries(RESOURCE_CONFIG)) {
            if (pathname.includes(pattern)) {
                return config;
            }
        }
        
        return { strategy: CACHE_STRATEGIES.NETWORK_FIRST, maxAge: 300000 };
    }
    
    async executeStrategy(request, config) {
        switch (config.strategy) {
            case CACHE_STRATEGIES.CACHE_FIRST:
                return this.cacheFirst(request, config);
            case CACHE_STRATEGIES.NETWORK_FIRST:
                return this.networkFirst(request, config);
            case CACHE_STRATEGIES.STALE_WHILE_REVALIDATE:
                return this.staleWhileRevalidate(request, config);
            default:
                return fetch(request);
        }
    }
    
    async cacheFirst(request, config) {
        const cache = await caches.open(CACHE_NAME);
        const cached = await cache.match(request);
        
        if (cached) {
            // Check if cached response is still fresh
            const cacheDate = cached.headers.get('sw-cache-date');
            if (cacheDate && (Date.now() - parseInt(cacheDate)) < config.maxAge) {
                return cached;
            }
        }
        
        try {
            const response = await fetch(request);
            if (response.ok) {
                await this.cacheResponse(cache, request, response.clone());
            }
            return response;
        } catch (error) {
            return cached || this.getOfflineResponse(request);
        }
    }
    
    async networkFirst(request, config) {
        const cache = await caches.open(CACHE_NAME);
        
        try {
            const response = await fetch(request);
            if (response.ok) {
                await this.cacheResponse(cache, request, response.clone());
            }
            return response;
        } catch (error) {
            const cached = await cache.match(request);
            return cached || this.getOfflineResponse(request);
        }
    }
    
    async staleWhileRevalidate(request, config) {
        const cache = await caches.open(CACHE_NAME);
        const cached = await cache.match(request);
        
        const fetchPromise = fetch(request).then((response) => {
            if (response.ok) {
                this.cacheResponse(cache, request, response.clone());
            }
            return response;
        });
        
        return cached || fetchPromise;
    }
    
    async cacheResponse(cache, request, response) {
        // Add timestamp to response
        const responseToCache = new Response(response.body, {
            status: response.status,
            statusText: response.statusText,
            headers: {
                ...Object.fromEntries(response.headers.entries()),
                'sw-cache-date': Date.now().toString()
            }
        });
        
        await cache.put(request, responseToCache);
    }
    
    getOfflineResponse(request) {
        if (request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
        }
        
        return new Response('Offline', { status: 503 });
    }
}

new ServiceWorkerManager();
```

### PWA Installation Handler
```javascript
class PWAInstaller {
    constructor() {
        this.deferredPrompt = null;
        this.installButton = null;
        
        this.init();
    }
    
    init() {
        this.createInstallButton();
        this.bindEvents();
    }
    
    createInstallButton() {
        this.installButton = document.createElement('button');
        this.installButton.className = 'slbp-install-app button button-primary';
        this.installButton.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-15"></path>
                <polyline points="7,10 12,15 17,10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            Install App
        `;
        this.installButton.style.display = 'none';
        
        // Add to quick actions if available
        const quickActions = document.querySelector('.action-buttons');
        if (quickActions) {
            quickActions.prepend(this.installButton);
        }
    }
    
    bindEvents() {
        // Install prompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });
        
        // Install button click
        this.installButton?.addEventListener('click', () => {
            this.promptInstall();
        });
        
        // App installed event
        window.addEventListener('appinstalled', () => {
            this.hideInstallButton();
            this.trackInstallation();
        });
    }
    
    showInstallButton() {
        if (this.installButton) {
            this.installButton.style.display = 'inline-flex';
        }
    }
    
    hideInstallButton() {
        if (this.installButton) {
            this.installButton.style.display = 'none';
        }
    }
    
    async promptInstall() {
        if (!this.deferredPrompt) return;
        
        this.deferredPrompt.prompt();
        
        const choiceResult = await this.deferredPrompt.userChoice;
        
        if (choiceResult.outcome === 'accepted') {
            console.log('User accepted the install prompt');
        }
        
        this.deferredPrompt = null;
        this.hideInstallButton();
    }
    
    trackInstallation() {
        // Track PWA installation
        if (typeof gtag === 'function') {
            gtag('event', 'pwa_install', {
                event_category: 'PWA',
                event_label: 'App Installed'
            });
        }
    }
}

// Initialize PWA installer
document.addEventListener('DOMContentLoaded', () => {
    new PWAInstaller();
});
```

## API Development for Mobile

### Mobile-Optimized API Responses
```php
<?php
/**
 * Mobile API Enhancements
 */
class SLBP_Mobile_API {
    
    /**
     * Register mobile-specific API endpoints
     */
    public static function register_routes() {
        register_rest_route('skylearn-billing-pro/v1', '/mobile/dashboard', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_mobile_dashboard'],
            'permission_callback' => [self::class, 'check_permissions']
        ]);
        
        register_rest_route('skylearn-billing-pro/v1', '/mobile/sync', [
            'methods' => 'POST',
            'callback' => [self::class, 'sync_mobile_data'],
            'permission_callback' => [self::class, 'check_permissions']
        ]);
    }
    
    /**
     * Get optimized dashboard data for mobile
     */
    public static function get_mobile_dashboard($request) {
        $user_id = get_current_user_id();
        
        // Optimize data for mobile consumption
        $dashboard_data = [
            'stats' => self::get_optimized_stats($user_id),
            'recent_transactions' => self::get_recent_transactions($user_id, 5), // Limit for mobile
            'active_subscriptions' => self::get_active_subscriptions($user_id, 3),
            'cache_timestamp' => time(),
            'offline_capabilities' => self::get_offline_capabilities()
        ];
        
        // Add mobile-specific metadata
        $response = new WP_REST_Response($dashboard_data);
        $response->header('Cache-Control', 'public, max-age=300'); // 5 minutes
        $response->header('X-Mobile-Optimized', 'true');
        
        return $response;
    }
    
    /**
     * Get optimized stats for mobile
     */
    private static function get_optimized_stats($user_id) {
        return [
            'total_spent' => number_format(self::calculate_total_spent($user_id), 2),
            'active_subscriptions' => self::count_active_subscriptions($user_id),
            'enrolled_courses' => self::count_enrolled_courses($user_id),
            'completion_rate' => self::calculate_completion_rate($user_id) . '%'
        ];
    }
    
    /**
     * Sync data for offline-first mobile apps
     */
    public static function sync_mobile_data($request) {
        $sync_data = $request->get_json_params();
        $user_id = get_current_user_id();
        
        $results = [
            'synced_items' => 0,
            'failed_items' => 0,
            'conflicts' => []
        ];
        
        foreach ($sync_data['items'] as $item) {
            try {
                self::process_sync_item($user_id, $item);
                $results['synced_items']++;
            } catch (Exception $e) {
                $results['failed_items']++;
                error_log('Mobile sync failed: ' . $e->getMessage());
            }
        }
        
        return new WP_REST_Response($results);
    }
    
    /**
     * Check API permissions
     */
    public static function check_permissions() {
        return is_user_logged_in();
    }
}

// Register mobile API routes
add_action('rest_api_init', ['SLBP_Mobile_API', 'register_routes']);
```

### Mobile-Specific Error Handling
```javascript
class MobileAPIClient {
    constructor(baseURL) {
        this.baseURL = baseURL;
        this.retryAttempts = 3;
        this.retryDelay = 1000;
    }
    
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Mobile-Client': 'true',
                ...options.headers
            }
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        for (let attempt = 1; attempt <= this.retryAttempts; attempt++) {
            try {
                const response = await fetch(url, finalOptions);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return await response.json();
            } catch (error) {
                if (attempt === this.retryAttempts) {
                    throw error;
                }
                
                // Exponential backoff
                await this.delay(this.retryDelay * Math.pow(2, attempt - 1));
            }
        }
    }
    
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    // Offline-first data fetching
    async getWithCache(endpoint, cacheKey) {
        try {
            // Try network first
            const data = await this.request(endpoint);
            
            // Cache successful response
            localStorage.setItem(cacheKey, JSON.stringify({
                data,
                timestamp: Date.now()
            }));
            
            return data;
        } catch (error) {
            // Fall back to cache
            const cached = localStorage.getItem(cacheKey);
            if (cached) {
                const { data, timestamp } = JSON.parse(cached);
                
                // Check if cache is still valid (1 hour)
                if (Date.now() - timestamp < 3600000) {
                    return { ...data, fromCache: true };
                }
            }
            
            throw error;
        }
    }
}
```

## Testing Mobile Features

### Automated Mobile Testing Setup
```javascript
// cypress/support/mobile-commands.js
Cypress.Commands.add('setMobileViewport', (device = 'iphone-x') => {
    const viewports = {
        'iphone-se': { width: 375, height: 667 },
        'iphone-x': { width: 375, height: 812 },
        'iphone-12-pro': { width: 390, height: 844 },
        'android': { width: 360, height: 640 },
        'tablet': { width: 768, height: 1024 }
    };
    
    const viewport = viewports[device];
    cy.viewport(viewport.width, viewport.height);
});

Cypress.Commands.add('testTouchTarget', (selector) => {
    cy.get(selector).then(($el) => {
        const { width, height } = $el[0].getBoundingClientRect();
        expect(width).to.be.at.least(44, 'Touch target width should be at least 44px');
        expect(height).to.be.at.least(44, 'Touch target height should be at least 44px');
    });
});

Cypress.Commands.add('testMobileNavigation', () => {
    cy.setMobileViewport('iphone-x');
    
    // Should show mobile menu toggle
    cy.get('.slbp-mobile-nav-toggle').should('be.visible');
    
    // Should open menu when clicked
    cy.get('.slbp-mobile-nav-toggle').click();
    cy.get('.dashboard-nav').should('have.class', 'mobile-open');
    
    // Should close menu with overlay click
    cy.get('.slbp-mobile-overlay').click();
    cy.get('.dashboard-nav').should('not.have.class', 'mobile-open');
    
    // Should close menu with escape key
    cy.get('.slbp-mobile-nav-toggle').click();
    cy.get('body').type('{esc}');
    cy.get('.dashboard-nav').should('not.have.class', 'mobile-open');
});

// Mobile test example
describe('Mobile Dashboard', () => {
    beforeEach(() => {
        cy.login();
        cy.setMobileViewport('iphone-x');
        cy.visit('/dashboard');
    });
    
    it('should display mobile-optimized layout', () => {
        // Test mobile navigation
        cy.testMobileNavigation();
        
        // Test stats cards stack vertically
        cy.get('.stats-grid').should('have.css', 'grid-template-columns', '1fr');
        
        // Test touch targets
        cy.get('.button').each(($btn) => {
            cy.testTouchTarget($btn);
        });
    });
    
    it('should work offline', () => {
        // Go offline
        cy.goOffline();
        
        // Should show offline indicator
        cy.get('.slbp-offline-indicator').should('be.visible');
        
        // Should still display cached content
        cy.get('.dashboard-content').should('be.visible');
        
        // Go back online
        cy.goOnline();
        
        // Should hide offline indicator
        cy.get('.slbp-offline-indicator').should('not.exist');
    });
});
```

## Performance Optimization

### Mobile Performance Checklist
```javascript
// Performance monitoring for mobile
class MobilePerformanceMonitor {
    constructor() {
        this.metrics = {};
        this.init();
    }
    
    init() {
        this.measureCoreWebVitals();
        this.measureCustomMetrics();
        this.setupPerformanceObserver();
    }
    
    measureCoreWebVitals() {
        // Largest Contentful Paint
        new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                this.metrics.lcp = entry.startTime;
                this.reportMetric('lcp', entry.startTime);
            }
        }).observe({ entryTypes: ['largest-contentful-paint'] });
        
        // First Input Delay
        new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                this.metrics.fid = entry.processingStart - entry.startTime;
                this.reportMetric('fid', this.metrics.fid);
            }
        }).observe({ entryTypes: ['first-input'] });
        
        // Cumulative Layout Shift
        let clsValue = 0;
        new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
            this.metrics.cls = clsValue;
            this.reportMetric('cls', clsValue);
        }).observe({ entryTypes: ['layout-shift'] });
    }
    
    measureCustomMetrics() {
        // Time to Interactive
        this.measureTTI();
        
        // Mobile-specific metrics
        this.measureBatteryLevel();
        this.measureNetworkSpeed();
    }
    
    async measureTTI() {
        // Simplified TTI measurement
        return new Promise((resolve) => {
            let startTime = performance.now();
            
            const checkInteractive = () => {
                const longTasks = performance.getEntriesByType('longtask');
                const lastLongTask = longTasks[longTasks.length - 1];
                
                if (!lastLongTask || performance.now() - lastLongTask.startTime > 5000) {
                    const tti = performance.now() - startTime;
                    this.metrics.tti = tti;
                    this.reportMetric('tti', tti);
                    resolve(tti);
                } else {
                    setTimeout(checkInteractive, 1000);
                }
            };
            
            setTimeout(checkInteractive, 1000);
        });
    }
    
    async measureBatteryLevel() {
        if ('getBattery' in navigator) {
            const battery = await navigator.getBattery();
            this.metrics.batteryLevel = battery.level;
            
            battery.addEventListener('levelchange', () => {
                this.metrics.batteryLevel = battery.level;
            });
        }
    }
    
    measureNetworkSpeed() {
        if ('connection' in navigator) {
            const connection = navigator.connection;
            this.metrics.networkType = connection.effectiveType;
            this.metrics.downlink = connection.downlink;
            
            connection.addEventListener('change', () => {
                this.metrics.networkType = connection.effectiveType;
                this.metrics.downlink = connection.downlink;
            });
        }
    }
    
    reportMetric(name, value) {
        // Report to analytics
        if (typeof gtag === 'function') {
            gtag('event', 'mobile_performance', {
                event_category: 'Performance',
                event_label: name,
                value: Math.round(value)
            });
        }
        
        // Log performance issues
        if (this.isPerformanceIssue(name, value)) {
            console.warn(`Performance issue detected: ${name} = ${value}`);
        }
    }
    
    isPerformanceIssue(name, value) {
        const thresholds = {
            lcp: 2500,  // 2.5 seconds
            fid: 100,   // 100 milliseconds
            cls: 0.1,   // 0.1
            tti: 3500   // 3.5 seconds
        };
        
        return value > thresholds[name];
    }
}

// Initialize performance monitoring
if (MobileUtils.isMobile()) {
    new MobilePerformanceMonitor();
}
```

This developer guide provides a comprehensive foundation for extending mobile support in SkyLearn Billing Pro. Each section includes practical examples and best practices for building mobile-first, accessible, and performant features.