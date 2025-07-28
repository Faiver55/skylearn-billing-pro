module.exports = {
  config: {
    target: 'http://localhost:8080',
    phases: [
      { duration: '2m', arrivalRate: 5 },   // Warm-up
      { duration: '5m', arrivalRate: 10 },  // Ramp-up
      { duration: '10m', arrivalRate: 20 }, // Sustained load
      { duration: '2m', arrivalRate: 5 }    // Cool-down
    ],
    payload: {
      path: './tests/fixtures/test-data.csv',
      fields: ['email', 'course_id', 'amount']
    },
    defaults: {
      headers: {
        'Content-Type': 'application/json',
        'User-Agent': 'Artillery Load Test'
      }
    },
    plugins: {
      'artillery-plugin-metrics-by-endpoint': {},
      'artillery-plugin-cloudwatch': {
        namespace: 'SkyLearnBillingPro/LoadTest'
      }
    }
  },
  scenarios: [
    {
      name: 'API Health Check',
      weight: 10,
      flow: [
        {
          get: {
            url: '/wp-json/skylearn-billing-pro/v1/health',
            expect: [
              { statusCode: 200 },
              { hasProperty: 'status' },
              { equals: ['status', 'healthy'] }
            ]
          }
        }
      ]
    },
    {
      name: 'Course Browsing',
      weight: 30,
      flow: [
        {
          get: {
            url: '/courses',
            capture: {
              json: '$.courses[0].id',
              as: 'courseId'
            }
          }
        },
        {
          get: {
            url: '/courses/{{ courseId }}',
            expect: [
              { statusCode: 200 }
            ]
          }
        }
      ]
    },
    {
      name: 'User Registration',
      weight: 20,
      flow: [
        {
          post: {
            url: '/wp-json/wp/v2/users',
            json: {
              username: 'testuser{{ $randomString() }}',
              email: 'test{{ $randomString() }}@example.com',
              password: 'SecurePass123!'
            },
            expect: [
              { statusCode: [201, 400] } // 400 might be validation error, which is expected
            ]
          }
        }
      ]
    },
    {
      name: 'Payment Processing',
      weight: 25,
      flow: [
        {
          post: {
            url: '/wp-json/skylearn-billing-pro/v1/auth',
            json: {
              username: 'testuser',
              password: 'testpass'
            },
            capture: {
              json: '$.token',
              as: 'authToken'
            }
          }
        },
        {
          post: {
            url: '/wp-json/skylearn-billing-pro/v1/transactions',
            headers: {
              'Authorization': 'Bearer {{ authToken }}'
            },
            json: {
              amount: '{{ amount }}',
              currency: 'USD',
              course_id: '{{ course_id }}',
              payment_method: 'test_card'
            },
            expect: [
              { statusCode: [201, 400, 401] }
            ]
          }
        }
      ]
    },
    {
      name: 'Admin Dashboard',
      weight: 10,
      flow: [
        {
          post: {
            url: '/wp-json/skylearn-billing-pro/v1/auth',
            json: {
              username: 'admin',
              password: 'admin'
            },
            capture: {
              json: '$.token',
              as: 'adminToken'
            }
          }
        },
        {
          get: {
            url: '/wp-json/skylearn-billing-pro/v1/admin/stats',
            headers: {
              'Authorization': 'Bearer {{ adminToken }}'
            },
            expect: [
              { statusCode: [200, 401] }
            ]
          }
        },
        {
          get: {
            url: '/wp-json/skylearn-billing-pro/v1/admin/transactions',
            headers: {
              'Authorization': 'Bearer {{ adminToken }}'
            },
            expect: [
              { statusCode: [200, 401] }
            ]
          }
        }
      ]
    },
    {
      name: 'Webhook Processing',
      weight: 5,
      flow: [
        {
          post: {
            url: '/wp-json/skylearn-billing-pro/v1/webhooks/lemon-squeezy',
            headers: {
              'X-Signature': 'test_signature_{{ $randomString() }}'
            },
            json: {
              event: 'payment.completed',
              data: {
                transaction_id: 'txn_{{ $randomString() }}',
                amount: 99.99,
                currency: 'USD',
                customer_email: '{{ email }}'
              }
            },
            expect: [
              { statusCode: [200, 400] }
            ]
          }
        }
      ]
    }
  ]
};