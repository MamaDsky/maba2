<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Inisialisasi Variabel Pelacakan
$order_code = isset($_GET['order_id']) ? trim(htmlspecialchars($_GET['order_id'])) : (isset($_GET['code']) ? trim(htmlspecialchars($_GET['code'])) : '');
$order_data = null;
$order_items = [];

// Eksekusi Pencarian Data Berdasarkan 'order_code' Sesuai Database
if (!empty($order_code)) {
    // 1. Ambil data utama invoice order dari tabel orders & batches
    $stmt = $db->prepare("SELECT o.*, b.batch_name FROM orders o LEFT JOIN batches b ON o.batch_id = b.id WHERE o.order_code = ?");
    if ($stmt) {
        $stmt->bind_param("s", $order_code);
        $stmt->execute();
        $order_data = $stmt->get_result()->fetch_assoc();

        if ($order_data) {
            // 2. Ambil semua item komoditas yang dibeli maba
            $item_stmt = $db->prepare("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            if ($item_stmt) {
                $item_stmt->bind_param("i", $order_data['id']);
                $item_stmt->execute();
                $item_res = $item_stmt->get_result();
                while ($row = $item_res->fetch_assoc()) {
                    $order_items[] = $row;
                }
            }
        }
    }
}

// Helper penentu level indeks tracking timeline
$status_steps = ['Diproses' => 1, 'Di-packing' => 2, 'Dikirim' => 3];
$current_step = ($order_data && isset($status_steps[$order_data['status']])) ? $status_steps[$order_data['status']] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MabaStore — Pelacakan Manifes Atribut Pre-Order</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .drawer-open { overflow: hidden; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#fafafa] text-gray-900 font-sans antialiased selection:bg-[#dff6f9] selection:text-[#024a54]">

     <nav class="bg-white/80 backdrop-blur-md border-b border-gray-200/60 py-3.5 sm:py-4 px-4 sm:px-8 md:px-16 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex flex-row justify-between items-center w-full gap-2">
            <a href="index.php" class="text-sm font-black tracking-tight flex items-center gap-2 text-gray-950 shrink-0">
                <div class="w-7 h-7 bg-gray-950 rounded-md flex items-center justify-center text-white text-xs shadow-3xs">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <span class="hidden sm:block">Maba<span class="text-[#06b7d2]">Store.</span></span>
            </a>
            
            <div class="flex items-center gap-4 sm:gap-6 md:gap-10 font-bold text-[11px] sm:text-xs text-gray-400">
                <a href="index.php" class="hover:text-[#06b7d2] hover:scale-105 transition-all">Home</a>
                <a href="products.php" class="hover:text-[#06b7d2] hover:scale-105 transition-all">Katalog</a>
                <a href="track.php" class="text-gray-950 border-b-2 border-gray-950 pb-1 font-black transition-all">Lacak</a>
                
                <button onclick="toggleCartDrawer()" class="hover:text-[#06b7d2] transition-all cursor-pointer relative ml-1 sm:ml-2 shrink-0">
                    <i class="fa-solid fa-bag-shopping text-sm sm:text-base text-gray-800"></i>
                    <span class="nav-cart-counter absolute -top-1.5 -right-2 bg-[#06b7d2] text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center scale-80"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
                </button>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 sm:px-8 py-10 md:py-16 space-y-8">
        
        <div class="bg-white border border-gray-200 rounded-2xl p-6 md:p-8 shadow-[0_2px_12px_rgba(0,0,0,0.01)] space-y-4">
            <div>
                <span class="text-[10px] font-black text-[#06b7d2] uppercase tracking-widest block">Logistik Engine Monitor</span>
                <h1 class="text-xl md:text-2xl font-black text-gray-950 tracking-tight mt-1">Pelacakan Manifes Atribut</h1>
                <p class="text-gray-400 text-xs font-medium mt-0.5">Masukkan kode transaksi unik (order code) Anda untuk memantau status pengerjaan kain atau paket bundle.</p>
            </div>

            <form action="track.php" method="GET" class="flex flex-col sm:flex-row gap-2 pt-2">
                <div class="relative flex-1">
                    <i class="fa-solid fa-receipt absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                    <input type="text" name="order_id" value="<?= htmlspecialchars($order_code); ?>" placeholder="Contoh: PO-20260517-5306" required
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-950 placeholder-gray-400 focus:outline-none focus:border-[#06b7d2] focus:bg-white transition-all">
                </div>
                <button type="submit" class="bg-gray-950 hover:bg-gray-900 text-white font-bold text-xs px-6 py-3 rounded-xl transition cursor-pointer flex items-center justify-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-[10px]"></i> Periksa Progres
                </button>
            </form>
        </div>

        <?php if (!empty($order_code)): ?>
            <?php if ($order_data): ?>
                
                <div class="bg-white border border-gray-200 rounded-2xl p-6 md:p-8 shadow-[0_2px_12px_rgba(0,0,0,0.01)] space-y-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-4">
                        <div>
                            <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest">Garis Waktu Distribusi</h2>
                            <p class="text-sm font-black text-gray-950 mt-0.5">Timeline Log Produksi Antrean</p>
                        </div>
                        <span class="text-[10px] font-black px-2.5 py-1 rounded-md bg-[#f0fbfd] text-[#0594a8] border border-[#06b7d2]/20 uppercase tracking-wider self-start sm:self-auto shadow-3xs">
                            <i class="fa-solid fa-clock-rotate-left mr-1"></i> Status: <?= htmlspecialchars($order_data['status']); ?>
                        </span>
                    </div>

                    <div class="relative flex flex-col md:flex-row justify-between gap-8 md:gap-4 px-2">
                        
                        <div class="hidden md:block absolute top-4 left-6 right-6 h-[2px] bg-gray-100 z-0">
                            <div class="h-full bg-[#06b7d2] transition-all duration-500" style="width: <?= (($current_step - 1) / 2) * 100; ?>%;"></div>
                        </div>

                        <div class="flex md:flex-col items-center md:text-center gap-4 md:gap-3 relative z-10 md:w-1/3">
                            <div class="block md:hidden absolute top-8 left-4 bottom-[-32px] w-[2px] <?= $current_step > 1 ? 'bg-[#06b7d2]' : 'bg-gray-100'; ?> z-0"></div>
                            <div class="w-8 h-8 rounded-lg font-black text-xs flex items-center justify-center border-2 transition-all <?= $current_step >= 1 ? 'bg-gray-950 border-gray-950 text-white shadow-xs' : 'bg-white border-gray-200 text-gray-300'; ?>">
                                <?php if($current_step > 1): ?><i class="fa-solid fa-check text-[10px]"></i><?php else: ?>01<?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs <?= $current_step >= 1 ? 'text-gray-950' : 'text-gray-300'; ?> tracking-tight">Antrean Diproses</h4>
                                <p class="text-[11px] text-gray-400 font-medium leading-tight mt-0.5">Nota terekam di lini pengerjaan</p>
                            </div>
                        </div>

                        <div class="flex md:flex-col items-center md:text-center gap-4 md:gap-3 relative z-10 md:w-1/3">
                            <div class="block md:hidden absolute top-8 left-4 bottom-[-32px] w-[2px] <?= $current_step > 2 ? 'bg-[#06b7d2]' : 'bg-gray-100'; ?> z-0"></div>
                            <div class="w-8 h-8 rounded-lg font-black text-xs flex items-center justify-center border-2 transition-all <?= $current_step >= 2 ? 'bg-[#06b7d2] border-[#06b7d2] text-white shadow-xs' : 'bg-white border-gray-200 text-gray-300'; ?>">
                                <?php if($current_step > 2): ?><i class="fa-solid fa-check text-[10px]"></i><?php else: ?>02<?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs <?= $current_step >= 2 ? 'text-gray-950' : 'text-gray-300'; ?> tracking-tight">Tahap Di-packing</h4>
                                <p class="text-[11px] text-gray-400 font-medium leading-tight mt-0.5">Atribut disortir masuk boks</p>
                            </div>
                        </div>

                        <div class="flex md:flex-col items-center md:text-center gap-4 md:gap-3 relative z-10 md:w-1/3">
                            <div class="w-8 h-8 rounded-lg font-black text-xs flex items-center justify-center border-2 transition-all <?= $current_step >= 3 ? 'bg-emerald-600 border-emerald-600 text-white shadow-xs animate-bounce' : 'bg-white border-gray-200 text-gray-300'; ?>">
                                03
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs <?= $current_step >= 3 ? 'text-emerald-600' : 'text-gray-300'; ?> tracking-tight">Siap Dikirim / Ambil</h4>
                                <p class="text-[11px] text-gray-400 font-medium leading-tight mt-0.5">Siap didistribusikan kolektif</p>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 md:p-6 shadow-[0_2px_12px_rgba(0,0,0,0.01)] md:col-span-5 space-y-4">
                        <div class="border-b border-gray-100 pb-2.5">
                            <h3 class="text-xs font-black text-gray-950 uppercase tracking-wider">Detail Manifes Pemesan</h3>
                        </div>
                        <div class="space-y-3 font-medium text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Nama Lengkap Mahasiswa</span>
                                <span class="text-gray-950 font-bold"><?= htmlspecialchars($order_data['customer_name']); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Fakultas / Departemen</span>
                                <span class="text-gray-950 font-bold"><?= htmlspecialchars($order_data['customer_department'] ?? 'Umum'); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Alokasi Gelombang Pre-Order</span>
                                <span class="text-[#06b7d2] font-bold"><i class="fa-solid fa-circle-nodes text-[9px] mr-1"></i><?= htmlspecialchars($order_data['batch_name'] ?? 'Kloter Aktif'); ?></span>
                            </div>
                            <?php if(!empty($order_data['receipt_number'])): ?>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Nomor Resi Distribusi</span>
                                <span class="text-gray-950 font-mono font-bold"><?= htmlspecialchars($order_data['receipt_number']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-2xl p-5 md:p-6 shadow-[0_2px_12px_rgba(0,0,0,0.01)] md:col-span-7 space-y-4">
                        <div class="border-b border-gray-100 pb-2.5">
                            <h3 class="text-xs font-black text-gray-950 uppercase tracking-wider">Komoditas Barang Di-Order</h3>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-56 overflow-y-auto pr-1 no-scrollbar space-y-2">
                            <?php foreach ($order_items as $item): ?>
                                <div class="flex justify-between items-center py-2 text-xs font-medium">
                                    <div class="min-w-0 pr-4">
                                        <h4 class="font-bold text-gray-950 truncate"><?= htmlspecialchars($item['product_name']); ?></h4>
                                        <span class="text-[10px] text-gray-400 font-mono block mt-0.5">Kuantitas: <?= $item['quantity']; ?>x</span>
                                    </div>
                                    <span class="text-gray-950 font-black shrink-0">Rp<?= number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="border-t border-gray-100 pt-3 flex justify-between items-center text-gray-950 font-black">
                            <span class="text-xs uppercase text-gray-400">Total Nominal Pembayaran</span>
                            <span class="text-sm text-[#06b7d2]">Rp<?= number_format($order_data['total_price'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="bg-[#f0fbfd] border border-[#06b7d2]/30 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[#dff6f9] flex items-center justify-center text-[#06b7d2] text-xs shrink-0"><i class="fa-solid fa-headset"></i></div>
                        <div>
                            <h4 class="text-xs font-black text-gray-900 uppercase tracking-wide">Butuh Bantuan Logistik?</h4>
                            <p class="text-[11px] text-gray-400 font-medium mt-0.5 leading-relaxed">Jika ada kendala pelacakan atau ingin merubah data ukuran kemeja kain, hubungi admin penanggung jawab gelombang.</p>
                        </div>
                    </div>
                    <?php 
                        $wa_msg = urlencode("Halo Admin MabaStore, saya ingin menanyakan status progres pemesanan dengan kode order: *" . $order_code . "*");
                    ?>
                    <a href="https://wa.me/6281234567890?text=<?= $wa_msg; ?>" target="_blank" class="w-full sm:w-auto bg-gray-950 hover:bg-gray-900 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 shrink-0 shadow-3xs">
                        <i class="fa-brands fa-whatsapp text-sm text-emerald-400"></i> Hubungi Logistik Admin
                    </a>
                </div>

            <?php else: ?>
                <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center text-gray-400 text-xs font-medium">
                    <i class="fa-solid fa-triangle-exclamation text-3xl text-gray-200 block mb-2"></i>
                    Kode transaksi <strong class="text-gray-800">"<?= htmlspecialchars($order_code); ?>"</strong> tidak ditemukan. <br>Pastikan Anda menginputkan susunan huruf kapital sesuai contoh (e.g., PO-20260517-5306).
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <div id="cart-backdrop" class="fixed inset-0 bg-black/40 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-300 z-50" onclick="toggleCartDrawer()"></div>
    <div id="cart-drawer" class="fixed top-0 right-0 bottom-0 w-full sm:w-[400px] bg-white border-l border-gray-200 shadow-2xl z-50 translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between">
        <div class="px-6 py-5 border-b border-b-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-xs font-black text-gray-950 uppercase tracking-widest">Keranjang Kamu</h3>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Daftar item pre-order siap checkout.</p>
            </div>
            <button onclick="toggleCartDrawer()" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-400 hover:text-gray-900 hover:bg-white flex items-center justify-center transition text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="drawer-items-container" class="flex-1 overflow-y-auto p-4 space-y-4"></div>
        <div class="p-6 border-t border-t-gray-100 bg-gray-50 space-y-4">
            <div class="flex justify-between items-center text-gray-950">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Estimasi</span>
                <span id="drawer-total" class="font-black text-base">Rp0</span>
            </div>
            <div class="grid grid-cols-1 gap-2">
                <a href="checkout.php" class="bg-gray-950 hover:bg-gray-900 text-center text-white font-bold text-xs py-3.5 rounded-xl transition tracking-wide block">Lanjutkan ke Formulir Order <i class="fa-solid fa-arrow-right text-[10px] ml-1.5"></i></a>
                <button onclick="toggleCartDrawer()" class="text-center text-xs text-gray-400 hover:text-gray-900 font-bold transition py-1 cursor-pointer">Kembali Belanja</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success') && urlParams.get('success') == '1') {
            Swal.fire({
                icon: 'success',
                title: 'Pembayaran Diterima!',
                text: 'Bukti pembayaran Anda sedang diverifikasi oleh admin. Terima kasih!',
                confirmButtonColor: '#06b7d2'
            });
        }
    </script>

    <script>
    function toggleCartDrawer() {
        const backdrop = document.getElementById('cart-backdrop');
        const drawer = document.getElementById('cart-drawer');
        
        if (drawer.classList.contains('translate-x-full')) {
            drawer.classList.remove('translate-x-full');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            document.body.classList.add('drawer-open');
            fetchCartContents(); 
        } else {
            drawer.classList.add('translate-x-full');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('drawer-open');
        }
    }

    // PERBAIKAN SCRIPT KERANJANG AGAR SINKRON DENGAN PRODUK & INDEX (Menambahkan + / - QTY)
    function fetchCartContents() {
        const container = document.getElementById('drawer-items-container');
        const totalLabel = document.getElementById('drawer-total');

        fetch('cart_action.php?action=get_summary')
        .then(res => {
            if (!res.ok) throw new Error("Network response error");
            return res.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text.trim()); 
                if (data.status === 'success' && data.items.length > 0) {
                    let html = '';
                    data.items.forEach(item => {
                        html += `
                        <div class="flex items-center gap-4 bg-white border border-gray-100 p-2.5 rounded-xl shadow-2xs">
                            <img src="uploads/${item.image || 'placeholder.jpg'}" class="w-14 h-14 object-cover rounded-lg bg-gray-50 border border-gray-100" onerror="this.src='uploads/placeholder.jpg'">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-950 text-xs truncate">${item.name}</h4>
                                
                                <div class="flex items-center gap-3 mt-1.5">
                                    <div class="flex items-center border border-gray-200 rounded-md bg-gray-50">
                                        <button onclick="updateDrawerQty('${item.cart_key}', 'decrease')" class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-950 hover:bg-gray-200 transition cursor-pointer text-[10px] rounded-l-md"><i class="fa-solid fa-minus"></i></button>
                                        
                                        <span class="w-6 text-center text-[10px] font-black text-gray-950">${item.qty}</span>
                                        
                                        <button onclick="updateDrawerQty('${item.cart_key}', 'increase')" class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-950 hover:bg-gray-200 transition cursor-pointer text-[10px] rounded-r-md"><i class="fa-solid fa-plus"></i></button>
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-medium tracking-wide">@ Rp${parseInt(item.price).toLocaleString('id-ID')}</span>
                                </div>
                                
                            </div>
                            <button onclick="updateDrawerQty('${item.cart_key}', 'remove')" class="w-7 h-7 rounded-lg border border-gray-100 text-gray-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition text-[11px] cursor-pointer shrink-0">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>`;
                    });
                    container.innerHTML = html;
                    totalLabel.innerText = 'Rp ' + parseInt(data.grand_total).toLocaleString('id-ID');
                } else {
                    container.innerHTML = `<div class="text-center py-12 text-gray-400 text-xs font-medium"><i class="fa-solid fa-basket-shopping text-2xl mb-2 text-gray-200 block"></i>Keranjang belanja kosong.</div>`;
                    totalLabel.innerText = 'Rp 0';
                }
            } catch (err) {
                container.innerHTML = `<div class="text-center py-12 text-xs text-gray-400 font-medium"><i class="fa-solid fa-triangle-exclamation text-xl mb-2 text-[#06b7d2] block"></i>Gagal memuat item, coba segarkan halaman.</div>`;
            }
        });
    }

    function updateDrawerQty(cartKey, actionType) {
        let formData = new FormData();
        formData.append('action', actionType);
        formData.append('cart_key', cartKey);

        fetch('cart_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.querySelectorAll('.nav-cart-counter').forEach(el => el.innerText = data.total_items);
                fetchCartContents(); 
            }
        });
    }
    </script>
</body>
</html>