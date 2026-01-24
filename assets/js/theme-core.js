/**
 * DRIYUM REBORN - Core Theme JS
 * Handles specific interactions for the new organic theme.
 */

// 1. Mobile Menu Toggle
function toggleMobileMenu() {
    const nav = document.getElementById('navbar');
    // Create or toggle a mobile menu overlay
    let menu = document.getElementById('mobile-menu-overlay');

    if (!menu) {
        menu = document.createElement('div');
        menu.id = 'mobile-menu-overlay';
        menu.className = 'fixed inset-0 bg-dark z-40 flex flex-col items-center justify-center gap-8 opacity-0 pointer-events-none transition-opacity duration-300';
        menu.innerHTML = `
            <button class="absolute top-6 right-6 text-white p-2" onclick="toggleMobileMenu()">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <a href="/" class="text-3xl font-heading font-bold text-white hover:text-accent">Home</a>
            <a href="shop.php" class="text-3xl font-heading font-bold text-white hover:text-accent">Shop All</a>
            <a href="about.php" class="text-3xl font-heading font-bold text-white hover:text-accent">Our Story</a>
            <a href="login.php" class="text-3xl font-heading font-bold text-white hover:text-accent">Login</a>
        `;
        document.body.appendChild(menu);
    }

    if (menu.classList.contains('opacity-0')) {
        menu.classList.remove('opacity-0', 'pointer-events-none');
        document.body.style.overflow = 'hidden';
    } else {
        menu.classList.add('opacity-0', 'pointer-events-none');
        document.body.style.overflow = '';
    }
}

// 2. Cart Drawer Toggle (Re-implementation for new theme)
function toggleCart() {
    // Check if cart sidebar exists, if not, load it
    let sidebar = document.getElementById('cart-sidebar');
    if (!sidebar) {
        // Fallback if component not loaded
        window.location.href = 'cart.php';
        return;
    }

    // Toggle logic for existing sidebar
    sidebar.classList.toggle('translate-x-full');
    const overlay = document.getElementById('cart-overlay');
    if (overlay) overlay.classList.toggle('hidden');
}

// 3. Add to Cart Animation
function addToCart(productId) {
    // 1. Call API
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: 1
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 2. Visual Feedback (Floating Bubble)
                const btn = event.target.closest('button');
                const rect = btn.getBoundingClientRect();

                const bubble = document.createElement('div');
                bubble.className = 'fixed w-8 h-8 rounded-full bg-accent z-50 flex items-center justify-center text-xs font-bold pointer-events-none';
                bubble.innerHTML = '+1';
                bubble.style.left = rect.left + 'px';
                bubble.style.top = rect.top + 'px';
                bubble.style.transition = 'all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';

                document.body.appendChild(bubble);

                // Animate to cart icon
                setTimeout(() => {
                    const cartIcon = document.querySelector('button[onclick="toggleCart()"]');
                    const cartRect = cartIcon.getBoundingClientRect();

                    bubble.style.left = cartRect.left + 'px';
                    bubble.style.top = cartRect.top + 'px';
                    bubble.style.opacity = '0';
                    bubble.style.transform = 'scale(0.5)';

                    // Update Badge Count
                    setTimeout(() => {
                        bubble.remove();
                        updateCartBadge(data.cart_count); // Assume this exists or reload
                        location.reload(); // Simple reload for now to update counts
                    }, 800);
                }, 50);

            }
        });
}

function updateCartBadge(count) {
    // Logic to update the red dot
}

// 4. Hero Parallax (Simple)
document.addEventListener('mousemove', (e) => {
    const moveX = (e.clientX * -0.015);
    const moveY = (e.clientY * -0.015);

    // Target Hero Image
    const heroImg = document.querySelector('.hero-split img');
    if (heroImg) {
        heroImg.style.transform = `translate(${moveX}px, ${moveY}px)`;
    }
});
