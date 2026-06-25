<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Memastikan sinkronisasi session cart global aktif
}
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Logika Backend Filter
$filter_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';

$where_clause = " WHERE 1=1";
if (!empty($filter_type)) {
    $where_clause .= " AND p.type = '" . $db->real_escape_string($filter_type) . "'";
}

// PERUBAHAN: Menampilkan 8 produk per halaman
$limit = 8; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// Hitung total data berdasarkan filter untuk menentukan jumlah halaman
$total_res = $db->query("SELECT COUNT(*) as total FROM products p $where_clause");
$total_data = $total_res->fetch_assoc()['total'];
$pages = ceil($total_data / $limit);
if ($pages < 1) $pages = 1;

// Ambil data produk terfilter dengan batasan limit halaman
$query = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as main_image 
          FROM products p $where_clause 
          ORDER BY p.id DESC LIMIT $start, $limit";
$all_products = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MabaStore — Semua Koleksi Perlengkapan Resmi</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .drawer-open { overflow: hidden; }
        /* Menyembunyikan scrollbar bawaan browser pada filter x-axis mobile */
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
                <a href="index.php" class="hover:text-gray-950 hover:scale-105 transition-all">Home</a>
                <a href="products.php" class="text-gray-950 border-b-2 border-gray-950 pb-1 font-black transition-all">Katalog</a>
                <a href="track.php" class="hover:text-gray-950 hover:scale-105 transition-all">Lacak</a>
                
                <button onclick="toggleCartDrawer()" class="hover:text-gray-950 transition-all cursor-pointer relative ml-1 sm:ml-2 shrink-0">
                    <i class="fa-solid fa-bag-shopping text-sm sm:text-base text-gray-800"></i>
                    <span class="nav-cart-counter absolute -top-1.5 -right-2 bg-[#06b7d2] text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center scale-80"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
                </button>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-8 md:px-16 py-6 md:py-12">
        <div class="mb-6 md:mb-12 border-b border-gray-200/60 pb-6 md:pb-8 flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div>
                <span class="text-[10px] font-black text-[#06b7d2] uppercase tracking-widest block">Katalog Aset Atribut</span>
                <h1 class="text-xl md:text-3xl font-black text-gray-950 tracking-tight mt-1">Koleksi Perlengkapan</h1>
                <p class="text-gray-400 text-xs mt-1 font-medium">Cari kebutuhan ospek fakultas, paket kombinasi jualan merchant, dan kemeja wajib maba.</p>
            </div>
            
            <div class="w-full lg:w-auto overflow-x-auto no-scrollbar -mx-4 px-4 lg:mx-0 lg:px-0 shrink-0">
                <div class="bg-gray-100 p-1 rounded-xl flex gap-1 border border-gray-200/30 w-max lg:w-auto">
                    <a href="products.php?type=" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= empty($filter_type) ? 'bg-white text-gray-950 shadow-2xs' : 'text-gray-400 hover:text-gray-600'; ?>">
                        Semua Atribut
                    </a>
                    <a href="products.php?type=reguler" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= $filter_type === 'reguler' ? 'bg-white text-gray-950 shadow-2xs' : 'text-gray-400 hover:text-gray-600'; ?>">
                        Satuan Reguler
                    </a>
                    <a href="products.php?type=bundle" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= $filter_type === 'bundle' ? 'bg-white text-gray-950 shadow-2xs' : 'text-gray-400 hover:text-gray-600'; ?>">
                        Paket Bundle
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
            <?php if($all_products->num_rows > 0): ?>
                <?php while($row = $all_products->fetch_assoc()): ?>
                
                <div onclick="window.location.href='detail.php?id=<?= $row['id']; ?>';" class="group bg-white border border-gray-200 rounded-xl overflow-hidden transition flex flex-col justify-between hover:border-gray-400/80 p-1.5 md:p-2 shadow-2xs cursor-pointer duration-300">
                    
                    <div class="relative overflow-hidden rounded-lg bg-[#fcfcfc]">
                        <img src="uploads/<?= $row['main_image'] ?? 'placeholder.jpg'; ?>" class="w-full h-32 sm:h-44 md:h-48 object-cover border border-gray-100/40 group-hover:scale-103 transition-transform duration-500" onerror="this.src='uploads/placeholder.jpg'">
                    </div>
                    
                    <div class="p-2 md:p-4 flex-1 flex flex-col justify-between">
                        <div class="space-y-1">
                            <span class="text-[8px] md:text-[9px] font-black px-1.5 py-0.5 rounded-md border border-gray-200 bg-gray-50 text-gray-400 uppercase tracking-wider inline-block">
                                <i class="fa-solid fa-tag text-[7px] mr-1"></i><?= $row['type']; ?>
                            </span>
                            <h3 class="font-bold text-gray-950 text-xs md:text-sm tracking-tight truncate group-hover:text-[#06b7d2] transition-colors" title="<?= htmlspecialchars($row['name']); ?>"><?= htmlspecialchars($row['name']); ?></h3>
                            <p class="text-gray-400 text-[11px] md:text-xs line-clamp-1 md:line-clamp-2 leading-relaxed font-medium"><?= htmlspecialchars($row['description']); ?></p>
                        </div>
                        
                        <div class="mt-3 md:mt-5 pt-2 md:pt-3 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <span class="text-gray-950 font-black text-xs md:text-sm">Rp<?= number_format($row['price'], 0, ',', '.'); ?></span>
                            
                            <div class="flex gap-1 w-full sm:w-auto justify-end" onclick="event.stopPropagation();">
                                <a href="detail.php?id=<?= $row['id']; ?>" class="w-7 h-7 md:w-8 md:h-8 rounded-lg border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 flex items-center justify-center transition text-[10px] md:text-xs shrink-0" title="Detail"><i class="fa-solid fa-eye"></i></a>
                                
                                <button onclick="handleAddToCart(<?= $row['id']; ?>, '<?= htmlspecialchars($row['available_sizes'] ?? '', ENT_QUOTES); ?>', '<?= $row['type']; ?>')" class="flex-1 sm:flex-none bg-gray-950 hover:bg-gray-800 text-white text-[10px] md:text-xs px-2.5 py-1 md:py-1.5 rounded-lg font-bold transition cursor-pointer flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-plus text-[9px]"></i> <span>Bag</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-16 bg-white border border-gray-200 rounded-2xl p-6">
                    <i class="fa-solid fa-folder-open text-3xl text-gray-200 mb-2 block"></i>
                    <p class="text-gray-400 font-medium text-xs italic">Tidak ada komoditas produk yang sesuai.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if($pages > 1): ?>
        <div class="flex justify-center items-center gap-1.5 mt-12 border-t border-gray-200/60 pt-8">
            <a href="products.php?page=<?= $page - 1; ?>&type=<?= $filter_type; ?>" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 flex items-center justify-center transition text-xs font-bold bg-white hover:bg-gray-50 <?= $page <= 1 ? 'pointer-events-none opacity-40' : ''; ?>">
                <i class="fa-solid fa-angle-left"></i>
            </a>
            <?php for($i = 1; $i <= $pages; $i++): ?>
                <a href="products.php?page=<?= $i; ?>&type=<?= $filter_type; ?>" class="w-8 h-8 rounded-lg text-xs font-bold transition flex items-center justify-center <?= $page == $i ? 'bg-gray-950 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>
            <a href="products.php?page=<?= $page + 1; ?>&type=<?= $filter_type; ?>" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 flex items-center justify-center transition text-xs font-bold bg-white hover:bg-gray-50 <?= $page >= $pages ? 'pointer-events-none opacity-40' : ''; ?>">
                <i class="fa-solid fa-angle-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </main>

    <div class="fixed bottom-6 right-6 z-40">
        <button onclick="toggleCartDrawer()" class="bg-gray-950 hover:bg-gray-900 text-white px-4 py-3 rounded-xl shadow-md flex items-center space-x-3 text-xs font-bold transition border border-gray-800 cursor-pointer">
            <i class="fa-solid fa-bag-shopping text-gray-300"></i>
            <span>Keranjang</span>
            <span id="cart-counter" class="bg-[#06b7d2] text-white px-1.5 py-0.5 rounded font-black text-[10px]"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span>
        </button>
    </div>

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
        .then(res => {
            if (!res.ok) throw new Error("Network response error");
            return res.text();
        })
        .then(text => {
            try {
                const cleanJsonText = text.trim(); 
                const data = JSON.parse(cleanJsonText); 
                
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
                console.error("Gagal parse JSON.", text);
                container.innerHTML = `
                <div class="text-center py-12 text-gray-400 text-xs font-medium">
                    <i class="fa-solid fa-triangle-exclamation text-xl mb-2 text-[#06b7d2] block"></i>Gagal memuat item, coba segarkan halaman.
                </div>`;
            }
        })
        .catch(error => console.error("Fetch error:", error));
    }

    function handleAddToCart(id, sizesStr, type) {
        if (type === 'bundle') {
            window.location.href = 'detail.php?id=' + id;
            return;
        }
        
        if (sizesStr && sizesStr.trim() !== '') {
            const sizesArray = sizesStr.split(',').map(s => s.trim()).filter(s => s);
            
            let sizesHtml = `
                <p class="text-xs text-gray-400 font-medium mb-5">Pastikan ukuran atribut sesuai dengan panduan sizechart.</p>
                <div class="flex flex-wrap justify-center gap-2.5">
            `;
            
            sizesArray.forEach(s => {
                sizesHtml += `
                    <label class="cursor-pointer">
                        <input type="radio" name="premium_size" value="${s}" class="sr-only peer">
                        <span class="px-5 py-2.5 text-xs font-black rounded-xl border-2 border-gray-100 bg-white text-gray-500 inline-block peer-checked:bg-[#06b7d2] peer-checked:text-white peer-checked:border-[#06b7d2] hover:border-gray-300 transition-all shadow-3xs">
                            ${s}
                        </span>
                    </label>
                `;
            });
            sizesHtml += '</div>';

            Swal.fire({
                title: 'Pilih Ukuran',
                html: sizesHtml, 
                showCancelButton: true,
                confirmButtonText: 'Tambah ke Tas',
                cancelButtonText: 'Batal',
                buttonsStyling: false, 
                backdrop: `rgba(17, 24, 39, 0.7) backdrop-blur-sm`,
                customClass: {
                    popup: 'rounded-3xl border border-gray-100 shadow-2xl bg-white w-[92%] md:w-[28rem] p-5 md:p-8',
                    title: 'text-lg md:text-xl font-black text-gray-950 uppercase tracking-tight mb-2',
                    actions: 'flex flex-row gap-3 w-full mt-8',
                    confirmButton: 'flex-1 bg-[#06b7d2] hover:bg-[#0594a8] text-white text-xs md:text-sm font-bold py-3.5 md:py-4 rounded-xl transition shadow-md cursor-pointer !m-0',
                    cancelButton: 'flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs md:text-sm font-bold py-3.5 md:py-4 rounded-xl transition cursor-pointer !m-0'
                },
                preConfirm: () => {
                    const selectedSize = Swal.getPopup().querySelector('input[name="premium_size"]:checked');
                    if (!selectedSize) {
                        Swal.showValidationMessage('Kamu wajib memilih salah satu ukuran terlebih dahulu!');
                        return false; 
                    }
                    return selectedSize.value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    executeAddToCart(id, result.value);
                }
            });
        } else {
            executeAddToCart(id, '');
        }
    }

    function executeAddToCart(id, selectedSize) {
        let formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', id);
        formData.append('size', selectedSize);

        fetch('cart_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('cart-counter').innerText = data.total_items;
                document.querySelectorAll('.nav-cart-counter').forEach(el => el.innerText = data.total_items);
                toggleCartDrawer(); 
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
                document.getElementById('cart-counter').innerText = data.total_items;
                document.querySelectorAll('.nav-cart-counter').forEach(el => el.innerText = data.total_items);
                fetchCartContents(); 
            }
        });
    }
    </script>
</body>
</html>