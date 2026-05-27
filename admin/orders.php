<?php
session_start();
require_once '../config/database.php';
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

// 🎛️ LOGIKA BACKEND PAGINATION & FILTER ORDERS
$limit = 10; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$filter_status = $_GET['status'] ?? '';
$filter_batch = $_GET['batch_id'] ?? '';

$where_clause = " WHERE 1=1";
if (!empty($filter_status)) { $where_clause .= " AND o.status = '$filter_status'"; }
if (!empty($filter_batch)) { $where_clause .= " AND o.batch_id = " . intval($filter_batch); }

$total_res = $db->query("SELECT COUNT(*) as total FROM orders o $where_clause");
$total_data = $total_res->fetch_assoc()['total'];
$pages = ceil($total_data / $limit);

// Ubah query menjadi seperti ini
$orders = $db->query("SELECT o.*, b.batch_name FROM orders o JOIN batches b ON o.batch_id = b.id $where_clause ORDER BY o.id DESC LIMIT $start, $limit");
$all_batches = $db->query("SELECT id, batch_name FROM batches ORDER BY id DESC");
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
<body class="bg-gray-50 p-6 md:p-10 text-sm antialiased text-gray-800">

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
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Daftar Pre-Order Mahasiswa</h2>
                    <p class="text-gray-400 text-xs mt-1">Kelola data pesanan, rincian belanjaan produk maba, dan pelacakan nomor resi pengiriman.</p>
                </div>
                
                <form method="GET" class="flex flex-wrap gap-2 bg-gray-50 p-2 rounded-xl border border-gray-200/50">
                    <select name="status" class="border border-gray-200 bg-white px-3 py-1.5 rounded-lg text-xs font-medium focus:outline-none focus:border-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="Diproses" <?= $filter_status=='Diproses'?'selected':''; ?>>Diproses</option>
                        <option value="Di-packing" <?= $filter_status=='Di-packing'?'selected':''; ?>>Di-packing</option>
                        <option value="Dikirim" <?= $filter_status=='Dikirim'?'selected':''; ?>>Dikirim</option>
                    </select>
                    <select name="batch_id" class="border border-gray-200 bg-white px-3 py-1.5 rounded-lg text-xs font-medium focus:outline-none focus:border-indigo-500">
                        <option value="">Semua Batch</option>
                        <?php while($bt = $all_batches->fetch_assoc()): ?>
                            <option value="<?= $bt['id']; ?>" <?= $filter_batch==$bt['id']?'selected':''; ?>><?= $bt['batch_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-semibold text-xs px-4 py-1.5 rounded-lg transition">Filter</button>
                    <?php if(!empty($filter_status) || !empty($filter_batch)): ?>
                        <a href="orders.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold text-xs px-3 py-1.5 rounded-lg transition flex items-center">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="overflow-x-auto -mx-6 md:mx-0">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs text-gray-400 font-semibold uppercase tracking-wider">
                            <th class="pb-4 px-6">Kode / Pembeli</th>
                            <th class="pb-4 px-4">Item Belanjaan</th>
                            <th class="pb-4 px-4">Status</th>
                            <th class="pb-4 px-4">No. Resi</th>
                            <th class="pb-4 px-4">Total Pembayaran</th>
                            <th class="pb-4 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-gray-600">
                        <?php if($orders->num_rows > 0): ?>
                            <?php while($ord = $orders->fetch_assoc()): ?>
                            <tr class="align-top hover:bg-gray-50/50 transition">
                                <td class="py-4 px-6">
                                    <span class="font-mono font-bold text-indigo-600 block text-xs tracking-wide"><?= $ord['order_code']; ?></span>
                                    <span class="text-gray-900 font-semibold block mt-0.5"><?= htmlspecialchars($ord['customer_name']); ?></span>
                                    <span class="text-gray-400 text-xs block mt-0.5"><?= htmlspecialchars($ord['customer_phone']); ?></span>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-200/40 max-w-[260px] space-y-1.5">
                                        <?php 
                                        $current_order_id = $ord['id'];
                                        $items_q = $db->query("SELECT oi.quantity, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $current_order_id");
                                        while($item = $items_q->fetch_assoc()):
                                        ?>
                                            <div class="text-xs font-medium text-gray-700 flex items-start gap-1">
                                                <span class="bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded-md font-bold text-[10px] mt-0.5"><?= $item['quantity']; ?>x</span> 
                                                <span class="truncate" title="<?= htmlspecialchars($item['name']); ?>"><?= htmlspecialchars($item['name']); ?></span>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <?php
                                    $status_class = 'bg-amber-50 text-amber-700 border-amber-200/60';
                                    if($ord['status'] == 'Di-packing') $status_class = 'bg-blue-50 text-blue-700 border-blue-200/60';
                                    if($ord['status'] == 'Dikirim') $status_class = 'bg-green-50 text-green-700 border-green-200/60';
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold inline-block border <?= $status_class; ?>">
                                        <?= $ord['status']; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-mono text-xs text-gray-500">
                                    <?= !empty($ord['receipt_number']) ? '<span class="bg-gray-100 text-gray-800 px-2 py-1 rounded font-medium">'.$ord['receipt_number'].'</span>' : '<span class="text-gray-300 italic">Belum Ada</span>'; ?>
                                </td>
                                <td class="py-4 px-4 font-bold text-gray-900 text-base">
                                    Rp<?= number_format($ord['total_price'],0,',','.'); ?>
                                </td>
                                <td class="py-4 px-6 text-right space-x-3 whitespace-nowrap">
                                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($ord)); ?>)" class="text-indigo-600 hover:text-indigo-900 font-semibold transition cursor-pointer">Edit</button>
                                    <button onclick="confirmDeleteOrder(<?= $ord['id']; ?>)" class="text-red-500 hover:text-red-700 font-semibold transition cursor-pointer">Hapus</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400 italic bg-gray-50/30 rounded-xl">Belum ada data pesanan masuk untuk filter ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center border-t border-gray-100 pt-5 mt-8 gap-4">
            <span class="text-xs text-gray-400 font-medium">Menampilkan total <strong class="text-gray-700"><?= $total_data; ?></strong> log transaksi data maba.</span>
            <div class="flex space-x-1">
                <?php for($i=1; $i<=$pages; $i++): ?>
                    <a href="orders.php?page=<?= $i; ?>&status=<?= $filter_status; ?>&batch_id=<?= $filter_batch; ?>" class="px-3.5 py-1.5 text-xs rounded-xl font-semibold transition <?= $page == $i ? 'bg-indigo-600 text-white shadow-xs':'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>"><?= $i; ?></a>
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

    // Fungsi membuka modal pop-up dan mapping value dari database secara dinamis
    function openEditModal(orderData) {
        document.getElementById('modalOrderId').value = orderData.id;
        document.getElementById('modalOrderCode').innerText = orderData.order_code;
        document.getElementById('modalCustomerName').value = orderData.customer_name;
        document.getElementById('modalCustomerPhone').value = orderData.customer_phone;
        document.getElementById('modalCustomerAddress').value = orderData.customer_address;
        document.getElementById('modalStatus').value = orderData.status;
        document.getElementById('modalReceiptNumber').value = orderData.receipt_number || '';

        // Trigger Animasi Fade In & Scale Up
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }, 20);
    }

    // Fungsi menutup modal pop-up dengan efek transisi smooth
    function closeEditModal() {
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 300);
    }

    // Menutup modal otomatis jika area hitam di luar box diklik
    window.onclick = function(event) {
        if (event.target == modal) { closeEditModal(); }
    }

    // Fungsi konfirmasi hapus data berbasis SweetAlert2
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