<?php
session_start();
require_once __DIR__ . '/db_connect.php';

/* ===============================
   CHỈ CHO POST
=============================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin-orders.php");
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$new_status = strtoupper(trim($_POST['status'] ?? ''));

if ($order_id <= 0 || empty($new_status)) {
    die("Thiếu dữ liệu");
}

/* ===============================
   CHỐNG FAKE STATUS
=============================== */
$allowedStatuses = ['PAID', 'SHIPPING'];
if (!in_array($new_status, $allowedStatuses)) {
    die("Status không hợp lệ");
}

/* ===============================
   LẤY STATUS HIỆN TẠI
=============================== */
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Không tìm thấy đơn hàng");
}

$current_status = strtoupper(trim($order['status']));

/* ===============================
   FLOW ADMIN CHUẨN
=============================== */
$validFlow = [
    'PENDING' => ['PAID'],
    'PAID'    => ['SHIPPING'],
    'SHIPPING'=> [],
    'COMPLETED'=> []
];

/* ===============================
   VALIDATE FLOW
=============================== */
if (!isset($validFlow[$current_status])) {
    die("Trạng thái hiện tại không hợp lệ");
}

if (!in_array($new_status, $validFlow[$current_status])) {
    die("Không thể chuyển từ $current_status → $new_status");
}

/* ===============================
   UPDATE
=============================== */
$update = $conn->prepare("
    UPDATE orders 
    SET status = ?
    WHERE id = ?
");
$update->bind_param("si", $new_status, $order_id);
$update->execute();

/* ===============================
   REDIRECT
=============================== */
header("Location: admin-orders.php?updated=1");
exit;