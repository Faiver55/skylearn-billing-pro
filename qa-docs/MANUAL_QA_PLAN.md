# SkyLearn Billing Pro - Manual QA Test Plan

## Test Environment Setup

### Prerequisites
- [ ] WordPress 5.0+ installation
- [ ] LearnDash plugin activated
- [ ] PHP 7.4+ 
- [ ] Test payment gateway credentials configured
- [ ] Test user accounts created
- [ ] Sample courses available

### Test Data Requirements
- [ ] 5+ test courses with different pricing models
- [ ] 10+ test user accounts with various roles
- [ ] Test payment methods configured
- [ ] Sample subscription plans created

## Functional Testing

### 1. Plugin Installation and Activation
**Test Case ID:** TC001
**Priority:** High
**Objective:** Verify plugin installs and activates correctly

**Steps:**
1. Upload plugin files to WordPress
2. Activate plugin through admin interface
3. Check for activation errors
4. Verify admin menu appears
5. Check database tables are created

**Expected Results:**
- [ ] Plugin activates without errors
- [ ] Admin menu "SkyLearn Billing Pro" appears
- [ ] Database tables created successfully
- [ ] No PHP errors in logs

### 2. Payment Gateway Configuration
**Test Case ID:** TC002
**Priority:** High
**Objective:** Verify payment gateway setup works correctly

**Steps:**
1. Navigate to Settings > SkyLearn Billing Pro
2. Configure Lemon Squeezy settings
3. Enter API credentials
4. Test connection
5. Save settings

**Expected Results:**
- [ ] Settings page loads correctly
- [ ] API credentials validate successfully
- [ ] Settings save without errors
- [ ] Success notification appears

### 3. Course Purchase Flow
**Test Case ID:** TC003
**Priority:** Critical
**Objective:** Verify complete course purchase process

**Steps:**
1. Log in as test user
2. Browse to course page
3. Click "Enroll" button
4. Complete payment form
5. Submit payment
6. Verify enrollment

**Expected Results:**
- [ ] Payment form displays correctly
- [ ] Payment processes successfully
- [ ] User is enrolled in course
- [ ] Confirmation email sent
- [ ] Transaction recorded in admin

### 4. Subscription Management
**Test Case ID:** TC004
**Priority:** High
**Objective:** Verify subscription creation and management

**Steps:**
1. Create subscription plan
2. User subscribes to plan
3. Verify subscription is active
4. Test subscription cancellation
5. Verify billing cycle updates

**Expected Results:**
- [ ] Subscription created successfully
- [ ] Billing cycle functions correctly
- [ ] Cancellation works properly
- [ ] Email notifications sent

### 5. Admin Dashboard
**Test Case ID:** TC005
**Priority:** Medium
**Objective:** Verify admin dashboard functionality

**Steps:**
1. Log in as admin user
2. Navigate to plugin dashboard
3. Check revenue statistics
4. Review transaction list
5. Test export functionality

**Expected Results:**
- [ ] Dashboard loads without errors
- [ ] Statistics display correctly
- [ ] Transaction data accurate
- [ ] Export functions work

### 6. Webhook Processing
**Test Case ID:** TC006
**Priority:** High
**Objective:** Verify webhook handling works correctly

**Steps:**
1. Configure webhook endpoint
2. Trigger test webhook from payment gateway
3. Verify webhook is received
4. Check transaction status updates
5. Verify enrollment automation

**Expected Results:**
- [ ] Webhook endpoint responds correctly
- [ ] Transaction status updates
- [ ] User enrollment automated
- [ ] Webhook logged properly

## Integration Testing

### 7. LearnDash Integration
**Test Case ID:** TC007
**Priority:** Critical
**Objective:** Verify LearnDash integration works seamlessly

**Steps:**
1. Create LearnDash course
2. Set course pricing in SLBP
3. User purchases course
4. Verify LearnDash enrollment
5. Test course access

**Expected Results:**
- [ ] Course pricing syncs correctly
- [ ] Enrollment happens automatically
- [ ] User can access course content
- [ ] Progress tracking works

### 8. User Role Management
**Test Case ID:** TC008
**Priority:** Medium
**Objective:** Verify user roles and permissions

**Steps:**
1. Test with different user roles
2. Verify permission restrictions
3. Check admin capabilities
4. Test subscriber limitations

**Expected Results:**
- [ ] Admin has full access
- [ ] Subscribers have limited access
- [ ] Permissions enforced correctly
- [ ] No unauthorized access

## Security Testing

### 9. Input Validation
**Test Case ID:** TC009
**Priority:** High
**Objective:** Verify input validation prevents attacks

**Steps:**
1. Test SQL injection attempts
2. Try XSS payloads
3. Test file upload vulnerabilities
4. Check CSRF protection
5. Verify nonce validation

**Expected Results:**
- [ ] SQL injection blocked
- [ ] XSS attempts sanitized
- [ ] File uploads secured
- [ ] CSRF protection active
- [ ] Nonces validated

### 10. Authentication Security
**Test Case ID:** TC010
**Priority:** Critical
**Objective:** Verify authentication mechanisms are secure

**Steps:**
1. Test API authentication
2. Verify session management
3. Check password requirements
4. Test account lockout
5. Verify logout functionality

**Expected Results:**
- [ ] API requires authentication
- [ ] Sessions managed securely
- [ ] Strong passwords enforced
- [ ] Lockout prevents brute force
- [ ] Logout clears sessions

## Performance Testing

### 11. Page Load Performance
**Test Case ID:** TC011
**Priority:** Medium
**Objective:** Verify acceptable page load times

**Steps:**
1. Measure course page load time
2. Test payment form performance
3. Check admin dashboard speed
4. Verify API response times

**Expected Results:**
- [ ] Course pages load < 3 seconds
- [ ] Payment forms responsive
- [ ] Admin dashboard < 5 seconds
- [ ] API responses < 1 second

### 12. Database Performance
**Test Case ID:** TC012
**Priority:** Medium
**Objective:** Verify database queries are optimized

**Steps:**
1. Enable query logging
2. Process multiple transactions
3. Analyze slow queries
4. Check index usage

**Expected Results:**
- [ ] No slow queries detected
- [ ] Indexes used effectively
- [ ] Query count reasonable
- [ ] No N+1 query issues

## Usability Testing

### 13. User Experience Flow
**Test Case ID:** TC013
**Priority:** High
**Objective:** Verify user-friendly experience

**Steps:**
1. Test course discovery
2. Evaluate checkout process
3. Check error messages
4. Verify mobile responsiveness
5. Test accessibility features

**Expected Results:**
- [ ] Intuitive navigation
- [ ] Clear checkout process
- [ ] Helpful error messages
- [ ] Mobile-friendly design
- [ ] Accessible to all users

### 14. Admin User Experience
**Test Case ID:** TC014
**Priority:** Medium
**Objective:** Verify admin interface is user-friendly

**Steps:**
1. Test admin navigation
2. Check configuration ease
3. Verify reporting clarity
4. Test bulk operations

**Expected Results:**
- [ ] Admin interface intuitive
- [ ] Configuration straightforward
- [ ] Reports easy to understand
- [ ] Bulk operations efficient

## Compatibility Testing

### 15. WordPress Compatibility
**Test Case ID:** TC015
**Priority:** High
**Objective:** Verify compatibility with WordPress versions

**Steps:**
1. Test with WordPress 5.0
2. Test with WordPress 6.0+
3. Verify theme compatibility
4. Check plugin conflicts

**Expected Results:**
- [ ] Works with supported WP versions
- [ ] Theme compatibility maintained
- [ ] No plugin conflicts
- [ ] Core functionality intact

### 16. Browser Compatibility
**Test Case ID:** TC016
**Priority:** Medium
**Objective:** Verify cross-browser compatibility

**Steps:**
1. Test in Chrome
2. Test in Firefox
3. Test in Safari
4. Test in Edge
5. Check mobile browsers

**Expected Results:**
- [ ] Functions in all major browsers
- [ ] UI displays correctly
- [ ] JavaScript works properly
- [ ] Payment forms functional

## Regression Testing

### 17. Core Functionality Regression
**Test Case ID:** TC017
**Priority:** Critical
**Objective:** Verify updates don't break existing features

**Steps:**
1. Re-run critical test cases
2. Check payment processing
3. Verify data integrity
4. Test integration points

**Expected Results:**
- [ ] All critical features work
- [ ] Payment processing intact
- [ ] Data remains accurate
- [ ] Integrations functional

## Bug Reporting Template

### Bug Report Format
```
Bug ID: BUG-XXXX
Title: [Brief description]
Priority: [Critical/High/Medium/Low]
Environment: [Browser, OS, WordPress version]
Steps to Reproduce:
1. Step one
2. Step two
3. Step three

Expected Result: [What should happen]
Actual Result: [What actually happened]
Screenshots: [Attach if relevant]
Additional Notes: [Any other information]
```

## Test Execution Tracking

### Test Run Summary
- **Test Date:** _________________
- **Tester:** _________________
- **Environment:** _________________
- **Total Test Cases:** 17
- **Passed:** _____ / 17
- **Failed:** _____ / 17
- **Blocked:** _____ / 17
- **Overall Status:** [ ] PASS [ ] FAIL

### Sign-off
- **QA Lead:** _________________ Date: _________
- **Dev Lead:** _________________ Date: _________
- **Product Owner:** _________________ Date: _________