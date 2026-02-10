<?php include 'includes/header.php'; ?>
<?php
$conn = get_db_connection();
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Name, Email and Password are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $existing = fetch_one("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $error = "A user with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, phone, password, is_admin, is_active, created_at) VALUES (?, ?, ?, ?, 1, 1, NOW())";
            if (execute_query($sql, [$name, $email, $phone, $hashed])) {
                // Automatically subscribe to newsletter
                subscribe_newsletter($email);
                $success = "New administrator account created successfully!";
            } else {
                $error = "Failed to create administrator account.";
            }
        }
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-12 flex items-center justify-between anim-up">
        <div>
            <a href="users.php" class="text-[#19DC7E] font-black text-xs uppercase tracking-widest flex items-center gap-2 mb-4 hover:translate-x-[-5px] transition-transform">
                <i class="fas fa-arrow-left"></i> Back to Community
            </a>
            <h1 class="text-4xl font-black text-gray-900 crimson-pro mb-2">Summon Authority.</h1>
            <p class="text-gray-500 font-medium font-['Inter'] italic">Expanding the guard of Driyum.</p>
        </div>
        <div class="w-20 h-20 bg-black text-[#19DC7E] rounded-[30px] flex items-center justify-center text-3xl shadow-2xl">
            <i class="fas fa-crown"></i>
        </div>
    </div>

    <?php if($success): ?>
        <div class="mb-8 p-6 bg-[#19DC7E] text-black rounded-[30px] shadow-lg shadow-green-500/20 font-black text-sm anim-up flex items-center gap-4">
            <div class="w-10 h-10 bg-black/10 rounded-full flex items-center justify-center"><i class="fas fa-check"></i></div>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="mb-8 p-6 bg-red-500 text-white rounded-[30px] shadow-lg shadow-red-500/20 font-black text-sm anim-up flex items-center gap-4">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center"><i class="fas fa-times"></i></div>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-[50px] p-10 md:p-16 shadow-2xl border border-gray-100 anim-up relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-gray-50 rounded-full -mr-32 -mt-32 -z-0"></div>
        
        <div class="relative z-10 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 ml-4">Full Name</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="text" name="name" required placeholder="John Admin" class="w-full bg-gray-50 border-3 border-transparent rounded-[24px] pl-14 pr-8 py-5 outline-none focus:border-[#19DC7E] focus:bg-white transition-all font-bold text-gray-800">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 ml-4">Direct Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="email" name="email" required placeholder="admin@driyum.com" class="w-full bg-gray-50 border-3 border-transparent rounded-[24px] pl-14 pr-8 py-5 outline-none focus:border-[#19DC7E] focus:bg-white transition-all font-bold text-gray-800">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 ml-4">Phone (Optional)</label>
                    <div class="relative">
                        <i class="fas fa-phone absolute left-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="text" name="phone" placeholder="+91 00000 00000" class="w-full bg-gray-50 border-3 border-transparent rounded-[24px] pl-14 pr-8 py-5 outline-none focus:border-[#19DC7E] focus:bg-white transition-all font-bold text-gray-800">
                    </div>
                </div>
                <div class="bg-black/5 rounded-[30px] p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-black text-white rounded-2xl flex items-center justify-center text-xl shadow-lg">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-xs uppercase tracking-widest text-gray-900">Security Note</h4>
                        <p class="text-[10px] font-bold text-gray-400">All administrative actions are logged in the vault.</p>
                    </div>
                </div>
            </div>

            <div class="h-px bg-gray-100 my-8"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 ml-4">Master Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full bg-gray-50 border-3 border-transparent rounded-[24px] pl-14 pr-8 py-5 outline-none focus:border-[#19DC7E] focus:bg-white transition-all font-bold text-gray-800">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 ml-4">Confirm Passcode</label>
                    <div class="relative">
                        <i class="fas fa-check-double absolute left-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full bg-gray-50 border-3 border-transparent rounded-[24px] pl-14 pr-8 py-5 outline-none focus:border-[#19DC7E] focus:bg-white transition-all font-bold text-gray-800">
                    </div>
                </div>
            </div>

            <div class="pt-8">
                <button type="submit" name="create_admin" class="btn-chunky w-full bg-[#19DC7E] text-black py-6 rounded-[30px] font-black uppercase tracking-[0.2em] shadow-2xl shadow-green-500/20 hover:scale-[1.02] active:scale-95 transition-all text-sm">
                    CREATE ADMIN <i class="fas fa-sparkles ml-3"></i>
                </button>
            </div>
        </div>
    </form>
</div>

</body>
</html>


