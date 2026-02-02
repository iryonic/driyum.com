<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driyum Admin</title>
    <link rel="icon" type="image/png" href="<?php echo get_url('assets/images/logoicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo get_url('assets/images/logoicon.png'); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/chunky.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800;900&display=swap');
        
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .fredoka { font-family: 'Fredoka', sans-serif; }
        
        .admin-sidebar { 
            height: 100vh; 
            position: fixed; 
            left: 0; 
            top: 0; 
            width: 280px; 
            overflow-y: auto; 
            background: #0f172a;
            z-index: 100;
        }
        
        .admin-sidebar::-webkit-scrollbar { width: 4px; }
        .admin-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        
        .admin-content { margin-left: 280px; padding: 2.5rem; }
        
        .nav-link {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-link-active {
            background: #19DC7E !important;
            color: #000 !important;
            font-weight: 800;
            box-shadow: 0 10px 15px -3px rgba(25, 220, 126, 0.4);
        }
        
        .btn-chunky {
            border-bottom: 4px solid rgba(0,0,0,0.2);
            transition: all 0.2s;
        }
        .btn-chunky:active {
            transform: translateY(2px);
            border-bottom-width: 2px;
        }

        /* Image Preview Overlay */
        .preview-container img {
            transition: transform 0.3s ease;
        }
        .preview-container:hover img {
            transform: scale(1.05);
        }
        
        @media (max-width: 1024px) {
            .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-content { margin-left: 0; padding: 1.5rem; }
            
            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
                z-index: 90;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease;
            }
            .sidebar-overlay.active {
                opacity: 1;
                pointer-events: auto;
            }
        }
    </style>
    <script>
        function previewImage(input, targetId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    const target = document.getElementById(targetId);
                    if (target.tagName === 'IMG') {
                        target.src = e.target.result;
                    } else {
                        target.style.backgroundImage = `url(${e.target.result})`;
                        target.innerHTML = ''; // Clear icon/text
                    }
                    target.classList.add('preview-active');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewMultipleImages(input, targetContainerId) {
            if (input.files) {
                const container = document.getElementById(targetContainerId);
                // Don't clear existing, just append new previews for clarity or maybe clear selected?
                // Let's clear the specific "new previews" area
                let previewArea = container.querySelector('.new-previews-area');
                if(!previewArea) {
                    previewArea = document.createElement('div');
                    previewArea.className = 'new-previews-area grid grid-cols-4 gap-4 mt-4 w-full';
                    container.appendChild(previewArea);
                }
                previewArea.innerHTML = ''; 

                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative group aspect-square';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-full object-cover rounded-xl border-2 border-[#19DC7E]">
                            <span class="absolute top-1 left-1 bg-[#19DC7E] text-black text-[8px] font-black px-1.5 py-0.5 rounded-full">NEW</span>
                        `;
                        previewArea.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            }
        }
    </script>
</head>
<body class="bg-gray-50">

    <!-- SIDEBAR OVERLAY -->
    <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar(false)"></div>

    <!-- MOBILE TOGGLE -->
    <button onclick="toggleSidebar()" class="lg:hidden fixed bottom-6 right-6 z-[110] w-14 h-14 bg-black text-white rounded-full shadow-[0_15px_30px_rgba(25,220,126,0.4)] flex items-center justify-center text-xl hover:scale-110 active:scale-90 transition-all">
        <i class="fas fa-bars"></i>
    </button>

    <!-- SIDEBAR -->
    <aside class="admin-sidebar bg-[#111827] text-white p-6 flex flex-col">
        <div class="mb-10 flex items-center gap-3 px-2">
            <div class="w-10 h-10 bg-[#19DC7E] rounded-full flex items-center justify-center text-black text-xl font-bold">
                <i class="fas fa-crown"></i>
            </div>
            <span class="font-['Fredoka'] font-bold text-2xl tracking-wide">Admin</span>
        </div>

        <nav class="space-y-2 flex-1 font-['Outfit']">
            <a href="index.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='index.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-chart-pie w-6"></i> Dashboard
            </a>
            <a href="products.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo (basename($_SERVER['PHP_SELF'])=='products.php' || basename($_SERVER['PHP_SELF'])=='product_form.php')?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-box w-6"></i> Products
            </a>
            <a href="categories.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo (basename($_SERVER['PHP_SELF'])=='categories.php' || basename($_SERVER['PHP_SELF'])=='category_form.php')?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-tags w-6"></i> Categories
            </a>
            <a href="orders.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='orders.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-shipping-fast w-6"></i> Orders
            </a>
            <a href="abandoned_carts.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='abandoned_carts.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-ghost w-6"></i> Abandoned Carts
            </a>
            <a href="users.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='users.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-users w-6"></i> Customers
            </a>
            <a href="coupons.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='coupons.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-ticket-alt w-6"></i> Coupons
            </a>
            <a href="affiliates.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='affiliates.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-handshake w-6"></i> Creators & Affiliates
            </a>
            <a href="subscribers.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='subscribers.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-envelope-open-text w-6"></i> Subscribers
            </a>
            <a href="shipping.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='shipping.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-truck-moving w-6"></i> Shipping & Delivery
            </a>
            
            <div class="h-px bg-gray-800 my-4 mx-2"></div>
            <h4 class="px-4 text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Content</h4>
            
            <a href="manage_home.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='manage_home.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-home w-6"></i> Homepage
            </a>
            <a href="manage_pages.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='manage_pages.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-file-alt w-6"></i> About & Legal Pages
            </a>
            <a href="manage_contact.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='manage_contact.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-headset w-6"></i> Contact & Inbox
            </a>
            <a href="reviews.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='reviews.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-star w-6"></i> Product Reviews
            </a>
            <a href="manage_testimonials.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='manage_testimonials.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-quote-left w-6"></i> Testimonials
            </a>
            <a href="settings.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 <?php echo basename($_SERVER['PHP_SELF'])=='settings.php'?'nav-link-active':'text-gray-400'; ?>">
                <i class="fas fa-cog w-6"></i> Store Settings
            </a>
        </nav>

        <div class="pt-6 border-t border-gray-800">
            <a href="../index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-800 text-gray-400 transition mb-2">
                <i class="fas fa-external-link-alt w-6"></i> View Store
            </a>
            <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition">
                <i class="fas fa-sign-out-alt w-6"></i> Logout
            </a>
        </div>
    </aside>

    <main class="admin-content">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-100 text-green-600 rounded-2xl font-bold flex items-center gap-3 anim-up shadow-sm">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-600 rounded-2xl font-bold flex items-center gap-3 anim-up shadow-sm">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <header class="mb-12 relative z-50">
            <div class="flex flex-row  justify-between items-center gap-6 bg-white/50 backdrop-blur-md p-6 rounded-[35px] border border-gray-100 shadow-sm">
                <!-- Global Omni-Search -->
                <div class="relative w-full md:max-w-md group" id="omni-search-container">
                    <div class="relative">
                        <input type="text" id="omni-search-input" placeholder="Search orders, snacks, or people..." 
                            class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[22px] px-6 py-3 pl-12 outline-none transition-all font-bold text-sm"
                            autocomplete="off">
                        <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-[#19DC7E] transition-colors"></i>
                        
                        <!-- Loading Spinner -->
                        <div id="omni-search-loading" class="absolute right-5 top-1/2 -translate-y-1/2 hidden">
                            <i class="fas fa-circle-notch fa-spin text-[#19DC7E]"></i>
                        </div>
                    </div>

                    <!-- Search Results Dropdown -->
                    <div id="omni-results" class="absolute left-0 top-full mt-4 w-full bg-white rounded-[32px] shadow-[0_25px_70px_rgba(0,0,0,0.15)] border border-gray-100 overflow-hidden hidden anim-up">
                        <div id="omni-results-content" class="max-h-[60vh] overflow-y-auto p-2">
                            <!-- Results injected here -->
                        </div>
                        <div class="bg-gray-50 px-6 py-3 text-[9px] font-black text-gray-400 uppercase tracking-widest border-t border-gray-100 flex justify-between">
                            <span>Quick Find</span>
                            <span>ESC to close</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 ml-auto md:ml-0">
                    <?php 
                    $unread_notifs = get_unread_notifications(); 
                    $unread_count = count($unread_notifs);
                    ?>
                    <div class="relative" id="notif-dropdown">
                        <button onclick="toggleNotifDropdown(event)" class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-lg hover:bg-gray-50 transition-all relative">
                            <i class="fas fa-bell text-gray-400 text-lg hover:text-black transition-colors"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full border-2 border-white animate-pulse">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Dropdown -->
                        <div id="notif-dropdown-content" class="absolute right-0 top-full mt-4 w-96 bg-white rounded-2xl shadow-2xl border border-gray-100 p-2 hidden z-[100] anim-up">
                            <div class="px-4 py-3 border-b border-gray-50 flex justify-between items-center">
                                <h4 class="font-bold text-gray-900">Notifications</h4>
                                <span class="text-xs text-gray-400"><?php echo $unread_count; ?> new</span>
                            </div>
                            <div class="max-h-[70vh] overflow-y-auto">
                                <?php if (empty($unread_notifs)): ?>
                                    <div class="p-8 text-center text-gray-400 text-sm">
                                        <i class="far fa-bell-slash text-2xl mb-2 block opacity-50"></i>
                                        All caught up!
                                    </div>
                                <?php else: ?>
                                    <?php foreach($unread_notifs as $notif): ?>
                                        <a href="<?php echo !empty($notif['link']) ? $notif['link'] : '#'; ?>" onclick="markRead(<?php echo $notif['id']; ?>)" class="block px-4 py-4 hover:bg-gray-50 rounded-xl transition-colors border-b border-gray-50 last:border-0 relative group/item">
                                            <div class="flex gap-4">
                                                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                                                     <?php if($notif['type'] == 'order'): ?>
                                                         <i class="fas fa-shopping-bag"></i>
                                                     <?php elseif($notif['type'] == 'alert'): ?>
                                                         <i class="fas fa-exclamation-triangle text-amber-500"></i>
                                                     <?php else: ?>
                                                         <i class="fas fa-info-circle"></i>
                                                     <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-800 leading-tight mb-1 group-hover/item:text-blue-600 transition-colors">
                                                        <?php echo htmlspecialchars($notif['message']); ?>
                                                    </p>
                                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                                        <?php echo date('M d, H:i', strtotime($notif['created_at'])); ?>
                                                    </p>
                                                </div>
                                                 <?php if(!$notif['is_read']): ?>
                                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 w-2 h-2 bg-blue-500 rounded-full"></div>
                                                 <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <script>
        async function markRead(id) {
            try {
                await fetch('mark_notification_read.php?id=' + id);
            } catch(e) {}
        }

        function toggleSidebar(force) {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (force === true) {
                sidebar.classList.add('open');
                overlay.classList.add('active');
            } else if (force === false) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            } else {
                const isOpen = sidebar.classList.toggle('open');
                overlay.classList.toggle('active', isOpen);
            }
        }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                toggleSidebar(false);
                document.getElementById('omni-results').classList.add('hidden');
                document.getElementById('notif-dropdown-content').classList.add('hidden');
            }
        });

        function toggleNotifDropdown(e) {
            e.stopPropagation();
            document.getElementById('notif-dropdown-content').classList.toggle('hidden');
            document.getElementById('omni-results').classList.add('hidden');
        }

        // Omni-Search Logic
        const omniInput = document.getElementById('omni-search-input');
        const omniResults = document.getElementById('omni-results');
        const omniResultsContent = document.getElementById('omni-results-content');
        const omniLoading = document.getElementById('omni-search-loading');
        let searchTimeout = null;

        omniInput.addEventListener('input', (e) => {
            const q = e.target.value.trim();
            clearTimeout(searchTimeout);

            if (q.length < 2) {
                omniResults.classList.add('hidden');
                return;
            }

            omniLoading.classList.remove('hidden');

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api/omni_search.php?q=${encodeURIComponent(q)}`);
                    const data = await response.json();
                    
                    renderOmniResults(data);
                } catch (err) {
                    console.error('Search failed', err);
                } finally {
                    omniLoading.classList.add('hidden');
                }
            }, 300);
        });

        function renderOmniResults(results) {
            if (results.length === 0) {
                omniResultsContent.innerHTML = `
                    <div class="p-8 text-center text-gray-400">
                        <i class="fas fa-ghost text-2xl mb-2 block opacity-30"></i>
                        <p class="text-xs font-bold uppercase tracking-widest">Nothing found for "${omniInput.value}"</p>
                    </div>
                `;
            } else {
                let html = '';
                results.forEach(res => {
                    const icon = res.image 
                        ? `<img src="../${res.image}" class="w-10 h-10 rounded-lg object-cover">`
                        : `<div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 border border-gray-100"><i class="${res.icon || 'fas fa-info-circle'}"></i></div>`;
                    
                    html += `
                        <a href="${res.url}" class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-2xl transition-all group/item">
                            ${icon}
                            <div>
                                <p class="text-sm font-black text-gray-900 group-hover/item:text-[#19DC7E] transition-colors">${res.title}</p>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">${res.subtitle}</p>
                            </div>
                            <div class="ml-auto opacity-0 group-hover/item:opacity-100 transition-opacity">
                                <i class="fas fa-chevron-right text-[10px] text-[#19DC7E]"></i>
                            </div>
                        </a>
                    `;
                });
                omniResultsContent.innerHTML = html;
            }
            omniResults.classList.remove('hidden');
        }

        // Close dropdowns on click outside
        document.addEventListener('click', (e) => {
            if (!document.getElementById('omni-search-container').contains(e.target)) {
                omniResults.classList.add('hidden');
            }
            if (!document.getElementById('notif-dropdown').contains(e.target)) {
                document.getElementById('notif-dropdown-content').classList.add('hidden');
            }
        });
        </script>
