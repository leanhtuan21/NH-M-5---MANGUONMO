<?php
// ========== TẬP TIN XỬ LÝ THÊM ĐỊA CHỈ GIAO HÀNG MỚI ==========
// File này nhận dữ liệu JSON từ JavaScript, xác thực và lưu vào database
// Endpoint: POST /add_address.php

session_start();
include 'db_connect.php';

// ========== CẤU HÌNH RESPONSE ==========
// Thiết lập header để trả về dữ liệu JSON
header('Content-Type: application/json');

// ========== KIỂM TRA NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP ==========
// Nếu chưa đăng nhập, trả về thông báo lỗi
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit();
}

// ========== ĐỌC DỮ LIỆU JSON TỪ REQUEST ==========
// Nhận dữ liệu từ JavaScript gửi lên
$data = json_decode(file_get_contents('php://input'), true);

// ========== KIỂM TRA DỮ LIỆU VÀ LƯU VÀO DATABASE ==========
if ($data) {
    // Lấy thông tin từ $_SESSION và $data
    $user_id = $_SESSION['user_id'];
    $name = $data['name'];
    $phone = $data['phone'];
    $address = $data['address'];
    $city = $data['city'];
    $is_default = $data['is_default'] ? 1 : 0;

    // ========== NẾU CHỌN "MẶC ĐỊNH", RESET ĐỊA CHỈ CŨ ==========
    // Nếu người dùng chọn địa chỉ này làm mặc định, 
    // thì tất cả các địa chỉ cũ của user này sẽ không còn là mặc định
    if ($is_default == 1) {
        $update_sql = "UPDATE user_addresses SET is_default = 0 WHERE user_id = ?";
        $upd_stmt = $conn->prepare($update_sql);
        $upd_stmt->bind_param("i", $user_id);
        $upd_stmt->execute();
        $upd_stmt->close();
    }

    // ========== INSERT ĐỊA CHỈ MỚI VÀO DATABASE ==========
    // Chuẩn bị câu lệnh SQL để thêm địa chỉ mới
    $sql = "INSERT INTO user_addresses (user_id, name, phone, address, city, is_default) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Bind các tham số: i (integer), s (string), ...
    $stmt->bind_param("issssi", $user_id, $name, $phone, $address, $city, $is_default);

    // ========== KIỂM TRA KẾT QUẢ EXECUTE ==========
    // Nếu thành công, trả về success = true
    // Nếu thất bại, trả về success = false và thông báo lỗi
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    
    // Đóng prepared statement
    $stmt->close();
}

// Đóng kết nối database
$conn->close();
?>
