<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db = (new Database())->getConnection();

// Proses Aksi Hapus Pesanan (Delete)
if (isset($_GET['delete_id'])) {
    $order_id = intval($_GET['delete_id']);
    $db->query("DELETE FROM orders WHERE id = $order_id");
    $_SESSION['swal'] = ['type' => 'success', 'title' => 'Terhapus!', 'text' => 'Data pesanan berhasil dibersihkan dari database.'];
    header("Location: orders.php");
    exit;
}

// Logika Edit Informasi Identitas Pesanan & Resi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_order_edit'])) {
    $order_id = intval($_POST['order_id']);
    $name = htmlspecialchars($_POST['customer_name']);
    $phone = htmlspecialchars($_POST['customer_phone']);
    $address = htmlspecialchars($_POST['customer_address']);
    $status = $_POST['status'];
    $receipt = htmlspecialchars($_POST['receipt_number']);

    $stmt = $db->prepare("UPDATE orders SET customer_name = ?, customer_phone = ?, customer_address = ?, status = ?, receipt_number = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $phone, $address, $status, $receipt, $order_id);
    $stmt->execute();

    $_SESSION['swal'] = ['type' => 'success', 'title' => 'Berhasil Diubah!', 'text' => 'Data transaksi dan log resi berhasil diperbarui.'];
    header("Location: orders.php");
    exit;
}

// 🎛️ LOGIKA BACKEND PAGINATION, FILTER, & SEARCH ORDERS
$limit = 12; // Diubah ke 12 agar pas dengan grid susunan kelipatan 2, 3, atau 4 card
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$filter_status = $_GET['status'] ?? '';
$filter_batch = $_GET['batch_id'] ?? '';
$filter_product = $_GET['product_search'] ?? ''; 
$search = $_GET['search'] ?? '';

// Menyusun Klausa WHERE secara dinamis dan aman dari SQL Injection
$where_clause = " WHERE 1=1";
if (!empty($filter_status)) { 
    $where_clause .= " AND o.status = '" . $db->real_escape_string($filter_status) . "'"; 
}
if (!empty($filter_batch)) { 
    $where_clause .= " AND o.batch_id = " . intval($filter_batch); 
}
if (!empty($search)) { 
    $search_escaped = $db->real_escape_string($search);
    $where_clause .= " AND (o.order_code LIKE '%$search_escaped%' OR o.customer_name LIKE '%$search_escaped%')"; 
}

// LOGIKA FILTER PRODUK: Cek ke tabel order_items & gabung ke products (Mendukung eceran & bundle)
if (!empty($filter_product)) {
    $product_escaped = $db->real_escape_string($filter_product);
    $where_clause .= " AND EXISTS (
        SELECT 1 FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = o.id AND (
            p.name LIKE '%$product_escaped%' 
            OR oi.selected_size LIKE '%$product_escaped%'
            OR EXISTS (
                SELECT 1 FROM bundle_relations br 
                JOIN products sub_p ON br.regular_product_id = sub_p.id 
                WHERE br.bundle_product_id = p.id AND sub_p.name LIKE '%$product_escaped%'
            )
        )
    )";
}

// Hitung total data setelah difilter/search untuk pagination
$total_res = $db->query("SELECT COUNT(*) as total FROM orders o $where_clause");
$total_data = $total_res->fetch_assoc()['total'];
$pages = ceil($total_data / $limit);

// Ambil data utama
$orders = $db->query("SELECT o.*, b.batch_name FROM orders o JOIN batches b ON o.batch_id = b.id $where_clause ORDER BY o.id DESC LIMIT $start, $limit");
$all_batches = $db->query("SELECT id, batch_name FROM batches ORDER BY id DESC");

// Ambil list semua produk reguler untuk opsi dropdown filter agar dinamis
$all_products_list = $db->query("SELECT name FROM products WHERE type = 'reguler' ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Pesanan</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 p-4 md:p-10 text-sm antialiased text-gray-800">

    <?php if(isset($_SESSION['swal'])): ?>
        <script>
            Swal.fire({ icon: '<?= $_SESSION['swal']['type']; ?>', title: '<?= $_SESSION['swal']['title']; ?>', text: '<?= $_SESSION['swal']['text']; ?>', confirmButtonColor: '#4f46e5' });
        </script>
        <?php unset($_SESSION['swal']); ?>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto mb-6 flex justify-between items-center">
        <a href="index.php" class="text-indigo-600 hover:text-indigo-800 font-semibold transition flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Dashboard
        </a>
        <span class="bg-indigo-50 text-indigo-700 text-xs px-3 py-1 rounded-full font-medium">Panel Admin v2.0</span>
    </div>

    <div class="max-w-7xl mx-auto bg-white rounded-2xl border border-gray-100 shadow-xs p-6 md:p-8 flex flex-col justify-between min-h-[70px]">
        <div>
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Daftar Pre-Order Mahasiswa</h2>
                    <p class="text-gray-400 text-xs mt-1">Kelola data pesanan, rincian belanjaan produk maba, dan pelacakan nomor resi pengiriman.</p>
                </div>
                
                <form method="GET" class="flex flex-wrap items-center gap-2 bg-gray-50 p-2 rounded-xl border border-gray-200/50 target-form">
                    <div class="relative flex items-center">
                        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari Kode PO / Nama..." class="border border-gray-200 bg-white pl-8 pr-3 py-1.5 rounded-lg text-xs font-medium focus:outline-none focus:border-indigo-500 w-44 sm:w-52">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    <select name="product_search" class="border border-gray-200 bg-white px-3 py-1.5 rounded-lg text-xs font-medium focus:outline-none focus:border-indigo-500 max-w-[160px]">
                        <option value="">Semua Item / Produk</option>
                        <?php while($prod = $all_products_list->fetch_assoc()): ?>
                            <?php $p_name = htmlspecialchars($prod['name']); ?>
                            <option value="<?= $p_name; ?>" <?= $filter_product == $p_name ? 'selected' : ''; ?>><?= $p_name; ?></option>
                        <?php endwhile; ?>
                    </select>

                    <select name="status" class="border border-gray-200 bg-white px-3 py-1.5 rounded-lg text-xs font-medium focus:outline-none focus:border-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="Diproses" <?= $filter_status=='Diproses'?'selected':''; ?>>Diproses</option>
                        <option value="Di-packing" <?= $filter_status=='Di-packing'?'selected':''; ?>>Di-packing</option>
                        <option value="Dikirim" <?= $filter_status=='Dikirim'?'selected':''; ?>>Dikirim</option>
                    </select>

                    <select name="batch_id" class="border border-gray-200 bg-white px-3 py-1.5 rounded-lg text-xs font-medium focus:outline-none focus:border-indigo-500">
                        <option value="">Semua Batch</option>
                        <?php while($bt = $all_batches->fetch_assoc()): ?>
                            <option value="<?= $bt['id']; ?>" <?= $filter_batch==$bt['id']?'selected':''; ?>><?= htmlspecialchars($bt['batch_name']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-semibold text-xs px-4 py-1.5 rounded-lg transition cursor-pointer">Cari & Filter</button>
                    
                    <?php if(!empty($filter_status) || !empty($filter_batch) || !empty($search) || !empty($filter_product)): ?>
                        <a href="orders.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold text-xs px-3 py-1.5 rounded-lg transition flex items-center">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="mb-6 flex items-center">
                <span class="inline-flex items-center gap-1.5 bg-indigo-50 text-indigo-700 font-bold px-3 py-1.5 rounded-lg text-xs border border-indigo-100">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Ditemukan: <?= $total_data; ?> data pesanan cocok
                </span>
            </div>

            <?php if($orders->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while($ord = $orders->fetch_assoc()): ?>
                        <div class="bg-white border border-gray-200/70 rounded-2xl shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden">
                            <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <div>
                                        <span class="font-mono font-bold text-indigo-600 text-xs tracking-wide block"><?= $ord['order_code']; ?></span>
                                        <h3 class="text-gray-900 font-bold text-base mt-0.5 leading-snug"><?= htmlspecialchars($ord['customer_name']); ?></h3>
                                    </div>
                                    <?php
                                    $status_class = 'bg-amber-50 text-amber-700 border-amber-200/60';
                                    if($ord['status'] == 'Di-packing') $status_class = 'bg-blue-50 text-blue-700 border-blue-200/60';
                                    if($ord['status'] == 'Dikirim') $status_class = 'bg-green-50 text-green-700 border-green-200/60';
                                    ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border shrink-0 <?= $status_class; ?>">
                                        <?= $ord['status']; ?>
                                    </span>
                                </div>
                                <p class="text-gray-400 text-xs font-medium flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    <?= htmlspecialchars($ord['customer_phone']); ?>
                                </p>
                            </div>

                            <div class="p-5 flex-1 space-y-3">
                                <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100 space-y-3">
                                    <?php 
                                    $current_order_id = $ord['id'];
                                    $items_q = $db->query("SELECT oi.quantity, oi.selected_size, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $current_order_id");
                                    while($item = $items_q->fetch_assoc()):
                                    ?>
                                        <div class="text-xs border-b border-gray-200/40 pb-2 last:border-0 last:pb-0">
                                            <div class="flex items-start gap-2">
                                                <span class="bg-indigo-600 text-white px-1.5 py-0.5 rounded-md font-black text-[9px] shrink-0 mt-0.5">
                                                    <?= $item['quantity']; ?>x
                                                </span> 
                                                <span class="text-gray-950 font-bold leading-tight">
                                                    <?= htmlspecialchars($item['name']); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if(!empty($item['selected_size'])): ?>
                                                <div class="pl-7 mt-1.5">
                                                    <span class="block text-[10px] uppercase tracking-wider text-gray-400 font-bold mb-1">Daftar Isi Paket:</span>
                                                    <ul class="list-disc list-inside bg-white rounded-lg border border-gray-200/60 p-2 space-y-1 text-gray-800 font-medium">
                                                        <?php 
                                                        // Memisah isi bundle berdasarkan tanda koma
                                                        $sub_items = explode(',', $item['selected_size']);
                                                        foreach($sub_items as $sub_item): 
                                                            if(trim($sub_item) != ''):
                                                        ?>
                                                            <li class="truncate text-[11px]">▪ <?= htmlspecialchars(trim($sub_item)); ?></li>
                                                        <?php 
                                                            endif;
                                                        endforeach; 
                                                        ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                                    <div>
                                        <span class="block text-gray-400 text-[10px] font-bold uppercase tracking-wide">No. Resi Kurir</span>
                                        <div class="mt-1 font-mono">
                                            <?= !empty($ord['receipt_number']) ? '<span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded font-bold border border-gray-200 text-[11px]">'.$ord['receipt_number'].'</span>' : '<span class="text-gray-300 italic text-[11px]">Belum Ada</span>'; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="block text-gray-400 text-[10px] font-bold uppercase tracking-wide">Konfirmasi Bayar</span>
                                        <div class="mt-1">
                                            <?php if(!empty($ord['payment_proof'])): ?>
                                                <a href="../uploads/<?= htmlspecialchars($ord['payment_proof']); ?>" target="_blank" class="inline-flex items-center gap-1 bg-white border border-gray-200 text-gray-700 px-2 py-0.5 rounded-md hover:text-indigo-600 transition shadow-3xs text-[11px] font-semibold">
                                                    👁️ Lihat Bukti
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-300 italic text-[11px]">Belum Kirim</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-5 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-2">
                                <div>
                                    <span class="block text-gray-400 text-[10px] font-bold uppercase tracking-wide">Total Tagihan</span>
                                    <span class="text-gray-900 font-extrabold text-base">Rp<?= number_format($ord['total_price'],0,',','.'); ?></span>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <?php
                                    $wa_phone = preg_replace('/[^0-9]/', '', $ord['customer_phone']);
                                    if(substr($wa_phone, 0, 1) == '0') {
                                        $wa_phone = '62' . substr($wa_phone, 1);
                                    }

                                    $wa_text = "Halo, *" . $ord['customer_name'] . "*! 👋\n\n";
                                    $wa_text .= "Terima kasih telah melakukan pre-order bundle di toko kami. 📝\n\n";
                                    $wa_text .= "📦 *Nomor PO:* " . $ord['order_code'] . "\n\n";
                                    $wa_text .= "⚠️ *PENTING: KONFIRMASI UKURAN SEPATU*\n";
                                    $wa_text .= "Kami melihat Anda memesan paket **Bundle**, namun data **Ukuran Sepatu Pantofel** Anda belum terinput di sistem kami.\n\n";
                                    $wa_text .= "Mohon balas pesan ini dengan format berikut untuk kelanjutan proses orderan Anda:\n";
                                    $wa_text .= "*Ukuran Sepatu Pantofel: [Isi Ukuran Anda]*\n\n";
                                    $wa_text .= "Jangan lupa juga untuk *menyimpan Nama dan Nomor PO* di atas untuk keperluan tracking status pesanan di website nanti ya.\n\n";
                                    $wa_text .= "Terima kasih! ✨";
                                    $wa_link = "https://wa.me/" . $wa_phone . "?text=" . urlencode($wa_text);
                                    ?>
                                    <a href="<?= $wa_link; ?>" target="_blank" title="Chat WhatsApp" class="p-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-xl transition border border-emerald-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    </a>
                                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($ord)); ?>)" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl font-bold border border-indigo-100 transition text-xs">Edit</button>
                                    <button onclick="confirmDeleteOrder(<?= $ord['id']; ?>)" class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-500 rounded-xl font-bold border border-red-100 transition text-xs">Hapus</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="py-12 text-center text-gray-400 italic bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                    Belum ada data pesanan masuk untuk filter ini.
                </div>
            <?php endif; ?>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center border-t border-gray-100 pt-5 mt-8 gap-4">
            <span class="text-xs text-gray-400 font-medium">Menampilkan total <strong class="text-gray-700"><?= $orders->num_rows; ?></strong> data dari total keseluruhan <strong class="text-gray-700"><?= $total_data; ?></strong> data maba.</span>
            <div class="flex space-x-1">
                <?php for($i=1; $i<=$pages; $i++): ?>
                    <a href="orders.php?page=<?= $i; ?>&status=<?= urlencode($filter_status); ?>&batch_id=<?= urlencode($filter_batch); ?>&search=<?= urlencode($search); ?>&product_search=<?= urlencode($filter_product); ?>" class="px-3.5 py-1.5 text-xs rounded-xl font-semibold transition <?= $page == $i ? 'bg-indigo-600 text-white shadow-xs':'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>"><?= $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div id="editOrderModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4 transition-all opacity-0">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-2xl w-full max-w-lg transform scale-95 transition-all duration-300 flex flex-col overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Ubah Berkas Log Order</h3>
                    <p class="text-gray-400 text-xxs mt-0.5">Kode Nota: <span id="modalOrderCode" class="font-mono font-bold text-indigo-600"></span></p>
                </div>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="order_id" id="modalOrderId">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Nama Lengkap Pemesan</label>
                    <input type="text" name="customer_name" id="modalCustomerName" required class="w-full border border-gray-200 px-3.5 py-2 rounded-xl focus:outline-none focus:border-indigo-500 font-medium text-gray-900 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">No. WhatsApp Aktif</label>
                    <input type="text" name="customer_phone" id="modalCustomerPhone" required class="w-full border border-gray-200 px-3.5 py-2 rounded-xl focus:outline-none focus:border-indigo-500 font-mono text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Alamat Lengkap / Keterangan NRP</label>
                    <textarea name="customer_address" id="modalCustomerAddress" rows="3" class="w-full border border-gray-200 px-3.5 py-2 rounded-xl focus:outline-none focus:border-indigo-500 text-xs font-medium"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Status Alur Order</label>
                        <select name="status" id="modalStatus" class="w-full border border-gray-200 px-3.5 py-2 rounded-xl bg-white focus:outline-none focus:border-indigo-500 text-xs font-semibold text-gray-700">
                            <option value="Diproses">Diproses</option>
                            <option value="Di-packing">Di-packing</option>
                            <option value="Dikirim">Dikirim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Nomor Resi Kurir</label>
                        <input type="text" name="receipt_number" id="modalReceiptNumber" placeholder="Resi Logistik" class="w-full border border-gray-200 px-3.5 py-2 rounded-xl focus:outline-none focus:border-indigo-500 font-mono text-xs text-indigo-600 font-semibold">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4 mt-6">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-semibold text-xs transition cursor-pointer">Batal</button>
                    <button type="submit" name="save_order_edit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-xs shadow-xs transition cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const modal = document.getElementById('editOrderModal');
    const modalContent = modal.querySelector('.transform');

    function openEditModal(orderData) {
        document.getElementById('modalOrderId').value = orderData.id;
        document.getElementById('modalOrderCode').innerText = orderData.order_code;
        document.getElementById('modalCustomerName').value = orderData.customer_name;
        document.getElementById('modalCustomerPhone').value = orderData.customer_phone;
        document.getElementById('modalCustomerAddress').value = orderData.customer_address;
        document.getElementById('modalStatus').value = orderData.status;
        document.getElementById('modalReceiptNumber').value = orderData.receipt_number || '';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }, 20);
    }

    function closeEditModal() {
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 300);
    }

    window.onclick = function(event) {
        if (event.target == modal) { closeEditModal(); }
    }

    function confirmDeleteOrder(id) {
        Swal.fire({
            title: 'Hapus Log Transaksi?',
            text: "Seluruh sub item belanjaan mahasiswa ini juga akan ikut dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Ya, Hapus Sekarang!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) { 
                window.location.href = 'orders.php?delete_id=' + id; 
            }
        });
    }
    </script>
</body>
</html>