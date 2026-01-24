<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verify Token
$reset_request = fetch_one("SELECT * FROM password_resets WHERE token = ?", [$token]);

if (!$reset_request) {
    $error = "This reset link is invalid or has expired.";
} else {
    // Check if token is older than 1 hour
    $created_at = strtotime($reset_request['created_at']);
    if (time() - $created_at > 3600) {
        execute_query("DELETE FROM password_resets WHERE token = ?", [$token]);
        $error = "This reset link has expired. Please request a new one.";
    }
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
<body class="bg-[#FFFBEB] min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-[3rem] shadow-2xl overflow-hidden p-8 md:p-12 anim-up">
        
        <div class="text-center mb-8">
            <a href="<?php echo get_url('index'); ?>" class="inline-block mb-6">
                <img src="assets/images/logo.png" alt="Driyum Logo" class="w-24 mx-auto">
            </a>
            <h1 class="text-3xl font-['Fredoka'] font-bold text-gray-900">Set New Password</h1>
            <p class="text-gray-500 font-['Outfit'] mt-2">Almost there! Choose a strong password.</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 text-red-500 p-6 rounded-2xl mb-8 border border-red-100 text-center">
                <div class="text-4xl mb-3">⚠️</div>
                <p class="font-bold"><?php echo $error; ?></p>
                <a href="<?php echo get_url('forgot-password'); ?>" class="inline-block mt-4 text-[#19DC7E] font-black uppercase tracking-widest text-xs hover:underline">Request New Link</a>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">New Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                </div>

                <div>
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                </div>

                <button type="submit" class="btn-chunky btn-primary w-full py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-1">
                    Reset Password <i class="fas fa-lock ml-2 opacity-70"></i>
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-500 font-['Outfit']">
            Back to <a href="<?php echo get_url('login'); ?>" class="text-black font-bold hover:text-[#19DC7E] underline decoration-wavy">Log In</a>
        </div>
        
    </div>

</body>
</html>
