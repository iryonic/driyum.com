<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - System Overheated | DRIYUM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;700&family=Figtree:wght@400;900&display=swap" rel="stylesheet">
    <?php
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    ?>
    <base href="<?php echo get_url(''); ?>">
    <style>
        body { font-family: 'Figtree', sans-serif; cursor: none; }
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

        .smoke-particle {
            position: fixed;
            background: rgba(0,0,0,0.05);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        @keyframes shake {
            0% { transform: translate(1px, 1px) rotate(0deg); }
            10% { transform: translate(-1px, -2px) rotate(-1deg); }
            20% { transform: translate(-3px, 0px) rotate(1deg); }
            30% { transform: translate(3px, 2px) rotate(0deg); }
            40% { transform: translate(1px, -1px) rotate(1deg); }
            50% { transform: translate(-1px, 2px) rotate(-1deg); }
            60% { transform: translate(-3px, 1px) rotate(0deg); }
            70% { transform: translate(3px, 1px) rotate(-1deg); }
            80% { transform: translate(-1px, -1px) rotate(1deg); }
            90% { transform: translate(1px, 2px) rotate(0deg); }
            100% { transform: translate(1px, -2px) rotate(-1deg); }
        }
        .vibrate { animation: shake 0.5s infinite; }

        .cursor-pan {
            width: 40px;
            height: 40px;
            position: fixed;
            pointer-events: none;
            z-index: 100;
            font-size: 32px;
            transform: translate(-50%, -50%);
        }
    </style>
</head>
<body class="bg-[#FFF1F2] min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <div class="cursor-pan" id="custom-cursor">🍳</div>

    <div class="relative z-10 max-w-3xl w-full text-center">
        <!-- Brand Header (Mini) -->
        <a href="<?php echo get_url(''); ?>" class="inline-flex items-center gap-3 mb-12 group">
            <span class="text-2xl font-heading font-black text-gray-900 tracking-tight"><img src="<?php echo get_url('assets/images/logo.svg'); ?>" alt="logo" height="100px" width="100px"></span>
        </a>

        <!-- 500 Visual -->
        <div class="relative mb-12">
            <h1 class="text-[150px] md:text-[220px] font-sans font-black text-red-500/5 leading-none select-none tracking-tighter vibrate">500</h1>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-[100px] md:text-[140px] vibrate">🔥</div>
            </div>
            
            <div class="absolute top-[20%] left-0 text-4xl opacity-20 vibrate" style="animation-delay: 0.1s">🍲</div>
            <div class="absolute bottom-[20%] right-0 text-4xl opacity-20 vibrate" style="animation-delay: 0.2s">🚒</div>
        </div>

        <!-- Content -->
        <div class="space-y-6">
            <h2 class="text-4xl md:text-6xl font-heading font-black text-gray-900 leading-tight">
                Server <span class="text-red-500">Overload</span>!
            </h2>
            <p class="text-gray-500 font-bold text-lg md:text-xl max-w-lg mx-auto leading-relaxed">
                Something got a bit too crunchy in the server room. Our chefs are already cleaning up the mess and recalibrating the oven.
            </p>
        </div>

        <!-- Call to Action -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6 pt-12">
            <button onclick="window.location.reload()" class="btn-chunky bg-[#FF4D4D] text-white px-12 py-6 text-lg shadow-2xl flex items-center gap-3 hover:scale-105 active:scale-95 w-full sm:w-auto">
                <i class="fas fa-sync-alt"></i> Try Refreshing
            </button>
            <a href="<?php echo get_url('contact'); ?>" class="btn-chunky bg-white text-gray-900 px-12 py-6 text-lg shadow-sm border-2 border-gray-100 hover:border-black w-full sm:w-auto">
                <i class="fas fa-headset"></i> Report Issue
            </a>
        </div>

        <!-- Footer Note -->
        <div class="mt-20">
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-red-400">
                Critical Alert: SYSTEM_RECIPE_ERROR_500
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('mousemove', (e) => {
            const cursor = document.getElementById('custom-cursor');
            cursor.style.left = e.clientX + 'px';
            cursor.style.top = e.clientY + 'px';
            
            if(Math.random() > 0.8) {
                createSmoke(e.clientX, e.clientY);
            }
        });

        function createSmoke(x, y) {
            const smoke = document.createElement('div');
            smoke.className = 'smoke-particle';
            const size = Math.random() * 20 + 10;
            smoke.style.width = size + 'px';
            smoke.style.height = size + 'px';
            smoke.style.left = x + 'px';
            smoke.style.top = y + 'px';
            document.body.appendChild(smoke);

            const animation = smoke.animate([
                { transform: 'translate(0, 0) scale(1)', opacity: 0.3 },
                { transform: `translate(${(Math.random()-0.5)*100}px, -100px) scale(3)`, opacity: 0 }
            ], {
                duration: 2000,
                easing: 'ease-out'
            });

            animation.onfinish = () => smoke.remove();
        }
    </script>
</body>
</html>


