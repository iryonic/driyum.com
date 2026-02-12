<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $page_title = 'Privacy, Terms & Refunds';
    $page_description = "Our commitment to trust and transparency. Read our privacy policy, terms of service, and refund guidelines. No secrets, just healthy snacks.";
    include 'includes/head.php'; 
    ?>
</head>
<body class="bg-[#FFFEDC] font-sans">

    <?php include 'includes/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="pt-32 pb-16 px-6">
        <div class="container mx-auto max-w-4xl text-center">
            <span class="bg-[#24B25D]/10 text-[#24B25D] text-xs font-black px-6 py-2 rounded-full uppercase tracking-widest mb-6 inline-block anim-up">Trust & Transparency</span>
            <h1 class="text-5xl md:text-7xl font-heading font-black text-gray-900 mb-6 anim-up delay-100">Legal Bits <span class="text-[#24B25D]">.</span></h1>
            <p class="text-xl text-gray-400 font-medium max-w-2xl mx-auto anim-up delay-200">Everything you need to know about our relationship, your data, and how we handle your crunchy boxes.</p>
        </div>
    </section>

    <!-- CONTENT SECTION -->
    <section class="pb-32 px-6">
        <div class="container mx-auto max-w-4xl">
            
            <div class="bg-white rounded-[50px] p-8 md:p-16 shadow-sm border border-gray-100 space-y-16 anim-up delay-300">
                
                <!-- PRIVACY POLICY -->
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-[#24B25D]/10 rounded-2xl flex items-center justify-center text-[#24B25D] text-xl">
                            <i class="fas fa-shield-halved"></i>
                        </div>
                        <h2 class="text-3xl font-heading font-black text-gray-900">Privacy Policy</h2>
                    </div>
                    <div class="text-gray-500 leading-relaxed space-y-4 font-medium">
                        <?php echo nl2br(htmlspecialchars(get_setting('legal_privacy_policy', "At Driyum, we value your privacy and are committed to protecting your personal information. When you visit our website or place an order, we may collect basic details such as your name, phone number, email address, delivery address, and payment-related information.\n\nThis information is collected solely for the purpose of processing orders, providing customer support, and improving our services. We do not sell, rent, or share your personal data with third parties, except where required to complete your order (such as payment gateways and delivery partners) or when required by law.\n\nAll online payments are processed through secure third-party payment gateways. Driyum does not store your card, UPI, or banking details.\n\nBy using our website, you consent to the collection and use of information as described in this Privacy Policy. Driyum reserves the right to update this policy at any time. Any changes will be reflected on this page."))); ?>
                    </div>
                </div>

                <div class="h-px bg-gray-50"></div>

                <!-- TERMS & CONDITIONS -->
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-500 text-xl">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h2 class="text-3xl font-heading font-black text-gray-900">Terms & Conditions</h2>
                    </div>
                    <div class="text-gray-500 leading-relaxed space-y-4 font-medium">
                        <?php echo nl2br(htmlspecialchars(get_setting('legal_terms_conditions', "By accessing and using the Driyum website, you agree to comply with these terms and conditions.\n\nAll products sold by Driyum are food products. Dehydrated fruits are intended for direct consumption, while dehydrated vegetables are intended for cooking purposes only. Product images shown on the website are for representation purposes only. Actual product colour, size, and texture may vary due to natural variations in fruits and vegetables.\n\nPrices, product availability, and offers are subject to change without prior notice. Driyum reserves the right to cancel or refuse any order due to unforeseen circumstances, including stock unavailability, pricing errors, or quality concerns. By placing an order, you confirm that the information provided by you is accurate and complete."))); ?>
                    </div>
                </div>

                <div class="h-px bg-gray-50"></div>

                <!-- RETURNS & REFUND POLICY -->
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-orange-500/10 rounded-2xl flex items-center justify-center text-orange-500 text-xl">
                            <i class="fas fa-rotate-left"></i>
                        </div>
                        <h2 class="text-3xl font-heading font-black text-gray-900">Returns & Refund Policy</h2>
                    </div>
                    <div class="text-gray-500 leading-relaxed space-y-6 font-medium">
                        <?php echo nl2br(htmlspecialchars(get_setting('legal_returns_refunds', "Due to the nature of food products, returns are not accepted once an order has been delivered. Refunds may be considered only in special cases, including:\n\n• Damaged or broken packaging\n• Opened or unsealed pouches\n• Product received in a compromised or unfit condition\n\nTo request a refund, customers are required to share a clear unboxing video of the package. The video must show the parcel being opened from start to finish and clearly display the condition of the outer packaging, inner pouch, and product seal at the time of opening. Refund requests without a valid unboxing video may not be considered."))); ?>
                    </div>
                </div>

                <div class="h-px bg-gray-50"></div>

                <!-- DISCLAIMER -->
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-500/10 rounded-2xl flex items-center justify-center text-red-500 text-xl">
                            <i class="fas fa-triangle-exclamation"></i>
                        </div>
                        <h2 class="text-3xl font-heading font-black text-gray-900">Disclaimer</h2>
                    </div>
                    <div class="text-gray-500 leading-relaxed space-y-4 font-medium">
                        <?php echo nl2br(htmlspecialchars(get_setting('legal_disclaimer', "Driyum products are made using natural fruits and vegetables. As these are agricultural products, variations in colour, taste, texture, and appearance may occur.\n\nNutritional values mentioned on the website or packaging are approximate values based on standard food composition data. Actual values may vary.\n\nDriyum products are not intended to diagnose, treat, cure, or prevent any disease. Customers with specific medical conditions or dietary requirements are advised to consult a professional before consumption.\n\nDehydrated vegetables sold by Driyum are intended for cooking purposes only and are not meant for direct consumption."))); ?>
                    </div>
                </div>

            </div>

            <!-- FAQ CTA -->
            <div class="mt-16 text-center anim-up delay-500">
                <p class="text-gray-400 mb-6 font-bold uppercase tracking-widest text-xs">Still have questions?</p>
                <a href="<?php echo get_url('contact'); ?>" class="btn-chunky bg-[#111827] text-white px-10 py-5 shadow-2xl hover:bg-[#24B25D] hover:text-black inline-flex items-center gap-3">
                    Chat with Us <i class="fas fa-comments"></i>
                </a>
            </div>

        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>


