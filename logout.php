<?php
    session_start();
    // 1. Xóa hết biến session và hủy phiên
    session_unset();
    session_destroy();

    // 2. Kiểm tra nếu có yêu cầu redirect cụ thể, nếu không thì về index.php
    $redirect = $_GET['redirect'] ?? 'index.php';

    // Bảo mật: Nếu tham số redirect là 'sign-up' thì về trang đăng ký, 
    // còn lại mặc định về index
    if ($redirect === 'sign-up') {
        header("Location: sign-up.php");
    } else {
        header("Location: index.php");
    }
    exit; // Luôn dùng exit sau header để dừng script ngay lập tức
?>