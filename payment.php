<?php
session_start();
require_once 'db_connect.php';

if (!isset($_GET['code'])) {
    header("Location: checkout.php");
    exit;
}

$order_code = $_GET['code'];

/* lấy đơn */
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_code=? LIMIT 1");
$stmt->bind_param("s", $order_code);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Không tìm thấy đơn hàng");
}
/* ===== ngày giao dự kiến ===== */
$today = new DateTime();

/* ví dụ: giao 3–7 ngày */
$delivery_from = (clone $today)->modify('+3 days')->format('d/m/Y');
$delivery_to   = (clone $today)->modify('+7 days')->format('d/m/Y');

/* lấy item của đơn */
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
$total_qty = $sum['total_qty'] ?? 0;
$total_amount = $sum['total_amount'] ?? 0;

/* lấy đơn */
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_code=? LIMIT 1");
$stmt->bind_param("s", $order_code);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

/* lấy địa chỉ */
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
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Payment | Grocery Mart</title>

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
                            <a href="./checkout.php" class="breadcrumbs__link">
                                Checkout
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="./shipping.php" class="breadcrumbs__link">
                                Shipping
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link breadcrumbs__link--current">Payment method</a>
                        </li>
                    </ul>
                </div>

                <!-- Checkout content -->
                <div class="checkout-container">
                    <div class="row gy-xl-3">
                        <div class="col-8 col-xl-8 col-lg-12">
                            <div class="cart-info">
                                <div class="cart-info__top">
                                    <h2 class="cart-info__heading cart-info__heading--lv2">
                                        1. Thời gian giao dự kiến giữa <?= $delivery_from ?> — <?= $delivery_to ?>
                                    </h2>
                                    <a class="cart-info__edit-btn" href="./shipping.php">
                                        <img class="icon" src="./assets/icons/edit.svg" alt="" />
                                        Edit
                                    </a>
                                </div>

                                <!-- Payment item 1 -->
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

                                <!-- Payment item 2 -->
                                <article class="payment-item">
                                <div class="payment-item__info">
                                    <h3 class="payment-item__title">Chi tiết sản phẩm</h3>

                                    <div style="color:#9e9da8;font-size:14px;margin-bottom:6px;">
                                        Mã đơn hàng: <?= htmlspecialchars($order_code) ?>
                                    </div>

                                        <?php while($it = $items->fetch_assoc()): ?>
                                        <div style="display:flex;align-items:center;margin-bottom:10px">
                                            <img src="<?= htmlspecialchars($it['image_url'] ?? './assets/img/product/no-image.png') ?>"
                                            style="width:50px;height:50px;object-fit:cover;margin-right:10px">

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
                                </article>
                            </div>
                            <div class="cart-info">
                                <h2 class="cart-info__heading cart-info__heading--lv2">2. Shipping method</h2>
                                <div class="cart-info__separate"></div>
                                <h3 class="cart-info__sub-heading">Availeble Shipping method</h3>

                                <!-- Payment item 3 -->
                                <label>
                                    <article class="payment-item payment-item--pointer">
                                        <img
                                            src="./assets/img/payment/delivery-1.png"
                                            alt=""
                                            class="payment-item__thumb"
                                        />
                                        <div class="payment-item__content">
                                            <div class="payment-item__info">
                                                <h3 class="payment-item__title">Fedex Delivery</h3>
                                                <p class="payment-item__desc payment-item__desc--low">
                                                    Delivery: 2-3 days work
                                                </p>
                                            </div>

                                            <span class="cart-info__checkbox payment-item__checkbox">
                                                <input
                                                    type="radio"
                                                    name="delivery-method"
                                                    checked
                                                    class="cart-info__checkbox-input payment-item__checkbox-input"
                                                />
                                                <span class="payment-item__cost">Free</span>
                                            </span>
                                        </div>
                                    </article>
                                </label>

                                <!-- Payment item 4 -->
                                <label>
                                    <article class="payment-item payment-item--pointer">
                                        <img
                                            src="./assets/img/payment/delivery-2.png"
                                            alt=""
                                            class="payment-item__thumb"
                                        />
                                        <div class="payment-item__content">
                                            <div class="payment-item__info">
                                                <h3 class="payment-item__title">DHL Delivery</h3>
                                                <p class="payment-item__desc payment-item__desc--low">
                                                    Delivery: 2-3 days work
                                                </p>
                                            </div>

                                            <span class="cart-info__checkbox payment-item__checkbox">
                                                <input
                                                    type="radio"
                                                    name="delivery-method"
                                                    class="cart-info__checkbox-input payment-item__checkbox-input"
                                                />
                                                <span class="payment-item__cost">$12.00</span>
                                            </span>
                                        </div>
                                    </article>
                                </label>
                            </div>
                        </div>
                        <div class="col-4 col-xl-4 col-lg-12">
                            <div class="cart-info">
                                <h2 class="cart-info__heading cart-info__heading--lv2">Payment Details</h2>
                                <p class="cart-info__desc">
                                    Complete your purchase item by providing your payment details order.
                                </p>
                                <form action="" class="form cart-info__form">
                                    <div class="form__group">
                                        <label for="email" class="form__label form__label--medium">Email Address</label>
                                        <div class="form__text-input">
                                            <input
                                                type="email"
                                                name="email"
                                                id="email"
                                                placeholder="Email"
                                                class="form__input"
                                                required
                                            />
                                            <img
                                                src="./assets/icons/form-error.svg"
                                                alt=""
                                                class="form__input-icon-error"
                                            />
                                        </div>
                                        <p class="form__error">Password must be at least 6 characters</p>
                                    </div>
                                    <div class="form__group">
                                        <label for="card-holder" class="form__label form__label--medium">
                                            Card Holder
                                        </label>
                                        <div class="form__text-input">
                                            <input
                                                type="text"
                                                name="card-holder"
                                                id="card-holder"
                                                placeholder="Card Holder"
                                                class="form__input"
                                                required
                                            />
                                            <img
                                                src="./assets/icons/form-error.svg"
                                                alt=""
                                                class="form__input-icon-error"
                                            />
                                        </div>
                                        <p class="form__error">Password must be at least 6 characters</p>
                                    </div>
                                    <div class="form__group">
                                        <label for="card-details" class="form__label form__label--medium">
                                            Card Details
                                        </label>
                                        <div class="form__text-input">
                                            <input
                                                type="text"
                                                name="card-details"
                                                id="card-details"
                                                placeholder="Card Details"
                                                class="form__input"
                                                required
                                            />
                                            <img
                                                src="./assets/icons/form-error.svg"
                                                alt=""
                                                class="form__input-icon-error"
                                            />
                                        </div>
                                        <p class="form__error">Password must be at least 6 characters</p>
                                    </div>
                                    <div class="form__row cart-info__form-row">
                                        <div class="form__group">
                                            <div class="form__text-input">
                                                <input
                                                    type="text"
                                                    name="card-expire"
                                                    id="card-expire"
                                                    placeholder="MM/YY"
                                                    class="form__input"
                                                    required
                                                />
                                                <img
                                                    src="./assets/icons/form-error.svg"
                                                    alt=""
                                                    class="form__input-icon-error"
                                                />
                                            </div>
                                            <p class="form__error">Password must be at least 6 characters</p>
                                        </div>
                                        <div class="form__group">
                                            <div class="form__text-input">
                                                <input
                                                    type="text"
                                                    name="card-cvc"
                                                    id="card-cvc"
                                                    placeholder="CVC"
                                                    class="form__input"
                                                    required
                                                />
                                                <img
                                                    src="./assets/icons/form-error.svg"
                                                    alt=""
                                                    class="form__input-icon-error"
                                                />
                                            </div>
                                            <p class="form__error">Password must be at least 6 characters</p>
                                        </div>
                                    </div>
                                </form>
                                <div class="cart-info__row">
                                    <span>Subtotal <span class="cart-info__sub-label">(items)</span></span>
                                    <span><?= $total_qty ?></span>
                                </div>
                                <div class="cart-info__row">
                                    <span>Price <span class="cart-info__sub-label">(Total)</span></span>
                                    <span><?= number_format($total_amount,0,',','.') ?>đ</span>                                </div>
                                <div class="cart-info__row">
                                    <span>Shipping</span>
                                    <span>$10.00</span>
                                </div>
                                <div class="cart-info__separate"></div>
                                <div class="cart-info__row">
                                    <span>Estimated Total</span>
                                    <span>$201.65</span>
                                </div>
                                <a href="#!" class="cart-info__next-btn btn btn--primary btn--rounded">Pay $201.65</a>
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

        <!-- Modal: address new shipping address -->
        <div id="add-new-address" class="modal hide" style="--content-width: 650px">
            <div class="modal__content">
                <form action="" class="form">
                    <h2 class="modal__heading">Add new shipping address</h2>
                    <div class="modal__body">
                        <div class="form__row">
                            <div class="form__group">
                                <label for="name" class="form__label form__label--small">Name</label>
                                <div class="form__text-input form__text-input--small">
                                    <input
                                        type="text"
                                        name="name"
                                        id="name"
                                        placeholder="Name"
                                        class="form__input"
                                        required
                                        minlength="2"
                                    />
                                    <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                                </div>
                                <p class="form__error">Name must be at least 2 characters</p>
                            </div>
                            <div class="form__group">
                                <label for="phone" class="form__label form__label--small">Phone</label>
                                <div class="form__text-input form__text-input--small">
                                    <input
                                        type="tel"
                                        name="phone"
                                        id="phone"
                                        placeholder="Phone"
                                        class="form__input"
                                        required
                                        minlength="10"
                                    />
                                    <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                                </div>
                                <p class="form__error">Phone must be at least 10 characters</p>
                            </div>
                        </div>
                        <div class="form__group">
                            <label for="address" class="form__label form__label--small">Address</label>
                            <div class="form__text-area">
                                <textarea
                                    name="address"
                                    id="address"
                                    placeholder="Address (Area and street)"
                                    class="form__text-area-input"
                                    required
                                ></textarea>
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Address not empty</p>
                        </div>
                        <div class="form__group">
                            <label for="city" class="form__label form__label--small">City/District/Town</label>
                            <div class="form__text-input form__text-input--small">
                                <input
                                    type="text"
                                    name=""
                                    placeholder="City/District/Town"
                                    id="city"
                                    class="form__input js-toggle"
                                    toggle-target="#city-dialog"
                                />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />

                                <!-- Select dialog -->
                                <div id="city-dialog" class="form__select-dialog hide">
                                    <h2 class="form__dialog-heading d-none d-sm-block">City/District/Town</h2>
                                    <button
                                        class="form__close-dialog d-none d-sm-block js-toggle"
                                        toggle-target="#city-dialog"
                                    >
                                        &times
                                    </button>
                                    <div class="form__search">
                                        <input type="text" placeholder="Search" class="form__search-input" />
                                        <img src="./assets/icons/search.svg" alt="" class="form__search-icon icon" />
                                    </div>
                                    <ul class="form__options-list">
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option form__option--current">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                    </ul>
                                </div>
                            </div>
                            <p class="form__error">Phone must be at least 11 characters</p>
                        </div>
                        <div class="form__group form__group--inline">
                            <label class="form__checkbox">
                                <input type="checkbox" name="" id="" class="form__checkbox-input d-none" />
                                <span class="form__checkbox-label">Set as default address</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal__bottom">
                        <button class="btn btn--small btn--text modal__btn js-toggle" toggle-target="#add-new-address">
                            Cancel
                        </button>
                        <button class="btn btn--small btn--primary modal__btn btn--no-margin">Create</button>
                    </div>
                </form>
            </div>
            <div class="modal__overlay"></div>
        </div>
    </body>
</html>
