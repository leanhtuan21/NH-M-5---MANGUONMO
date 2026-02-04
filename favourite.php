<?php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
/* ===== XỬ LÝ BỎ YÊU THÍCH ===== */
if (isset($_GET['remove_wishlist'])) {
    $remove_id = (int)$_GET['remove_wishlist'];
    $uid = $_SESSION['user_id'];
if (!empty($_SESSION['wishlist'][$uid])) {
    unset($_SESSION['wishlist'][$uid][$remove_id]);
}

    header("Location: favourite.php");
    exit;
}
$uid = $_SESSION['user_id'];

/* ==== FIX WISHLIST CŨ SAI KIỂU ==== */
if (isset($_SESSION['wishlist'][$uid])) {
    foreach ($_SESSION['wishlist'][$uid] as $key => $val) {
        // nếu item KHÔNG phải mảng → xoá
        if (!is_array($val)) {
            unset($_SESSION['wishlist'][$uid][$key]);
        }
    }
}
$wishlist = $_SESSION['wishlist'][$uid] ?? [];
if (!empty($wishlist)) {
    $ids = array_keys($wishlist);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "
        SELECT id, brand
        FROM products
        WHERE id IN ($placeholders)
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $brandMap = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $brandMap[$row['id']] = $row['brand'];
    }
    foreach ($wishlist as $pid => $item) {
    if (is_array($item)) {
        $wishlist[$pid]['brand'] = $brandMap[$pid] ?? '';
    }
}
    unset($item);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Favourite List | Grocery Mart</title>

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
                            <a href="./index-logined.php" class="breadcrumbs__link">
                                Home
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link breadcrumbs__link--current">Favorite</a>
                        </li>
                    </ul>
                </div>

                <!-- Checkout content -->
                <div class="checkout-container">
                    <div class="row gy-xl-3">
                        <div class="col-12">
                            <div class="cart-info">
                                <h1 class="cart-info__heading">Danh sách sản phẩm yêu thích</h1>
                                <p class="cart-info__desc">
                                    <?= count($wishlist) ?> sản phẩm
                                </p>
                                <div class="cart-info__check-all"></div>
                                <div class="cart-info__list">
                                    <?php if (empty($wishlist)): ?>
                                        <p>Chưa có sản phẩm yêu thích</p>
                                    <?php else: ?>
                                    <?php foreach ($wishlist as $item): ?>
                                    <article class="cart-item">
                                        <a href="./product-detail.php?id=<?= $item['id'] ?>">
                                            <img
                                                src="<?= $item['image'] ?>"
                                                class="cart-item__thumb"
                                            />
                                        </a>

                                        <div class="cart-item__content">
                                            <div class="cart-item__content-left">
                                                <h3 class="cart-item__title">
                                                    <a href="./product-detail.php?id=<?= $item['id'] ?>">
                                                        <?= htmlspecialchars($item['name']) ?>
                                                    </a>
                                                </h3>
                                                <p class="cart-item__price-wrap">
                                                    <?= number_format($item['price'], 0, ',', '.') ?> ₫
                                                    <span class="cart-item__status">In Stock</span>
                                                </p>
                                                <!-- GIỮ NGUYÊN CTRL -->
                                                <div class="cart-item__ctrl-wrap">
                                                    <div class="cart-item__ctrl cart-item__ctrl--md-block">
                                                        <div class="cart-item__input">
                                                            <?= htmlspecialchars($item['brand']) ?>
                                                        </div>

                                                        <div class="cart-item__input">
                                                            <button class="cart-item__input-btn">
                                                                <img class="icon" src="./assets/icons/minus.svg" />
                                                            </button>
                                                            <span>1</span>
                                                            <button class="cart-item__input-btn">
                                                                <img class="icon" src="./assets/icons/plus.svg" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="cart-item__ctrl">
                                                        <a
                                                            href="?remove_wishlist=<?= $item['id'] ?>"
                                                            class="cart-item__ctrl-btn"
                                                            title="Bỏ khỏi yêu thích"
                                                        >
                                                            <img
                                                                src="./assets/icons/heart-red.svg"
                                                                class="heart-icon"
                                                                alt="Yêu thích"
                                                            />
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="cart-item__content-right">
                                                <p class="cart-item__total-price">
                                                    <?= number_format($item['price'], 0, ',', '.') ?> ₫
                                                </p>
                                                <!-- CHECK OUT GIỮ NGUYÊN -->
                                                <button class="cart-item__checkout-btn btn btn--primary btn--rounded">
                                                    Check Out
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-info__bottom">
                                    <div class="cart-info__row cart-info__row-md--block">
                                        <div class="cart-info__continue">
                                            <a href="./index-logined.php" class="cart-info__continue-link">
                                                <img
                                                    class="cart-info__continue-icon icon"
                                                    src="./assets/icons/arrow-down-2.svg"
                                                    alt=""
                                                />
                                                Continue Shopping
                                            </a>
                                        </div>
                                        <a
                                            href="./checkout.php"
                                            class="cart-info__checkout-all btn btn--primary btn--rounded"
                                        >
                                            All Check Out
                                        </a>
                                    </div>
                                </div>
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
        <!-- tính tiền với số lượng -->
        <script>
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".cart-item__input-btn");
        if (!btn) return;
        const cartItem = btn.closest(".cart-item");
        const qtySpan = btn.parentElement.querySelector("span");
        if (!qtySpan) return;
        let qty = parseInt(qtySpan.textContent);
        const isPlus = btn.querySelector('img[src*="plus"]');
        const isMinus = btn.querySelector('img[src*="minus"]');
        if (isPlus) qty++;
        if (isMinus && qty > 1) qty--;
        qtySpan.textContent = qty;
        // cập nhật tổng tiền bên phải
        const priceText = cartItem
            .querySelector(".cart-item__price-wrap")
            .innerText.replace(/[^\d]/g, "");
        const price = parseInt(priceText);
        const total = price * qty;
        cartItem.querySelector(".cart-item__total-price").textContent = total.toLocaleString("vi-VN") + " ₫";
    });
    </script>
    <script>
    document.addEventListener("change", function (e) {
        // checkbox chọn tất cả
        if (e.target.closest(".cart-info__check-all input")) {
            const checked = e.target.checked;
            document
                .querySelectorAll(".cart-item .cart-info__checkbox-input")
                .forEach(cb => cb.checked = checked);
        }
    });
    </script>
    <!--yêu thích -->
   <script>
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".cart-item__ctrl-btn");
        if (!btn) return;
        // Chỉ xử lý nút có icon tim
        const heartImg = btn.querySelector("img[src*='heart']");
        if (!heartImg) return;
        const cartItem = btn.closest(".cart-item");
        if (!cartItem) return;
        // hiệu ứng rung (đã có trong main.css)
        heartImg.classList.add("shake");
        setTimeout(() => heartImg.classList.remove("shake"), 400);
        // === XOÁ KHỎI YÊU THÍCH ===
        const id = btn.href?.split("remove_wishlist=")[1];
        if (!id) return;
        fetch("?remove_wishlist=" + id)
            .then(() => {
                cartItem.remove();
                // cập nhật số lượng
                const count = document.querySelectorAll(".cart-item").length;
                document.querySelector(".cart-info__desc").textContent =
                    count + " sản phẩm";
            });
    });
    </script>
    </body>
</html>
