const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:8080',
    supportFile: 'tests/e2e/support/e2e.js',
    specPattern: 'tests/e2e/integration/**/*.cy.js',
    fixturesFolder: 'tests/e2e/fixtures',
    screenshotsFolder: 'tests/e2e/screenshots',
    videosFolder: 'tests/e2e/videos',
    downloadsFolder: 'tests/e2e/downloads',
    
    // Viewport settings
    viewportWidth: 1280,
    viewportHeight: 720,
    
    // Timeouts
    defaultCommandTimeout: 10000,
    requestTimeout: 10000,
    responseTimeout: 10000,
    pageLoadTimeout: 30000,
    
    // Test isolation
    testIsolation: true,
    
    // Video recording
    video: true,
    videoCompression: 32,
    
    // Screenshots
    screenshotOnRunFailure: true,
    
    // Retry settings
    retries: {
      runMode: 2,
      openMode: 0
    },
    
    // Environment variables
    env: {
      admin_username: 'admin',
      admin_password: 'password',
      site_url: 'http://localhost:8080',
      api_base: '/wp-json/skylearn-billing-pro/v1',
      test_mode: true
    },
    
    setupNodeEvents(on, config) {
      // Task definitions
      on('task', {
        log(message) {
          console.log(message);
          return null;
        },
        
        // Database tasks for test data setup
        seedDatabase() {
          // In a real setup, this would seed test data
          console.log('Seeding test database...');
          return null;
        },
        
        cleanDatabase() {
          // In a real setup, this would clean test data
          console.log('Cleaning test database...');
          return null;
        },
        
        // API tasks
        createTestUser(userData) {
          // Create test user via API
          console.log('Creating test user:', userData);
          return { id: 123, ...userData };
        },
        
        createTestCourse(courseData) {
          // Create test course
          console.log('Creating test course:', courseData);
          return { id: 456, ...courseData };
        },
        
        // Email testing
        getLastEmail() {
          // In a real setup, this would check email queue/service
          return {
            to: 'test@example.com',
            subject: 'Test Email',
            body: 'Test email body'
          };
        }
      });
      
      // Plugins
      require('@cypress/code-coverage/task')(on, config);
      
      return config;
    }
  },
  
  component: {
    devServer: {
      framework: 'vanilla',
      bundler: 'webpack'
    },
    specPattern: 'tests/e2e/component/**/*.cy.js',
    supportFile: 'tests/e2e/support/component.js'
  },
  
  // Global configuration
  chromeWebSecurity: false,
  defaultBrowser: 'chrome',
  
  // Reporting
  reporter: 'cypress-multi-reporters',
  reporterOptions: {
    reporterEnabled: 'mochawesome,json',
    mochawesomeReporterOptions: {
      reportDir: 'tests/e2e/reports',
      quite: true,
      overwrite: false,
      html: false,
      json: true
    },
    jsonReporterOptions: {
      output: 'tests/e2e/reports/results.json'
    }
  }
});