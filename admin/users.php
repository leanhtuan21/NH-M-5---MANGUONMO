<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// 1. XỬ LÝ LỌC VÀ TÌM KIẾM
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';

$where_clauses = [];
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $where_clauses[] = "(full_name LIKE '%$s%' OR email LIKE '%$s%' OR phone LIKE '%$s%')";
}
if (!empty($role)) {
    $where_clauses[] = "role = '$role'";
}

$sql_where = "";
if (count($where_clauses) > 0) {
    $sql_where = "WHERE " . implode(' AND ', $where_clauses);
}

// Đếm tổng
$total_rows = $conn->query("SELECT COUNT(*) as total FROM users $sql_where")->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Lấy danh sách
$sql = "SELECT * FROM users $sql_where ORDER BY id DESC LIMIT $offset, $limit";
$result = $conn->query($sql);
?>

<div class="header-bar mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Quản lý Người dùng</h4>
        <p class="text-muted mb-0">Tổng số: <?php echo $total_rows; ?> tài khoản</p>
    </div>
    <a href="user_add.php" class="btn btn-primary shadow-sm px-4">
        <i class="fas fa-user-plus me-2"></i>Thêm tài khoản
    </a>
</div>

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0" placeholder="Tìm tên, email, sđt..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select bg-light border-0">
                    <option value="">-- Tất cả vai trò --</option>
                    <option value="customer" <?php if($role=='customer') echo 'selected'; ?>>Khách hàng</option>
                    <option value="admin" <?php if($role=='admin') echo 'selected'; ?>>Quản trị viên</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">Lọc</button>
            </div>
            <?php if(!empty($search) || !empty($role)): ?>
            <div class="col-md-2">
                <a href="users.php" class="btn btn-light w-100 text-danger"><i class="fas fa-undo"></i> Bỏ lọc</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0 align-middle table-hover">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4 py-3">Người dùng</th>
                    <th>Liên hệ</th>
                    <th>Địa chỉ</th>
                    <th>Vai trò</th>
                    <th class="text-end pe-4">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 text-white shadow-sm" 
                                     style="width: 45px; height: 45px; background: linear-gradient(135deg, #00A76F, #007867);">
                                    <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark"><?php echo $row['full_name']; ?></h6>
                                    <small class="text-muted">ID: #<?php echo $row['id']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-bold text-dark"><?php echo $row['email']; ?></div>
                            <div class="small text-muted"><i class="fas fa-phone-alt me-1" style="font-size: 10px;"></i> <?php echo $row['phone'] ?? '---'; ?></div>
                        </td>
                        <td>
                            <span class="text-muted small" style="display: block; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo $row['address'] ?? 'Chưa cập nhật'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if($row['role'] == 'admin'): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3">Quản trị viên</span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3">Khách hàng</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <a href="user_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-light text-primary btn-sm me-1" title="Sửa thông tin">
                                <i class="fas fa-pen"></i>
                            </a>
                            <?php if($row['id'] != $_SESSION['user_id']): ?>
                                <a href="user_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-light text-danger btn-sm" title="Xóa" onclick="return confirm('CẢNH BÁO: Xóa người dùng sẽ xóa tất cả đơn hàng liên quan. Tiếp tục?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-light text-muted btn-sm" disabled><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Không tìm thấy người dùng nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): 
        $query_params = $_GET; unset($query_params['page']); $query_str = http_build_query($query_params);
    ?>
    <div class="card-footer bg-white py-3 border-top-0 d-flex justify-content-end">
        <nav>
            <ul class="pagination pagination-sm mb-0 gap-1">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link rounded <?php echo ($page == $i) ? 'bg-primary border-primary text-white' : 'text-dark border-0 bg-light'; ?>" href="?page=<?php echo $i; ?>&<?php echo $query_str; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>