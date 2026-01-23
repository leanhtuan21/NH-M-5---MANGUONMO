<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo "Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng.";
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $product_name = $_POST['product_name'];
    $product_price = (float)$_POST['product_price'];
    $product_quantity = (int)$_POST['product_quantity'];

    // Tìm product_id từ product_name
    $product_stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
    $product_stmt->bind_param("s", $product_name);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    if ($product_result->num_rows == 0) {
        echo "Sản phẩm không tồn tại.";
        exit();
    }
    $product = $product_result->fetch_assoc();
    $product_id = $product['id'];
    $product_stmt->close();

    // Lưu vào session cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $product_quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'quantity' => $product_quantity,
            'price' => $product_price,
            'name' => $product_name
        ];
    }

    $conn->close();

    // Redirect về trang sản phẩm với thông báo
    header('Location: product-detail.php?added=1');
    exit();
} else {
    echo "Truy cập không hợp lệ.";
}
?> 