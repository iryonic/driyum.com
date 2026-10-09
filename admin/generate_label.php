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

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

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
        .barcode-area { text-align: center; padding: 10px 0; border-top: 2px solid #000; }
        .barcode-area svg { max-width: 100%; height: auto; }
        .order-num { font-size: 14px; font-weight: bold; margin-top: -5px; }
        @media screen {
            body { background: #f8fafc; padding: 20px 0; }
            .label-container { background: #fff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); margin: 0 auto; }
            .no-print-bar {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                background: #ffffff;
                padding: 16px 20px;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.12);
                border: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                gap: 12px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .no-print-btn {
                background: #004f42;
                color: #ffffff;
                border: none;
                border-radius: 10px;
                padding: 10px 18px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: background 0.2s;
            }
            .no-print-btn:hover { background: #00382f; }
            .no-print-btn-secondary {
                background: #f1f5f9;
                color: #475569;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                padding: 10px 14px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
            }
            .no-print-btn-secondary:hover { background: #e2e8f0; }
        }
        @media print { .no-print { display: none; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

    <div class="no-print no-print-bar">
        <div>
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Thermal 4x6 Label</div>
            <div style="font-size: 14px; font-weight: 800; color: #0f172a;">#<?php echo htmlspecialchars($order['order_number']); ?></div>
        </div>
        <button onclick="window.print()" class="no-print-btn">
            <i class="fas fa-print"></i> Print Label
        </button>
        <button onclick="window.close()" class="no-print-btn-secondary">
            Close
        </button>
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
                <svg id="barcode"></svg>
                <div class="order-num"><?php echo $order['order_number']; ?></div>
            </div>
        </div>
    </div>

    <script>
        JsBarcode("#barcode", "<?php echo $order['order_number']; ?>", {
            format: "CODE128",
            width: 2,
            height: 60,
            displayValue: false
        });
    </script>

</body>
</html>


