/* ==========================================================================
   DRIYUM CHUNKY PRO JS - Global Core Logic
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    // Debug for prod issues
    console.log("BASE_URL Configured:", BASE_URL);

    if (typeof updateCartIcon === 'function') updateCartIcon();
    initSearch();
});

/* --- SEARCH LOGIC --- */
function initSearch() {
    const input = document.getElementById('header-search-input');
    const resultsContainer = document.getElementById('search-results');
    if (!input || !resultsContainer) return;

    let debounceTimer;
    input.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const response = await fetch(`${BASE_URL}api/search.php?q=${encodeURIComponent(query)}`);
                const data = await response.json();
                renderSearchResults(data.results || [], resultsContainer);
            } catch (err) {
                console.error('Search failed', err);
            }
        }, 300);
    });
}

function renderSearchResults(results, container) {
    if (!container) return;
    if (results.length === 0) {
        container.innerHTML = '<p class="text-white/40 text-sm italic py-4">No snacks found for that craving...</p>';
        return;
    }

    container.innerHTML = `<div class="grid gap-3">` + results.map(product => `
        <a href="${BASE_URL}product/${product.slug}" class="flex items-center gap-6 p-4 rounded-[20px] bg-white/5 border border-white/10 hover:bg-white/20 hover:scale-[1.02] transition-all duration-300 group relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent skew-x-12 translate-x-[-200%] group-hover:animate-shine"></div>
            
            <div class="w-20 h-20 bg-white rounded-2xl p-2 flex-shrink-0 shadow-lg group-hover:rotate-6 transition-transform duration-500">
                <img src="${BASE_URL}${product.image}" class="w-full h-full object-contain" 
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22100%22 height=%22100%22/%3E%3C/svg%3E'">
            </div>
            
            <div class="flex-1 min-w-0">
                <h4 class="text-white font-['Fredoka'] font-bold text-xl truncate group-hover:text-[#19DC7E] transition-colors">${product.name}</h4>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[#19DC7E] font-black text-lg">₹${product.price}</span>
                    <span class="text-white/40 text-xs uppercase font-bold tracking-widest hidden sm:inline-block">View Product</span>
                </div>
            </div>
            
            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white group-hover:bg-[#19DC7E] group-hover:text-black transition-all">
                <i class="fas fa-arrow-right -rotate-45 group-hover:rotate-0 transition-transform duration-300"></i>
            </div>
        </a>
    `).join('') + `</div>`;
}

/* --- UI CONTROLLERS --- */
window.toggleMobileMenuDrawer = function () {
    const overlay = document.getElementById('mobile-menu-overlay');
    const drawer = document.getElementById('mobile-menu-drawer');
    const triggerBtn = document.getElementById('mobile-menu-trigger-btn');
    if (!overlay || !drawer) return;

    const isHidden = overlay.classList.contains('hidden');
    if (isHidden) {
        // Open Menu
        overlay.classList.remove('hidden');
        void overlay.offsetWidth; // Force reflow
        overlay.style.opacity = '1';
        drawer.style.transform = 'translateX(0)';
        document.body.style.overflow = 'hidden';

        // Update Trigger State if exists in dock
        if (triggerBtn) {
            triggerBtn.classList.add('active');
            const icon = triggerBtn.querySelector('i');
            const label = triggerBtn.querySelector('span');
            if (icon) icon.className = 'fas fa-times'; // Change to X
            if (label) label.textContent = 'Close';
        }
    } else {
        // Close Menu
        overlay.style.opacity = '0';
        drawer.style.transform = 'translateX(-100%)';
        setTimeout(() => overlay.classList.add('hidden'), 300);
        document.body.style.overflow = '';

        // Reset Trigger State
        if (triggerBtn) {
            triggerBtn.classList.remove('active');
            const icon = triggerBtn.querySelector('i');
            const label = triggerBtn.querySelector('span');
            if (icon) icon.className = 'fas fa-bars'; // Back to Hamburger
            if (label) label.textContent = 'Menu';
        }
    }
};

window.openCartSidebar = function () {
    const overlay = document.getElementById('cart-sidebar-overlay');
    const sidebar = document.getElementById('cart-sidebar');
    if (!overlay || !sidebar) return;

    overlay.classList.remove('hidden');
    setTimeout(() => {
        overlay.style.opacity = '1';
        sidebar.style.transform = 'translateX(0)';
    }, 10);
    document.body.style.overflow = 'hidden';
    if (typeof loadCartItems === 'function') loadCartItems();
};

window.closeCartSidebar = function () {
    const overlay = document.getElementById('cart-sidebar-overlay');
    const sidebar = document.getElementById('cart-sidebar');
    if (!overlay || !sidebar) return;

    overlay.style.opacity = '0';
    sidebar.style.transform = 'translateX(100%)';
    setTimeout(() => overlay.classList.add('hidden'), 300);
    document.body.style.overflow = '';
};

window.toggleSearch = function () {
    const overlay = document.getElementById('search-modal-overlay');
    const modal = document.getElementById('search-modal');
    const input = document.getElementById('header-search-input');
    if (!overlay || !modal) return;

    const isHidden = overlay.classList.contains('hidden');
    if (isHidden) {
        overlay.classList.remove('hidden');
        setTimeout(() => {
            overlay.style.opacity = '1';
            modal.style.transform = 'translateY(0)';
            if (input) input.focus();
        }, 10);
        document.body.style.overflow = 'hidden';
    } else {
        overlay.style.opacity = '0';
        modal.style.transform = 'translateY(-3rem)';
        setTimeout(() => overlay.classList.add('hidden'), 300);
        document.body.style.overflow = '';
    }
};

/* --- CONFETTI EFFECT --- */
function fireConfetti(element) {
    if (!element) return;
    const rect = element.getBoundingClientRect();
    const colors = ['#19DC7E', '#FFD700', '#FF6B6B', '#4F46E5'];
    for (let i = 0; i < 12; i++) {
        const particle = document.createElement('div');
        particle.style.position = 'fixed';
        particle.style.left = rect.left + rect.width / 2 + 'px';
        particle.style.top = rect.top + rect.height / 2 + 'px';
        particle.style.width = Math.random() * 8 + 4 + 'px';
        particle.style.height = particle.style.width;
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];
        particle.style.borderRadius = '50%';
        particle.style.zIndex = 9999;
        particle.style.pointerEvents = 'none';

        const angle = Math.random() * Math.PI * 2;
        const velocity = 3 + Math.random() * 5;
        const tx = Math.cos(angle) * 60 * velocity;
        const ty = Math.sin(angle) * 60 * velocity;
        const rot = Math.random() * 360;

        particle.animate([
            { transform: 'translate(0,0) rotate(0deg) scale(1)', opacity: 1 },
            { transform: `translate(${tx}px, ${ty}px) rotate(${rot}deg) scale(0)`, opacity: 0 }
        ], {
            duration: 800 + Math.random() * 400,
            easing: 'cubic-bezier(0, .9, .57, 1)'
        }).onfinish = () => particle.remove();
        document.body.appendChild(particle);
    }
}

/* --- CART ACTIONS --- */
window.addToCart = async function (productId, btnElement, quantity = 1) {
    if (!btnElement) {
        console.warn("addToCart: btnElement is null");
        return;
    }
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btnElement.disabled = true;

    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        const response = await fetch(`${BASE_URL}api/cart.php?action=add`, { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            btnElement.innerHTML = '<i class="fas fa-check"></i>';
            btnElement.classList.add('bg-[#19DC7E]', 'text-white', 'border-transparent', 'scale-110');
            fireConfetti(btnElement);

            // Update all UI components
            await updateCartIcon();

            // Seamlessly open the sidebar to show the user the result
            openCartSidebar();

            setTimeout(() => {
                btnElement.innerHTML = originalText;
                btnElement.classList.remove('bg-[#19DC7E]', 'text-white', 'border-transparent', 'scale-110');
                btnElement.disabled = false;
            }, 2000);
        } else {
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
            showToast(data.message || 'Error occurred', 'error');
        }
    } catch (error) {
        console.error('Cart Error:', error);
        btnElement.innerHTML = '<i class="fas fa-exclamation"></i>';
        btnElement.disabled = false;
        setTimeout(() => {
            btnElement.innerHTML = originalText;
        }, 2000);
    }
}

window.quickBuy = async function (productId, btnElement, quantity = 1) {
    if (!btnElement) return;
    const originalContent = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-bolt fa-spin"></i>';
    btnElement.disabled = true;

    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        const response = await fetch(`${BASE_URL}api/cart.php?action=add`, { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            window.location.href = `${BASE_URL}checkout`;
        } else {
            btnElement.innerHTML = originalContent;
            btnElement.disabled = false;
            showToast(data.message || 'Error occurred', 'error');
        }
    } catch (e) {
        console.error(e);
        btnElement.innerHTML = originalContent;
        btnElement.disabled = false;
        showToast('Could not quick buy. Try adding to cart.', 'error');
    }
};

window.updateCartQty = async function (productId, quantity) {
    const itemEl = document.getElementById(`cart-item-${productId}`);

    // OPTIMISTIC UI: If removing, hide immediately
    if (quantity === 0 && itemEl) {
        itemEl.style.transition = 'all 0.5s cubic-bezier(0.16, 1, 0.3, 1)';
        itemEl.style.opacity = '0';
        itemEl.style.transform = 'translateX(50px) scale(0.9)';
        itemEl.style.pointerEvents = 'none';
        // After animation, we'll reload anyway, but this makes it look instant
    }

    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        const response = await fetch(`${BASE_URL}api/cart.php?action=update`, { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            // Force both updates immediately
            await updateCartIcon();

            // If sidebar is open, reload it
            const sidebar = document.getElementById('cart-sidebar');
            const isSidebarOpen = sidebar && (
                sidebar.style.transform === 'translateX(0px)' ||
                sidebar.style.transform === 'translateX(0)' ||
                !sidebar.classList.contains('translate-x-full')
            );

            if (isSidebarOpen) {
                loadCartItems(true);
            }
        } else {
            // ROLLBACK if failed
            if (itemEl) {
                itemEl.style.opacity = '1';
                itemEl.style.transform = 'translateX(0) scale(1)';
                itemEl.style.pointerEvents = 'auto';
            }
            showToast(data.message || 'Update failed', 'error');
        }

    } catch (e) {
        console.error("Update failed", e);
        // ROLLBACK
        if (itemEl) {
            itemEl.style.opacity = '1';
            itemEl.style.transform = 'translateX(0) scale(1)';
            itemEl.style.pointerEvents = 'auto';
        }
    }
}

window.updateCartIcon = async function () {
    try {
        const response = await fetch(`${BASE_URL}api/cart.php?action=get_count`);
        const data = await response.json();
        if (data.success) {
            document.querySelectorAll('#cart-count, #mobile-cart-count, #desktop-cart-count').forEach(el => {
                if (el.id === 'cart-count') {
                    el.textContent = `Cart (${data.count})`;
                } else if (el.id === 'mobile-cart-count') {
                    el.textContent = data.count;
                } else {
                    el.textContent = data.count;
                }
            });
        }
    } catch (e) { }
}

/* --- SIDEBAR CART --- */
/* --- SIDEBAR CART --- */
window.loadCartItems = async function (isUpdate = false) {
    const container = document.getElementById('cart-items-container');
    if (!container) return;

    try {
        const response = await fetch(`${BASE_URL}api/cart.php?action=get_items`);
        const data = await response.json();

        if (data.success) {
            const totalEl = document.getElementById('cart-total');
            if (totalEl) totalEl.textContent = `₹${data.subtotal}`;

            if (data.items.length === 0) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full px-12 text-center">
                        <div class="w-32 h-32 bg-white rounded-[40px] shadow-2xl flex items-center justify-center text-5xl mb-8">🛍️</div>
                        <h3 class="text-3xl font-['Fredoka'] font-black text-gray-900 mb-4">Bag is empty!</h3>
                        <p class="text-gray-400 font-medium mb-10 leading-relaxed">It seems your snack vault is empty.</p>
                        <button onclick="closeCartSidebar(); window.location.href='${BASE_URL}shop'" class="w-full bg-black text-white py-5 rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-[#19DC7E] hover:text-black transition-all shadow-xl active:scale-95">Explore Snacks</button>
                    </div>`;
            } else {
                container.innerHTML = data.items.map((item, index) => {
                    const animationStyle = isUpdate ? '' : `animation: cartItemSlideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; animation-delay: ${index * 50}ms; opacity: 0; transform: translateX(20px);`;
                    return `
                    <div id="cart-item-${item.id}" 
                         class="cart-item-card group relative flex gap-5 items-center bg-white p-4 rounded-[28px] border-2 border-transparent hover:border-gray-50 transition-all duration-300"
                         style="${animationStyle}">
                        <!-- Image Container with Float Animation -->
                        <div class="relative w-24 h-24 flex-shrink-0 bg-[#F3F4F6] rounded-[22px] overflow-hidden group-hover:shadow-[0_15px_30px_rgba(0,0,0,0.1)] transition-shadow">
                             <a href="${BASE_URL}product/${item.id}"><img src="${BASE_URL}${item.image}" class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-500" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22100%22 height=%22100%22/%3E%3C/svg%3E'"></a>
                        </div>
                        
                        <div class="flex-1 min-w-0 pr-6">
                            <a href="${BASE_URL}product/${item.id}" class="block">
                                <h4 class="font-black text-gray-900 truncate font-['Fredoka'] text-lg mb-0.5 leading-tight group-hover:text-[#19DC7E] transition-colors">${item.name}</h4>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-wide mb-3">500g Pack</p>
                            </a>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center bg-gray-50 rounded-xl p-1 border border-gray-100">
                                    <button onclick="updateCartQty(${item.id}, ${item.quantity - 1})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-white text-gray-400 hover:text-black shadow-sm transition-all hover:scale-110 active:scale-95"><i class="fas fa-minus text-[10px]"></i></button>
                                    <span class="text-sm font-black w-8 text-center">${item.quantity}</span>
                                    <button onclick="updateCartQty(${item.id}, ${item.quantity + 1})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-white text-black hover:bg-[#19DC7E] shadow-sm transition-all hover:scale-110 active:scale-95"><i class="fas fa-plus text-[10px]"></i></button>
                                </div>
                                <span class="font-black text-gray-900 text-lg">₹${item.total}</span>
                            </div>
                        </div>

                        <!-- Modern Remove Button -->
                        <button onclick="updateCartQty(${item.id}, 0)" class="absolute top-2 right-2 w-7 h-7 bg-red-50 text-red-300 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all shadow-lg scale-75 hover:scale-100">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                    </div>`;
                }).join('');
            }
        }
    } catch (e) {
        console.error(e);
    }
};

/* --- TOAST NOTIFICATIONS --- */
window.showToast = function (message, type = 'success', duration = 5000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast-premium ${type} p-4 flex items-center gap-4 mb-4 shadow-xl translate-x-12 opacity-0 transition-all duration-500`;

    const icons = {
        success: '<i class="fas fa-check-circle text-green-500"></i>',
        error: '<i class="fas fa-times-circle text-red-500"></i>',
        warning: '<i class="fas fa-exclamation-triangle text-yellow-500"></i>',
        info: '<i class="fas fa-info-circle text-blue-500"></i>'
    };

    toast.innerHTML = `
        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm">${icons[type] || icons.info}</div>
        <div class="flex-1">
            <div class="text-gray-500 text-sm font-medium">${message}</div>
        </div>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.remove('translate-x-12', 'opacity-0');
    }, 10);

    setTimeout(() => {
        toast.classList.add('translate-x-12', 'opacity-0');
        setTimeout(() => toast.remove(), 500);
    }, duration);
}

/* --- WISHLIST --- */
window.toggleWishlist = async function (productId, btnElement) {
    if (!productId || !btnElement) return;
    const icon = btnElement.querySelector('i');
    if (!icon) return;
    const isAdding = !btnElement.classList.contains('active');

    try {
        const response = await fetch(`${BASE_URL}api/wishlist.php`, {
            method: 'POST',
            body: JSON.stringify({ action: isAdding ? 'add' : 'remove', product_id: productId })
        });
        const data = await response.json();

        if (data.success) {
            btnElement.classList.toggle('active');
            if (isAdding) {
                icon.className = 'fas fa-heart text-red-500 scale-125';
                showToast('Added to wishlist!');
            } else {
                icon.className = 'far fa-heart text-gray-300';
                showToast('Removed from wishlist!', 'info');
            }
        } else if (data.message === 'Login required') {
            showToast('Please login first!', 'warning');
            setTimeout(() => window.location.href = `${BASE_URL}login`, 1500);
        }
    } catch (e) {
        console.error(e);
    }
}

/* --- VIDEO MODAL --- */
window.openVideoModal = function (url) {
    if (!url || url === '#') return showToast("No video available!", "error");
    let content = '';
    if (typeof url === 'string' && url.match(/\.(mp4|webm|ogg|mov)$/i)) {
        content = `<video controls autoplay class="w-full h-full object-contain"><source src="${url}" type="video/mp4"></video>`;
    } else if (typeof url === 'string' && (url.includes('youtube.com') || url.includes('youtu.be'))) {
        const videoId = url.split('v=')[1] ? url.split('v=')[1].split('&')[0] : url.split('/').pop();
        content = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1" class="w-full h-full" frameborder="0" allowfullscreen></iframe>`;
    } else {
        content = `<iframe src="${url}" class="w-full h-full" frameborder="0" allowfullscreen></iframe>`;
    }

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/90 backdrop-blur-xl z-[200] flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="relative w-full max-w-6xl aspect-video bg-black rounded-[32px] overflow-hidden border border-white/10">
            <button onclick="this.closest('.fixed').remove()" class="absolute top-6 right-6 z-50 bg-white/10 hover:bg-white text-white hover:text-black w-12 h-12 rounded-full flex items-center justify-center transition-all"><i class="fas fa-times text-xl"></i></button>
            ${content}
        </div>
    `;
    document.body.appendChild(modal);
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (typeof toggleSearch === 'function') {
            const overlay = document.getElementById('search-modal-overlay');
            if (overlay && !overlay.classList.contains('hidden')) toggleSearch();
        }
        if (typeof closeCartSidebar === 'function') closeCartSidebar();
    }
});
