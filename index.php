<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Ambil Gelombang Batch PO yang Sedang Aktif
$batch_query = "SELECT * FROM batches WHERE is_active = 1 LIMIT 1";
$batch_res = $db->query($batch_query);
$active_batch = $batch_res->fetch_assoc();

// Ambil 3 Produk Terbaru untuk Efek Animasi Slideshow di Hero Banner
$hero_prod_query = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as main_image FROM products p ORDER BY p.id DESC LIMIT 3";
$hero_prod_res = $db->query($hero_prod_query);
$hero_products = [];
while ($row = $hero_prod_res->fetch_assoc()) {
    $hero_products[] = $row;
}

// Ambil 4 Atribut Terbaru untuk Etalase Rekomendasi Utama
$prod_query = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as main_image FROM products p ORDER BY p.id DESC LIMIT 4";
$products = $db->query($prod_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MabaStore — Atribut & Perlengkapan Resmi Mahasiswa Baru</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .drawer-open { overflow: hidden; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#f4f4f7] text-gray-900 font-sans antialiased selection:bg-[#dff6f9] selection:text-[#024a54] pb-12 md:pb-0">

    <nav class="bg-white/80 backdrop-blur-md border-b border-gray-200/60 py-3.5 sm:py-4 px-4 sm:px-8 md:px-16 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex flex-row justify-between items-center w-full gap-2">
            <a href="index.php" class="text-sm font-black tracking-tight flex items-center gap-2 text-gray-950 shrink-0">
                <div class="w-7 h-7 bg-gray-950 rounded-md flex items-center justify-center text-white text-xs shadow-3xs">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <span class="hidden sm:block">Maba<span class="text-[#06b7d2]">Store.</span></span>
            </a>
            
            <div class="flex items-center gap-4 sm:gap-6 md:gap-10 font-bold text-[11px] sm:text-xs text-gray-400">
                <a href="index.php" class="text-gray-950 border-b-2 border-gray-950 pb-1 font-black transition-all">Home</a>
                <a href="products.php" class="hover:text-[#06b7d2] hover:scale-105 transition-all">Katalog</a>
                <a href="track.php" class="hover:text-[#06b7d2] hover:scale-105 transition-all">Lacak</a>
                
                <button onclick="toggleCartDrawer()" class="hover:text-[#06b7d2] transition-all cursor-pointer relative ml-1 sm:ml-2 shrink-0">
                    <i class="fa-solid fa-bag-shopping text-sm sm:text-base text-gray-800"></i>
                    <span class="nav-cart-counter absolute -top-1.5 -right-2 bg-[#06b7d2] text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center scale-80"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
                </button>
            </div>
        </div>
    </nav>

    <header class="max-w-7xl mx-auto px-4 sm:px-8 pt-6 md:pt-10">
        <div class="bg-white rounded-3xl border border-gray-200/70 overflow-hidden relative shadow-3xs p-6 sm:p-12 md:p-16">
            <div class="absolute inset-0 opacity-[0.015] bg-[linear-gradient(to_right,#000_1px,transparent_1px),linear-gradient(to_bottom,#000_1px,transparent_1px)] bg-[size:24px_24px] pointer-events-none"></div>
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center relative z-10">
                
                <div class="lg:col-span-7 flex flex-col justify-between h-full space-y-8 lg:space-y-12 order-2 lg:order-1">
                    <div class="space-y-6">
                        <div>
                            <?php if($active_batch): ?>
                                <div class="inline-flex items-center gap-2 bg-[#f0fbfd] border border-[#dff6f9] px-3 py-1.5 rounded-full">
                                    <span class="w-2 h-2 rounded-full bg-[#06b7d2] animate-pulse"></span>
                                    <span class="text-[10px] md:text-xs font-black text-[#0594a8] uppercase tracking-wider">Pre-Order Active: <?= htmlspecialchars($active_batch['batch_name']); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="inline-flex items-center gap-2 bg-gray-50 border border-gray-100 px-3 py-1.5 rounded-full">
                                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                    <span class="text-[10px] md:text-xs font-black text-gray-500 uppercase tracking-wider">Pre-Order Gelombang Ditutup</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight text-gray-950 leading-[1.05]">
                            Atribut Resmi.<br>Standardisasi Sempurna.
                        </h1>
                        <p class="text-xs sm:text-sm md:text-base font-medium text-gray-400 max-w-lg leading-relaxed">
                            Sistem penyediaan perlengkapan pre-order resmi mahasiswa baru secara kolektif terpusat. Bahan premium yang terstandardisasi sesuai dengan regulasi universitas.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 border-t border-gray-100 pt-6">
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="products.php" class="bg-gray-950 hover:bg-gray-800 text-white font-bold text-xs px-6 py-3.5 rounded-xl transition shadow-sm flex items-center gap-1.5">
                                Jelajahi Katalog Atribut <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                            <a href="track.php" class="bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 font-bold text-xs px-5 py-3.5 rounded-xl transition">
                                Lacak Status Invoice
                            </a>
                        </div>
                        <div class="flex gap-6 md:gap-10 border-l border-gray-100 sm:pl-8">
                            <div>
                                <span class="block text-xl md:text-2xl font-black text-gray-950 tracking-tight">100%</span>
                                <span class="block text-[9px] font-bold uppercase tracking-widest text-gray-400 mt-0.5">Lolos Verifikasi</span>
                            </div>
                            <div>
                                <span class="block text-xl md:text-2xl font-black text-gray-950 tracking-tight">Premium</span>
                                <span class="block text-[9px] font-bold uppercase tracking-widest text-gray-400 mt-0.5">Kualitas Bahan</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 w-full flex justify-center items-center order-1 lg:order-2">
                    <div class="relative w-full max-w-[320px] sm:max-w-[360px] lg:max-w-none aspect-[4/5] bg-gray-50 rounded-2xl border border-gray-100 shadow-3xs overflow-hidden group/hero-img">
                        
                        <?php if(!empty($hero_products)): ?>
                            <?php foreach($hero_products as $index => $hp): ?>
                                <div class="hero-slide absolute inset-0 transition-opacity duration-1000 ease-in-out <?= $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' ?>">
                                    
                                    <img src="uploads/<?= !empty($hp['main_image']) ? $hp['main_image'] : 'placeholder.jpg'; ?>" 
                                         class="hero-img-el w-full h-full object-cover transition-transform duration-[5000ms] ease-out <?= $index === 0 ? 'scale-105' : 'scale-100' ?>" 
                                         alt="<?= htmlspecialchars($hp['name']); ?>" 
                                         onerror="this.src='uploads/placeholder.jpg'">
                                    
                                    <div class="absolute bottom-4 left-4 right-4 bg-white/80 backdrop-blur-md p-3.5 rounded-xl border border-white/40 shadow-xs flex justify-between items-center transition-all">
                                        <div class="min-w-0 pr-2">
                                            <span class="text-[9px] font-black text-[#06b7d2] uppercase tracking-widest block">Koleksi Teratas</span>
                                            <h3 class="text-xs font-black text-gray-950 truncate mt-0.5"><?= htmlspecialchars($hp['name']); ?></h3>
                                        </div>
                                        <a href="detail.php?id=<?= $hp['id']; ?>" class="bg-gray-950 hover:bg-gray-900 text-white text-[10px] font-black px-3 py-2 rounded-lg transition shadow-3xs shrink-0">
                                            Lihat
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center p-8 text-center bg-gray-50">
                                <i class="fa-solid fa-shirt text-4xl text-gray-200 mb-2"></i>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Belum Ada Atribut</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </header>

    <section class="max-w-7xl mx-auto px-4 sm:px-8 mt-12 mb-4">
        <p class="text-center text-[10px] font-black text-gray-400 uppercase tracking-widest mb-8">In Official Collaboration With</p>
        <div class="flex flex-wrap justify-center items-center gap-8 md:gap-16 opacity-80">
            <img src="uploads/tdc.png" alt="Partner 1" class="h-10 md:h-14 object-contain grayscale hover:grayscale-0 hover:scale-105 transition-all duration-300" onerror="this.src='https://via.placeholder.com/150x50/f4f4f7/cccccc?text=LOGO+KAMPUS'">
            <img src="uploads/algatra.png" alt="Partner 2" class="h-10 md:h-14 object-contain grayscale hover:grayscale-0 hover:scale-105 transition-all duration-300" onerror="this.src='https://via.placeholder.com/150x50/f4f4f7/cccccc?text=BEM+FAKULTAS'">
            <!-- <img src="uploads/logo3.png" alt="Partner 3" class="h-10 md:h-14 object-contain grayscale hover:grayscale-0 hover:scale-105 transition-all duration-300" onerror="this.src='https://via.placeholder.com/150x50/f4f4f7/cccccc?text=HIMA+JURUSAN'">
            <img src="uploads/logo4.png" alt="Partner 4" class="h-10 md:h-14 object-contain grayscale hover:grayscale-0 hover:scale-105 transition-all duration-300" onerror="this.src='https://via.placeholder.com/150x50/f4f4f7/cccccc?text=VENDOR+KAIN'"> -->
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-8 py-12 md:py-16 border-t border-gray-100/60 mt-8">
        <div class="flex justify-between items-end mb-6 md:mb-8">
            <div>
                <span class="text-[10px] font-black text-[#06b7d2] uppercase tracking-widest block mb-1">Rekomendasi Wajib</span>
                <h2 class="text-xl md:text-2xl font-black tracking-tight text-gray-950">Atribut Pre-Order Terbaru</h2>
            </div>
            <a href="products.php" class="text-xs font-bold text-gray-400 hover:text-[#06b7d2] transition flex items-center gap-1">Lihat semua produk →</a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-6 w-full">
            <?php if($products && $products->num_rows > 0): ?>
                <?php while($row = $products->fetch_assoc()): ?>
                    <div class="bg-white rounded-2xl border border-gray-200/70 p-2.5 sm:p-4 flex flex-col justify-between shadow-3xs group transition-all duration-300 hover:shadow-xs">
                        <div class="space-y-3">
                            <div class="aspect-[4/5] rounded-xl overflow-hidden bg-gray-50 border border-gray-100 relative">
                                <img src="uploads/<?= !empty($row['main_image']) ? $row['main_image'] : 'placeholder.jpg'; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="<?= htmlspecialchars($row['name']); ?>" onerror="this.src='uploads/placeholder.jpg'">
                                <span class="absolute top-2 left-2 bg-white/90 backdrop-blur-xs border border-gray-100 text-[8px] font-black px-2 py-0.5 rounded text-gray-500 uppercase tracking-wider shadow-3xs"><?= $row['type']; ?></span>
                            </div>
                            <div class="px-1 min-w-0">
                                <h3 class="font-bold text-gray-900 text-xs sm:text-sm truncate group-hover:text-[#06b7d2] transition-colors" title="<?= htmlspecialchars($row['name']); ?>"><?= htmlspecialchars($row['name']); ?></h3>
                                <p class="text-[#06b7d2] font-black text-xs sm:text-sm mt-1">Rp<?= number_format($row['price'], 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <div class="mt-4 px-1">
                            <a href="detail.php?id=<?= $row['id']; ?>" class="w-full bg-gray-50 hover:bg-[#06b7d2] hover:text-white border border-gray-200/60 text-gray-700 font-bold text-center py-2 rounded-xl text-[11px] block transition-all shadow-3xs">
                                Lihat Detail Atribut
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-2 md:col-span-4 bg-white border border-gray-200/60 p-12 text-center rounded-2xl">
                    <p class="text-gray-400 italic">Belum ada komoditas perlengkapan yang dimasukkan ke etalase.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-8 pb-12 md:pb-16">
        <div class="bg-white border border-gray-200/70 rounded-3xl p-6 sm:p-10 md:p-12 shadow-3xs">
            <div class="mb-10">
                <span class="text-[10px] font-black text-[#06b7d2] uppercase tracking-widest block mb-1">Panduan Pengguna</span>
                <h2 class="text-xl md:text-2xl font-black tracking-tight text-gray-950">Alur & Tutorial Pemesanan Produk</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 relative">
                <div class="space-y-3 relative group">
                    <div class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight flex items-baseline gap-1">
                        01<span class="text-[#06b7d2] text-sm">.</span>
                    </div>
                    <h4 class="text-xs sm:text-sm font-black text-gray-950 uppercase tracking-wide">Pilih Produk</h4>
                    <p class="text-xs text-gray-400 font-medium leading-relaxed">
                        Cari atribut reguler atau paket Bundling. Pastikan Anda memeriksa panduan gambar *size chart* pada setiap detail barang sebelum menekan tombol simpan keranjang.
                    </p>
                </div>

                <div class="space-y-3 relative group">
                    <div class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight flex items-baseline gap-1">
                        02<span class="text-[#06b7d2] text-sm">.</span>
                    </div>
                    <h4 class="text-xs sm:text-sm font-black text-gray-950 uppercase tracking-wide">Isi Form Website</h4>
                    <p class="text-xs text-gray-400 font-medium leading-relaxed">
                        Akses halaman keranjang lalu lanjutkan ke formulir *checkout*. Masukkan identitas berupa nama, jurusan lengkap, serta nomor WhatsApp aktif sebagai data kami.
                    </p>
                </div>

                <div class="space-y-3 relative group">
                    <div class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight flex items-baseline gap-1">
                        03<span class="text-[#06b7d2] text-sm">.</span>
                    </div>
                    <h4 class="text-xs sm:text-sm font-black text-gray-950 uppercase tracking-wide">Amankan Pembayaran</h4>
                    <p class="text-xs text-gray-400 font-medium leading-relaxed">
                        Lakukan transfer dana ke nomor rekening bank resmi BCA yang tertera pada modul sistem, lalu segera unggah file foto bukti resi transaksi transfer Anda untuk divalidasi oleh admin.
                    </p>
                </div>

                <div class="space-y-3 relative group">
                    <div class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight flex items-baseline gap-1">
                        04<span class="text-[#06b7d2] text-sm">.</span>
                    </div>
                    <h4 class="text-xs sm:text-sm font-black text-gray-950 uppercase tracking-wide">Pantau Status Invoice</h4>
                    <p class="text-xs text-gray-400 font-medium leading-relaxed">
                        Salin kode invoice unik pesanan Anda dan gunakan menu *Lacak* untuk memantau proses verifikasi admin hingga jadwal pembagian batch kolektif atribut diumumkan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-8 pb-16 md:pb-24">
        <div class="text-center max-w-lg mx-auto mb-10 md:mb-12">
            <span class="text-[10px] font-black text-[#06b7d2] uppercase tracking-widest block mb-1">Sudut Pandang Mereka</span>
            <h2 class="text-xl md:text-3xl font-black tracking-tight text-gray-950">Ditinjau & Dipercaya Oleh Mahasiswa Berbagai Angkatan</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-gray-200/70 shadow-3xs flex flex-col justify-between">
                <p class="text-xs sm:text-sm font-medium text-gray-500 leading-relaxed italic">
                    "Sistem pre-ordernya sangat transparan dan efisien. Pelacakan kodenya *real-time* memudahkan koordinasi angkatan secara kolektif tanpa ada barang yang terselip."
                </p>
                <div class="flex items-center gap-3 mt-6 border-t border-gray-50 pt-4">
                    <div class="w-8 h-8 rounded-full bg-[#f0fbfd] flex items-center justify-center text-[#06b7d2] font-black text-xs">AG</div>
                    <div>
                        <h4 class="text-xs font-black text-gray-900">Akbar Galang</h4>
                        <span class="text-[10px] font-bold text-gray-400 block mt-0.5">Ketua Angkatan Mahasiswa Baru</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-gray-200/70 shadow-3xs flex flex-col justify-between">
                <p class="text-xs sm:text-sm font-medium text-gray-500 leading-relaxed italic">
                    "Kualitas kemeja resmi dan atributnya luar biasa tebal, ukurannya pas sesuai sizechart digital yang disediakan. Sangat merekomendasikan pembelian paket kombinasi bundle."
                </p>
                <div class="flex items-center gap-3 mt-6 border-t border-gray-50 pt-4">
                    <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 font-black text-xs">MN</div>
                    <div>
                        <h4 class="text-xs font-black text-gray-900">Mesa Natadenta</h4>
                        <span class="text-[10px] font-bold text-gray-400 block mt-0.5">Gubernur Badan Eksekutif Mahasiswa</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-gray-200/70 shadow-3xs flex flex-col justify-between">
                <p class="text-xs sm:text-sm font-medium text-gray-500 leading-relaxed italic">
                    "Terbantu sekali dengan integrasi konfirmasi otomatis ke WhatsApp Admin. Sebagai mahasiswa baru, alurnya terasa ringkas, jelas, dan tidak ribet sama sekali."
                </p>
                <div class="flex items-center gap-3 mt-6 border-t border-gray-50 pt-4">
                    <div class="w-8 h-8 rounded-full bg-amber-50 flex items-center justify-center text-amber-600 font-black text-xs">N</div>
                    <div>
                        <h4 class="text-xs font-black text-gray-900">Naila Syarifah</h4>
                        <span class="text-[10px] font-bold text-gray-400 block mt-0.5">Staf Koordinator Lapangan Ospek</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white border-t border-gray-200/60 pt-12 pb-6 px-4 sm:px-8 md:px-16">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 border-b border-gray-100">
            <div class="md:col-span-5 space-y-4">
                <a href="index.php" class="text-sm font-black tracking-tight flex items-center gap-2 text-gray-950">
                    <div class="w-7 h-7 bg-gray-950 rounded-md flex items-center justify-center text-white text-xs">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <span>Maba<span class="text-[#06b7d2]">Store.</span></span>
                </a>
                <p class="text-xs font-medium text-gray-400 max-w-sm leading-relaxed">
                    Platform distribusi perlengkapan terpusat untuk membantu menyukseskan pemenuhan kebutuhan penunjang aktivitas akademik mahasiswa secara aman dan terstandardisasi.
                </p>
            </div>

            <div class="md:col-span-3 space-y-3">
                <h5 class="text-[10px] font-black uppercase tracking-widest text-gray-950">Ecosystem</h5>
                <ul class="space-y-2 text-xs font-bold text-gray-400">
                    <li><a href="index.php" class="hover:text-[#06b7d2] transition">Halaman Utama</a></li>
                    <li><a href="products.php" class="hover:text-[#06b7d2] transition">Katalog Atribut</a></li>
                    <li><a href="track.php" class="hover:text-[#06b7d2] transition">Lacak Invoice Pesanan</a></li>
                </ul>
            </div>

            <div class="md:col-span-4 space-y-3">
                <h5 class="text-[10px] font-black uppercase tracking-widest text-gray-950">Support & Help</h5>
                <ul class="space-y-2 text-xs font-medium text-gray-400">
                    <li class="flex items-center gap-1.5 font-bold text-gray-800"><i class="fa-brands fa-whatsapp text-emerald-500 text-sm"></i> +62 812-3456-7890</li>
                    <li>Jam Layanan: 08.00 - 17.00 WIB</li>
                    <li class="text-[11px] italic text-red-400 font-semibold">* Atribut dikelola secara resmi sesuai batchPO aktif.</li>
                </ul>
            </div>
        </div>
        
        <div class="max-w-7xl mx-auto pt-6 flex flex-col sm:flex-row justify-between items-center gap-4 text-[10px] sm:text-[11px] text-gray-400 font-medium">
            <p>© 2026 MabaStore. All Rights Reserved.</p>
            <div class="flex gap-4">
            </div>
        </div>
    </footer>

    <div id="cart-backdrop" class="fixed inset-0 bg-black/40 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-300 z-50" onclick="toggleCartDrawer()"></div>
    
    <div id="cart-drawer" class="fixed top-0 right-0 bottom-0 w-full sm:w-[400px] bg-white border-l border-gray-200 shadow-2xl z-50 translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-xs font-black text-gray-950 uppercase tracking-widest">Keranjang Kamu</h3>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Daftar item pre-order siap checkout.</p>
            </div>
            <button onclick="toggleCartDrawer()" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-400 hover:text-gray-900 hover:bg-white flex items-center justify-center transition text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div id="drawer-items-container" class="flex-1 overflow-y-auto p-4 space-y-4">
            </div>

        <div class="p-6 border-t border-gray-100 bg-gray-50 space-y-4">
            <div class="flex justify-between items-center text-gray-950">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Estimasi</span>
                <span id="drawer-total" class="font-black text-base">Rp0</span>
            </div>
            <div class="grid grid-cols-1 gap-2">
                <a href="checkout.php" class="bg-gray-950 hover:bg-gray-800 text-center text-white font-bold text-xs py-3.5 rounded-xl transition tracking-wide block">Lanjutkan ke Formulir Order <i class="fa-solid fa-arrow-right text-[10px] ml-1.5"></i></a>
                <button onclick="toggleCartDrawer()" class="text-center text-xs text-gray-400 hover:text-gray-900 font-bold transition py-1 cursor-pointer">Kembali Belanja</button>
            </div>
        </div>
    </div>

    <script>
    // LOGIKA SLIDESHOW HERO BANNERS
    document.addEventListener("DOMContentLoaded", function() {
        const slides = document.querySelectorAll('.hero-slide');
        const images = document.querySelectorAll('.hero-img-el');
        let currentSlide = 0;

        if(slides.length > 1) {
            setInterval(() => {
                // Sembunyikan slide aktif saat ini
                slides[currentSlide].classList.remove('opacity-100', 'z-10');
                slides[currentSlide].classList.add('opacity-0', 'z-0');
                
                // Reset zoom (scale) gambar agar animasi bisa diulang
                images[currentSlide].classList.remove('scale-105');
                images[currentSlide].classList.add('scale-100');
                
                // Pindah ke index produk berikutnya
                currentSlide = (currentSlide + 1) % slides.length;
                
                // Tampilkan slide selanjutnya dengan fade in
                slides[currentSlide].classList.remove('opacity-0', 'z-0');
                slides[currentSlide].classList.add('opacity-100', 'z-10');
                
                // Beri jeda sangat kecil sebelum memulai efek zoom perlahan ke scale-105
                setTimeout(() => {
                    images[currentSlide].classList.remove('scale-100');
                    images[currentSlide].classList.add('scale-105');
                }, 50);

            }, 4500); // Berganti produk setiap 4.5 detik
        }
    });

    // LOGIKA KERANJANG BELANJA BAWAAN
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

    function fetchCartContents() {
        const container = document.getElementById('drawer-items-container');
        const totalLabel = document.getElementById('drawer-total');

        fetch('cart_action.php?action=get_summary')
        .then(res => res.json())
        .then(data => {
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
                container.innerHTML = `
                <div class="text-center py-12 text-gray-400 text-xs font-medium">
                    <i class="fa-solid fa-basket-shopping text-2xl mb-2 text-gray-200 block"></i>Keranjang belanja kosong.
                </div>`;
                totalLabel.innerText = 'Rp 0';
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