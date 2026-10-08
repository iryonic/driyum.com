/* ==========================================================================
   DRIYUM CHUNKY PRO JS - Global Core Logic
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    // Debug for prod issues
    if (typeof BASE_URL !== 'undefined') {
        console.log("BASE_URL Configured:", BASE_URL);
    }

    if (typeof updateCartIcon === 'function') updateCartIcon();
    initSearch();
});

/* --- PASSWORD LOGIC --- */
window.togglePasswordVisibility = function (inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
};

window.validatePassword = function (val) {
    const validator = document.getElementById('password-validator');
    const bar = document.getElementById('strength-bar');
    if (!validator || !bar) return;

    if (val.length > 0) validator.classList.remove('hidden');
    else {
        validator.classList.add('hidden');
        return;
    }

    const criteria = {
        length: val.length >= 8,
        upper: /[A-Z]/.test(val),
        number: /[0-9]/.test(val),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(val)
    };

    let score = 0;
    Object.keys(criteria).forEach(key => {
        const el = document.getElementById(`crit-${key}`);
        if (criteria[key]) {
            el.classList.replace('text-gray-400', 'text-[#24B25D]');
            el.querySelector('i').classList.replace('fa-circle', 'fa-check-circle');
            score++;
        } else {
            el.classList.replace('text-[#24B25D]', 'text-gray-400');
            el.querySelector('i').classList.replace('fa-check-circle', 'fa-circle');
        }
    });

    // Update Strength Bar
    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-[#24B25D]'];
    bar.className = `h-full transition-all duration-500 ${colors[score]}`;
    bar.style.width = `${(score / 4) * 100}%`;
};

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
                <h4 class="text-white font-heading font-bold text-xl truncate group-hover:text-[#24B25D] transition-colors">${product.name}</h4>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[#24B25D] font-black text-lg">₹ ${product.price}</span>
                    <span class="text-white/40 text-xs uppercase font-bold tracking-widest hidden sm:inline-block">View Product</span>
                </div>
            </div>
            
            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white group-hover:bg-[#24B25D] group-hover:text-black transition-all">
                <i class="fas fa-arrow-right -rotate-45 group-hover:rotate-0 transition-transform duration-300"></i>
            </div>
        </a>
    `).join('') + `</div>`;
}

/* --- UI CONTROLLERS --- */


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
    const colors = ['#24B25D', '#FFD700', '#FF6B6B', '#4F46E5'];
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
            btnElement.classList.add('bg-[#24B25D]', 'text-white', 'border-transparent', 'scale-110');
            fireConfetti(btnElement);

            // Update all UI components
            await updateCartIcon();

            // Seamlessly open the sidebar to show the user the result
            openCartSidebar();

            setTimeout(() => {
                btnElement.innerHTML = originalText;
                btnElement.classList.remove('bg-[#24B25D]', 'text-white', 'border-transparent', 'scale-110');
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
                el.textContent = data.count;
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

            // --- FREE SHIPPING PROGRESS LOGIC ---
            const fsContainer = document.getElementById('free-shipping-progress-container');
            const fsMsg = document.getElementById('free-shipping-msg');
            const fsBar = document.getElementById('free-shipping-bar');

            if (fsContainer && fsMsg && fsBar) {
                if (data.items.length === 0) {
                    fsContainer.classList.add('hidden');
                } else {
                    fsContainer.classList.remove('hidden');
                    const subtotal = data.subtotal;
                    const threshold = typeof FREE_SHIPPING_THRESHOLD !== 'undefined' ? FREE_SHIPPING_THRESHOLD : 499;

                    if (subtotal >= threshold) {
                        fsMsg.innerHTML = '<span class="text-[#24B25D] font-black">Booyah! You unlocked FREE SHIPPING! 🚀</span>';
                        fsBar.style.width = '100%';
                        fsBar.classList.add('progress-shimmer');
                        document.getElementById('free-shipping-icon').textContent = '🎉';
                    } else {
                        const remaining = threshold - subtotal;
                        const percentage = (subtotal / threshold) * 100;
                        fsMsg.innerHTML = `Add <span class="text-black font-black">₹${remaining}</span> more for <span class="text-[#24B25D]">FREE SHIPPING</span>`;
                        fsBar.style.width = `${percentage}%`;
                        fsBar.classList.remove('progress-shimmer');
                        document.getElementById('free-shipping-icon').textContent = '🚚';
                    }
                }
            }
            // ------------------------------------

            if (data.items.length === 0) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full px-12 text-center">
                        <div class="w-32 h-32 bg-white rounded-[40px] shadow-2xl flex items-center justify-center text-5xl mb-8">🛍️</div>
                        <h3 class="text-3xl font-heading font-black text-gray-900 mb-4">Bag is empty!</h3>
                        <p class="text-gray-400 font-medium mb-10 leading-relaxed">It seems your snack vault is empty.</p>
                        <button onclick="closeCartSidebar(); window.location.href='${BASE_URL}shop'" class="w-full bg-black text-white py-5 rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-[#24B25D] hover:text-black transition-all shadow-xl active:scale-95">Explore Snacks</button>
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
                                <h4 class="font-black text-gray-900 truncate font-heading text-lg mb-0.5 leading-tight group-hover:text-[#24B25D] transition-colors">${item.name}</h4>
                                <p class="text-xs text-gray-400 font-bold uppercase tracking-wide mb-3">${item.weight ? (parseFloat(item.weight) < 10 ? (parseFloat(item.weight) * 1000) + 'G' : item.weight) : 'Premium Pack'}</p>
                            </a>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center bg-gray-50 rounded-xl p-1 border border-gray-100">
                                    <button onclick="updateCartQty(${item.id}, ${item.quantity - 1})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-white text-gray-400 hover:text-black shadow-sm transition-all hover:scale-110 active:scale-95"><i class="fas fa-minus text-[10px]"></i></button>
                                    <span class="text-sm font-black w-8 text-center">${item.quantity}</span>
                                    <button onclick="updateCartQty(${item.id}, ${item.quantity + 1})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-white text-black hover:bg-[#24B25D] shadow-sm transition-all hover:scale-110 active:scale-95"><i class="fas fa-plus text-[10px]"></i></button>
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
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-6 right-6 z-[999999] flex flex-col gap-2 pointer-events-none';
        document.body.appendChild(container);
    }

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

/* ==========================================================================
   DRIYUM EXECUTIVE CUSTOM DIALOG SYSTEM (window.alert & window.confirm UI)
   ========================================================================== */
(function () {
    if (window.__DRIYUM_DIALOGS_INITIALIZED__) return;
    window.__DRIYUM_DIALOGS_INITIALIZED__ = true;

    // Inject dedicated styling
    const STYLES_ID = 'driyum-custom-dialog-styles';
    function ensureStyles() {
        if (document.getElementById(STYLES_ID)) return;
        const style = document.createElement('style');
        style.id = STYLES_ID;
        style.textContent = `
            @keyframes driyumDialogBackdropIn {
                from { opacity: 0; backdrop-filter: blur(0px); -webkit-backdrop-filter: blur(0px); }
                to { opacity: 1; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
            }
            @keyframes driyumDialogBackdropOut {
                from { opacity: 1; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
                to { opacity: 0; backdrop-filter: blur(0px); -webkit-backdrop-filter: blur(0px); }
            }
            @keyframes driyumDialogCardIn {
                0% { opacity: 0; transform: scale(0.88) translateY(18px); }
                65% { transform: scale(1.02) translateY(-2px); }
                100% { opacity: 1; transform: scale(1) translateY(0); }
            }
            @keyframes driyumDialogCardOut {
                0% { opacity: 1; transform: scale(1) translateY(0); }
                100% { opacity: 0; transform: scale(0.92) translateY(12px); }
            }
            .driyum-dialog-backdrop {
                position: fixed;
                inset: 0;
                z-index: 9999999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.25rem;
                background-color: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                animation: driyumDialogBackdropIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                font-family: 'Figtree', 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .driyum-dialog-backdrop.closing {
                animation: driyumDialogBackdropOut 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .driyum-dialog-card {
                position: relative;
                width: 100%;
                max-width: 440px;
                background: #ffffff;
                border-radius: 28px;
                padding: 34px 28px 28px 28px;
                text-align: center;
                box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(15, 23, 42, 0.06);
                border: 1px solid rgba(226, 232, 240, 0.9);
                animation: driyumDialogCardIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                box-sizing: border-box;
            }
            .driyum-dialog-backdrop.closing .driyum-dialog-card {
                animation: driyumDialogCardOut 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .driyum-dialog-close {
                position: absolute;
                top: 18px;
                right: 18px;
                width: 34px;
                height: 34px;
                border-radius: 50%;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                color: #64748b;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                outline: none;
                padding: 0;
            }
            .driyum-dialog-close:hover {
                background: #f1f5f9;
                color: #0f172a;
                transform: rotate(90deg);
            }
            .driyum-dialog-icon-wrapper {
                display: flex;
                justify-content: center;
                margin-bottom: 20px;
            }
            .driyum-dialog-icon-badge {
                width: 68px;
                height: 68px;
                border-radius: 22px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .driyum-dialog-card:hover .driyum-dialog-icon-badge {
                transform: scale(1.06);
            }
            /* Danger */
            .driyum-dialog-danger .driyum-dialog-icon-badge {
                background: #fff1f2;
                color: #e11d48;
                border: 1px solid #ffe4e6;
                box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.25), 0 0 0 8px rgba(254, 226, 226, 0.55);
            }
            /* Warning */
            .driyum-dialog-warning .driyum-dialog-icon-badge {
                background: #fffbeb;
                color: #d97706;
                border: 1px solid #fef3c7;
                box-shadow: 0 10px 25px -5px rgba(217, 119, 6, 0.25), 0 0 0 8px rgba(254, 243, 199, 0.55);
            }
            /* Success */
            .driyum-dialog-success .driyum-dialog-icon-badge {
                background: #ecfdf5;
                color: #24B25D;
                border: 1px solid #d1fae5;
                box-shadow: 0 10px 25px -5px rgba(36, 178, 93, 0.25), 0 0 0 8px rgba(209, 250, 229, 0.55);
            }
            /* Info */
            .driyum-dialog-info .driyum-dialog-icon-badge {
                background: #f0fdf4;
                color: #15803d;
                border: 1px solid #bbf7d0;
                box-shadow: 0 10px 25px -5px rgba(36, 178, 93, 0.2), 0 0 0 8px rgba(240, 253, 244, 0.7);
            }
            .driyum-dialog-title {
                font-size: 21px;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 10px 0;
                letter-spacing: -0.02em;
                line-height: 1.3;
            }
            .driyum-dialog-message {
                font-size: 14.5px;
                line-height: 1.6;
                color: #475569;
                margin: 0;
                font-weight: 500;
                white-space: pre-line;
                word-break: break-word;
                max-height: 50vh;
                overflow-y: auto;
            }
            .driyum-dialog-actions {
                display: flex;
                gap: 12px;
                margin-top: 28px;
                width: 100%;
            }
            .driyum-dialog-btn {
                position: relative;
                outline: none;
                cursor: pointer;
                border-radius: 16px;
                padding: 14px 20px;
                font-size: 13px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                box-sizing: border-box;
                font-family: inherit;
            }
            .driyum-dialog-btn:active {
                transform: scale(0.97);
            }
            .driyum-dialog-btn-cancel {
                flex: 1;
                background: #f1f5f9;
                color: #475569;
                border: 1px solid #e2e8f0;
            }
            .driyum-dialog-btn-cancel:hover {
                background: #e2e8f0;
                color: #0f172a;
            }
            .driyum-dialog-btn-confirm {
                flex: 1.25;
                border: none;
            }
            .driyum-dialog-danger .driyum-dialog-btn-confirm {
                background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
                color: #ffffff;
                box-shadow: 0 8px 22px -4px rgba(225, 29, 72, 0.4);
            }
            .driyum-dialog-danger .driyum-dialog-btn-confirm:hover {
                box-shadow: 0 12px 28px -4px rgba(225, 29, 72, 0.5);
                filter: brightness(1.08);
                transform: translateY(-1px);
            }
            .driyum-dialog-warning .driyum-dialog-btn-confirm {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                color: #ffffff;
                box-shadow: 0 8px 22px -4px rgba(217, 119, 6, 0.35);
            }
            .driyum-dialog-warning .driyum-dialog-btn-confirm:hover {
                box-shadow: 0 12px 28px -4px rgba(217, 119, 6, 0.45);
                filter: brightness(1.06);
                transform: translateY(-1px);
            }
            .driyum-dialog-success .driyum-dialog-btn-confirm {
                background: linear-gradient(135deg, #24B25D 0%, #1ea153 100%);
                color: #000000;
                font-weight: 900;
                box-shadow: 0 8px 22px -4px rgba(36, 178, 93, 0.35);
            }
            .driyum-dialog-success .driyum-dialog-btn-confirm:hover {
                box-shadow: 0 12px 28px -4px rgba(36, 178, 93, 0.45);
                filter: brightness(1.06);
                transform: translateY(-1px);
            }
            .driyum-dialog-info .driyum-dialog-btn-confirm {
                background: #0f172a;
                color: #ffffff;
                box-shadow: 0 8px 22px -4px rgba(15, 23, 42, 0.25);
            }
            .driyum-dialog-info .driyum-dialog-btn-confirm:hover {
                background: #1e293b;
                transform: translateY(-1px);
            }
            /* Alert single action */
            .driyum-dialog-alert .driyum-dialog-btn-confirm {
                flex: 1;
                width: 100%;
            }
        `;
        document.head.appendChild(style);
    }

    const ICONS = {
        danger: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>`,
        warning: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`,
        success: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`,
        info: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`
    };

    function escapeHtml(str) {
        if (typeof str !== 'string') return String(str || '');
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    let activeDialog = null;

    function renderDialog(config) {
        ensureStyles();
        return new Promise((resolve) => {
            if (activeDialog) {
                activeDialog.close(false);
            }

            const isConfirm = config.mode === 'confirm';
            const type = config.type || (isConfirm ? 'warning' : 'info');
            const iconSvg = ICONS[type] || ICONS.info;

            const backdrop = document.createElement('div');
            backdrop.className = `driyum-dialog-backdrop driyum-dialog-${type} ${isConfirm ? 'driyum-dialog-confirm' : 'driyum-dialog-alert'}`;
            backdrop.setAttribute('role', 'dialog');
            backdrop.setAttribute('aria-modal', 'true');

            backdrop.innerHTML = `
                <div class="driyum-dialog-card" id="driyum-active-card">
                    <button type="button" class="driyum-dialog-close" id="driyum-dlg-close" aria-label="Close">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1L13 13M1 13L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                    <div class="driyum-dialog-icon-wrapper">
                        <div class="driyum-dialog-icon-badge">
                            ${iconSvg}
                        </div>
                    </div>
                    <h3 class="driyum-dialog-title">${escapeHtml(config.title)}</h3>
                    <div class="driyum-dialog-message">${config.html ? config.message : escapeHtml(config.message)}</div>
                    <div class="driyum-dialog-actions">
                        ${isConfirm ? `<button type="button" class="driyum-dialog-btn driyum-dialog-btn-cancel" id="driyum-dlg-cancel">${escapeHtml(config.cancelText || 'Cancel')}</button>` : ''}
                        <button type="button" class="driyum-dialog-btn driyum-dialog-btn-confirm" id="driyum-dlg-confirm">${escapeHtml(config.confirmText || (isConfirm ? (type === 'danger' ? 'Delete' : 'Confirm') : 'OK'))}</button>
                    </div>
                </div>
            `;

            document.body.appendChild(backdrop);

            const confirmBtn = backdrop.querySelector('#driyum-dlg-confirm');
            const cancelBtn = backdrop.querySelector('#driyum-dlg-cancel');
            const closeBtn = backdrop.querySelector('#driyum-dlg-close');

            setTimeout(() => {
                if (confirmBtn) confirmBtn.focus();
            }, 50);

            let closed = false;
            function close(result) {
                if (closed) return;
                closed = true;
                document.removeEventListener('keydown', handleKey);
                backdrop.classList.add('closing');
                setTimeout(() => {
                    if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                    if (activeDialog && activeDialog.element === backdrop) {
                        activeDialog = null;
                    }
                    resolve(result);
                }, 180);
            }

            function handleKey(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    close(isConfirm ? false : true);
                } else if (e.key === 'Enter') {
                    if (document.activeElement !== cancelBtn) {
                        e.preventDefault();
                        close(true);
                    }
                }
            }
            document.addEventListener('keydown', handleKey);

            confirmBtn.addEventListener('click', () => close(true));
            if (cancelBtn) cancelBtn.addEventListener('click', () => close(false));
            if (closeBtn) closeBtn.addEventListener('click', () => close(isConfirm ? false : true));

            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    close(isConfirm ? false : true);
                }
            });

            activeDialog = { element: backdrop, close };
        });
    }

    function autoDetectType(message, isConfirm) {
        const text = String(message || '').toLowerCase();
        if (/delete|erase|remove|nuke|danger|destroy|forever|trash/.test(text)) {
            return 'danger';
        }
        if (/warning|caution|alert|risk|irreversible/.test(text)) {
            return 'warning';
        }
        if (/success|congratulat|saved|updated|done/.test(text)) {
            return 'success';
        }
        if (/error|failed|failure|invalid|wrong|cannot/.test(text)) {
            return 'danger';
        }
        return isConfirm ? 'warning' : 'info';
    }

    function autoDetectTitle(type, isConfirm) {
        if (isConfirm) {
            if (type === 'danger') return 'Confirm Deletion';
            if (type === 'warning') return 'Are you sure?';
            if (type === 'success') return 'Confirm Action';
            return 'Please Confirm';
        } else {
            if (type === 'danger') return 'Attention';
            if (type === 'warning') return 'Notice';
            if (type === 'success') return 'Success';
            return 'Notice';
        }
    }

    // --- GLOBAL API ---
    window.showConfirm = function (message, options = {}) {
        if (typeof options === 'string') options = { title: options };
        const type = options.type || autoDetectType(message, true);
        const title = options.title || autoDetectTitle(type, true);
        const defaultConfirmText = type === 'danger' ? 'Confirm' : 'Confirm';
        return renderDialog({
            mode: 'confirm',
            message: String(message || ''),
            title: title,
            type: type,
            confirmText: options.confirmText || defaultConfirmText,
            cancelText: options.cancelText || 'Cancel',
            html: !!options.html
        });
    };
    window.customConfirm = window.showConfirm;

    window.showAlert = function (message, options = {}) {
        if (typeof options === 'string') options = { title: options };
        const type = options.type || autoDetectType(message, false);
        const title = options.title || autoDetectTitle(type, false);
        return renderDialog({
            mode: 'alert',
            message: String(message || ''),
            title: title,
            type: type,
            confirmText: options.confirmText || 'OK',
            html: !!options.html
        });
    };
    window.customAlert = window.showAlert;

    // --- OVERRIDE NATIVE ALERT & CONFIRM ---
    let _bypassConfirmValue = null;

    window.confirm = function (message) {
        if (_bypassConfirmValue !== null) {
            return _bypassConfirmValue;
        }
        return window.showConfirm(message);
    };

    window.alert = function (message) {
        return window.showAlert(message);
    };

    // --- INLINE HANDLER INTERCEPTORS ---
    document.addEventListener('click', async function (e) {
        const el = e.target.closest('a, button, input[type="submit"], [onclick*="confirm("], [data-confirm]');
        if (!el) return;

        if (el._dialogConfirmed) {
            delete el._dialogConfirmed;
            return;
        }

        const onclickAttr = el.getAttribute('onclick') || '';
        const dataConfirm = el.getAttribute('data-confirm');

        let message = null;
        if (dataConfirm) {
            message = dataConfirm;
        } else if (onclickAttr && /confirm\s*\(/.test(onclickAttr)) {
            const match = onclickAttr.match(/confirm\s*\(\s*(['"`])(.*?)\1\s*\)/s);
            if (match && match[2]) {
                message = match[2];
            } else {
                message = 'Are you sure you want to proceed?';
            }
        }

        if (!message) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const isDanger = /delete|erase|remove|nuke|danger|destroy|forever|trash/i.test(message);
        const confirmed = await window.showConfirm(message, {
            type: isDanger ? 'danger' : 'warning',
            confirmText: isDanger ? 'Yes, Delete' : 'Confirm'
        });

        if (confirmed) {
            el._dialogConfirmed = true;
            _bypassConfirmValue = true;

            // Direct link navigation
            if (el.tagName === 'A' && el.href && !el.href.startsWith('javascript:')) {
                if (/^\s*return\s+confirm\s*\(.*?\)\s*;?\s*$/.test(onclickAttr) || !onclickAttr) {
                    window.location.href = el.href;
                    _bypassConfirmValue = null;
                    return;
                }
            }

            // if(confirm(...)) window.location='...'
            const locMatch = onclickAttr.match(/window\.location\s*=\s*(['"`])(.*?)\1/);
            if (locMatch && locMatch[2]) {
                window.location.href = locMatch[2];
                _bypassConfirmValue = null;
                return;
            }

            // Form submit button
            if ((el.type === 'submit' || el.getAttribute('type') === 'submit') && el.form) {
                const preStatements = onclickAttr.replace(/return\s+confirm\s*\(.*?\)\s*;?/g, '').trim();
                if (preStatements) {
                    try { new Function(preStatements).call(el); } catch (err) { console.error(err); }
                }
                el.form._dialogConfirmed = true;
                if (el.name) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = el.name;
                    hidden.value = el.value || '1';
                    el.form.appendChild(hidden);
                }
                el.form.submit();
                _bypassConfirmValue = null;
                return;
            }

            // Re-trigger click with bypass
            el.click();
            setTimeout(() => {
                _bypassConfirmValue = null;
                delete el._dialogConfirmed;
            }, 50);
        }
    }, true);

    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!form || !(form instanceof HTMLFormElement)) return;

        if (form._dialogConfirmed) {
            delete form._dialogConfirmed;
            return;
        }

        const onsubmitAttr = form.getAttribute('onsubmit') || '';
        const dataConfirm = form.getAttribute('data-confirm');

        let message = null;
        if (dataConfirm) {
            message = dataConfirm;
        } else if (onsubmitAttr && /confirm\s*\(/.test(onsubmitAttr)) {
            const match = onsubmitAttr.match(/confirm\s*\(\s*(['"`])(.*?)\1\s*\)/s);
            if (match && match[2]) {
                message = match[2];
            } else {
                message = 'Are you sure you want to submit this form?';
            }
        }

        if (!message) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const isDanger = /delete|erase|remove|nuke|danger|destroy|forever|trash/i.test(message);
        const confirmed = await window.showConfirm(message, {
            type: isDanger ? 'danger' : 'warning',
            confirmText: isDanger ? 'Yes, Proceed' : 'Confirm'
        });

        if (confirmed) {
            form._dialogConfirmed = true;
            _bypassConfirmValue = true;
            form.submit();
            setTimeout(() => {
                _bypassConfirmValue = null;
                delete form._dialogConfirmed;
            }, 50);
        }
    }, true);
})();

