<?php
session_start();
require_once __DIR__ . "/db_connect.php";

/* ===== 1. Kiểm tra mã đơn ===== */
if (!isset($_GET['code']) || empty($_GET['code'])) {
    header("Location: checkout.php");
    exit;
}

$order_code = $_GET['code'];

/* ===== 2. Lấy đơn hàng (1 lần duy nhất) ===== */
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
$stmt->bind_param("s", $order_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Không tìm thấy đơn hàng.");
}

$order = $result->fetch_assoc();
$stmt->close();

$order_id = $order['id'];

/* ===== 3. Nếu đã thanh toán thì chuyển trang chi tiết ===== */
if ($order['payment_status'] === 'paid') {
    header("Location: order-detail.php?code=" . $order_code);
    exit;
}

/* ===== NGÀY GIAO DỰ KIẾN ===== */
$today = new DateTime();
$delivery_from = (clone $today)->modify('+3 days')->format('d/m/Y');
$delivery_to   = (clone $today)->modify('+7 days')->format('d/m/Y');

/* ===== LẤY TỔNG SẢN PHẨM ===== */
$stmt2 = $conn->prepare("
    SELECT SUM(quantity) AS total_qty,
           SUM(quantity * price_at_purchase) AS total_amount
    FROM order_items
    WHERE order_id = ?
");
$stmt2->bind_param("i", $order['id']);
$stmt2->execute();
$sum = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

$total_qty = $sum['total_qty'] ?? 0;
$total_amount = $sum['total_amount'] ?? 0;

/* ===== LẤY DANH SÁCH ITEM ===== */
$stmt_items = $conn->prepare("
    SELECT 
        p.name AS product_name,
        oi.quantity,
        oi.price_at_purchase,
        img.image_url
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    LEFT JOIN product_images img 
        ON img.product_id = p.id AND img.is_main = 1
    WHERE oi.order_id = ?
");
$stmt_items->bind_param("i", $order['id']);
$stmt_items->execute();
$items = $stmt_items->get_result();

/* ===== PHÍ SHIP ===== */
$shipping_fee = 15000; //đang để tạm cố định là 15k phí ship 
if ($total_amount >= 5000000) {
    $shipping_fee = 0;
}

/* ===== TỔNG THANH TOÁN ===== */
$grand_total = $total_amount + $shipping_fee;

/* ===== QR CODE ===== */
$bank_id = "970422";
$account_no = "0338379358";

$qr_url = "https://api.vietqr.io/image/$bank_id-$account_no-compact2.png?amount=$grand_total&addInfo=DH$order_code";

/* ===== LẤY ĐỊA CHỈ ===== */
$address = null;
if (!empty($order['address_id'])) {
    $stmt3 = $conn->prepare("SELECT * FROM shipping_addresses WHERE id=?");
    $stmt3->bind_param("i", $order['address_id']);
    $stmt3->execute();
    $address = $stmt3->get_result()->fetch_assoc();
    $stmt3->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thanh toán | Grocery Mart</title>

    <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />
    <link rel="stylesheet" href="./assets/css/main.css" />
    <script src="./assets/js/scripts.js"></script>
</head>

<body>

<!-- HEADER -->
<header id="header" class="header"></header>
<script>
    load("#header", "./templates/header-logined.php");
</script>

<main class="checkout-page">
<div class="container">

    <!-- Breadcrumb -->
    <div class="checkout-container">
        <ul class="breadcrumbs checkout-page__breadcrumbs">
            <li>
                <a href="./" class="breadcrumbs__link">
                    Trang chủ
                    <img src="./assets/icons/arrow-right.svg" />
                </a>
            </li>
            <li>
                <a href="./checkout.php" class="breadcrumbs__link">
                    Giỏ hàng
                    <img src="./assets/icons/arrow-right.svg" />
                </a>
            </li>
            <li>
                <a href="./shipping.php" class="breadcrumbs__link">
                    Địa chỉ giao hàng
                    <img src="./assets/icons/arrow-right.svg" />
                </a>
            </li>
            <li>
                <a href="#" class="breadcrumbs__link breadcrumbs__link--current">
                    Thanh toán
                </a>
            </li>
        </ul>
    </div>

    <div class="checkout-container">
    <div class="row gy-xl-3">

        <!-- ================= CỘT TRÁI ================= -->
        <div class="col-8 col-xl-8 col-lg-12">

            <!-- Thông tin giao hàng -->
            <div class="cart-info">
                <div class="cart-info__top">
                    <h2 class="cart-info__heading cart-info__heading--lv2">
                        1. Thời gian giao dự kiến: <?= $delivery_from ?> — <?= $delivery_to ?>
                    </h2>
                    <a class="cart-info__edit-btn" href="./shipping.php">
                        <img class="icon" src="./assets/icons/edit.svg" />
                        Chỉnh sửa
                    </a>
                </div>

                <article class="payment-item">
                    <div class="payment-item__info">
                        <h3 class="payment-item__title">
                            <?= htmlspecialchars($address['receiver_name'] ?? '') ?>
                        </h3>
                        <p class="payment-item__desc">
                            <?= htmlspecialchars(($address['address'] ?? '') . ', ' . ($address['city'] ?? '')) ?>
                            <br>
                            <?= htmlspecialchars($address['phone'] ?? '') ?>
                        </p>
                    </div>
                </article>
            </div>

            <!-- Chi tiết đơn hàng -->
            <div class="cart-info">
                <h2 class="cart-info__heading cart-info__heading--lv2">
                    2. Chi tiết đơn hàng
                </h2>

                <div style="color:#9e9da8;font-size:14px;margin-bottom:6px;">
                    Mã đơn hàng: <?= htmlspecialchars($order_code) ?>
                </div>

                <?php while($it = $items->fetch_assoc()): ?>
                    <div style="display:flex;align-items:center;margin-bottom:10px">
                        <img src="<?= htmlspecialchars($it['image_url'] ?? './assets/img/product/no-image.png') ?>"
                             style="width:60px;height:60px;object-fit:cover;margin-right:10px">

                        <div style="font-size:14px">
                            <?= htmlspecialchars($it['product_name'] ?? '') ?>
                            (x<?= (int)$it['quantity'] ?>)
                            <br>
                            <span style="color:#9e9da8">
                                <?= number_format($it['price_at_purchase'],0,',','.') ?>đ
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Vận chuyển -->
            <div class="cart-info">
                <h2 class="cart-info__heading cart-info__heading--lv2">
                    3. Hình thức vận chuyển
                </h2>
                <div class="cart-info__separate"></div>

                <article class="payment-item">
                    <div class="payment-item__info">
                        <h3 class="payment-item__title">Giao hàng tiêu chuẩn</h3>
                        <p class="payment-item__desc payment-item__desc--low">
                            Thời gian: 2-3 ngày làm việc
                        </p>
                        <p style="color:#ff9800;font-size:14px;margin-top:5px">
                            Phí vận chuyển sẽ được thu khi giao hàng.
                        </p>
                    </div>
                </article>
            </div>

        </div>


        <!-- ================= CỘT PHẢI ================= -->
        <div class="col-4 col-xl-4 col-lg-12">

            <div class="cart-info">

                <h2 class="cart-info__heading cart-info__heading--lv2">
                    4. Phương thức thanh toán
                </h2>

                <form action="confirm-payment.php" method="POST">

                    <!-- Ẩn order_id -->
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">

                    <!-- Chọn phương thức -->
                    <div style="margin-bottom:15px">

                        <label style="display:block;margin-bottom:10px">
                            <input type="radio" name="method" value="cod" checked onclick="selectPayment('cod')">
                            Thanh toán khi nhận hàng (COD)
                        </label>

                        <label style="display:block">
                            <input type="radio" name="method" value="qr" onclick="selectPayment('qr')">
                            Thanh toán chuyển khoản (QR)
                        </label>

                    </div>

                    <div class="cart-info__separate"></div>

                    <!-- ===== COD ===== -->
                    <div id="cod-section">

                        <div class="cart-info__row">
                            <span>Tổng tiền hàng</span>
                            <span><?= number_format($total_amount,0,',','.') ?>đ</span>
                        </div>

                        <div class="cart-info__row">
                            <span>Phí vận chuyển</span>
                            <span>Thu khi giao hàng</span>
                        </div>

                        <div class="cart-info__separate"></div>

                        <div class="cart-info__row" style="font-weight:700;font-size:16px">
                            <span>Tổng thanh toán</span>
                            <span><?= number_format($grand_total,0,',','.') ?>đ</span>
                        </div>

                        <button 
                            type="submit"
                            style="margin-top:15px;width:100%;padding:10px;background:#4caf50;color:white;border:none;border-radius:6px;cursor:pointer">
                            Xác nhận đặt hàng
                        </button>

                    </div>

                    <!-- ===== QR ===== -->
                    <div id="qr-section" style="display:none">

                        <p style="color:#666;margin-bottom:10px">
                            Quét mã QR bằng ứng dụng ngân hàng để chuyển khoản.
                        </p>

                        <div style="text-align:center;margin:20px 0">
                            <img src="<?= $qr_url ?>" width="250" alt="QR thanh toán">
                        </div>

                        <div class="cart-info__row">
                            <span>Số tiền cần chuyển</span>
                            <span style="font-weight:700">
                                <?= number_format($grand_total,0,',','.') ?>đ
                            </span>
                        </div>

                        <button 
                            type="submit"
                            style="margin-top:15px;width:100%;padding:10px;background:#2196f3;color:white;border:none;border-radius:6px;cursor:pointer">
                            Tôi đã thanh toán
                        </button>

                        <p style="font-size:13px;color:#ff9800;margin-top:10px">
                            Admin sẽ xác nhận thanh toán trước khi xử lý đơn hàng.
                        </p>

                    </div>

                </form>


        </div>

    </div>
    </div>

</div>
</main>

<!-- FOOTER -->
<footer id="footer" class="footer"></footer>
<script>
    load("#footer", "./templates/footer.php");
</script>

<script>
function selectPayment(method) {
    if (method === 'cod') {
        document.getElementById("cod-section").style.display = "block";
        document.getElementById("qr-section").style.display = "none";
    } else {
        document.getElementById("cod-section").style.display = "none";
        document.getElementById("qr-section").style.display = "block";
    }
}

function confirmCOD() {
    window.location.href = "payment-success.php?code=<?= $order_code ?>&method=cod";
}

function confirmQR() {
    window.location.href = "payment-success.php?code=<?= $order_code ?>&method=qr";
}
</script>


</body>
</html>

