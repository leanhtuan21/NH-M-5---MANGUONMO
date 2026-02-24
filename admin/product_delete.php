<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Kiểm tra sản phẩm tồn tại
    $check_stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $product = $check_stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        echo "<script>alert('Sản phẩm không tồn tại!'); window.location.href='products.php';</script>";
        exit;
    }

    // Xóa sản phẩm
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show'><i class='fas fa-trash-alt me-2'></i> Xóa sản phẩm thành công!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        header("Location: products.php");
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show'><i class='fas fa-exclamation-circle me-2'></i> Lỗi: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        header("Location: products.php");
    }
} else {
    header("Location: products.php");
}
?>
