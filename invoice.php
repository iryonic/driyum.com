<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$order_number = sanitize_input($_GET['id'] ?? '');
if (!$order_number) die("Order number required.");

$order = fetch_one("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                   FROM orders o 
                   LEFT JOIN users u ON u.id = o.user_id 
                   WHERE o.order_number = ?", [$order_number]);

if (!$order) die("Order not found.");

// Only allow owner or admin to view invoice
if (!is_admin() && $_SESSION['user_id'] != $order['user_id']) {
    die("Access denied.");
}

// Restriction: Customers can only see invoice after delivery
if (!is_admin() && $order['order_status'] !== 'delivered') {
    die("Order not delivered yet. Get invoice after delivery.");
}

$items = fetch_all("SELECT oi.*, p.name, p.sku FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?", [$order['id']]);
$address = json_decode($order['shipping_address'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice_<?php echo $order['order_number']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 20px; background-color: #fff; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #000; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .details-grid h3 { font-size: 12px; text-transform: uppercase; color: #888; margin-bottom: 10px; border-bottom: 1px solid #eee; }
        .details-grid p { margin: 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table th { background: #f9f9f9; border-bottom: 2px solid #eee; padding: 12px; font-size: 12px; text-align: left; text-transform: uppercase; }
        table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        .totals { float: right; width: 300px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .total-row.grand-total { border-top: 2px solid #333; margin-top: 10px; padding-top: 10px; font-weight: bold; font-size: 18px; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .invoice-box { border: none; }
        }
        .btn-print { background: #333; color: #fff; padding: 10px 20px; border: none; cursor: pointer; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right; max-width: 800px; margin: auto;">
        <button onclick="window.print()" class="btn-print">Print Invoice</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div>
                <h1>DRIYUM</h1>
                <p style="font-size: 12px; color: #666; margin-top: 5px;">
                    Srinagar, Jammu & Kashmir 190001<br>
                    GSTIN: 01ABCDE1234F1Z5
                </p>
            </div>
            <div style="text-align: right;">
                <h2 style="margin: 0; font-size: 20px;">INVOICE</h2>
                <p style="font-size: 14px; margin: 5px 0;">#<?php echo $order['order_number']; ?></p>
                <p style="font-size: 12px; color: #666;">Date: <?php echo date('Y-m-d', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <div class="details-grid">
            <div>
                <h3>Billed To:</h3>
                <p><strong><?php echo htmlspecialchars($order['user_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($order['user_email']); ?></p>
                <p><?php echo htmlspecialchars($order['user_phone']); ?></p>
            </div>
            <div>
                <h3>Shipped To:</h3>
                <p><strong><?php echo htmlspecialchars($address['name'] ?? 'N/A'); ?></strong></p>
                <p><?php echo htmlspecialchars($address['address'] ?? ''); ?></p>
                <p><?php echo htmlspecialchars($address['city'] ?? ''); ?>, <?php echo htmlspecialchars($address['state'] ?? ''); ?> - <?php echo htmlspecialchars($address['zip'] ?? ''); ?></p>
                <p>Phone: <?php echo htmlspecialchars($address['phone'] ?? ''); ?></p>
            </div>
        </div>

        <table>
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
                        <strong><?php echo $item['name']; ?></strong><br>
                        <span style="font-size: 11px; color: #888;">SKU: <?php echo $item['sku'] ?: 'N/A'; ?></span>
                    </td>
                    <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($item['price'], 2); ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="overflow: hidden;">
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₹<?php echo number_format($order['subtotal'], 2); ?></span>
                </div>
                <?php if($order['discount'] > 0): ?>
                <div class="total-row" style="color: #d32f2f;">
                    <span>Discount:</span>
                    <span>- ₹<?php echo number_format($order['discount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>₹<?php echo number_format($order['shipping_cost'], 2); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Total:</span>
                    <span>₹<?php echo number_format($order['total'], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer generated document.</p>
        </div>
    </div>

</body>
</html>
