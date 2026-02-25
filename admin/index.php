<?php
// 1. GỌI FILE BẢO VỆ ĐẦU TIÊN (Bắt buộc)
require_once 'includes/auth.php'; 

// 2. Sau đó mới gọi kết nối và giao diện
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// --- TRUY VẤN DỮ LIỆU THỐNG KÊ ---

// ĐÃ SỬA: Thay 'delivered' thành 'completed' để tính đúng doanh thu từ DB
$revenue_query = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
$revenue = $revenue_query->fetch_assoc()['total'] ?? 0;

$orders_query = $conn->query("SELECT COUNT(*) as total FROM orders");
$orders = $orders_query->fetch_assoc()['total'] ?? 0;

$products_query = $conn->query("SELECT COUNT(*) as total FROM products");
$products = $products_query->fetch_assoc()['total'] ?? 0;

$cust_query = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$customers = $cust_query->fetch_assoc()['total'] ?? 0;

// Lấy 5 đơn hàng mới nhất
$recent_query = $conn->query("SELECT o.*, u.full_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT 5");
?>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card p-4 h-100 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Doanh thu</p>
                    <h3 class="fw-bold mb-0 text-success"><?php echo number_format($revenue, 0, ',', '.'); ?> Đ</h3>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle"><i class="fas fa-money-bill-wave fa-lg"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 h-100 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Đơn hàng</p>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($orders, 0, ',', '.'); ?></h3>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle"><i class="fas fa-shopping-bag fa-lg"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 h-100 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Sản phẩm</p>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($products, 0, ',', '.'); ?></h3>
                </div>
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle"><i class="fas fa-box-open fa-lg"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 h-100 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Khách hàng</p>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($customers, 0, ',', '.'); ?></h3>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle"><i class="fas fa-users fa-lg"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
        <span class="fw-bold text-dark m-0"><i class="fas fa-shopping-cart text-primary me-2"></i> Đơn hàng mới nhất</span>
        <a href="orders.php" class="btn btn-sm btn-outline-primary fw-bold px-3">Xem tất cả</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 table-hover align-middle">
            <thead class="bg-light">
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
                    // Chuẩn hóa trạng thái từ DB
                    $stt = strtolower(trim($row['status']));

                    $badge = match($stt) {
                        'completed' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                        'pending'   => 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                        'cancelled' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                        'shipping'  => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                        'paid'      => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                        default     => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25'
                    };

                    $txt = match($stt) {
                        'completed' => 'Đã giao',
                        'pending'   => 'Chờ xử lý',
                        'paid'      => 'Đã thanh toán',
                        'shipping'  => 'Đang giao',
                        'cancelled' => 'Đã hủy',
                        default     => ucfirst($stt)
                    };
                ?>
                <tr>
                    <td class="ps-4 fw-bold text-primary">#<?php echo $row['id']; ?></td>
                    <td class="fw-bold text-dark"><?php echo $row['full_name']; ?></td>
                    <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($row['order_date'])); ?></td>
                    <td class="fw-bold text-danger"><?php echo number_format($row['total_amount'], 0, ',', '.'); ?> Đ</td>
                    <td><span class="badge rounded-pill <?php echo $badge; ?> px-3"><?php echo $txt; ?></span></td>
                </tr>
                <?php endwhile; ?>
                
                <?php if($recent_query->num_rows == 0): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Chưa có dữ liệu đơn hàng nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>