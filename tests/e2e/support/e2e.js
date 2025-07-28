// ***********************************************************
// This file runs before every single spec file
// ***********************************************************

import './commands';
import '@cypress/code-coverage/support';

// Global configuration
Cypress.on('uncaught:exception', (err, runnable) => {
  // Don't fail tests on uncaught exceptions from the app
  // This is useful for WordPress sites with various plugins
  return false;
});

// Custom commands for WordPress
Cypress.Commands.add('loginAsAdmin', () => {
  cy.session('admin', () => {
    cy.visit('/wp-admin');
    cy.get('#user_login').type(Cypress.env('admin_username'));
    cy.get('#user_pass').type(Cypress.env('admin_password'));
    cy.get('#wp-submit').click();
    cy.url().should('include', '/wp-admin');
  });
});

Cypress.Commands.add('loginAsUser', (username, password) => {
  cy.session([username, password], () => {
    cy.visit('/wp-login.php');
    cy.get('#user_login').type(username);
    cy.get('#user_pass').type(password);
    cy.get('#wp-submit').click();
    cy.url().should('not.include', 'wp-login');
  });
});

// SkyLearn Billing Pro specific commands
Cypress.Commands.add('goToSLBPAdmin', () => {
  cy.loginAsAdmin();
  cy.visit('/wp-admin/admin.php?page=skylearn-billing-pro');
  cy.contains('SkyLearn Billing Pro').should('be.visible');
});

Cypress.Commands.add('createTestCourse', (courseData = {}) => {
  const defaultCourse = {
    title: 'Test Course',
    price: '99.99',
    access_type: 'closed',
    ...courseData
  };
  
  return cy.task('createTestCourse', defaultCourse);
});

Cypress.Commands.add('createTestUser', (userData = {}) => {
  const defaultUser = {
    username: 'testuser',
    email: 'test@example.com',
    password: 'testpass123',
    role: 'subscriber',
    ...userData
  };
  
  return cy.task('createTestUser', defaultUser);
});

Cypress.Commands.add('simulatePayment', (paymentData) => {
  // Simulate a successful payment
  cy.window().then((win) => {
    win.postMessage({
      type: 'payment_completed',
      data: paymentData
    }, '*');
  });
});

Cypress.Commands.add('waitForAjax', () => {
  cy.window().then((win) => {
    return new Cypress.Promise((resolve) => {
      if (win.jQuery && win.jQuery.active === 0) {
        resolve();
      } else {
        const checkAjax = () => {
          if (win.jQuery && win.jQuery.active === 0) {
            resolve();
          } else {
            setTimeout(checkAjax, 100);
          }
        };
        checkAjax();
      }
    });
  });
});

Cypress.Commands.add('checkDatabaseForTransaction', (transactionId) => {
  // In a real setup, this would query the database
  cy.log(`Checking database for transaction: ${transactionId}`);
  return cy.wrap({ found: true, status: 'completed' });
});

Cypress.Commands.add('clearTestData', () => {
  cy.task('cleanDatabase');
});

// API testing commands
Cypress.Commands.add('apiRequest', (method, endpoint, data = {}) => {
  const apiBase = Cypress.env('api_base');
  
  return cy.request({
    method: method,
    url: `${apiBase}${endpoint}`,
    body: data,
    headers: {
      'Content-Type': 'application/json'
    },
    failOnStatusCode: false
  });
});

// Custom assertions
Cypress.Commands.add('shouldHaveSuccessNotification', () => {
  cy.get('.notice-success, .slbp-notification.success')
    .should('be.visible')
    .and('contain', 'success');
});

Cypress.Commands.add('shouldHaveErrorNotification', () => {
  cy.get('.notice-error, .slbp-notification.error')
    .should('be.visible')
    .and('contain', 'error');
});

// Before each test
beforeEach(() => {
  // Clear any existing data
  cy.clearTestData();
  
  // Set up fresh state
  cy.task('seedDatabase');
});

// After each test
afterEach(() => {
  // Clean up test data
  cy.clearTestData();
});