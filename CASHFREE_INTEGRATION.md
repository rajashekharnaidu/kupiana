# Cashfree Payment Gateway Integration

This document describes the Cashfree payment gateway integration for the Kupiana e-commerce platform.

## Setup

### 1. Environment Configuration

Add your Cashfree credentials to `.env`:

```env
CASHFREE_APP_ID=your_app_id_here
CASHFREE_SECRET_KEY=your_secret_key_here
```

Get your credentials from: https://dashboard.cashfree.com/

### 2. Sandbox Testing

For testing, use Cashfree's sandbox environment:
- **Sandbox Dashboard**: https://sandbox.dashboard.cashfree.com/
- **API Base URL**: https://sandbox.cashfree.com/pg

Test credentials are available in the Cashfree documentation.

### 3. Test Card Details

Use these test cards in Cashfree sandbox:

**Success Cases:**
- Card: `4111111111111111`
- Expiry: Any future date (e.g., 12/25)
- CVV: Any 3 digits (e.g., 123)

**Decline Cases:**
- Card: `4000000000000002`
- Expiry: Any future date
- CVV: Any 3 digits

## Payment Flow

### Customer Checkout Flow

1. Customer adds items to cart and proceeds to checkout
2. Customer fills in shipping details
3. Customer selects **"Cashfree Payment Gateway"** as payment method
4. Customer clicks "Place Order"
5. System creates order and payment record
6. Customer is redirected to Cashfree payment page
7. Customer completes payment on Cashfree
8. Cashfree redirects back to application with payment status
9. Application confirms payment and shows success page

### Backend Flow

1. **Order Creation** (`/checkout`)
   - Order placed with `payment_method = 'cashfree'`
   - Redirects to `/payments/cashfree/pay/{order_id}`

2. **Payment Initialization** (`/payments/cashfree/pay/{order_id}`)
   - Creates payment record in `payments` table
   - Calls Cashfree API to create order
   - Stores Cashfree `order_id` and payment link
   - Displays payment page with link to Cashfree

3. **Payment Verification** (`/payments/cashfree/verify`)
   - Receives callback from Cashfree with payment status
   - Verifies signature using shared secret
   - Marks payment as captured or failed
   - Redirects to success or error page

4. **Webhook Handling** (`/payments/cashfree/webhook`)
   - Receives real-time payment status updates from Cashfree
   - Updates payment records for async payment confirmations
   - Handles success, failed, and declined events

## Testing Steps

### Test 1: Successful Payment

1. Go to http://localhost:8000/checkout
2. Fill in order details:
   - First Name: John
   - Email: john@example.com
   - Phone: 9876543210
   - Address: 123 Main St
   - City: Bangalore
   - State: Karnataka
   - PIN: 560001
3. Select **Cashfree Payment Gateway**
4. Click "Place Order"
5. You'll be redirected to Cashfree payment page
6. Use test card: `4111111111111111`
7. Fill in any future expiry and CVV
8. Complete the payment
9. You should see success page

### Test 2: Payment Failure

1. Repeat steps 1-4 above
2. Use test card: `4000000000000002`
3. Attempt payment
4. You should be redirected to payment failed page
5. Check admin panel to see failed payment record

## Database Changes

### Payments Table Schema

The following columns are used for Cashfree:

- `gateway` - Set to 'cashfree'
- `gateway_order_id` - Cashfree order ID (e.g., 'order_1_1692360000')
- `gateway_response` - Full Cashfree API response (JSON)
- `gateway_payment_id` - Cashfree payment ID
- `gateway_signature` - Payment verification signature
- `status` - Payment status (pending, captured, failed, etc)

## API Integration Details

### Create Order Endpoint

**Request:**
```
POST https://sandbox.cashfree.com/pg/orders
Content-Type: application/json
X-Client-Id: {APP_ID}
X-Client-Secret: {SECRET_KEY}
x-api-version: 2023-08-01

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
```

**Response:**
```json
{
  "order_id": "order_1_1692360000",
  "order_amount": 1000.50,
  "order_currency": "INR",
  "order_status": "PENDING",
  "payments_links": [
    {
      "url": "https://checkout.cashfree.com/payment/...",
      "type": "cfl_link"
    }
  ]
}
```

### Webhook Signature Verification

Cashfree sends webhooks with signature verification:

```
Signature Calculation:
message = order_id + secret_key
signature = SHA256(message)

Header: X-CF-Signature
```

## Code Structure

### Cashfree_gateway Library
Location: `application/libraries/Cashfree_gateway.php`

**Key Methods:**
- `enabled()` - Check if Cashfree is configured
- `create_order($order, $payment)` - Create Cashfree order
- `get_payment_link($payment)` - Extract payment link from response
- `verify_webhook_signature($order_id, $signature)` - Verify webhook
- `make_request($method, $endpoint, $body)` - Make API calls

### Payments Controller
Location: `application/modules/catalog/controllers/Payments.php`

**Cashfree Methods:**
- `cashfree_pay($order_id)` - Display payment page
- `cashfree_verify()` - Handle return callback
- `cashfree_webhook()` - Handle webhook

### Routes
Location: `application/config/routes.php`

```php
$route['payments/cashfree/pay/(:num)'] = 'catalog/payments/cashfree_pay/$1';
$route['payments/cashfree/verify'] = 'catalog/payments/cashfree_verify';
$route['payments/cashfree/webhook'] = 'catalog/payments/cashfree_webhook';
```

## Troubleshooting

### Payment Link Not Generated
- Check that CASHFREE_APP_ID and CASHFREE_SECRET_KEY are set in `.env`
- Check application logs in `application/logs/`
- Verify API credentials are correct

### Webhook Not Received
- Ensure webhook URL is publicly accessible
- Check firewall/security settings
- Verify webhook URL in Cashfree dashboard

### Signature Verification Failed
- Ensure SECRET_KEY is correct
- Check log files for signature mismatch details
- Verify X-CF-Signature header is being sent

### Order Not Marked as Paid
- Check if payment was actually successful in Cashfree dashboard
- Verify webhook is being called
- Check payment logs in admin panel

## Monitoring Payments

### Admin Panel
1. Go to Admin > Payments > Transactions
2. Filter by gateway 'cashfree'
3. View payment details and status

### Database Queries
```sql
-- View all Cashfree payments
SELECT * FROM payments WHERE gateway = 'cashfree' ORDER BY created_at DESC;

-- View pending payments
SELECT * FROM payments WHERE gateway = 'cashfree' AND status = 'pending';

-- View failed payments
SELECT * FROM payments WHERE gateway = 'cashfree' AND status = 'failed';
```

## Production Deployment

### 1. Update Base URL
In `application/libraries/Cashfree_gateway.php`, the production URL is already configured:
```php
protected $base_url = 'https://api.cashfree.com/pg';
```

### 2. Update Environment
```env
CASHFREE_APP_ID=your_production_app_id
CASHFREE_SECRET_KEY=your_production_secret_key
```

### 3. Update Webhook URL
In Cashfree Dashboard > Settings:
- Set Notify URL to: `https://yourdomain.com/payments/cashfree/webhook`
- Set Return URL to: `https://yourdomain.com/payments/cashfree/verify`

### 4. SSL Certificate
Ensure your website has valid SSL certificate (required by Cashfree)

### 5. Testing
Test with production account's test mode before going live

## Support

For Cashfree API documentation: https://dev.cashfree.com/payments/

For integration issues, check:
1. Application logs at `application/logs/log-*.php`
2. Cashfree Dashboard for payment status
3. Verify callback URLs are correct

