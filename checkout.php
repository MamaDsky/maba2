<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
$db = (new Database())->getConnection();

$cart = $_SESSION['cart'] ?? [];
$total_price = 0;
$items = [];

// Ekstraksi cart_key yang presisi untuk menghitung Total Harga
if (!empty($cart)) {
    foreach ($cart as $cart_key => $qty) {
        $qty = intval($qty);
        if ($qty <= 0) continue;

        // Pecah ID produk dan ukurannya
        $parts = explode('_', $cart_key);
        $p_id = intval($parts[0]);
        $size = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : '';

        $stmt = $db->prepare("SELECT id, name, price FROM products WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $p_id);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                $row['qty'] = $qty;
                $row['selected_size'] = $size; 
                $row['subtotal'] = intval($row['price']) * $qty;
                $total_price += $row['subtotal'];
                $items[] = $row;
            }
            $stmt->close();
        }
    }
}

// Cek status apakah sedang diarahkan untuk membuka modal pembayaran
$show_modal = isset($_GET['show_modal']) ? true : false;
$modal_code = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';
$modal_total = isset($_GET['total']) ? intval($_GET['total']) : 0;

// Langkah 1: Handle Submit Form Checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_order'])) {
    
    // Cegah keranjang kosong masuk ke database
    if (!empty($items)) {
        $batch_q = $db->query("SELECT id FROM batches WHERE is_active = 1 LIMIT 1");
        $batch_id = ($b = $batch_q->fetch_assoc()) ? $b['id'] : 1;

        $name = htmlspecialchars($_POST['name']);
        $dept = htmlspecialchars($_POST['department']);
        $phone = htmlspecialchars($_POST['phone']);
        $address = htmlspecialchars($_POST['address']);
        $order_code = "PO-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -4));

        $stmt = $db->prepare("INSERT INTO orders (order_code, batch_id, customer_name, customer_department, customer_phone, customer_address, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissssi", $order_code, $batch_id, $name, $dept, $phone, $address, $total_price);
        
        if ($stmt->execute()) {
            $order_id = $db->insert_id;
            
            $it_stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, selected_size, quantity, price) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($items as $it) {
                $prod_id = $it['id'];
                // Jika tidak ada ukuran, set NULL agar tersimpan rapi di DB
                $sel_size = $it['selected_size'] !== '' ? $it['selected_size'] : NULL;
                $item_qty = $it['qty'];
                $item_price = intval($it['price']);
                
                $it_stmt->bind_param("iisii", $order_id, $prod_id, $sel_size, $item_qty, $item_price);
                $it_stmt->execute();
            }
            
            // Kosongkan keranjang di session
            $_SESSION['cart'] = []; 
            
            // Redirect URL agar tidak terjadi error Double Form Submit Loop
            header("Location: checkout.php?show_modal=1&code=" . $order_code . "&total=" . $total_price);
            exit;
        }
    } else {
        header("Location: products.php");
        exit;
    }
}

// Langkah 2: Handle Upload Bukti Pembayaran dari Modal Pembayaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_proof'])) {
    $code = htmlspecialchars($_POST['order_code']);
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === 0) {
        $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
        $filename = "PROOF_" . $code . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($_FILES['proof']['tmp_name'], "uploads/" . $filename)) {
            $db->query("UPDATE orders SET payment_proof = '$filename', status = 'Diproses' WHERE order_code = '$code'");
            
            echo "<script>
                window.location.href = 'track.php?code=" . $code . "&success=1';
            </script>";
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Secure Checkout - MabaStore</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50 md:bg-[#f4f4f7] text-sm text-gray-800 tracking-tight font-sans selection:bg-[#dff6f9] selection:text-[#024a54]"> 

    <div class="max-w-5xl mx-auto flex flex-col md:p-8 w-full">
        
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white px-5 py-6 md:p-8 md:rounded-3xl border-b md:border border-gray-200 md:border-gray-100 md:shadow-xs gap-4 w-full md:mb-6">
            <div>
                <span class="text-xxs font-extrabold uppercase tracking-widest text-[#06b7d2] bg-[#f0fbfd] px-2.5 py-1 rounded-md">Secure Checkout</span>
                <h1 class="text-2xl md:text-3xl font-black text-gray-900 mt-2">Penyelesaian Pesanan</h1>
            </div>
            <a href="products.php" class="text-xs font-bold text-gray-400 hover:text-[#06b7d2] transition flex items-center gap-1">← Kembali ke halaman produk</a>
        </header>

        <?php if(empty($items) && !$show_modal): ?>
            <div class="bg-white p-12 md:rounded-3xl text-center border-b md:border border-gray-200 md:border-gray-100 md:shadow-xs">
                <p class="text-gray-400 italic">Keranjang belanja kosong. Silakan pilih produk maba terlebih dahulu.</p>
                <a href="products.php" class="inline-block mt-6 bg-gray-900 hover:bg-gray-800 text-white px-6 py-3 rounded-2xl font-bold text-xs transition">Lihat Katalog Produk</a>
            </div>
        <?php elseif(!$show_modal): ?>
            
            <div class="flex flex-col md:flex-row md:gap-6 w-full items-start pb-24 md:pb-0">
                
                <form method="POST" class="w-full md:w-2/3 order-2 md:order-1 flex flex-col">
                    <div class="bg-white px-5 py-6 md:p-8 md:rounded-3xl border-b md:border border-gray-200 md:border-gray-100 md:shadow-xs space-y-6">
                        <h2 class="text-base md:text-lg font-extrabold text-gray-900 border-b border-gray-50 pb-3">📋 Informasi Pengiriman & Identitas</h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 md:gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Nama Lengkap</label>
                                <input type="text" name="name" required placeholder="Nama Anda" class="w-full bg-gray-50 border border-gray-200 md:border-gray-100 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-[#06b7d2] focus:bg-white transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Departemen / Jurusan</label>
                                <input type="text" name="department" required placeholder="Contoh: Sistem Informasi" class="w-full bg-gray-50 border border-gray-200 md:border-gray-100 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-[#06b7d2] focus:bg-white transition-all">
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Nomor WhatsApp Aktif</label>
                            <input type="text" name="phone" required placeholder="Contoh: 08123456789" class="w-full bg-gray-50 border border-gray-200 md:border-gray-100 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-[#06b7d2] focus:bg-white transition-all">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Alamat Lengkap / Nomor Kamar</label>
                            <textarea name="address" required rows="3" placeholder="Tulis alamat secara detail..." class="w-full bg-gray-50 border border-gray-200 md:border-gray-100 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-[#06b7d2] focus:bg-white transition-all resize-none"></textarea>
                        </div>
                    </div>

                    <div class="bg-white px-5 py-6 md:p-0 md:mt-6 border-b md:border-0 border-gray-200">
                        <button type="submit" name="submit_order" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-bold py-4 rounded-xl md:rounded-2xl shadow-md transition tracking-wide text-sm cursor-pointer">
                            Konfirmasi & Proses Pembayaran →
                        </button>
                    </div>
                </form>

                <div class="w-full md:w-1/3 bg-white px-5 py-6 md:p-6 md:rounded-3xl border-b md:border border-gray-200 md:border-gray-100 md:shadow-xs flex flex-col justify-between h-fit space-y-6 order-1 md:order-2">
                    <div>
                        <h2 class="text-sm md:text-base font-extrabold text-gray-900 border-b border-gray-50 pb-3">🛒 Item Belanjaan</h2>
                        
                        <div class="divide-y divide-gray-50 max-h-60 overflow-y-auto pr-1">
                            <?php foreach($items as $it): ?>
                                <div class="py-3 flex justify-between items-center text-xs">
                                    <div class="max-w-[70%]">
                                        <p class="font-bold text-gray-800 truncate"><?= htmlspecialchars($it['name']); ?></p>
                                        <?php if(!empty($it['selected_size'])): ?>
                                            <span class="inline-block bg-[#f0fbfd] border border-[#06b7d2]/20 text-[#06b7d2] font-black text-[9px] px-1.5 py-0.5 rounded-md mt-0.5 mb-0.5">Size: <?= htmlspecialchars($it['selected_size']); ?></span>
                                        <?php endif; ?>
                                        <p class="text-xxs text-gray-400 mt-0.5"><?= $it['qty']; ?>x @ Rp <?= number_format($it['price'], 0, ',', '.'); ?></p>
                                    </div>
                                    <p class="font-bold text-gray-900">Rp <?= number_format($it['subtotal'], 0, ',', '.'); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 space-y-2">
                        <div class="flex justify-between text-xs text-gray-400">
                            <span>Subtotal Belanja</span>
                            <span>Rp <?= number_format($total_price, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-50">
                            <span class="text-xs font-bold text-gray-900">Total Tagihan:</span>
                            <span class="text-xl font-black text-[#06b7d2]">Rp <?= number_format($total_price, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

            </div>

        <?php endif; ?>
  
   <div id="paymentModal" class="fixed inset-0 bg-gray-900/60 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-2xl md:rounded-3xl overflow-hidden shadow-2xl transition-all border border-gray-100 flex flex-col max-h-[90vh]">
            
            <div class="bg-gray-950 p-5 md:p-6 text-white text-center shrink-0">
                <h3 class="text-base md:text-lg font-black tracking-tight">🔒 Transaksi Terkunci Aman</h3>
                <p class="text-[10px] md:text-xxs text-gray-400 mt-1 uppercase tracking-widest font-bold">Langkah Terakhir: Verifikasi Pembayaran</p>
            </div>
            
            <form method="POST" action="checkout.php" enctype="multipart/form-data" class="p-5 md:p-8 space-y-5 overflow-y-auto">
                <input type="hidden" name="order_code" id="modal_order_code">
                
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 flex flex-col gap-2 text-center shadow-3xs">
                    <span class="text-[11px] text-gray-400 font-bold uppercase tracking-wider block">Total Wajib Ditransfer</span>
                    <span id="modal_total_display" class="text-3xl font-black text-[#06b7d2] tracking-tight"></span>
                    <div class="mt-0.5">
                        <span class="text-[9px] font-extrabold text-amber-600 bg-amber-50 border border-amber-200/60 px-2.5 py-1 rounded-md uppercase tracking-wider inline-block">Menunggu Bukti Transfer</span>
                    </div>
                </div>

                <div class="pt-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-2 text-center">Tujuan Pembayaran (BCA)</span>
                    <div class="bg-[#f0fbfd] border border-[#06b7d2]/30 rounded-xl p-5 text-center">
                        <p class="text-[11px] font-bold text-[#06b7d2] uppercase tracking-widest">Bank Central Asia (BCA)</p>
                        <p class="text-2xl font-mono font-black text-gray-900 tracking-widest mt-1.5 select-all">003-142-7109</p>
                        <p class="text-[11px] text-gray-500 font-medium mt-1">A/N MUHAMAD FARREL RIZKY ALDOVA</p>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 text-center">Upload Bukti Transfer *</label>
                    <input type="file" name="proof" required accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#f0fbfd] file:text-[#06b7d2] hover:file:bg-[#dff6f9] cursor-pointer border border-gray-200 rounded-xl p-1 bg-gray-50">
                </div>

                <button type="submit" name="upload_proof" class="w-full bg-[#06b7d2] hover:bg-[#0594a8] text-white font-bold py-3.5 rounded-xl shadow-md transition text-sm cursor-pointer mt-2">
                    Kirim Bukti Pembayaran Resmi
                </button>
                <p class="text-center text-[10px] text-red-500 font-semibold italic mt-2 px-2">⚠️ Selesaikan langkah ini. Pembayaran otomatis terkunci di halaman ini jika ditutup.</p>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        <?php if($show_modal): ?>
            document.getElementById('modal_order_code').value = '<?= $modal_code; ?>';
            document.getElementById('modal_total_display').innerText = 'Rp ' + parseInt(<?= $modal_total; ?>).toLocaleString('id-ID');
            document.getElementById('paymentModal').classList.remove('hidden');
        <?php endif; ?>
    });
    </script>
</body>
</html>