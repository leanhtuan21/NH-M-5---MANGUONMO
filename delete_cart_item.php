<?php
// ===== XỬ LÝ XÓA SẢN PHẨM TRONG GIỎ HÀNG =====

session_start();
include 'db_connect.php';

// Thiết lập header JSON
header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện hành động này']);
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

// Lấy ID sản phẩm cần xóa từ dữ liệu JSON
$cartItemId = isset($data['id']) ? (int)$data['id'] : 0;

// Kiểm tra ID hợp lệ
if (!$cartItemId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID giỏ hàng không hợp lệ']);
    exit();
}

// Lấy user_id từ session
$userId = $_SESSION['user_id'];

// Chuẩn bị câu lệnh SQL xóa sản phẩm
// Điều kiện: xóa sản phẩm có id = $cartItemId AND user_id = $userId (bảo mật)
$sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);

// Kiểm tra xem prepare có thành công không
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $conn->error]);
    exit();
}

// Gắn các tham số vào câu lệnh SQL
$stmt->bind_param("ii", $cartItemId, $userId);

// Thực thi câu lệnh
$result = $stmt->execute();

// Kiểm tra kết quả thực thi
if ($result && $stmt->affected_rows > 0) {
    // ✅ Xóa thành công - có dòng bị xóa
    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Xóa sản phẩm thành công',
        'affected_rows' => $stmt->affected_rows
    ]);
} elseif ($result && $stmt->affected_rows === 0) {
    // ❌ Không có dòng nào bị xóa - sản phẩm không tồn tại hoặc không phải của user này
    http_response_code(404);
    echo json_encode([
        'success' => false, 
        'message' => 'Không tìm thấy sản phẩm trong giỏ hàng hoặc sản phẩm không phải của bạn'
    ]);
} else {
    // ❌ Lỗi khi thực thi câu lệnh
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi khi xóa sản phẩm: ' . $stmt->error
    ]);
}

// Đóng statement và kết nối
$stmt->close();
$conn->close();



