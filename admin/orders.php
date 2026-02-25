<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Lọc trạng thái
$status = isset($_GET['status']) ? $_GET['status'] : '';
$where = "";
if (!empty($status)) {
    $s = $conn->real_escape_string($status);
    $where = "WHERE o.status = '$s'";
}

// Đếm tổng
$count_res = $conn->query("SELECT COUNT(*) as total FROM orders o $where");
$total_rows = $count_res->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Lấy danh sách
$sql = "SELECT o.*, u.full_name, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        $where
        ORDER BY o.order_date DESC 
        LIMIT $offset, $limit";
$result = $conn->query($sql);
?>

<div class="header-bar mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Quản lý Đơn hàng</h4>
        <p class="text-muted mb-0">Tổng cộng: <?php echo $total_rows; ?> đơn hàng</p>
    </div>
</div>

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" class="d-flex gap-3 align-items-center">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white"><i class="fas fa-filter text-muted"></i></span>
                <select name="status" class="form-select border-start-0" onchange="this.form.submit()">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="pending" <?php if($status=='pending') echo 'selected'; ?>>Chờ xử lý</option>
                    <option value="processing" <?php if($status=='processing') echo 'selected'; ?>>Đang xử lý</option>
                    <option value="shipping" <?php if($status=='shipping') echo 'selected'; ?>>Đang giao hàng</option>
                    <option value="completed" <?php if($status=='completed') echo 'selected'; ?>>Đã giao thành công</option>
                    <option value="cancelled" <?php if($status=='cancelled') echo 'selected'; ?>>Đã hủy</option>
                </select>
            </div>
            <?php if(!empty($status)): ?>
                <a href="orders.php" class="btn btn-light text-danger"><i class="fas fa-times me-1"></i> Bỏ lọc</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0 align-middle table-hover">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4 py-3">Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái</th>
                    <th class="text-end pe-4">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        // Màu sắc trạng thái
                        // Chuẩn hóa chuỗi (chuyển thành chữ thường và xóa khoảng trắng thừa)
                        // Lấy trạng thái chuẩn từ Database
                        $db_status = strtolower(trim($row['status']));

                        // Màu sắc trạng thái
                        $stt_badge = match($db_status) {
                            'completed' => 'bg-success bg-opacity-10 text-success',
                            'pending' => 'bg-warning bg-opacity-10 text-warning',
                            'cancelled' => 'bg-danger bg-opacity-10 text-danger',
                            'shipping' => 'bg-primary bg-opacity-10 text-primary',
                            'processing' => 'bg-info bg-opacity-10 text-info',
                            default => 'bg-secondary bg-opacity-10 text-secondary'
                        };
                        
                        // Chữ hiển thị ra tiếng Việt
                        $stt_text = match($db_status) {
                            'completed' => 'Đã giao',
                            'pending' => 'Chờ xử lý',
                            'cancelled' => 'Đã hủy',
                            'shipping' => 'Đang giao',
                            'processing' => 'Đang xử lý',
                            default => ucfirst($db_status)
                        };
                        
                        $pay_badge = ($row['payment_status'] == 'paid') ? 'text-success' : 'text-muted';
                        $pay_text = ($row['payment_status'] == 'paid') ? 'Đã thanh toán' : 'Chưa thanh toán';
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-primary">#<?php echo $row['id']; ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo $row['full_name']; ?></div>
                            <small class="text-muted"><?php echo $row['email']; ?></small>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['order_date'])); ?></td>
                        
                        <td class="fw-bold"><?php echo number_format($row['total_amount'], 0, ',', '.'); ?> Đ</td>
                        
                        <td>
                            <span class="small fw-bold <?php echo $pay_badge; ?>">
                                <i class="fas fa-circle small me-1" style="font-size: 8px;"></i><?php echo $pay_text; ?>
                            </span>
                        </td>
                        <td><span class="badge rounded-pill <?php echo $stt_badge; ?> px-3"><?php echo $stt_text; ?></span></td>
                        <td class="text-end pe-4">
                            <a href="order_view.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
                                Xem chi tiết <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Chưa có đơn hàng nào.</td></tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-white py-3 border-top-0 d-flex justify-content-end">
        <nav>
            <ul class="pagination pagination-sm mb-0 gap-1">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link rounded <?php echo ($page == $i) ? 'bg-primary border-primary text-white' : 'text-dark border-0 bg-light'; ?>" 
                           href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>"
                           style="min-width: 32px; text-align: center; font-weight: 600;">
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