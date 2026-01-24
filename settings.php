<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . get_url('login.php'));
    exit;
}

$user = fetch_one("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = sanitize_input($_POST['name']);
        execute_query("UPDATE users SET name = ? WHERE id = ?", [$name, $_SESSION['user_id']]);
        $_SESSION['user_name'] = $name;
        set_flash_message("Profile updated successfully!");
        header("Location: " . get_url('settings.php'));
        exit;
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if (password_verify($current, $user['password'])) {
            if ($new === $confirm) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                execute_query("UPDATE users SET password = ? WHERE id = ?", [$hashed, $_SESSION['user_id']]);
                set_flash_message("Password changed successfully!");
                header("Location: " . get_url('settings.php'));
                exit;
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

$flash = get_flash_message();
$current_page = 'settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - DRIYUM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/chunky.css">
</head>
<body class="bg-[#FFFBEB] font-['Outfit']">

    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-6 py-12 min-h-screen">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- SIDEBAR -->
            <aside class="w-full lg:w-80 space-y-6">
                <div class="bg-white rounded-[32px] p-8 shadow-sm border border-gray-100 text-center">
                    <div class="w-24 h-24 bg-[#19DC7E] rounded-full mx-auto mb-6 flex items-center justify-center text-white text-3xl font-bold border-4 border-[#19DC7E]/20 shadow-xl">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <h2 class="text-2xl font-['Fredoka'] font-black text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p class="text-gray-400 font-bold uppercase tracking-widest text-[10px] mt-1">Driyum Member</p>
                </div>

                <div class="bg-white rounded-[32px] p-4 shadow-sm border border-gray-100 space-y-2">
                    <a href="<?php echo get_url('account'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-gray-500 hover:bg-gray-50 hover:text-black font-black transition-all group">
                        <i class="fas fa-th-large text-gray-300 group-hover:text-[#19DC7E] transition-colors w-5"></i> Dashboard
                    </a>
                    <a href="<?php echo get_url('settings'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl font-black transition-all group bg-[#19DC7E] text-black shadow-lg shadow-green-200">
                        <i class="fas fa-cog text-black transition-colors w-5"></i> Settings
                    </a>
                    <a href="<?php echo get_url('wishlist'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-gray-500 hover:bg-gray-50 hover:text-black font-black transition-all group">
                        <i class="fas fa-heart text-gray-300 group-hover:text-red-500 transition-colors w-5"></i> Wishlist
                    </a>
                    <div class="h-px bg-gray-50 my-4 mx-4"></div>
                    <a href="<?php echo get_url('logout'); ?>" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-red-400 hover:bg-red-50 font-black transition-all group">
                        <i class="fas fa-sign-out-alt text-red-200 group-hover:text-red-500 transition-colors w-5"></i> Logout
                    </a>
                </div>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="flex-1 space-y-8">
                <div class="mb-2">
                    <h1 class="text-5xl font-['Fredoka'] font-black text-gray-900">Settings <span class="text-[#19DC7E]">.</span></h1>
                    <p class="text-gray-400 font-bold uppercase tracking-widest text-xs mt-2">Manage your account and preferences</p>
                </div>

                <?php if($flash): ?>
                    <div class="bg-green-50 text-green-600 p-4 rounded-2xl border border-green-100 font-bold flex items-center gap-3 anim-up">
                        <i class="fas fa-check-circle"></i> <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="bg-red-50 text-red-500 p-4 rounded-2xl border border-red-100 font-bold flex items-center gap-3 anim-up">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="grid lg:grid-cols-2 gap-8 anim-up">
                    <!-- PROFILE FORM -->
                    <div class="bg-white rounded-[40px] p-10 border border-gray-100 shadow-sm transition-all hover:shadow-xl">
                        <h3 class="text-2xl font-['Fredoka'] font-black text-gray-900 mb-8 border-b border-gray-50 pb-4">Personal Info</h3>
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="update_profile">
                            <div>
                                <label class="block text-gray-400 font-black uppercase tracking-widest text-[10px] mb-3">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                            </div>
                            <div>
                                <label class="block text-gray-400 font-black uppercase tracking-widest text-[10px] mb-3">Email Address (Read Only)</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled class="input-chunky bg-gray-100 border-transparent w-full opacity-60">
                            </div>
                            <button type="submit" class="btn-chunky btn-primary px-8 py-3.5 shadow-xl hover:-translate-y-1 transition-all">Save Profile</button>
                        </form>
                    </div>

                    <!-- PASSWORD FORM -->
                    <div class="bg-white rounded-[40px] p-10 border border-gray-100 shadow-sm transition-all hover:shadow-xl">
                        <h3 class="text-2xl font-['Fredoka'] font-black text-gray-900 mb-8 border-b border-gray-50 pb-4">Security</h3>
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="change_password">
                            <div>
                                <label class="block text-gray-400 font-black uppercase tracking-widest text-[10px] mb-3">Current Password</label>
                                <input type="password" name="current_password" required placeholder="••••••••" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                            </div>
                            <div>
                                <label class="block text-gray-400 font-black uppercase tracking-widest text-[10px] mb-3">New Password</label>
                                <input type="password" name="new_password" required placeholder="••••••••" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                            </div>
                            <div>
                                <label class="block text-gray-400 font-black uppercase tracking-widest text-[10px] mb-3">Confirm New Password</label>
                                <input type="password" name="confirm_password" required placeholder="••••••••" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                            </div>
                            <button type="submit" class="btn-chunky bg-black text-white px-8 py-3.5 shadow-xl hover:bg-[#19DC7E] hover:text-black transition-all">Update Password</button>
                        </form>
                    </div>
                </div>
            </main>

        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
