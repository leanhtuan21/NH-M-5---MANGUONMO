<?php
session_start();
require_once 'db_connect.php';
/* === AJAX WISHLIST (CSDL) === */
if (isset($_POST['ajax_wishlist'])) {
    require_once 'db_connect.php';
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'not_login']);
        exit;
    }

    $uid = (int)$_SESSION['user_id'];
    $product_id = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($product_id <= 0) {
        echo json_encode(['status' => 'invalid']);
        exit;
    }

    if ($action === 'add') {
        $stmt = mysqli_prepare($conn,
            "INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ii", $uid, $product_id);
        mysqli_stmt_execute($stmt);
    }

    if ($action === 'remove') {
        $stmt = mysqli_prepare($conn,
            "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $uid, $product_id);
        mysqli_stmt_execute($stmt);
    }

    /* kiểm tra lại trạng thái thật trong DB */
    $check = mysqli_prepare($conn,
        "SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($check, "ii", $uid, $product_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    $liked = mysqli_stmt_num_rows($check) > 0;

    /* đếm tổng wishlist */
    $countRes = mysqli_query($conn,
        "SELECT COUNT(*) AS total FROM wishlists WHERE user_id = $uid"
    );
    $count = mysqli_fetch_assoc($countRes)['total'];

    echo json_encode([
        'status' => 'ok',
        'liked'  => $liked,
        'count'  => $count
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
if (!isset($_GET['id'])) {
    die('Thiếu ID sản phẩm');
}
$product_id = (int) $_GET['id'];
$uid = $_SESSION['user_id'];
 //kiểm tra nút đã thích chưa
$isLiked = false;
$check = mysqli_prepare($conn,
    "SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($check, "ii", $uid, $product_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) > 0) {
    $isLiked = true;
}
/* Lấy thông tin sản phẩm */
$sql = "
    SELECT 
        p.id,
        p.category_id,
        p.name,
        p.price,
        p.tax_percent,
        p.average_score,
        p.stock_quantity,
        p.description,
        p.weight_unit,
        pi.image_url
    FROM products p
    LEFT JOIN product_images pi 
        ON p.id = pi.product_id AND pi.is_main = 1
    WHERE p.id = ?
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$product) {
    die('Sản phẩm không tồn tại');
}
$ds_khoi_luong = [];
if (!empty($product['weight_unit'])) {
    $ds_khoi_luong = array_map('trim', explode(',', $product['weight_unit']));
}

/* ===== TÍNH GIÁ & THUẾ SAU KHI CÓ PRODUCT ===== */
// Giá gốc trong DB = giá gốc
$gia_goc = (float)$product['price'];
$thue = (int)$product['tax_percent'];
// gram mặc định = option đầu tiên
$gram_chon = isset($ds_khoi_luong[0]) ? (int)$ds_khoi_luong[0] : 100;
// tính giá theo gram
$gia_theo_gram = $gia_goc * ($gram_chon / 100);
// giá sau thuế
$gia_sau_thue = $gia_theo_gram * (1 + $thue / 100);
// Sản phẩm có nhiều ảnh
$images = [];
$stmt_img = mysqli_prepare($conn, "
    SELECT image_url 
    FROM product_images 
    WHERE product_id = ?
    ORDER BY is_main DESC
");
mysqli_stmt_bind_param($stmt_img, "i", $product_id);
mysqli_stmt_execute($stmt_img);
$result_img = mysqli_stmt_get_result($stmt_img);

while ($row = mysqli_fetch_assoc($result_img)) {
    $images[] = $row['image_url'];
}

if (empty($images)) {
    $images[] = 'default-product.png';
}
/* ===== SẢN PHẨM TƯƠNG TỰ ===== */
$sql_related = "
    SELECT 
        p.id,
        p.name,
        p.price,
        p.average_score,
        p.brand,
        pi.image_url
    FROM products p
    LEFT JOIN product_images pi 
        ON p.id = pi.product_id AND pi.is_main = 1
    WHERE p.category_id = ?
      AND p.id != ?
    LIMIT 6
";

$stmt_related = mysqli_prepare($conn, $sql_related);
mysqli_stmt_bind_param(
    $stmt_related,
    "ii",
    $product['category_id'],
    $product['id']
);
mysqli_stmt_execute($stmt_related);
$relatedProducts = mysqli_stmt_get_result($stmt_related);

?>
<!DOCTYPE html>
<html lang="en">
    <style>
    /* Ép dòng giá xuống cột */
    .prod-info__row:has(#gia-goc) {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    /* Style giá */
    .prod-info__price {
        font-size: 32px;
        font-weight: 700;
        color: #e53935;
    }
    .prod-info__tax {
        font-size: 14px;
        color: #2e7d32;
        background: #e8f5e9;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .prod-info__total-price {
        font-size: 18px;
        font-weight: 600;
    }
    .like-btn--liked .like-btn__icon {
        display: none;
    }
    .like-btn--liked .like-btn__icon--liked {
        display: inline-block;
    }
    .like-btn__icon--liked {
        display: none;
    }
    /* ảnh */
    .prod-preview__item {
    display: none;
}
.prod-preview__item--current {
    display: block;
}

    </style>
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Grocery Mart</title>

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
        <main class="product-page">
            <div class="container">
                <!-- Search bar -->
                <div class="product-container">
                    <div class="search-bar d-none d-md-flex">
                        <input type="text" name="" id="" placeholder="Search for item" class="search-bar__input" />
                        <button class="search-bar__submit">
                            <img src="./assets/icons/search.svg" alt="" class="search-bar__icon icon" />
                        </button>
                    </div>
                </div>

                <!-- Breadcrumbs -->
                <div class="product-container">
                    <ul class="breadcrumbs">
                        <li>
                            <a href="#!" class="breadcrumbs__link">
                                Departments
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link">
                                Coffee
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link">
                                Coffee Beans
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link breadcrumbs__link--current">LavAzza</a>
                        </li>
                    </ul>
                </div>

                <!-- Product info -->
                <div class="product-container prod-info-content">
                    <div class="row">
                        <div class="col-5 col-xl-6 col-lg-12">
                            <div class="prod-preview">
                                <div class="prod-preview__list">
                                    <?php foreach ($images as $i => $img): ?>
                                        <div class="prod-preview__item <?= $i === 0 ? 'prod-preview__item--current' : '' ?>">
                                            <img src="<?= htmlspecialchars($img) ?>" class="prod-preview__img">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="prod-preview__thumbs">
                                <?php foreach ($images as $i => $img): ?>
                                    <img 
                                        src="<?= htmlspecialchars($img) ?>"
                                        class="prod-preview__thumb-img <?= $i === 0 ? 'prod-preview__thumb-img--current' : '' ?>"
                                        data-index="<?= $i ?>"
                                    >
                                <?php endforeach; ?>
                            </div>
                            </div>
                        </div>
                        <div class="col-7 col-xl-6 col-lg-12">
                            <form method="POST" class="form" action="product-detail.php?id=<?= $product['id'] ?>" >
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <section class="prod-info">
                                    <h1 class="prod-info__heading">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </h1>
                                    <div class="prod-info__row">
                                        <div class="prod-info__total-price" id="gia-sau-thue">
                                            <?= number_format($gia_sau_thue, 0, ',', '.') ?> ₫
                                        </div>
                                        <div class="prod-info__price" id="gia-goc">
                                            <?= number_format($gia_theo_gram, 0, ',', '.') ?> ₫
                                        </div>
                                        <div class="prod-info__tax">
                                            Thuế VAT: <?= $thue ?>%
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-5 col-xxl-6 col-xl-12">
                                            <div class="prod-prop">
                                                <img src="./assets/icons/star.svg" alt="" class="prod-prop__icon" />
                                                <h4 class="prod-prop__title">
                                                    (<?= $product['average_score'] ?>) Reviews
                                                </h4>
                                            </div>
                                            <label for="" class="form__label prod-info__label">Khối lượng</label>
                                            <div class="filter__form-group">
                                                <div class="form__select-wrap">
                                                    <div class="form__select" style="--width: 146px">
                                                        <select name="weight_unit" id="weightSelect" class="prod-prop__title">
                                                            <?php foreach ($ds_khoi_luong as $khoi_luong): ?>
                                                                <option value="<?= $khoi_luong ?>">
                                                                    <?= $khoi_luong >= 1000 
                                                                        ? ($khoi_luong / 1000) . 'kg' 
                                                                        : $khoi_luong . 'g' 
                                                                    ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <label class="form__label prod-info__label">Số lượng</label>
                                            <div class="filter__form-group">
                                                <small>Còn <?= $product['stock_quantity'] ?> sản phẩm</small>
                                            </div>
                                        </div>
                                        <div class="col-7 col-xxl-6 col-xl-12">
                                            <div class="prod-props">
                                                <div class="prod-prop">
                                                    <img
                                                        src="./assets/icons/buy.svg"
                                                        alt=""
                                                        class="prod-prop__icon icon"
                                                    />
                                                    <div>
                                                        <h4 class="prod-prop__title">Vận chuyển</h4>
                                                        <p class="prod-prop__desc">Thời gian vận chuyển sẽ từ 3 - 6 ngày</p>
                                                    </div>
                                                </div>
                                                <div class="prod-info__card">
                                                    <div class="prod-info__row">
                                                        <button 
                                                            type="button" 
                                                            id="addToCartBtn"
                                                            name="add_to_cart"
                                                            class="btn btn--primary prod-info__add-to-cart"
                                                        >
                                                            Add to cart
                                                        </button>
                                                        <button type="button"
    class="like-btn <?= $isLiked ? 'like-btn--liked' : '' ?>"
    data-product-id="<?= $product['id'] ?>">

                                                            <img
                                                                src="./assets/icons/heart.svg"
                                                                alt=""
                                                                class="like-btn__icon icon"
                                                            />
                                                            <img
                                                                src="./assets/icons/heart-red.svg"
                                                                alt=""
                                                                class="like-btn__icon--liked"
                                                            />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Product content -->
                <div class="product-container">
                    <div class="prod-tab js-tabs">
                        <ul class="prod-tab__list">
                            <li class="prod-tab__item prod-tab__item--current">Miêu tả</li>
                            <li class="prod-tab__item">Đánh giá</li>
                            <li class="prod-tab__item">Sản phẩm tương tự</li>
                        </ul>
                        <div class="prod-tab__contents">
                            <div class="prod-tab__content prod-tab__content--current">
                                <div class="row">
                                    <div class="col-8 col-xl-10 col-lg-12">
                                        <div class="text-content prod-tab__text-content">
                                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="prod-tab__content">
                                <div class="prod-content">
                                    <h2 class="prod-content__heading">What our customers are saying</h2>
                                    <div class="row row-cols-3 gx-lg-2 row-cols-md-1 gy-md-3">
                                        <!-- Review card 1 -->
                                        <div class="col">
                                            <div class="review-card">
                                                <div class="review-card__content">
                                                    <img
                                                        src="./assets/img/avatar/avatar-1.png"
                                                        alt=""
                                                        class="review-card__avatar"
                                                    />
                                                    <div class="review-card__info">
                                                        <h4 class="review-card__title">Jakir Hussen</h4>
                                                        <p class="review-card__desc">
                                                            Great product, I love this Coffee Beans
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="review-card__rating">
                                                    <div class="review-card__star-list">
                                                        <img
                                                            src="./assets/icons/star.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star-half.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star-blank.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                    </div>
                                                    <span class="review-card__rating-title">(3.5) Review</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Review card 2 -->
                                        <div class="col">
                                            <div class="review-card">
                                                <div class="review-card__content">
                                                    <img
                                                        src="./assets/img/avatar/avatar-2.png"
                                                        alt=""
                                                        class="review-card__avatar"
                                                    />
                                                    <div class="review-card__info">
                                                        <h4 class="review-card__title">Jubed Ahmed</h4>
                                                        <p class="review-card__desc">
                                                            Awesome Coffee, I love this Coffee Beans
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="review-card__rating">
                                                    <div class="review-card__star-list">
                                                        <img
                                                            src="./assets/icons/star.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star-half.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star-blank.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                    </div>
                                                    <span class="review-card__rating-title">(3.5) Review</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Review card 3 -->
                                        <div class="col">
                                            <div class="review-card">
                                                <div class="review-card__content">
                                                    <img
                                                        src="./assets/img/avatar/avatar-3.png"
                                                        alt=""
                                                        class="review-card__avatar"
                                                    />
                                                    <div class="review-card__info">
                                                        <h4 class="review-card__title">Delwar Hussain</h4>
                                                        <p class="review-card__desc">
                                                            Great product, I like this Coffee Beans
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="review-card__rating">
                                                    <div class="review-card__star-list">
                                                        <img
                                                            src="./assets/icons/star.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star-half.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                        <img
                                                            src="./assets/icons/star-blank.svg"
                                                            alt=""
                                                            class="review-card__star"
                                                        />
                                                    </div>
                                                    <span class="review-card__rating-title">(3.5) Review</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="prod-tab__content">
                                <div class="prod-content">
                                    <h2 class="prod-content__heading">Gợi ý sản phẩm tương tự</h2>
                                    <div class="row row-cols-6 row-cols-xl-4 row-cols-lg-3 row-cols-md-2 row-cols-sm-1 g-2">
                                        <?php if (mysqli_num_rows($relatedProducts) == 0): ?>
                                            <p>Không có sản phẩm tương tự</p>
                                        <?php else: ?>
                                            <?php while ($rp = mysqli_fetch_assoc($relatedProducts)): ?>
                                                <div class="col">
                                                    <article class="product-card">
                                                        <div class="product-card__img-wrap">
                                                            <a href="product-detail.php?id=<?= $rp['id'] ?>">
                                                                <img
                                                                    src="<?= $rp['image_url'] ?? 'default-product.png' ?>"
                                                                    class="product-card__thumb"
                                                                />
                                                            </a>
                                                        </div>

                                                        <h3 class="product-card__title">
                                                            <a href="product-detail.php?id=<?= $rp['id'] ?>">
                                                                <?= htmlspecialchars($rp['name']) ?>
                                                            </a>
                                                        </h3>

                                                        <p class="product-card__brand">
                                                            <?= htmlspecialchars($rp['brand']) ?>
                                                        </p>

                                                        <div class="product-card__row">
                                                            <span class="product-card__price">
                                                                <?= number_format($rp['price'], 0, ',', '.') ?> ₫
                                                            </span>
                                                            <img src="./assets/icons/star.svg" class="product-card__star" />
                                                            <span class="product-card__score">
                                                                <?= $rp['average_score'] ?>
                                                            </span>
                                                        </div>
                                                    </article>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
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
        <!-- Phần chọn gram nhảy giá -->
        <script>
            const gia100 = <?= (float)$product['price'] ?>;
            const thue = <?= (int)$product['tax_percent'] ?>;
            function dinhDangGia(vnd) {
                return vnd.toLocaleString('vi-VN') + ' ₫';
            }
            document.getElementById('weightSelect').addEventListener('change', function () {
                const gram = parseInt(this.value);
                const gia = gia100 * (gram / 100);
                const giaSauThue = gia * (1 + thue / 100);

                document.getElementById('gia-goc').innerText = dinhDangGia(gia);
                document.getElementById('gia-sau-thue').innerText = dinhDangGia(giaSauThue);
            });
        </script>
        <!-- Ảnh -->
        <script>
            const thumbs = document.querySelectorAll('.prod-preview__thumb-img');
            const bigImages = document.querySelectorAll('.prod-preview__item');
            thumbs.forEach(thumb => {
                thumb.addEventListener('click', function () {
                    const index = this.dataset.index;
                    // reset ảnh lớn
                    bigImages.forEach(img => img.classList.remove('prod-preview__item--current'));
                    // reset thumbnail
                    thumbs.forEach(t => t.classList.remove('prod-preview__thumb-img--current'));
                    // set ảnh được chọn
                    bigImages[index].classList.add('prod-preview__item--current');
                    this.classList.add('prod-preview__thumb-img--current');
                });
            });
        </script>
        <!-- mục yêu thích -->
    <script>
    function initWishlist() {
        document.querySelectorAll('.like-btn[data-product-id]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const productId = this.dataset.productId;
            const isLikedNow = this.classList.contains('like-btn--liked');
            const action = isLikedNow ? 'remove' : 'add';

            fetch('product-detail.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ajax_wishlist=1&product_id=${productId}&action=${action}`
            })
            .then(res => res.json())
            .then(data => {
            if (data.status !== 'ok') return;

            if (data.liked) {
                this.classList.add('like-btn--liked');
            } else {
                this.classList.remove('like-btn--liked');
            }
            load("#header", "./templates/header-logined.php")
            // update số tim
            document.getElementById('wishlistCount').innerText =
                String(data.count).padStart(2, '0');

            const textCount = document.getElementById('wishlistCountText');
            if (textCount) textCount.textContent = data.count;
        });
        });
        });
    }

    // ⏳ CHỜ HEADER LOAD XONG
    const wait = setInterval(() => {
        if (
            document.getElementById('wishlistCount') &&
            document.querySelector('.act-dropdown__list')
        ) {
            clearInterval(wait);
            initWishlist();
        }
    }, 100);
    </script>
    </body>
</html>
