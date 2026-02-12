<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . get_url('account.php'));
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $user = fetch_one("SELECT id, name FROM users WHERE email = ?", [$email]);
    
    if ($user) {
        $token = bin2hex(random_bytes(32));
        
        // Clear old tokens for this email
        execute_query("DELETE FROM password_resets WHERE email = ?", [$email]);
        // Insert new token
        execute_query("INSERT INTO password_resets (email, token) VALUES (?, ?)", [$email, $token]);
        
        // Generate Reset Link
        $reset_link = FULL_BASE_URL . "reset-password.php?token=" . $token;

        // For Live Mode: Send actual email
        $subject = "Reset Your Driyum Password";
        $email_content = "
            <div style='font-family: sans-serif; padding: 20px; color: #333;'>
                <h2 style='color: #24B25D;'>Hello, {$user['name']}!</h2>
                <p>You requested to reset your password for your Driyum account.</p>
                <p>Click the button below to set a new password. This link will expire in 1 hour.</p>
                <a href='{$reset_link}' style='display: inline-block; padding: 12px 24px; background-color: #000; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold;'>Reset Password</a>
                <p style='margin-top: 20px; font-size: 12px; color: #999;'>If you didn't request this, you can safely ignore this email.</p>
            </div>
        ";
        
        send_email($email, $subject, $email_content);
        
        $success = "If an account exists for this email, you will receive a reset link shortly.";
    } else {
        $success = "If an account exists for this email, you will receive a reset link shortly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Forgot Password';
    $page_description = 'Reset your DRIYUM account password easily.';
    include 'includes/head.php'; 
    ?>
</head>
<body class="bg-[#FFFEDC] min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-[3rem] shadow-2xl overflow-hidden p-8 md:p-12 anim-up">
        
        <div class="text-center mb-8">
            <a href="<?php echo get_url('index'); ?>" class="inline-block mb-6">
                <img src="assets/images/logo.svg" alt="Driyum Logo" class="w-24 mx-auto">
            </a>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Forgot Password?</h1>
            <p class="text-gray-500 font-sans mt-2">No worries, it happens to the best of us.</p>
        </div>

        <?php if($success): ?>
            <div class="bg-green-50 text-green-600 p-6 rounded-2xl mb-8 border border-green-100 text-center">
                <div class="text-4xl mb-3">📧</div>
                <p class="font-bold"><?php echo $success; ?></p>
                <a href="<?php echo get_url('login'); ?>" class="inline-block mt-4 text-[#24B25D] font-black uppercase tracking-widest text-xs hover:underline">Return to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">Enter your email</label>
                    <input type="email" name="email" required placeholder="hello@example.com" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                </div>

                <button type="submit" class="btn-chunky btn-primary w-full py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-1">
                    Send Reset Link <i class="fas fa-paper-plane ml-2 opacity-70"></i>
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-500 font-sans">
            Remembered it? <a href="<?php echo get_url('login'); ?>" class="text-black font-bold hover:text-[#24B25D] underline decoration-wavy">Log In</a>
        </div>
        
    </div>

</body>
</html>


