<?php
session_start();
require_once __DIR__ . '/db_connect.php';

/* 1. Check login */
if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* 2. Nhận dữ liệu */
if (!isset($_POST['order_id']) || !isset($_POST['method'])) {
    die("Thiếu dữ liệu.");
}

$order_id = (int)$_POST['order_id'];
$method = $_POST['method']; // cod | qr

/* 3. Lấy đơn hàng */
$sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Đơn hàng không hợp lệ.");
}

$order = $result->fetch_assoc();

/* 4. Check trạng thái hợp lệ */
if ($order['status'] !== 'pending') {
    die("Đơn hàng đã được xử lý.");
}

/* 5. Xử lý theo phương thức thanh toán */
if ($method === "cod") {

    $new_status = "pending"; // chờ admin duyệt
    $new_payment_status = "unpaid";

} elseif ($method === "qr") {

    $new_status = "pending"; // vẫn chờ admin
    $new_payment_status = "pending_confirmation";

} else {
    die("Phương thức không hợp lệ.");
}

/* 6. Update đơn hàng */
$update_sql = "UPDATE orders 
               SET payment_method = ?, status = ?, payment_status = ?
               WHERE id = ? AND user_id = ?";

$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param(
    "sssii",
    $method,
    $new_status,
    $new_payment_status,
    $order_id,
    $user_id
);
$update_stmt->execute();

/* 7. Redirect */
header("Location: payment-success.php?order_id=" . $order_id);
exit();