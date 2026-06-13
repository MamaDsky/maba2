<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = (new Database())->getConnection();

// Ambil data batch untuk dropdown filter
$all_batches = $db->query("SELECT id, batch_name FROM batches ORDER BY id DESC");

// Filter Batch (Default ke batch aktif)
$filter_batch = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : '';
$where_clause = " WHERE 1=1";

if (!empty($filter_batch)) {
    $where_clause .= " AND o.batch_id = $filter_batch";
} else {
    $active_batch_q = $db->query("SELECT id FROM batches WHERE is_active = 1 LIMIT 1");
    if ($ab = $active_batch_q->fetch_assoc()) {
        $filter_batch = $ab['id'];
        $where_clause .= " AND o.batch_id = " . $ab['id'];
    }
}

// ----------------------------------------------------------------------
// DATA CONTAINER (Wadah Penampung Hasil Perhitungan)
// ----------------------------------------------------------------------
$rekap_satuan = []; // Khusus menampung item reguler/satuan (termasuk pecahan isi bundle)
$rekap_bundle = []; // Khusus menampung paket bundle utama

// Ambil semua item pesanan berdasarkan filter batch
$query_orders = "SELECT oi.product_id, oi.quantity, oi.selected_size, p.name, p.type 
                 FROM order_items oi 
                 JOIN orders o ON oi.order_id = o.id 
                 JOIN products p ON oi.product_id = p.id 
                 $where_clause";
$res_orders = $db->query($query_orders);

while ($row = $res_orders->fetch_assoc()) {
    $pid = intval($row['product_id']);
    $qty = intval($row['quantity']);
    $pname = $row['name'];
    $ptype = $row['type'];
    $size = !empty($row['selected_size']) ? $row['selected_size'] : 'All Size';

    if ($ptype === 'bundle') {
        // --- LOGIKA JIKA BARANG ADALAH BUNDLE ---
        // 1. Masukkan ke rekap bundle utama
        if (!isset($rekap_bundle[$pid])) {
            $rekap_bundle[$pid] = [
                'name' => $pname,
                'total_qty' => 0
            ];
        }
        $rekap_bundle[$pid]['total_qty'] += $qty;

        // 2. PECAH ISI BUNDLE-NYA & MASUKKAN +1 KE MASING-MASING ITEM SATUAN
        $stmt_bundle = $db->prepare("SELECT br.regular_product_id, p.name FROM bundle_relations br JOIN products p ON br.regular_product_id = p.id WHERE br.bundle_product_id = ?");
        $stmt_bundle->bind_param("i", $pid);
        $stmt_bundle->execute();
        $res_bundle = $stmt_bundle->get_result();

        while ($sub = $res_bundle->fetch_assoc()) {
            $sub_id = intval($sub['regular_product_id']);
            $sub_name = $sub['name'];

            if (!isset($rekap_satuan[$sub_id])) {
                $rekap_satuan[$sub_id] = ['name' => $sub_name, 'total_qty' => 0, 'sizes' => []];
            }
            // Tambahkan jumlah sesuai kuantitas bundle yang dibeli
            $rekap_satuan[$sub_id]['total_qty'] += $qty;

            // Masukkan rincian ukurannya (mengikuti ukuran yang dipilih saat beli bundle)
            if (!isset($rekap_satuan[$sub_id]['sizes'][$size])) {
                $rekap_satuan[$sub_id]['sizes'][$size] = 0;
            }
            $rekap_satuan[$sub_id]['sizes'][$size] += $qty;
        }
        $stmt_bundle->close();

    } else {
        // --- LOGIKA JIKA BARANG ADALAH SATUAN BIASA (REGULER) ---
        if (!isset($rekap_satuan[$pid])) {
            $rekap_satuan[$pid] = ['name' => $pname, 'total_qty' => 0, 'sizes' => []];
        }
        $rekap_satuan[$pid]['total_qty'] += $qty;

        if (!isset($rekap_satuan[$pid]['sizes'][$size])) {
            $rekap_satuan[$pid]['sizes'][$size] = 0;
        }
        $rekap_satuan[$pid]['sizes'][$size] += $qty;
    }
}

// Urutkan nama produk berdasarkan alfabet
uasort($rekap_satuan, function($a, $b) { return strcmp($a['name'], $b['name']); });
uasort($rekap_bundle, function($a, $b) { return strcmp($a['name'], $b['name']); });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MabaStore — Rekap Jumlah Barang</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#fafafa] text-gray-800 text-sm antialiased">

    <div class="flex min-h-screen">
        <aside class="w-64 bg-white border-r border-gray-200 p-6 flex flex-col justify-between shrink-0 sticky top-0 h-screen print:hidden">
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
                    <a href="index.php" class="text-gray-500 hover:text-gray-900 font-semibold block px-4 py-2.5 rounded-xl transition flex items-center gap-3">
                        <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
                    </a>
                    <a href="product_crud.php" class="text-gray-500 hover:text-gray-900 font-semibold block px-4 py-2.5 rounded-xl transition flex items-center gap-3">
                        <i class="fa-solid fa-box-archive"></i> <span>Katalog & Bundle</span>
                    </a>
                    <a href="orders.php" class="text-gray-500 hover:text-gray-900 font-semibold block px-4 py-2.5 rounded-xl transition flex items-center gap-3">
                        <i class="fa-solid fa-receipt"></i> <span>Data Pesanan</span>
                    </a>
                    <a href="recap.php" class="bg-indigo-50 text-indigo-600 font-bold block px-4 py-2.5 rounded-xl transition flex items-center gap-3">
                        <i class="fa-solid fa-calculator"></i> <span>Rekap Jumlah Barang</span>
                    </a>
                    <a href="batch.php" class="text-gray-500 hover:text-gray-900 font-semibold block px-4 py-2.5 rounded-xl transition flex items-center gap-3">
                        <i class="fa-solid fa-toggle-on"></i> <span>Batch Pre-Order</span>
                    </a>
                </nav>
            </div>
            <div>
                <a href="logout.php" class="text-red-600 border border-red-100 hover:bg-red-50 font-bold block px-4 py-2.5 rounded-xl transition text-center flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-from-bracket"></i> <span>Log Out</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 p-8 lg:p-12 overflow-y-auto">
            
            <header class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-8 pb-6 border-b border-gray-200 print:hidden">
                <div>
                    <h1 class="text-2xl font-black text-gray-950 tracking-tight">Perhitungan Rekap Barang</h1>
                    <p class="text-gray-400 text-xs mt-1">Total jumlah kebutuhan produksi kain bersih (Otomatis memecah isi paket bundle).</p>
                </div>
                
                <form method="GET" class="flex items-center gap-2 bg-white p-2 rounded-xl border border-gray-200 shadow-xs">
                    <span class="text-xs font-semibold text-gray-500 px-1">Batch:</span>
                    <select name="batch_id" onchange="this.form.submit()" class="border border-gray-200 bg-white px-3 py-1.5 rounded-lg text-xs font-bold text-gray-700 focus:outline-none">
                        <?php while($bt = $all_batches->fetch_assoc()): ?>
                            <option value="<?= $bt['id']; ?>" <?= $filter_batch == $bt['id'] ? 'selected' : ''; ?>><?= $bt['batch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </header>

            <div class="flex justify-end mb-6 print:hidden">
                <button onclick="window.print()" class="bg-gray-900 hover:bg-gray-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 cursor-pointer shadow-xs">
                    <i class="fa-solid fa-print"></i> Cetak / Save PDF Vendor
                </button>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs mb-8">
                <div class="mb-4">
                    <h2 class="text-base font-black text-indigo-600 flex items-center gap-2">
                        <i class="fa-solid fa-shirt"></i> 
                        1. REKAP ITEM SATUAN BERSIH (UNTUK KONVEKSI / VENDOR)
                    </h2>
                    <p class="text-gray-400 text-xs mt-0.5">Tabel ini menampilkan total kebutuhan kain asli. Pembelian lewat <b>Bundle</b> sudah otomatis dibongkar dan dijumlahkan langsung ke dalam item satuannya di bawah ini.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-[11px] text-gray-500 font-bold uppercase tracking-wider">
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nama Barang (Atribut Maba)</th>
                                <th class="py-3 px-4">Rincian Total per Ukuran (Size)</th>
                                <th class="py-3 px-4 text-center bg-indigo-50 text-indigo-700 font-bold w-40">Total Bersih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium text-gray-600">
                            <?php if(!empty($rekap_satuan)): ?>
                                <?php $no = 1; foreach($rekap_satuan as $id_satuan => $data): ?>
                                <tr>
                                    <td class="py-4 px-4 text-center font-mono font-bold text-gray-400"><?= $no++; ?></td>
                                    <td class="py-4 px-4">
                                        <span class="text-gray-900 font-bold text-sm block"><?= htmlspecialchars($data['name']); ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach($data['sizes'] as $s_name => $s_qty): ?>
                                                <span class="inline-flex items-center gap-1.5 bg-gray-100 border border-gray-200/60 px-2.5 py-1 rounded-md font-semibold text-gray-700">
                                                    Size <b><?= htmlspecialchars($s_name); ?></b>: <span class="text-indigo-600 font-bold font-mono"><?= $s_qty; ?></span> pcs
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center bg-indigo-50/30 font-black text-base text-indigo-600">
                                        <?= $data['total_qty']; ?> <span class="text-xs font-normal text-gray-400">Pcs</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-400 italic">Belum ada data pesanan barang satuan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs">
                <div class="mb-4">
                    <h2 class="text-base font-black text-purple-600 flex items-center gap-2">
                        <i class="fa-solid fa-cubes"></i> 
                        2. REKAP TOTAL BUNDLE UTAMA (UNTUK CEK PACKING / STOK KEMASAN)
                    </h2>
                    <p class="text-gray-400 text-xs mt-0.5">Hanya mencatat kuantitas penjualan paket utuhnya (tidak dipecah). Berguna untuk menghitung jumlah kantong plastik/tas kemasan bundle.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-[11px] text-gray-500 font-bold uppercase tracking-wider">
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nama Paket Bundle</th>
                                <th class="py-3 px-4 text-center bg-purple-50 text-purple-700 font-bold w-40">Total Terjual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium text-gray-600">
                            <?php if(!empty($rekap_bundle)): ?>
                                <?php $no = 1; foreach($rekap_bundle as $id_bundle => $data): ?>
                                <tr>
                                    <td class="py-4 px-4 text-center font-mono font-bold text-gray-400"><?= $no++; ?></td>
                                    <td class="py-4 px-4">
                                        <span class="text-gray-900 font-bold text-sm block"><?= htmlspecialchars($data['name']); ?></span>
                                    </td>
                                    <td class="py-4 px-4 text-center bg-purple-50/30 font-black text-base text-purple-600">
                                        <?= $data['total_qty']; ?> <span class="text-xs font-normal text-gray-400">Paket</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-gray-400 italic">Tidak ada penjualan produk bertipe paket bundle pada batch ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

</body>
</html>