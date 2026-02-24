<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $role = $_POST['role'];

    // 1. Kiểm tra Email đã tồn tại chưa
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>Email này đã được sử dụng! Vui lòng chọn email khác.</div>";
    } else {
        // 2. Thêm mới - Hash password bằng MD5
        $hashedPassword = md5($password);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $hashedPassword, $phone, $address, $role);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert alert-success'><i class='fas fa-check-circle me-1'></i> Tạo tài khoản thành công! <a href='users.php'>Về danh sách</a></div>";
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi: " . $conn->error . "</div>";
        }
    }
}
?>

<div class="header-bar mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Thêm người dùng mới</h4>
        <p class="text-muted mb-0">Tạo tài khoản cho Khách hàng hoặc Admin</p>
    </div>
    <a href="users.php" class="btn btn-light border shadow-sm px-4">
        <i class="fas fa-arrow-left me-2"></i>Quay lại
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <?php echo $msg; ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="fw-bold form-label">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required placeholder="Nguyễn Văn A">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold form-label">Vai trò <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="customer">Khách hàng</option>
                                <option value="admin">Quản trị viên (Admin)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="fw-bold form-label">Email đăng nhập <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="email@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold form-label">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" placeholder="09xxxxxxxx">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="text" name="password" class="form-control" required placeholder="Nhập mật khẩu...">
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold form-label">Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Số nhà, đường, phường/xã..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end border-top pt-3">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Tạo tài khoản</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>