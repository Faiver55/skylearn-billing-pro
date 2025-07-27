#!/bin/bash

# SkyLearn Billing Pro - Stress Testing Script
# This script performs load testing on the plugin's critical endpoints

# Configuration
BASE_URL="https://your-site.com"
API_BASE="${BASE_URL}/wp-json/skylearn-billing-pro/v1"
ADMIN_USER="admin"
ADMIN_PASS="your-password"
CONCURRENT_USERS=50
TEST_DURATION=300  # 5 minutes
OUTPUT_DIR="./stress-test-results"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Create output directory
mkdir -p "$OUTPUT_DIR"

echo -e "${GREEN}SkyLearn Billing Pro Stress Testing${NC}"
echo "=================================="
echo "Base URL: $BASE_URL"
echo "Concurrent Users: $CONCURRENT_USERS"
echo "Test Duration: $TEST_DURATION seconds"
echo ""

# Function to check if required tools are installed
check_dependencies() {
    echo -e "${YELLOW}Checking dependencies...${NC}"
    
    if ! command -v ab &> /dev/null; then
        echo -e "${RED}Apache Bench (ab) is not installed${NC}"
        echo "Install with: sudo apt-get install apache2-utils"
        exit 1
    fi
    
    if ! command -v curl &> /dev/null; then
        echo -e "${RED}curl is not installed${NC}"
        exit 1
    fi
    
    if ! command -v jq &> /dev/null; then
        echo -e "${RED}jq is not installed${NC}"
        echo "Install with: sudo apt-get install jq"
        exit 1
    fi
    
    echo -e "${GREEN}All dependencies are installed${NC}"
}

# Function to get authentication token
get_auth_token() {
    echo -e "${YELLOW}Getting authentication token...${NC}"
    
    local response=$(curl -s -X POST "${BASE_URL}/wp-json/jwt-auth/v1/token" \
        -H "Content-Type: application/json" \
        -d "{\"username\":\"${ADMIN_USER}\",\"password\":\"${ADMIN_PASS}\"}")
    
    if [ $? -eq 0 ]; then
        AUTH_TOKEN=$(echo "$response" | jq -r '.token // empty')
        if [ -n "$AUTH_TOKEN" ] && [ "$AUTH_TOKEN" != "null" ]; then
            echo -e "${GREEN}Authentication successful${NC}"
            return 0
        fi
    fi
    
    echo -e "${RED}Authentication failed${NC}"
    echo "Response: $response"
    exit 1
}

# Function to test health endpoints
test_health_endpoints() {
    echo -e "${YELLOW}Testing health endpoints...${NC}"
    
    # Basic health check
    echo "Testing basic health check..."
    ab -n 100 -c 10 "${API_BASE}/health" > "${OUTPUT_DIR}/health_basic.txt" 2>&1
    
    # Detailed health check (requires auth)
    echo "Testing detailed health check..."
    ab -n 50 -c 5 -H "Authorization: Bearer ${AUTH_TOKEN}" \
        "${API_BASE}/health/detailed" > "${OUTPUT_DIR}/health_detailed.txt" 2>&1
    
    # Readiness probe
    echo "Testing readiness probe..."
    ab -n 100 -c 10 "${API_BASE}/ready" > "${OUTPUT_DIR}/readiness.txt" 2>&1
    
    # Liveness probe
    echo "Testing liveness probe..."
    ab -n 100 -c 10 "${API_BASE}/live" > "${OUTPUT_DIR}/liveness.txt" 2>&1
    
    echo -e "${GREEN}Health endpoint tests completed${NC}"
}

# Function to test API endpoints with rate limiting
test_rate_limiting() {
    echo -e "${YELLOW}Testing rate limiting...${NC}"
    
    # Test general API rate limiting
    echo "Testing general API rate limits..."
    ab -n 500 -c 20 -H "Authorization: Bearer ${AUTH_TOKEN}" \
        "${API_BASE}/metrics/dashboard" > "${OUTPUT_DIR}/rate_limit_general.txt" 2>&1
    
    # Test authentication rate limiting
    echo "Testing authentication rate limits..."
    for i in {1..50}; do
        curl -s -X POST "${BASE_URL}/wp-json/jwt-auth/v1/token" \
            -H "Content-Type: application/json" \
            -d "{\"username\":\"invalid\",\"password\":\"invalid\"}" \
            > /dev/null &
    done
    wait
    
    echo -e "${GREEN}Rate limiting tests completed${NC}"
}

# Function to test database performance
test_database_performance() {
    echo -e "${YELLOW}Testing database performance...${NC}"
    
    # Create test data endpoint calls
    echo "Testing transaction queries..."
    ab -n 200 -c 10 -H "Authorization: Bearer ${AUTH_TOKEN}" \
        "${API_BASE}/transactions?limit=50" > "${OUTPUT_DIR}/db_transactions.txt" 2>&1
    
    echo "Testing subscription queries..."
    ab -n 200 -c 10 -H "Authorization: Bearer ${AUTH_TOKEN}" \
        "${API_BASE}/subscriptions?limit=50" > "${OUTPUT_DIR}/db_subscriptions.txt" 2>&1
    
    echo "Testing analytics queries..."
    ab -n 100 -c 5 -H "Authorization: Bearer ${AUTH_TOKEN}" \
        "${API_BASE}/analytics/revenue?period=30d" > "${OUTPUT_DIR}/db_analytics.txt" 2>&1
    
    echo -e "${GREEN}Database performance tests completed${NC}"
}

# Function to analyze results
analyze_results() {
    echo -e "${YELLOW}Analyzing test results...${NC}"
    
    cat > "${OUTPUT_DIR}/analysis_summary.txt" << EOF
SkyLearn Billing Pro Stress Test Analysis
==========================================
Test Date: $(date)
Configuration:
- Concurrent Users: $CONCURRENT_USERS
- Test Duration: $TEST_DURATION seconds
- Base URL: $BASE_URL

Performance Summary:
EOF
    
    # Extract key metrics from Apache Bench results
    for file in "${OUTPUT_DIR}"/*.txt; do
        if [ -f "$file" ]; then
            filename=$(basename "$file" .txt)
            echo "" >> "${OUTPUT_DIR}/analysis_summary.txt"
            echo "=== $filename ===" >> "${OUTPUT_DIR}/analysis_summary.txt"
            
            # Extract requests per second
            rps=$(grep "Requests per second" "$file" | head -1 | awk '{print $4}')
            if [ -n "$rps" ]; then
                echo "Requests per second: $rps" >> "${OUTPUT_DIR}/analysis_summary.txt"
            fi
            
            # Extract response time percentiles
            p50=$(grep "50%" "$file" | awk '{print $2}')
            p95=$(grep "95%" "$file" | awk '{print $2}')
            p99=$(grep "99%" "$file" | awk '{print $2}')
            
            if [ -n "$p50" ]; then
                echo "Response time (ms) - 50%: $p50, 95%: $p95, 99%: $p99" >> "${OUTPUT_DIR}/analysis_summary.txt"
            fi
            
            # Extract failed requests
            failed=$(grep "Failed requests" "$file" | awk '{print $3}')
            if [ -n "$failed" ]; then
                echo "Failed requests: $failed" >> "${OUTPUT_DIR}/analysis_summary.txt"
            fi
        fi
    done
    
    echo -e "${GREEN}Analysis completed. Check ${OUTPUT_DIR}/analysis_summary.txt${NC}"
}

# Main execution
main() {
    echo -e "${GREEN}Starting SkyLearn Billing Pro stress testing...${NC}"
    
    check_dependencies
    get_auth_token
    
    echo -e "${YELLOW}Running test suite...${NC}"
    
    test_health_endpoints
    test_rate_limiting
    test_database_performance
    
    analyze_results
    
    echo ""
    echo -e "${GREEN}==================================${NC}"
    echo -e "${GREEN}Stress testing completed!${NC}"
    echo -e "${GREEN}==================================${NC}"
    echo "Results saved to: $OUTPUT_DIR"
    echo "Summary: $OUTPUT_DIR/analysis_summary.txt"
}

# Run the main function
main "$@"