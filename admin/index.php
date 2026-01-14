<?php
// 1. Kết nối CSDL
require_once '../db_connect.php';

// 2. Kiểm tra quyền Admin (Tạm ẩn để test, sau này mở ra)
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     header("Location: ../sign-in.php");
//     exit();
// }

// 3. Truy vấn thống kê (Chuẩn MySQLi)
try {
    // Tổng doanh thu
    $sqlRevenue = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'";
    $resultRevenue = $conn->query($sqlRevenue);
    $rowRevenue = $resultRevenue->fetch_assoc();
    $revenue = $rowRevenue['total'] ?? 0;

    // Tổng đơn hàng
    $sqlOrders = "SELECT COUNT(*) as total FROM orders";
    $resultOrders = $conn->query($sqlOrders);
    $rowOrders = $resultOrders->fetch_assoc();
    $totalOrders = $rowOrders['total'];

    // Tổng khách hàng
    $sqlUsers = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
    $resultUsers = $conn->query($sqlUsers);
    $rowUsers = $resultUsers->fetch_assoc();
    $totalCustomers = $rowUsers['total'];

    // Lấy 5 đơn hàng mới nhất
    $sqlRecent = "
        SELECT o.id, u.full_name, o.total_amount, o.status, o.order_date 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.order_date DESC 
        LIMIT 5
    ";
    $resultRecent = $conn->query($sqlRecent);

} catch (Exception $e) {
    die("Lỗi truy vấn dữ liệu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Grocery Mart</title>
    
    <link rel="stylesheet" href="../assets/fonts/stylesheet.css">
    <link rel="stylesheet" href="../assets/css/main.css">

    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        body { background-color: #f6f6f6; }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #fff;
            padding: 24px;
            border-right: 1px solid #eee;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            z-index: 100;
        }
        
        .admin-logo {
            font-size: 24px;
            font-weight: 700;
            color: #1a162e;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
            text-decoration: none;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #9e9da8;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 8px;
            font-weight: 500;
            transition: 0.2s;
        }

        .menu-item:hover, .menu-item.active {
            background-color: #ffb700;
            color: #1a162e;
        }

        .menu-item i { font-size: 20px; }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title { font-size: 24px; font-weight: 700; color: #1a162e; }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.blue { background: #e3f2fd; color: #2196f3; }
        .stat-icon.green { background: #e8f5e9; color: #4caf50; }
        .stat-icon.orange { background: #fff3e0; color: #ff9800; }
        .stat-icon.purple { background: #f3e5f5; color: #9c27b0; }

        .stat-info h3 { font-size: 24px; font-weight: 700; margin: 0; color: #1a162e; }
        .stat-info p { margin: 0; color: #9e9da8; font-size: 14px; }

        /* Recent Orders Table */
        .section-box {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }

        .section-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: #1a162e; }

        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { text-align: left; color: #9e9da8; font-weight: 500; padding: 12px; border-bottom: 1px solid #eee; }
        .custom-table td { padding: 16px 12px; color: #1a162e; border-bottom: 1px solid #f6f6f6; font-weight: 500; }
        .custom-table tr:last-child td { border-bottom: none; }

        /* Status Badges */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge.pending { background: #fff3e0; color: #ff9800; }
        .badge.delivered { background: #e8f5e9; color: #4caf50; }
        .badge.cancelled { background: #ffebee; color: #f44336; }
        .badge.processing { background: #e3f2fd; color: #2196f3; }

        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .sidebar { transform: translateX(-100%); transition: 0.3s; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <aside class="sidebar">
            <a href="../index.php" class="admin-logo">
                <i class="ph ph-shopping-cart"></i> GroceryMart
            </a>
            
            <nav>
                <a href="index.php" class="menu-item active">
                    <i class="ph ph-squares-four"></i> Dashboard
                </a>
                <a href="#!" class="menu-item">
                    <i class="ph ph-package"></i> Sản phẩm
                </a>
                <a href="#!" class="menu-item">
                    <i class="ph ph-shopping-cart-simple"></i> Đơn hàng
                </a>
                <a href="#!" class="menu-item">
                    <i class="ph ph-users"></i> Khách hàng
                </a>
                <a href="#!" class="menu-item">
                    <i class="ph ph-chat-circle-dots"></i> Tin nhắn
                </a>
                <a href="#!" class="menu-item">
                    <i class="ph ph-gear"></i> Cài đặt
                </a>
                <a href="../index.php" class="menu-item" style="margin-top: 40px; color: #f44336;">
                    <i class="ph ph-sign-out"></i> Đăng xuất
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <h1 class="page-title">Tổng quan</h1>
                <a href="add-product.php"><div class="user-info">
                    <button class="btn btn--primary btn--small">
                        <i class="ph ph-plus"></i> Thêm sản phẩm
                    </button>
                </div></a>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="ph ph-currency-dollar"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($revenue, 2); ?></h3>
                        <p>Tổng doanh thu</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="ph ph-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalOrders; ?></h3>
                        <p>Tổng đơn hàng</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="ph ph-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalCustomers; ?></h3>
                        <p>Khách hàng</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="ph ph-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3>4.8</h3>
                        <p>Đánh giá TB</p>
                    </div>
                </div>
            </div>

            <div class="section-box">
                <div class="page-header" style="margin-bottom: 20px;">
                    <h2 class="section-title">Đơn hàng gần đây</h2>
                    <a href="#!" style="color: #0071dc; text-decoration: none; font-weight: 500;">Xem tất cả</a>
                </div>
                
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultRecent && $resultRecent->num_rows > 0): ?>
                            <?php while($row = $resultRecent->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="ph ph-user-circle" style="font-size: 20px; color: #9e9da8;"></i>
                                            <?php echo htmlspecialchars($row['full_name'] ?? 'Khách vãng lai'); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['order_date'])); ?></td>
                                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo strtolower($row['status']); ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="#!" style="color: #9e9da8;"><i class="ph ph-dots-three-outline-vertical"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #999;">Chưa có đơn hàng nào</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

</body>
</html>