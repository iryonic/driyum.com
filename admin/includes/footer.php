        </main>
        
        <footer class="mt-auto px-4 sm:px-6 lg:px-8 py-4 border-t border-slate-200/80 bg-white/50 text-slate-400 text-xs flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                &copy; <?php echo date('Y'); ?> <span class="font-semibold text-slate-600">Driyum</span> Ecommerce Operations Console <span class="text-xs text-[#24B25D]">Powered by Saastify</span>
            </div>
            <div class="flex items-center gap-4 text-[11px]">
                <a href="../" target="_blank" class="hover:text-slate-600 transition-colors">Storefront</a>
                <span>&bull;</span>
                <a href="settings.php" class="hover:text-slate-600 transition-colors">Settings</a>
                <span>&bull;</span>
                <span class="text-emerald-600 font-semibold flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online</span>
            </div>
        </footer>
    </div>

    <?php
    $footer_curr_page = $curr_page ?? basename($_SERVER['PHP_SELF']);
    $footer_orders_today = $total_orders_today ?? 0;
    ?>

    <!-- Dedicated Styles for Mobile Bottom Navigation to Guarantee Instant Rendering -->
    <style>
        #admin-mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(64px + env(safe-area-inset-bottom, 0px));
            padding: 0 0.4rem env(safe-area-inset-bottom, 0px) 0.4rem;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 -4px 20px -2px rgba(15, 23, 42, 0.08), 0 -1px 3px 0 rgba(15, 23, 42, 0.03);
            z-index: 85;
            display: flex;
            align-items: center;
            justify-content: space-around;
        }

        .mob-nav-btn {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.15rem;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            position: relative;
            border: none;
            background: transparent;
            cursor: pointer;
        }

        .mob-nav-btn .mob-nav-icon-box {
            width: 38px;
            height: 28px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            transition: all 0.2s ease;
            position: relative;
        }

        .mob-nav-btn .mob-nav-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: -0.01em;
            margin-top: 2px;
            transition: color 0.2s ease;
            line-height: 1.1;
        }

        .mob-nav-btn:hover {
            color: #004f42;
        }

        .mob-nav-btn.active {
            color: #004f42;
        }

        .mob-nav-btn.active .mob-nav-icon-box {
            background: rgba(41, 226, 88, 0.18);
            color: #004f42;
            transform: translateY(-1px);
        }

        .mob-nav-btn.active .mob-nav-label {
            color: #004f42;
            font-weight: 700;
        }

        .mob-nav-btn.active::after {
            content: '';
            position: absolute;
            bottom: 2px;
            width: 14px;
            height: 3px;
            border-radius: 999px;
            background: #29e258;
        }

        .mob-fab-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .mob-fab-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #15803d;
            color: #ffffff !important;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3.5px solid #ffffff;
            box-shadow: 0 4px 14px rgba(21, 128, 61, 0.4);
            margin-top: -24px;
            transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-decoration: none;
            -webkit-tap-highlight-color: transparent;
            position: relative;
            z-index: 2;
        }

        .mob-fab-btn i {
            font-size: 16px;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.25));
            transition: transform 0.25s ease;
        }

        .mob-fab-btn:hover {
            transform: scale(1.08) translateY(-2px);
        }

        .mob-fab-btn:hover i {
            transform: rotate(90deg);
        }

        .mob-fab-btn:active {
            transform: scale(0.92);
        }

        .mob-fab-label {
            font-size: 10px;
            font-weight: 700;
            color: #004f42;
            margin-top: 2px;
            letter-spacing: -0.01em;
            line-height: 1.1;
        }

        .mob-badge {
            position: absolute;
            top: -3px;
            right: -6px;
            background: #ef4444;
            color: #ffffff;
            font-size: 9px;
            font-weight: 800;
            height: 16px;
            min-width: 16px;
            padding: 0 4px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid #ffffff;
            box-shadow: 0 2px 5px rgba(239, 68, 68, 0.4);
            line-height: 1;
        }

        @media (max-width: 768px) {
            main.flex-1 {
                padding-bottom: calc(84px + env(safe-area-inset-bottom, 0px)) !important;
            }
            footer.mt-auto {
                margin-bottom: calc(64px + env(safe-area-inset-bottom, 0px)) !important;
            }
            .admin-bulk-dock {
                bottom: calc(68px + 0.65rem + env(safe-area-inset-bottom, 0px)) !important;
                z-index: 95;
            }
            body.bulk-bar-active main.flex-1,
            body:has(.admin-bulk-dock:not(.hidden)) main.flex-1 {
                padding-bottom: calc(145px + env(safe-area-inset-bottom, 0px)) !important;
            }
        }

        @media (min-width: 769px) {
            #admin-mobile-bottom-nav {
                display: none !important;
            }
        }
    </style>

    <!-- MOBILE BOTTOM NAVIGATION BAR (< 768px) -->
    <nav id="admin-mobile-bottom-nav" aria-label="Mobile Navigation">
        
        <!-- 1. Dashboard -->
        <a href="index.php" class="mob-nav-btn <?php echo $footer_curr_page == 'index.php' ? 'active' : ''; ?>">
            <div class="mob-nav-icon-box">
                <i class="fas fa-chart-pie"></i>
            </div>
            <span class="mob-nav-label">Dashboard</span>
        </a>

        <!-- 2. Orders -->
        <a href="orders.php" class="mob-nav-btn <?php echo in_array($footer_curr_page, ['orders.php', 'generate_batch_shipments.php', 'generate_label.php']) ? 'active' : ''; ?>">
            <div class="mob-nav-icon-box">
                <i class="fas fa-box-open"></i>
                <?php if ($footer_orders_today > 0): ?>
                    <span class="mob-badge">
                        <?php echo $footer_orders_today > 9 ? '9+' : $footer_orders_today; ?>
                    </span>
                <?php endif; ?>
            </div>
            <span class="mob-nav-label">Orders</span>
        </a>

        <!-- 3. Center Raised FAB: Add Hero Slide -->
        <div class="mob-fab-col">
            <a href="<?php echo $footer_curr_page == 'hero_slides.php' ? 'javascript:void(0);' : 'hero_slides.php?action=new'; ?>" 
               onclick="<?php echo $footer_curr_page == 'hero_slides.php' ? 'if(typeof openAddSlideModal===\'function\'){ openAddSlideModal(); return false; }' : ''; ?>"
               class="mob-fab-btn"
               title="Add New Hero Banner Slide"
               aria-label="Add Hero Slide">
                <i class="fas fa-plus"></i>
            </a>
            <span class="mob-fab-label">Add Slide</span>
        </div>

        <!-- 4. Products Sorting -->
        <a href="product_sorting.php" class="mob-nav-btn <?php echo $footer_curr_page == 'product_sorting.php' ? 'active' : ''; ?>">
            <div class="mob-nav-icon-box">
                <i class="fas fa-sort-amount-down"></i>
            </div>
            <span class="mob-nav-label">Sorting</span>
        </a>

        <!-- 5. Hamburger Menu: Opens Sidebar -->
        <button type="button" onclick="toggleSidebar(true)" class="mob-nav-btn" aria-label="Open navigation sidebar">
            <div class="mob-nav-icon-box">
                <i class="fas fa-bars"></i>
            </div>
            <span class="mob-nav-label">Menu</span>
        </button>

    </nav>

    <!-- Global Observer to prevent collision between bulk bar, bottom nav, and pagination -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        function checkBulkBar() {
            const bulkDock = document.querySelector('.admin-bulk-dock');
            if (bulkDock) {
                const isActive = !bulkDock.classList.contains('hidden') && bulkDock.style.display !== 'none';
                document.body.classList.toggle('bulk-bar-active', isActive);
            }
        }
        
        const bulkDock = document.querySelector('.admin-bulk-dock');
        if (bulkDock && window.MutationObserver) {
            const observer = new MutationObserver(checkBulkBar);
            observer.observe(bulkDock, { attributes: true, attributeFilter: ['class', 'style'] });
            checkBulkBar();
        }
    });
    </script>
</body>
</html>
