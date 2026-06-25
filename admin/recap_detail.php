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
// DATA REKAPAN MANUAL (OVERRIDE UNTUK SEPATU YANG TERPOTONG)
// ----------------------------------------------------------------------
$manual_shoes_map = [
    'bhrama' => '42',
    'gus muhammad arya' => '42',
    'zhafira az zahra' => '40',
    'fadhil alauddin' => '43',
    'faizah nailatul karimah' => '37',
    'kayyis muhammad ridho' => '45',
    'denisya amaliana putri' => '39',
    'iqlima nailah salwa' => '41',
    'terine benetta' => '40',
    'gyanendra cahaya kesuma' => '44',
    'darren daiva' => '44',
    'elberd mukuan' => '43',
    'kezia avril felicia' => '41',
    'zaidan reifan al faros' => '43',
    'mohammad farrel ramadhan tito' => '43',
    'alina citra mandasari' => '38',
    'nahlita vidya arulyuna' => '41',
    'eithen rafay pasah' => '42',
    'ardha barani shidqini' => '42',
    'kimberly putri ananda' => '39',
    'nayla langit rizki' => '39',
    'maria beata grace artajati' => '38',
    'monica citra lestari' => '40',
    'timothy togarma gaho' => '40',
    'sabrina audya margadien' => '41',
    'maria gonza yovita beu' => '39',
    'adrian efron jhon' => '41',
    'nabilah putri wahyudi' => '37',
    'muhammad adnan' => '44',
    'anggita permata indra putri nasir' => '37',
    'muhammad abdanyal malakan' => '41',
    'muhammad adi raihan' => '41',
    'putri bening nurani' => '40',
    'radya christy putri pambayun' => '40',
    'alimah rasyidah salsabila putri' => '39',
    'arif rafi algifarri' => '43',
    'keira aurelia trixie' => '37',
    'ahmad hanif al miqdad' => '38',
    'ananta muhammad fikar' => '42'
];

// ----------------------------------------------------------------------
// DATA CONTAINER (Wadah Penampung Hasil Perhitungan)
// ----------------------------------------------------------------------
$rekap_satuan = []; 
$rekap_bundle = []; 

// Ambil semua item pesanan berdasarkan filter batch beserta nama customer untuk pencocokan manual
$query_orders = "SELECT oi.product_id, oi.quantity, oi.selected_size, p.name as product_name, p.type, o.customer_name 
                 FROM order_items oi 
                 JOIN orders o ON oi.order_id = o.id 
                 JOIN products p ON oi.product_id = p.id 
                 $where_clause";
$res_orders = $db->query($query_orders);

// ID Produk Sepatu berdasarkan database Anda
$id_pantofel_cowok = 11;
$id_pantofel_cewek = 12;

while ($row = $res_orders->fetch_assoc()) {
    $pid = intval($row['product_id']);
    $qty = intval($row['quantity']);
    $pname = $row['product_name'];
    $ptype = $row['type'];
    $raw_size = !empty($row['selected_size']) ? $row['selected_size'] : 'All Size';
    $customer_name_lower = strtolower(trim($row['customer_name']));

    if ($ptype === 'bundle') {
        // 1. Masukkan ke rekap bundle utama
        if (!isset($rekap_bundle[$pid])) {
            $rekap_bundle[$pid] = ['name' => $pname, 'total_qty' => 0];
        }
        $rekap_bundle[$pid]['total_qty'] += $qty;

        // 2. Ambil relasi produk di dalam bundle tersebut
        $stmt_bundle = $db->prepare("SELECT br.regular_product_id, p.name FROM bundle_relations br JOIN products p ON br.regular_product_id = p.id WHERE br.bundle_product_id = ?");
        $stmt_bundle->bind_param("i", $pid);
        $stmt_bundle->execute();
        $res_bundle = $stmt_bundle->get_result();

        while ($sub = $res_bundle->fetch_assoc()) {
            $sub_id = intval($sub['regular_product_id']);
            $sub_name = $sub['name'];
            $final_sub_size = 'All Size';

            // Cek apakah produk ini adalah sepatu pantofel
            $is_sepatu = ($sub_id === $id_pantofel_cowok || $sub_id === $id_pantofel_cewek);

            // LOGIK CHECK: Cek apakah nama customer ada di daftar rekapan manual
            $matched_manual_size = false;
            if ($is_sepatu) {
                foreach ($manual_shoes_map as $manual_name => $manual_size) {
                    if (strpos($customer_name_lower, $manual_name) !== false) {
                        $final_sub_size = $manual_size; // Langsung kunci ukuran manual di sini
                        $matched_manual_size = true;
                        
                        // Menentukan gender sepatu berdasarkan isi text bundle
                        if (strpos(strtolower($raw_size), 'celana') !== false) {
                            $sub_id = $id_pantofel_cowok;
                            $sub_name = 'Sepatu Pantopel Cowok';
                        } else {
                            $sub_id = $id_pantofel_cewek;
                            $sub_name = 'Sepatu Pantopel Cewek';
                        }
                        break;
                    }
                }
            }

            // JIKA TIDAK COCOK DATA MANUAL, BARU JALANKAN REGEX BAWAAN
            if (!$matched_manual_size && $raw_size !== 'All Size') {
                $escaped_sub_name = preg_quote($sub_name, '/');
                if (preg_match('/' . $escaped_sub_name . '\s*:\s*([^,]+)/i', $raw_size, $matches)) {
                    $final_sub_size = trim($matches[1]);
                }
            }

            if (!isset($rekap_satuan[$sub_id])) {
                $rekap_satuan[$sub_id] = ['name' => $sub_name, 'total_qty' => 0, 'sizes' => []];
            }
            $rekap_satuan[$sub_id]['total_qty'] += $qty;

            if (!isset($rekap_satuan[$sub_id]['sizes'][$final_sub_size])) {
                $rekap_satuan[$sub_id]['sizes'][$final_sub_size] = 0;
            }
            $rekap_satuan[$sub_id]['sizes'][$final_sub_size] += $qty;
        }
        $stmt_bundle->close();

    } else {
        // Produk Reguler / Eceran biasa
        if (!isset($rekap_satuan[$pid])) {
            $rekap_satuan[$pid] = ['name' => $pname, 'total_qty' => 0, 'sizes' => []];
        }
        $rekap_satuan[$pid]['total_qty'] += $qty;

        if (!isset($rekap_satuan[$pid]['sizes'][$raw_size])) {
            $rekap_satuan[$pid]['sizes'][$raw_size] = 0;
        }
        $rekap_satuan[$pid]['sizes'][$raw_size] += $qty;
    }
}

// Urutkan alfabetis berdasarkan Nama Produk
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
    
    <style>
        @media print {
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                font-size: 11px !important;
            }
            aside, .print\:hidden, button, form {
                display: none !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                overflow: visible !important;
                display: block !important;
            }
            tr, .avoid-break {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .shadow-xs, .rounded-2xl {
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            th, td, div, span {
                overflow: visible !important;
                white-space: normal !important;
                word-break: break-word !important;
                text-overflow: clip !important;
            }
            @page { margin: 1cm; }
        }
    </style>
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
                    <p class="text-gray-400 text-xs mt-1">Total produksi bersih (Otomatis memecah bundle & memperbaiki data size sepatu maba yang terpotong).</p>
                </div>
                <form method="GET" class="flex items-center gap-2 bg-white p-2 rounded-xl border border-gray-200 shadow-xs">
                    <span class="text-xs font-semibold text-gray-500 px-1">Batch Filter:</span>
                    <select name="batch_id" onchange="this.form.submit()" class="border border-gray-200 bg-white px-3 py-1.5 rounded-lg text-xs font-bold text-gray-700 focus:outline-none">
                        <?php while($bt = $all_batches->fetch_assoc()): ?>
                            <option value="<?= $bt['id']; ?>" <?= $filter_batch == $bt['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($bt['batch_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </header>

            <div class="flex justify-end mb-6 print:hidden">
                <button onclick="window.print()" class="bg-gray-900 hover:bg-gray-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 cursor-pointer shadow-xs">
                    <i class="fa-solid fa-print"></i> Cetak / Save PDF Vendor
                </button>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs mb-8 avoid-break">
                <div class="mb-4">
                    <h2 class="text-base font-black text-indigo-600 flex items-center gap-2">
                        <i class="fa-solid fa-shirt"></i> 
                        1. REKAP ITEM SATUAN BERSIH (UNTUK KONVEKSI / VENDOR)
                    </h2>
                    <p class="text-gray-400 text-xs mt-0.5">Tabel menampilkan total kuantitas asli. Data sepatu terpotong otomatis diklasifikasi berdasarkan pakaian utama (Celana = Cowok, Rok = Cewek).</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs table-auto">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-[11px] text-gray-500 font-bold uppercase tracking-wider">
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nama Barang (Atribut Maba)</th>
                                <th class="py-3 px-4">Rincian Kuantitas per Ukuran (Size)</th>
                                <th class="py-3 px-4 text-center bg-indigo-50 text-indigo-700 font-bold w-32">Total Bersih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium text-gray-600">
                            <?php if(!empty($rekap_satuan)): ?>
                                <?php $no = 1; foreach($rekap_satuan as $id_satuan => $data): ?>
                                <tr>
                                    <td class="py-4 px-4 text-center font-mono font-bold text-gray-400"><?= $no++; ?></td>
                                    <td class="py-4 px-4">
                                        <span class="text-gray-900 font-bold text-sm block whitespace-normal break-words"><?= htmlspecialchars($data['name']); ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 w-full min-w-[250px]">
                                            <?php 
                                            // Urutkan size agar rapi dari kecil ke besar
                                            ksort($data['sizes']); 
                                            foreach($data['sizes'] as $s_name => $s_qty): 
                                            ?>
                                                <div class="flex justify-between items-center p-2 bg-gray-50 border border-gray-200/60 rounded-lg font-semibold text-gray-700 text-[11px] avoid-break">
                                                    <span class="text-gray-900">Size: <strong class="text-indigo-950 font-bold"><?= htmlspecialchars($s_name); ?></strong></span>
                                                    <span class="bg-white text-indigo-600 font-bold font-mono px-2 py-0.5 rounded border border-gray-200 text-[11px]">
                                                        <?= $s_qty; ?> pcs
                                                    </span>
                                                </div>
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
                                    <td colspan="4" class="py-8 text-center text-gray-400 italic">Belum ada data transaksi pesanan barang satuan pada batch ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-xs avoid-break">
                <div class="mb-4">
                    <h2 class="text-base font-black text-purple-600 flex items-center gap-2">
                        <i class="fa-solid fa-cubes"></i> 
                        2. REKAP TOTAL BUNDLE UTAMA (UNTUK CEK PACKING / STOK KEMASAN)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs table-auto">
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
                                        <span class="text-gray-900 font-bold text-sm block whitespace-normal break-words"><?= htmlspecialchars($data['name']); ?></span>
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