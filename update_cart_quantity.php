<?php
// ===== XỬ LÝ CẬP NHẬT SỐ LƯỢNG SẢN PHẨM TRONG GIỎ HÀNG =====

session_start();
include 'db_connect.php';

// Thiết lập header JSON
header('Content-Type: application/json; charset=utf-8');

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
if ($cartItemId <= 0 || !in_array($action, ['increase', 'decrease'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

$userId = (int)$_SESSION['user_id'];

/**
 * ✅ Lấy số lượng hiện tại + tồn kho theo product_id + weight_gram
 */
$selectSql = "
    SELECT 
        c.quantity,
        c.product_id,
        c.weight_gram,
        pw.stock_quantity
    FROM cart c
    JOIN product_weights pw 
        ON pw.product_id = c.product_id
       AND pw.weight_gram = c.weight_gram
    WHERE c.id = ? AND c.user_id = ?
";
$selectStmt = $conn->prepare($selectSql);
$selectStmt->bind_param("ii", $cartItemId, $userId);
$selectStmt->execute();
$result = $selectStmt->get_result();

// Kiểm tra sản phẩm có tồn tại không
if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ hoặc biến thể khối lượng']);
    exit();
}

// Lấy số lượng hiện tại + tồn kho
$row = $result->fetch_assoc();
$currentQuantity = (int)$row['quantity'];
$stock = (int)$row['stock_quantity'];

$selectStmt->close();

/**
 * ✅ Tính số lượng mới
 */
if ($action === 'increase') {
    $newQuantity = $currentQuantity + 1;

    // 🚫 Không cho vượt tồn kho theo khối lượng
    if ($newQuantity > $stock) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Chỉ còn $stock sản phẩm trong kho cho khối lượng này"
        ]);
        exit();
    }

} else { // decrease
    $newQuantity = $currentQuantity - 1;

    // 🚫 Không cho nhỏ hơn 1
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
$ok = $updateStmt->execute();

if ($ok && $updateStmt->affected_rows >= 0) {
    // ✅ Cập nhật thành công (>=0 vì có thể update cùng giá trị)
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
exit();
