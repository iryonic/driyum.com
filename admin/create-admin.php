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
        $error = "Name, Email and Password are required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $existing = fetch_one("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $error = "An account with this email address already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, phone, password, is_admin, is_active, created_at) VALUES (?, ?, ?, ?, 1, 1, NOW())";
            if (execute_query($sql, [$name, $email, $phone, $hashed])) {
                subscribe_newsletter($email);
                $success = "Administrator account created successfully!";
            } else {
                $error = "Failed to create administrator account.";
            }
        }
    }
}
?>

<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="users.php" class="text-xs text-slate-500 hover:text-slate-800 transition-colors flex items-center gap-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Users
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-xs text-slate-500">Create</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">New Administrator</h1>
            <p class="text-sm text-slate-500 mt-0.5">Provision an authorized team account with full admin panel access.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="p-3.5 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <a href="users.php" class="text-xs font-semibold underline text-emerald-700 hover:text-emerald-900">View Users</a>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-3.5 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-rose-600"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times text-xs"></i></button>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="admin-card p-6">
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Aijaz Ahmad" class="admin-input text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address *</label>
                    <input type="email" name="email" required placeholder="admin@driyum.com" class="admin-input text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone Number (Optional)</label>
                <input type="text" name="phone" placeholder="+91 9419809801" class="admin-input text-xs font-mono">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password *</label>
                    <input type="password" name="password" required placeholder="••••••••" class="admin-input text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Confirm Password *</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" class="admin-input text-xs">
                </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 flex items-start gap-2.5 mt-2">
                <i class="fas fa-shield-alt text-slate-400 mt-0.5"></i>
                <span>This user will be granted full administrative authority to manage catalog, orders, configurations, and customer data.</span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <a href="users.php" class="btn-admin btn-admin-secondary text-xs">Cancel</a>
                <button type="submit" name="create_admin" class="btn-admin btn-admin-primary text-xs">
                    <i class="fas fa-user-plus"></i> Create Administrator
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
