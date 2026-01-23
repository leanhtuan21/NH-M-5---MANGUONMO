


<?php
session_start();
include 'db_connect.php'; // Đảm bảo bạn đã có file kết nối DB

if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php'); // Chuyển hướng nếu chưa đăng nhập
    exit();
}

$user_id = $_SESSION['user_id'];

// Nếu có cart trong session, lưu vào DB
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    // Kiểm tra xem user có order 'cart' chưa
    $order_stmt = $conn->prepare("SELECT id FROM orders WHERE user_id = ? AND status = 'cart'");
    $order_stmt->bind_param("i", $user_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    if ($order_result->num_rows == 0) {
        // Tạo order mới
        $insert_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, 0, 'cart')");
        $insert_order->bind_param("i", $user_id);
        $insert_order->execute();
        $order_id = $conn->insert_id;
        $insert_order->close();
    } else {
        $order = $order_result->fetch_assoc();
        $order_id = $order['id'];
    }
    $order_stmt->close();

    // Lưu từng item từ session vào DB
    foreach ($_SESSION['cart'] as $product_id => $item) {
        // Kiểm tra sản phẩm đã có trong order_items chưa
        $item_stmt = $conn->prepare("SELECT id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
        $item_stmt->bind_param("ii", $order_id, $product_id);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        if ($item_result->num_rows > 0) {
            // Cập nhật quantity
            $existing_item = $item_result->fetch_assoc();
            $new_quantity = $existing_item['quantity'] + $item['quantity'];
            $update_item = $conn->prepare("UPDATE order_items SET quantity = ? WHERE id = ?");
            $update_item->bind_param("ii", $new_quantity, $existing_item['id']);
            $update_item->execute();
            $update_item->close();
        } else {
            // Thêm mới
            $insert_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            $insert_item->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
            $insert_item->execute();
            $insert_item->close();
        }
        $item_stmt->close();
    }

    // Xóa session cart sau khi lưu
    unset($_SESSION['cart']);
}

// Truy vấn lấy sản phẩm trong giỏ hàng (order với status 'cart') và ảnh của sản phẩm đó
$sql = "SELECT oi.quantity, oi.price_at_purchase, p.name AS product_name, p.id AS product_id, pi.image_url 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        LEFT JOIN products p ON oi.product_id = p.id 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
        WHERE o.user_id = ? AND o.status = 'cart'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$subtotal = 0;
$total_quantity = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal += $row['price_at_purchase'] * $row['quantity'];
    $total_quantity += $row['quantity'];
}

$shipping_fee = 10.00; // Phí ship cố định hoặc tính toán tùy ý
$total_all = $subtotal + $shipping_fee;

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Checkout | Grocery Mart</title>

        <!-- Favicon -->
        <link rel="apple-touch-icon" sizes="76x76" href="./assets/favicon/apple-touch-icon.png" />
        <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon/favicon-32x32.png" />
        <link rel="icon" type="image/png" sizes="16x16" href="./assets/favicon/favicon-16x16.png" />
        <link rel="manifest" href="./assets/favicon/site.webmanifest" />
        <meta name="msapplication-TileColor" content="#da532c" />
        <meta name="theme-color" content="#ffffff" />

        <!-- Fonts -->
        <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />

        <!-- Styles -->
        <link rel="stylesheet" href="./assets/css/main.css" />

        <!-- Scripts -->
        <script src="./assets/js/scripts.js"></script>
    </head>
    <body>
        <!-- Header -->
        <header id="header" class="header"></header>
        <script>
            load("#header", "./templates/header-logined.php");
        </script>

        <!-- MAIN -->
        <main class="checkout-page">
            <div class="container">
                <!-- Search bar -->
                <div class="checkout-container">
                    <div class="search-bar d-none d-md-flex">
                        <input type="text" name="" id="" placeholder="Search for item" class="search-bar__input" />
                        <button class="search-bar__submit">
                            <img src="./assets/icons/search.svg" alt="" class="search-bar__icon icon" />
                        </button>
                    </div>
                </div>

                <!-- Breadcrumbs -->
                <div class="checkout-container">
                    <ul class="breadcrumbs checkout-page__breadcrumbs">
                        <li>
                            <a href="./" class="breadcrumbs__link">
                                Home
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link breadcrumbs__link--current">Checkout</a>
                        </li>
                    </ul>
                </div>

                <!-- Checkout content -->
                <div class="checkout-container">
                    <div class="row gy-xl-3">
                        <div class="col-8 col-xl-12">
                            <div class="cart-info">
                                <div class="cart-info__list">
                                    <?php if (count($cart_items) > 0): ?>
                                        <?php foreach ($cart_items as $item): ?>
                                            <!-- Cart item -->
                                            <article class="cart-item">
                                                <a href="./product-detail.php?id=<?php echo $item['product_id']; ?>">
                                                    <img
                                                        src="<?php echo $item['image_url'] ?? './assets/img/product/item-1.png'; ?>"
                                                        alt=""
                                                        class="cart-item__thumb"
                                                    />
                                                </a>
                                                <div class="cart-item__content">
                                                    <div class="cart-item__content-left">
                                                        <h3 class="cart-item__title">
                                                            <a href="./product-detail.php?id=<?php echo $item['product_id']; ?>">
                                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                                            </a>
                                                        </h3>
                                                        <p class="cart-item__price-wrap">
                                                            $<?php echo number_format($item['price_at_purchase'], 2); ?> | <span class="cart-item__status">In Stock</span>
                                                        </p>
                                                        <div class="cart-item__ctrl cart-item__ctrl--md-block">
                                                            <div class="cart-item__input">
                                                                <button class="cart-item__input-btn">
                                                                    <img class="icon" src="./assets/icons/minus.svg" alt="" />
                                                                </button>
                                                                <span><?php echo $item['quantity']; ?></span>
                                                                <button class="cart-item__input-btn">
                                                                    <img class="icon" src="./assets/icons/plus.svg" alt="" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="cart-item__content-right">
                                                        <p class="cart-item__total-price">$<?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2); ?></p>
                                                        <div class="cart-item__ctrl">
                                                            <button class="cart-item__ctrl-btn">
                                                                <img src="./assets/icons/heart-2.svg" alt="" />
                                                                Save
                                                            </button>
                                                            <button
                                                                class="cart-item__ctrl-btn js-toggle"
                                                                toggle-target="#delete-confirm"
                                                            >
                                                                <img src="./assets/icons/trash.svg" alt="" />
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>Giỏ hàng của bạn đang trống.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-info__bottom d-md-none">
                                    <div class="row">
                                        <div class="col-8 col-xxl-7">
                                            <div class="cart-info__continue">
                                                <a href="./" class="cart-info__continue-link">
                                                    <img
                                                        class="cart-info__continue-icon icon"
                                                        src="./assets/icons/arrow-down-2.svg"
                                                        alt=""
                                                    />
                                                    Continue Shopping
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-4 col-xxl-5">
                                            <div class="cart-info__row">
                                                <span>Subtotal:</span>
                                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                                            </div>
                                            <div class="cart-info__row">
                                                <span>Shipping:</span>
                                                <span>$<?php echo number_format($shipping_fee, 2); ?></span>
                                            </div>
                                            <div class="cart-info__separate"></div>
                                            <div class="cart-info__row cart-info__row--bold">
                                                <span>Total:</span>
                                                <span>$<?php echo number_format($total_all, 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-xl-12">
                            <div class="cart-info">
                                <div class="cart-info__row">
                                    <span>Subtotal <span class="cart-info__sub-label">(items)</span></span>
                                    <span><?php echo $total_quantity; ?></span>
                                </div>
                                <div class="cart-info__row">
                                    <span>Price <span class="cart-info__sub-label">(Total)</span></span>
                                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="cart-info__row">
                                    <span>Shipping</span>
                                    <span>$<?php echo number_format($shipping_fee, 2); ?></span>
                                </div>
                                <div class="cart-info__separate"></div>
                                <div class="cart-info__row">
                                    <span>Estimated Total</span>
                                    <span>$<?php echo number_format($total_all, 2); ?></span>
                                </div>
                                <a href="./shipping.php" class="cart-info__next-btn btn btn--primary btn--rounded">
                                    Continue to checkout
                                </a>
                            </div>
                            <div class="cart-info">
                                <a href="#!">
                                    <article class="gift-item">
                                        <div class="gift-item__icon-wrap">
                                            <img src="./assets/icons/gift.svg" alt="" class="gift-item__icon" />
                                        </div>
                                        <div class="gift-item__content">
                                            <h3 class="gift-item__title">Send this order as a gift.</h3>
                                            <p class="gift-item__desc">
                                                Available items will be shipped to your gift recipient.
                                            </p>
                                        </div>
                                    </article>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer id="footer" class="footer"></footer>
        <script>
            load("#footer", "./templates/footer.php");
        </script>

        <!-- Modal: confirm remove shopping cart item -->
        <div id="delete-confirm" class="modal modal--small hide">
            <div class="modal__content">
                <p class="modal__text">Do you want to remove this item from shopping cart?</p>
                <div class="modal__bottom">
                    <button class="btn btn--small btn--outline modal__btn js-toggle" toggle-target="#delete-confirm">
                        Cancel
                    </button>
                    <button
                        class="btn btn--small btn--danger btn--primary modal__btn btn--no-margin js-toggle"
                        toggle-target="#delete-confirm"
                    >
                        Delete
                    </button>
                </div>
            </div>
            <div class="modal__overlay js-toggle" toggle-target="#delete-confirm"></div>
        </div>
    </body>
</html>
