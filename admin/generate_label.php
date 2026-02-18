<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_admin()) die("Unauthorized access.");

$order_number = sanitize_input($_GET['id'] ?? '');
if (!$order_number) die("Order number required.");

$order = fetch_one("SELECT o.*, u.name as user_name, u.phone as user_phone 
                   FROM orders o 
                   LEFT JOIN users u ON u.id = o.user_id 
                   WHERE o.order_number = ?", [$order_number]);

if (!$order) die("Order not found.");
$address = json_decode($order['shipping_address'], true);
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

    <meta charset="UTF-8">
    <title>Label_<?php echo $order['order_number']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; width: 4in; height: 6in; background: #fff; color: #000; }
        .label-container { width: 3.8in; height: 5.8in; border: 2px solid #000; margin: 0.1in; box-sizing: border-box; display: flex; flex-direction: column; }
        .section { border-bottom: 1px solid #000; padding: 10px; }
        .last-section { border-bottom: none; }
        .header { display: flex; justify-content: space-between; font-weight: bold; border-bottom: 2px solid #000; }
        .small-text { font-size: 8px; text-transform: uppercase; margin-bottom: 2px; }
        .recipient { flex: 1; padding: 15px; }
        .recipient h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
        .recipient p { margin: 5px 0; font-size: 14px; line-height: 1.4; }
        .cod-box { background: #000; color: #fff; padding: 10px; text-align: center; font-size: 18px; font-weight: bold; margin: 10px 0; }
        .prepaid-box { border: 2px solid #000; padding: 10px; text-align: center; font-size: 18px; font-weight: bold; margin: 10px 0; }
        .barcode-area { text-align: center; padding: 20px 0; border-top: 2px solid #000; }
        .order-num { font-size: 14px; font-weight: bold; margin-top: 5px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->


    <div class="no-print" style="position: fixed; top: 10px; right: 10px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Label</button>
    </div>

    <div class="label-container">
        <div class="section header">
            <img src="<?php echo get_url('assets/images/logo.svg'); ?>" alt="DRIYUM" width="100" >
            <span></span>
        </div>

        <div class="recipient" style="border-bottom: 1px solid #000;">
            <div class="small-text">Deliver To:</div>
            <h1><?php echo htmlspecialchars($address['name'] ?? 'N/A'); ?></h1>
            <p>
                <?php echo htmlspecialchars($address['address'] ?? ''); ?><br>
                <?php echo htmlspecialchars($address['city'] ?? ''); ?>, <?php echo htmlspecialchars($address['state'] ?? ''); ?> - <?php echo htmlspecialchars($address['zip'] ?? ''); ?><br>
                <strong>Phone: <?php echo htmlspecialchars($address['phone'] ?? ''); ?></strong>
            </p>
        </div>

        <div class="section">
            <div class="small-text">Ship From:</div>
            <div style="font-size: 10px; font-weight: bold;">
                DRIYUM <br>
                BAGHI MEHTAB SRINAGAR, J&K 190019 <br>
                PHONE  : 9419809801
            </div>
        </div>

        <div class="section last-section" style="margin-top: auto;">
            <?php if($order['payment_method'] == 'cod'): ?>
                <div class="cod-box">COD: ₹<?php echo number_format($order['total'], 0); ?></div>
            <?php else: ?>
                <div class="prepaid-box">PREPAID</div>
            <?php endif; ?>

            <div class="barcode-area">
                <div style="font-size: 30px; letter-spacing: 5px;">||||||||||||||||||</div>
                <div class="order-num"><?php echo $order['order_number']; ?></div>
            </div>
        </div>
    </div>

</body>
</html>


