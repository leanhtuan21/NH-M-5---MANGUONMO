<?php $p = basename($_SERVER['PHP_SELF']); ?>

<div class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-shopping-basket me-2"></i> Grocery<span>Mart</span>
    </div>
    
    <div class="sidebar-label">Quản lý chính</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo $p=='index.php'?'active':''; ?>">
                <i class="fas fa-th-large"></i> Tổng quan
            </a>
        </li>
        <li class="nav-item">
            <a href="stats.php" class="nav-link <?php echo $p=='stats.php'?'active':''; ?>">
                <i class="fas fa-chart-bar"></i> Thống kê
            </a>
        </li>
    </ul>

    <div class="sidebar-label">Bán hàng</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="products.php" class="nav-link <?php echo ($p=='products.php'||$p=='product_add.php')?'active':''; ?>">
                <i class="fas fa-box-open"></i> Sản phẩm
            </a>
        </li>
        <li class="nav-item">
            <a href="orders.php" class="nav-link <?php echo $p=='orders.php'?'active':''; ?>">
                <i class="fas fa-receipt"></i> Đơn hàng
            </a>
        </li>
        <li class="nav-item">
            <a href="users.php" class="nav-link <?php echo $p=='users.php'?'active':''; ?>">
                <i class="fas fa-users"></i> Khách hàng
            </a>
        </li>
    </ul>

    <div class="sidebar-label">Hệ thống</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="profile.php" class="nav-link <?php echo $p=='profile.php'?'active':''; ?>">
                    <i class="fas fa-user-circle"></i> Hồ sơ Admin
                </a>
            </li>
        </ul>

        <div class="mt-auto p-4">
            <div class="text-white mb-2 small text-center">
                Xin chào, <strong><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></strong>
            </div>
            <a href="../logout.php" class="btn btn-danger w-100 fw-bold d-flex align-items-center justify-content-center">
                <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
            </a>
        </div>
</div>

<div class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h4 class="fw-bold text-dark mb-0">Hệ thống quản trị</h4>
            <p class="text-muted small mb-0">Hôm nay: <?php echo date('d/m/Y'); ?></p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="../index.php" target="_blank" class="btn btn-white bg-white border shadow-sm">
                <i class="fas fa-globe me-2"></i>Xem Website
            </a>
            <div class="d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">A</div>
                <div class="ms-2">
                    <div class="fw-bold small">Admin</div>
                    <div class="text-muted" style="font-size: 11px;">Super User</div>
                </div>
            </div>
        </div>
    </div>