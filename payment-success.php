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
<meta charset="UTF-8">
<title>Đặt hàng thành công</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: linear-gradient(135deg, #eef2f7, #dfe6f0);
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
}

.card {
    width: 100%;
    max-width: 650px;
    background: #ffffff;
    padding: 50px 45px;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    animation: fadeIn 0.4s ease-in-out;
}

@keyframes fadeIn {
    from {opacity:0; transform: translateY(15px);}
    to {opacity:1; transform: translateY(0);}
}

.icon-success {
    width: 90px;
    height: 90px;
    margin: 0 auto 20px;
    background: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 45px;
    color: white;
}

h2 {
    text-align: center;
    margin: 0 0 30px;
    font-weight: 600;
    color: #222;
}

.order-box {
    background: #f8fafc;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.order-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
}

.order-row strong {
    color: #333;
}

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-processing { background: #fff3cd; color: #856404; }
.badge-paid { background: #d4edda; color: #155724; }
.badge-pending { background: #cce5ff; color: #004085; }

.notice {
    padding: 15px 18px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 30px;
}

.notice-unpaid { background: #fff3cd; color: #856404; }
.notice-pending { background: #cce5ff; color: #004085; }
.notice-paid { background: #d4edda; color: #155724; }

.actions {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.btn {
    padding: 12px 22px;
    border-radius: 8px;
    font-size: 14px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.25s ease;
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1e40af;
}

.btn-outline {
    border: 1px solid #2563eb;
    color: #2563eb;
}

.btn-outline:hover {
    background: #2563eb;
    color: white;
}
</style>
</head>

<body>

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
        echo "<div class='notice notice-unpaid'>
                Bạn sẽ thanh toán khi nhận hàng (COD).
              </div>";
    } elseif ($order['payment_status'] === 'pending_confirmation') {
        echo "<div class='notice notice-pending'>
                Chúng tôi đã ghi nhận thanh toán và đang chờ xác nhận.
              </div>";
    } elseif ($order['payment_status'] === 'paid') {
        echo "<div class='notice notice-paid'>
                Thanh toán đã được xác nhận. Đơn hàng đang được xử lý.
              </div>";
    }
    ?>

    <div class="actions">
        <a href="index-logined.php" class="btn btn-primary">Tiếp tục mua sắm</a>
        <a href="order-detail.php?order_id=<?= $order['id']; ?>" class="btn btn-outline">Xem đơn hàng</a>
    </div>

</div>

</body>
</html>

