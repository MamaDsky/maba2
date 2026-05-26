<?php
session_start();
require_once '../config/database.php';
$db = (new Database())->getConnection();

// ==========================================
// PROCESS LOGIC: TAMBAH / EDIT / HAPUS
// ==========================================

// 1. Aksi Hapus Produk
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    // Bersihkan file gambar tambahan dari server
    $img_res = $db->query("SELECT image_path FROM product_images WHERE product_id = $id");
    while ($img = $img_res->fetch_assoc()) {
        if (!empty($img['image_path']) && file_exists("../uploads/" . $img['image_path'])) {
            @unlink("../uploads/" . $img['image_path']);
        }
    }
    
    // Bersihkan file sizechart jika ada
    $prod_res = $db->query("SELECT sizechart_path FROM products WHERE id = $id");
    $prod_data = $prod_res->fetch_assoc();
    if (!empty($prod_data['sizechart_path']) && file_exists("../uploads/" . $prod_data['sizechart_path'])) {
        @unlink("../uploads/" . $prod_data['sizechart_path']);
    }

    $db->query("DELETE FROM products WHERE id = $id");
    $_SESSION['swal'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Produk dan berkas gambar berhasil dihapus permanen.'];
    header("Location: product_crud.php");
    exit;
}

// 2. Aksi Simpan (Tambah Baru & Update Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $name = htmlspecialchars($_POST['name']);
    $desc = htmlspecialchars($_POST['description']);
    $price = intval($_POST['price']);
    $type = $_POST['type'];

    if ($id > 0) {
        // Mode UPDATE data inti produk
        $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ?, type = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $name, $desc, $price, $type, $id);
        $stmt->execute();
        $product_id = $id;
        $_SESSION['swal'] = ['type' => 'success', 'title' => 'Diperbarui!', 'text' => 'Data produk berhasil diperbarui.'];
    } else {
        // Mode INSERT produk baru
        $stmt = $db->prepare("INSERT INTO products (name, description, price, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $name, $desc, $price, $type);
        $stmt->execute();
        $product_id = $db->insert_id;
        $_SESSION['swal'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Produk baru masuk ke katalog.'];
    }

    // Pemrosesan File Upload Sizechart Gambar
    if (isset($_FILES['sizechart']) && $_FILES['sizechart']['error'] === 0) {
        if ($id > 0) {
            $old_sc = $db->query("SELECT sizechart_path FROM products WHERE id = $id")->fetch_assoc();
            if(!empty($old_sc['sizechart_path']) && file_exists("../uploads/" . $old_sc['sizechart_path'])) { 
                @unlink("../uploads/" . $old_sc['sizechart_path']); 
            }
        }
        $ext = pathinfo($_FILES['sizechart']['name'], PATHINFO_EXTENSION);
        $sc_filename = "SIZE_" . uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES['sizechart']['tmp_name'], "../uploads/" . $sc_filename)) {
            $db->query("UPDATE products SET sizechart_path = '$sc_filename' WHERE id = $product_id");
        }
    }

    // Pemrosesan File MULTIPLE UPLOAD 3 Gambar (Perbaikan Utama)
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $files = $_FILES['images'];
        $total_uploaded = min(count($files['name']), 3);
        
        // Jika admin mengunggah file baru pada mode edit, bersihkan foto lama terlebih dahulu
        if ($id > 0 && $files['error'][0] === 0) {
            $old_img = $db->query("SELECT image_path FROM product_images WHERE product_id = $id");
            while($oi = $old_img->fetch_assoc()) { 
                if(file_exists("../uploads/" . $oi['image_path'])) { @unlink("../uploads/" . $oi['image_path']); }
            }
            $db->query("DELETE FROM product_images WHERE product_id = $id");
        }
        
        // Loop simpan maks 3 file ke folder uploads dan database
        for ($i = 0; $i < $total_uploaded; $i++) {
            if ($files['error'][$i] === 0) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $new_filename = "IMG_" . uniqid() . "_" . $i . "." . $ext;
                if (move_uploaded_file($files['tmp_name'][$i], "../uploads/" . $new_filename)) {
                    $db->query("INSERT INTO product_images (product_id, image_path) VALUES ($product_id, '$new_filename')");
                }
            }
        }
    }

    // Mengelola Relasi Bundle Ulang jika bertipe bundle
    if ($type == 'bundle' && isset($_POST['bundled_items'])) {
        $db->query("DELETE FROM bundle_relations WHERE bundle_product_id = $product_id");
        foreach ($_POST['bundled_items'] as $reg_id) {
            $reg_id = intval($reg_id);
            $db->query("INSERT INTO bundle_relations (bundle_product_id, regular_product_id) VALUES ($product_id, $reg_id)");
        }
    }

    header("Location: product_crud.php");
    exit;
}

// 🎛️ LOGIKA BACKEND PAGINATION PRODUK
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$total_res = $db->query("SELECT COUNT(*) as total FROM products");
$total_data = $total_res->fetch_assoc()['total'];
$pages = ceil($total_data / $limit);

$all_products = $db->query("SELECT p.*, (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as total_img FROM products p ORDER BY p.id DESC LIMIT $start, $limit");

$reg_products_arr = [];
$reg_products_res = $db->query("SELECT id, name FROM products WHERE type = 'reguler' ORDER BY name ASC");
while($row = $reg_products_res->fetch_assoc()) {
    $reg_products_arr[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - CRUD Katalog & Bundle</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <a href="index.php" class="text-indigo-600 hover:text-indigo-800 font-semibold transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Kembali ke Dashboard
        </a>
        <button onclick="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-4 py-2 rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
            <i class="fa-solid fa-plus text-xs"></i>
            Tambah Produk Baru
        </button>
    </div>

    <div class="max-w-7xl mx-auto bg-white rounded-2xl border border-gray-100 shadow-xs p-6 md:p-8 flex flex-col justify-between">
        <div>
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Katalog Manajemen Produk</h2>
                <p class="text-gray-400 text-xs mt-1">Kelola item jualan tunggal, paket bundle kombinasi, panduan sizechart kemeja, serta galeri foto merchandise.</p>
            </div>

            <div class="overflow-x-auto -mx-6 md:mx-0">
                <table class="w-full text-left border-collapse min-w-[850px]">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs text-gray-400 font-semibold uppercase tracking-wider">
                            <th class="pb-4 px-6 w-48">Galeri Foto</th>
                            <th class="pb-4 px-4">Nama Produk</th>
                            <th class="pb-4 px-4">Tipe</th>
                            <th class="pb-4 px-4">Harga Katalog</th>
                            <th class="pb-4 px-4">Sizechart</th>
                            <th class="pb-4 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-gray-600">
                        <?php if($all_products->num_rows > 0): ?>
                            <?php while($p = $all_products->fetch_assoc()): ?>
                            <tr class="align-middle hover:bg-gray-50/50 transition">
                                <td class="py-4 px-6">
                                    <div class="flex gap-1.5 overflow-hidden">
                                        <?php
                                        $p_id = $p['id'];
                                        $galeri_res = $db->query("SELECT image_path FROM product_images WHERE product_id = $p_id LIMIT 3");
                                        if($galeri_res->num_rows > 0):
                                            while($g = $galeri_res->fetch_assoc()):
                                        ?>
                                            <img src="../uploads/<?= $g['image_path']; ?>" class="w-10 h-10 object-cover rounded-lg border border-gray-200 shadow-xxs">
                                        <?php 
                                            endwhile;
                                        else: 
                                        ?>
                                            <span class="text-gray-300 italic text-xs">Tanpa Foto</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="text-gray-900 font-semibold block text-sm"><?= htmlspecialchars($p['name']); ?></span>
                                    <p class="text-xs text-gray-400 line-clamp-1 mt-0.5 max-w-xs"><?= htmlspecialchars($p['description']); ?></p>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="px-2.5 py-0.5 rounded-md text-xs font-bold inline-block <?= $p['type'] == 'bundle'?'bg-purple-50 text-purple-600 border border-purple-100':'bg-gray-100 text-gray-600'; ?>">
                                        <?= $p['type']; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-bold text-gray-950">
                                    Rp<?= number_format($p['price'],0,',','.'); ?>
                                </td>
                                <td class="py-4 px-4 text-xs font-medium">
                                    <?= !empty($p['sizechart_path']) ? '<span class="text-green-600 font-bold"><i class="fa-solid fa-ruler-combined mr-1"></i>Ada</span>' : '<span class="text-gray-300"><i class="fa-solid fa-xmark mr-1"></i>Tidak Ada</span>'; ?>
                                </td>
                                <td class="py-4 px-6 text-right space-x-3 whitespace-nowrap">
                                    <?php
                                    // Mapping array foto dan bundle komponen untuk dilempar ke Javascript Modal
                                    $relations = [];
                                    if($p['type'] == 'bundle') {
                                        $rel_res = $db->query("SELECT regular_product_id FROM bundle_relations WHERE bundle_product_id = $p_id");
                                        while($r = $rel_res->fetch_assoc()) { $relations[] = $r['regular_product_id']; }
                                    }
                                    $p['bundled_items'] = $relations;

                                    $img_arr = [];
                                    $img_paths_res = $db->query("SELECT image_path FROM product_images WHERE product_id = $p_id LIMIT 3");
                                    while($im = $img_paths_res->fetch_assoc()) { $img_arr[] = $im['image_path']; }
                                    $p['images_gallery'] = $img_arr;
                                    ?>
                                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($p)); ?>)" class="text-indigo-600 hover:text-indigo-900 font-semibold transition cursor-pointer">Edit</button>
                                    <button onclick="confirmDelete(<?= $p['id']; ?>)" class="text-red-500 hover:text-red-700 font-semibold transition cursor-pointer">Hapus</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400 italic bg-gray-50/30 rounded-xl">Belum ada item produk di katalog.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center border-t border-gray-100 pt-5 mt-8 gap-4">
            <span class="text-xs text-gray-400">Total data: <strong class="text-gray-700"><?= $total_data; ?></strong> item katalog terdaftar.</span>
            <div class="flex space-x-1">
                <?php for($i=1; $i<=$pages; $i++): ?>
                    <a href="product_crud.php?page=<?= $i; ?>" class="px-3.5 py-1.5 text-xs rounded-xl font-semibold transition <?= $page == $i ? 'bg-indigo-600 text-white':'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>"><?= $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>


    <div id="productModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4 transition-all opacity-0">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-2xl w-full max-w-lg transform scale-95 transition-all duration-300 flex flex-col overflow-hidden max-h-[90vh]">
            
            <div class="bg-gray-50 border-b border-gray-100 px-6 py-4 flex justify-between items-center shrink-0">
                <div>
                    <h3 id="modalTitle" class="text-base font-bold text-gray-900">Tambah Produk Baru</h3>
                    <p class="text-gray-400 text-xxs mt-0.5">Konfigurasi berkas penjualan merchandise maba.</p>
                </div>
                <button onclick="closeProductModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto flex-1">
                <input type="hidden" name="product_id" id="modalProductId" value="0">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Nama Produk</label>
                    <input type="text" name="name" id="modalName" required class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none focus:border-indigo-500 text-xs font-medium">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
                    <textarea name="description" id="modalDescription" rows="3" class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none focus:border-indigo-500 text-xs font-medium resize-none"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Harga Jual (Rp)</label>
                        <input type="number" name="price" id="modalPrice" required class="w-full border border-gray-200 px-3 py-2 rounded-xl focus:outline-none focus:border-indigo-500 font-semibold text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Tipe Produk</label>
                        <select name="type" id="modalType" onchange="toggleBundleBox()" class="w-full border border-gray-200 px-3 py-2 rounded-xl bg-white focus:outline-none focus:border-indigo-500 font-bold text-xs text-gray-700">
                            <option value="reguler">Reguler</option>
                            <option value="bundle">Bundle (Paket Kombinasi)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Upload Sizechart (Gambar Panduan Ukuran)</label>
                    <input type="file" name="sizechart" accept="image/*" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 cursor-pointer">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Gambar Produk (Pilih Sekaligus Maks 3 Foto)</label>
                    <input type="file" name="images[]" multiple accept="image/*" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                    
                    <div id="modalImagesPreview" class="hidden mt-3 p-3 bg-gray-50 rounded-xl border border-gray-100 flex gap-2">
                        </div>
                    <p class="text-[10px] text-gray-400 mt-1 italic">*Tahan tombol Ctrl / Shift di keyboard untuk memilih lebih dari 1 foto sekaligus.</p>
                </div>

                <div id="bundle_checklist_box" class="hidden bg-gray-50 p-4 rounded-xl border border-gray-200 max-h-40 overflow-y-auto space-y-2">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Centang Komponen Isi Paket:</label>
                    <div class="space-y-1.5">
                        <?php foreach($reg_products_arr as $reg): ?>
                            <label class="flex items-center space-x-2 text-xs font-medium text-gray-700 cursor-pointer">
                                <input type="checkbox" name="bundled_items[]" value="<?= $reg['id']; ?>" class="rounded text-indigo-600 checkbox-bundle-item">
                                <span><?= htmlspecialchars($reg['name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4 mt-6 shrink-0">
                    <button type="button" onclick="closeProductModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-semibold text-xs transition cursor-pointer">Batal</button>
                    <button type="submit" name="save_product" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-xs shadow-xs transition cursor-pointer">Simpan Katalog</button>
                </div>
            </form>
        </div>
    </div>


    <script>
    const modal = document.getElementById('productModal');
    const modalContent = modal.querySelector('.transform');

    function toggleBundleBox() {
        let type = document.getElementById('modalType').value;
        document.getElementById('bundle_checklist_box').classList.toggle('hidden', type !== 'bundle');
    }

    function openAddModal() {
        document.getElementById('modalProductId').value = "0";
        document.getElementById('modalTitle').innerText = "Tambah Produk Baru";
        document.getElementById('modalName').value = "";
        document.getElementById('modalDescription').value = "";
        document.getElementById('modalPrice').value = "";
        document.getElementById('modalType').value = "reguler";
        document.getElementById('modalImagesPreview').innerHTML = "";
        document.getElementById('modalImagesPreview').classList.add('hidden');
        
        document.querySelectorAll('.checkbox-bundle-item').forEach(cb => cb.checked = false);
        toggleBundleBox();
        showModalEffect();
    }

    function openEditModal(productData) {
        document.getElementById('modalProductId').value = productData.id;
        document.getElementById('modalTitle').innerText = "Ubah Informasi Produk";
        document.getElementById('modalName').value = productData.name;
        document.getElementById('modalDescription').value = productData.description;
        document.getElementById('modalPrice').value = productData.price;
        document.getElementById('modalType').value = productData.type;

        // Render Multi-Preview Foto di dalam Modal Form saat Edit
        const previewContainer = document.getElementById('modalImagesPreview');
        previewContainer.innerHTML = "";
        
        if (productData.images_gallery && productData.images_gallery.length > 0) {
            productData.images_gallery.forEach(imgName => {
                let imgEl = document.createElement('img');
                imgEl.src = "../uploads/" + imgName;
                imgEl.className = "w-12 h-12 object-cover rounded-lg border border-gray-200 shadow-xxs";
                previewContainer.appendChild(imgEl);
            });
            previewContainer.classList.remove('hidden');
        } else {
            previewContainer.classList.add('hidden');
        }

        document.querySelectorAll('.checkbox-bundle-item').forEach(cb => {
            cb.checked = productData.bundled_items.includes(parseInt(cb.value));
        });

        toggleBundleBox();
        showModalEffect();
    }

    function showModalEffect() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }, 20);
    }

    function closeProductModal() {
        modal.classList.add('opacity-0');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 300);
    }

    window.onclick = function(event) {
        if (event.target == modal) { closeProductModal(); }
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Apakah kamu yakin?',
            text: "Data produk beserta berkas file gambar fisik sizechart akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'product_crud.php?delete_id=' + id;
            }
        });
    }
    </script>
</body>
</html>