# Single Shop Order Policy Implementation

## Overview
This feature restricts users to order products from only **one shop per order**. If a user tries to add products from different shops to the same cart, the system will prevent it and prompt them to complete their current order before ordering from another shop.

## How It Works

### 1. **Add to Cart Validation** (`user/add_to_cart.php`)
When a user adds a product to their cart:
- The system checks if the cart already has items
- If yes, it compares the vendor_id of the new product with the existing items
- **If vendor IDs don't match**: A clear error message is shown:
  ```
  "You can only order from one shop per order. Your cart has items from "[Shop A]" 
  but you're trying to add an item from "[Shop B]". Please complete your current order first, 
  then you can order from a different shop."
  ```
- **If vendor IDs match**: The product is added to the cart normally

### 2. **Cart Display Enhancement** (`user/cart.php`)
The cart page now displays:
- **Shop Information**: Shows which shop the user is currently ordering from
- **Order Policy Notice**: Explains that only one shop can be ordered from per order
- **Clear Cart Button**: Allows users to clear their cart and switch to a different shop with one click
  - Button includes a confirmation dialog to prevent accidental clearing

### 3. **Checkout Safety Check** (`user/checkout.php`)
During checkout, there's an additional safety measure:
- The system validates that all items in the cart are from the same vendor
- If multiple vendors are detected (which shouldn't happen with the add-to-cart check):
  - The cart is cleared automatically
  - User sees an error message
  - They're redirected to the cart page

---

## User Experience Flow

### Scenario 1: Single Shop Order (Normal Flow)
```
1. User browses products from Shop A
2. User adds Product 1 from Shop A to cart
3. User adds Product 2 from Shop A to cart
4. User proceeds to checkout and places order
✅ Order placed successfully from Shop A only
```

### Scenario 2: Multiple Shop Order (Restricted Flow)
```
1. User adds Product 1 from Shop A to cart
2. Cart now shows: "You are currently ordering from Shop A"
3. User tries to add Product 2 from Shop B
4. System shows error: "You can only order from one shop per order..."
   - Option 1: Go back and remove items from Shop A, then add Shop B items
   - Option 2: Click "Clear Cart & Switch Shop" button
5. If user clears cart, they can now add items from Shop B
6. User completes Shop B order
7. Later, user can place a new order from Shop A
✅ Orders are successfully separated by shop
```

---

## Modified Files

### 1. `user/add_to_cart.php`
**New Logic**: Single vendor validation
- Added check to verify new product's vendor_id matches existing cart vendor_id
- If mismatch found, user gets informative error with shop names
- User is redirected back to the product page

### 2. `user/cart.php`
**New UI Elements**:
- Added shop order policy notice box
- Shows current shop name: "You are currently ordering from [Shop Name]"
- Added explanation: "You can only add products from this shop in a single order"
- Added "Clear Cart & Switch Shop" button with confirmation

### 3. `user/checkout.php`
**New Validation**:
- Added multi-vendor detection check after cart validation
- If multiple vendors detected, clears cart and shows error
- Prevents creation of orders with mixed vendors

---

## Key Features

✅ **User-Friendly**: Clear error messages explain the restriction  
✅ **Flexible**: Users can easily clear cart and switch shops  
✅ **Safe**: Multiple validation layers prevent order errors  
✅ **Compatible**: Works with existing cart and order systems  
✅ **Non-Breaking**: Doesn't affect existing orders in the database  

---

## Messages Shown to Users

### When Adding Product from Different Shop:
```
"You can only order from one shop per order. Your cart has items from 
"[Current Shop]" but you're trying to add an item from "[New Shop]". 
Please complete your current order first, then you can order from a different shop."
```

### In Cart Display:
```
📦 Shop Order Policy: You are currently ordering from "[Shop Name]"
You can only add products from this shop in a single order. 
To order from a different shop, you must complete this order first.

[Button: Clear Cart & Switch Shop]
```

---

## Testing Checklist

- [ ] Add product from Shop A to cart
- [ ] Try adding product from Shop B → Should show error
- [ ] Click back and add more products from Shop A → Should work
- [ ] Proceed to checkout → Should complete order
- [ ] Place new order from Shop B → Should work after clearing previous order
- [ ] Click "Clear Cart & Switch Shop" → Should clear cart and allow Shop B products
- [ ] Verify error message shows correct shop names

---

## Database Impact

**No database changes required.** The restriction is enforced at the application level using:
- Session cart validation
- Vendor ID comparison
- Error handling and user redirects

---

## Future Enhancements (Optional)

1. **Show "Switch Shop" recommendation**: When user tries to add from different shop, show "Complete Shop A order first, then order from Shop B"
2. **Automatic cart separation**: Create multiple orders automatically for different shops (removes restriction)
3. **Shop suggestions**: Show other available shops while restricting current order
4. **Loyalty tracking**: Track which shops users order from to suggest promotions

