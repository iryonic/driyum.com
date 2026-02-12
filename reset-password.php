<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verify Token
$reset_request = fetch_one("SELECT *, (created_at < NOW() - INTERVAL 1 HOUR) as is_expired FROM password_resets WHERE token = ?", [$token]);

if (!$reset_request) {
    $error = "This reset link is invalid or has already been used.";
} elseif ($reset_request['is_expired']) {
    $error = "This reset link has expired. Please request a new one.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $email = $reset_request['email'];
        
        // Update User Password
        execute_query("UPDATE users SET password = ? WHERE email = ?", [$hashed, $email]);
        
        // Delete Token
        execute_query("DELETE FROM password_resets WHERE email = ?", [$email]);
        
        set_flash_message("Your password has been reset successfully! You can now log in.", "success");
        header("Location: " . get_url('login.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Reset Password';
    include 'includes/head.php'; 
    ?>
</head>
<body class="bg-[#FFFEDC] min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-[3rem] shadow-2xl overflow-hidden p-8 md:p-12 anim-up">
        
        <div class="text-center mb-8">
            <a href="<?php echo get_url('index'); ?>" class="inline-block mb-6">
                <img src="assets/images/logo.svg" alt="Driyum Logo" class="w-24 mx-auto">
            </a>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Set New Password</h1>
            <p class="text-gray-500 font-sans mt-2">Almost there! Choose a strong password.</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 text-red-500 p-6 rounded-2xl mb-8 border border-red-100 text-center">
                <div class="text-4xl mb-3">⚠️</div>
                <p class="font-bold"><?php echo $error; ?></p>
                <a href="<?php echo get_url('forgot-password'); ?>" class="inline-block mt-4 text-[#24B25D] font-black uppercase tracking-widest text-xs hover:underline">Request New Link</a>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">New Password</label>
                    <div class="relative group">
                        <input type="password" name="password" id="new_password" required placeholder="••••••••" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full pr-14" oninput="validatePassword(this.value)">
                        <button type="button" onclick="togglePasswordVisibility('new_password', this)" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-gray-300 hover:text-black transition-colors focus:outline-none">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>

                    <!-- Password Validator UI -->
                    <div id="password-validator" class="mt-4 space-y-3 hidden anim-up">
                        <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div id="strength-bar" class="h-full w-0 transition-all duration-500 bg-red-500"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-y-2 gap-x-4">
                            <div id="crit-length" class="text-[9px] font-black text-gray-400 uppercase flex items-center gap-2">
                                <i class="fas fa-circle text-[6px]"></i> 8+ Characters
                            </div>
                            <div id="crit-upper" class="text-[9px] font-black text-gray-400 uppercase flex items-center gap-2">
                                <i class="fas fa-circle text-[6px]"></i> Uppercase
                            </div>
                            <div id="crit-number" class="text-[9px] font-black text-gray-400 uppercase flex items-center gap-2">
                                <i class="fas fa-circle text-[6px]"></i> One Number
                            </div>
                            <div id="crit-special" class="text-[9px] font-black text-gray-400 uppercase flex items-center gap-2">
                                <i class="fas fa-circle text-[6px]"></i> Special Char
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">Confirm Password</label>
                    <div class="relative group">
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="••••••••" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full pr-14">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-gray-300 hover:text-black transition-colors focus:outline-none">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-chunky btn-primary w-full py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-1">
                    Reset Password <i class="fas fa-lock ml-2 opacity-70"></i>
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-500 font-sans">
            Back to <a href="<?php echo get_url('login'); ?>" class="text-black font-bold hover:text-[#24B25D] underline decoration-wavy">Log In</a>
        </div>
        
    </div>

</body>
</html>


