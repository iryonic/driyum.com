<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . get_url('account'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);
    
    $user = fetch_one("SELECT * FROM users WHERE email = ?", [$email]);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin'] = (int)($user['is_admin'] ?? 0);
        
        // Handle Remember Me
        if ($remember) {
            $token = base64_encode($user['id'] . ":" . bin2hex(random_bytes(16)));
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        }
        
        if ($user['is_admin'] == 1) {
            $redirect = get_url('admin/index.php');
        } else {
            $redirect = $_SESSION['redirect_url'] ?? get_url('account');
            // Security: Prevent redirecting non-admins to admin pages
            if (strpos($redirect, 'admin/') !== false) {
                $redirect = get_url('account');
            }
        }
        
        unset($_SESSION['redirect_url']);
        set_flash_message("Welcome back, " . $user['name'] . "!");
        header("Location: $redirect");
        exit;
    } else {
        set_flash_message($user ? "Invalid password." : "Account not found.", "error");
    }
}

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'logged_out':
            set_flash_message("You have been successfully logged out. See you soon!", "success");
            break;
        case 'login_required':
            set_flash_message("Please log in to continue.", "error");
            break;
    }
}

$flash = get_flash_message();
$error = $flash ? $flash['message'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php render_seo_tags('Login', 'Sign in to your DRIYUM account to access your wishlist, saved addresses, and order history.'); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo get_url('assets/css/chunky.css'); ?>">
</head>
<body class="bg-[#FFFBEB] min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-5xl bg-white rounded-[3rem] shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px] anim-up">
        
        <!-- LEFT: ARTWORK -->
        <div class="w-full md:w-1/2 bg-[#19DC7E] p-12 flex flex-col justify-between relative overflow-hidden">
            <a href="<?php echo get_url(''); ?>" class="text-white font-['Fredoka'] font-bold text-2xl relative z-10"><i class="fas fa-arrow-left mr-2"></i> Back to Shop</a>
            
            <div class="relative z-10">
                <h1 class="text-white mb-4 uppercase">Welcome Back!</h1>
                <p class="text-green-50 text-lg font-['Outfit']">Your daily dose of sun-dried happiness is waiting.</p>
            </div>
            
            <!-- Decor -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-white rounded-full opacity-10 blur-3xl"></div>
            <div class="absolute -bottom-10 -right-10 text-9xl opacity-20 rotate-12">🍎</div>
        </div>

        <!-- RIGHT: FORM -->
        <div class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center">
            
            <h2 class="mb-8 uppercase">Sign In</h2>
            
            <?php if($flash): ?>
                <div class="<?php echo $flash['type'] === 'error' ? 'bg-red-50 text-red-500' : 'bg-green-50 text-green-600'; ?> p-4 rounded-xl mb-6 font-bold flex items-center gap-2">
                    <i class="fas <?php echo $flash['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i> 
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">Email Address</label>
                    <input type="email" name="email" required placeholder="hello@example.com" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                </div>

                <div>
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-5 h-5 rounded text-[#19DC7E]">
                        <span class="text-sm font-bold text-gray-500">Remember Me</span>
                    </label>
                    <a href="<?php echo get_url('forgot-password'); ?>" class="text-sm font-bold text-[#19DC7E] hover:underline">Lost Password?</a>
                </div>

                <button type="submit" class="btn-chunky btn-primary w-full py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-1">
                    Log In <i class="fas fa-arrow-right ml-2 opacity-70"></i>
                </button>
            </form>

            <div class="mt-8 text-center text-gray-500 font-['Outfit']">
                New to the family? <a href="<?php echo get_url('register'); ?>" class="text-black font-bold hover:text-[#19DC7E] underline decoration-wavy">Create Account</a>
            </div>
            
        </div>
    </div>

</body>
</html>
