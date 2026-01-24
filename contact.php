<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$info = fetch_one("SELECT * FROM contact_info LIMIT 1");

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    if ($name && $email && $message) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        if ($stmt->execute()) {
            $success = "Your message has been sent successfully! We'll get back to you soon.";
        } else {
            $error = "Oops! Something went wrong. Please try again later.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php render_seo_tags('Contact Us', 'Have a question? Get in touch with the DRIYUM team. We are here to help you with your snack queries and orders.'); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo get_url('assets/css/chunky.css'); ?>">
</head>
<body class="bg-[#FFFBEB]">

    <?php include 'includes/header.php'; ?>

    <section class="relative pt-20 pb-20 px-4 overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 left-0 w-64 h-64 bg-[#19DC7E] rounded-full blur-[120px] opacity-10"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-yellow-300 rounded-full blur-[150px] opacity-10"></div>

        <div class="container mx-auto max-w-6xl relative z-10">
            <div class="text-center mb-16">
                <span class="inline-block bg-white border border-gray-100 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest text-[#19DC7E] mb-4 shadow-sm transform -rotate-2">Say Hello 🍑</span>
                <h1 class="text-5xl md:text-7xl font-['Fredoka'] font-black text-gray-900 mb-6">Let's Get <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#19DC7E] to-[#0ea5e9]">Crunching.</span></h1>
                <p class="text-gray-500 font-['Outfit'] text-lg md:text-xl max-w-2xl mx-auto">Have a question or just want to tell us how much you love our chunky snacks? We're all ears!</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <!-- Contact Info Cards -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Card 1: Address -->
                    <div class="bg-white p-8 rounded-[40px] shadow-xl shadow-gray-100 border border-gray-50 group hover:-translate-y-2 transition-transform duration-300">
                        <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center text-[#19DC7E] mb-6 group-hover:bg-[#19DC7E] group-hover:text-white transition-colors">
                            <i class="fas fa-map-marker-alt text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-black font-['Fredoka'] text-gray-900 mb-2">Our Valley</h3>
                        <p class="text-gray-500 font-medium leading-relaxed"><?php echo htmlspecialchars($info['address']); ?></p>
                    </div>

                    <!-- Card 2: Phone/Email -->
                    <div class="bg-white p-8 rounded-[40px] shadow-xl shadow-gray-100 border border-gray-50 group hover:-translate-y-2 transition-transform duration-300">
                        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-500 mb-6 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                            <i class="fas fa-phone-alt text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-black font-['Fredoka'] text-gray-900 mb-2">Direct Line</h3>
                        <p class="text-gray-900 font-bold mb-1"><?php echo htmlspecialchars($info['phone']); ?></p>
                        <p class="text-gray-500 font-medium"><?php echo htmlspecialchars($info['email']); ?></p>
                    </div>

                    <!-- Card 3: Socials -->
                    <div class="bg-black p-8 rounded-[40px] shadow-2xl text-white relative overflow-hidden group">
                        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-[#19DC7E] rounded-full blur-[60px] opacity-20 group-hover:opacity-40 transition-opacity"></div>
                        <h3 class="text-xl font-black font-['Fredoka'] mb-6 relative z-10">Join the Fam</h3>
                        <div class="flex gap-4 relative z-10">
                            <a href="https://wa.me/<?php echo $info['whatsapp']; ?>" class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center hover:bg-[#19DC7E] hover:text-black transition-all"><i class="fab fa-whatsapp text-xl"></i></a>
                            <a href="https://instagram.com/<?php echo $info['instagram']; ?>" class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center hover:bg-[#E1306C] transition-all"><i class="fab fa-instagram text-xl"></i></a>
                            <a href="https://facebook.com/<?php echo $info['facebook']; ?>" class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center hover:bg-[#1877F2] transition-all"><i class="fab fa-facebook-f text-xl"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="lg:col-span-8">
                    <div class="bg-white p-6 md:p-12 rounded-[50px] shadow-2xl shadow-gray-200/50 border border-gray-50">
                        <?php if($success): ?>
                            <div class="bg-green-500 text-white p-6 rounded-3xl mb-8 flex items-center gap-4 animate-bounce">
                                <i class="fas fa-check-circle text-2xl"></i>
                                <p class="font-bold"><?php echo $success; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if($error): ?>
                            <div class="bg-red-500 text-white p-6 rounded-3xl mb-8 flex items-center gap-4">
                                <i class="fas fa-exclamation-circle text-2xl"></i>
                                <p class="font-bold"><?php echo $error; ?></p>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 ml-2 tracking-widest">Your Name</label>
                                    <input type="text" name="name" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-2xl px-6 py-4 outline-none transition-all font-bold text-gray-900" placeholder="e.g. irfan Manzoor">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 ml-2 tracking-widest">Email Address</label>
                                    <input type="email" name="email" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-2xl px-6 py-4 outline-none transition-all font-bold text-gray-900" placeholder="irfan@example.com">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 ml-2 tracking-widest">The Reason</label>
                                <input type="text" name="subject" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-2xl px-6 py-4 outline-none transition-all font-bold text-gray-900" placeholder="Product Query, Franchise, etc.">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 ml-2 tracking-widest">What's on your mind?</label>
                                <textarea name="message" required rows="6" class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-[30px] px-6 py-5 outline-none transition-all font-bold text-gray-900" placeholder="Type your message here..."></textarea>
                            </div>
                            
                            <button type="submit" name="send_message" class="w-full bg-black text-[#19DC7E] py-6 rounded-3xl font-black uppercase tracking-widest hover:bg-[#19DC7E] hover:text-black transition-all shadow-xl shadow-green-100 flex items-center justify-center gap-3">
                                <span>Send Message 🚀</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="mt-20 rounded-[50px] overflow-hidden shadow-2xl border-8 border-white bg-white">
                <div class="p-4 bg-white flex justify-between items-center">
                    <span class="text-[10px] font-black uppercase tracking-widest px-4 py-2 bg-gray-100 rounded-full text-gray-500">Find us in the mountains</span>
                    <div class="flex gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-400"></span>
                        <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                        <span class="w-3 h-3 rounded-full bg-green-400"></span>
                    </div>
                </div>
                <div class="aspect-video lg:aspect-[21/9]">
                    <?php echo $info['map_iframe']; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
