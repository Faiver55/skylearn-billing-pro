# SkyLearn Billing Pro - Testing Guidelines

## Overview

This document outlines the testing standards, practices, and guidelines for contributors to the SkyLearn Billing Pro project. Following these guidelines ensures consistent, reliable, and maintainable test code.

## Table of Contents

1. [Testing Philosophy](#testing-philosophy)
2. [Test Types and Structure](#test-types-and-structure)
3. [PHP Testing Guidelines](#php-testing-guidelines)
4. [JavaScript Testing Guidelines](#javascript-testing-guidelines)
5. [Integration Testing](#integration-testing)
6. [End-to-End Testing](#end-to-end-testing)
7. [Test Data Management](#test-data-management)
8. [Performance Testing](#performance-testing)
9. [Security Testing](#security-testing)
10. [Continuous Integration](#continuous-integration)
11. [Best Practices](#best-practices)

## Testing Philosophy

### Test Pyramid
We follow the test pyramid approach:
- **Unit Tests (70%)**: Fast, isolated tests for individual components
- **Integration Tests (20%)**: Test component interactions and API endpoints
- **End-to-End Tests (10%)**: Full user journey validation

### Testing Principles
- **Test Early and Often**: Write tests before or alongside code
- **Keep Tests Simple**: Each test should verify one specific behavior
- **Make Tests Independent**: Tests should not depend on each other
- **Use Descriptive Names**: Test names should clearly describe what is being tested
- **Maintain Test Quality**: Test code should be as clean as production code

## Test Types and Structure

### Directory Structure
```
tests/
├── unit/                 # Unit tests for individual classes/functions
├── integration/          # Integration tests for component interactions
├── api/                  # API endpoint testing
├── e2e/                  # End-to-end user journey tests
├── fixtures/             # Test data files
├── includes/             # Test utilities and helpers
├── coverage/             # Code coverage reports
└── results/              # Test execution results
```

### Naming Conventions
- **PHP Test Files**: `test-class-{class-name}.php`
- **JS Test Files**: `{component-name}.test.js`
- **E2E Test Files**: `{feature-name}.cy.js`
- **Test Methods**: `test_{action}_{expected_result}`

## PHP Testing Guidelines

### PHPUnit Setup

#### Writing Unit Tests
```php
<?php
class Test_SLBP_Payment_Gateway extends SLBP_Test_Case {
    
    public function test_process_payment_with_valid_data() {
        // Arrange
        $gateway = new SLBP_Lemon_Squeezy();
        $payment_data = array(
            'amount' => 99.99,
            'currency' => 'USD',
            'user_id' => 123
        );
        
        // Act
        $result = $gateway->process_payment( $payment_data );
        
        // Assert
        $this->assertTrue( $result['success'] );
        $this->assertArrayHasKey( 'transaction_id', $result );
        $this->assertEquals( 99.99, $result['amount'] );
    }
    
    public function test_process_payment_with_invalid_amount() {
        $gateway = new SLBP_Lemon_Squeezy();
        $payment_data = array(
            'amount' => -10,
            'currency' => 'USD',
            'user_id' => 123
        );
        
        $this->expectException( InvalidArgumentException::class );
        $gateway->process_payment( $payment_data );
    }
}
```

#### Test Structure Guidelines
1. **Arrange**: Set up test data and conditions
2. **Act**: Execute the code being tested
3. **Assert**: Verify the expected outcome

#### Mocking Dependencies
```php
public function test_with_mocked_dependency() {
    // Create mock
    $mock_db = $this->createMock( 'wpdb' );
    $mock_db->expects( $this->once() )
            ->method( 'insert' )
            ->willReturn( true );
    
    // Inject mock into class
    $service = new SLBP_Transaction_Service( $mock_db );
    
    // Test with mock
    $result = $service->save_transaction( $this->sample_transaction_data );
    $this->assertTrue( $result );
}
```

### Code Coverage Requirements
- **Minimum Coverage**: 80% for all new code
- **Critical Components**: 95% coverage required
- **Exception Handling**: All catch blocks must be tested

## JavaScript Testing Guidelines

### Jest Configuration
```javascript
// jest.config.js
module.exports = {
    testEnvironment: 'jsdom',
    setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
    collectCoverageFrom: [
        'admin/js/**/*.js',
        'public/js/**/*.js'
    ],
    coverageThreshold: {
        global: {
            branches: 80,
            functions: 80,
            lines: 80,
            statements: 80
        }
    }
};
```

### Writing JavaScript Tests
```javascript
describe('SLBP Payment Form', () => {
    let paymentForm;
    
    beforeEach(() => {
        document.body.innerHTML = `
            <form id="payment-form">
                <input type="text" id="card-number" />
                <input type="text" id="expiry" />
                <button type="submit">Pay Now</button>
            </form>
        `;
        paymentForm = new SLBPPaymentForm('#payment-form');
    });
    
    afterEach(() => {
        document.body.innerHTML = '';
    });
    
    test('should validate card number format', () => {
        const cardInput = document.getElementById('card-number');
        cardInput.value = '4242424242424242';
        
        const isValid = paymentForm.validateCardNumber();
        
        expect(isValid).toBe(true);
    });
    
    test('should show error for invalid card number', () => {
        const cardInput = document.getElementById('card-number');
        cardInput.value = '1234';
        
        const isValid = paymentForm.validateCardNumber();
        
        expect(isValid).toBe(false);
        expect(document.querySelector('.error-message')).toBeTruthy();
    });
});
```

### Testing Async Operations
```javascript
test('should process payment successfully', async () => {
    // Mock fetch
    global.fetch = jest.fn().mockResolvedValue({
        ok: true,
        json: jest.fn().mockResolvedValue({
            success: true,
            transaction_id: 'txn_123'
        })
    });
    
    const result = await paymentForm.submitPayment(paymentData);
    
    expect(result.success).toBe(true);
    expect(result.transaction_id).toBe('txn_123');
    expect(fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/payments'),
        expect.objectContaining({
            method: 'POST'
        })
    );
});
```

## Integration Testing

### API Integration Tests
```php
class Test_REST_API_Integration extends SLBP_Test_Case {
    
    public function test_create_transaction_endpoint() {
        $request = new WP_REST_Request( 'POST', '/skylearn-billing-pro/v1/transactions' );
        $request->set_body_params( array(
            'amount' => 99.99,
            'currency' => 'USD',
            'user_id' => 123
        ) );
        
        $response = rest_do_request( $request );
        
        $this->assertEquals( 201, $response->get_status() );
        $this->assertArrayHasKey( 'transaction_id', $response->get_data() );
    }
}
```

### Database Integration Tests
```php
public function test_transaction_persistence() {
    global $wpdb;
    
    $transaction_data = array(
        'user_id' => 123,
        'amount' => 99.99,
        'status' => 'completed'
    );
    
    $transaction_id = $this->transaction_service->create( $transaction_data );
    
    // Verify in database
    $saved_transaction = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}slbp_transactions WHERE id = %d",
            $transaction_id
        )
    );
    
    $this->assertNotNull( $saved_transaction );
    $this->assertEquals( 123, $saved_transaction->user_id );
    $this->assertEquals( 99.99, $saved_transaction->amount );
}
```

## End-to-End Testing

### Cypress Best Practices
```javascript
// Good: Use data attributes for selectors
cy.get('[data-testid="payment-form"]').should('be.visible');

// Bad: Use brittle CSS selectors
cy.get('.payment-form.active').should('be.visible');

// Good: Custom commands for reusable actions
cy.loginAsUser('student@example.com', 'password');
cy.purchaseCourse('advanced-wordpress');

// Good: Descriptive test names
it('should allow user to purchase course with valid payment details', () => {
    // Test implementation
});
```

### Test Data Management
```javascript
beforeEach(() => {
    cy.task('resetDatabase');
    cy.task('seedTestData', {
        users: 5,
        courses: 3,
        transactions: 10
    });
});
```

## Test Data Management

### Test Fixtures
Create reusable test data in fixtures:

```php
// tests/fixtures/sample-transactions.php
return array(
    'completed_payment' => array(
        'id' => 'txn_123',
        'amount' => 99.99,
        'currency' => 'USD',
        'status' => 'completed',
        'user_id' => 123
    ),
    'failed_payment' => array(
        'id' => 'txn_456',
        'amount' => 49.99,
        'currency' => 'USD',
        'status' => 'failed',
        'user_id' => 456
    )
);
```

### Factory Pattern
```php
class SLBP_Test_Factory {
    public static function create_user( $args = array() ) {
        $defaults = array(
            'user_login' => 'testuser_' . uniqid(),
            'user_email' => 'test_' . uniqid() . '@example.com',
            'user_pass' => 'testpass123'
        );
        
        return wp_insert_user( array_merge( $defaults, $args ) );
    }
}
```

## Performance Testing

### Load Testing Guidelines
```javascript
// artillery.config.js example
module.exports = {
    config: {
        target: 'http://localhost:8080',
        phases: [
            { duration: '1m', arrivalRate: 5 },
            { duration: '2m', arrivalRate: 10 },
            { duration: '1m', arrivalRate: 5 }
        ]
    },
    scenarios: [
        {
            name: 'Payment Processing',
            weight: 70,
            flow: [
                { post: { url: '/api/payments', json: '{{ paymentData }}' } }
            ]
        }
    ]
};
```

### Performance Assertions
```php
public function test_payment_processing_performance() {
    $start_time = microtime( true );
    
    $this->payment_gateway->process_payment( $this->sample_payment_data );
    
    $execution_time = microtime( true ) - $start_time;
    
    // Payment should complete within 2 seconds
    $this->assertLessThan( 2.0, $execution_time );
}
```

## Security Testing

### Input Validation Tests
```php
public function test_sql_injection_prevention() {
    $malicious_input = "'; DROP TABLE wp_users; --";
    
    $result = $this->service->search_transactions( $malicious_input );
    
    // Should return empty results, not cause database error
    $this->assertIsArray( $result );
    $this->assertEmpty( $result );
}
```

### XSS Prevention Tests
```javascript
test('should sanitize user input to prevent XSS', () => {
    const maliciousInput = '<script>alert("XSS")</script>';
    const form = new PaymentForm();
    
    form.setCardholderName(maliciousInput);
    
    const sanitizedValue = form.getCardholderName();
    expect(sanitizedValue).not.toContain('<script>');
    expect(sanitizedValue).toBe('&lt;script&gt;alert("XSS")&lt;/script&gt;');
});
```

## Continuous Integration

### GitHub Actions Integration
Tests run automatically on:
- Every pull request
- Push to main/develop branches
- Scheduled security scans
- Release preparation

### Quality Gates
- All tests must pass
- Code coverage > 80%
- No critical security vulnerabilities
- Performance benchmarks met

## Best Practices

### General Testing Best Practices
1. **Write Tests First**: TDD approach when possible
2. **Test Behavior, Not Implementation**: Focus on what, not how
3. **Use Descriptive Test Names**: Anyone should understand the test purpose
4. **Keep Tests Fast**: Unit tests should run in milliseconds
5. **Avoid Test Dependencies**: Each test should be isolated
6. **Use Meaningful Assertions**: Assert specific values, not just truthiness
7. **Clean Up After Tests**: Reset state to avoid side effects

### WordPress-Specific Guidelines
1. **Mock WordPress Functions**: Use test doubles for WP functions
2. **Test Hooks and Filters**: Verify actions and filters are registered
3. **Validate Nonces**: Test security token validation
4. **Check Capabilities**: Verify permission requirements
5. **Test Sanitization**: Ensure all input is properly sanitized

### Performance Testing Guidelines
1. **Set Realistic Thresholds**: Based on actual usage patterns
2. **Test Under Load**: Simulate concurrent users
3. **Monitor Resource Usage**: Check memory and CPU consumption
4. **Profile Slow Operations**: Identify bottlenecks
5. **Test Edge Cases**: Maximum data sizes, timeouts

### Code Review Checklist for Tests
- [ ] Tests are well-named and describe behavior
- [ ] Each test focuses on one specific functionality
- [ ] Assertions are meaningful and specific
- [ ] Test data is realistic and comprehensive
- [ ] Error cases are covered
- [ ] Performance requirements are validated
- [ ] Security implications are considered
- [ ] Tests are maintainable and readable

## Testing Commands

### Running Tests
```bash
# PHP Tests
composer test                    # Run all PHPUnit tests
composer test:coverage          # Run with coverage report
composer test:unit              # Run unit tests only
composer test:integration       # Run integration tests only

# JavaScript Tests
npm test                        # Run Jest tests
npm run test:watch             # Run in watch mode
npm run test:coverage          # Run with coverage

# End-to-End Tests
npm run test:e2e               # Run Cypress tests
npm run test:e2e:open          # Open Cypress GUI

# Code Quality
composer cs:check              # Check coding standards
composer analyze               # Run static analysis
npm run lint                   # Run ESLint

# Security
composer security:check        # Check for vulnerabilities
npm audit                      # Check npm dependencies
```

### Coverage Reports
- **PHP Coverage**: Available at `tests/coverage/html/index.html`
- **JavaScript Coverage**: Available at `tests/coverage/js/index.html`
- **Combined Reports**: Generated in CI/CD pipeline

## Getting Help

### Resources
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Cypress Documentation](https://docs.cypress.io/)
- [WordPress Testing Handbook](https://make.wordpress.org/core/handbook/testing/)

### Team Support
- **Slack Channel**: #testing
- **Code Reviews**: Tag @testing-team
- **Questions**: Create issue with `question` label

---

*This document is living and should be updated as testing practices evolve.*