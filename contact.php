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

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T3LPLX64');</script>
<!-- End Google Tag Manager -->

    <title>Contact Driyum | Customer Support & Enquiries</title>

<meta name="description" content="Get in touch with Driyum for orders, support or business enquiries. We’d love to hear from you.">

    <?php 
    $page_title = 'Contact Driyum | Customer Support & Enquiries';
    $page_description = "Have questions about our premium snacks? Want to discuss a partnership? Contact the DRIYUM team today. We're here to help from the heart of the valley.";
    include 'includes/head.php'; 
    ?>
</head>
<body class="bg-[#FFFBEB]">

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->


    <?php include 'includes/header.php'; ?>

    <section class="relative pt-20 pb-20 px-4 overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 left-0 w-64 h-64 bg-[#19DC7E] rounded-full blur-[120px] opacity-10"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-yellow-300 rounded-full blur-[150px] opacity-10"></div>

        <div class="container mx-auto max-w-6xl relative z-10 px-4">
            <div class="text-center mb-12 md:mb-16">
                <span class="inline-block bg-white border border-gray-100 px-4 py-2 rounded-full text-[9px] md:text-[10px] font-black uppercase tracking-widest text-[#19DC7E] mb-4 shadow-sm transform -rotate-1 md:-rotate-2">Say Hello 🍑</span>
                <h1 class="text-[clamp(2.5rem,8vw,5rem)] font-['Fredoka'] font-black text-gray-900 mb-6 leading-tight">Let's Get <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#19DC7E] to-[#0ea5e9]">Crunching.</span></h1>
                <p class="text-gray-500 font-['Outfit'] text-base md:text-xl max-w-2xl mx-auto px-4">Have a question or just want to tell us how much you love our chunky snacks? We're all ears!</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <!-- Contact Info Cards -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Card 1: Address -->
                    <div class="bg-white p-6 md:p-8 rounded-[32px] md:rounded-[40px] shadow-xl shadow-gray-100 border border-gray-50 group hover:-translate-y-2 transition-transform duration-300">
                        <div class="w-12 h-12 md:w-14 md:h-14 bg-green-50 rounded-2xl flex items-center justify-center text-[#19DC7E] mb-6 group-hover:bg-[#19DC7E] group-hover:text-white transition-colors">
                            <i class="fas fa-map-marker-alt text-xl md:text-2xl"></i>
                        </div>
                        <h3 class="text-lg md:text-xl font-black font-['Fredoka'] text-gray-900 mb-2">Our Valley</h3>
                        <p class="text-gray-500 font-medium text-sm md:text-base leading-relaxed"><?php echo htmlspecialchars($info['address']); ?></p>
                    </div>

                    <!-- Card 2: Phone/Email -->
                    <div class="bg-white p-6 md:p-8 rounded-[32px] md:rounded-[40px] shadow-xl shadow-gray-100 border border-gray-50 group hover:-translate-y-2 transition-transform duration-300">
                        <div class="w-12 h-12 md:w-14 md:h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-500 mb-6 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                            <i class="fas fa-phone-alt text-xl md:text-2xl"></i>
                        </div>
                        <h3 class="text-lg md:text-xl font-black font-['Fredoka'] text-gray-900 mb-2">Direct Line</h3>
                        <p class="text-gray-900 font-bold mb-1 text-sm md:text-base"><?php echo htmlspecialchars($info['phone']); ?></p>
                        <p class="text-gray-500 font-medium text-sm md:text-base"><?php echo htmlspecialchars($info['email']); ?></p>
                    </div>

                    <!-- Card 3: Socials -->
                    <div class="bg-black p-6 md:p-8 rounded-[32px] md:rounded-[40px] shadow-2xl text-white relative overflow-hidden group">
                        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-[#19DC7E] rounded-full blur-[60px] opacity-20 group-hover:opacity-40 transition-opacity"></div>
                        <h3 class="text-lg md:text-xl font-black font-['Fredoka'] mb-6 relative z-10">Join the Fam</h3>
                        <div class="flex gap-4 relative z-10">
                            <a href="https://wa.me/<?php echo $info['whatsapp']; ?>" class="w-11 h-11 md:w-12 md:h-12 bg-white/10 rounded-xl md:rounded-2xl flex items-center justify-center hover:bg-[#19DC7E] hover:text-black transition-all"><i class="fab fa-whatsapp text-lg md:text-xl"></i></a>
                            <a href="https://instagram.com/<?php echo $info['instagram']; ?>" class="w-11 h-11 md:w-12 md:h-12 bg-white/10 rounded-xl md:rounded-2xl flex items-center justify-center hover:bg-[#E1306C] transition-all"><i class="fab fa-instagram text-lg md:text-xl"></i></a>
                            <a href="https://facebook.com/<?php echo $info['facebook']; ?>" class="w-11 h-11 md:w-12 md:h-12 bg-white/10 rounded-xl md:rounded-2xl flex items-center justify-center hover:bg-[#1877F2] transition-all"><i class="fab fa-facebook-f text-lg md:text-xl"></i></a>
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
                                    <input type="text" name="name" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-2xl px-6 py-4 outline-none transition-all font-bold text-gray-900" placeholder="e.g. Driyum">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-3 ml-2 tracking-widest">Email Address</label>
                                    <input type="email" name="email" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[#19DC7E] focus:bg-white rounded-2xl px-6 py-4 outline-none transition-all font-bold text-gray-900" placeholder="contact@driyum.com">
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
