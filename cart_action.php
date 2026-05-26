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
        foreach ($_SESSION['cart'] as $p_id => $qty) {
            $qty = intval($qty);
            if ($qty <= 0) continue;
            
            $stmt = $db->prepare("SELECT p.name, p.price, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as img FROM products p WHERE p.id = ?");
            $stmt->bind_param("i", $p_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            
            if ($res) {
                $subtotal = intval($res['price']) * $qty;
                $grand_total += $subtotal;
                $response['items'][] = [
                    'id' => intval($p_id),
                    'name' => $res['name'],
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
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += 1;
        } else {
            $_SESSION['cart'][$product_id] = 1;
        }
        echo json_encode(['status' => 'success', 'total_items' => array_sum($_SESSION['cart'])]);
        exit;
    }

    if ($action == 'remove') {
        $product_id = intval($_POST['product_id']);
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
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