/**
 * SkyLearn Billing Pro - Service Worker
 * Provides offline functionality and caching for PWA features
 */

const CACHE_NAME = 'skylearn-billing-pro-v1.0.0';
const CACHE_EXPIRY_TIME = 24 * 60 * 60 * 1000; // 24 hours

// Files to cache for offline use
const STATIC_CACHE_URLS = [
    // Core plugin files
    'css/user-dashboard.css',
    'js/user-dashboard.js',
    
    // Admin assets (for admin PWA)
    '../admin/css/admin-style.css',
    '../admin/js/admin-script.js',
    
    // Essential images
    '../assets/images/icon-192x192.png',
    '../assets/images/icon-512x512.png',
    
    // Fallback page
    'offline.html'
];

// Dynamic cache patterns for API responses
const DYNAMIC_CACHE_PATTERNS = [
    new RegExp('/wp-admin/admin-ajax.php\\?action=slbp_'),
    new RegExp('/wp-json/skylearn-billing-pro/v1/')
];

/**
 * Service Worker Installation
 */
self.addEventListener('install', (event) => {
    console.log('SkyLearn Billing Pro Service Worker installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Caching static assets...');
                return cache.addAll(STATIC_CACHE_URLS.map(url => {
                    // Convert relative URLs to absolute
                    if (url.startsWith('../')) {
                        return new URL(url, self.location.href).href;
                    }
                    return url;
                }));
            })
            .catch((error) => {
                console.error('Failed to cache static assets:', error);
            })
    );
    
    // Force activation of new service worker
    self.skipWaiting();
});

/**
 * Service Worker Activation
 */
self.addEventListener('activate', (event) => {
    console.log('SkyLearn Billing Pro Service Worker activating...');
    
    event.waitUntil(
        Promise.all([
            // Clean up old caches
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter(cacheName => cacheName.startsWith('skylearn-billing-pro-') && cacheName !== CACHE_NAME)
                        .map(cacheName => caches.delete(cacheName))
                );
            }),
            
            // Take control of all clients
            self.clients.claim()
        ])
    );
});

/**
 * Fetch Event Handler
 * Implements caching strategies for different types of requests
 */
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip Chrome extension requests
    if (url.protocol === 'chrome-extension:') {
        return;
    }
    
    // Handle different types of requests
    if (isStaticAsset(request)) {
        event.respondWith(handleStaticAsset(request));
    } else if (isAPIRequest(request)) {
        event.respondWith(handleAPIRequest(request));
    } else if (isNavigationRequest(request)) {
        event.respondWith(handleNavigationRequest(request));
    }
});

/**
 * Check if request is for a static asset
 */
function isStaticAsset(request) {
    const url = new URL(request.url);
    const pathname = url.pathname;
    
    return pathname.endsWith('.css') ||
           pathname.endsWith('.js') ||
           pathname.endsWith('.png') ||
           pathname.endsWith('.jpg') ||
           pathname.endsWith('.jpeg') ||
           pathname.endsWith('.gif') ||
           pathname.endsWith('.svg') ||
           pathname.endsWith('.woff') ||
           pathname.endsWith('.woff2');
}

/**
 * Check if request is for API data
 */
function isAPIRequest(request) {
    const url = new URL(request.url);
    
    return DYNAMIC_CACHE_PATTERNS.some(pattern => pattern.test(url.href)) ||
           url.pathname.includes('admin-ajax.php') ||
           url.pathname.includes('/wp-json/');
}

/**
 * Check if request is for navigation
 */
function isNavigationRequest(request) {
    return request.mode === 'navigate' ||
           (request.method === 'GET' && request.headers.get('accept').includes('text/html'));
}

/**
 * Handle static asset requests with cache-first strategy
 */
async function handleStaticAsset(request) {
    try {
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            // Check if cache is expired
            const cacheDate = cachedResponse.headers.get('sw-cache-date');
            if (cacheDate && (Date.now() - parseInt(cacheDate)) > CACHE_EXPIRY_TIME) {
                // Cache expired, try to fetch new version
                try {
                    const networkResponse = await fetch(request);
                    if (networkResponse.ok) {
                        await updateCache(request, networkResponse.clone());
                        return networkResponse;
                    }
                } catch (error) {
                    console.warn('Network fetch failed, using cached version:', error);
                }
            }
            
            return cachedResponse;
        }
        
        // Not in cache, fetch from network
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            await updateCache(request, networkResponse.clone());
        }
        return networkResponse;
        
    } catch (error) {
        console.error('Failed to handle static asset:', error);
        return new Response('Asset not available offline', { status: 503 });
    }
}

/**
 * Handle API requests with network-first strategy
 */
async function handleAPIRequest(request) {
    try {
        // Try network first for fresh data
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache successful API responses
            await updateCache(request, networkResponse.clone());
            return networkResponse;
        }
        
        // Network failed, try cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            // Add offline indicator to cached response
            const modifiedResponse = new Response(cachedResponse.body, {
                status: cachedResponse.status,
                statusText: cachedResponse.statusText,
                headers: {
                    ...Object.fromEntries(cachedResponse.headers.entries()),
                    'X-Served-From': 'cache-offline'
                }
            });
            return modifiedResponse;
        }
        
        return networkResponse;
        
    } catch (error) {
        console.error('API request failed:', error);
        
        // Try to serve from cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return new Response(cachedResponse.body, {
                status: cachedResponse.status,
                statusText: cachedResponse.statusText,
                headers: {
                    ...Object.fromEntries(cachedResponse.headers.entries()),
                    'X-Served-From': 'cache-offline'
                }
            });
        }
        
        // Return offline response
        return new Response(
            JSON.stringify({
                error: 'This feature is not available offline',
                offline: true
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

/**
 * Handle navigation requests
 */
async function handleNavigationRequest(request) {
    try {
        const networkResponse = await fetch(request);
        return networkResponse;
    } catch (error) {
        console.error('Navigation request failed:', error);
        
        // Try to serve cached offline page
        const offlineResponse = await caches.match('offline.html');
        if (offlineResponse) {
            return offlineResponse;
        }
        
        // Fallback offline response
        return new Response(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Offline - SkyLearn Billing Pro</title>
                <style>
                    body { 
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        text-align: center; 
                        padding: 50px;
                        background: #f6f7f7;
                    }
                    .container {
                        max-width: 400px;
                        margin: 0 auto;
                        background: white;
                        padding: 40px;
                        border-radius: 8px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }
                    h1 { color: #6366f1; margin-bottom: 20px; }
                    p { color: #646970; line-height: 1.6; }
                    .retry-btn {
                        background: #6366f1;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 4px;
                        cursor: pointer;
                        margin-top: 20px;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>You're Offline</h1>
                    <p>SkyLearn Billing Pro is not available while offline. Please check your internet connection and try again.</p>
                    <button class="retry-btn" onclick="location.reload()">Retry</button>
                </div>
            </body>
            </html>
        `, {
            status: 200,
            headers: { 'Content-Type': 'text/html' }
        });
    }
}

/**
 * Update cache with timestamp
 */
async function updateCache(request, response) {
    try {
        const cache = await caches.open(CACHE_NAME);
        
        // Add timestamp to response headers
        const responseToCache = new Response(response.body, {
            status: response.status,
            statusText: response.statusText,
            headers: {
                ...Object.fromEntries(response.headers.entries()),
                'sw-cache-date': Date.now().toString()
            }
        });
        
        await cache.put(request, responseToCache);
    } catch (error) {
        console.error('Failed to update cache:', error);
    }
}

/**
 * Background Sync for offline actions
 */
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync-billing') {
        event.waitUntil(syncBillingData());
    }
});

/**
 * Sync billing data when connection is restored
 */
async function syncBillingData() {
    try {
        // Get pending sync data from IndexedDB or localStorage
        const pendingData = await getPendingSyncData();
        
        for (const item of pendingData) {
            try {
                await fetch(item.url, {
                    method: item.method,
                    headers: item.headers,
                    body: item.body
                });
                
                // Remove successfully synced item
                await removeSyncData(item.id);
            } catch (error) {
                console.error('Failed to sync item:', error);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

/**
 * Get pending sync data (placeholder - would need IndexedDB implementation)
 */
async function getPendingSyncData() {
    // This would typically read from IndexedDB
    return [];
}

/**
 * Remove synced data (placeholder)
 */
async function removeSyncData(id) {
    // This would typically remove from IndexedDB
    console.log('Removing synced data:', id);
}

/**
 * Handle push notifications (if needed in future)
 */
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        
        const title = data.title || 'SkyLearn Billing Pro';
        const options = {
            body: data.body,
            icon: '../assets/images/icon-192x192.png',
            badge: '../assets/images/badge-icon.png',
            tag: 'skylearn-billing-notification',
            requireInteraction: true,
            actions: [
                {
                    action: 'view',
                    title: 'View Details'
                },
                {
                    action: 'dismiss',
                    title: 'Dismiss'
                }
            ]
        };
        
        event.waitUntil(
            self.registration.showNotification(title, options)
        );
    }
});

/**
 * Handle notification clicks
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            self.clients.openWindow('/wp-admin/admin.php?page=skylearn-billing-pro')
        );
    }
});

console.log('SkyLearn Billing Pro Service Worker loaded successfully');