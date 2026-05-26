<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Memastikan session aktif di baris paling atas untuk counter keranjang
}
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Ambil Gelombang Batch PO yang Sedang Aktif
$batch_query = "SELECT * FROM batches WHERE is_active = 1 LIMIT 1";
$batch_res = $db->query($batch_query);
$active_batch = $batch_res->fetch_assoc();

// Ambil 4 Atribut Terbaru untuk Etalase Rekomendasi Utama
$prod_query = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as main_image FROM products p ORDER BY p.id DESC LIMIT 4";
$products = $db->query($prod_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MabaStore — Atribut & Perlengkapan Resmi Mahasiswa Baru</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .drawer-open { overflow: hidden; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#fafafa] text-gray-900 font-sans antialiased selection:bg-cyan-100 selection:text-cyan-900">

    <div class="bg-[#06b7d2] text-white py-2 text-xs font-bold tracking-wider uppercase border-b border-[#05a3ba] overflow-hidden">
        <marquee scrollamount="5" class="w-full">
            <span class="mx-8"><i class="fa-solid fa-circle-nodes mr-2 text-[10px] text-cyan-100 animate-pulse"></i> Sistem Pre-Order Aktif: <span class="underline underline-offset-4 font-black"><?= $active_batch ? $active_batch['batch_name'] : 'Tidak Ada Batch Aktif'; ?></span></span>
            <span class="mx-8"><i class="fa-solid fa-shield-check mr-2 text-[10px] text-cyan-100"></i> Garansi 100% Lolos Verifikasi Atribut Ospek Kampus</span>
            <span class="mx-8"><i class="fa-solid fa-truck-fast mr-2 text-[10px] text-cyan-100"></i> Pantau Kesiapan Produksi Real-Time Melalui Menu Lacak Order</span>
        </marquee>
    </div>

    <nav class="bg-white/70 backdrop-blur-md border-b border-gray-200/60 py-4 px-6 md:px-16 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex flex-row justify-between items-center w-full">
            <a href="index.php" class="text-sm font-black tracking-tight flex items-center gap-2 text-gray-950 shrink-0">
                <div class="w-6 h-6 bg-gray-950 rounded-md flex items-center justify-center text-white text-[10px]">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <span>Maba<span class="text-[#06b7d2]">Store.</span></span>
            </a>
            
            <div class="flex items-center gap-6 sm:gap-8 md:gap-10 font-bold text-xs text-gray-400">
                <a href="index.php" class="text-gray-950 border-b-2 border-gray-950 pb-1 font-black transition-all">Home</a>
                <a href="products.php" class="hover:text-gray-950 hover:scale-102 transition-all">Katalog</a>
                <a href="track.php" class="hover:text-gray-950 hover:scale-102 transition-all">Lacak Order</a>
                
                <button onclick="toggleCartDrawer()" class="hover:text-gray-950 transition-all cursor-pointer relative pt-0.5 ml-2 shrink-0">
                    <i class="fa-solid fa-bag-shopping text-sm text-gray-800"></i>
                    <span class="nav-cart-counter absolute -top-1.5 -right-2 bg-[#06b7d2] text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center scale-80"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
                </button>
            </div>
        </div>
    </nav>

    <header class="max-w-7xl mx-auto px-4 sm:px-8 md:px-16 pt-10 md:pt-20 pb-16 md:pb-28">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 md:gap-16 items-center">
            <div class="lg:col-span-5 space-y-6 md:space-y-8">
                <span class="text-[10px] font-black text-[#06b7d2] uppercase tracking-widest block">Atribut Kelengkapan Kuliah</span>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-gray-950 tracking-tight leading-[1.05]">Persiapan ospek maba, tanpa perlu mengantre.</h1>
                <p class="text-gray-500 text-xs sm:text-sm leading-relaxed max-w-sm md:max-w-none font-medium">Penyediaan kemeja spesifikasi resmi, rok/celana bahan kain, atribut ospek fakultas, hingga paket bundle kombinasi secara kolektif terpusat.</p>
                <div class="pt-2 flex flex-wrap items-center gap-4">
                    <a href="products.php" class="bg-gray-950 hover:bg-gray-900 text-white font-bold text-xs px-6 py-3.5 rounded-xl transition shadow-3xs">Buka Katalog Produk</a>
                    <a href="#alur-section" class="text-xs font-bold text-gray-400 hover:text-gray-900 transition flex items-center gap-2">Pelajari mekanisme pembelian <i class="fa-solid fa-arrow-down text-[10px]"></i></a>
                </div>
            </div>

            <div class="lg:col-span-7 w-full">
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-200/80 px-4 py-3 flex items-center gap-1.5 shrink-0 select-none">
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-200 inline-block"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-200 inline-block"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-200 inline-block"></span>
                        <span class="text-[10px] text-gray-400 font-mono ml-2 truncate">Get_To_Know_Bapel</span>
                    </div>
                    <div class="aspect-video w-full bg-gray-950 relative">
                        <video class="w-full h-full object-cover" controls preload="metadata" poster="uploads/placeholder_video.jpg">
                            <source src="uploads/panduan_maba.mp4" type="video/mp4">
                        </video>
                    </div>
                </div>
                <p class="text-[11px] text-gray-400 font-medium mt-3 text-left md:text-center px-1">
                    <i class="fa-solid fa-circle-info mr-1.5 text-[#06b7d2]"></i> Tonton rekaman video cara menginput data ukuran pakaian agar tidak keliru saat batch dikirim.
                </p>
            </div>
        </div>
    </header>

    <section class="max-w-7xl mx-auto px-4 sm:px-8 md:px-16 py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-gray-200/80 p-5 rounded-xl flex items-start gap-4 shadow-3xs">
            <div class="w-9 h-9 bg-cyan-50/50 rounded-xl flex items-center justify-center text-[#06b7d2] text-xs shrink-0"><i class="fa-solid fa-shield-check"></i></div>
            <div>
                <h4 class="text-xs font-black text-gray-950 uppercase tracking-wide">Akurat Sesuai Aturan</h4>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Seluruh bentuk kerah kemeja, bahan kain, dan kelengkapan dasi dijamin lolos inspeksi panitia ospek.</p>
            </div>
        </div>
        <div class="bg-white border border-gray-200/80 p-5 rounded-xl flex items-start gap-4 shadow-3xs">
            <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 text-xs shrink-0"><i class="fa-solid fa-arrow-rotate-left"></i></div>
            <div>
                <h4 class="text-xs font-black text-gray-950 uppercase tracking-wide">Garansi Tukar Ukuran</h4>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Kekecilan atau kebesaran saat barang didistribusikan? Tenang, bisa ditukar dengan mudah dan cepat.</p>
            </div>
        </div>
        <div class="bg-white border border-gray-200/80 p-5 rounded-xl flex items-start gap-4 shadow-3xs">
            <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 text-xs shrink-0"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div>
                <h4 class="text-xs font-black text-gray-950 uppercase tracking-wide">Kombinasi Paket Bundle</h4>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Pilih paket lengkap (Atasan + Bawahan + Aksesoris) untuk menghemat pengeluaran maba.</p>
            </div>
        </div>
    </section>

    <main class="max-w-7xl mx-auto px-4 sm:px-8 md:px-16 py-16 md:py-24">
        <div class="flex justify-between items-end border-b border-gray-200/60 pb-4 mb-8">
            <div>
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Rekomendasi Utama</span>
                <h2 class="text-xl font-black text-gray-950 tracking-tight mt-1">Item Esensial Maba</h2>
            </div>
            <a href="products.php" class="text-xs font-bold text-[#06b7d2] hover:text-[#05a3ba] transition flex items-center gap-1 font-sans">Lihat Semua Katalog <i class="fa-solid fa-chevron-right text-[9px] ml-0.5"></i></a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
            <?php if($products->num_rows > 0): ?>
                <?php while($row = $products->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl border border-gray-200/80 overflow-hidden shadow-3xs flex flex-col justify-between group hover:border-gray-300 transition duration-300 p-1.5 sm:p-2">
                        <div class="relative aspect-square bg-[#fcfcfc] overflow-hidden rounded-lg border border-gray-100/40">
                            <img src="uploads/<?= !empty($row['image_path']) ? $row['image_path'] : 'placeholder.jpg'; ?>" class="w-full h-full object-cover group-hover:scale-102 transition duration-500" onerror="this.src='uploads/placeholder.jpg'">
                            <span class="absolute top-2 left-2 bg-gray-950 text-white text-[8px] font-black px-1.5 py-0.5 rounded-sm uppercase tracking-wide shadow-sm">
                                <?= $row['type']; ?>
                            </span>
                        </div>

                        <div class="p-2 sm:p-4 flex-1 flex flex-col justify-between space-y-3">
                            <div class="space-y-1">
                                <div class="flex justify-between items-center gap-1">
                                    <span class="text-[8px] md:text-[9px] text-emerald-600 font-black bg-emerald-50 px-1.5 py-0.5 rounded flex items-center gap-1">
                                        <i class="fa-solid fa-circle text-[4px]"></i> Ready PO
                                    </span>
                                    <span class="text-[10px] text-amber-500 font-bold flex items-center gap-0.5">
                                        <i class="fa-solid fa-star text-[8px]"></i> 5.0
                                    </span>
                                </div>
                                <h3 class="font-bold text-gray-950 text-xs sm:text-sm line-clamp-1 md:line-clamp-2 pt-0.5 leading-tight group-hover:text-[#06b7d2] transition" title="<?= htmlspecialchars($row['name']); ?>"><?= htmlspecialchars($row['name']); ?></h3>
                                <p class="text-xs sm:text-sm font-black text-[#06b7d2] pt-0.5">Rp<?= number_format($row['price'], 0, ',', '.'); ?></p>
                            </div>

                            <div class="pt-0.5">
                                <a href="detail.php?id=<?= $row['id']; ?>" class="w-full text-center bg-gray-950 hover:bg-gray-800 text-white font-bold text-[10px] sm:text-xs py-2 rounded-lg transition block shadow-3xs">
                                    Detail Atribut <i class="fa-solid fa-arrow-right text-[8px] ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200 text-gray-400 text-xs font-medium">
                    <i class="fa-solid fa-basket-shopping text-3xl text-gray-200 block mb-2"></i>Belum ada produk aktif terdaftar di etalase.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <section id="alur-section" class="border-t border-gray-200/60 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 md:px-16 py-16 md:py-24">
            <div class="text-left md:text-center max-w-xl md:mx-auto mb-16 space-y-2">
                <span class="text-[10px] font-black text-[#06b7d2] uppercase tracking-widest block">Mekanisme Transaksi</span>
                <h2 class="text-2xl font-black text-gray-950 tracking-tight">Prosedur Pengadaan Atribut</h2>
                <p class="text-gray-400 text-xs font-medium">Ikuti 4 alur baku berikut untuk menjamin ketersediaan kuota inventaris penjahit konveksi.</p>
            </div>

            <div class="relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-6">
                
                <div class="hidden lg:block absolute top-7 left-12 right-12 h-[2px] border-t-2 border-dashed border-gray-200/80 z-0"></div>

                <div class="bg-white border border-gray-200 rounded-xl p-5 relative z-10 flex flex-col justify-between space-y-4 hover:border-gray-300 transition shadow-[0_2px_8px_rgba(0,0,0,0.01)]">
                    <div class="block lg:hidden absolute top-12 left-9 bottom-[-32px] w-[2px] border-l-2 border-dashed border-gray-200 z-0"></div>
                    
                    <div class="flex justify-between items-center w-full">
                        <div class="w-10 h-10 rounded-xl bg-gray-950 text-white text-xs font-black flex items-center justify-center border-2 border-white shadow-xs shrink-0 relative z-10">01</div>
                        <span class="text-[9px] font-black text-[#06b7d2] bg-cyan-50 px-2 py-0.5 rounded-md uppercase tracking-wider">Step 1</span>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-gray-950 text-sm tracking-tight">Pilih Atribut & Sizechart</h4>
                        <p class="text-gray-400 text-[11px] leading-relaxed font-medium">Buka menu spesifikasi produk kemeja atau bundle, lalu cocokkan ukuran badan Anda pada tombol *Sizechart* interaktif.</p>
                    </div>
                </div>
                
                <div class="bg-white border border-gray-200 rounded-xl p-5 relative z-10 flex flex-col justify-between space-y-4 hover:border-gray-300 transition shadow-[0_2px_8px_rgba(0,0,0,0.01)]">
                    <div class="block lg:hidden absolute top-12 left-9 bottom-[-32px] w-[2px] border-l-2 border-dashed border-gray-200 z-0"></div>
                    
                    <div class="flex justify-between items-center w-full">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-950 text-xs font-black flex items-center justify-center border-2 border-white shadow-xs shrink-0 relative z-10">02</div>
                        <span class="text-[9px] font-black text-gray-400 bg-gray-100 px-2 py-0.5 rounded-md uppercase tracking-wider">Step 2</span>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-gray-950 text-sm tracking-tight">Isi Formulir & Transaksi</h4>
                        <p class="text-gray-400 text-[11px] leading-relaxed font-medium">Masukkan barang ke keranjang samping (*side bag*), lalu lengkapi kolom alamat rumah dan nomor WhatsApp untuk kiriman invoice nota.</p>
                    </div>
                </div>
                
                <div class="bg-white border border-gray-200 rounded-xl p-5 relative z-10 flex flex-col justify-between space-y-4 hover:border-gray-300 transition shadow-[0_2px_8px_rgba(0,0,0,0.01)]">
                    <div class="block lg:hidden absolute top-12 left-9 bottom-[-32px] w-[2px] border-l-2 border-dashed border-gray-200 z-0"></div>
                    
                    <div class="flex justify-between items-center w-full">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-950 text-xs font-black flex items-center justify-center border-2 border-white shadow-xs shrink-0 relative z-10">03</div>
                        <span class="text-[9px] font-black text-gray-400 bg-gray-100 px-2 py-0.5 rounded-md uppercase tracking-wider">Step 3</span>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-gray-950 text-sm tracking-tight">Antrean Meja Produksi</h4>
                        <p class="text-gray-400 text-[11px] leading-relaxed font-medium">Data pesanan Anda langsung direkap masuk ke list pengerjaan jahit massal vendor tepat setelah kloter gelombang resmi ditutup admin.</p>
                    </div>
                </div>
                
                <div class="bg-white border border-[#06b7d2]/40 rounded-xl p-5 relative z-10 flex flex-col justify-between space-y-4 hover:border-[#06b7d2]/80 transition shadow-[0_2px_12px_rgba(6,183,210,0.03)]">
                    <div class="flex justify-between items-center w-full">
                        <div class="w-10 h-10 rounded-xl bg-cyan-50 text-[#06b7d2] text-xs font-black flex items-center justify-center border-2 border-white shadow-xs shrink-0 relative z-10">04</div>
                        <span class="text-[9px] font-black text-cyan-700 bg-cyan-100 px-2 py-0.5 rounded-md uppercase tracking-wider">Verified</span>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-gray-950 text-sm tracking-tight">Pengiriman & Lacak PO</h4>
                        <p class="text-gray-400 text-[11px] leading-relaxed font-medium">Gunakan kode nota unik yang didapatkan untuk mengecek progres pemotongan kain hingga penyerahan kurir secara *real-time*.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section id="faq-section" class="border-t border-gray-200/60 bg-[#fafafa]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-20 md:py-28">
            <div class="mb-10">
                <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Prosedur & Aturan PO</h2>
                <p class="text-xl font-black text-gray-950 tracking-tight mt-1">Frequently Asked Questions</p>
            </div>

            <div class="space-y-2.5">
                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-3xs">
                    <details class="group p-4 [&_summary::-webkit-details-marker]:hidden cursor-pointer" open>
                        <summary class="flex items-center justify-between text-gray-950 font-bold text-xs sm:text-sm">
                            <span class="group-hover:text-[#06b7d2] transition">Bagaimana cara menentukan ukuran kemeja/rok yang pas?</span>
                            <span class="transition duration-300 group-open:-rotate-180 text-gray-400 text-xs"><i class="fa-solid fa-chevron-down"></i></span>
                        </summary>
                        <p class="mt-3 leading-relaxed text-gray-500 text-xs font-medium border-t border-gray-100 pt-3 max-w-2xl">
                            Anda wajib membuka tombol **Lihat Sizechart** yang berada di dalam setiap halaman detail produk sebelum menekan tombol keranjang belanja. Ukur lingkar dada dan panjang badan Anda menggunakan meteran kain untuk hasil paling akurat.
                        </p>
                    </details>
                </div>

                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-3xs">
                    <details class="group p-4 [&_summary::-webkit-details-marker]:hidden cursor-pointer">
                        <summary class="flex items-center justify-between text-gray-950 font-bold text-xs sm:text-sm">
                            <span class="group-hover:text-[#06b7d2] transition">Kapan barang pre-order saya akan mulai diproduksi dan dikirim?</span>
                            <span class="transition duration-300 group-open:-rotate-180 text-gray-400 text-xs"><i class="fa-solid fa-chevron-down"></i></span>
                        </summary>
                        <p class="mt-3 leading-relaxed text-gray-500 text-xs font-medium border-t border-gray-100 pt-3 max-w-2xl">
                            Sistem yang kami gunakan adalah *Pre-Order (PO) Kolektif*. Produksi massal akan berjalan serentak setelah masa gelombang penutupan batch yang sedang aktif berakhir. Estimasi pengerjaan berkisar 7-14 hari kerja, dan statusnya bisa Anda pantau via menu **Lacak Order**.
                        </p>
                    </details>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white border-t border-gray-200/80 font-sans">
        <div class="max-w-7xl mx-auto px-6 md:px-16 py-16 grid grid-cols-1 md:grid-cols-12 gap-10 md:gap-12">
            
            <div class="md:col-span-5 space-y-4">
                <a href="index.php" class="text-sm font-black tracking-tight flex items-center gap-2 text-gray-950 select-none">
                    <div class="w-6 h-6 bg-gray-950 rounded-md flex items-center justify-center text-white text-[10px]">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <span>Maba<span class="text-[#06b7d2]">Store.</span></span>
                </a>
                <p class="text-gray-400 text-xs leading-relaxed max-w-sm font-medium">
                    Pusat distribusi logistik dan merchandising perlengkapan resmi mahasiswa baru. Seluruh spesifikasi pakaian dikontrol ketat demi kelayakan atribut ospek universitas.
                </p>
                <div class="pt-2 flex flex-wrap gap-2 text-gray-400 text-[10px] font-black uppercase tracking-wider">
                    <span class="px-2.5 py-1 rounded-md border border-gray-100 bg-gray-50 flex items-center gap-1.5"><i class="fa-solid fa-shield text-[#06b7d2]"></i> Secure Gateway</span>
                    <span class="px-2.5 py-1 rounded-md border border-gray-100 bg-gray-50 flex items-center gap-1.5"><i class="fa-solid fa-rotate text-[#06b7d2]"></i> Size Warranty</span>
                </div>
            </div>

            <div class="md:col-span-3 space-y-3.5">
                <h4 class="text-[10px] font-black text-gray-950 uppercase tracking-widest border-l-2 border-[#06b7d2] pl-2">Navigasi Utama</h4>
                <ul class="space-y-2.5 text-xs font-bold text-gray-400">
                    <li><a href="index.php" class="hover:text-gray-950 hover:translate-x-1 transition-all inline-block">Halaman Utama (Home)</a></li>
                    <li><a href="products.php" class="hover:text-gray-950 hover:translate-x-1 transition-all inline-block">Katalog Lengkap Atribut</a></li>
                    <li><a href="track.php" class="hover:text-gray-950 hover:translate-x-1 transition-all inline-block">Sistem Pelacakan Order</a></li>
                </ul>
            </div>

            <div class="md:col-span-4 space-y-3.5">
                <h4 class="text-[10px] font-black text-gray-950 uppercase tracking-widest border-l-2 border-[#06b7d2] pl-2">Standardisasi Logistik</h4>
                <ul class="space-y-2.5 text-xs font-medium text-gray-400">
                    <li class="flex items-start gap-2.5">
                        <i class="fa-solid fa-check text-[#06b7d2] text-[10px] mt-0.5 shrink-0"></i>
                        <span>Pola potong jarum kemeja menggunakan kain TC Cotton premium resmi.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <i class="fa-solid fa-check text-[#06b7d2] text-[10px] mt-0.5 shrink-0"></i>
                        <span>Inspeksi Quality Control terpusat sebelum penyerahan kurir.</span>
                    </li>
                </ul>
            </div>

        </div>

        <div class="border-t border-gray-100 py-6 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50/60 select-none">
            &copy; <?= date('Y'); ?> MabaStore Platform. All rights reserved. Built with standard-engineered framework.
        </div>
    </footer>

    <div class="fixed bottom-6 right-6 z-40">
        <button onclick="toggleCartDrawer()" class="bg-gray-950 hover:bg-gray-800 text-white px-4 py-3 rounded-xl shadow-md flex items-center space-x-3 text-xs font-bold transition border border-gray-800 cursor-pointer">
            <i class="fa-solid fa-bag-shopping text-gray-300"></i>
            <span>Keranjang</span>
            <span id="cart-counter" class="bg-[#06b7d2] text-white px-1.5 py-0.5 rounded font-black text-[10px]"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
        </button>
    </div>

    <div id="cart-backdrop" class="fixed inset-0 bg-black/40 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-300 z-50" onclick="toggleCartDrawer()"></div>
    
    <div id="cart-drawer" class="fixed top-0 right-0 bottom-0 w-full sm:w-[400px] bg-white border-l border-gray-200 shadow-2xl z-50 translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between">
        <div class="px-6 py-5 border-b border-b-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-xs font-black text-gray-950 uppercase tracking-widest">Keranjang Kamu</h3>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Daftar item pre-order siap checkout.</p>
            </div>
            <button onclick="toggleCartDrawer()" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-400 hover:text-gray-900 hover:bg-white flex items-center justify-center transition text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div id="drawer-items-container" class="flex-1 overflow-y-auto p-4 space-y-4">
            </div>

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

    function fetchCartContents() {
        const container = document.getElementById('drawer-items-container');
        const totalLabel = document.getElementById('drawer-total');

        fetch('cart_action.php?action=get_summary')
        .then(res => res.text())
        .then(text => {
            try {
                const data = JSON.parse(text.trim()); 
                if (data.status === 'success' && data.items.length > 0) {
                    let html = '';
                    data.items.forEach(item => {
                        html += `
                        <div class="flex items-center gap-4 bg-white border border-gray-150 p-2.5 rounded-xl shadow-2xs">
                            <img src="uploads/${item.image || 'placeholder.jpg'}" class="w-14 h-14 object-cover rounded-lg bg-gray-50 border border-gray-100" onerror="this.src='uploads/placeholder.jpg'">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-950 text-xs truncate">${item.name}</h4>
                                <p class="text-[11px] text-gray-400 font-medium mt-0.5">${item.qty}x — Rp${parseInt(item.price).toLocaleString('id-ID')}</p>
                            </div>
                            <button onclick="updateDrawerQty(${item.id}, 'remove')" class="w-6 h-6 rounded-md border border-gray-100 text-gray-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition text-[10px] cursor-pointer">
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
            } catch (err) {
                container.innerHTML = `<div class="text-center py-12 text-xs text-gray-400 font-medium"><i class="fa-solid fa-triangle-exclamation text-xl mb-2 text-[#06b7d2] block"></i>Gagal memuat item.</div>`;
            }
        });
    }

    function updateDrawerQty(id, actionType) {
        let formData = new FormData();
        formData.append('action', actionType);
        formData.append('product_id', id);

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