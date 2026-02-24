<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    die("Thiếu order_id.");
}

$user_id = $_SESSION['user_id'];
$order_id = (int)$_GET['order_id'];

/* Lấy đơn hàng */
$sql = "SELECT * FROM orders 
        WHERE id = ? AND user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Đơn hàng không tồn tại.");
}

$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đặt hàng thành công</title>

    <!-- Favicon -->
    <link rel="icon" href="./assets/favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./assets/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" href="./assets/favicon/apple-touch-icon.png">

    <!-- CSS hệ thống -->
    <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />
    <link rel="stylesheet" href="./assets/css/main.css" />
    <link rel="stylesheet" href="./assets/css/panagition.css" />

    <!-- JS load header -->
    <script src="./assets/js/scripts.js"></script>

    <style>
    * { box-sizing: border-box; }

    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, sans-serif;
        background: #f5f6fa;
    }

    .success-wrapper {
        padding: 60px 20px 80px;
        min-height: calc(100vh - 80px);
    }

    .card {
        max-width: 520px;
        margin: 0 auto;
        background: #fff;
        padding: 35px 30px;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.08);
    }

    .icon-success {
        width: 80px;
        height: 80px;
        margin: 0 auto 15px;
        background: #22c55e;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        color: #fff;
    }

    h2 {
        text-align: center;
        margin: 10px 0 25px;
        font-weight: 600;
    }

    .order-box {
        background: #f8fafc;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }

    .order-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .badge {
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-processing { background: #fff3cd; color: #856404; }
    .badge-paid { background: #d4edda; color: #166534; }
    .badge-pending { background: #cce5ff; color: #1e40af; }

    .notice {
        padding: 14px 16px;
        border-radius: 10px;
        font-size: 14px;
        margin-bottom: 25px;
    }

    .notice-unpaid { background: #fff3cd; color: #856404; }
    .notice-pending { background: #cce5ff; color: #1e40af; }
    .notice-paid { background: #d4edda; color: #166534; }

    /* FIX NÚT */
    .actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .btn {
        width: 100%;
        padding: 12px 18px;
        border-radius: 8px;
        font-size: 14px;
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: .2s;
    }

    .btn-primary {
        background: #2563eb;
        color: #fff;
    }

    .btn-primary:hover { background: #1e40af; }

    .btn-outline {
        border: 1px solid #2563eb;
        color: #2563eb;
    }

    .btn-outline:hover {
        background: #2563eb;
        color: #fff;
    }

    @media (max-width: 500px) {
        .actions {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>

<!-- HEADER -->
<header id="header" class="header"></header>
<script>
document.addEventListener("DOMContentLoaded", function () {
    load("#header", "./templates/header-logined.php");
});
</script>

<!-- CONTENT -->
<main class="success-wrapper">
    <div class="card">

        <div class="icon-success">✓</div>
        <h2>Đặt hàng thành công!</h2>

        <div class="order-box">
            <div class="order-row">
                <span>Mã đơn</span>
                <strong>#<?= $order['id']; ?></strong>
            </div>

            <div class="order-row">
                <span>Tổng tiền</span>
                <strong><?= number_format($order['total_amount']); ?> VND</strong>
            </div>

            <div class="order-row">
                <span>Ngày đặt</span>
                <strong><?= $order['order_date']; ?></strong>
            </div>

            <div class="order-row">
                <span>Trạng thái đơn</span>
                <span class="badge badge-processing"><?= $order['status']; ?></span>
            </div>

            <div class="order-row">
                <span>Thanh toán</span>
                <span class="badge 
                <?= $order['payment_status']=='paid'?'badge-paid':
                    ($order['payment_status']=='pending_confirmation'?'badge-pending':'badge-processing'); ?>">
                    <?= $order['payment_status']; ?>
                </span>
            </div>
        </div>

        <?php
        if ($order['payment_status'] === 'unpaid') {
            echo "<div class='notice notice-unpaid'>Bạn sẽ thanh toán khi nhận hàng (COD).</div>";
        } elseif ($order['payment_status'] === 'pending_confirmation') {
            echo "<div class='notice notice-pending'>Chúng tôi đã ghi nhận thanh toán và đang chờ xác nhận.</div>";
        } elseif ($order['payment_status'] === 'paid') {
            echo "<div class='notice notice-paid'>Thanh toán đã được xác nhận. Đơn hàng đang được xử lý.</div>";
        }
        ?>

        <!-- FIXED CLASS HERE -->
        <div class="actions">
            <a href="index-logined.php" class="btn btn-primary">Tiếp tục mua sắm</a>
            <a href="order-detail.php?order_id=<?= $order['id']; ?>" class="btn btn-outline">Xem đơn hàng</a>
        </div>

    </div>
</main>

</body>
</html>
