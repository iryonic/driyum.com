<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle AJAX Newsletter Subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subscribe') {
    header('Content-Type: application/json');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }
    
    $result = subscribe_newsletter($email);
    echo json_encode($result);
    exit;
}

$maintenance = get_setting('maintenance_mode', 'off');
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

if ($maintenance !== 'on' && !$is_admin) {
    header("Location: " . get_url('index.php'));
    exit;
}

// Dynamic Settings with Defaults
$store_name = get_setting('store_name', 'DRIYUM');
$insta = get_setting('instagram_url', '#');
$wa = get_setting('whatsapp_number', '');

$headline = get_setting('maintenance_headline', 'System Update <br><span class="text-[#19DC7E]">In Progress.</span>');
$description = get_setting('maintenance_description', "We're performing scheduled maintenance to improve your experience.\nThings will be back and better than ever very soon.");
$progress = get_setting('maintenance_progress', '80');
$status_label = get_setting('maintenance_status_label', 'System Optimization');
$upgrade_mode_text = get_setting('maintenance_mode_text', 'Maintenance Mode');
$image_url = get_setting('maintenance_image', 'assets/images/hero.jpg');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance | <?php echo $store_name; ?></title>
    <meta name="description" content="We are currently undergoing scheduled maintenance. We will be back shortly.">
    <link rel="icon" type="image/png" href="<?php echo get_url('assets/images/logoicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo get_url('assets/images/logoicon.png'); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800;900&display=swap');
        
        :root {
            --primary: #19DC7E;
            --secondary: #0F172A;
            --accent: #FDFBF7;
        }

        body { 
            font-family: 'Outfit', sans-serif;
            background-color: var(--accent);
            cursor: default;
            overflow: hidden;
            min-height: 100vh;
            color: var(--secondary);
        }
        
        .fredoka { font-family: 'Fredoka', sans-serif; }

        /* Animated Background Blobs */
        .blob {
            position: absolute;
            background: radial-gradient(circle, rgba(25, 220, 126, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            z-index: -1;
            filter: blur(60px);
            animation: moveBlob 20s infinite alternate ease-in-out;
            opacity: 0.8;
        }
        
        .blob-1 { width: 60vw; height: 60vw; top: -10%; left: -10%; animation-delay: 0s; }
        .blob-2 { width: 50vw; height: 50vw; bottom: -10%; right: -10%; animation-delay: -5s; background: radial-gradient(circle, rgba(15, 23, 42, 0.05) 0%, rgba(255, 255, 255, 0) 70%); }

        @keyframes moveBlob {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(5%, 5%) scale(1.05); }
        }

        /* Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        /* Custom Scrollbar override for different browsers */
        .glass-card { -ms-overflow-style: none; scrollbar-width: none; }
        .glass-card::-webkit-scrollbar { display: none; }

        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #0F172A 30%, #19DC7E 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Wave Loader */
        .wave-container { display: flex; gap: 3px; align-items: center; }
        .wave-bar {
            width: 3px;
            height: 12px;
            background: #64748B;
            border-radius: 10px;
            animation: wave 1s infinite ease-in-out;
        }
        .wave-bar:nth-child(2) { animation-delay: 0.1s; height: 16px; }
        .wave-bar:nth-child(3) { animation-delay: 0.2s; height: 12px; }
        .wave-bar:nth-child(4) { animation-delay: 0.3s; }
        @keyframes wave {
            0%, 100% { transform: scaleY(1); opacity: 0.5; }
            50% { transform: scaleY(1.5); opacity: 1; }
        }

        /* Chunky Button */
        .btn-primary {
            background: var(--secondary);
            color: white;
            border-radius: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 2px solid transparent;
        }
        .btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
            border-color: rgba(255,255,255,0.2);
        }
        .btn-primary:active { transform: translateY(0) scale(0.98); }
        .btn-primary:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        /* Progress Bar */
        .progress-container {
            height: 10px;
            background: #F1F5F9;
            border-radius: 99px;
            overflow: hidden;
            position: relative;
        }
        .progress-bar {
            height: 100%;
            background: var(--primary);
            width: <?php echo $progress; ?>%;
            border-radius: 99px;
            position: relative;
            overflow: hidden;
            transition: width 1.5s cubic-bezier(0.22, 1, 0.36, 1);
        }
        /* Striped Pattern Overlay */
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: linear-gradient(
                45deg,
                rgba(255,255,255,0.3) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255,255,255,0.3) 50%,
                rgba(255,255,255,0.3) 75%,
                transparent 75%,
                transparent
            );
            background-size: 20px 20px;
            animation: moveStripes 1s linear infinite;
        }
        @keyframes moveStripes { from { background-position: 0 0; } to { background-position: 20px 20px; } }

        /* Floating Sticker Badges */
        .sticker {
            padding: 8px 14px;
            background: #0F172A;
            color: #19DC7E;
            font-weight: 900;
            border-radius: 100px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.15);
            z-index: 10;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.3s ease;
            position: absolute;
        }
        .sticker:hover { transform: scale(1.1) rotate(0) !important; z-index: 20; }
        
        /* Floating Elements Animation */
        .float-slow { animation: float 6s ease-in-out infinite; }
        .float-medium { animation: float 5s ease-in-out infinite reverse; }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(var(--rot)); }
            50% { transform: translateY(-15px) rotate(var(--rot)); }
        }

        /* Toast */
        #toast {
            visibility: hidden;
            background-color: #0F172A;
            color: #fff;
            text-align: center;
            border-radius: 12px;
            padding: 12px 24px;
            position: fixed;
            z-index: 100;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%) translateY(20px);
            font-size: 14px;
            font-weight: 600;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #toast.show { visibility: visible; opacity: 1; transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body class="flex items-center justify-center p-4 md:p-8">
    
    <!-- Background Elements -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <!-- Main Card -->
    <main class="w-full max-w-[1240px] glass-card rounded-[40px] md:rounded-[56px] p-8 md:p-14 relative flex flex-col justify-center min-h-[500px]">
        
        <!-- Decoration Stickers -->
        <div class="sticker -rotate-6 top-6 right-6 md:top-10 md:right-10" style="--rot: -6deg;">
            <i class="fas fa-sync-alt animate-spin"></i> System Update
        </div>
    

        <div class="grid lg:grid-cols-12 gap-12 lg:gap-20 items-center h-full">
            
            <!-- Left Content -->
            <div class="lg:col-span-7 flex flex-col justify-center text-center lg:text-left">
                
                <!-- Brand Header -->
                <div class="mb-10 flex flex-col md:flex-row items-center gap-6 justify-center lg:justify-start">
                    <img src="<?php echo get_url('assets/images/logo.png'); ?>" class="h-10 md:h-12 w-auto object-contain drop-shadow" alt="Logo">
                    <div class="h-8 w-px bg-slate-200 hidden md:block"></div>
                    <div class="flex items-center gap-3 bg-white/50 px-4 py-2 rounded-full border border-white/60 shadow-sm backdrop-blur-sm">
                        <div class="wave-container">
                            <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-[0.15em] text-slate-500"><?php echo htmlspecialchars($upgrade_mode_text); ?></span>
                    </div>
                </div>

                <!-- Headline -->
                <h1 class="text-5xl sm:text-6xl md:text-7xl lg:text-[5.5rem] font-black fredoka leading-[0.95] tracking-tight mb-6 gradient-text">
                    <?php echo $headline; ?>
                </h1>

                <!-- Description -->
                <p class="text-lg md:text-xl text-slate-600 font-medium leading-relaxed mb-10 max-w-2xl mx-auto lg:mx-0">
                    <?php echo nl2br(htmlspecialchars($description)); ?>
                </p>

                <!-- Status & Progress -->
                <div class="mb-12 max-w-md mx-auto lg:mx-0 w-full space-y-3">
                    <div class="flex justify-between items-end px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#19DC7E] animate-pulse"></span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-[#19DC7E]"><?php echo htmlspecialchars($status_label); ?></span>
                        </div>
                        <span class="text-xl font-black text-slate-900 fredoka"><?php echo $progress; ?>%</span>
                    </div>
                    <div class="progress-container shadow-inner">
                        <div class="progress-bar"></div>
                    </div>
                </div>

                <!-- Notify Input -->
                <div class="max-w-md mx-auto lg:mx-0 w-full">
                    <form id="notifyForm" class="relative group">
                        <div class="absolute inset-0 bg-[#19DC7E] rounded-[24px] blur opacity-20 group-hover:opacity-30 transition duration-500"></div>
                        <div class="relative bg-white p-2 rounded-[24px] border border-slate-100 shadow-xl flex items-center pr-2 focus-within:ring-2 focus-within:ring-[#19DC7E]/20 transition-all">
                            <div class="pl-4 text-slate-300"><i class="far fa-envelope text-lg"></i></div>
                            <input type="email" id="emailInput" placeholder="Get notified when we're back..." required 
                                   class="flex-1 bg-transparent border-none px-4 py-3 outline-none font-semibold text-slate-700 placeholder-slate-400 text-sm md:text-base">
                            <button type="submit" id="submitBtn" class="btn-primary py-3 px-6 text-xs md:text-sm whitespace-nowrap shadow-md">
                                Notify Me
                            </button>
                        </div>
                    </form>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-3 pl-4 text-center sm:text-left">
                        <i class="fas fa-lock text-[9px] mr-1"></i> No spam, promised.
                    </p>
                </div>

            </div>

            <!-- Right Content (Visuals) -->
            <div class="lg:col-span-5 hidden lg:block relative h-full">
                <!-- Rotated Card Container -->
                <div class="relative w-full aspect-[4/5] max-w-[400px] mx-auto float-slow" style="--rot: 3deg;">
                    <div class="absolute inset-0 bg-white rounded-[40px] shadow-2xl rotate-3 transform transition hover:rotate-0 duration-500 border-4 border-white overflow-hidden">
                        <img src="<?php echo get_url($image_url); ?>" class="w-full h-full object-cover opacity-95 group-hover:scale-110 transition duration-700" alt="Visual">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                        
                        <div class="absolute bottom-8 left-8 text-white">
                            <p class="text-[10px] font-bold uppercase tracking-widest opacity-80 mb-1">Coming Soon</p>
                            <p class="text-2xl font-black fredoka">New Experience</p>
                        </div>
                    </div>

                    <!-- Floater 1 -->
                    <div class="absolute -top-6 -right-6 w-24 h-24 bg-[#19DC7E] text-[#0F172A] rounded-full flex flex-col items-center justify-center border-4 border-white shadow-xl float-medium" style="--rot: 12deg;">
                        <span class="text-[9px] font-black uppercase opacity-60">Status</span>
                        <span class="text-lg font-black leading-none">SAFE</span>
                    </div>

                    <!-- Floater 2 -->
                    <div class="absolute -bottom-6 -left-6 bg-white py-3 px-6 rounded-2xl flex items-center gap-3 border border-slate-100 shadow-xl float-medium" style="--rot: -5deg; animation-delay: -1s;">
                        <i class="fas fa-shield-alt text-[#19DC7E] text-xl"></i>
                        <div class="leading-none">
                            <span class="block text-[8px] font-black uppercase text-slate-400">Security</span>
                            <span class="block text-sm font-black text-slate-800">Enhanced</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="mt-auto pt-10 border-t border-slate-200/50 grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div class="flex items-center gap-4 justify-center md:justify-start">
               <?php if($insta !== '#' || $wa !== ''): ?>
                    <div class="flex gap-2">
                        <?php if($insta !== '#'): ?><a href="<?php echo $insta; ?>" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-[#E1306C] hover:text-white transition-all"><i class="fab fa-instagram"></i></a><?php endif; ?>
                        <?php if($wa !== ''): ?><a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $wa); ?>" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-[#25D366] hover:text-white transition-all"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-center md:text-right">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    &copy; <?php echo date('Y'); ?> <?php echo $store_name; ?> &bull; 
                </p>
            </div>
        </div>

    </main>

    <!-- Toast Notification -->
    <div id="toast"><i class="fas fa-check-circle text-[#19DC7E]"></i> <span>Signed Up Successfully!</span></div>

    <script>
        // Smooth Cursor Movement for Blobs
        document.addEventListener('mousemove', (e) => {
            const x = (window.innerWidth - e.pageX * 2) / 100;
            const y = (window.innerHeight - e.pageY * 2) / 100;
            
            document.querySelector('.blob-1').style.transform = `translateX(${x}px) translateY(${y}px)`;
            document.querySelector('.blob-2').style.transform = `translateX(${x * -1}px) translateY(${y * -1}px)`;
        });

        // Form Logic
        const form = document.getElementById('notifyForm');
        const submitBtn = document.getElementById('submitBtn');
        const emailInput = document.getElementById('emailInput');
        const toast = document.getElementById('toast');

        function showToast(message, isError = false) {
            const toastContent = toast.querySelector('span');
            const toastIcon = toast.querySelector('i');
            
            toastContent.textContent = message;
            toastIcon.className = isError ? 'fas fa-exclamation-circle text-red-500' : 'fas fa-check-circle text-[#19DC7E]';
            
            toast.className = 'show';
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = emailInput.value;
            const originalBtnText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';

            try {
                const formData = new FormData();
                formData.append('action', 'subscribe');
                formData.append('email', email);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                // Handle non-JSON responses gracefully
                const text = await response.text();
                try {
                    const result = JSON.parse(text);
                    if (result.success) {
                        showToast(result.message);
                        emailInput.value = '';
                    } else {
                        showToast(result.message, true);
                    }
                } catch (e) {
                    console.error('Invalid JSON:', text);
                    showToast('Server error. Please try again.', true);
                }

            } catch (error) {
                showToast('Connection failed. Please check internet.', true);
                console.error(error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    </script>
</body>
</html>
