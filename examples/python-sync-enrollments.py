#!/usr/bin/env python3
"""
SkyLearn Billing Pro Enrollment Sync Script

This Python script demonstrates how to sync course enrollments between
SkyLearn Billing Pro and external systems using the API.

Requirements:
pip install requests

Usage:
python python-sync-enrollments.py
"""

import os
import sys
import time
import requests
import json
from datetime import datetime, timedelta
from typing import List, Dict, Optional

class SkyLearnAPI:
    """SkyLearn Billing Pro API Client"""
    
    def __init__(self, base_url: str, api_key: str):
        self.base_url = base_url.rstrip('/') + '/wp-json/slbp/v1'
        self.api_key = api_key
        self.session = requests.Session()
        self.session.headers.update({
            'Authorization': f'Bearer {api_key}',
            'Content-Type': 'application/json',
            'User-Agent': 'SkyLearn-Sync-Script/1.0'
        })
    
    def _request(self, endpoint: str, method: str = 'GET', data: Optional[Dict] = None) -> Dict:
        """Make API request"""
        url = f"{self.base_url}/{endpoint.lstrip('/')}"
        
        try:
            if method == 'GET':
                response = self.session.get(url)
            elif method == 'POST':
                response = self.session.post(url, json=data)
            else:
                raise ValueError(f"Unsupported method: {method}")
            
            response.raise_for_status()
            return response.json()
            
        except requests.RequestException as e:
            print(f"API request failed: {e}")
            if hasattr(e, 'response') and e.response is not None:
                try:
                    error_data = e.response.json()
                    print(f"Error details: {error_data}")
                except:
                    print(f"Response text: {e.response.text}")
            raise
    
    def get_status(self) -> Dict:
        """Get API status"""
        return self._request('status')
    
    def get_transactions(self, params: Optional[Dict] = None) -> List[Dict]:
        """Get transactions with optional filters"""
        if params is None:
            params = {}
        
        query_string = '&'.join([f"{k}={v}" for k, v in params.items()])
        endpoint = f"billing/transactions?{query_string}" if query_string else "billing/transactions"
        
        return self._request(endpoint)
    
    def get_subscriptions(self, params: Optional[Dict] = None) -> List[Dict]:
        """Get subscriptions"""
        if params is None:
            params = {}
            
        query_string = '&'.join([f"{k}={v}" for k, v in params.items()])
        endpoint = f"subscriptions?{query_string}" if query_string else "subscriptions"
        
        return self._request(endpoint)

class EnrollmentSyncer:
    """Handles enrollment synchronization"""
    
    def __init__(self, api: SkyLearnAPI, external_system_url: str = None):
        self.api = api
        self.external_system_url = external_system_url
        self.processed_transactions = set()
    
    def sync_recent_enrollments(self, hours_back: int = 24) -> None:
        """Sync enrollments from the last N hours"""
        print(f"Syncing enrollments from the last {hours_back} hours...")
        
        # Calculate date range
        end_date = datetime.now()
        start_date = end_date - timedelta(hours=hours_back)
        
        # Get recent paid transactions
        params = {
            'status': 'paid',
            'after': start_date.isoformat(),
            'before': end_date.isoformat(),
            'per_page': 100
        }
        
        try:
            transactions = self.api.get_transactions(params)
            print(f"Found {len(transactions)} paid transactions")
            
            enrollments_processed = 0
            
            for transaction in transactions:
                if self.process_transaction(transaction):
                    enrollments_processed += 1
            
            print(f"Processed {enrollments_processed} enrollments")
            
        except Exception as e:
            print(f"Error syncing enrollments: {e}")
    
    def process_transaction(self, transaction: Dict) -> bool:
        """Process a single transaction for enrollment"""
        transaction_id = transaction.get('id')
        
        if transaction_id in self.processed_transactions:
            return False
        
        # Check if transaction has course association
        course_id = transaction.get('course_id')
        if not course_id:
            print(f"Transaction {transaction_id} has no course association")
            return False
        
        user_id = transaction.get('user_id')
        amount = transaction.get('amount')
        currency = transaction.get('currency', 'USD')
        
        print(f"Processing enrollment: User {user_id} -> Course {course_id} (Transaction: {transaction_id})")
        
        # Create enrollment record
        enrollment_data = {
            'user_id': user_id,
            'course_id': course_id,
            'transaction_id': transaction_id,
            'amount_paid': amount,
            'currency': currency,
            'enrollment_date': transaction.get('created_at'),
            'source': 'skylearn_billing_pro'
        }
        
        # Sync to external system if configured
        if self.external_system_url:
            self.sync_to_external_system(enrollment_data)
        
        # Log locally
        self.log_enrollment(enrollment_data)
        
        self.processed_transactions.add(transaction_id)
        return True
    
    def sync_to_external_system(self, enrollment_data: Dict) -> None:
        """Sync enrollment to external system"""
        try:
            response = requests.post(
                f"{self.external_system_url}/enrollments",
                json=enrollment_data,
                timeout=30
            )
            response.raise_for_status()
            print(f"✓ Synced to external system: User {enrollment_data['user_id']} -> Course {enrollment_data['course_id']}")
            
        except requests.RequestException as e:
            print(f"✗ Failed to sync to external system: {e}")
    
    def log_enrollment(self, enrollment_data: Dict) -> None:
        """Log enrollment locally"""
        timestamp = datetime.now().isoformat()
        log_entry = {
            'timestamp': timestamp,
            'action': 'enrollment_processed',
            'data': enrollment_data
        }
        
        # Write to log file
        log_file = 'enrollment_sync.log'
        with open(log_file, 'a') as f:
            f.write(json.dumps(log_entry) + '\n')
    
    def sync_subscription_enrollments(self) -> None:
        """Sync enrollments for active subscriptions"""
        print("Syncing subscription-based enrollments...")
        
        try:
            subscriptions = self.api.get_subscriptions({'status': 'active'})
            print(f"Found {len(subscriptions)} active subscriptions")
            
            for subscription in subscriptions:
                self.process_subscription(subscription)
                
        except Exception as e:
            print(f"Error syncing subscription enrollments: {e}")
    
    def process_subscription(self, subscription: Dict) -> None:
        """Process subscription for enrollment access"""
        subscription_id = subscription.get('id')
        user_id = subscription.get('user_id')
        plan_id = subscription.get('plan_id')
        
        print(f"Processing subscription {subscription_id} for user {user_id}")
        
        # Here you would map plan_id to course access
        # This is a simplified example
        course_mapping = {
            'basic_plan': [101, 102],
            'premium_plan': [101, 102, 103, 104],
            'enterprise_plan': [101, 102, 103, 104, 105, 106]
        }
        
        courses = course_mapping.get(plan_id, [])
        
        for course_id in courses:
            enrollment_data = {
                'user_id': user_id,
                'course_id': course_id,
                'subscription_id': subscription_id,
                'plan_id': plan_id,
                'access_type': 'subscription',
                'enrollment_date': subscription.get('created_at'),
                'source': 'skylearn_billing_pro_subscription'
            }
            
            self.log_enrollment(enrollment_data)
            
            if self.external_system_url:
                self.sync_to_external_system(enrollment_data)

def main():
    """Main function"""
    # Configuration
    BASE_URL = os.getenv('SKYLEARN_BASE_URL', 'https://yoursite.com')
    API_KEY = os.getenv('SKYLEARN_API_KEY', 'your_api_key_here')
    EXTERNAL_SYSTEM_URL = os.getenv('EXTERNAL_SYSTEM_URL')  # Optional
    
    if API_KEY == 'your_api_key_here':
        print("Error: Please set your API key in SKYLEARN_API_KEY environment variable")
        sys.exit(1)
    
    try:
        # Initialize API client
        api = SkyLearnAPI(BASE_URL, API_KEY)
        
        # Test connection
        print("Testing API connection...")
        status = api.get_status()
        print(f"✓ Connected to SkyLearn Billing Pro API v{status['version']}")
        
        # Initialize syncer
        syncer = EnrollmentSyncer(api, EXTERNAL_SYSTEM_URL)
        
        # Sync recent enrollments (last 24 hours)
        syncer.sync_recent_enrollments(hours_back=24)
        
        # Sync subscription enrollments
        syncer.sync_subscription_enrollments()
        
        print("✓ Enrollment sync completed successfully")
        
    except Exception as e:
        print(f"✗ Sync failed: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()