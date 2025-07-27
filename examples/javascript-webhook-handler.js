const express = require('express');
const crypto = require('crypto');
const bodyParser = require('body-parser');

/**
 * SkyLearn Billing Pro Webhook Handler Example (Node.js)
 * 
 * This example demonstrates how to handle webhooks from SkyLearn Billing Pro
 * using Express.js.
 * 
 * Install dependencies:
 * npm install express body-parser
 */

const app = express();
const PORT = process.env.PORT || 3000;

// Your webhook secret (set when creating the webhook)
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET || 'your_webhook_secret_here';

// Use raw body parser for webhook signature verification
app.use('/webhook', bodyParser.raw({ type: 'application/json' }));
app.use(bodyParser.json());

/**
 * Verify webhook signature
 */
function verifySignature(payload, signature, secret) {
    const expectedSignature = 'sha256=' + crypto
        .createHmac('sha256', secret)
        .update(payload)
        .digest('hex');
    
    return crypto.timingSafeEqual(
        Buffer.from(signature),
        Buffer.from(expectedSignature)
    );
}

/**
 * Handle webhook events
 */
app.post('/webhook', (req, res) => {
    const signature = req.get('X-SLBP-Signature') || '';
    const event = req.get('X-SLBP-Event') || '';
    const deliveryId = req.get('X-SLBP-Delivery') || '';
    
    console.log(`Received webhook: ${event} (${deliveryId})`);
    
    // Verify the signature
    if (!verifySignature(req.body, signature, WEBHOOK_SECRET)) {
        console.error('Invalid webhook signature');
        return res.status(401).send('Unauthorized');
    }
    
    try {
        // Parse the payload
        const payload = JSON.parse(req.body.toString());
        
        // Handle different event types
        switch (event) {
            case 'payment_success':
                handlePaymentSuccess(payload.data);
                break;
                
            case 'subscription_created':
                handleSubscriptionCreated(payload.data);
                break;
                
            case 'subscription_cancelled':
                handleSubscriptionCancelled(payload.data);
                break;
                
            case 'enrollment_created':
                handleEnrollmentCreated(payload.data);
                break;
                
            case 'refund_processed':
                handleRefundProcessed(payload.data);
                break;
                
            case 'test':
                console.log('Test webhook received successfully');
                break;
                
            default:
                console.log(`Unhandled event type: ${event}`);
        }
        
        // Respond with 200 to acknowledge receipt
        res.status(200).send('OK');
        
    } catch (error) {
        console.error('Error processing webhook:', error);
        res.status(500).send('Internal Server Error');
    }
});

/**
 * Event handlers
 */

function handlePaymentSuccess(data) {
    console.log('Payment successful:', {
        transactionId: data.transaction_id,
        amount: data.amount,
        currency: data.currency,
        userId: data.user_id
    });
    
    // Your business logic here
    // e.g., send confirmation email, update external systems, etc.
}

function handleSubscriptionCreated(data) {
    console.log('New subscription created:', {
        subscriptionId: data.subscription_id,
        userId: data.user_id,
        planId: data.plan_id,
        status: data.status
    });
    
    // Your business logic here
    // e.g., send welcome email, activate features, etc.
}

function handleSubscriptionCancelled(data) {
    console.log('Subscription cancelled:', {
        subscriptionId: data.subscription_id,
        userId: data.user_id,
        reason: data.reason
    });
    
    // Your business logic here
    // e.g., schedule end-of-period processing, send cancellation email, etc.
}

function handleEnrollmentCreated(data) {
    console.log('User enrolled in course:', {
        userId: data.user_id,
        courseId: data.course_id,
        enrollmentDate: data.enrollment_date
    });
    
    // Your business logic here
    // e.g., sync with external LMS, send course materials, etc.
}

function handleRefundProcessed(data) {
    console.log('Refund processed:', {
        refundId: data.refund_id,
        transactionId: data.transaction_id,
        amount: data.amount,
        reason: data.reason
    });
    
    // Your business logic here
    // e.g., update accounting system, send refund confirmation, etc.
}

/**
 * Health check endpoint
 */
app.get('/health', (req, res) => {
    res.json({
        status: 'healthy',
        timestamp: new Date().toISOString(),
        uptime: process.uptime()
    });
});

/**
 * Root endpoint with info
 */
app.get('/', (req, res) => {
    res.json({
        name: 'SkyLearn Billing Pro Webhook Handler',
        version: '1.0.0',
        endpoints: {
            webhook: 'POST /webhook',
            health: 'GET /health'
        }
    });
});

/**
 * Error handler
 */
app.use((error, req, res, next) => {
    console.error('Unhandled error:', error);
    res.status(500).json({
        error: 'Internal Server Error',
        message: error.message
    });
});

// Start the server
app.listen(PORT, () => {
    console.log(`SkyLearn Billing Pro webhook handler listening on port ${PORT}`);
    console.log(`Webhook URL: http://localhost:${PORT}/webhook`);
    console.log(`Health check: http://localhost:${PORT}/health`);
});

module.exports = app;