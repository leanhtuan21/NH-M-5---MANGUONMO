<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit;
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
    $update = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND user_id = ?");
    $update->bind_param("ii", $order_id, $_SESSION['user_id']);
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
    die("Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.");
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
    p.brand,
    (
        SELECT image_url 
        FROM product_images 
        WHERE product_id = p.id 
        ORDER BY is_main DESC LIMIT 1
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
$total_product_price = 0; // Biến tính tổng tiền hàng
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total_product_price += ($row['quantity'] * $row['price_at_purchase']); // Cộng dồn tiền hàng
}

function getStatusText($status) {
    return match($status) {
        'pending'   => 'Chờ xử lý',
        'paid'      => 'Đã thanh toán',
        'shipping'  => 'Đang giao hàng',
        'completed' => 'Đã hoàn thành',
        'cancelled' => 'Đã hủy',
        default     => $status
    };
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= $order_id ?></title>
    <link rel="stylesheet" href="./assets/css/main.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 15px; }
        .card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 8px 25px rgba(0,0,0,0.05); }
        .title-group { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f8f9fa; padding-bottom: 15px; }
        .status-badge { padding: 6px 16px; border-radius: 50px; font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .status-shipping { background: #e0f2fe; color: #0284c7; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .product-item { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #f1f1f1; }
        .product-info { display: flex; gap: 20px; align-items: center; }
        .product-item img { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 1px solid #eee; }
        .total-section { margin-top: 30px; padding-top: 20px; border-top: 2px solid #f8f9fa; text-align: right; }
        .shipping-note { color: #64748b; font-size: 13px; font-style: italic; margin-top: 5px; display: block; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; transition: 0.3s; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-confirm { background: #16a34a; color: white; width: 100%; margin-top: 20px; }
        .btn-confirm:hover { background: #15803d; }
        .btn-review { background: #fff; color: #2563eb; border: 1px solid #2563eb; }
        .btn-review:hover { background: #2563eb; color: #fff; }
    </style>
</head>
<body>

<header id="header" class="header"></header>
<script src="./assets/js/scripts.js"></script>
<script>load("#header", "./templates/header-logined.php");</script>

<div class="container">
    <div class="card">
        <div class="title-group">
            <div>
                <h2 style="margin:0">Đơn hàng #<?= $order['id']; ?></h2>
                <small class="text-muted">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['order_date'])); ?></small>
            </div>
            <span class="status-badge status-<?= $status ?>">
                <?= getStatusText($status); ?>
            </span>
        </div>

        <?php foreach ($items as $item): ?>
        <div class="product-item">
            <div class="product-info">
                <img src="<?= !empty($item['product_image']) ? './assets/img/product/' . $item['product_image'] : './assets/img/product/no-image.png'; ?>" 
                     onerror="this.src='./assets/img/product/item-1.png'">
                <div>
                    <div class="fw-bold" style="font-size: 16px;"><?= htmlspecialchars($item['product_name']); ?></div>
                    <div class="text-muted small">Thương hiệu: <?= htmlspecialchars($item['brand'] ?? 'GroceryMart'); ?></div>
                    <div class="mt-1">Số lượng: <strong>x<?= $item['quantity']; ?></strong></div>
                </div>
            </div>
            
            <div class="text-end">
                <div class="fw-bold text-dark"><?= number_format($item['price_at_purchase']); ?> đ</div>
                <?php if ($status === 'completed'): ?>
                    <a href="add_review.php?order_item_id=<?= $item['order_item_id'] ?>&product_id=<?= $item['product_id'] ?>&order_id=<?= $order_id ?>" 
                       class="btn btn-review mt-2">
                       Đánh giá
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="total-section">
            <div class="h3 fw-bold mt-2">Tổng tiền hàng: <span style="color: #e11d48;"><?= number_format($total_product_price); ?> đ</span></div>
            <span class="shipping-note">* Phí vận chuyển sẽ được thanh toán riêng cho đơn vị vận chuyển khi nhận hàng.</span>
            
            <?php if ($status === 'shipping'): ?>
                <form method="POST">
                    <button type="submit" name="confirm_received" class="btn btn-confirm" onclick="return confirm('Bạn xác nhận đã nhận được kiện hàng này?')">
                        <i class="fas fa-check-circle me-2"></i>Tôi đã nhận được hàng
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>