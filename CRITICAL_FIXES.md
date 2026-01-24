# CRITICAL FIXES FOR DRIYUM.COM DEPLOYMENT

**Priority:** URGENT
**Date:** January 24, 2026
**Status:** Pre-Deployment Fixes Required

---

## 🔴 CRITICAL ISSUES TO FIX IMMEDIATELY

### 1. Missing `get_cart_weight()` Function
**File:** `api/shipping.php` (line 16)
**Error:** Function `get_cart_weight()` is called but not defined in `includes/functions.php`

**Impact:** Shipping calculator will fail completely
**Status:** ⚠️ CRITICAL - Will break checkout

**Fix Required:** Add to `includes/functions.php`:
```php
/**
 * Calculate total weight of cart items
 * @return float Weight in kg
 */
function get_cart_weight() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    
    $total_weight = 0;
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product = get_product_by_id($product_id);
        if ($product && !empty($product['weight'])) {
            $weight_str = strtolower($product['weight']);
            $val = (float)$weight_str;
            $weight_kg = $val;
            
            // Convert grams to kg
            if (strpos($weight_str, 'g') !== false && strpos($weight_str, 'kg') === false) {
                $weight_kg = $val / 1000;
            }
            
            $total_weight += ($weight_kg * $quantity);
        }
    }
    
    return $total_weight;
}
```

### 2. Missing `get_shipping_methods_with_rates()` Function
**File:** `api/shipping.php` (lines 17, 45)
**Error:** Function `get_shipping_methods_with_rates()` is called but not defined

**Impact:** Shipping methods won't load on checkout
**Status:** ⚠️ CRITICAL - Will break checkout

**Fix Required:** Add to `includes/functions.php`:
```php
/**
 * Get available shipping methods with rates for a pincode
 * @param string $pincode Delivery pincode
 * @param float $weight Package weight in kg
 * @return array Array of shipping methods with calculated rates
 */
function get_shipping_methods_with_rates($pincode, $weight = 0.5) {
    // Get shipping zone for pincode
    $zone = get_shipping_zone($pincode);
    
    if (!$zone) {
        return [];
    }
    
    // Get active shipping methods
    $methods = fetch_all("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order ASC");
    
    $results = [];
    foreach ($methods as $method) {
        // Get rate for this method and zone
        $rate_sql = "SELECT * FROM shipping_rates 
                     WHERE method_id = ? AND zone_id = ? 
                     AND (max_weight IS NULL OR max_weight >= ?) 
                     AND (min_weight IS NULL OR min_weight <= ?)
                     ORDER BY min_weight DESC LIMIT 1";
        
        $rate = fetch_one($rate_sql, [$method['id'], $zone['id'], $weight, $weight]);
        
        if ($rate) {
            $calculated_cost = 0;
            
            if ($rate['rate_type'] === 'flat') {
                $calculated_cost = $rate['rate'];
            } elseif ($rate['rate_type'] === 'per_kg') {
                $calculated_cost = $rate['rate'] * $weight;
            }
            
            // Apply free shipping threshold
            $free_shipping_threshold = get_setting('free_shipping_threshold', 500);
            $cart = get_cart_items();
            if ($cart['total'] >= $free_shipping_threshold) {
                $calculated_cost = 0;
            }
            
            $results[] = [
                'id' => $method['id'],
                'name' => $method['name'],
                'description' => $method['description'],
                'cost' => round($calculated_cost, 2),
                'estimated_days' => $method['estimated_days'],
                'zone_name' => $zone['name']
            ];
        }
    }
    
    return $results;
}
```

### 3. Missing `run_crons()` Function
**File:** `includes/header.php` (line 18)
**Error:** Function `run_crons()` is called but not defined

**Impact:** Page load errors
**Status:** ⚠️ HIGH - Will cause PHP warnings

**Fix Required:** Add to `includes/functions.php`:
```php
/**
 * Run periodic cron tasks
 * Checks if cron should run based on last run time
 */
function run_crons() {
    $last_run = get_setting('last_cron_run', 0);
    $current_time = time();
    
    // Run cron every 30 minutes
    if ($current_time - $last_run < 1800) {
        return;
    }
    
    // Update last run time
    update_setting('last_cron_run', $current_time);
    
    // Run abandoned cart reminder (async)
    $cron_file = __DIR__ . '/../cron/abandoned_cart_reminder.php';
    if (file_exists($cron_file)) {
        // Run in background (non-blocking)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            pclose(popen("start /B php " . escapeshellarg($cron_file), "r"));
        } else {
            // Linux/Unix
            exec("php " . escapeshellarg($cron_file) . " > /dev/null 2>&1 &");
        }
    }
}
```

---

## ⚠️ HIGH PRIORITY FIXES

### 4. Toast Container Missing in HTML
**Issue:** `showToast()` function requires a `toast-container` element
**Files Affected:** All pages using toast notifications

**Fix:** Add to `includes/footer.php` before closing `</body>` tag:
```html
<!-- Toast Container -->
<div id="toast-container" class="fixed bottom-8 right-8 z-[999] flex flex-col-reverse gap-4 pointer-events-none">
    <!-- Toasts will be inserted here -->
</div>

<style>
.toast-premium {
    pointer-events: auto;
    background: white;
    border-radius: 20px;
    min-width: 320px;
    max-width: 400px;
    border: 2px solid #f3f4f6;
}
.toast-premium.success {
    border-color: #19DC7E;
}
.toast-premium.error {
    border-color: #ef4444;
}
.toast-premium.warning {
    border-color: #f59e0b;
}
.toast-premium.info {
    border-color: #3b82f6;
}
</style>
```

### 5. Search Results Product URL Fix
**File:** `assets/js/chunky.js` (line 46)
**Issue:** Using `product.php?id=` instead of slug-based URL

**Fix:** Change line 46 from:
```javascript
<a href="${BASE_URL}product.php?id=${product.id}" ...>
```
To:
```javascript
<a href="${BASE_URL}product/${product.slug}" ...>
```

**Also update API:** `api/search.php` should return `slug` field:
```php
$sql = "SELECT id, name, slug, price, image, description FROM products 
        WHERE (name LIKE ? OR description LIKE ?) AND is_active = 1 
        LIMIT 10";
```

---

## 🔧 MEDIUM PRIORITY FIXES

### 6. Product Image Fallback
**Issue:** Broken images show logo instead of placeholder
**Files:** Multiple (cart sidebar, search results, etc.)

**Fix:** Create a proper placeholder image or use a better fallback:
```javascript
// In chunky.js, update image error handler
onerror="this.src='${BASE_URL}assets/images/placeholder-product.jpg'"
```

Create `assets/images/placeholder-product.jpg` or use a data URI:
```javascript
onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23d1d5db%22 font-family=%22sans-serif%22 font-size=%2224%22%3ENo Image%3C/text%3E%3C/svg%3E'"
```

### 7. Mobile Bottom Navigation
**Issue:** Mobile bottom navigation not implemented
**File:** `includes/footer.php`

**Fix:** Add mobile bottom navigation before closing `</body>`:
```html
<!-- MOBILE BOTTOM NAV -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 z-[80] bg-white border-t border-gray-100 safe-area-inset-bottom">
    <div class="grid grid-cols-5 h-20">
        <a href="<?php echo get_url(''); ?>" class="flex flex-col items-center justify-center gap-1 text-gray-400 hover:text-[#19DC7E] transition-colors">
            <i class="fas fa-home text-xl"></i>
            <span class="text-[10px] font-bold">Home</span>
        </a>
        <a href="<?php echo get_url('shop'); ?>" class="flex flex-col items-center justify-center gap-1 text-gray-400 hover:text-[#19DC7E] transition-colors">
            <i class="fas fa-store text-xl"></i>
            <span class="text-[10px] font-bold">Shop</span>
        </a>
        <button onclick="openCartSidebar()" class="flex flex-col items-center justify-center gap-1 text-gray-400 hover:text-[#19DC7E] transition-colors relative">
            <i class="fas fa-shopping-bag text-xl"></i>
            <span class="text-[10px] font-bold">Cart</span>
            <span id="mobile-cart-count" class="absolute -top-1 -right-1 bg-[#19DC7E] text-black text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center">0</span>
        </button>
        <a href="<?php echo get_url('wishlist'); ?>" class="flex flex-col items-center justify-center gap-1 text-gray-400 hover:text-[#19DC7E] transition-colors">
            <i class="far fa-heart text-xl"></i>
            <span class="text-[10px] font-bold">Saved</span>
        </a>
        <a href="<?php echo get_url($is_logged_in ? 'account' : 'login'); ?>" class="flex flex-col items-center justify-center gap-1 text-gray-400 hover:text-[#19DC7E] transition-colors">
            <i class="fas fa-user text-xl"></i>
            <span class="text-[10px] font-bold">Account</span>
        </a>
    </div>
</nav>

<!-- Add padding to body to prevent content from being hidden behind bottom nav -->
<style>
@media (max-width: 768px) {
    body {
        padding-bottom: 80px;
    }
}
</style>
```

### 8. Checkout Browse More Modal Error
**File:** `checkout.php`
**Issue:** Needs to handle API errors gracefully

**Fix:** Update the fetch error handling in checkout.php:
```javascript
// Find the loadSuggestions function and update error handling
async function loadSuggestions() {
    try {
        const response = await fetch(`${BASE_URL}api/get_suggestions.php`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        
        if (data.success && data.products) {
            renderSuggestions(data.products);
        } else {
            document.getElementById('suggestions-container').innerHTML = 
                '<p class="text-gray-400 text-center py-8">No suggestions available</p>';
        }
    } catch (error) {
        console.error('Error loading suggestions:', error);
        document.getElementById('suggestions-container').innerHTML = 
            '<p class="text-gray-400 text-center py-8">Unable to load suggestions. Please continue with checkout.</p>';
    }
}
```

---

## 📋 DEPLOYMENT CHECKLIST

### Before Upload
- [ ] Add missing functions to `includes/functions.php`
- [ ] Add toast container to `includes/footer.php`
- [ ] Add mobile bottom navigation
- [ ] Fix search results URL
- [ ] Test all API endpoints locally
- [ ] Verify database has all required tables
- [ ] Clean test data from database
- [ ] Update production credentials

### After Upload
- [ ] Test homepage loads
- [ ] Test product pages
- [ ] Test add to cart
- [ ] Test checkout flow
- [ ] Test shipping calculator
- [ ] Test search functionality
- [ ] Test mobile navigation
- [ ] Test payment gateway
- [ ] Test email sending

---

## 🔍 TESTING COMMANDS

### Test Database Connection
```php
// Create test_connection.php in root
<?php
require_once 'config/database.php';
$conn = get_db_connection();
if ($conn) {
    echo "✅ Database connected successfully!<br>";
    echo "Database: " . DB_NAME . "<br>";
    
    // Test a query
    $result = fetch_one("SELECT COUNT(*) as count FROM products");
    echo "Products in database: " . $result['count'];
} else {
    echo "❌ Database connection failed!";
}
?>
```

### Test Shipping Functions
```php
// Create test_shipping.php in root
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Test cart weight
$_SESSION['cart'] = [10 => 2, 11 => 1]; // Sample cart
echo "Cart Weight: " . get_cart_weight() . " kg<br>";

// Test shipping methods
$methods = get_shipping_methods_with_rates('190014', 1.5);
echo "<pre>";
print_r($methods);
echo "</pre>";
?>
```

### Test API Endpoints
```bash
# Test cart API
curl -X POST http://localhost/driyum.com/api/cart.php?action=add \
  -d "product_id=10&quantity=1"

# Test shipping API
curl -X POST http://localhost/driyum.com/api/shipping.php \
  -d "pincode=190014"

# Test search API
curl http://localhost/driyum.com/api/search.php?q=apple

# Test suggestions API
curl http://localhost/driyum.com/api/get_suggestions.php
```

---

## 🚨 KNOWN BUGS TO MONITOR

### From Conversation History

1. **Quick Buy Error** (Conversation f41a5bf5)
   - Error: "Could not quick buy. Try adding to cart."
   - Location: `assets/js/chunky.js` line 232
   - Status: ✅ Error handling exists, likely API issue
   - Action: Test with real products

2. **Search Modal** (Conversation 8adf4ee7)
   - Issue: Search icon not opening modal
   - Location: `toggleSearch()` function
   - Status: ✅ Function exists, check HTML IDs
   - Action: Verify `search-modal-overlay` and `search-modal` IDs exist

3. **Footer Navigation** (Conversation 8adf4ee7)
   - Issue: Footer bottom navigation not working
   - Status: ⚠️ Needs implementation (see Fix #7)
   - Action: Add mobile bottom navigation

4. **Checkout Errors** (Multiple conversations)
   - Issue: "Error loading suggestions" and "Error fetching methods"
   - Status: ⚠️ Missing functions (see Fixes #1, #2)
   - Action: Add missing functions and test

---

## 📝 POST-DEPLOYMENT MONITORING

### Files to Watch for Errors
1. `config/database.php` - Connection issues
2. `api/shipping.php` - Shipping calculation
3. `api/cart.php` - Cart operations
4. `checkout.php` - Checkout process
5. `includes/functions.php` - Core functions

### Error Log Locations
- **Hostinger:** Check via cPanel → Error Logs
- **Local:** `C:\xampp\apache\logs\error.log`

### Common Errors to Watch For
```
PHP Fatal error: Call to undefined function get_cart_weight()
PHP Fatal error: Call to undefined function get_shipping_methods_with_rates()
PHP Fatal error: Call to undefined function run_crons()
PHP Warning: Undefined array key "cart"
```

---

## ✅ FINAL VERIFICATION

Before declaring site ready:
- [ ] All critical functions added
- [ ] All API endpoints tested
- [ ] Database fully imported
- [ ] SSL certificate active
- [ ] Email sending works
- [ ] Payment gateway configured
- [ ] Mobile experience verified
- [ ] All forms tested
- [ ] Error logging enabled
- [ ] Backup system in place

---

**Document Created:** January 24, 2026
**Last Updated:** January 24, 2026
**Status:** Ready for implementation
**Priority:** CRITICAL - Must fix before deployment
