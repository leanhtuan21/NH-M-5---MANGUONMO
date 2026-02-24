<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

if (!isset($_GET['id'])) header("Location: users.php");
$id = (int)$_GET['id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $role = $_POST['role'];
    $new_pass = $_POST['password'];

    // Nếu có nhập mật khẩu mới thì cập nhật, không thì giữ nguyên
    if (!empty($new_pass)) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, address=?, role=?, password=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $email, $phone, $address, $role, $new_pass, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, address=?, role=? WHERE id=?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $address, $role, $id);
    }

    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success'>Cập nhật thông tin thành công!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Lỗi: " . $conn->error . "</div>";
    }
}

// Lấy thông tin user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) die("Người dùng không tồn tại.");
?>

<div class="header-bar mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Chỉnh sửa người dùng</h4>
        <p class="text-muted mb-0">ID: #<?php echo $id; ?></p>
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
                            <label class="fw-bold form-label">Họ và tên</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold form-label">Vai trò</label>
                            <select name="role" class="form-select" <?php echo ($id == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                <option value="customer" <?php echo ($user['role'] == 'customer') ? 'selected' : ''; ?>>Khách hàng</option>
                                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Quản trị viên</option>
                            </select>
                            <?php if ($id == $_SESSION['user_id']): ?>
                                <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="fw-bold form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold form-label">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold form-label text-danger">Đổi mật khẩu mới</label>
                        <input type="text" name="password" class="form-control border-danger" placeholder="Chỉ nhập nếu muốn đổi mật khẩu...">
                        <div class="form-text">Để trống nếu muốn giữ nguyên mật khẩu cũ.</div>
                    </div>

                    <div class="mb-4">
                        <label class="fw-bold form-label">Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo $user['address']; ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end border-top pt-3">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>