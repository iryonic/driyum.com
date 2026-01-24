<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$maintenance = get_setting('maintenance_mode', 'off');
if ($maintenance !== 'on') {
    header("Location: " . get_url('index.php'));
    exit;
}

$store_name = get_setting('store_name', 'DRIYUM');
$insta = get_setting('instagram_url', '#');
$wa = get_setting('whatsapp_number', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restocking Freshness | <?php echo $store_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800;900&display=swap');
        
        :root {
            --primary: #19DC7E;
            --accent: #FFFDED;
        }

        body { 
            font-family: 'Outfit', sans-serif;
            background-color: var(--accent);
            overflow: hidden;
        }
        
        .fredoka { font-family: 'Fredoka', sans-serif; }

        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(25, 220, 126, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            z-index: -1;
            filter: blur(80px);
            animation: move 20s infinite alternate;
        }

        @keyframes move {
            from { transform: translate(-10%, -10%); }
            to { transform: translate(10%, 10%); }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.05);
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .gradient-text {
            background: linear-gradient(135deg, #111827 0%, #19DC7E 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .p-glow {
            box-shadow: 0 0 30px rgba(25, 220, 126, 0.3);
        }

        /* Custom Wave Loader */
        .wave-container {
            display: flex;
            gap: 4px;
        }
        .wave-bar {
            width: 4px;
            height: 20px;
            background: var(--primary);
            border-radius: 10px;
            animation: wave 1s infinite ease-in-out;
        }
        .wave-bar:nth-child(2) { animation-delay: 0.1s; }
        .wave-bar:nth-child(3) { animation-delay: 0.2s; }
        .wave-bar:nth-child(4) { animation-delay: 0.3s; }
        @keyframes wave {
            0%, 100% { height: 10px; }
            50% { height: 30px; }
        }

        .btn-premium {
            background: #111827;
            color: white;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .btn-premium:hover {
            transform: scale(1.05) translateY(-5px);
            background: var(--primary);
            color: #111827;
            box-shadow: 0 20px 40px rgba(25, 220, 126, 0.3);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-6 relative">
    
    <!-- Animated Background Blobs -->
    <div class="blob top-[-10%] left-[-10%]"></div>
    <div class="blob bottom-[-10%] right-[-10%] select-none pointer-events-none" style="background: radial-gradient(circle, rgba(17, 24, 39, 0.05) 0%, rgba(255, 255, 255, 0) 70%);"></div>

    <main class="max-w-4xl w-full grid grid-cols-1 lg:grid-cols-2 gap-12 items-center relative z-10">
        
        <!-- Illustration / Visual Left -->
        <div class="hidden lg:flex flex-col items-center justify-center p-12 relative">
            <div class="absolute inset-0 bg-white/40 rounded-[60px] blur-3xl -z-10"></div>
            
            <!-- Logo Floating -->
            <img src="assets/images/logo.png" class="w-64 floating mb-12 drop-shadow-2xl" alt="Restocking">
            
            <!-- Status Indicators -->
            <div class="space-y-4 w-full">
                <div class="bg-white/80 backdrop-blur-md p-6 rounded-[32px] border border-white shadow-xl flex items-center gap-4 anim-up" style="animation-delay: 0.2s">
                    <div class="w-12 h-12 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center text-xl">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Harvesting</p>
                        <p class="font-bold text-gray-900">Premium Sun-Dried Picks</p>
                    </div>
                </div>
                <div class="bg-white/80 backdrop-blur-md p-6 rounded-[32px] border border-white shadow-xl flex items-center gap-4 translate-x-12 anim-up" style="animation-delay: 0.4s">
                    <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-xl">
                        <i class="fas fa-truck-loading"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Restocking</p>
                        <p class="font-bold text-gray-900">Entering Warehouse</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Right -->
        <div class="glass-card rounded-[60px] p-10 md:p-16 flex flex-col justify-center text-center lg:text-left">
            
            <!-- Mobile Logo -->
            <img src="assets/images/logo.png" class="w-24 mx-auto lg:hidden mb-8" alt="Logo">

            <div class="mb-10 inline-flex items-center gap-4 bg-white/80 px-5 py-2 rounded-full border border-white shadow-sm self-center lg:self-start">
                <div class="wave-container">
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-600">Restoring Freshness</span>
            </div>

            <h1 class="text-6xl md:text-7xl font-black gradient-text fredoka leading-tight mb-8 tracking-tighter">
                Fresh Batch <br><span class="text-[#19DC7E]">Incoming.</span>
            </h1>

            <p class="text-xl text-gray-500 font-medium leading-relaxed mb-12">
                We're currently restocking our mountain harvest to ensure you get only the most distinct, premium sun-dried snacks.
            </p>

            <!-- Notification Form -->
            <div class="space-y-6 mb-12">
                <p class="text-xs font-black uppercase tracking-widest text-gray-400">Want to be the first to know?</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="email" placeholder="Your best email..." class="flex-1 bg-white border-2 border-transparent focus:border-[#19DC7E] rounded-[24px] px-8 py-5 outline-none transition-all font-bold shadow-inner">
                    <button class="btn-premium px-10 py-5 rounded-[24px] font-black uppercase tracking-widest text-xs whitespace-nowrap">
                        Notify Me
                    </button>
                </div>
            </div>

            <!-- Footer & Links -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 pt-10 border-t border-gray-100/50">
                <div class="flex gap-4">
                    <?php if($insta !== '#'): ?>
                    <a href="<?php echo $insta; ?>" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-gray-400 hover:text-pink-500 hover:shadow-lg transition-all border border-gray-50">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                    <?php endif; ?>
                    <?php if($wa !== ''): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $wa); ?>" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-gray-400 hover:text-[#25D366] hover:shadow-lg transition-all border border-gray-50">
                        <i class="fab fa-whatsapp text-xl"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-300">
                    &copy; <?php echo date('Y'); ?> <?php echo $store_name; ?> . Master Batch
                </p>
            </div>
        </div>
    </main>

    <!-- Floating Graphic Elements -->
    <div class="absolute top-[10%] right-[10%] opacity-10 floating hidden md:block">
        <i class="fas fa-leaf text-[120px] text-[#19DC7E]"></i>
    </div>
    <div class="absolute bottom-[5%] left-[5%] opacity-5 floating hidden md:block" style="animation-delay: 1.5s">
        <i class="fas fa-box-open text-[150px] text-[#111827]"></i>
    </div>

</body>
</html>
