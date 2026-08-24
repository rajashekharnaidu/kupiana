# Cashfree Credentials Setup Guide

## Current Issue
The credentials in `.env` are not valid Cashfree test credentials, which causes:
- API returns 401 "authentication Failed"
- Payment creation fails
- Users see error message instead of payment page

## How to Get Valid Cashfree Test Credentials

### Step 1: Create a Cashfree Account
1. Go to [Cashfree Dashboard](https://dashboard.cashfree.com/)
2. Sign up with email and password (or login if you have an account)
3. Complete email verification

### Step 2: Access Test/Sandbox Mode
1. In the dashboard, look for **Environment** selector (usually top-right)
2. Switch to **SANDBOX** mode (for testing)
3. You should see "Sandbox Environment" indicator

### Step 3: Get Your Test Credentials
1. Navigate to **Settings** → **API Keys**
2. Under **Sandbox/Test Environment**, you'll see:
   - **APP ID** (also called Client ID)
   - **SECRET KEY** (also called Client Secret)
3. Copy both values

### Step 4: Update Your .env File

Open `.env` file and update:

```env
# OLD (INVALID):
CASHFREE_APP_ID=TEST111879745e6f2e178aa07e90f87847978111
CASHFREE_SECRET_KEY=cfsk_ma_test_ee8c5e4ac1836e74334aafc7fe7a017a_1b37b21c

# NEW (FROM YOUR CASHFREE DASHBOARD):
CASHFREE_APP_ID=your_sandbox_app_id_here
CASHFREE_SECRET_KEY=your_sandbox_secret_key_here
```

### Step 5: Verify Configuration
1. Restart your application (or clear cache)
2. Check if `kupiana_env('CASHFREE_APP_ID')` loads the new value
3. In logs, you should see: `Cashfree initialized with credentials...`

## Testing with Valid Credentials

Once you have valid credentials, test the payment flow:

### Test Payment Flow:
```
1. Go to /checkout
2. Add a product and proceed
3. Fill shipping details
4. Select "Cashfree Payment Gateway"
5. Click "Place Order"
6. You should see the payment page with a REAL Cashfree checkout link
7. Click "Pay with Cashfree" button
8. You'll be redirected to actual Cashfree checkout
```

### Test Cards (Sandbox)
Once you have a valid checkout link, you can use these test cards:

| Card Type | Number | Expiry | CVV | Result |
|-----------|--------|--------|-----|--------|
| Visa Success | 4111111111111111 | Any future | Any 3 digits | ✅ Success |
| Visa Decline | 4000000000000002 | Any future | Any 3 digits | ❌ Decline |
| Mastercard Success | 5555555555554444 | Any future | Any 3 digits | ✅ Success |

Example:
- Card: `4111111111111111`
- Expiry: `12/25`
- CVV: `123`

## Troubleshooting

### "Authentication Failed" Error
- ❌ Invalid or expired credentials
- ❌ Credentials not loaded from .env
- ✅ Double-check credentials from Cashfree dashboard
- ✅ Verify APP_ID and SECRET_KEY are in correct fields

### Payment Link Shows Error
- ❌ Credentials are invalid
- ❌ You're using production credentials in sandbox
- ✅ Make sure you're in SANDBOX mode when copying credentials

### No Payment Link Generated
- ❌ Cashfree API returned error
- ❌ Check application logs for error details
- ✅ Verify credentials are correct in .env

### Getting "Check if there is a typo in checkout.cashfree.com"
- ❌ Browser tried to visit mock/fake payment URL
- ❌ Cashfree API is not being called successfully
- ✅ Verify your credentials are valid in Cashfree dashboard
- ✅ Make sure you're using SANDBOX credentials

## Production Deployment

When ready for production:

### Step 1: Switch to Production Credentials
1. In Cashfree dashboard, switch to **PRODUCTION** environment
2. Get production APP ID and SECRET KEY
3. Update .env with production credentials

### Step 2: Update Environment Setting
In `application/config/constants.php`:
```php
define('ENVIRONMENT', 'production');
```

### Step 3: Enable HTTPS
Production requires HTTPS. Verify:
```php
$config['base_url'] = 'https://yourdomain.com/';
```

### Step 4: Update Webhooks in Cashfree
In Cashfree dashboard:
1. Settings → Webhooks
2. Set Notify URL: `https://yourdomain.com/payments/cashfree/webhook`
3. Set Return URL: `https://yourdomain.com/payments/cashfree/verify`

### Step 5: Test with Production Sandbox
Before going live:
1. Use production credentials but stay in sandbox
2. Test with test cards
3. Verify everything works

### Step 6: Enable Live Payments
Once verified, enable live payments in Cashfree dashboard.

## API Versions & URLs

### Sandbox (Testing)
```
Base URL: https://sandbox.cashfree.com/pg
Environment: Test/Sandbox
Credentials: Sandbox APP ID & SECRET KEY
Use for: Development & Testing
```

### Production (Live)
```
Base URL: https://api.cashfree.com/pg
Environment: Production
Credentials: Production APP ID & SECRET KEY
Use for: Live Payments
```

## Documentation References

- **Cashfree Dashboard**: https://dashboard.cashfree.com/
- **Cashfree API Docs**: https://dev.cashfree.com/payments/
- **Getting Started**: https://dev.cashfree.com/payments/payments-api/integration-guide/
- **API Reference**: https://dev.cashfree.com/payments/payments-api/api-reference/orders/create-order

## Support

If you have issues:
1. Check logs at `application/logs/log-YYYY-MM-DD.php`
2. Look for "Cashfree API response" lines for details
3. Contact Cashfree support: https://support.cashfree.com/
4. Reference your Merchant ID in support tickets
