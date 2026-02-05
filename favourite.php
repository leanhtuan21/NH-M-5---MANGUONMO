<?php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
if (isset($_GET['remove_wishlist'])) {
    $pid = (int)$_GET['remove_wishlist'];
    $uid = (int)$_SESSION['user_id'];

    $sql = "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $uid, $pid);
    mysqli_stmt_execute($stmt);

    // Nếu gọi bằng fetch thì không redirect
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header("Location: favourite.php");
    }
    exit;
}
//Yêu thích
$uid = (int)$_SESSION['user_id'];
$sql = "
    SELECT 
        p.id,
        p.name,
        p.price,
        p.stock_quantity,
        p.weight_unit,
        pi.image_url AS image,
        p.brand
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN product_images pi 
        ON pi.product_id = p.id AND pi.is_main = 1
    WHERE w.user_id = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$wishlist = [];
while ($row = mysqli_fetch_assoc($result)) {
    $wishlist[] = $row;
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
                                                    <p class="cart-item__weight" style="font-size: 1.4rem; color: #717385; margin-top: 4px;">
                                                        Khối lượng: 100g
                                                    </p>
                                                    <?php if ($item['stock_quantity'] >= 1): ?>
                                                        <span class="cart-item__status" style="color: #67ce5d;">Còn hàng</span>
                                                    <?php else: ?>
                                                        <span class="cart-item__status" style="color: #ed6237;">Hết hàng</span>
                                                    <?php endif; ?>
                                                </p>
                                                <!-- GIỮ NGUYÊN CTRL -->
                                                <div class="cart-item__ctrl-wrap">
                                                    <div class="cart-item__ctrl cart-item__ctrl--md-block">
                                                        <div class="cart-item__input">
                                                            <?= htmlspecialchars($item['brand']) ?>
                                                        </div>
                                                    </div>
                                                    <div class="cart-item__ctrl">
                                                        <a href="#"
   class="cart-item__ctrl-btn"
   data-id="<?= $item['id'] ?>"
   title="Bỏ khỏi yêu thích">

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

    e.preventDefault();

    const id = btn.dataset.id;
    const cartItem = btn.closest(".cart-item");
    const heartImg = btn.querySelector("img");

    if (!id) return;

    heartImg.classList.add("shake");
    setTimeout(() => heartImg.classList.remove("shake"), 400);

    fetch("favourite.php?remove_wishlist=" + id, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(() => {
        cartItem.remove();

        const count = document.querySelectorAll(".cart-item").length;
        document.querySelector(".cart-info__desc").textContent =
            count + " sản phẩm";

        if (count === 0) {
            document.querySelector(".cart-info__list").innerHTML =
                "<p>Chưa có sản phẩm yêu thích</p>";
        }
        load("#header", "./templates/header-logined.php");
    });
});
</script>

    </body>
</html>
