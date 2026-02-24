<?php
// admin/includes/auth.php

// Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra xem đã đăng nhập chưa (có user_id chưa?)
if (!isset($_SESSION['user_id'])) {
    // Chưa đăng nhập -> Đá về trang login (giả sử file login.php nằm ở thư mục gốc)
    header("Location: ../sign-in.php");
    exit();
}

// 2. Kiểm tra xem có phải là Admin không? (Dựa vào cột 'role' trong bảng users)
// Nếu bạn chưa lưu role vào session khi login, hãy mở code login và thêm $_SESSION['role'] = $row['role'];
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    die("Lỗi: Bạn không có quyền truy cập trang quản trị!");
}
?>