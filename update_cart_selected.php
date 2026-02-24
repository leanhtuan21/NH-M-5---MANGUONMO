<?php
// File: update_cart_selected.php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cart_id = (int)($input['id'] ?? 0);
$selected = (int)($input['selected'] ?? 0); // 0 hoặc 1
$user_id = $_SESSION['user_id'];

// Cập nhật trạng thái selected vào database
$stmt = $conn->prepare("UPDATE cart SET selected = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("iii", $selected, $cart_id, $user_id);
$result = $stmt->execute();

echo json_encode(['success' => $result]);
?>