<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = (new Database())->getConnection();

$income_q = $db->query("SELECT SUM(total_price) as total FROM orders");
$income = $income_q->fetch_assoc()['total'] ?? 0;

$orders_q = $db->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $orders_q->fetch_assoc()['total'] ?? 0;

$products_q = $db->query("SELECT COUNT(*) as total FROM products");
$total_products = $products_q->fetch_assoc()['total'] ?? 0;

$batch_q = $db->query("SELECT batch_name FROM batches WHERE is_active = 1 LIMIT 1");
$active_batch = $batch_q->fetch_assoc()['batch_name'] ?? 'Tidak Ada Batch Aktif';

$recent_orders = $db->query("SELECT o.*, b.batch_name FROM orders o JOIN batches b ON o.batch_id = b.id ORDER BY o.id DESC LIMIT 5");
$recent_products = $db->query("SELECT p.*, (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as total_img FROM products p ORDER BY p.id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MabaStore — Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#fafafa] text-gray-800 font-sans text-sm antialiased selection:bg-indigo-100 selection:text-indigo-900">

    <div class="flex min-h-screen">
        <aside class="w-64 bg-white border-r border-gray-200/80 p-6 flex flex-col justify-between shrink-0 sticky top-0 h-screen">
            <div class="space-y-8">
                <div class="px-2">
                    <div class="text-base font-black tracking-tight text-gray-950 flex items-center gap-2.5">
                        <div class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center text-white text-xs">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <span>MabaStore.</span>
                    </div>
                </div>
                
                <nav class="space-y-1">
                    <a href="index.php" class="bg-indigo-50/60 text-indigo-600 font-bold block px-4 py-2.5 rounded-xl transition flex items-center gap-3">
                        <i class="fa-solid fa-chart-pie text-sm"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="product_crud.php" class="text-gray-500 hover:text-gray-900 font-semibold block px-4 py-2.5 rounded-xl transition flex items-center gap-3">
                        <i class="fa-solid fa-box-archive text-sm"></i>
                        <span>Katalog & Bundle</span>
                    </a>
                    <a href="orders.php" class="text-gray-500 hover:text-gray-900 font-semibold block px-4 py-2.5 rounded-xl transition flex items-center gap-3">
                        <i class="fa-solid fa-receipt text-sm"></i>
                        <span>Data Pesanan</span>
                    </a>
                    <a href="batch.php" class="text-gray-500 hover:text-gray-900 font-semibold block px-4 py-2.5 rounded-xl transition flex items-center gap-3">
                        <i class="fa-solid fa-toggle-on text-sm"></i>
                        <span>Batch Pre-Order</span>
                    </a>
                </nav>
            </div>
            
            <div>
                <a href="logout.php" class="text-red-600 hover:bg-red-50/60 font-bold block px-4 py-2.5 rounded-xl transition text-center border border-red-100 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 p-8 lg:p-12 overflow-y-auto">
            <header class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-10 pb-6 border-b border-gray-200/60">
                <div>
                    <h1 class="text-2xl font-black text-gray-950 tracking-tight">Console Overview</h1>
                    <p class="text-gray-400 text-xs mt-1">Metrik performa penjualan operasional kelengkapan maba aktif.</p>
                </div>
                
                <div class="bg-white border border-gray-200 px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2.5 shadow-2xs">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-gray-400">Batch Aktif:</span>
                    <span class="text-gray-900 font-bold"><?= $active_batch; ?></span>
                </div>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white border border-gray-200/80 p-6 rounded-2xl shadow-2xs flex flex-col justify-between min-h-[125px]">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Omzet</p>
                        <p class="text-3xl font-black text-gray-950 tracking-tight">Rp <?= number_format($income, 0, ',', '.'); ?></p>
                    </div>
                    <p class="text-gray-400 text-[10px]">*Akumulasi transaksi terverifikasi</p>
                </div>
                
                <div class="bg-white border border-gray-200/80 p-6 rounded-2xl shadow-2xs flex flex-col justify-between min-h-[125px]">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Pesanan Masuk</p>
                        <p class="text-3xl font-black text-gray-950 tracking-tight"><?= $total_orders; ?> <span class="text-xs font-medium text-gray-400">Inbound</span></p>
                    </div>
                    <a href="orders.php" class="text-indigo-600 text-xs font-bold hover:text-indigo-800 flex items-center gap-1 mt-2">Data Antrean <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                </div>
                
                <div class="bg-white border border-gray-200/80 p-6 rounded-2xl shadow-2xs flex flex-col justify-between min-h-[125px]">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Aset Katalog</p>
                        <p class="text-3xl font-black text-gray-950 tracking-tight"><?= $total_products; ?> <span class="text-xs font-medium text-gray-400">Item</span></p>
                    </div>
                    <a href="product_crud.php" class="text-indigo-600 text-xs font-bold hover:text-indigo-800 flex items-center gap-1 mt-2">Kelola Katalog <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <div class="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-2xs">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-sm font-bold text-gray-950 uppercase tracking-wide">Recent Orders</h3>
                            <p class="text-gray-400 text-xxs mt-0.5">5 antrean invoice masuk terbaru.</p>
                        </div>
                        <a href="orders.php" class="text-xs font-bold text-gray-400 hover:text-gray-900 transition">Lihat Semua</a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-gray-100 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                    <th class="pb-3 px-2">Invoice Code</th>
                                    <th class="pb-3 px-2">Nama Pembeli</th>
                                    <th class="pb-3 px-2">Status Alur</th>
                                    <th class="pb-3 px-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 font-medium text-gray-600">
                                <?php if($recent_orders->num_rows > 0): ?>
                                    <?php while($ro = $recent_orders->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50/40">
                                        <td class="py-3 px-2 font-mono font-bold text-indigo-600"><?= $ro['order_code']; ?></td>
                                        <td class="py-3 px-2 text-gray-900 font-semibold"><?= htmlspecialchars($ro['customer_name']); ?></td>
                                        <td class="py-3 px-2">
                                            <?php
                                            $lbl = 'bg-amber-50 text-amber-700 border-amber-200/40';
                                            if($ro['status'] == 'Di-packing') $lbl = 'bg-blue-50 text-blue-700 border-blue-200/40';
                                            if($ro['status'] == 'Dikirim') $lbl = 'bg-emerald-50 text-emerald-700 border-emerald-200/40';
                                            ?>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border <?= $lbl; ?>"><?= $ro['status']; ?></span>
                                        </td>
                                        <td class="py-3 px-2 text-right font-bold text-gray-950">Rp<?= number_format($ro['total_price'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="py-6 text-center text-gray-400 italic">No inbound orders.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-2xs">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-sm font-bold text-gray-950 uppercase tracking-wide">Latest Inventory</h3>
                            <p class="text-gray-400 text-xxs mt-0.5">5 aset katalog komoditas terbaru.</p>
                        </div>
                        <a href="product_crud.php" class="text-xs font-bold text-gray-400 hover:text-gray-900 transition">Lihat Semua</a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-gray-100 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                    <th class="pb-3 px-2">Item Name</th>
                                    <th class="pb-3 px-2">Type</th>
                                    <th class="pb-3 px-2 text-right">Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 font-medium text-gray-600">
                                <?php if($recent_products->num_rows > 0): ?>
                                    <?php while($rp = $recent_products->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50/40">
                                        <td class="py-3 px-2">
                                            <span class="text-gray-900 font-semibold block"><?= htmlspecialchars($rp['name']); ?></span>
                                            <span class="text-[10px] text-gray-400 mt-0.5"><i class="fa-solid fa-image text-gray-300 mr-1"></i><?= $rp['total_img']; ?> asset file</span>
                                        </td>
                                        <td class="py-3 px-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border <?= $rp['type'] == 'bundle' ? 'bg-purple-50 text-purple-700 border-purple-200/40':'bg-gray-100 text-gray-600 border-gray-200/40'; ?>">
                                                <?= $rp['type']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-2 text-right font-bold text-gray-950">Rp<?= number_format($rp['price'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="py-6 text-center text-gray-400 italic">No items listed.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>