# Cashfree Payment Gateway - Complete Integration Guide

## Overview

The Cashfree payment gateway is fully integrated into the Kupiana e-commerce platform checkout flow. Customers can now make secure online payments using cards, UPI, wallets, and netbanking.

## Payment Methods Available

1. **Cash on Delivery (COD)** - Offline payment after delivery
2. **Cashfree** - Online payment with multiple payment options

## End-to-End Payment Flow

### 1. Checkout Page (/checkout)
```
Customer adds products → Cart → Checkout form
↓
Customer fills shipping details
↓
Selects payment method (COD or Cashfree)
↓
Clicks "Place Order"
```

**Controller**: `application/modules/catalog/controllers/Checkout.php`
**View**: `application/modules/catalog/views/checkout.php`

### 2. Order Creation
- Form validation checks all required fields
- Payment method must be 'cod' or 'cashfree'
- Order created in `orders` table with `payment_method` set
- Payment record created in `payments` table with status='pending'

**Code**: `Checkout.php` → `Order_model->create_from_cart()`

### 3. Cashfree Payment Initialization (/payments/cashfree/pay/{order_id})
```
Redirect to payment handler
↓
Load Cashfree gateway library
↓
Check if payment already has gateway_order_id
↓
If not, create order in Cashfree system
  ├─ Call Cashfree API: POST /orders
  ├─ Send: amount, customer details, callback URLs
  └─ Receive: order_id, payment_link, status
↓
Store gateway_order_id in payment record
↓
Generate payment link
↓
Display payment page with checkout link
```

**Controller**: `application/modules/catalog/controllers/Payments.php::cashfree_pay()`
**View**: `application/modules/catalog/views/payment_cashfree.php`
**Gateway**: `application/libraries/Cashfree_gateway.php`

### 4. Customer Payment on Cashfree
```
User clicks "Pay with Cashfree"
↓
Redirected to Cashfree checkout page
↓
Selects payment method (card, UPI, wallet, etc)
↓
Enters payment details
↓
Completes authentication
↓
Cashfree processes payment
```

### 5. Payment Callback (Return URL: /payments/cashfree/verify)
```
Cashfree redirects to return_url with payment status
↓
Receive parameters:
  - order_id: Cashfree order ID
  - cf_payment_id: Cashfree payment ID
  - cf_signature: Signature for verification
↓
Verify signature using CASHFREE_SECRET_KEY
↓
If signature valid:
  ├─ Mark payment as captured
  ├─ Update order payment_status
  └─ Redirect to success page
↓
If signature invalid:
  ├─ Mark payment as failed
  ├─ Set error message
  └─ Redirect to failed page
```

**Controller**: `application/modules/catalog/controllers/Payments.php::cashfree_verify()`

### 6. Webhook Handling (POST /payments/cashfree/webhook)
```
Cashfree sends real-time payment updates
↓
Verify webhook signature
↓
Process based on event type:
  - PAYMENT_SUCCESS_WEBHOOK → Mark as captured
  - PAYMENT_AUTHORIZED_WEBHOOK → Mark as captured
  - PAYMENT_FAILED_WEBHOOK → Mark as failed
  - PAYMENT_DECLINED_WEBHOOK → Mark as failed
↓
Update payment status in database
↓
Log webhook event
↓
Return "ok" to acknowledge receipt
```

**Controller**: `application/modules/catalog/controllers/Payments.php::cashfree_webhook()`

### 7. Success Page (/checkout/success/{order_id})
```
Show order confirmation
↓
Display order details and status
↓
Show payment status (paid/pending/failed)
↓
Provide options to:
  - View full order details
  - Track order
  - Continue shopping
```

## Configuration

### Environment Variables
Required in `.env` file:
```env
CASHFREE_APP_ID=your_app_id
CASHFREE_SECRET_KEY=your_secret_key
```

The `kupiana_env()` function in `application/config/config.php` loads these from the `.env` file.

### Cashfree Gateway Library
**File**: `application/libraries/Cashfree_gateway.php`

**Key Methods**:
- `enabled()` - Check if Cashfree is configured
- `create_order($order, $payment)` - Create payment order in Cashfree
- `get_payment_link($payment)` - Extract checkout URL from response
- `verify_webhook_signature($order_id, $signature, $data)` - Verify webhook authenticity

### Routes
**File**: `application/config/routes.php`

```php
$route['payments/cashfree/pay/(:num)'] = 'catalog/payments/cashfree_pay/$1';
$route['payments/cashfree/verify'] = 'catalog/payments/cashfree_verify';
$route['payments/cashfree/webhook'] = 'catalog/payments/cashfree_webhook';
```

## Database Schema

### Orders Table
- `payment_method` - Set to 'cashfree' or 'cod'
- `payment_status` - pending, paid, failed, refunded

### Payments Table
- `gateway` - 'cashfree'
- `gateway_order_id` - Cashfree order ID (e.g., 'order_1_1692360000')
- `gateway_payment_id` - Cashfree payment ID
- `gateway_signature` - Payment verification signature
- `gateway_response` - Full Cashfree API response (JSON)
- `status` - pending, captured, failed

### Payment Logs Table
- `gateway` - 'cashfree'
- `event` - order.create, webhook.received, etc
- `request` - API request data (JSON)
- `response` - API response data (JSON)

## API Integration

### Cashfree Create Order API
```
POST https://api.cashfree.com/pg/orders

Headers:
  Content-Type: application/json
  X-Client-Id: {CASHFREE_APP_ID}
  X-Client-Secret: {CASHFREE_SECRET_KEY}
  x-api-version: 2023-08-01

Request Body:
{
  "order_id": "order_1_1692360000",
  "order_amount": 1000.50,
  "order_currency": "INR",
  "order_note": "Order for ORD-001",
  "customer_details": {
    "customer_id": "customer_1",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "9876543210"
  },
  "order_meta": {
    "notify_url": "https://yoursite.com/payments/cashfree/webhook",
    "return_url": "https://yoursite.com/payments/cashfree/verify"
  }
}

Response:
{
  "order_id": "order_1_1692360000",
  "order_amount": 1000.50,
  "order_currency": "INR",
  "order_status": "PENDING",
  "payments_links": [
    {
      "url": "https://checkout.cashfree.com/pay/...",
      "type": "cfl_link"
    }
  ]
}
```

## Signature Verification

All callbacks and webhooks include a signature for verification:

```php
$message = $order_id . $secret_key;
$expected_signature = hash('sha256', $message);
$valid = hash_equals($expected_signature, $provided_signature);
```

## Error Handling

### API Failures
- If Cashfree API is unreachable: Error logged, user sees "Cashfree order could not be created"
- If cURL is not available: Error logged, payment creation fails

### Signature Verification Failures
- Invalid signature redirected to `/payments/failed/{order_id}`
- Error message: "Payment verification failed"

### Payment Failures
- Failed payment marked in database
- Webhook processes failure events
- User sees failure page with retry option

## Testing

### Test Credentials
- App ID: `TEST111879745e6f2e178aa07e90f87847978111`
- Secret: `cfsk_ma_test_ee8c5e4ac1836e74334aafc7fe7a017a_1b37b21c`

### Test Cards
- **Success**: 4111111111111111
- **Decline**: 4000000000000002
- **Pending**: 4012888888881881
- Expiry: Any future date (e.g., 12/25)
- CVV: Any 3 digits (e.g., 123)

### Test Flow
1. Go to /checkout
2. Fill shipping details
3. Select "Cashfree Payment Gateway"
4. Place order
5. Use test card details on Cashfree page
6. Verify payment success

## Logging

All payment events are logged to help with debugging:

```
application/logs/log-YYYY-MM-DD.php
```

Common log entries:
- "Cashfree API response (200): ..." - API call successful
- "Cashfree cURL error: ..." - Network error
- "webhook.received" - Webhook received
- Admin CRUD logs payment updates

## Production Deployment

1. Update credentials in `.env`:
   ```env
   CASHFREE_APP_ID=your_production_app_id
   CASHFREE_SECRET_KEY=your_production_secret_key
   ```

2. Verify HTTPS is enabled (required by Cashfree)

3. Update webhook URL in Cashfree Dashboard:
   - Notify URL: `https://yourdomain.com/payments/cashfree/webhook`
   - Return URL: `https://yourdomain.com/payments/cashfree/verify`

4. Test with production account's test mode first

5. Enable live payments

## Troubleshooting

### "Cashfree not configured" in checkout
- Check `.env` has `CASHFREE_APP_ID` and `CASHFREE_SECRET_KEY`
- Verify credentials are correct
- Check `kupiana_env()` function is loading .env properly

### "Unable to load requested class: Curl"
- Check PHP has cURL extension installed
- The gateway now uses native PHP cURL functions, not a library

### Payment link not generated
- Check Cashfree API response in logs
- Verify API credentials
- Check network connectivity

### Webhook not received
- Verify webhook URL is publicly accessible
- Check Cashfree dashboard webhook settings
- Monitor logs for webhook receipt

### Signature verification failed
- Verify SECRET_KEY is correct
- Check order_id matches what was sent
- Compare expected vs provided signature in logs

## Support

- Cashfree API Docs: https://dev.cashfree.com/payments/
- Dashboard: https://dashboard.cashfree.com/
- API Reference: https://dev.cashfree.com/payments/payments-api/api-reference/

