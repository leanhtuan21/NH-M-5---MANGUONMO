<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$id = $_SESSION['user_id'];
$msg = '';

// XỬ LÝ CẬP NHẬT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $new_pass = trim($_POST['password']);

    // 1. Kiểm tra Email có bị trùng với người khác không?
    $check = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $id");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>Email này đã được sử dụng bởi tài khoản khác!</div>";
    } else {
        // 2. Kiểm tra có đổi mật khẩu không?
        if (!empty($new_pass)) {
            // Có đổi pass
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, address=?, password=? WHERE id=?");
            $stmt->bind_param("sssssi", $name, $email, $phone, $address, $new_pass, $id);
        } else {
            // Không đổi pass (Giữ nguyên)
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, address=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $id);
        }

        if ($stmt->execute()) {
            $msg = "<div class='alert alert-success'>Cập nhật hồ sơ thành công!</div>";
            // Cập nhật lại tên trong Session để Sidebar hiển thị đúng ngay lập tức
            $_SESSION['full_name'] = $name;
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi: " . $conn->error . "</div>";
        }
    }
}

// LẤY THÔNG TIN HIỆN TẠI
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<div class="header-bar mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Hồ sơ cá nhân</h4>
        <p class="text-muted mb-0">Quản lý thông tin tài khoản của bạn</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 text-center p-4">
            <div class="card-body">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow mx-auto mb-3" 
                     style="width: 100px; height: 100px; font-size: 2.5rem; background: linear-gradient(135deg, #00A76F, #007867);">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                
                <h5 class="fw-bold text-dark mb-1"><?php echo $user['full_name']; ?></h5>
                <p class="text-muted small mb-3"><?php echo $user['email']; ?></p>
                
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill border border-primary border-opacity-25">
                    <?php echo strtoupper($user['role']); ?>
                </span>

                <hr class="my-4 text-muted opacity-25">

                <div class="text-start">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-light rounded p-2 me-3"><i class="fas fa-phone text-success"></i></div>
                        <div>
                            <small class="text-muted d-block text-uppercase font-size-sm fw-bold">Điện thoại</small>
                            <span class="text-dark fw-bold"><?php echo $user['phone'] ?? '---'; ?></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-light rounded p-2 me-3"><i class="fas fa-map-marker-alt text-danger"></i></div>
                        <div>
                            <small class="text-muted d-block text-uppercase font-size-sm fw-bold">Địa chỉ</small>
                            <span class="text-dark small"><?php echo $user['address'] ?? 'Chưa cập nhật'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0"><i class="fas fa-user-edit me-2 text-primary"></i>Cập nhật thông tin</h6>
            </div>
            <div class="card-body p-4">
                <?php echo $msg; ?>
                
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email đăng nhập <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Vai trò</label>
                            <input type="text" class="form-control bg-light" value="<?php echo ucfirst($user['role']); ?>" disabled>
                            <div class="form-text small">Bạn không thể tự thay đổi vai trò của mình.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo $user['address']; ?></textarea>
                    </div>

                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark"><i class="fas fa-key me-1"></i> Đổi mật khẩu</label>
                            <input type="password" name="password" class="form-control border-warning" placeholder="Nhập mật khẩu mới (Nếu muốn đổi)">
                            <div class="form-text text-dark opacity-75">Chỉ nhập vào ô này nếu bạn muốn thay đổi mật khẩu hiện tại. Nếu không, hãy để trống.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                            <i class="fas fa-save me-2"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>