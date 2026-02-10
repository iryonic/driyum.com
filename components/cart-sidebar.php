<?php
// Premium Cart Sidebar
?>
<!-- Overlay -->
<div id="cart-overlay" onclick="toggleCart()" class="fixed inset-0 bg-black/40 hidden z-[60] backdrop-blur-sm transition-opacity opacity-0 pointer-events-none data-[show=true]:opacity-100 data-[show=true]:pointer-events-auto"></div>

<!-- Sidebar -->
<div id="cart-sidebar" class="fixed top-0 right-0 h-full w-full max-w-md bg-white z-[70] transform translate-x-full transition-transform duration-300 shadow-2xl flex flex-col">
    
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <h2 class="text-lg font-bold text-gray-900">Shopping Cart</h2>
        <button onclick="toggleCart()" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <!-- Items -->
    <div class="flex-1 overflow-y-auto p-6" id="cart-items-container">
        <!-- JS Injected Items -->
        <div class="flex flex-col items-center justify-center h-full text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            </div>
            <p class="text-gray-500 font-medium">Your cart is empty</p>
            <button onclick="toggleCart()" class="text-primary font-semibold mt-2 hover:underline">Continue Shopping</button>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-6 border-t border-gray-100 bg-gray-50/50">
        <div class="flex justify-between items-center mb-4">
             <span class="text-gray-600">Subtotal</span>
             <span class="font-bold text-xl text-gray-900" id="cart-total">₹0.00</span>
        </div>
        <p class="text-xs text-gray-500 mb-6 text-center">Shipping & taxes calculated at checkout</p>
        <a href="<?php echo get_url('checkout'); ?>" class="btn-primary w-full justify-center py-4 text-lg shadow-lg shadow-green-200">
            Checkout Securely
        </a>
    </div>
</div>

<script>
    // Simple Toggle Logic
    const overlay = document.getElementById('cart-overlay');
    const sidebar = document.getElementById('cart-sidebar');
    
    function toggleCart() {
        const isClosed = sidebar.classList.contains('translate-x-full');
        if (isClosed) {
            sidebar.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            // Small delay for opacity transition
            setTimeout(() => {
                overlay.setAttribute('data-show', 'true');
            }, 10);
        } else {
            sidebar.classList.add('translate-x-full');
            overlay.setAttribute('data-show', 'false');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
        }
    }
</script>


