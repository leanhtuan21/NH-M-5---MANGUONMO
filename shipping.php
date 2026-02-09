<?php
require_once 'db_connect.php';

/* ===== SESSION ===== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== KIỂM TRA ĐĂNG NHẬP ===== */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
if (!isset($_SESSION['checkout']) || empty($_SESSION['checkout']['items'])) {
    header("Location: checkout.php");
    exit;
}
/* ===== LẤY SẢN PHẨM ĐANG CHECKOUT ===== */
$checkoutItems = $_SESSION['checkout']['items'];
$total_qty     = $_SESSION['checkout']['total_qty'];
$total_price   = $_SESSION['checkout']['total_price'];
$total_price = (int)$total_price;

if ($total_price >= 500000) {
    $shipping_fee = 0;
    $shipping_text = 'Miễn phí';
} else {
    $shipping_fee = 15000;
    $shipping_text = number_format($shipping_fee, 0, ',', '.') . 'đ';
}

$grand_total = $total_price + $shipping_fee;
/* ===== TÍNH NGÀY GIAO DỰ KIẾN ===== */
$today = new DateTime();
$min_days = 3;
$max_days = 7;
$delivery_from = (clone $today)->modify("+$min_days days")->format('Y-m-d');
$delivery_to   = (clone $today)->modify("+$max_days days")->format('Y-m-d');
$weight = $_SESSION['checkout']['items'];
/* ===== LẤY DANH SÁCH ĐỊA CHỈ ===== */
$sql = "
    SELECT * FROM shipping_addresses
    WHERE user_id = $user_id
    ORDER BY is_default DESC, id DESC
";

$result = $conn->query($sql);

$addresses = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
}

/* ===== LẤY ĐỊA CHỈ ĐỂ EDIT (NẾU CÓ) ===== */
$editAddress = null;

if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];

    $stmt = $conn->prepare("
        SELECT * FROM shipping_addresses
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();

    $res = $stmt->get_result();
    $editAddress = $res->fetch_assoc();
}

/* ===== XỬ LÝ ADD / UPDATE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 /* =========================
       1. ĐẶT HÀNG
    ==========================*/
    if (isset($_POST['place_order'])) {

        $order_code = 'ORD' . date('YmdHis') . rand(100,999);
        $shipping_method = 'COD';
        $address_id = (int)$_POST['address_id'];   // id địa chỉ user chọn
        $stmt = $conn->prepare("
        INSERT INTO orders
        (order_code,user_id,total_amount,shipping_method,shipping_fee,status,payment_status,address_id)
        VALUES (?,?,?,?,?,'pending','unpaid',?)
        ");

        $stmt->bind_param(
            "siddsi",
            $order_code,
            $user_id,
            $grand_total,
            $shipping_method,
            $shipping_fee,
            $address_id
        );
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        /* insert items */
        $item_stmt = $conn->prepare("
            INSERT INTO order_items
            (order_id, product_id, quantity, price_at_purchase)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($_SESSION['checkout']['items'] as $item) {
            $item_stmt->bind_param(
                "iiid",
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            );
            $item_stmt->execute();
        }

        $item_stmt->close();
        unset($_SESSION['checkout']);

        header("Location: payment.php?code=".$order_code);
        exit;
    }

    // ❌ $name = trim($_POST['name']);
    $receiver_name = trim($_POST['receiver_name']);
    $phone_raw   = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city    = trim($_POST['city']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    // CHECK: bắt đầu bằng 0 & đủ 10 số
    if (!preg_match('/^0\d{9}$/', $phone_raw)) {
        echo "<script>alert('Số điện thoại không hợp lệ (phải bắt đầu bằng 0 và đủ 10 số)')</script>";
        exit; 
    }

    $phone = $phone_raw; // dùng biến này để lưu DB
    // Nếu set default → bỏ default cũ
    if ($is_default) {
        $conn->query("UPDATE shipping_addresses SET is_default = 0 WHERE user_id = $user_id");
    }

    if (!empty($_POST['address_id'])) {
        /* ===== UPDATE ===== */
        $address_id = (int) $_POST['address_id'];

        $stmt = $conn->prepare("
            UPDATE shipping_addresses
            SET receiver_name=?, phone=?, address=?, city=?, is_default=?
            WHERE id=? AND user_id=?
        ");
        $stmt->bind_param(
            "ssssiii",
            $receiver_name,
            $phone,
            $address,
            $city,
            $is_default,
            $address_id,
            $user_id
        );
        $stmt->execute();

    } else {
        /* ===== ADD ===== */
        $stmt = $conn->prepare("
            INSERT INTO shipping_addresses (user_id, receiver_name, phone, address, city, is_default)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "issssi",
            $user_id,
            $receiver_name,
            $phone,
            $address,
            $city,
            $is_default
        );
        $stmt->execute();
    }
    header("Location: shipping.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Shipping | Grocery Mart</title>

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
    <style>
        /* Địa chỉ mặc định */
    .address-card--default {
        border: 2px solid #2e7dcc;
        background: #f6fffa;
        position: relative;
    }

    /* Nhãn DEFAULT */
    .address-card--default::before {
        content: "Mặc định";
        position: absolute;
        top: -10px;
        left: 16px;
        background: #2e7dcc;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
    }
    /* Địa chỉ nhận hàng */
    /* Box địa chỉ đang chọn */
    .address-selected-box {
        background: #f0fff4;
        border: 2px solid #2ecc71;
        border-radius: 8px;
        padding: 12px 16px;
        min-width: 260px;
    }

    /* Tên người nhận */
    .address-selected-box strong {
        color: #1e8449;
        font-size: 15px;
    }

    /* Số điện thoại */
    .address-selected-box .phone {
        font-weight: 500;
    }

    /* Địa chỉ chi tiết */
    .address-selected-box span {
        display: block;
        margin-top: 4px;
        font-size: 14px;
        color: #333;
    }
    </style>
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
                            <a href="./index-logined.php" class="breadcrumbs__link">
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
                            <a href="#!" class="breadcrumbs__link breadcrumbs__link--current">Shipping</a>
                        </li>
                    </ul>
                </div>

                <!-- Checkout content -->
                <div class="checkout-container">
                    <div class="row gy-xl-3">
                        <div class="col-8 col-xl-12">
                            <div class="cart-info">
                                <p>
                                    Giao hàng dự kiến:
                                    <?= date('d/m/Y', strtotime($delivery_from)) ?>
                                    —
                                    <?= date('d/m/Y', strtotime($delivery_to)) ?>
                                    </p>
                                <div class="cart-info__separate"></div>

                                <!-- Checkout address -->
                                <div class="user-address">
                                    <div class="user-address__top">
                                        <div>
                                            <h2 class="user-address__title">Địa chỉ nhận hàng</h2>
                                            <p class="user-address__desc">Chúng tôi nên giao đơn hàng của bạn đến đâu?</p>
                                        </div>
                                        <div class="user-address__selected" id="selected-address">
                                            <!-- Địa chỉ mặc định / đang chọn sẽ hiện ở đây -->
                                        </div>
                                        <button
                                            class="user-address__btn btn btn--primary btn--rounded btn--small js-toggle"
                                            toggle-target="#add-new-address"
                                        >
                                            <img src="./assets/icons/plus.svg" alt="" />
                                            Thêm địa chỉ mới
                                        </button>
                                    </div>
                                    <div class="user-address__list">
                                            <?php if (empty($addresses)): ?>
                                                <p class="user-address__message">
                                                    Chưa có địa chỉ nhận hàng.
                                                    <a class="user-address__link js-toggle"
                                                    href="#!"
                                                    toggle-target="#add-new-address">
                                                        Vui lòng nhập địa chỉ nhận hàng
                                                    </a>
                                                </p>
                                            <?php else: ?>
                                                <?php foreach ($addresses as $addr): ?>
                                                    <article class="address-card <?= $addr['is_default'] ? 'address-card--default' : '' ?>">            <div class="address-card__left">
                                                            <div class="address-card__choose">
                                                                <label class="cart-info__checkbox">
                                                                    <input type="radio"
                                                                        name="shipping-address"
                                                                        class="cart-info__checkbox-input js-select-address"
                                                                        value="<?= $addr['id'] ?>"
                                                                        data-name="<?= htmlspecialchars($addr['receiver_name']) ?>"
                                                                        data-phone="<?= htmlspecialchars($addr['phone']) ?>"
                                                                        data-address="<?= htmlspecialchars($addr['address'] . ', ' . $addr['city']) ?>"
                                                                        <?= $addr['is_default'] ? 'checked' : '' ?>>
                                                                    </label>
                                                                </div>

                                                                <div class="address-card__info">
                                                                    <h3 class="address-card__title">
                                                                        <?= htmlspecialchars($addr['receiver_name']) ?>
                                                                    </h3>

                                                                    <p class="address-card__desc">
                                                                        <?= htmlspecialchars($addr['address']) ?>,
                                                                        <?= htmlspecialchars($addr['city']) ?><br>
                                                                        📞 <?= htmlspecialchars($addr['phone']) ?>
                                                                    </p>

                                                                    <ul class="address-card__list">
                                                                        <li class="address-card__list-item">Shipping</li>
                                                                        <?php if ($addr['is_default']): ?>
                                                                <li class="address-card__list-item">Mặc định</li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <div class="address-card__right">
                                                    <div class="address-card__ctrl">
                                                        <a href="shipping.php?edit_id=<?= $addr['id'] ?>"
                                                        class="cart-info__edit-btn">
                                                            <img class="icon" src="./assets/icons/edit.svg" alt="">
                                                            Sửa
                                                        </a>
                                                    </div>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                </div>

                                <div class="cart-info__separate"></div>

                                <h2 class="cart-info__sub-heading">Sản phẩm đã mua</h2>
                                <div class="cart-info__list">
                                <?php foreach ($checkoutItems as $item): ?>
                                    <?php
                                        $weight = (int)($item['weight_gram'] ?? 0);

                                        if ($weight >= 1000) {
                                            $weight_text = ($weight / 1000) . ' kg';
                                        } else {
                                            $weight_text = $weight . ' g';
                                        }
                                    ?>
                                    <article class="cart-item">
                                        <img 
                                        src="<?= $item['image_url'] ?? './assets/img/product/item-1.png' ?>" 
                                        class="cart-item__thumb"
                                        style="width:80px; height:80px; object-fit:cover;"
                                    >
                                        <div class="cart-item__content">
                                            <div class="cart-item__content-left">
                                                <h3 class="cart-item__title">
                                                    <?= htmlspecialchars($item['product_name']) ?>
                                                </h3>
                                                <p class="cart-item__price-wrap">
                                                    <?= number_format($item['price'], 0, ',', '.') ?>đ |
                                                    <span><?= $weight_text ?></span>
                                                </p>
                                                <div>Số lượng: <?= (int)$item['quantity'] ?></div>
                                            </div>

                                            <div class="cart-item__content-right">
                                                <p class="cart-item__total-price">
                                                    <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                                                </p>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
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
                                                    Tiếp tục mua sắm
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-4 col-xxl-5">
                                            <div class="cart-info__row">
                                                <span>Tổng tiền hàng: <span class="cart-info__sub-label"></span></span>
                                                <span><?= number_format($total_price, 0, ',', '.') ?>đ</span>
                                            </div>

                                            <div class="cart-info__row">
                                                <span>Tiền ship: </span>
                                                <span><?= $shipping_text ?></span>
                                            </div>
                                            <div class="cart-info__row">
                                                <small style="color:#666">
                                                    Tiền ship sẽ trả khi nhận đơn hàng
                                                </small>
                                            </div>
                                            <div class="cart-info__separate"></div>
                                            <div class="cart-info__row cart-info__row--bold">
                                                <span>Thành tiền</span>
                                                <span><?= number_format($grand_total, 0, ',', '.') ?>đ</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-xl-12">
                            <div class="cart-info">
                                <div class="cart-info__row">
                                    <span>Tổng tiền hàng: </span>
                                    <span><?= number_format($total_price, 0, ',', '.') ?>đ</span>
                                </div>
                                <div class="cart-info__row">
                                    <span>Tổng sản phẩm</span>
                                    <span><?= $total_qty ?></span>
                                </div>

                                <div class="cart-info__row">
                                    <span>Tiền ship: </span>
                                    <span><?= $shipping_text ?></span>
                                </div>
                                <div class="cart-info__row">
                                    <small style="color:#666">
                                        Tiền ship sẽ trả khi nhận đơn hàng
                                    </small>
                                </div>
                                <div class="cart-info__separate"></div>

                                <div class="cart-info__row cart-info__row--bold">
                                    <span>Thành tiền: </span>
                                    <span><?= number_format($grand_total, 0, ',', '.') ?>đ</span>
                                </div>
                                <form method="post" id="order-form">
                                    <input type="hidden" name="address_id" id="address_id_input">
                                    <button name="place_order"
                                            class="cart-info__next-btn btn btn--primary btn--rounded">
                                        Đặt hàng
                                    </button>
                                </form>
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
        <div id="add-new-address" class="modal <?= isset($editAddress) ? '' : 'hide' ?>">
            <div class="modal__content">
                <form action="" class="form" method="post">
                    <h2 class="modal__heading">
                        <?= $editAddress ? 'Cập nhật địa chỉ' : 'Thêm địa chỉ mới' ?>
                    </h2>
                    <div class="modal__body">
                        <div class="form__row">
                            <div class="form__group">
                                <label for="name" class="form__label form__label--small">Tên</label>
                                <div class="form__text-input form__text-input--small">
                                    <input type="text"
                                    name="receiver_name"
                                    value="<?= htmlspecialchars($editAddress['receiver_name'] ?? '') ?>"
                                    class="form__input"
                                    required>
                                    <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                                </div>
                                <p class="form__error">Name must be at least 2 characters</p>
                            </div>
                            <div class="form__group">
                                <label for="phone" class="form__label form__label--small">Phone</label>
                                <div class="form__text-input form__text-input--small">
                                    <input type="tel"
                                        name="phone"
                                        required
                                        maxlength="10"
                                        value="<?= htmlspecialchars($editAddress['phone'] ?? '') ?>"
                                        class="form__input"
                                        >
                                    <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                                </div>
                                <p class="form__error">Phone must be at least 10 characters</p>
                            </div>
                        </div>
                        <div class="form__group">
                            <label for="address" class="form__label form__label--small">Address</label>
                            <div class="form__text-area">
                                <textarea name="address" class="form__text-area-input" required><?= htmlspecialchars($editAddress['address'] ?? '') ?></textarea>
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Address not empty</p>
                        </div>
                        <div class="form__group">
                            <label for="city" class="form__label form__label--small">City/District/Town</label>
                            <div class="form__text-input form__text-input--small">
                                

                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />

                                <!-- Select dialog -->
                                <select name="city" class="form__input" required>
                                    <option value="">-- Chọn tỉnh / thành phố --</option>
                                    <?php
                                    $cities = ['TP.Hà Nội','TP.Hồ Chí Minh', 'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu', 'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước', 'Bình Thuận', 'Cà Mau', 'Cần Thơ', 'Cao Bằng', 'Đà Nẵng', 'Đắk Lắk', 'Đắk Nông', 'Điện Biên', 'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Tĩnh', 'Hải Dương', 
                                    'Hải Phòng', 'Hậu Giang', 'Hòa Bình', 'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu', 'Lạng Sơn', 'Lào Cai', 'Lâm Đồng', 'Long An', 'Nam Định', 'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên', 'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị', 'Sóc Trăng', 'Sơn La',
                                    'Tây Ninh', 'Thái Bình', 'Thái Nguyên', 'Thanh Hóa', 'Thừa Thiên Huế', 'Tiền Giang', 'Trà Vinh', 'Tuyên Quang', 'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái'];
                                    $currentCity = $editAddress['city'] ?? '';

                                    foreach ($cities as $city) {
                                        $selected = ($city === $currentCity) ? 'selected' : '';
                                        echo "<option value=\"$city\" $selected>$city</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form__group form__group--inline">
                            <label class="form__checkbox">
                             <input type="checkbox" name="is_default" <?= !empty($editAddress['is_default']) ? 'checked' : '' ?>>
                                <span class="form__checkbox-label">Đặt làm địa chỉ mặc định</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal__bottom">
                       <?php if (isset($editAddress)): ?>
                            <a href="shipping.php"
                            class="btn btn--small btn--text modal__btn">
                                Cancel
                            </a>
                        <?php else: ?>
                            <button class="btn btn--small btn--text modal__btn js-toggle"
                                    toggle-target="#add-new-address">
                                Cancel
                            </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn--small btn--primary modal__btn btn--no-margin">
                            <?= isset($editAddress) ? 'Update' : 'Create' ?>
                        </button>
                        <input type="hidden" name="address_id"
                            value="<?= $editAddress['id'] ?? '' ?>">
                    </div>
                </form>
            </div>
            <div class="modal__overlay js-toggle"
     toggle-target="#add-new-address"></div>        </div>
    </body>
    <?php if (isset($editAddress)): ?>
<script>
window.addEventListener("template-loaded", function () {
    const modal = document.querySelector("#add-new-address");
    if (modal && modal.classList.contains("modal")) {
        modal.classList.remove("hide");
        modal.classList.add("show");
        document.body.classList.add("no-scroll");
    }
});
</script>
<?php endif; ?>
<script>
document.querySelectorAll('.js-select-address').forEach(radio => {
    radio.addEventListener('change', function () {
        const addressId = this.value;

        fetch('set_default_address.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'address_id=' + addressId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload(); // reload để địa chỉ nhảy lên trên
            } else {
                alert('Không thể chọn địa chỉ');
            }
        });
    });
});
</script>
<!-- Địa chỉ nhận hàng -->
 <script>
const selectedBox = document.getElementById('selected-address');

function renderSelected(radio) {
    const name = radio.dataset.name;
    const phone = radio.dataset.phone;
    const address = radio.dataset.address;

    selectedBox.innerHTML = `
        <div class="address-selected-box">
            <strong>${name}</strong> | ${phone}<br>
            <span>${address}</span>
        </div>
    `;
}

// Click chọn địa chỉ khác
document.querySelectorAll('.js-select-address').forEach(radio => {
    radio.addEventListener('change', function () {
        renderSelected(this);
    });
});

// 👉 TỰ ĐỘNG HIỂN ĐỊA CHỈ MẶC ĐỊNH
const defaultRadio = document.querySelector('.js-select-address:checked');
if (defaultRadio) {
    renderSelected(defaultRadio);
}
const addressInput = document.getElementById('address_id_input');

document.querySelectorAll('.js-select-address').forEach(radio => {
    radio.addEventListener('change', function () {
        addressInput.value = this.value;
    });
});

// auto set mặc định
const checked = document.querySelector('.js-select-address:checked');
if (checked) addressInput.value = checked.value;

</script>

</html>
