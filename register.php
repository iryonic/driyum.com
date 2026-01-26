<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . get_url('account'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    
    // Check if email exists
    $exists = fetch_one("SELECT id FROM users WHERE email = ?", [$email]);
    
    if ($exists) {
        $error = "That email is already snacking with us.";
    } else {
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['is_admin'] = 0; // Standard registration is never admin
            set_flash_message("Account created successfully! Welcome to Driyum.");
            header("Location: " . get_url('account'));
            exit;
        } else {
            $error = "Something went wrong.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Join the Crunch Club';
    $page_description = "Join the DRIYUM family today. Create an account to track your sun-dried snack orders, save your mountain favorites, and get exclusive harvest alerts.";
    include 'includes/head.php'; 
    ?>
</head>
<body class="bg-[#FFFBEB] min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-5xl bg-white rounded-[3rem] shadow-2xl overflow-hidden flex flex-col md:flex-row-reverse min-h-[600px] anim-up">
        
        <!-- RIGHT: ARTWORK -->
        <div class="w-full md:w-1/2 bg-[#19DC7E] p-12 flex flex-col justify-between relative overflow-hidden text-gray-900">
            <a href="<?php echo get_url(''); ?>" class="font-['Fredoka'] font-bold text-2xl relative z-10"><i class="fas fa-arrow-left mr-2"></i> Back to Shop</a>
            
            <div class="relative z-10">
                <h1 class="text-5xl font-['Fredoka'] font-bold mb-4">Join the Crunch Club.</h1>
                <p class="text-lg font-['Outfit'] opacity-80">Get exclusive deals, new harvest alerts, and faster checkout.</p>
            </div>
            
            <!-- Decor -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-white rounded-full opacity-20 blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 text-9xl opacity-20 rotate-[-12deg]">🥝</div>
        </div>

        <!-- LEFT: FORM -->
        <div class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-center">
            
            <h2 class="text-3xl font-['Fredoka'] font-bold text-gray-900 mb-8">Create Account</h2>
            
            <?php if($error): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-xl mb-6 font-bold flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">Full Name</label>
                    <input type="text" name="name" required placeholder="John Doe" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                </div>

                <div>
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">Email Address</label>
                    <input type="email" name="email" required placeholder="hello@example.com" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                </div>

                <div>
                    <label class="block text-gray-400 font-bold mb-2 text-sm uppercase">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="input-chunky bg-gray-50 border-transparent focus:bg-white w-full">
                </div>

                <button type="submit" class="btn-chunky btn-primary w-full py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-1 bg-black text-white hover:bg-[#19DC7E]">
                    Sign Up <i class="fas fa-user-plus ml-2 opacity-70"></i>
                </button>
            </form>

            <div class="mt-8 text-center text-gray-500 font-['Outfit']">
                Already have an account? <a href="<?php echo get_url('login'); ?>" class="text-black font-bold hover:text-[#19DC7E] underline decoration-wavy">Log In</a>
            </div>
            
        </div>
    </div>

</body>
</html>
