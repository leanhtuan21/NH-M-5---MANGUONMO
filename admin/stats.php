<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// --- 1. XỬ LÝ BỘ LỌC THỜI GIAN ---
// Mặc định: 30 ngày gần nhất
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// Chuyển đổi định dạng để query (thêm giờ phút giây để lấy trọn ngày cuối)
$start_sql = $start_date . " 00:00:00";
$end_sql = $end_date . " 23:59:59";

// --- 2. TRUY VẤN DỮ LIỆU TỔNG QUAN ---
// Tổng doanh thu (Chỉ tính đơn đã giao)
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'delivered' AND order_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$revenue = (float)$stmt->get_result()->fetch_assoc()['revenue'];

// Tổng đơn hàng
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE order_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Đơn hàng thành công
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE status = 'delivered' AND order_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$success_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Đơn hàng bị hủy
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE status = 'cancelled' AND order_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$cancelled_orders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;


// --- 3. DỮ LIỆU BIỂU ĐỒ DOANH THU (LINE CHART) ---
// Gom nhóm theo ngày
$chart_sql = "SELECT DATE(order_date) as date, SUM(total_amount) as total 
              FROM orders 
              WHERE status = 'delivered' AND order_date BETWEEN ? AND ? 
              GROUP BY DATE(order_date) 
              ORDER BY date ASC";
$stmt = $conn->prepare($chart_sql);
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$chart_res = $stmt->get_result();

$dates = [];
$totals = [];
while($row = $chart_res->fetch_assoc()) {
    $dates[] = date('d/m', strtotime($row['date']));
    $totals[] = (float)$row['total'];
}
// Nếu không có dữ liệu, thêm dữ liệu mặc định
if (empty($dates)) {
    $dates = ['Không có dữ liệu'];
    $totals = [0];
}

// --- 4. DỮ LIỆU TRẠNG THÁI ĐƠN (DOUGHNUT CHART) ---
$status_sql = "SELECT status, COUNT(*) as count FROM orders WHERE order_date BETWEEN ? AND ? GROUP BY status";
$stmt = $conn->prepare($status_sql);
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$status_res = $stmt->get_result();

$stt_labels = [];
$stt_data = [];
while($row = $status_res->fetch_assoc()) {
    $stt_labels[] = ucfirst($row['status']); // Viết hoa chữ cái đầu
    $stt_data[] = (int)$row['count'];
}
// Nếu không có dữ liệu, thêm dữ liệu mặc định
if (empty($stt_labels)) {
    $stt_labels = ['Không có dữ liệu'];
    $stt_data = [0];
}

// --- 5. TOP SẢN PHẨM BÁN CHẠY ---
$top_prod_sql = "SELECT p.name, SUM(oi.quantity) as sold, SUM(oi.quantity * oi.price_at_purchase) as earned
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 JOIN products p ON oi.product_id = p.id
                 WHERE o.status = 'delivered' AND o.order_date BETWEEN ? AND ?
                 GROUP BY p.id
                 ORDER BY sold DESC
                 LIMIT 5";
$stmt = $conn->prepare($top_prod_sql);
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$top_products = $stmt->get_result();

// --- 6. TOP KHÁCH HÀNG VIP ---
$top_user_sql = "SELECT u.full_name, u.email, COUNT(o.id) as orders_count, SUM(o.total_amount) as total_spent
                 FROM orders o
                 JOIN users u ON o.user_id = u.id
                 WHERE o.status = 'delivered' AND o.order_date BETWEEN ? AND ?
                 GROUP BY u.id
                 ORDER BY total_spent DESC
                 LIMIT 5";
$stmt = $conn->prepare($top_user_sql);
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$top_users = $stmt->get_result();
?>

<div class="header-bar mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Báo cáo Thống kê</h4>
        <p class="text-muted mb-0">Phân tích hiệu quả kinh doanh</p>
    </div>
    
    <form method="GET" class="d-flex align-items-center gap-2 bg-white p-2 rounded shadow-sm">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-0">Từ</span>
            <input type="date" name="start" class="form-control border-0 bg-light fw-bold" value="<?php echo $start_date; ?>">
        </div>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-0">Đến</span>
            <input type="date" name="end" class="form-control border-0 bg-light fw-bold" value="<?php echo $end_date; ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">Xem</button>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Doanh thu thực</p>
                    <h4 class="fw-bold text-success mb-0"><?php echo number_format($revenue, 0, ',', '.'); ?> đ</h4>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle"><i class="fas fa-dollar-sign fa-lg"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Tổng đơn hàng</p>
                    <h4 class="fw-bold text-dark mb-0"><?php echo $total_orders; ?></h4>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle"><i class="fas fa-shopping-bag fa-lg"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Thành công</p>
                    <h4 class="fw-bold text-info mb-0"><?php echo $success_orders; ?></h4>
                </div>
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle"><i class="fas fa-check-circle fa-lg"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted small fw-bold text-uppercase mb-1">Đã hủy</p>
                    <h4 class="fw-bold text-danger mb-0"><?php echo $cancelled_orders; ?></h4>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle"><i class="fas fa-times-circle fa-lg"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="fas fa-chart-line me-2 text-primary"></i>Biểu đồ doanh thu</h6>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" style="height: 350px; width: 100%;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-dark"><i class="fas fa-chart-pie me-2 text-warning"></i>Trạng thái đơn hàng</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="width: 100%; max-width: 280px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0 text-dark">🏆 Top Sản Phẩm Bán Chạy</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Sản phẩm</th>
                            <th class="text-center">Đã bán</th>
                            <th class="text-end pe-4">Thu về</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($top_products->num_rows > 0): ?>
                            <?php while($p = $top_products->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?php echo $p['name']; ?></td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3"><?php echo $p['sold']; ?></span></td>
                                <td class="text-end pe-4 fw-bold"><?php echo number_format($p['earned'], 0, ',', '.'); ?> đ</td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Chưa có dữ liệu</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0 text-dark">💎 Top Khách Hàng VIP</h6>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Khách hàng</th>
                            <th class="text-center">Số đơn</th>
                            <th class="text-end pe-4">Tổng chi tiêu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($top_users->num_rows > 0): ?>
                            <?php while($u = $top_users->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?php echo $u['full_name']; ?></div>
                                    <small class="text-muted"><?php echo $u['email']; ?></small>
                                </td>
                                <td class="text-center fw-bold"><?php echo $u['orders_count']; ?></td>
                                <td class="text-end pe-4 fw-bold text-primary"><?php echo number_format($u['total_spent'], 0, ',', '.'); ?> đ</td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Chưa có dữ liệu</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
    // 1. Biểu đồ Doanh thu (Line)
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Doanh thu (VND)',
                data: <?php echo json_encode($totals); ?>,
                borderColor: '#00A76F',
                backgroundColor: 'rgba(0, 167, 111, 0.1)',
                borderWidth: 2,
                tension: 0.4, // Đường cong mềm mại
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#00A76F',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Biểu đồ Trạng thái (Doughnut)
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($stt_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($stt_data); ?>,
                backgroundColor: [
                    '#00A76F', // Xanh lá (Giao thành công)
                    '#FFAB00', // Vàng (Chờ xử lý)
                    '#00B8D9', // Xanh dương (Đang giao/Xử lý)
                    '#FF5630', // Đỏ (Hủy)
                    '#637381'  // Xám (Khác)
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>