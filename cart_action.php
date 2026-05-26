<?php
// Mengunci buffer output agar tidak ada spasi atau teks liar yang bocor ke browser
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// -------------------------------------------------------------------------
// HANDLE METHOD GET: MEMBACA ISI KERANJANG
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_summary') {
    // Bersihkan semua teks bocor dari file lain sebelum JSON dikirim
    ob_clean(); 
    header('Content-Type: application/json; charset=utf-8');
    
    $response = ['status' => 'success', 'items' => [], 'grand_total' => 0];
    
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $grand_total = 0;
        foreach ($_SESSION['cart'] as $cart_key => $qty) {
            $qty = intval($qty);
            if ($qty <= 0) continue;
            
            // Ekstrak ID produk dan Ukuran dari kunci keranjang (format: id_size)
            $parts = explode('_', $cart_key);
            $p_id = intval($parts[0]);
            $size = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null;
            
            $stmt = $db->prepare("SELECT p.name, p.price, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as img FROM products p WHERE p.id = ?");
            $stmt->bind_param("i", $p_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            
            if ($res) {
                $subtotal = intval($res['price']) * $qty;
                $grand_total += $subtotal;
                
                // Tambahkan label ukuran di nama produk yang tampil di laci keranjang
                $display_name = htmlspecialchars($res['name']);
                if ($size) {
                    $display_name .= " (Ukuran: " . htmlspecialchars($size) . ")";
                }

                $response['items'][] = [
                    'cart_key' => $cart_key, // Key ini nanti dipakai untuk tombol Hapus item
                    'id' => intval($p_id),
                    'name' => $display_name,
                    'price' => intval($res['price']),
                    'qty' => $qty,
                    'image' => $res['img']
                ];
            }
        }
        $response['grand_total'] = $grand_total;
    }
    
    echo json_encode($response);
    exit;
}

// -------------------------------------------------------------------------
// HANDLE METHOD POST: MANIPULASI DATA KERANJANG
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $action = $_POST['action'];

    if ($action == 'add') {
        $product_id = intval($_POST['product_id']);
        $size = isset($_POST['size']) ? trim($_POST['size']) : '';
        
        // Buat kunci unik gabungan ID dan Size (contoh: '12_XL' atau '12')
        $cart_key = $product_id . ($size !== '' ? '_' . $size : '');

        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key] += 1;
        } else {
            $_SESSION['cart'][$cart_key] = 1;
        }
        
        echo json_encode(['status' => 'success', 'total_items' => array_sum($_SESSION['cart'])]);
        exit;
    }

    if ($action == 'remove') {
        // Hapus berdasarkan kunci unik
        if (isset($_POST['cart_key'])) {
            $cart_key = $_POST['cart_key'];
            if (isset($_SESSION['cart'][$cart_key])) {
                unset($_SESSION['cart'][$cart_key]);
            }
        } else if (isset($_POST['product_id'])) {
            // Logika lama (fallback) untuk berjaga-jaga jika halaman frontend belum ter-refresh
            $product_id = intval($_POST['product_id']);
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        echo json_encode(['status' => 'success', 'total_items' => array_sum($_SESSION['cart'])]);
        exit;
    }

    if ($action == 'clear') {
        $_SESSION['cart'] = [];
        header("Location: checkout.php");
        exit;
    }
}