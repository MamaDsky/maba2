<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Sinkronisasi session cart global maba
}
require_once 'config/database.php';
$db = (new Database())->getConnection();

$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) { 
    die("Produk tidak ditemukan."); 
}

// Ambil Gambar Detail Galeri (Maksimal 3 Alternatif)
$images = $db->query("SELECT image_path FROM product_images WHERE product_id = $id LIMIT 3");

// Ambil Isi Komponen Bundle jika produk bertipe paket kombinasi
$bundle_items = [];
if ($product['type'] == 'bundle') {
    $bi_res = $db->query("SELECT p.id, p.name, p.available_sizes FROM bundle_relations br JOIN products p ON br.regular_product_id = p.id WHERE br.bundle_product_id = $id");
    while($row = $bi_res->fetch_assoc()) { 
        $bundle_items[] = $row; 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']); ?> — MabaStore</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .drawer-open { overflow: hidden; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#fafafa] text-gray-900 font-sans antialiased selection:bg-[#dff6f9] selection:text-[#024a54] pb-24 md:pb-0">

    <nav class="bg-white/70 backdrop-blur-md border-b border-gray-200/60 py-4 px-4 sm:px-8 md:px-16 flex justify-between items-center sticky top-0 z-50">
        <a href="index.php" class="text-sm font-black tracking-tight flex items-center gap-2 text-gray-950">
            <div class="w-6 h-6 bg-gray-950 rounded-md flex items-center justify-center text-white text-[10px]">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <span>Maba<span class="text-[#06b7d2]">Store.</span></span>
        </a>
        <div class="flex space-x-6 sm:space-x-8 font-bold text-xs text-gray-400 items-center">
            <a href="index.php" class="hover:text-[#06b7d2] transition">Home</a>
            <a href="products.php" class="hover:text-[#06b7d2] transition">Katalog</a>
            <a href="track.php" class="hover:text-[#06b7d2] transition">Lacak</a>
            
            <button onclick="toggleCartDrawer()" class="hover:text-[#06b7d2] transition cursor-pointer relative pt-0.5">
                <i class="fa-solid fa-bag-shopping text-sm text-gray-800"></i>
                <span class="nav-cart-counter absolute -top-1.5 -right-2 bg-[#06b7d2] text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center scale-80"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
            </button>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 sm:px-8 py-6 md:py-12">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden grid grid-cols-1 lg:grid-cols-12 gap-0 md:gap-4">
            
            <div class="lg:col-span-6 p-4 md:p-6 bg-gray-50/50 flex flex-col justify-center border-b lg:border-b-0 lg:border-r border-gray-200/80">
               <div class="flex space-x-3 overflow-x-auto snap-x snap-mandatory pb-2 no-scrollbar scroll-smooth">
                    <?php if($images->num_rows > 0): ?>
                        <?php while($img = $images->fetch_assoc()): ?>
                            <img src="uploads/<?= $img['image_path']; ?>" class="w-full aspect-[4/5] object-cover rounded-xl snap-center shrink-0 shadow-3xs border border-gray-200/40 bg-white" onerror="this.src='uploads/placeholder.jpg'">
                        <?php endwhile; ?>
                    <?php else: ?>
                        <img src="uploads/placeholder.jpg" class="w-full aspect-[4/5] object-cover rounded-xl snap-center shrink-0 border border-gray-200/40 bg-white">
                    <?php endif; ?>
                </div>
                <p class="text-center text-[10px] text-gray-400 font-bold tracking-wide mt-2 uppercase">
                    <i class="fa-solid fa-left-right mr-1"></i> Geser layar untuk melihat alternatif foto barang
                </p>

                <div class="grid grid-cols-3 gap-2 mt-6 pt-6 border-t border-gray-200/60 text-center">
                    <div class="p-2 bg-white rounded-xl border border-gray-100 shadow-3xs">
                        <i class="fa-solid fa-shirt text-[#06b7d2] text-xs mb-1 block"></i>
                        <span class="text-[9px] font-black text-gray-990 block uppercase">Bahan Premium</span>
                        <span class="text-[8px] text-gray-400 font-medium block">Standard Resmi</span>
                    </div>
                    <div class="p-2 bg-white rounded-xl border border-gray-100 shadow-3xs">
                        <i class="fa-solid fa-calendar-check text-emerald-600 text-xs mb-1 block"></i>
                        <span class="text-[9px] font-black text-gray-900 block uppercase">Ready Garansi</span>
                        <span class="text-[8px] text-gray-400 font-medium block">Tukar Ukuran</span>
                    </div>
                    <div class="p-2 bg-white rounded-xl border border-gray-100 shadow-3xs">
                        <i class="fa-solid fa-truck-fast text-amber-600 text-xs mb-1 block"></i>
                        <span class="text-[9px] font-black text-gray-900 block uppercase">Ambil Kolektif</span>
                        <span class="text-[8px] text-gray-400 font-medium block">Fakultas / Gelombang</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-6 p-5 sm:p-8 md:p-10 flex flex-col justify-between">
                <div class="space-y-6">
                    <div>
                        <span class="bg-gray-100 border border-gray-200 text-gray-500 text-[9px] font-black px-2.5 py-0.5 rounded-md uppercase tracking-wider inline-block">
                            <i class="fa-solid fa-tag text-[8px] mr-1"></i><?= $product['type']; ?>
                        </span>
                        <h1 class="text-xl md:text-2xl font-black text-gray-950 mt-3 tracking-tight leading-tight"><?= htmlspecialchars($product['name']); ?></h1>
                        <p class="text-xl font-black text-[#06b7d2] mt-2 tracking-tight">Rp<?= number_format($product['price'], 0, ',', '.'); ?></p>
                    </div>

                    <?php if($product['type'] == 'reguler' && !empty($product['available_sizes'])): 
                        $sizes = array_map('trim', explode(',', $product['available_sizes']));
                    ?>
                    <div class="space-y-2.5">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Pilih Ukuran Tersedia:</span>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($sizes as $sz): if($sz === '') continue; ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="product_size" value="<?= htmlspecialchars($sz); ?>" class="sr-only peer">
                                    <span class="px-3.5 py-2 text-xs font-bold rounded-xl border border-gray-200 bg-white text-gray-700 inline-block peer-checked:bg-[#06b7d2] peer-checked:text-white peer-checked:border-[#06b7d2] hover:bg-gray-50 transition-all shadow-3xs">
                                        <?= htmlspecialchars($sz); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($product['type'] == 'bundle' && !empty($bundle_items)): ?>
                    <div class="space-y-4 bg-gray-50 p-4 rounded-xl border border-gray-200/60">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Pilih Ukuran Isi Paket:</span>
                        <?php foreach($bundle_items as $b_item): 
                            if(empty($b_item['available_sizes'])) continue; 
                            $b_sizes = array_map('trim', explode(',', $b_item['available_sizes']));
                        ?>
                            <div class="space-y-2 bundle-size-group" data-item-name="<?= htmlspecialchars($b_item['name']); ?>">
                                <span class="text-xs font-bold text-gray-900 block"><?= htmlspecialchars($b_item['name']); ?></span>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach($b_sizes as $sz): if($sz === '') continue; ?>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="bundle_size_<?= $b_item['id']; ?>" value="<?= htmlspecialchars($sz); ?>" class="sr-only peer bundle-size-radio">
                                            <span class="px-3 py-1.5 text-[11px] font-bold rounded-lg border border-gray-200 bg-white text-gray-700 inline-block peer-checked:bg-[#06b7d2] peer-checked:text-white peer-checked:border-[#06b7d2] hover:bg-gray-50 transition-all shadow-3xs">
                                                <?= htmlspecialchars($sz); ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php 
                    if ($product['type'] == 'bundle' || ($product['type'] == 'reguler' && !empty($product['available_sizes']))): 
                    ?>
                    <div class="bg-white border border-gray-200 p-4 rounded-xl flex items-center justify-between gap-4 shadow-3xs">
                        <div class="min-w-0">
                            <span class="text-[10px] font-black text-gray-950 block uppercase tracking-wider">Panduan Ukuran (Sizechart)</span>
                            <span class="text-[11px] text-gray-400 font-medium block mt-0.5 truncate">Wajib cek ukuran badan sebelum checkout pesanan</span>
                        </div>
                        <?php if(!empty($product['sizechart_path'])): ?>
                            <button onclick="showSizechart('uploads/<?= $product['sizechart_path']; ?>')" class="bg-gray-950 hover:bg-gray-800 text-white text-xs px-4 py-2 rounded-lg font-bold transition shadow-3xs cursor-pointer shrink-0">
                                <i class="fa-solid fa-ruler-combined mr-1.5 text-[10px]"></i> Lihat Sizechart
                            </button>
                        <?php else: ?>
                            <button onclick="showSizechart('uploads/default_sizechart.jpg')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs px-4 py-2 rounded-lg font-bold transition shadow-3xs cursor-pointer shrink-0">
                                <i class="fa-solid fa-ruler-combined mr-1.5 text-[10px]"></i> Sizechart Standar
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-100 pt-5 space-y-5">
                        <div class="text-xs sm:text-sm text-gray-500 font-medium leading-relaxed">
                            <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Deskripsi Spesifikasi</span>
                            <?= nl2br(htmlspecialchars($product['description'])); ?>
                        </div>

                       <?php if(!empty($bundle_items)): ?>
                        <div class="bg-[#f0fbfd] p-4 rounded-xl border border-[#06b7d2]/30">
                            <span class="text-[10px] font-black text-[#06b7d2] uppercase tracking-widest block mb-2.5">
                                <i class="fa-solid fa-box-archive mr-1"></i> Komponen Isi Paket Kombinasi
                            </span>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <?php foreach($bundle_items as $item): ?>
                                    <li class="text-[#0594a8] text-xs flex items-center font-bold">
                                        <i class="fa-solid fa-circle-check text-[10px] text-[#06b7d2] mr-2 shrink-0"></i> 
                                        <span class="truncate"><?= htmlspecialchars($item['name']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50/40">
                            <details class="group p-3 [&_summary::-webkit-details-marker]:hidden cursor-pointer" open>
                                <summary class="flex items-center justify-between text-gray-900">
                                    <span class="text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5"><i class="fa-solid fa-circle-info text-[#06b7d2]"></i> Alur Sistem Pre-Order</span>
                                    <span class="transition duration-300 group-open:-rotate-180 text-gray-400 text-[10px]"><i class="fa-solid fa-chevron-down"></i></span>
                                </summary>
                                <p class="mt-2 leading-relaxed text-gray-500 text-[11px] font-medium border-t border-gray-200/60 pt-2">
                                    Atribut resmi dikelola terpusat. Setelah pembayaran berhasil dikonfirmasi via WhatsApp Admin, status orderan Anda akan diverifikasi secara *real-time* di halaman pelacakan. Estimasi pembagian batch mengikuti kalender ospek universitas.
                                </p>
                            </details>
                        </div>
                    </div>
                </div>

                <div class="hidden md:flex mt-8 pt-6 border-t border-gray-100 gap-3">
                    <a href="products.php" class="w-1/3 text-center py-3 border border-gray-200 text-gray-500 rounded-xl font-bold hover:bg-gray-50 hover:text-gray-900 transition text-xs flex items-center justify-center gap-1">
                        <i class="fa-solid fa-chevron-left text-[9px]"></i> Katalog
                    </a>
                    <button onclick="addToCart(<?= $product['id']; ?>, '<?= $product['type']; ?>')" class="w-2/3 py-3 bg-gray-950 hover:bg-gray-900 text-white rounded-xl font-bold shadow-xs transition text-xs flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-plus text-[10px]"></i> Masuk Keranjang
                </button>
                </div>
            </div>

        </div>
    </main>

    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 flex gap-3 items-center z-40 md:hidden shadow-[0_-4px_20px_rgba(0,0,0,0.03)]">
        <a href="products.php" class="w-12 h-12 border border-gray-200 text-gray-500 rounded-xl flex items-center justify-center transition shrink-0 bg-gray-50"><i class="fa-solid fa-arrow-left text-xs"></i></a>
        <button onclick="addToCart(<?= $product['id']; ?>, '<?= $product['type']; ?>')" class="flex-1 h-12 bg-gray-950 hover:bg-gray-900 text-white rounded-xl font-bold transition text-xs flex items-center justify-center gap-2 cursor-pointer">
            <i class="fa-solid fa-bag-shopping text-[11px]"></i> Masuk Keranjang
        </button>
    </div>

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
            <div class="text-center py-12 text-gray-400 text-xs font-medium">
                <i class="fa-solid fa-basket-shopping text-2xl mb-2 text-gray-200 block"></i>Keranjang belanja kosong.
            </div>
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
    window.cartCounterElementId = 'cart-counter'; 

    function showSizechart(url) {
        Swal.fire({
            imageUrl: url,
            imageAlt: 'Sizechart Atribut',
            showConfirmButton: false,
            showCloseButton: true,
            padding: '1.5rem',
            backdrop: `rgba(17, 24, 39, 0.7) backdrop-blur-sm`, 
            customClass: { 
                popup: 'rounded-3xl border border-gray-100 shadow-2xl bg-white w-[92%] md:w-[32rem] p-0',
                title: 'text-base md:text-lg font-black text-gray-950 uppercase tracking-wide pt-2',
                htmlContainer: 'text-[11px] text-gray-400 font-medium mb-4',
                image: 'rounded-xl border border-gray-100 shadow-3xs object-contain max-h-[60vh] md:max-h-[70vh] w-full mx-auto mt-2 mb-0 bg-gray-50',
                closeButton: 'text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-all focus:outline-none w-8 h-8 flex items-center justify-center mt-2 mr-2'
            }
        });
    }

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

    function addToCart(id, type) {
        let selectedSize = '';

        if (type === 'reguler') {
            const sizeRadio = document.querySelector('input[name="product_size"]:checked');
            if (document.querySelector('input[name="product_size"]')) {
                if (!sizeRadio) {
                    Swal.fire({ icon: 'warning', title: 'Pilih Ukuran!', text: 'Silakan tentukan ukuran atribut terlebih dahulu.', confirmButtonColor: '#06b7d2' });
                    return;
                }
                selectedSize = sizeRadio.value;
            }
        } else if (type === 'bundle') {
            let bundleSizes = [];
            let allSelected = true;
            const sizeGroups = document.querySelectorAll('.bundle-size-group');

            sizeGroups.forEach(group => {
                const itemName = group.getAttribute('data-item-name');
                const checkedRadio = group.querySelector('input[type="radio"]:checked');
                if (checkedRadio) {
                    bundleSizes.push(`${itemName}: ${checkedRadio.value}`);
                } else {
                    allSelected = false; 
                }
            });

            if (sizeGroups.length > 0 && !allSelected) {
                Swal.fire({ icon: 'warning', title: 'Pilih Lengkap!', text: 'Silakan lengkapi pilihan ukuran untuk setiap item dalam paket bundle.', confirmButtonColor: '#06b7d2' });
                return;
            }
            selectedSize = bundleSizes.join(', ');
        }

        let formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', id);
        formData.append('size', selectedSize);

        fetch('cart_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.querySelectorAll('.nav-cart-counter').forEach(el => el.innerText = data.total_items);
                toggleCartDrawer(); 
            }
        }).catch(err => console.error("Error add to bag:", err));
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