# Cashfree Payment Gateway - Quick Test Guide

## Prerequisites

1. **Cashfree Sandbox Account**
   - Go to https://sandbox.dashboard.cashfree.com/
   - Create an account if you don't have one
   - Get your App ID and Secret Key

2. **Update .env File**
   ```env
   CASHFREE_APP_ID=your_sandbox_app_id
   CASHFREE_SECRET_KEY=your_sandbox_secret_key
   ```

3. **Restart your application** (clear cache if needed)

## Quick Test (5 minutes)

### Step 1: Add Items to Cart
1. Open http://localhost:8000/shop
2. Add any product to cart
3. Go to cart and proceed to checkout

### Step 2: Checkout Form
1. Fill in the form:
   ```
   First Name: Test
   Last Name: User
   Email: test@example.com
   Phone: 9876543210
   Address Line 1: 123 Test Street
   City: Bangalore
   State: Karnataka
   PIN: 560001
   ```

### Step 3: Select Cashfree Payment
1. Under "Payment" section, select **"Cashfree Payment Gateway"**
2. Click **"Place Order"**

### Step 4: Cashfree Checkout
1. You'll be redirected to Cashfree payment page
2. Enter test card details:
   - **Card Number**: `4111111111111111`
   - **Expiry**: Any future date (e.g., 12/25)
   - **CVV**: Any 3 digits (e.g., 123)
   - **Cardholder Name**: Test User
3. Click **"Pay Now"**
4. Simulate OTP if prompted (any number)

### Step 5: Verify Success
1. You should see success message
2. Order should be marked as PAID
3. Check admin panel > Payments to see transaction

## Test Cards for Different Scenarios

### ✅ Success (Card Accepted)
- **Card**: 4111111111111111
- **Expiry**: Any future date
- **CVV**: Any 3 digits

### ❌ Decline (Card Rejected)  
- **Card**: 4000000000000002
- **Expiry**: Any future date
- **CVV**: Any 3 digits

### 🔄 Pending (Requires Verification)
- **Card**: 4012888888881881
- **Expiry**: Any future date
- **CVV**: Any 3 digits

## Verification

After successful payment:

1. **Check Order Status**
   - Account > My Orders > See payment_status = "paid"

2. **Check Payment Record**
   - Admin > Payments > Find transaction with gateway="cashfree"

3. **Check Logs**
   - View `application/logs/log-*.php`
   - Look for "Cashfree API response" entries

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Cashfree is not configured" | Check CASHFREE_APP_ID and CASHFREE_SECRET_KEY in .env |
| Payment link not generated | Verify API credentials and network access to Cashfree |
| Payment captured but order not updated | Check webhook in application logs |
| Signature verification failed | Ensure SECRET_KEY is copied exactly from dashboard |

## API Testing (Advanced)

### Test Create Order API

```bash
curl -X POST https://sandbox.cashfree.com/pg/orders \
  -H "Content-Type: application/json" \
  -H "X-Client-Id: YOUR_APP_ID" \
  -H "X-Client-Secret: YOUR_SECRET_KEY" \
  -H "x-api-version: 2023-08-01" \
  -d '{
    "order_id": "order_test_'$(date +%s)'",
    "order_amount": 100.00,
    "order_currency": "INR",
    "order_note": "Test Order"
  }'
```

Expected response:
```json
{
  "order_id": "order_test_1692360000",
  "order_amount": 100.00,
  "order_currency": "INR",
  "order_status": "PENDING",
  "payments_links": [
    {
      "url": "https://checkout.cashfree.com/...",
      "type": "cfl_link"
    }
  ]
}
```

## Next Steps

1. ✅ Complete the quick test
2. ✅ Verify orders are created correctly
3. ✅ Check payment records in database
4. ✅ Review Cashfree dashboard for transactions
5. ✅ Read full documentation: [CASHFREE_INTEGRATION.md](CASHFREE_INTEGRATION.md)
6. ✅ Deploy to production when ready

## Support Resources

- **Cashfree Docs**: https://dev.cashfree.com/payments/
- **Test Cards**: https://dev.cashfree.com/payments/payments-api/resources/testing/
- **API Reference**: https://dev.cashfree.com/payments/payments-api/api-reference/
- **Dashboard**: https://sandbox.dashboard.cashfree.com/

---

**Happy Testing! 🚀**
