<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: sign-in.php');
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['product_quantity'];

    // 1. Kiểm tra sản phẩm tồn tại và lấy thông tin
    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = ?");
    if (!$stmt) {
        die("Lỗi prepare: " . $conn->error);
    }
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        die("Sản phẩm không tồn tại.");
    }
    
    $product = $result->fetch_assoc();
    $product_name = $product['name'];
    $product_price = $product['price'];
    $stmt->close();

    // 2. Kiểm tra sản phẩm đã có trong giỏ hàng của user này chưa
    $check_cart = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_name = ?");
    if (!$check_cart) {
        die("Lỗi prepare: " . $conn->error);
    }
    $check_cart->bind_param("is", $user_id, $product_name);
    $check_cart->execute();
    $res = $check_cart->get_result();

    if ($res->num_rows > 0) {
        // Nếu có rồi thì update số lượng
        $row = $res->fetch_assoc();
        $new_qty = $row['quantity'] + $quantity;
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        if (!$update) {
            die("Lỗi prepare: " . $conn->error);
        }
        $update->bind_param("ii", $new_qty, $row['id']);
        $update->execute();
        $update->close();
    } else {
        // Nếu chưa có thì thêm mới
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_name, price, quantity) VALUES (?, ?, ?, ?)");
        if (!$insert) {
            die("Lỗi prepare: " . $conn->error);
        }
        $insert->bind_param("isdi", $user_id, $product_name, $product_price, $quantity);
        $insert->execute();
        $insert->close();
    }

    $check_cart->close();
    $conn->close();
    
    // Chuyển hướng sang checkout để xem giỏ hàng
    header('Location: checkout.php');
    exit();
} else {
    echo "Truy cập không hợp lệ.";
}
?> 