<?php
session_start();
require_once "db_connect.php";

/* Kiểm tra đăng nhập */
if (!isset($_SESSION['user_id'])) {
    die("Bạn chưa đăng nhập");
}

/* Kiểm tra order_id */
if (!isset($_POST['order_id'])) {
    die("Thiếu order_id");
}

$order_id = (int)$_POST['order_id'];
$user_id = $_SESSION['user_id'];

/* Chỉ cho update nếu:
   - Đơn thuộc user đó
   - Đang shipping
*/
$stmt = $conn->prepare("
    UPDATE orders 
    SET status = 'completed' 
    WHERE id = ? 
      AND user_id = ? 
      AND status = 'shipping'
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();

/* Quay lại trang chi tiết */
header("Location: order-detail.php?id=" . $order_id);
exit;