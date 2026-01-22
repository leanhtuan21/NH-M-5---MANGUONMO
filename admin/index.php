<?php
// 1. GỌI FILE BẢO VỆ ĐẦU TIÊN (Bắt buộc)
require_once 'includes/auth.php'; 

// 2. Sau đó mới gọi kết nối và giao diện
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// ... (Phần code xử lý thống kê giữ nguyên như cũ) ...
// Code lấy doanh thu, đơn hàng...
$revenue_query = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'");
$revenue = $revenue_query->fetch_assoc()['total'] ?? 0;

$orders_query = $conn->query("SELECT COUNT(*) as total FROM orders");
$orders = $orders_query->fetch_assoc()['total'] ?? 0;

$products_query = $conn->query("SELECT COUNT(*) as total FROM products");
$products = $products_query->fetch_assoc()['total'] ?? 0;

$cust_query = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$customers = $cust_query->fetch_assoc()['total'] ?? 0;

$recent_query = $conn->query("SELECT o.*, u.full_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT 5");
?>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card p-4 h-100">
            <div class="stat-card">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Doanh thu</p>
                    <h3 class="fw-bold mb-0 text-dark">$<?php echo number_format($revenue, 2); ?></h3>
                </div>
                <div class="stat-icon bg-light-primary"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 h-100">
            <div class="stat-card">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Đơn hàng</p>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $orders; ?></h3>
                </div>
                <div class="stat-icon bg-light-warning"><i class="fas fa-shopping-bag"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 h-100">
            <div class="stat-card">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Sản phẩm</p>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $products; ?></h3>
                </div>
                <div class="stat-icon bg-light-info"><i class="fas fa-box-open"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 h-100">
            <div class="stat-card">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Khách hàng</p>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $customers; ?></h3>
                </div>
                <div class="stat-icon bg-light-danger"><i class="fas fa-users"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span>Đơn hàng mới nhất</span>
        <a href="orders.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 table-hover">
            <thead>
                <tr>
                    <th class="ps-4">Mã Đơn</th>
                    <th>Khách hàng</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $recent_query->fetch_assoc()): 
                    $badge = match($row['status']) {
                        'delivered' => 'status-completed',
                        'pending' => 'status-pending',
                        'cancelled' => 'status-cancel',
                        default => 'status-info'
                    };
                    $txt = match($row['status']) {
                        'delivered' => 'Đã giao',
                        'pending' => 'Chờ xử lý',
                        'cancelled' => 'Đã hủy',
                        default => $row['status']
                    };
                ?>
                <tr>
                    <td class="ps-4 fw-bold text-primary">#<?php echo $row['id']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['order_date'])); ?></td>
                    <td class="fw-bold">$<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td><span class="status-badge <?php echo $badge; ?>"><?php echo $txt; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>