<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$ids = $_GET['ids'] ?? '';
$type = $_GET['type'] ?? 'all'; // labels, invoices, all
if (!$ids) die("Order IDs required.");

$ids_array = explode(',', $ids);
$ids_str = implode(',', array_map('intval', $ids_array));

$orders = fetch_all("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                    FROM orders o 
                    LEFT JOIN users u ON u.id = o.user_id 
                    WHERE o.id IN ($ids_str)
                    ORDER BY FIELD(o.id, $ids_str)");

if (empty($orders)) die("No orders found.");
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
    <title>Batch_<?php echo ucfirst($type); ?>_<?php echo date('Ymd_His'); ?></title>
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; background: #f4f7f6; color: #1e293b; }
        .no-print { 
            position: fixed; 
            top: 20px; 
            right: 20px; 
            z-index: 1000; 
            background: #fff; 
            padding: 24px; 
            border-radius: 30px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.1); 
            border: 1px solid rgba(0,0,0,0.05);
            width: 280px;
        }
        
        /* Thermal Label Styles (4x6) */
        .label-page { 
            width: 4in; 
            height: 6in; 
            background: #fff; 
            margin: 20px auto; 
            padding: 0.1in;
            box-sizing: border-box;
            position: relative;
            border: 1px dashed #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
           
            vertical-align: top;
        }
        .label-container { 
            width: 3.8in; 
            height: 5.8in; 
            border: 2px solid #000; 
            box-sizing: border-box; 
            display: flex; 
            flex-direction: column; 
        }
        .label-section { border-bottom: 1px solid #000; padding: 10px; }
        .label-header { display: flex; justify-content: space-between; font-weight: bold; border-bottom: 2px solid #000; background: #f8fafc; }
        .label-recipient { flex: 1; padding: 15px; }
        .label-recipient h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .label-recipient p { margin: 8px 0; font-size: 13px; line-height: 1.4; }
        .cod-box { background: #000; color: #fff; padding: 10px; text-align: center; font-size: 18px; font-weight: bold; margin: 10px 0; }
        .prepaid-box { border: 2px solid #000; padding: 10px; text-align: center; font-size: 18px; font-weight: bold; margin: 10px 0; }
        .barcode-area { text-align: center; padding: 15px 0; border-top: 2px solid #000; margin-top: auto; }
        
        /* Invoice Styles (A4 scale) */
        .invoice-page {
            width: 8.27in;
            min-height: 11.69in;
            padding: 40px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            border: 1px solid #e2e8f0;
        }
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .invoice-table th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 12px; font-size: 12px; text-align: left; text-transform: uppercase; font-weight: 800; color: #64748b; }
        .invoice-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .invoice-totals { float: right; width: 300px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .grand-total { border-top: 2px solid #0f172a; margin-top: 10px; padding-top: 10px; font-weight: 900; font-size: 18px; }

        .small-text { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: 800; letter-spacing: 0.05em; }

        @media print { 
            @page { 
                size: A4; 
                margin: 5mm; 
            }
            body { 
                margin: 0; 
                padding: 0; 
                width: 100%;
            }
            .no-print { display: none; } 
            
            .print-area {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-start;
                align-items: flex-start;
                gap: 0;
            }

            .invoice-page { 
                width: 100%; 
                height: auto; 
                margin: 0; 
                border: none; 
                box-shadow: none; 
                page-break-after: always;
                clear: both;
                display: block;
            }

            .label-page { 
                width: 49%; 
                height: 13.5cm; /* Reduced to fit 2 rows (4 labels) on A4 */
                margin: 0.5mm; 
                padding: 2mm; 
                box-sizing: border-box;
                border: 1px dotted #e2e8f0; 
                page-break-inside: avoid;
                page-break-after: auto;
                float: none;
            }
            
            .label-container {
                width: 100%;
                height: 100%;
                border: 2px solid #000;
                display: flex;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3LPLX64"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->


    <div class="no-print">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-[#24B25D] rounded-2xl flex items-center justify-center text-black">
                <i class="fas fa-shipping-fast text-xl"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-weight: 900;">Shipping Desk</h3>
                <p class="small-text"><?php echo count($orders); ?> Orders Prepared</p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px;">
            <button onclick="window.print()" style="padding: 16px; cursor: pointer; background: #000; border: none; border-radius: 18px; font-weight: 900; color: #24B25D; width: 100%; display: flex; items-center; justify-content: center; gap: 10px; transition: all 0.2s;">
                <i class="fas fa-print"></i> PRINT BATCH
            </button>
            
            <div style="height: 1px; background: #eee; margin: 10px 0;"></div>
            
            <p class="small-text" style="text-align: center;">Display Options</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button onclick="location.href='?ids=<?php echo $ids; ?>&type=labels'" style="padding: 10px; cursor: pointer; background: <?php echo $type=='labels'?'#f1f5f9':'#fff'; ?>; border: 1px solid #ddd; border-radius: 12px; font-size: 11px; font-weight: 700;">LABELS</button>
                <button onclick="location.href='?ids=<?php echo $ids; ?>&type=invoices'" style="padding: 10px; cursor: pointer; background: <?php echo $type=='invoices'?'#f1f5f9':'#fff'; ?>; border: 1px solid #ddd; border-radius: 12px; font-size: 11px; font-weight: 700;">INVOICES</button>
            </div>
            <button onclick="location.href='?ids=<?php echo $ids; ?>&type=all'" style="padding: 12px; cursor: pointer; background: <?php echo $type=='all'?'#f1f5f9':'#fff'; ?>; border: 1px solid #ddd; border-radius: 12px; font-size: 11px; font-weight: 700; width: 100%;">BOTH (SEQUENTIAL)</button>
            
            <button onclick="window.close()" style="margin-top: 10px; padding: 12px; cursor: pointer; background: transparent; border: 1px solid #eee; border-radius: 12px; font-size: 11px; font-weight: 700; width: 100%; color: #94a3b8;">Close Window</button>
        </div>
    </div>

    <div class="print-area">
    <?php foreach ($orders as $order): ?>
        <?php 
        $address = json_decode($order['shipping_address'], true); 
        $items = fetch_all("SELECT oi.*, p.name, p.sku FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", [$order['id']]);
        ?>

        <!-- Thermal Label -->
        <?php if ($type == 'all' || $type == 'labels'): ?>
        <div class="label-page">
            <div class="label-container">
                <div class="label-section label-header">
                    <span class="small-text"><img src="../assets/images/logo.svg" alt="logo" width="100"></span>
                    <span class="small-text"></span>
                </div>
                <div class="label-recipient" style="border-bottom: 1px solid #000;">
                    <div class="small-text">Deliver To:</div>
                    <h1><?php echo htmlspecialchars($address['name'] ?? 'N/A'); ?></h1>
                    <p>
                        <?php echo htmlspecialchars($address['address'] ?? ''); ?><br>
                        <?php echo htmlspecialchars($address['city'] ?? ''); ?>, <?php echo htmlspecialchars($address['state'] ?? ''); ?> - <?php echo htmlspecialchars($address['zip'] ?? ''); ?><br>
                        <strong style="display: block; margin-top: 5px;">Phone: <?php echo htmlspecialchars($address['phone'] ?? ''); ?></strong>
                    </p>
                </div>
                <div class="label-section">
                    <div class="small-text">Ship From:</div>
                    <div style="font-size: 9px; font-weight: bold;">
                        DRIYUM <br>
                        BAGHI MEHTAB SRINAGAR, J&K 190019 <br>
                        PHONE : 9419809801
                    </div>
                </div>
                <div class="label-section" style="margin-top: auto; border-bottom: none;">
                    <?php if(strtoupper($order['payment_method']) == 'COD'): ?>
                        <div class="cod-box">COD: ₹<?php echo number_format($order['total'], 0); ?></div>
                    <?php else: ?>
                        <div class="prepaid-box">PREPAID</div>
                    <?php endif; ?>
                    <div class="barcode-area">
                        <div style="font-size: 24px; letter-spacing: 4px;">||||||||||||||||||||</div>
                        <div style="font-size: 12px; font-weight: 900; margin-top: 5px;"><?php echo $order['order_number']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Invoice -->
        <?php if ($type == 'all' || $type == 'invoices'): ?>
        <div class="invoice-page">
            <div class="invoice-header">
                <div>
                    <h1 style="margin: 0; font-size: 32px; font-weight: 900; color: #000; letter-spacing: -1px;"><img src="../assets/images/logo.svg" alt="logo" width="100"
                    ></h1>
                    <p class="small-text" style="margin-top: 5px; font-size: 10px;">BAGHI MEHTAB SRINAGAR, J&K 190019 • Fssai number : 21025419000871 </p>
                </div>
                <div style="text-align: right;">
                    <h2 style="margin: 0; font-size: 24px; font-weight: 900;">TAX INVOICE</h2>
                    <p style="font-size: 14px; font-weight: 700; margin: 5px 0;">#<?php echo $order['order_number']; ?></p>
                    <p class="small-text">Date: <?php echo date('Y-m-d', strtotime($order['created_at'])); ?></p>
                </div>
            </div>

            <div class="invoice-grid">
                <div>
                    <h3 class="small-text">Billed To:</h3>
                    <p style="font-weight: 900; font-size: 16px;"><?php echo htmlspecialchars($order['user_name'] ?? 'Guest Customer'); ?></p>
                    <p style="font-size: 13px; color: #64748b;"><?php echo htmlspecialchars($order['user_email'] ?? '-'); ?></p>
                    <p style="font-size: 13px; color: #64748b;"><?php echo htmlspecialchars($order['user_phone'] ?? '-'); ?></p>
                </div>
                <div>
                    <h3 class="small-text">Shipped To:</h3>
                    <p style="font-weight: 900; font-size: 16px;"><?php echo htmlspecialchars($address['name'] ?? 'N/A'); ?></p>
                    <p style="font-size: 13px; color: #64748b;"><?php echo htmlspecialchars($address['address'] ?? ''); ?></p>
                    <p style="font-size: 13px; color: #64748b;"><?php echo htmlspecialchars($address['city'] ?? ''); ?>, <?php echo htmlspecialchars($address['state'] ?? ''); ?> - <?php echo htmlspecialchars($address['zip'] ?? ''); ?></p>
                </div>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Unit Price</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #1e293b;"><?php echo $item['name']; ?></div>
                            <div class="small-text">SKU: <?php echo $item['sku'] ?: 'N/A'; ?></div>
                        </td>
                        <td style="text-align: center; font-weight: 700;"><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right;">₹<?php echo number_format($item['price'], 2); ?></td>
                        <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="overflow: hidden;">
                <div class="invoice-totals">
                    <div class="total-row">
                        <span style="color: #64748b;">Subtotal:</span>
                        <span style="font-weight: 700;">₹<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <?php if($order['discount'] > 0): ?>
                    <div class="total-row" style="color: #ef4444;">
                        <span style="font-weight: 700;">Discount:</span>
                        <span style="font-weight: 700;">- ₹<?php echo number_format($order['discount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row">
                        <span style="color: #64748b;">Shipping:</span>
                        <span style="font-weight: 700;">₹<?php echo number_format($order['shipping_cost'], 2); ?></span>
                    </div>
                    <?php 
                    $tax = $order['total'] - ($order['subtotal'] - $order['discount'] + $order['shipping_cost']);
                    if ($tax > 0): 
                    ?>
                    <div class="total-row">
                        <span style="color: #64748b;">Processing Tax:</span>
                        <span style="font-weight: 700;">₹<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>₹<?php echo number_format($order['total'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 100px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                <p style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em;">Thank you for snacking with Driyum!</p>
            </div>
        </div>
        <?php endif; ?>

    <?php endforeach; ?>
    </div>

</body>
</html>


