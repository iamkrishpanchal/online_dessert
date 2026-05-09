# Payment Gateway Integration Quick Reference

## How the Payment Callback Works

Your payment gateway (Razorpay, PayPal, Stripe, CCAvenue, etc.) should be configured to call back to your server after payment processing.

### Callback URL
```
https://yourdomain.com/Sem-6%20Project/user/payment_callback.php
```

### Parameters Expected
Your gateway should POST the following to the callback:
- `order_id` → Your internal order ID (integer)
- `status` → 'success' or 'fail'

---

## Example Integrations

### **Razorpay Integration**

In your checkout form/button that redirects to Razorpay:

```php
<?php
// After order is created
$order_id = 123; // from tbl_orders.order_id
$amount = 64000; // in paise (₹640.00)

$razorpay_options = [
    'key' => 'your_razorpay_key_id',
    'amount' => $amount,
    'currency' => 'INR',
    'name' => 'FoodMart',
    'description' => 'Order #' . $order_id,
    'order_id' => $order_id, // send to Razorpay
];
?>

<form id="payment-form">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        var options = {
            key: "<?php echo $razorpay_options['key']; ?>",
            amount: <?php echo $razorpay_options['amount']; ?>,
            currency: "INR",
            name: "FoodMart",
            description: "Order",
            order_id: "<?php echo $razorpay_options['order_id']; ?>",
            handler: function (response) {
                // On success
                console.log('Payment successful:', response);
                
                // Call callback to update order
                fetch('payment_callback.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'order_id=<?php echo $order_id; ?>&status=success'
                }).then(() => {
                    alert('Payment successful! Order confirmed.');
                    window.location.href = 'orders.php';
                });
            },
            prefill: {
                email: "<?php echo $_SESSION['email']; ?>",
                contact: "<?php echo $_SESSION['phone']; ?>"
            },
            theme: {
                color: "#ff6b6b"
            }
        };
        
        var rzp = new Razorpay(options);
        document.getElementById('pay-btn').onclick = function(e) {
            e.preventDefault();
            rzp.open();
        }
    </script>
    <button type="button" id="pay-btn" class="btn btn-success">Pay with Razorpay</button>
</form>
```

---

### **PayPal Integration**

```html
<!-- PayPal checkout button -->
<div id="paypal-button-container"></div>

<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID"></script>
<script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: { value: "<?php echo $total_amount; ?>" }
                }],
                custom_id: "<?php echo $order_id; ?>" // your order ID
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(orderData) {
                // Send callback to our server
                fetch('payment_callback.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'order_id=<?php echo $order_id; ?>&status=success'
                }).then(() => {
                    alert('Payment successful!');
                    window.location.href = 'orders.php';
                });
            });
        },
        onError: function(err) {
            fetch('payment_callback.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'order_id=<?php echo $order_id; ?>&status=fail'
            });
            alert('Payment failed. Please try again.');
        }
    }).render('#paypal-button-container');
</script>
```

---

### **Stripe Integration**

```php
<?php
require 'vendor/autoload.php';
\Stripe\Stripe::setApiKey('sk_live_your_secret_key');

$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency' => 'inr',
            'product_data' => ['name' => 'Order #' . $order_id],
            'unit_amount' => intval($total_amount * 100),
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => 'https://yourdomain.com/Sem-6%20Project/user/payment_success.php?order_id=' . $order_id,
    'cancel_url' => 'https://yourdomain.com/Sem-6%20Project/user/payment_cancel.php?order_id=' . $order_id,
    'metadata' => ['order_id' => $order_id],
]);

// Redirect to Stripe checkout
header('Location: ' . $session->url);
?>

<!-- payment_success.php -->
<?php
$order_id = $_GET['order_id'] ?? 0;
// Call callback
file_get_contents('payment_callback.php?order_id=' . $order_id . '&status=success');
echo 'Payment successful!';
?>
```

---

### **CCAvenue Integration (For India)**

```php
<?php
$access_code = 'your_access_code';
$merchant_id = 'your_merchant_id';
$order_id = 123;
$amount = 640.00;

$data = array(
    'merchant_id' => $merchant_id,
    'order_id' => $order_id,
    'amount' => $amount,
    'currency' => 'INR',
    'redirect_url' => 'https://yourdomain.com/Sem-6%20Project/user/payment_callback.php',
    'cancel_url' => 'https://yourdomain.com/Sem-6%20Project/user/checkout.php',
    'language' => 'EN'
);

// Encrypt and send to CCAvenue gateway
?>
```

---

## Webhook/Callback Best Practices

1. **Verify Payment Status**: Always verify with the gateway API before trusting the callback
2. **Idempotency**: Check if order already updated before processing callback
3. **Logging**: Log all callbacks for troubleshooting
4. **Security**: Validate callback signature if gateway provides it

### Enhanced Callback with Verification

```php
<?php
session_start();
include 'connection.php';

$order_id = intval($_POST['order_id'] ?? 0);
$transaction_id = $_POST['transaction_id'] ?? '';
$signature = $_POST['signature'] ?? '';

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order']);
    exit;
}

// STEP 1: Verify signature (gateway-specific)
// Example for Razorpay:
$generated_signature = hash_hmac('sha256', $order_id, 'your_razorpay_secret');
if ($generated_signature !== $signature) {
    // Log suspicious activity
    file_put_contents('payment_logs.txt', 
        date('Y-m-d H:i:s') . " - Invalid signature for order $order_id\n", 
        FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Signature mismatch']);
    exit;
}

// STEP 2: Verify payment status with gateway API
// Example for Razorpay:
try {
    $razorpay_client = new Api('key_id', 'key_secret');
    $payment = $razorpay_client->payment->fetch($transaction_id);
    if ($payment['status'] !== 'captured') {
        throw new Exception('Payment not captured');
    }
} catch (Exception $e) {
    file_put_contents('payment_logs.txt',
        date('Y-m-d H:i:s') . " - Verification failed: " . $e->getMessage() . "\n",
        FILE_APPEND);
    exit;
}

// STEP 3: Check if order already updated (idempotency)
$existing = mysqli_query($conn, "SELECT order_id FROM tbl_orders WHERE order_id=? AND payment_status='Paid'");
if (mysqli_num_rows($existing) > 0) {
    echo json_encode(['success' => true, 'message' => 'Already processed']);
    exit;
}

// STEP 4: Update order safely
$upd = mysqli_prepare($conn, "UPDATE tbl_orders SET order_status='Confirmed', payment_status='Paid' WHERE order_id=?");
mysqli_stmt_bind_param($upd, 'i', $order_id);
mysqli_stmt_execute($upd);
mysqli_stmt_close($upd);

// STEP 5: Insert notification
$ins = mysqli_prepare($conn, 
    "INSERT INTO tbl_notifications (user_id, order_id, title, message) 
     VALUES ((SELECT user_id FROM tbl_orders WHERE order_id=?), ?, 'Payment Received', 'Payment successful')");
mysqli_stmt_bind_param($ins, 'ii', $order_id, $order_id);
mysqli_stmt_execute($ins);
mysqli_stmt_close($ins);

file_put_contents('payment_logs.txt',
    date('Y-m-d H:i:s') . " - Order $order_id payment confirmed\n",
    FILE_APPEND);

echo json_encode(['success' => true]);
mysqli_close($conn);
?>
```

---

## Testing Payment Flows

### **Test Mode (COD - No Gateway)**
1. Select "Cash on Delivery" at checkout
2. Order created immediately with `order_status = 'Confirmed'`
3. Notification: *"Order confirmed"*

### **Test Mode (Online - Sandbox)**
Use your gateway's sandbox credentials:
- Razorpay: Use test key/secret from dashboard
- PayPal: Use sandbox business account
- Stripe: Use pk_test_... keys

Test cards:
- **Visa**: `4111 1111 1111 1111` (any future expiry, any CVV)
- **Mastercard**: `5555 5555 5555 4444`
- **Amex**: `3782 822463 10005`

### **Callback Simulator**
For testing without actual gateway:

```bash
# Simulate successful payment
curl -X POST http://localhost:8080/Sem-6%20Project/user/payment_callback.php \
  -d "order_id=1&status=success"

# Simulate failed payment
curl -X POST http://localhost:8080/Sem-6%20Project/user/payment_callback.php \
  -d "order_id=1&status=fail"
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Callback not received | Verify URL is correct, check gateway logs, allow public access to callback.php |
| Order not updating | Check database logs, verify SQL query, ensure prepared statements work |
| Notification not showing | Verify tbl_notifications exists, check INSERT query, check user_id FK |
| Signature mismatch | Regenerate signature with correct secret key, check byte order |
| Order created twice | Add idempotency check (check if payment_status already 'Paid') |
| Customer sees old status | Clear browser cache, verify JavaScript is fetching fresh data |

