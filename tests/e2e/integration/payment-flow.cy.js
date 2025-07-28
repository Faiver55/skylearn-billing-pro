describe('SkyLearn Billing Pro - Complete Payment Flow', () => {
  let testUser;
  let testCourse;

  before(() => {
    // Create test data
    cy.createTestCourse({
      title: 'Premium WordPress Course',
      price: '149.99',
      access_type: 'closed'
    }).then((course) => {
      testCourse = course;
    });

    cy.createTestUser({
      username: 'student123',
      email: 'student@example.com',
      password: 'securepass123'
    }).then((user) => {
      testUser = user;
    });
  });

  beforeEach(() => {
    // Reset state before each test
    cy.clearTestData();
  });

  describe('Course Purchase Flow', () => {
    it('should allow user to purchase a course with card payment', () => {
      // Login as test user
      cy.loginAsUser(testUser.username, testUser.password);

      // Navigate to course page
      cy.visit(`/courses/${testCourse.id}`);
      cy.get('[data-testid="course-title"]').should('contain', testCourse.title);
      cy.get('[data-testid="course-price"]').should('contain', testCourse.price);

      // Start enrollment process
      cy.get('[data-testid="enroll-button"]').click();

      // Verify payment form is displayed
      cy.get('[data-testid="payment-form"]').should('be.visible');
      cy.get('[data-testid="payment-amount"]').should('contain', testCourse.price);

      // Process payment
      cy.processPayment(testCourse.price, testCourse.id);

      // Verify successful enrollment
      cy.get('[data-testid="enrollment-success"]')
        .should('be.visible')
        .and('contain', 'Successfully enrolled');

      // Verify course access is granted
      cy.get('[data-testid="course-access"]').should('be.visible');
      cy.get('[data-testid="course-content"]').should('be.visible');

      // Verify transaction in database
      cy.verifyTransactionInDB({
        id: 'latest',
        user_id: testUser.id,
        amount: testCourse.price,
        status: 'completed'
      });

      // Verify confirmation email was sent
      cy.verifyEmailSent('Course Enrollment Confirmation');
    });

    it('should handle payment failures gracefully', () => {
      cy.loginAsUser(testUser.username, testUser.password);
      cy.visit(`/courses/${testCourse.id}`);
      cy.get('[data-testid="enroll-button"]').click();

      // Use declined card number
      cy.get('#card-number').type('4000000000000002');
      cy.get('#card-expiry').type('12/25');
      cy.get('#card-cvc').type('123');
      cy.get('#cardholder-name').type('Test User');

      cy.get('[data-testid="submit-payment"]').click();

      // Verify error handling
      cy.get('[data-testid="payment-error"]')
        .should('be.visible')
        .and('contain', 'Payment failed');

      // Verify user is not enrolled
      cy.get('[data-testid="course-access"]').should('not.exist');
    });

    it('should prevent duplicate payments', () => {
      // First successful payment
      cy.loginAsUser(testUser.username, testUser.password);
      cy.enrollInCourse(testCourse.id);

      // Try to purchase again
      cy.visit(`/courses/${testCourse.id}`);
      cy.get('[data-testid="enroll-button"]').should('not.exist');
      cy.get('[data-testid="already-enrolled"]').should('be.visible');
    });
  });

  describe('Subscription Management', () => {
    it('should allow user to subscribe to a plan', () => {
      cy.loginAsUser(testUser.username, testUser.password);
      cy.visit('/pricing');

      // Select premium plan
      cy.createSubscription('premium-monthly');

      // Verify subscription is active
      cy.visit('/my-account/subscriptions');
      cy.get('[data-testid="subscription-status"]').should('contain', 'Active');
      cy.get('[data-testid="next-payment"]').should('be.visible');
    });

    it('should allow user to cancel subscription', () => {
      // First create a subscription
      cy.loginAsUser(testUser.username, testUser.password);
      cy.createSubscription('premium-monthly');

      // Navigate to subscription management
      cy.visit('/my-account/subscriptions');

      // Cancel subscription
      cy.cancelSubscription('latest');

      // Verify cancellation
      cy.get('[data-testid="subscription-status"]').should('contain', 'Cancelled');
      cy.get('[data-testid="cancellation-notice"]').should('be.visible');
    });

    it('should handle subscription renewal', () => {
      // This would test webhook handling for subscription renewal
      cy.sendWebhook('subscription.renewed', {
        subscription_id: 'sub_test_123',
        amount: 29.99,
        next_payment_date: '2024-02-01'
      });

      // Verify webhook processing
      cy.apiRequest('GET', '/webhooks/status/sub_test_123').then((response) => {
        expect(response.body.status).to.eq('renewed');
      });
    });
  });

  describe('Admin Dashboard Integration', () => {
    it('should update dashboard stats after successful payment', () => {
      // Record initial stats
      cy.goToSLBPAdmin();
      cy.get('[data-stat="revenue"] .stat-value').invoke('text').as('initialRevenue');
      cy.get('[data-stat="transactions"] .stat-value').invoke('text').as('initialTransactions');

      // Process a payment
      cy.loginAsUser(testUser.username, testUser.password);
      cy.enrollInCourse(testCourse.id);

      // Check updated stats
      cy.goToSLBPAdmin();
      cy.get('@initialRevenue').then((initial) => {
        const initialAmount = parseFloat(initial.replace(/[$,]/g, ''));
        const expected = initialAmount + parseFloat(testCourse.price);
        cy.get('[data-stat="revenue"] .stat-value')
          .should('contain', expected.toFixed(2));
      });

      cy.get('@initialTransactions').then((initial) => {
        const expected = parseInt(initial) + 1;
        cy.get('[data-stat="transactions"] .stat-value')
          .should('contain', expected.toString());
      });
    });

    it('should display recent transactions in admin', () => {
      // Process a payment
      cy.loginAsUser(testUser.username, testUser.password);
      cy.enrollInCourse(testCourse.id);

      // Check admin transactions list
      cy.goToSLBPAdmin();
      cy.get('[data-testid="transactions-tab"]').click();

      cy.get('[data-testid="transactions-table"]').within(() => {
        cy.get('tbody tr').first().within(() => {
          cy.get('[data-column="amount"]').should('contain', testCourse.price);
          cy.get('[data-column="status"]').should('contain', 'Completed');
          cy.get('[data-column="user"]').should('contain', testUser.email);
        });
      });
    });
  });

  describe('Performance and Load Testing', () => {
    it('should load course pages quickly', () => {
      cy.measurePageLoad(`/courses/${testCourse.id}`);
    });

    it('should handle multiple concurrent payments', () => {
      // Simulate multiple users purchasing simultaneously
      const users = Array.from({ length: 5 }, (_, i) => ({
        username: `user${i}`,
        email: `user${i}@example.com`,
        password: 'testpass123'
      }));

      users.forEach((user, index) => {
        cy.createTestUser(user).then(() => {
          cy.loginAsUser(user.username, user.password);
          cy.enrollInCourse(testCourse.id);
        });
      });

      // Verify all transactions completed
      cy.goToSLBPAdmin();
      cy.get('[data-testid="transactions-tab"]').click();
      cy.get('[data-testid="transactions-table"] tbody tr').should('have.length.gte', 5);
    });
  });

  describe('Security Testing', () => {
    it('should prevent XSS in payment forms', () => {
      cy.loginAsUser(testUser.username, testUser.password);
      cy.visit(`/courses/${testCourse.id}`);
      cy.get('[data-testid="enroll-button"]').click();

      // Test XSS prevention in cardholder name
      cy.testXSSPrevention('#cardholder-name');
    });

    it('should validate payment amount cannot be manipulated', () => {
      cy.loginAsUser(testUser.username, testUser.password);
      cy.visit(`/courses/${testCourse.id}`);
      cy.get('[data-testid="enroll-button"]').click();

      // Try to manipulate payment amount in browser
      cy.get('[data-testid="payment-amount"]').invoke('attr', 'data-amount', '0.01');

      cy.processPayment('0.01', testCourse.id);

      // Should still charge correct amount
      cy.verifyTransactionInDB({
        amount: testCourse.price, // Original price, not manipulated
        status: 'completed'
      });
    });

    it('should require authentication for admin endpoints', () => {
      cy.testAPIAuthentication('/admin/transactions');
      cy.testAPIAuthentication('/admin/settings');
      cy.testAPIAuthentication('/admin/stats');
    });
  });

  describe('Mobile Responsiveness', () => {
    it('should work on mobile devices', () => {
      cy.testMobileView();
      
      cy.loginAsUser(testUser.username, testUser.password);
      cy.visit(`/courses/${testCourse.id}`);
      
      // Verify mobile-optimized payment flow
      cy.get('[data-testid="mobile-payment-form"]').should('be.visible');
      cy.enrollInCourse(testCourse.id);
    });

    it('should work on tablet devices', () => {
      cy.testTabletView();
      
      cy.loginAsUser(testUser.username, testUser.password);
      cy.enrollInCourse(testCourse.id);
    });
  });

  describe('Accessibility', () => {
    it('should be accessible for screen readers', () => {
      cy.visit(`/courses/${testCourse.id}`);
      cy.checkAccessibility();
    });

    it('should support keyboard navigation', () => {
      cy.visit(`/courses/${testCourse.id}`);
      
      // Tab through payment form
      cy.get('body').tab();
      cy.focused().should('have.attr', 'data-testid', 'enroll-button');
      
      cy.focused().type('{enter}');
      cy.get('[data-testid="payment-form"]').should('be.visible');
    });
  });
});