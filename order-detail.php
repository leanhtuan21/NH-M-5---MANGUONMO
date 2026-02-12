<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    die("Thiếu order_id");
}

$user_id = $_SESSION['user_id'];
$order_id = (int)$_GET['order_id'];

/* ===============================
   LẤY THÔNG TIN ORDER
=================================*/
$sql = "SELECT * FROM orders 
        WHERE id = ? AND user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Đơn hàng không tồn tại hoặc không thuộc về bạn.");
}

$order = $result->fetch_assoc();

/* ===============================
   LẤY DANH SÁCH SẢN PHẨM
=================================*/
$item_sql = "SELECT oi.*, p.name, p.image
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?";

$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items = $item_stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng</title>
    <style>
        body { font-family: Arial; }
        .container { width: 800px; margin: 30px auto; }
        .order-box { border: 1px solid #ccc; padding: 20px; }
        .item { display: flex; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .item img { width: 80px; margin-right: 15px; }
        .status { font-weight: bold; color: green; }
        .btn {
            padding: 8px 15px;
            background: black;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="order-box">
        <h2>Chi tiết đơn hàng #<?= $order['id']; ?></h2>

        <p><strong>Ngày đặt:</strong> <?= $order['created_at']; ?></p>
        <p><strong>Trạng thái:</strong> 
            <span class="status"><?= strtoupper($order['status']); ?></span>
        </p>

        <h3>Sản phẩm:</h3>

        <?php while ($item = $items->fetch_assoc()): ?>
            <div class="item">
                <img src="uploads/<?= htmlspecialchars($item['image']); ?>" alt="">
                <div>
                    <p><strong><?= htmlspecialchars($item['name']); ?></strong></p>
                    <p>Số lượng: <?= $item['quantity']; ?></p>
                    <p>Giá: <?= number_format($item['price']); ?> đ</p>
                </div>
            </div>
        <?php endwhile; ?>

        <hr>
        <h3>Tổng tiền: <?= number_format($order['total_amount']); ?> đ</h3>

        <!-- Nút xác nhận nhận hàng -->
        <?php if ($order['status'] === 'delivered'): ?>
            <form action="confirm-received.php" method="POST">
                <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                <button class="btn" type="submit">
                    Tôi đã nhận được hàng
                </button>
            </form>
        <?php endif; ?>

        <!-- Nút đánh giá -->
        <?php if ($order['status'] === 'completed'): ?>
            <a href="add-review.php?order_id=<?= $order['id']; ?>">
                <button class="btn">
                    Đánh giá sản phẩm
                </button>
            </a>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
