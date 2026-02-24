<?php
session_start();
include 'db_connect.php';

// Kiểm tra người dùng đã đăng nhập và nhận được dữ liệu
if (!isset($_SESSION['user_id']) || !isset($_POST['cart_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$cart_id = (int)$_POST['cart_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$user_id = $_SESSION['user_id'];

// Kiểm tra tính hợp lệ của action
if (!in_array($action, ['increase', 'decrease'])) {
    echo json_encode(['success' => false]);
    exit();
}

// Kiểm tra sản phẩm có đúng của user này không
$stmt = $conn->prepare("SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $current_qty = $row['quantity'];

    // Tính toán số lượng mới
    if ($action === 'increase') {
        $new_qty = $current_qty + 1;
    } else {
        // Giảm số lượng nhưng không cho phép dưới 1
        $new_qty = ($current_qty > 1) ? $current_qty - 1 : 1;
    }

    // Cập nhật số lượng trong database
    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $new_qty, $cart_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'new_qty' => $new_qty]);
    } else {
        echo json_encode(['success' => false]);
    }
    $update_stmt->close();
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
