<?php
// ===== XỬ LÝ CẬP NHẬT SỐ LƯỢNG SẢN PHẨM TRONG GIỎ HÀNG =====

session_start();
include 'db_connect.php';

// Thiết lập header JSON
header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập']);
    exit();
}

// Kiểm tra phương thức request (phải là POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ']);
    exit();
}

// Lấy dữ liệu JSON từ request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Lấy các thông tin cần thiết
$cartItemId = isset($data['id']) ? (int)$data['id'] : 0;
$action = isset($data['action']) ? $data['action'] : '';

// Kiểm tra dữ liệu hợp lệ
if (!$cartItemId || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

// Kiểm tra action là increase hay decrease
if ($action !== 'increase' && $action !== 'decrease') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    exit();
}

$userId = $_SESSION['user_id'];

// Truy vấn để lấy số lượng hiện tại
$selectSql = "SELECT quantity FROM cart WHERE id = ? AND user_id = ?";
$selectStmt = $conn->prepare($selectSql);
$selectStmt->bind_param("ii", $cartItemId, $userId);
$selectStmt->execute();
$result = $selectStmt->get_result();

// Kiểm tra sản phẩm có tồn tại không
if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
    exit();
}

// Lấy số lượng hiện tại
$row = $result->fetch_assoc();
$currentQuantity = (int)$row['quantity'];
$selectStmt->close();

// Tính số lượng mới dựa trên action
if ($action === 'increase') {
    $newQuantity = $currentQuantity + 1;
} else {
    // Giảm nhưng không được nhỏ hơn 1
    $newQuantity = $currentQuantity - 1;
    if ($newQuantity < 1) {
        $newQuantity = 1;
    }
}

// Cập nhật số lượng trong database
$updateSql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
$updateStmt = $conn->prepare($updateSql);

if (!$updateStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
    exit();
}

$updateStmt->bind_param("iii", $newQuantity, $cartItemId, $userId);
$result = $updateStmt->execute();

if ($result && $updateStmt->affected_rows > 0) {
    // ✅ Cập nhật thành công
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật số lượng thành công',
        'new_quantity' => $newQuantity,
        'cart_id' => $cartItemId
    ]);
} else {
    // ❌ Cập nhật thất bại
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Không thể cập nhật số lượng'
    ]);
}

// Đóng statement và kết nối
$updateStmt->close();
$conn->close();
