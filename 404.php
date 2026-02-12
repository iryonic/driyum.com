<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Lost in the Orchard | DRIYUM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;700&family=Figtree:wght@400;900&display=swap" rel="stylesheet">
    <?php
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    ?>
    <base href="<?php echo get_url(''); ?>">
    <style>
        body { font-family: 'Figtree', sans-serif; }
        .crimson-pro { font-family: 'Montserrat', sans-serif; }
        
        .btn-chunky {
            border-bottom: 6px solid rgba(0,0,0,0.2);
            transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-radius: 24px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-chunky:hover {
            transform: translateY(-4px);
            border-bottom-width: 8px;
        }
        .btn-chunky:active {
            transform: translateY(2px);
            border-bottom-width: 2px;
        }

        .bg-pattern {
            background-color: #FFFEDC;
            background-image: radial-gradient(#24B25D 0.5px, transparent 0.5px);
            background-size: 24px 24px;
            background-position: 0 0, 12px 12px;
            opacity: 0.1;
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        .floating { animation: float 4s ease-in-out infinite; }
        
        .orchard-glow {
            background: radial-gradient(circle at 50% 50%, rgba(25, 220, 126, 0.15) 0%, transparent 70%);
        }
    </style>
</head>
<body class="bg-[#FFFEDC] min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Decorative Background -->
    <div class="fixed inset-0 bg-pattern"></div>
    <div class="fixed top-[-10%] left-[-10%] w-[40%] h-[40%] bg-[#24B25D]/10 rounded-full blur-[120px]"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-[#24B25D]/5 rounded-full blur-[150px]"></div>

    <div class="relative z-10 max-w-3xl w-full text-center">
        <!-- Brand Header (Mini) -->
         <a href="<?php echo get_url(''); ?>" class="inline-flex items-center gap-3 mb-12 group">
            <span class="text-2xl font-heading font-black text-gray-900 tracking-tight"><img src="<?php echo get_url('assets/images/logo.svg'); ?>" alt="logo" height="100px" width="100px"></span>
        </a>
        <!-- 404 Visual -->
        <div class="relative mb-8">
            <h1 class="text-[150px] md:text-[220px] font-sans font-black text-gray-900/5 leading-none select-none tracking-tighter">404</h1>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-[100px] md:text-[140px] floating">🍏</div>
            </div>
            
            <!-- Confused Emoji Overlays -->
            <div class="absolute top-[20%] left-0 text-4xl opacity-20 floating" style="animation-delay: 1s">🧺</div>
            <div class="absolute bottom-[20%] right-0 text-4xl opacity-20 floating" style="animation-delay: 2s">🤷‍♂️</div>
        </div>

        <!-- Content -->
        <div class="space-y-6">
            <h2 class="text-4xl md:text-6xl font-heading font-black text-gray-900 leading-tight">
                Page Not <span class="text-[#24B25D]">Found</span>?
            </h2>
            <p class="text-gray-500 font-bold text-lg md:text-xl max-w-lg mx-auto leading-relaxed">
                Even our freshest apples can't find this page. It seems to have vanished into thin Kashmiri air.
            </p>
        </div>

        <!-- Call to Action -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6 pt-12">
            <a href="<?php echo get_url(''); ?>" class="btn-chunky bg-[#111827] text-white px-12 py-6 text-lg shadow-2xl flex items-center gap-3 hover:bg-[#24B25D] hover:text-black w-full sm:w-auto">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="<?php echo get_url('shop'); ?>" class="btn-chunky bg-white text-gray-900 px-12 py-6 text-lg shadow-sm border-2 border-gray-100 hover:border-black w-full sm:w-auto">
                <i class="fas fa-shopping-basket"></i> Browse Shop
            </a>
        </div>

        <!-- Footer Note -->
        <div class="mt-20">
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400">
                Error Code: PRD-404-ORCHARD
            </p>
        </div>
    </div>

    <!-- Mouse Trail Spot (Optional Glow) -->
    <div class="fixed pointer-events-none w-96 h-96 bg-[#24B25D]/10 rounded-full blur-[100px]" id="glow"></div>

    <script>
        document.addEventListener('mousemove', (e) => {
            const glow = document.getElementById('glow');
            glow.style.left = e.clientX - 192 + 'px';
            glow.style.top = e.clientY - 192 + 'px';
        });
    </script>
</body>
</html>


