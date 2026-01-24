# 🍎 DRIYUM - Premium D2C Food eCommerce Platform

<div align="center">
  <h1 style="color: #2BB35C;">DRIYUM</h1>
  <p><strong>Premium Dehydrated Fruits & Traditional Hokh Suin</strong></p>
  <p>100% Real Fruit | No Oil | Preservative-Free</p>
</div>

---

## 🎯 Project Overview

**DRIYUM** is an enterprise-grade, production-ready D2C (Direct-to-Consumer) eCommerce platform built for selling premium dehydrated fruit snacks and traditional Kashmiri Hokh Suin (sun-dried vegetables).

### ✨ Key Highlights
- **Myntra-level Polish**: App-like mobile experience with smooth animations
- **100% Dynamic**: No hardcoded content - everything managed via admin panel
- **Production-Ready**: Secure, scalable, and optimized
- **Modern Tech Stack**: Procedural PHP + MySQL + Vanilla JS + Tailwind CSS
- **Mobile-First**: Responsive design with PWA support

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 7.4+ (Procedural - NO OOPS) |
| **Database** | MySQL 5.7+ / MariaDB |
| **Frontend** | HTML5 + Tailwind CSS (CDN) |
| **JavaScript** | Vanilla ES6+ (No frameworks) |
| **AJAX** | Fetch API |
| **Styling** | Custom CSS + Tailwind Utilities |
| **Sliders** | Swiper.js |

---

## 📁 Project Structure

```
newdry/
├── api/                          # AJAX API Endpoints
│   ├── cart.php                  # Cart operations
│   ├── search.php                # Product search
│   ├── newsletter.php            # Newsletter subscription
│   └── wishlist.php              # Wishlist management
│
├── assets/
│   ├── css/
│   │   └── style.css             # Premium custom styles
│   ├── js/
│   │   ├── main.js               # Core functionality
│   │   ├── cart.js               # Cart interactions
│   │   └── search.js             # Search functionality
│   └── images/                   # Product & UI images
│
├── components/                   # Reusable UI components
│   ├── product-card.php          # Product card template
│   ├── cart-sidebar.php          # AJAX cart sidebar
│   └── search-modal.php          # Full-screen search
│
├── config/
│   └── database.php              # Database configuration
│
├── database/
│   └── schema.sql                # Complete DB schema + sample data
│
├── includes/
│   ├── functions.php             # Core business logic
│   ├── header.php                # Global header with mega menu
│   ├── footer.php                # Global footer
│   └── mobile-nav.php            # Bottom navigation (mobile)
│
├── index.php                     # Homepage
├── login.php                     # Login page
├── shop.php                      # Shop/Products listing
├── product.php                   # Product detail page
├── checkout.php                  # Checkout flow
├── track-order.php               # Order tracking (public)
├── dashboard.php                 # User dashboard
└── README.md                     # Project documentation
```

---

## 🚀 Installation & Setup

### Prerequisites
- **XAMPP** (or WAMP/LAMP/MAMP)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Step 1: Clone/Extract Project
```bash
# Place project in XAMPP htdocs
C:\xampp\htdocs\newdry\
```

### Step 2: Database Setup
1. Start **Apache** and **MySQL** from XAMPP Control Panel
2. Import database:
```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "source database/schema.sql"
```

Or manually:
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Import `database/schema.sql`

### Step 3: Configuration
1. Edit `config/database.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Set if you have MySQL password
define('DB_NAME', 'driyum_db');
```

### Step 4: Access Application
```
Frontend: http://localhost/newdry/
Admin Panel: http://localhost/newdry/admin/
```

### Default Admin Credentials
```
Email: admin@driyum.com
Password: admin123
```

---

## 🎨 Design Features

### Visual Excellence
- ✅ Premium color palette (#2BB35C primary brand color)
- ✅ Smooth micro-animations and transitions
- ✅ Glassmorphism effects
- ✅ Skeleton loaders for better UX
- ✅ Toast notifications system
- ✅ Infinite marquee animations
- ✅ Hover effects and transforms

### App-Like Experience
- ✅ Sticky header with scroll effects
- ✅ AJAX cart sidebar (no page reload)
- ✅ Full-screen search modal
- ✅ Bottom navigation (mobile)
- ✅ Swipe gestures support
- ✅ PWA-ready structure

--- ## ⚙️ Core Features

### 🏠 Homepage
- [x] Hero slider (admin-managed)
- [x] Infinite announcement marquee
- [x] Live sale countdown timer
- [x] Brand values marquee
- [x] Category carousel
- [x] Featured products grid
- [x] Testimonials slider
- [x] Newsletter subscription
- [x] Dynamic footer

### 🛍️ Shop Page
- [x] Advanced filtering (category, price, type)
- [x] Sorting (price, name, newest)
- [x] Search functionality
- [x] Pagination
- [x] Product cards with quick actions
- [x] Skeleton loaders

### 📦 Product Detail Page
- [x] Image gallery
- [x] Product information
- [x] Nutritional values
- [x] Add to cart
- [x] Stock availability
- [x] Related products
- [x] Customer reviews

### 🛒 Cart & Checkout
- [x] AJAX cart sidebar
- [x] Real-time updates
- [x] Quantity controls
- [x] Coupon system
- [x] Address management
- [x] Order summary
- [x] Payment gateway ready

### 🚚 Order Tracking
- [x] Public tracking (Order ID + Email/Phone)
- [x] Status stages display
- [x] Order timeline
- [x] Download invoice

### 👤 User Dashboard
- [x] Login/Register
- [x] Order history
- [x] Profile management
- [x] Saved addresses
- [x] Wishlist
- [x] Download invoices

### 🔐 Security Features
- [x] CSRF protection
- [x] XSS prevention
- [x] SQL injection protection (prepared statements)
- [x] Password hashing (bcrypt)
- [x] Input sanitization
- [x] Session management

---

## 🎯 Brand Guidelines

### Colors
```css
Primary: #2BB35C (Driyum Green)
Primary Dark: #229447
Primary Light: #3DD96F
Text Dark: #1a202c
Text Gray: #718096
Background: #f7fafc
```

### Typography
- **Primary Font**: Dyson Sans Modern (from logo)
- **UI Font**: Inter (Google Fonts)

### Brand Values
1. 100% Real Fruit
2. No Oil Usage
3. Preservative-Free
4. Traditional yet Modern

---

## 📊 Database Schema

### Key Tables
- **users** - Customer accounts
- **categories** - Product categories
- **products** - Product catalog
- **orders** - Order management
- **order_items** - Order line items
- **coupons** - Discount codes
- **testimonials** - Customer reviews
- **hero_slides** - Homepage sliders
- **announcements** - Marquee messages
- **sale_countdowns** - Sale timers
- **newsletter_subscribers** - Email list
- **wishlist** - User wishlists

---

## 🔧 Development Guidelines

### PHP Best Practices
- Use procedural PHP only (NO OOPS)
- Prepared statements for all queries
- Input validation and sanitization
- Error logging
- Session security

### JavaScript Best Practices
- ES6+ syntax
- Async/await for AJAX
- Event delegation
- Error handling
- Performance optimization

### CSS Best Practices
- Mobile-first approach
- Custom properties (CSS variables)
- BEM-like naming
- Optimized animations
- Responsive design

---

## 🚀 Performance Optimizations

- ✅ Lazy loading images
- ✅ Debounced search
- ✅ AJAX for dynamic content
- ✅ Optimized database queries
- ✅ CDN for libraries
- ✅ Minified assets (production)
- ✅ Browser caching
- ✅ Gzip compression

---

## 📱 Mobile Responsiveness

The platform is fully responsive with breakpoints:
- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

Mobile-specific features:
- Bottom navigation bar
- Touch-friendly UI elements
- Optimized images
- Simplified layouts

---

## 🎁 Sample Data

The database comes pre-loaded with:
- 1 Admin user
- 4 Categories
- 8 Sample products
- 3 Hero slides
- 1 Active announcement
- 1 Sale countdown
- 4 Testimonials
- 3 Active coupons

---

## 🔄 Future Enhancements

### Phase 2 (Suggested)
- [ ] Advanced admin analytics
- [ ] Email notifications
- [ ] SMS integration
- [ ] Payment gateway integration (Razorpay/Stripe)
- [ ] Product reviews & ratings
- [ ] Referral system
- [ ] Loyalty points
- [ ] Advanced reporting

### Phase 3 (Advanced)
- [ ] Multi-vendor support
- [ ] Mobile app (React Native)
- [ ] Inventory management
- [ ] CRM integration
- [ ] Marketing automation
- [ ] AI product recommendations

---

## 📞 Support & Contact

For any issues or questions:
- **Email**: hello@driyum.com
- **Phone**: +91 98765 43210

---

## 📝 License

This is a proprietary eCommerce platform built for **DRIYUM**.  
All rights reserved © 2026 DRIYUM

---

## 🙏 Credits

**Designed & Developed with ❤️ in Kashmir**

**Tech Stack**:
- [Tailwind CSS](https://tailwindcss.com/)
- [Swiper.js](https://swiperjs.com/)
- [Google Fonts](https://fonts.google.com/)

---

<div align="center">
  <p><strong>🍎 DRIYUM</strong></p>
  <p>Premium Dehydrated Fruits & Traditional Hokh Suin</p>
  <p style="color: #2BB35C;"><em>Made with Love in Kashmir</em></p>
</div>
