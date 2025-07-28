// ***********************************************
// Custom commands for SkyLearn Billing Pro
// ***********************************************

// Payment processing commands
Cypress.Commands.add('processPayment', (amount, courseId) => {
  cy.get('[data-testid="payment-amount"]').should('contain', amount);
  cy.get('[data-testid="course-id"]').should('have.value', courseId);
  
  // Fill in payment form
  cy.get('#card-number').type('4242424242424242');
  cy.get('#card-expiry').type('12/25');
  cy.get('#card-cvc').type('123');
  cy.get('#cardholder-name').type('Test User');
  
  // Submit payment
  cy.get('[data-testid="submit-payment"]').click();
  
  // Wait for processing
  cy.get('[data-testid="payment-processing"]').should('be.visible');
  cy.get('[data-testid="payment-success"]', { timeout: 15000 }).should('be.visible');
});

// Subscription management commands
Cypress.Commands.add('createSubscription', (planId) => {
  cy.get(`[data-plan-id="${planId}"]`).click();
  cy.get('[data-testid="subscribe-button"]').click();
  
  // Complete payment flow
  cy.processPayment('29.99', null);
  
  // Verify subscription created
  cy.get('[data-testid="subscription-active"]').should('be.visible');
});

Cypress.Commands.add('cancelSubscription', (subscriptionId) => {
  cy.get(`[data-subscription-id="${subscriptionId}"]`).within(() => {
    cy.get('[data-testid="cancel-subscription"]').click();
  });
  
  // Confirm cancellation
  cy.get('[data-testid="confirm-cancel"]').click();
  
  // Wait for API response
  cy.waitForAjax();
  
  // Verify cancellation
  cy.get('[data-testid="subscription-cancelled"]').should('be.visible');
});

// Course enrollment commands
Cypress.Commands.add('enrollInCourse', (courseId, paymentMethod = 'card') => {
  cy.visit(`/courses/${courseId}`);
  cy.get('[data-testid="enroll-button"]').click();
  
  if (paymentMethod === 'card') {
    cy.processPayment('99.99', courseId);
  }
  
  // Verify enrollment
  cy.get('[data-testid="enrollment-success"]').should('be.visible');
  cy.get('[data-testid="course-access"]').should('be.visible');
});

// Admin dashboard commands
Cypress.Commands.add('checkDashboardStats', (expectedStats) => {
  cy.goToSLBPAdmin();
  
  Object.keys(expectedStats).forEach(stat => {
    cy.get(`[data-stat="${stat}"]`)
      .should('be.visible')
      .and('contain', expectedStats[stat]);
  });
});

Cypress.Commands.add('configurePaymentGateway', (gateway, settings) => {
  cy.goToSLBPAdmin();
  cy.get('[data-testid="settings-tab"]').click();
  cy.get(`[data-gateway="${gateway}"]`).click();
  
  Object.keys(settings).forEach(key => {
    cy.get(`#${key}`).clear().type(settings[key]);
  });
  
  cy.get('[data-testid="save-settings"]').click();
  cy.shouldHaveSuccessNotification();
});

// API testing helpers
Cypress.Commands.add('testAPIEndpoint', (endpoint, method = 'GET', expectedStatus = 200) => {
  cy.apiRequest(method, endpoint).then((response) => {
    expect(response.status).to.eq(expectedStatus);
    return cy.wrap(response);
  });
});

Cypress.Commands.add('testAPIAuthentication', (endpoint) => {
  // Test without authentication
  cy.apiRequest('GET', endpoint).then((response) => {
    expect(response.status).to.eq(401);
  });
  
  // Test with invalid token
  cy.request({
    method: 'GET',
    url: `${Cypress.env('api_base')}${endpoint}`,
    headers: {
      'Authorization': 'Bearer invalid_token'
    },
    failOnStatusCode: false
  }).then((response) => {
    expect(response.status).to.eq(401);
  });
});

// Webhook testing
Cypress.Commands.add('sendWebhook', (event, data) => {
  const webhookUrl = `${Cypress.env('api_base')}/webhooks/lemon-squeezy`;
  
  cy.request({
    method: 'POST',
    url: webhookUrl,
    body: {
      event: event,
      data: data,
      timestamp: Date.now()
    },
    headers: {
      'Content-Type': 'application/json',
      'X-Signature': 'test_signature'
    }
  }).then((response) => {
    expect(response.status).to.eq(200);
    return cy.wrap(response);
  });
});

// Performance testing helpers
Cypress.Commands.add('measurePageLoad', (url) => {
  cy.visit(url);
  
  cy.window().then((win) => {
    const navigation = win.performance.getEntriesByType('navigation')[0];
    const loadTime = navigation.loadEventEnd - navigation.fetchStart;
    
    // Log performance metrics
    cy.log(`Page load time: ${loadTime}ms`);
    cy.log(`DOM content loaded: ${navigation.domContentLoadedEventEnd - navigation.fetchStart}ms`);
    
    // Assert reasonable load times
    expect(loadTime).to.be.lessThan(5000); // 5 seconds max
  });
});

// Accessibility testing
Cypress.Commands.add('checkAccessibility', () => {
  cy.injectAxe();
  cy.checkA11y(null, {
    rules: {
      'color-contrast': { enabled: true },
      'keyboard-navigation': { enabled: true },
      'aria-labels': { enabled: true }
    }
  });
});

// Visual regression testing helpers
Cypress.Commands.add('compareScreenshot', (name) => {
  // This would integrate with a visual regression service
  cy.screenshot(name);
  cy.log(`Screenshot captured: ${name}`);
});

// Database helpers
Cypress.Commands.add('verifyTransactionInDB', (transactionData) => {
  cy.checkDatabaseForTransaction(transactionData.id).then((result) => {
    expect(result.found).to.be.true;
    expect(result.status).to.eq(transactionData.status);
  });
});

// Email testing
Cypress.Commands.add('verifyEmailSent', (expectedSubject) => {
  cy.task('getLastEmail').then((email) => {
    expect(email.subject).to.contain(expectedSubject);
  });
});

// Mobile testing helpers
Cypress.Commands.add('testMobileView', () => {
  cy.viewport('iphone-x');
  cy.get('[data-testid="mobile-menu"]').should('be.visible');
  cy.get('[data-testid="desktop-menu"]').should('not.be.visible');
});

Cypress.Commands.add('testTabletView', () => {
  cy.viewport('ipad-2');
  cy.get('[data-testid="responsive-layout"]').should('have.class', 'tablet');
});

// Security testing helpers
Cypress.Commands.add('testXSSPrevention', (inputSelector) => {
  const xssPayload = '<script>alert("XSS")</script>';
  
  cy.get(inputSelector).type(xssPayload);
  cy.get('form').submit();
  
  // Verify script tag is escaped/sanitized
  cy.get('body').should('not.contain', '<script>alert("XSS")</script>');
});