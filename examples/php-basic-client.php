<?php
/**
 * SkyLearn Billing Pro PHP API Client Example
 *
 * This example demonstrates how to interact with the SkyLearn Billing Pro API
 * using PHP and cURL.
 *
 * @author Skyian LLC
 * @link   https://skyianllc.com
 */

class SkyLearnBillingProAPI {
    
    private $base_url;
    private $api_key;
    
    public function __construct($base_url, $api_key) {
        $this->base_url = rtrim($base_url, '/') . '/wp-json/slbp/v1';
        $this->api_key = $api_key;
    }
    
    /**
     * Make API request
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->base_url . '/' . ltrim($endpoint, '/');
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'User-Agent: SkyLearn-API-Client/1.0'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if ($http_code >= 400) {
            $message = isset($decoded['message']) ? $decoded['message'] : 'API Error';
            throw new Exception("API Error ($http_code): " . $message);
        }
        
        return $decoded;
    }
    
    /**
     * Get API status
     */
    public function getStatus() {
        return $this->request('status');
    }
    
    /**
     * Get invoices
     */
    public function getInvoices($params = []) {
        $query = http_build_query($params);
        $endpoint = 'billing/invoices' . ($query ? '?' . $query : '');
        return $this->request($endpoint);
    }
    
    /**
     * Get specific invoice
     */
    public function getInvoice($invoice_id) {
        return $this->request("billing/invoices/{$invoice_id}");
    }
    
    /**
     * Get transactions
     */
    public function getTransactions($params = []) {
        $query = http_build_query($params);
        $endpoint = 'billing/transactions' . ($query ? '?' . $query : '');
        return $this->request($endpoint);
    }
    
    /**
     * Process refund
     */
    public function processRefund($transaction_id, $amount = null, $reason = 'Requested via API') {
        $data = [
            'transaction_id' => $transaction_id,
            'reason' => $reason
        ];
        
        if ($amount !== null) {
            $data['amount'] = $amount;
        }
        
        return $this->request('billing/refunds', 'POST', $data);
    }
    
    /**
     * Get subscriptions
     */
    public function getSubscriptions($params = []) {
        $query = http_build_query($params);
        $endpoint = 'subscriptions' . ($query ? '?' . $query : '');
        return $this->request($endpoint);
    }
    
    /**
     * Cancel subscription
     */
    public function cancelSubscription($subscription_id) {
        return $this->request("subscriptions/{$subscription_id}/cancel", 'POST');
    }
}

// Example usage
try {
    // Initialize the API client
    $api = new SkyLearnBillingProAPI('https://yoursite.com', 'YOUR_API_KEY');
    
    // Test API connection
    echo "Testing API connection...\n";
    $status = $api->getStatus();
    echo "API Status: " . $status['status'] . "\n";
    echo "API Version: " . $status['version'] . "\n\n";
    
    // Get recent invoices
    echo "Getting recent invoices...\n";
    $invoices = $api->getInvoices(['per_page' => 5]);
    echo "Found " . count($invoices) . " invoices\n";
    
    foreach ($invoices as $invoice) {
        echo "- Invoice {$invoice['id']}: {$invoice['currency']} {$invoice['amount']} ({$invoice['status']})\n";
    }
    echo "\n";
    
    // Get recent transactions
    echo "Getting recent transactions...\n";
    $transactions = $api->getTransactions(['per_page' => 5]);
    echo "Found " . count($transactions) . " transactions\n";
    
    foreach ($transactions as $transaction) {
        echo "- Transaction {$transaction['id']}: {$transaction['currency']} {$transaction['amount']} ({$transaction['status']})\n";
    }
    echo "\n";
    
    // Get subscriptions
    echo "Getting subscriptions...\n";
    $subscriptions = $api->getSubscriptions();
    echo "Found " . count($subscriptions) . " subscriptions\n";
    
    foreach ($subscriptions as $subscription) {
        echo "- Subscription {$subscription['id']}: {$subscription['status']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}