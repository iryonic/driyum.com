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

// Dynamic Settings
$store_name = get_setting('store_name', 'DRIYUM');
$headline = get_setting('maintenance_headline', 'We Are Upgrading!');
$description = get_setting('maintenance_description', "We're currently enhancing our platform to serve you better. We'll be back online shortly with an improved experience.");
$target_date = get_setting('maintenance_end_date', '');
$show_timer = get_setting('maintenance_show_timer', 'on');
$image_url = get_setting('maintenance_image', 'assets/images/hero.jpg');

// Visual Texts
$badge_text = get_setting('maintenance_mode_text', 'Maintenance Mode');
$sticker_text = get_setting('maintenance_sticker_text', 'Under Construction');
$overlay_text = get_setting('maintenance_overlay_text', 'Crafting Something Delicious.');
$countdown_label = get_setting('maintenance_countdown_label', 'WE WILL BE BACK SUBSCRIBE US TILL THEN');

?>
<!DOCTYPE html>
<html lang="en">
<head>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T3LPLX64');</script>
<!-- End Google Tag Manager -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($badge_text); ?> • <?php echo htmlspecialchars($store_name); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?php echo get_url('assets/images/logo.svg'); ?>">
    <link rel="apple-touch-icon" href="<?php echo get_url('assets/images/logo.svg'); ?>">
    <link rel="shortcut icon" href="<?php echo get_url('assets/images/logo.svg'); ?>" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Delius&family=Figtree:wght@300..900&family=Montserrat:wght@100..900&family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <style>
        :root {
            --font-heading: 'Montserrat', sans-serif;
            --font-body: 'Figtree', sans-serif;
            --font-cute: 'Delius', cursive;
        }
        body { 
            font-family: var(--font-body); 
            background-color: #FFFEDC;
            overflow-x: hidden;
        }
        .font-heading { font-family: var(--font-heading); }
        .font-crimson-pro { font-family: var(--font-heading); } /* Alias for legacy usage */
        .font-cute { font-family: var(--font-cute); }
        
        /* Animations */
        @keyframes anim-up {
            from { transform: translateY(30px); opacity: 0; filter: blur(5px); }
            to { transform: translateY(0); opacity: 1; filter: blur(0); }
        }
        .anim-up { animation: anim-up 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
        .animate-up { animation: anim-up 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; } /* Alias */
        
        @keyframes float-slow {
            0%, 100% { transform: translateY(0) rotate(5deg); }
            50% { transform: translateY(-20px) rotate(-5deg); }
        }
        .animate-float { animation: float-slow 6s ease-in-out infinite; }
        
        @keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .animate-spin-slow { animation: spin-slow 12s linear infinite; }

        /* Chunky Button Style from Index */
        .btn-chunky {
            border: 3px solid transparent;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .btn-chunky:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .btn-chunky:active {
            transform: translateY(0) scale(0.95);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#24B25D',
                        accent: '#FFFEDC'
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen flex  flex-col items-center justify-center p-4 selection:bg-[#24B25D] selection:text-black relative">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->


    <!-- FLOATING FRUITS BACKGROUND (Match Index) -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none select-none z-0">
        <!-- Top Left -->
        <div class="absolute top-10 left-[10%] text-6xl opacity-20 animate-bounce duration-[3000ms] rotate-12 drop-shadow-lg hidden sm:block">🍎</div>
        <div class="absolute top-40 left-[5%] text-4xl opacity-15 animate-ping duration-[4000ms] hidden sm:block">🍃</div>
        <div class="absolute top-60 left-[20%] text-5xl opacity-20 animate-bounce duration-[3500ms] -rotate-6 hidden lg:block">🍓</div>
        
        <!-- Bottom Left -->
        <div class="absolute bottom-20 left-[15%] text-7xl opacity-20 animate-bounce duration-[4000ms] -rotate-12 blur-[1px] hidden sm:block">🍑</div>
        <div class="absolute bottom-40 left-[25%] text-4xl opacity-15 animate-spin-slow duration-[12s] hidden lg:block">🥝</div>
        
        <!-- Top Right -->
        <div class="absolute top-20 right-[10%] text-5xl opacity-20 animate-bounce duration-[3500ms] rotate-[20deg] hidden sm:block">🌰</div>
        <div class="absolute top-1/2 right-[5%] text-4xl opacity-10 animate-spin-slow duration-[10s] hidden sm:block">🍇</div>
        <div class="absolute top-32 right-[20%] text-6xl opacity-20 animate-bounce duration-[4200ms] rotate-12 hidden lg:block">🥭</div>
        
        <!-- Bottom Right -->
        <div class="absolute bottom-32 right-[15%] text-6xl opacity-20 animate-bounce duration-[4500ms] -rotate-[15deg] blur-[1px] hidden sm:block">🍒</div>
        <div class="absolute bottom-10 right-[25%] text-5xl opacity-15 animate-bounce duration-[3800ms] rotate-6 hidden sm:block">🍊</div>

        <!-- Blobs -->
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-yellow-300 rounded-full blur-[100px] opacity-30 animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[500px] h-[500px] bg-[#24B25D] rounded-full blur-[120px] opacity-20 animate-pulse"></div>
    </div>

    <!-- Main Container (Centered Layout like Index) -->
    <main class="w-full max-w-5xl px-6 py-12 relative z-10 flex flex-col items-center text-center space-y-10 md:space-y-12">
        
        <!-- Header Content -->
        <div class="flex flex-col items-center space-y-8 animate-up">
            
            <!-- Badge -->
            <div class="inline-flex items-center gap-3 bg-white/60 backdrop-blur-sm border border-white/60 rounded-full px-6 py-2.5 shadow-lg shadow-yellow-900/5 hover:scale-105 transition duration-300">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#24B25D] opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-[#24B25D]"></span>
                </span>
                <span class="text-xs font-black font-crimson-pro uppercase tracking-[0.2em] text-gray-800"><?php echo htmlspecialchars($badge_text); ?></span>
            </div>

            <!-- Headline -->
            <div class="space-y-6 max-w-4xl">
                <h1 class="text-5xl sm:text-7xl md:text-8xl font-crimson-pro font-black leading-[0.9] text-gray-900 tracking-tight drop-shadow-sm">
                    <?php 
                    $parts = explode(' ', $headline);
                    $last = array_pop($parts);
                    echo implode(' ', $parts); 
                    ?> 
                    <span class="relative inline-block text-[#24B25D]">
                        <?php echo $last; ?>
                        <!-- Squiggle -->
                        <svg class="absolute w-full h-4 -bottom-1 left-0 text-[#24B25D] opacity-40 hidden md:block" viewBox="0 0 100 10" preserveAspectRatio="none">
                           <path d="M0 5 Q 50 15 100 5" stroke="currentColor" stroke-width="8" fill="none" class="animate-pulse"/>
                        </svg>
                    </span>
                </h1>
                <p class="text-lg md:text-2xl text-gray-600 font-medium leading-relaxed max-w-2xl mx-auto">
                    <?php echo nl2br(htmlspecialchars($description)); ?>
                </p>
            </div>

            <!-- Notify Form (Centered Pill) -->
            <div class="w-full max-w-md relative group z-20">
                <div class="absolute -inset-2 bg-gradient-to-r from-[#24B25D] to-blue-400 rounded-full blur opacity-20 group-hover:opacity-30 transition duration-500"></div>
                <form id="notifyForm" class="relative flex p-2 bg-white rounded-full shadow-xl ring-4 ring-transparent hover:ring-[#24B25D]/10 transition-all">
                    <input type="email" id="emailInput" placeholder="Enter email to Subscribe" 
                           class="flex-1 bg-transparent border-none outline-none pl-6 py-3 text-gray-900 placeholder-gray-400 font-bold rounded-full text-sm sm:text-base w-full min-w-0" required>
                    <button type="submit" id="submitBtn" class="shrink-0 bg-black text-white px-6 sm:px-8 py-3 rounded-full font-black uppercase text-xs tracking-widest hover:bg-[#24B25D] hover:text-black transition-colors shadow-md">
                        Subscribe
                    </button>
                </form>
                <div id="formMsg" class="mt-3 text-sm font-bold hidden"></div>
            </div>

        </div>

        <!-- Middle Section: Timer & Socials -->
         <div class="flex flex-col md:flex-row gap-8 md:gap-16 items-center justify-center w-full">
            
            <?php if (!empty($target_date) && $show_timer === 'on'): ?>
            <div class="flex flex-col items-center space-y-3">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400"><?php echo htmlspecialchars($countdown_label); ?></span>
                <div id="countdown" class="flex gap-3">
                    <div class="bg-white/80 p-3 rounded-2xl min-w-[70px] border border-gray-100 shadow-sm backdrop-blur-sm">
                        <div id="days" class="text-2xl font-black font-crimson-pro text-gray-900 leading-none">00</div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase">Days</div>
                    </div>
                    <div class="text-2xl font-black text-gray-300 pt-2">:</div>
                    <div class="bg-white/80 p-3 rounded-2xl min-w-[70px] border border-gray-100 shadow-sm backdrop-blur-sm">
                        <div id="hours" class="text-2xl font-black font-crimson-pro text-gray-900 leading-none">00</div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase">Hrs</div>
                    </div>
                    <div class="text-2xl font-black text-gray-300 pt-2">:</div>
                    <div class="bg-white/80 p-3 rounded-2xl min-w-[70px] border border-gray-100 shadow-sm backdrop-blur-sm">
                        <div id="minutes" class="text-2xl font-black font-crimson-pro text-gray-900 leading-none">00</div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase">Mins</div>
                    </div>
                    <div class="text-2xl font-black text-gray-300 pt-2">:</div>
                    <div class="bg-black p-3 rounded-2xl min-w-[70px] shadow-lg shadow-green-400/20">
                        <div id="seconds" class="text-2xl font-black font-crimson-pro text-[#24B25D] leading-none">00</div>
                        <div class="text-[9px] font-bold text-white/60 uppercase">Secs</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Socials -->
            <div class="flex flex-col items-center space-y-3">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Follow Us</span>
                <div class="flex gap-3">
                    <?php 
                    $socials = [
                        'instagram' => ['url' => get_setting('instagram_url', '#'), 'icon' => 'fab fa-instagram'],
                        'facebook' => ['url' => get_setting('facebook_url', '#'), 'icon' => 'fab fa-facebook-f'],
                        'twitter' => ['url' => get_setting('twitter_url', '#'), 'icon' => 'fab fa-twitter'],
                        'whatsapp' => ['url' => 'https://wa.me/' . preg_replace('/[^0-9]/', '', get_setting('whatsapp_number', '')), 'icon' => 'fab fa-whatsapp']
                    ];
                    foreach($socials as $key => $s): 
                        if($s['url'] !== '#' && $s['url'] !== '' && $s['url'] !== 'https://wa.me/'):
                    ?>
                        <a href="<?php echo htmlspecialchars($s['url']); ?>" class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-800 shadow-sm hover:scale-110 hover:bg-[#24B25D] hover:text-white transition-all duration-300 border border-gray-100">
                            <i class="<?php echo $s['icon']; ?> text-sm"></i>
                        </a>
                    <?php endif; endforeach; ?>
                </div>
            </div>

         </div>

        <!-- Featured Image (Centered Hero) -->
        <div class="w-full max-w-3xl mt-12 relative group perspective-[1000px]">
            <div class="relative bg-white p-2 rounded-[2.5rem] shadow-2xl shadow-yellow-900/10 transform transition-all hover:scale-[1.01] duration-500">
                <div class="aspect-[16/9] overflow-hidden rounded-[2rem] relative bg-gray-100">
                    <img src="<?php echo get_url($image_url); ?>" class="w-full h-full object-cover">
                    
                    <!-- Overlay Text (Subtle) -->
                    <div class="absolute inset-0 flex items-center justify-center bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                         <span class="bg-white/90 backdrop-blur text-black text-xs font-black px-6 py-2 rounded-full uppercase tracking-widest shadow-xl">
                            <?php echo htmlspecialchars($overlay_text); ?>
                        </span>
                    </div>
                </div>

                <!-- Sticker -->
                <div class="absolute -top-6 -right-6 bg-[#FFD700] text-black font-black font-crimson-pro px-6 py-3 rounded-full shadow-xl transform rotate-12 border-4 border-white animate-float hidden md:block">
                    <span class="text-sm uppercase tracking-wide flex items-center gap-2">
                        🚧 <?php echo htmlspecialchars($sticker_text); ?>
                    </span>
                </div>
            </div>
        </div>

    </main>
    
    <!-- Footer -->
    <div class="relative w-full text-center py-8 z-50">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] opacity-40">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($store_name); ?>
        </p>
        <a href="<?php echo get_url('login'); ?>" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] opacity-40 inline-block hover:opacity-100 transition-opacity pointer-events-auto relative z-50">
           Login
        </a>
    </div>

    <?php if (!empty($target_date) && $show_timer === 'on'): ?>
    <script>
        const targetDate = new Date("<?php echo $target_date; ?>").getTime();
        let isExpired = false;
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = targetDate - now;

            const countdownEl = document.getElementById("countdown");
            if (!countdownEl) return;

            if (distance < 0) {
                if (!isExpired) {
                    countdownEl.innerHTML = "<div class='bg-[#24B25D] text-white font-bold px-8 py-4 rounded-[2rem] w-full text-center shadow-2xl shadow-green-500/20 anim-up'><i class='fas fa-rocket mr-2'></i> Preparing Launch...</div>";
                    isExpired = true;
                }
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            const dEl = document.getElementById("days");
            const hEl = document.getElementById("hours");
            const mEl = document.getElementById("minutes");
            const sEl = document.getElementById("seconds");

            if(dEl) dEl.innerText = days.toString().padStart(2, '0');
            if(hEl) hEl.innerText = hours.toString().padStart(2, '0');
            if(mEl) mEl.innerText = minutes.toString().padStart(2, '0');
            if(sEl) sEl.innerText = seconds.toString().padStart(2, '0');
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>
    <?php endif; ?>

    <script>
        document.getElementById('notifyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const input = document.getElementById('emailInput');
            const msg = document.getElementById('formMsg');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const formData = new FormData();
                formData.append('action', 'subscribe');
                formData.append('email', input.value);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                msg.classList.remove('hidden', 'text-red-500', 'text-[#24B25D]');
                msg.classList.add(data.success ? 'text-[#24B25D]' : 'text-red-500');
                msg.innerHTML = `<i class="${data.success ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'} mr-1.5"></i> ${data.message}`;
                msg.classList.remove('hidden');

                if(data.success) input.value = '';

            } catch (err) {
                console.error(err);
                msg.classList.remove('hidden', 'text-[#24B25D]');
                msg.classList.add('text-red-500');
                msg.innerText = 'Connection error. Please try again.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    </script>
</body>
</html>


