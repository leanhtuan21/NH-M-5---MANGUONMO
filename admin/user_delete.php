<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // 1. Kiểm tra không được xóa chính mình
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('Lỗi: Bạn không thể xóa tài khoản đang đăng nhập!'); window.location.href='users.php';</script>";
        exit;
    }

    // 2. Kiểm tra xem user cần xóa có phải admin không
    $check_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $user_to_delete = $check_stmt->get_result()->fetch_assoc();
    
    if ($user_to_delete && $user_to_delete['role'] == 'admin') {
        echo "<script>alert('Lỗi: Không thể xóa tài khoản Admin!'); window.location.href='users.php';</script>";
        exit;
    }

    // 3. Thực hiện xóa
    // Lưu ý: Do DB đã có ràng buộc ON DELETE CASCADE (như trong file SQL bạn gửi), 
    // nên khi xóa user, các đơn hàng và tin nhắn chat của user đó cũng sẽ tự mất (hoặc set NULL tùy thiết kế).
    // Ở đây ta cứ xóa thẳng tay.
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: users.php?msg=deleted");
    } else {
        echo "<script>alert('Lỗi: " . $conn->error . "'); window.location.href='users.php';</script>";
    }
} else {
    header("Location: users.php");
}
?>