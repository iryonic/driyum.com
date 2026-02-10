<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>505 - Recipe Mismatch | DRIYUM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;700&family=Outfit:wght@400;900&display=swap" rel="stylesheet">
    <?php
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    ?>
    <base href="<?php echo get_url(''); ?>">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .fredoka { font-family: 'Fredoka', sans-serif; }
        
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

        .bg-gradient-teal {
            background: radial-gradient(circle at center, #F0FDFA 0%, #CCFBF1 100%);
        }

        @keyframes drift {
            0% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(10px, -15px) rotate(2deg); }
            66% { transform: translate(-5px, 10px) rotate(-2deg); }
            100% { transform: translate(0, 0) rotate(0deg); }
        }
        .kiwi-float { animation: drift 6s ease-in-out infinite; }

        .glitch-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(0deg, rgba(20, 184, 166, 0.05) 0px, rgba(20, 184, 166, 0.05) 1px, transparent 1px, transparent 2px);
            pointer-events: none;
        }

        .circle-reveal {
            width: 15rem;
            height: 15rem;
            background: #14B8A6;
            filter: blur(80px);
            opacity: 0.2;
            position: absolute;
            z-index: 1;
        }
    </style>
</head>
<body class="bg-gradient-teal min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Background Layers -->
    <div class="glitch-layer"></div>
    <div class="circle-reveal top-[-5%] left-[-5%]"></div>
    <div class="circle-reveal bottom-[-5%] right-[-5%]"></div>

    <div class="relative z-10 max-w-3xl w-full text-center">
        <!-- Brand Header (Mini) -->
        <a href="<?php echo get_url(''); ?>" class="inline-flex items-center gap-3 mb-12 group">
            <span class="text-2xl font-['Fredoka'] font-black text-gray-900 tracking-tight"><img src="<?php echo get_url('assets/images/logo.svg'); ?>" alt="logo" height="100px" width="100px"></span>
        </a>

        <!-- 505 Visual -->
        <div class="relative mb-12 group">
            <!-- Massive Transparent Number -->
            <h1 class="text-[140px] md:text-[200px] font-['Outfit'] font-black text-[#14B8A6]/10 leading-none select-none tracking-tighter transition-all duration-500 group-hover:tracking-normal group-hover:text-[#14B8A6]/20">
                505
            </h1>
            
            <!-- Centered Hero Icon -->
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="relative">
                    <div class="text-[120px] md:text-[160px] kiwi-float">🥝</div>
                    <!-- Small Sparkles -->
                    <div class="absolute -top-4 -right-4 text-4xl animate-pulse delay-700">✨</div>
                    <div class="absolute -bottom-2 -left-6 text-2xl animate-bounce">⚡</div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="space-y-6">
            <h2 class="text-4xl md:text-7xl font-['Fredoka'] font-black text-gray-900 leading-tight">
                Version <span class="text-[#14B8A6]">Not Supported</span>.
            </h2>
            <p class="text-gray-500 font-bold text-lg md:text-2xl max-w-lg mx-auto leading-relaxed">
                Your browser is using a vintage protocol. We only serve our premium mountain-fresh treats using the latest digital standards.
            </p>
        </div>

        <!-- Primary Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6 pt-16">
            <a href="<?php echo get_url(''); ?>" class="btn-chunky bg-gray-900 text-white px-14 py-6 text-xl shadow-2xl flex items-center gap-4 hover:bg-[#14B8A6] hover:text-white transition-all w-full sm:w-auto">
                <i class="fas fa-bolt"></i> Upgrade to Home
            </a>
            <a href="<?php echo get_url('shop'); ?>" class="btn-chunky bg-white text-gray-800 px-14 py-6 text-xl shadow-sm border-2 border-gray-100 hover:border-[#14B8A6] transition-all w-full sm:w-auto">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>

        <!-- Footer ID Tag -->
        <div class="mt-24">
            <div class="inline-block px-4 py-2 bg-white/40 backdrop-blur-md rounded-full border border-[#14B8A6]/20">
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-[#14B8A6] mb-0">
                    HTTP Version Not Supported
                </p>
            </div>
        </div>
    </div>

    <!-- Interactive Mouse Lighting -->
    <div class="fixed pointer-events-none w-[600px] h-[600px] rounded-full blur-[120px] bg-[#14B8A6]/5" id="mouse-glow"></div>

    <script>
        const glow = document.getElementById('mouse-glow');
        window.addEventListener('mousemove', (e) => {
            const x = e.clientX;
            const y = e.clientY;
            glow.style.left = (x - 300) + 'px';
            glow.style.top = (y - 300) + 'px';
        });
    </script>
</body>
</html>
