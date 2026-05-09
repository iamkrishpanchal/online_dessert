# Database Schema Documentation

## Overview
Complete database schema for user-vendor-product management system. This includes all tables needed for customers to browse shops, view products, place orders, and leave reviews.

---

## Tables Created

### 1. **tbl_users**
**Purpose:** Store all user information (customers, vendors, admins, riders)

| Column | Type | Description |
|--------|------|-------------|
| user_id | INT (PK, AUTO) | Unique user identifier |
| name | VARCHAR(255) | User's full name |
| email | VARCHAR(100) UNIQUE | User's email address |
| password | VARCHAR(255) | Hashed password |
| phone | VARCHAR(20) | Contact number |
| address | TEXT | Residential address |
| city | VARCHAR(100) | City name |
| pincode | VARCHAR(10) | Postal code |
| profile_image | VARCHAR(255) | Path to profile photo |
| user_type | ENUM | Type: customer, vendor, admin, rider |
| is_active | INT | 1=active, 0=inactive |
| created_at | TIMESTAMP | Account creation date |
| updated_at | TIMESTAMP | Last update date |

---

### 2. **tbl_vendors** ⭐ KEY TABLE
**Purpose:** Store vendor/shop information linked to users

| Column | Type | Description |
|--------|------|-------------|
| vendor_id | INT (PK, AUTO) | Unique vendor identifier |
| user_id | INT (FK) | Links to tbl_users |
| vendor_name | VARCHAR(255) | Vendor's legal name |
| **shop_name** | VARCHAR(255) UNIQUE | **Name shown in search** |
| email | VARCHAR(100) UNIQUE | Vendor's email |
| password | VARCHAR(255) | Vendor's password |
| phone | VARCHAR(20) | Shop contact number |
| address | TEXT | Shop address |
| city | VARCHAR(100) | Shop city |
| pincode | VARCHAR(10) | Shop postal code |
| latitude | DECIMAL | For map location |
| longitude | DECIMAL | For map location |
| description | TEXT | Shop description |
| image_path | VARCHAR(255) | Vendor profile image |
| logo_path | VARCHAR(255) | Shop logo |
| cover_image | VARCHAR(255) | Shop cover photo |
| opening_time | TIME | Shop opening time |
| closing_time | TIME | Shop closing time |
| is_online | INT | 1=online, 0=offline |
| is_active | INT | 1=active, 0=inactive |
| rating | DECIMAL(3,2) | Average shop rating (0-5) |
| total_reviews | INT | Number of reviews received |
| verification_status | ENUM | pending, approved, rejected |
| created_at | TIMESTAMP | Registration date |
| updated_at | TIMESTAMP | Last update date |

**Key:** Search by exact `shop_name` (case-insensitive)

---

### 3. **tbl_categories**
**Purpose:** Product categories (Desserts, Beverages, Snacks, etc.)

| Column | Type | Description |
|--------|------|-------------|
| category_id | INT (PK, AUTO) | Unique category ID |
| category_name | VARCHAR(100) UNIQUE | Category name |
| description | TEXT | Category description |
| image | VARCHAR(255) | Category icon/image |
| is_active | INT | 1=active, 0=inactive |
| created_at | TIMESTAMP | Creation date |

---

### 4. **tbl_product**
**Purpose:** Store all products from vendors

| Column | Type | Description |
|--------|------|-------------|
| product_id | INT (PK, AUTO) | Unique product ID |
| vendor_id | INT (FK) | Links to tbl_vendors |
| product_name | VARCHAR(255) | Product name |
| description | TEXT | Product description |
| category_id | INT (FK) | Links to tbl_categories |
| price | DECIMAL(10,2) | Original price |
| discount_price | DECIMAL(10,2) | Discounted price |
| discount_percent | DECIMAL(5,2) | Discount percentage |
| quantity_available | INT | Stock quantity |
| product_image | VARCHAR(255) | Product photo |
| is_vegetarian | INT | 1=veg, 0=non-veg |
| is_vegan | INT | 1=vegan, 0=not |
| ingredients | TEXT | Ingredients list |
| preparation_time | INT | Prep time in minutes |
| rating | DECIMAL(3,2) | Average product rating |
| total_ratings | INT | Number of ratings |
| is_active | INT | 1=active, 0=inactive |
| created_at | TIMESTAMP | Creation date |
| updated_at | TIMESTAMP | Last update date |

---

### 5. **tbl_cart**
**Purpose:** Shopping cart for users

| Column | Type | Description |
|--------|------|-------------|
| cart_id | INT (PK, AUTO) | Unique cart item ID |
| user_id | INT (FK) | Links to tbl_users |
| vendor_id | INT (FK) | Links to tbl_vendors |
| product_id | INT (FK) | Links to tbl_product |
| quantity | INT | Quantity in cart |
| price | DECIMAL(10,2) | Price per unit |
| subtotal | DECIMAL(10,2) | quantity × price |
| notes | TEXT | Special instructions |
| created_at | TIMESTAMP | Added to cart date |
| updated_at | TIMESTAMP | Last modified date |

---

### 6. **tbl_orders**
**Purpose:** Customer orders

| Column | Type | Description |
|--------|------|-------------|
| order_id | INT (PK, AUTO) | Unique order ID |
| order_number | VARCHAR(50) UNIQUE | Customer-facing order number |
| user_id | INT (FK) | Customer ID |
| vendor_id | INT (FK) | Vendor/Shop ID |
| subtotal | DECIMAL(10,2) | Items total |
| tax | DECIMAL(10,2) | Tax amount |
| delivery_charges | DECIMAL(10,2) | Delivery fee |
| discount | DECIMAL(10,2) | Discount applied |
| total_amount | DECIMAL(10,2) | Final amount |
| delivery_address | TEXT | Delivery location |
| delivery_city | VARCHAR(100) | Delivery city |
| delivery_pincode | VARCHAR(10) | Delivery postal code |
| phone | VARCHAR(20) | Contact for delivery |
| special_instructions | TEXT | Special notes |
| order_status | ENUM | pending, confirmed, preparing, dispatched, delivered, cancelled |
| payment_status | ENUM | pending, completed, failed, refunded |
| payment_method | VARCHAR(50) | COD, Card, UPI, etc. |
| transaction_id | VARCHAR(100) | Payment transaction ID |
| estimated_delivery_time | DATETIME | Expected delivery time |
| actual_delivery_time | DATETIME | Actual delivery time |
| rider_id | INT | Assigned rider ID |
| created_at | TIMESTAMP | Order placement date |
| updated_at | TIMESTAMP | Last update date |

---

### 7. **tbl_order_items**
**Purpose:** Individual items in an order

| Column | Type | Description |
|--------|------|-------------|
| order_item_id | INT (PK, AUTO) | Unique item ID |
| order_id | INT (FK) | Links to tbl_orders |
| product_id | INT (FK) | Links to tbl_product |
| product_name | VARCHAR(255) | Product name (snapshot) |
| quantity | INT | Quantity ordered |
| unit_price | DECIMAL(10,2) | Price at time of order |
| subtotal | DECIMAL(10,2) | quantity × unit_price |
| special_instructions | TEXT | Item-specific notes |

---

### 8. **tbl_reviews**
**Purpose:** Customer reviews and ratings for vendors/products

| Column | Type | Description |
|--------|------|-------------|
| review_id | INT (PK, AUTO) | Unique review ID |
| user_id | INT (FK) | Customer ID |
| vendor_id | INT (FK) | Vendor ID (optional) |
| product_id | INT (FK) | Product ID (optional) |
| order_id | INT (FK) | Order ID |
| rating | INT (CHECK 1-5) | Rating 1-5 stars |
| title | VARCHAR(255) | Review title |
| review_text | TEXT | Review content |
| helpful_count | INT | Number of helpful votes |
| is_verified_purchase | INT | 1=verified, 0=unverified |
| created_at | TIMESTAMP | Review date |
| updated_at | TIMESTAMP | Last update date |

---

### 9. **tbl_addresses**
**Purpose:** Multiple delivery addresses per user

| Column | Type | Description |
|--------|------|-------------|
| address_id | INT (PK, AUTO) | Unique address ID |
| user_id | INT (FK) | Links to tbl_users |
| address_type | ENUM | home, office, other |
| full_address | TEXT | Complete address |
| city | VARCHAR(100) | City name |
| state | VARCHAR(100) | State/Province |
| pincode | VARCHAR(10) | Postal code |
| latitude | DECIMAL | For map |
| longitude | DECIMAL | For map |
| phone | VARCHAR(20) | Address contact |
| is_default | INT | 1=default, 0=not |
| created_at | TIMESTAMP | Creation date |

---

### 10. **tbl_favorites**
**Purpose:** User's favorite shops and products (wishlist)

| Column | Type | Description |
|--------|------|-------------|
| favorite_id | INT (PK, AUTO) | Unique favorite ID |
| user_id | INT (FK) | Customer ID |
| vendor_id | INT (FK) | Shop ID (optional) |
| product_id | INT (FK) | Product ID (optional) |
| created_at | TIMESTAMP | Added to favorites date |

---

### 11. **tbl_coupons**
**Purpose:** Discount codes and promotional offers

| Column | Type | Description |
|--------|------|-------------|
| coupon_id | INT (PK, AUTO) | Unique coupon ID |
| coupon_code | VARCHAR(50) UNIQUE | Coupon code (e.g., "SAVE10") |
| discount_type | ENUM | percentage or fixed |
| discount_value | DECIMAL(10,2) | Discount amount |
| minimum_order_amount | DECIMAL(10,2) | Min order to use coupon |
| maximum_discount | DECIMAL(10,2) | Max discount allowed |
| vendor_id | INT (FK) | Shop-specific (NULL=global) |
| valid_from | DATETIME | Start date |
| valid_till | DATETIME | Expiry date |
| max_uses | INT | Max times coupon can be used |
| used_count | INT | Times already used |
| is_active | INT | 1=active, 0=inactive |
| created_at | TIMESTAMP | Creation date |

---

### 12. **tbl_notifications**
**Purpose:** Store messages sent to users (order confirmations, updates, etc.)

| Column | Type | Description |
|--------|------|-------------|
| notification_id | INT (PK, AUTO) | Unique notification ID |
| user_id | INT (FK) | Recipient user |
| order_id | INT (FK) | Related order (optional) |
| title | VARCHAR(255) | Short headline |
| message | TEXT | Full message body |
| status | ENUM('unread','read') | Unread/read flag |
| created_at | TIMESTAMP | When notification was created |

---

---

## Database Views

### **vw_vendor_details**
Optimized view for displaying vendor information to users
- Fields: vendor_id, shop_name, vendor_name, phone, address, rating, total_products, avg_rating
- Automatically filters inactive/unverified vendors

### **vw_products_with_vendor**
Join products with vendor and category information
- Useful for product listing pages
- Includes product, category, and vendor details

### **vw_order_summary**
Summary view for order management
- Shows order + customer + vendor details
- Used in dashboards

---

## How to Use

### 1. Run Setup Script
```bash
# Open in browser:
http://localhost/Sem-6%20Project/setup_tables.php
```

### 2. Verify Tables
```sql
SHOW TABLES;
DESCRIBE tbl_vendors;
```

### 3. Fetch Shop by Name (in menu.php)
```php
$shop_name = "Your Shop";
$query = "SELECT * FROM tbl_vendors WHERE shop_name = '" . mysqli_real_escape_string($conn, $shop_name) . "'";
```

### 4. Get Shop's Products
```php
$vendor_id = 5;
$query = "SELECT * FROM vw_products_with_vendor WHERE vendor_id = " . $vendor_id;
```

---

## Relationships

```
tbl_users (1) ──→ (many) tbl_vendors
    ↓                          ↓
    └─────────────(1)─────(many) tbl_product
                               ↓
                        tbl_categories

tbl_users (1) ──→ (many) tbl_cart
tbl_vendors (1) ──→ (many) tbl_cart
tbl_product (1) ──→ (many) tbl_cart

tbl_users (1) ──→ (many) tbl_orders
tbl_vendors (1) ──→ (many) tbl_orders
    ↓
    └─(1)──→ (many) tbl_order_items ←──(1)─ tbl_product
```

---

## Database Constraints (Foreign Keys)

All foreign key relationships are set with:
- `ON DELETE CASCADE` - Delete related records automatically
- `ON DELETE RESTRICT` - Prevent deletion if children exist

---

## Performance Indexes

Indexes are created on:
- `tbl_vendors.shop_name` - Fast shop search
- `tbl_product.vendor_id` - Fast product lookup by shop
- `tbl_product.category_id` - Fast category filtering
- `tbl_orders.created_at` - Order date filtering
- `tbl_cart.user_id, vendor_id, product_id` - Unique cart constraint

---

## Next Steps

1. ✅ Run `setup_tables.php` to create all tables
2. ✅ Update vendor registration to use `tbl_vendors`
3. ✅ Update product management to use `tbl_product`
4. ✅ Create user registration page for `tbl_users`
5. ✅ Build shopping cart using `tbl_cart`
6. ✅ Build checkout/order page using `tbl_orders`

---

**Created:** February 9, 2026  
**Version:** 1.0  
**Status:** Ready for use
