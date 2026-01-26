<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$maintenance = get_setting('maintenance_mode', 'off');
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

if ($maintenance !== 'on' && !$is_admin) {
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
            --secondary: #111827;
            --accent: #FFFDED;
        }

        body { 
            font-family: 'Outfit', sans-serif;
            background-color: var(--accent);
            cursor: default;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        .fredoka { font-family: 'Fredoka', sans-serif; }

        .blob {
            position: absolute;
            width: clamp(300px, 60vw, 800px);
            height: clamp(300px, 60vw, 800px);
            background: radial-gradient(circle, rgba(25, 220, 126, 0.2) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            z-index: -1;
            filter: blur(80px);
            animation: move 25s infinite alternate ease-in-out;
        }

        @keyframes move {
            from { transform: translate(-10%, -10%) scale(1); }
            to { transform: translate(10%, 10%) scale(1.1); }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 2px solid rgba(255, 255, 255, 0.8);
            box-shadow: 
                0 40px 100px rgba(0, 0, 0, 0.05),
                inset 0 0 20px rgba(255, 255, 255, 0.5);
        }

        .floating-item {
            animation: floating 6s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(5deg); }
        }

        .gradient-text {
            background: linear-gradient(135deg, #111827 0%, #19DC7E 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
            animation: wave 1.2s infinite ease-in-out;
        }
        .wave-bar:nth-child(2) { animation-delay: 0.15s; }
        .wave-bar:nth-child(3) { animation-delay: 0.3s; }
        .wave-bar:nth-child(4) { animation-delay: 0.45s; }
        @keyframes wave {
            0%, 100% { height: 10px; transform: scaleY(1); }
            50% { height: 30px; transform: scaleY(1.2); }
        }

        .btn-chunky {
            background: var(--secondary);
            color: white;
            padding: 16px 32px;
            border-radius: 20px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
        }
        .btn-chunky:hover {
            transform: scale(1.05) translateY(-3px);
            background: var(--primary);
            color: var(--secondary);
            box-shadow: 0 15px 30px rgba(25, 220, 126, 0.3);
        }

        .progress-track {
            height: 10px;
            background: rgba(0,0,0,0.05);
            border-radius: 50px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            position: absolute;
            top: 0; left: 0; bottom: 0;
            background: var(--primary);
            width: 85%;
            border-radius: 50px;
            animation: progressPulse 2s infinite alternate ease-in-out;
        }
        @keyframes progressPulse {
            from { opacity: 0.8; }
            to { opacity: 1; box-shadow: 0 0 15px var(--primary); }
        }

        .sticker {
            padding: 8px 16px;
            background: black;
            color: var(--primary);
            font-weight: 900;
            border-radius: 100px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            z-index: 20;
            pointer-events: none;
            white-space: nowrap;
        }

        @media (max-width: 640px) {
            .sticker { font-size: 8px; padding: 6px 12px; }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 md:p-8 lg:p-12 relative overflow-x-hidden bg-[#FFFDED]">
    
    <!-- Background Blobs -->
    <div class="blob top-[-10%] left-[-10%]"></div>
    <div class="blob bottom-[-10%] right-[-10%] select-none pointer-events-none" style="background: radial-gradient(circle, rgba(17, 24, 39, 0.05) 0%, rgba(255, 255, 255, 0) 70%);"></div>

    <!-- Main Content Wrapper -->
    <main class="w-full max-w-6xl relative z-50">
        
        <div class="glass-card rounded-[40px] md:rounded-[60px] lg:rounded-[80px] p-8 md:p-16 lg:p-20 relative">
            
            <!-- Dynamic Stickers (Responsive Placement) -->
            <div class="absolute top-6 right-8 md:top-12 md:right-12 -rotate-12 sticker">Restocking Freshness</div>
            <div class="absolute bottom-6 left-8 md:bottom-12 md:left-12 rotate-12 sticker">Hand-Picked Batch</div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
                
                <!-- Left Content Area -->
                <div class="lg:col-span-7 text-center lg:text-left">
                    
                    <!-- Top Branding & Status -->
                    <div class="mb-8 md:mb-12 flex flex-col md:flex-row items-center gap-6 justify-center lg:justify-start">
                        <img src="<?php echo get_url('assets/images/logo.png'); ?>" class="w-28 md:w-40 drop-shadow-2xl" alt="Logo">
                        <div class="h-8 w-px bg-gray-200 hidden md:block"></div>
                        <div class="flex items-center gap-3 bg-white/60 px-5 py-2.5 rounded-full border border-white shadow-sm">
                            <div class="wave-container">
                                <div class="wave-bar"></div>
                                <div class="wave-bar"></div>
                                <div class="wave-bar"></div>
                                <div class="wave-bar"></div>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-600">Upgrade Mode</span>
                        </div>
                    </div>

                    <!-- Main Headline -->
                    <h1 class="text-5xl sm:text-6xl md:text-7xl lg:text-8xl font-black fredoka leading-[1] tracking-tighter mb-6 md:mb-8 gradient-text">
                        Fresh Drop <br><span class="text-[#19DC7E]">Incoming.</span>
                    </h1>

                    <!-- Description -->
                    <p class="text-lg md:text-xl lg:text-2xl text-gray-500 font-medium leading-relaxed mb-10 md:mb-12 max-w-2xl mx-auto lg:mx-0">
                        We're currently refilling our vault with the newest harvest from the valley. 
                        Stocking up to bring you the crunchiest sun-dried experience yet.
                    </p>

                    <!-- Progress Indicator -->
                    <div class="mb-10 md:mb-12 max-w-md mx-auto lg:mx-0">
                        <div class="flex justify-between items-end mb-3 px-1">
                            <span class="text-[10px] font-black uppercase tracking-widest text-[#19DC7E]">Warehouse Sync</span>
                            <span class="text-xl font-black text-gray-900 fredoka">85% Done</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill"></div>
                        </div>
                    </div>

                    <!-- Notify Form -->
                    <div class="max-w-xl mx-auto lg:mx-0 mb-10 relative">
                        <form class="flex flex-col sm:flex-row gap-3 p-2 md:p-3 bg-white/70 rounded-[28px] md:rounded-[32px] border-2 border-white shadow-lg focus-within:border-[#19DC7E] transition-all" onsubmit="event.preventDefault(); alert('We will notify you!');">
                            <input type="email" placeholder="Your email for the drop alert..." required class="flex-1 bg-transparent border-none px-6 py-4 md:py-2 outline-none font-bold text-gray-800 placeholder-gray-400 text-sm md:text-base">
                            <button type="submit" class="btn-chunky text-xs md:text-sm whitespace-nowrap">
                                Notify Me <i class="fas fa-bolt ml-2 text-[#19DC7E]"></i>
                            </button>
                        </form>
                    </div>

                </div>

                <!-- Right Visuals (Hidden on smaller mobile, shown on Tablet+) -->
                <div class="lg:col-span-5 hidden lg:block relative">
                    <div class="relative bg-white rounded-[50px] lg:rounded-[70px] p-4 lg:p-6 shadow-2xl border-4 border-white rotate-6 transition-transform hover:rotate-2 duration-700">
                        <img src="<?php echo get_url('assets/images/hero.jpg'); ?>" class="w-full aspect-[4/5] object-cover rounded-[35px] lg:rounded-[50px] opacity-90 shadow-inner" alt="Snacks">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent rounded-[35px] lg:rounded-[50px] pointer-events-none"></div>
                        
                        <!-- Floating Counter -->
                        <div class="absolute -top-12 -left-12 w-32 h-32 md:w-36 md:h-36 lg:w-44 lg:h-44 bg-yellow-400 rounded-full flex flex-col items-center justify-center -rotate-12 border-4 border-white shadow-2xl floating-item">
                            <span class="text-black font-black text-center text-[10px] md:text-xs lg:text-sm leading-none fredoka uppercase tracking-tighter">New Drop in<br><span class="text-2xl md:text-3xl lg:text-4xl text-black">ASAP</span></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer Meta -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pt-10 lg:pt-16 mt-10 lg:mt-16 border-t border-gray-100 items-center">
                
                <div class="flex items-center gap-4 justify-center lg:justify-start">
                    <div class="w-12 h-12 bg-[#111827] text-[#19DC7E] rounded-2xl flex items-center justify-center shadow-lg"><i class="fas fa-box-open"></i></div>
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Inventory Status</p>
                        <p class="font-bold text-gray-900 text-sm">Quality Check Passed</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4 justify-center">
                     <?php if($insta !== '#'): ?>
                        <a href="<?php echo $insta; ?>" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-gray-400 hover:text-white hover:bg-[#E1306C] transition-all hover:-translate-y-1 shadow-sm"><i class="fab fa-instagram text-xl"></i></a>
                     <?php endif; ?>
                     <?php if($wa !== ''): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $wa); ?>" class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-gray-400 hover:text-white hover:bg-[#25D366] transition-all hover:-translate-y-1 shadow-sm"><i class="fab fa-whatsapp text-xl"></i></a>
                     <?php endif; ?>
                </div>

                <div class="text-center md:text-right">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-300">
                        &copy; <?php echo date('Y'); ?> <?php echo $store_name; ?> <br>
                        <a href="<?php echo get_url('admin/'); ?>" class="text-[#19DC7E] opacity-40 hover:opacity-100 transition-opacity">Staff Terminal</a>
                    </p>
                </div>

            </div>

        </div>

        <!-- Utility Links -->
        <div class="mt-8 text-center flex flex-col md:flex-row items-center justify-center gap-6">
             <a href="<?php echo get_url(''); ?>" class="text-[10px] font-black text-gray-400 hover:text-black uppercase tracking-[0.2em] transition-all"><i class="fas fa-arrow-left mr-2"></i> Mainland Return</a>
             <div class="w-1.5 h-1.5 rounded-full bg-gray-200 hidden md:block"></div>
             <a href="mailto:<?php echo get_setting('support_email', 'hello@driyum.com'); ?>" class="text-[10px] font-black text-gray-400 hover:text-black uppercase tracking-[0.2em] transition-all">Report Issue</a>
        </div>

    </main>

    <script>
        // Smooth Cursor Blob Drift
        document.addEventListener('mousemove', (e) => {
            const blobs = document.querySelectorAll('.blob');
            const { clientX, clientY } = e;
            const centerX = window.innerWidth / 2;
            const centerY = window.innerHeight / 2;
            
            blobs.forEach((blob, idx) => {
                const moveX = (clientX - centerX) / (25 + (idx * 10));
                const moveY = (clientY - centerY) / (25 + (idx * 10));
                blob.style.transform = `translate(${moveX}px, ${moveY}px) scale(${1 + (idx * 0.05)})`;
            });
        });
    </script>
</body>
</html>

</body>
</html>
