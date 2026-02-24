<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập");
}

if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
} elseif (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
} else {
    die("Thiếu ID đơn hàng");
}

/* =========================
   USER XÁC NHẬN ĐÃ NHẬN HÀNG
========================= */
if (isset($_POST['confirm_received'])) {
    $update = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
    $update->bind_param("i", $order_id);
    $update->execute();

    header("Location: order-detail.php?id=" . $order_id);
    exit;
}

/* =========================
   LẤY THÔNG TIN ĐƠN HÀNG
========================= */
$order_sql = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Không tìm thấy đơn hàng");
}

$status = $order['status'];

/* =========================
   LẤY DANH SÁCH SẢN PHẨM
========================= */
$sql = "
SELECT 
    oi.id AS order_item_id,
    oi.product_id,
    oi.quantity,
    oi.price_at_purchase,
    p.name AS product_name,
    (
        SELECT image_url 
        FROM product_images 
        WHERE product_id = p.id 
        LIMIT 1
    ) AS product_image
FROM order_items oi
JOIN products p ON oi.product_id = p.id
WHERE oi.order_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();

$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Chi tiết đơn hàng</title>

<link rel="stylesheet" href="./assets/css/main.css" />
<script src="./assets/js/scripts.js"></script>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #f5f6fa;
    margin: 0;
}
.container {
    max-width: 900px;
    margin: 40px auto;
}
.card {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.06);
}
.title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 20px;
}
.info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 25px;
    color: #555;
}
.status {
    font-weight: bold;
    color: #16a34a;
}
.product {
    display: flex;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}
.product img {
    width: 90px;
    height: 90px;
    border-radius: 8px;
    object-fit: cover;
    background: #f0f0f0;
}
.product-name {
    font-weight: 600;
}
.price {
    color: #666;
    font-size: 14px;
}
.total {
    text-align: right;
    margin-top: 20px;
    font-size: 20px;
    font-weight: bold;
}
.total span {
    color: #e11d48;
}
.btn {
    margin-top: 10px;
    padding: 10px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
}
.btn-confirm {
    background: #16a34a;
    color: white;
}
.btn-review {
    background: #2563eb;
    color: white;
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

<div class="container">
<div class="card">

<div class="title">Chi tiết đơn hàng #<?= $order['id']; ?></div>

<div class="info">
    <div>Ngày đặt: <?= $order['created_at'] ?? $order['order_date']; ?></div>
    <div class="status"><?= strtoupper($order['status']); ?></div>
</div>

<?php foreach ($items as $item): ?>
<div class="product">
    <img src="<?= !empty($item['product_image']) 
        ? 'uploads/' . $item['product_image'] 
        : 'uploads/no-image.png'; ?>">

    <div>
        <div class="product-name"><?= htmlspecialchars($item['product_name']); ?></div>
        <div class="price">Số lượng: <?= $item['quantity']; ?></div>
        <div class="price">Giá lúc mua: <?= number_format($item['price_at_purchase']); ?> đ</div>
    </div>
</div>
<?php endforeach; ?>

<div class="total">
    Tổng tiền: <span><?= number_format($order['total_amount']); ?> đ</span>
</div>

<!-- ACTION BUTTONS -->
<div class="order-actions">

<?php if ($status === 'shipping'): ?>
    <form method="POST">
        <button type="submit" name="confirm_received" class="btn btn-confirm">
            Tôi đã nhận được hàng
        </button>
    </form>
<?php endif; ?>


<?php if ($status === 'completed'): ?>
    <?php foreach ($items as $item): ?>
        <form action="add_review.php" method="GET">
            <input type="hidden" name="order_item_id" value="<?= $item['order_item_id'] ?>">
            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">
            <button class="btn btn-review">
                Đánh giá sản phẩm
            </button>
        </form>
    <?php endforeach; ?>
<?php endif; ?>

</div>

</div>
</div>

</body>
</html>