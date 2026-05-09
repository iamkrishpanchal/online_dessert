# Voucher System Implementation

## Overview
A 25% discount voucher system has been implemented that allows:
- **New users** to claim a one-time 25% discount voucher
- **Prevents duplicate claims** - users can only claim once
- **Admin tracking** of all claims and usage

## Files Created/Modified

### New Files:
1. **claim_voucher.php** - Handles voucher claim requests
2. **check_voucher_status.php** - Checks if user has claimed voucher
3. **verify_voucher_system.php** - Admin verification page
4. **admin_voucher_dashboard.php** - Complete voucher management dashboard
5. **voucher_setup.php** - Initial setup script

### Modified Files:
1. **index.php** - Added voucher claim UI and logic

## How It Works

### User Experience:
1. **Not Logged In**: User sees the newsletter subscription form
2. **Logged In + Haven't Claimed**: User sees "Claim 25% Discount Voucher" button
3. **Logged In + Already Claimed**: User sees "Voucher Already Claimed" message

### Claiming Process:
1. User clicks "Claim 25% Discount Voucher" button
2. System checks if user has already claimed
3. If not claimed: Creates record in `tbl_voucher_claims` and shows success
4. If already claimed: Shows error "You already claimed this voucher"
5. Voucher stored in session for checkout use

### Database Schema:
```sql
CREATE TABLE tbl_voucher_claims (
    claim_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    voucher_code VARCHAR(100) DEFAULT '25PERCENT',
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_in_order_id INT DEFAULT NULL,
    status ENUM('active', 'used') DEFAULT 'active',
    UNIQUE KEY unique_user_voucher (user_id, voucher_code)
)
```

## Pages to Access

### For Users:
- **Home Page**: http://localhost/Sem-6 Project/user/index.php
  - Logged in users see the voucher claim button
  - Voucher section displays in "Get 25% Discount" area

### For Admins:
- **Voucher Dashboard**: http://localhost/Sem-6 Project/user/admin_voucher_dashboard.php
  - View total claims, active vouchers, used vouchers
  - See conversion rate
  - Track all user claims

### For Verification:
- **System Check**: http://localhost/Sem-6 Project/user/verify_voucher_system.php
  - Verify database table structure
  - See all voucher claims

## Key Features

✓ **One-time Claim**: Each user can claim only once
✓ **Claim Validation**: System prevents duplicate claims
✓ **User Notification**: Clear messages for all scenarios
✓ **Admin Tracking**: Complete dashboard for monitoring claims
✓ **Error Handling**: Proper error messages and logging
✓ **Session Storage**: Voucher stored in session for checkout integration

## Integration with Checkout

To fully integrate the voucher into the checkout process, the following needs to be done:

1. **Apply Discount**: When user checkout with claimed voucher, apply 25% discount
2. **Mark as Used**: After order is placed, mark voucher as 'used' and link to order_id
3. **Database Update**: Update the `used_in_order_id` field when voucher is used

Example code to add in checkout.php:
```php
if (!empty($_SESSION['voucher_claimed'])) {
    $discount = floatval($total) * 0.25; // 25% off
    $final_total = $total - $discount;
    
    // After order is created, mark voucher as used
    $mark_used_sql = "UPDATE tbl_voucher_claims SET status = 'used', used_in_order_id = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $mark_used_sql);
    mysqli_stmt_bind_param($stmt, 'ii', $order_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
}
```

## Testing

To test the system:
1. Login as a user (or create new account)
2. Go to homepage
3. Click "Claim 25% Discount Voucher" button
4. Verify success message shows
5. Reload page - should show "Voucher Already Claimed"
6. Try from different user account - should be able to claim

## Status
✅ **Voucher System Fully Implemented**
⏳ **Pending: Checkout Integration** (to be done in next step)
