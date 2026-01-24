<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Simple Auth Check (assuming is_admin() is available or just checking log-in)
if (!is_logged_in()) {
    header("Location: ../login.php");
    exit;
}

$conn = get_db_connection();

// Handle AJAX Update
if (isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $pid = (int)$_POST['product_id'];
    $stock = (int)$_POST['stock'];
    
    $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->bind_param("ii", $stock, $pid);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.stock ASC";
$pagination = get_pagination_data($query, [], 15);
$products = $pagination['records'];

// Keep stats fetching separate for totals
$all_products = fetch_all("SELECT stock FROM products");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - DRIYUM Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; }
        .font-fredoka { font-family: 'Fredoka', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
        .low-stock { background: #fff7ed; border-color: #fed7aa; }
        .out-of-stock { background: #fef2f2; border-color: #fecaca; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-black font-fredoka text-gray-900">Inventory Management 📦</h1>
                <p class="text-gray-500 font-medium">Monitor and update your snack stock levels in real-time.</p>
            </div>
            <a href="index.php" class="inline-flex items-center gap-2 bg-white px-6 py-3 rounded-2xl shadow-sm border border-gray-100 hover:bg-gray-50 transition font-bold text-sm">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-[32px] shadow-sm border border-gray-100">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Total Products</p>
                <h2 class="text-3xl font-black font-fredoka"><?php echo count($all_products); ?></h2>
            </div>
            <div class="bg-amber-50 p-6 rounded-[32px] shadow-sm border border-amber-100">
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-600 mb-1">Low Stock (< 10)</p>
                <?php 
                    $low = array_filter($all_products, fn($p) => $p['stock'] > 0 && $p['stock'] < 10);
                ?>
                <h2 class="text-3xl font-black font-fredoka text-amber-700"><?php echo count($low); ?></h2>
            </div>
            <div class="bg-red-50 p-6 rounded-[32px] shadow-sm border border-red-100">
                <p class="text-[10px] font-black uppercase tracking-widest text-red-600 mb-1">Out of Stock</p>
                <?php 
                    $out = array_filter($all_products, fn($p) => $p['stock'] <= 0);
                ?>
                <h2 class="text-3xl font-black font-fredoka text-red-700"><?php echo count($out); ?></h2>
            </div>
        </div>

        <div class="bg-white rounded-[40px] shadow-xl shadow-gray-200/50 overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-gray-400">Product</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-gray-400">Category</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-gray-400">Current Stock</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($products as $p): 
                            $status_class = '';
                            $status_text = 'Healthy';
                            $dot_color = 'bg-green-500';
                            
                            if ($p['stock'] <= 0) {
                                $status_class = 'out-of-stock';
                                $status_text = 'Out of Stock';
                                $dot_color = 'bg-red-500';
                            } elseif ($p['stock'] < 10) {
                                $status_class = 'low-stock';
                                $status_text = 'Low Stock';
                                $dot_color = 'bg-amber-500';
                            }
                        ?>
                        <tr class="hover:bg-gray-50/50 transition-colors <?php echo $status_class; ?>">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-xl border border-gray-100 p-1">
                                        <img src="../<?php echo $p['image']; ?>" class="w-full h-full object-contain">
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 line-clamp-1"><?php echo $p['name']; ?></p>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">SKU: <?php echo $p['sku']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-gray-100 rounded-full text-[10px] font-black uppercase tracking-widest text-gray-500"><?php echo $p['category_name']; ?></span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <input type="number" id="stock-<?php echo $p['id']; ?>" value="<?php echo $p['stock']; ?>" 
                                        class="w-20 bg-white border-2 border-gray-100 rounded-xl px-3 py-2 text-center font-black focus:border-black outline-none transition uppercase">
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full <?php echo $dot_color; ?>"></div>
                                    <span class="text-xs font-bold text-gray-700"><?php echo $status_text; ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <button onclick="saveStock(<?php echo $p['id']; ?>)" id="btn-<?php echo $p['id']; ?>" class="bg-black text-white px-5 py-2.5 rounded-xl font-bold text-xs hover:scale-105 transition active:scale-95">
                                    Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php echo render_pagination($pagination['total_pages'], $pagination['current_page']); ?>
    </div>

    <div id="toast" class="fixed bottom-10 right-10 z-50 flex flex-col gap-2 translate-y-20 opacity-0 transition-all duration-500 pointer-events-none">
        <div id="toast-content" class="bg-black text-white px-8 py-4 rounded-2xl shadow-2xl font-bold text-sm flex items-center gap-3">
            <i class="fas fa-check-circle text-[#19DC7E]"></i>
            <span id="toast-text">Stock updated successfully!</span>
        </div>
    </div>

    <script>
        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-text').innerText = msg;
            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }

        async function saveStock(pid) {
            const stock = document.getElementById('stock-' + pid).value;
            const btn = document.getElementById('btn-' + pid);
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'update_stock');
            formData.append('product_id', pid);
            formData.append('stock', stock);

            try {
                const res = await fetch('inventory.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    showToast("Stock updated for item #" + pid);
                    // Reload to update status labels or just reload data
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert("Error: " + data.message);
                }
            } catch (e) {
                console.error(e);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
