<?php
session_start();
require_once 'db_connect.php';
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $gram       = (int)$_POST['weight_unit'];
    $quantity   = 1;
    // LẤY GIÁ + THUẾ TỪ DB
    $stmt = mysqli_prepare($conn, "
        SELECT 
            p.name,
            p.price,
            p.tax_percent,
            pi.image_url
        FROM products p
        LEFT JOIN product_images pi 
            ON p.id = pi.product_id AND pi.is_main = 1
        WHERE p.id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$p) {
        exit;
    }

    $gia_250g = (float)$p['price'];
    $thue     = (int)$p['tax_percent'];

    $gia = $gia_250g * ($gram / 250);
    $gia_sau_thue = $gia * (1 + $thue / 100);

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $key = $product_id . '_' . $gram;

    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += 1;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => $product_id,
            'name'       => $p['name'],
            'image'      => $p['image_url'] ?? 'default-product.png',
            'gram'       => $gram,
            'price'      => $gia_sau_thue,
            'quantity'   => 1
        ];
    }
    // ❗ KHÔNG redirect
    exit;
}
if (!isset($_GET['id'])) {
    die('Thiếu ID sản phẩm');
}
$product_id = (int) $_GET['id'];
/* Lấy thông tin sản phẩm */
$sql = "
    SELECT 
        p.id,
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
// Giá gốc trong DB = giá 250g
$gia_250g = (float)$product['price'];
$thue = (int)$product['tax_percent'];
// gram mặc định = option đầu tiên
$gram_chon = isset($ds_khoi_luong[0]) ? (int)$ds_khoi_luong[0] : 250;
// tính giá theo gram
$gia_theo_gram = $gia_250g * ($gram_chon / 250);
// giá sau thuế
$gia_sau_thue = $gia_theo_gram * (1 + $thue / 100);
/* Ảnh mặc định */
$image = !empty($product['image_url']) 
    ? $product['image_url'] 
    : 'default-product.png';
/* Giá sau thuế */
$total_price = $product['price'] * (1 + $product['tax_percent'] / 100);
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
                                    <?php foreach ($images as $img): ?>
                                        <div class="prod-preview__item">
                                            <img src="<?= htmlspecialchars($img) ?>" class="prod-preview__img">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="prod-preview__thumbs">
                                    <?php foreach ($images as $i => $img): ?>
                                        <img 
                                            src="<?= htmlspecialchars($img) ?>"
                                            class="prod-preview__thumb-img <?= $i === 0 ? 'prod-preview__thumb-img--current' : '' ?>"
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
                                                            type="submit" 
                                                            name="add_to_cart"
                                                            class="btn btn--primary prod-info__add-to-cart"
                                                        >
                                                            Add to cart
                                                        </button>
                                                        <button type="button" class="like-btn prod-info__like-btn">
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
                                    <h2 class="prod-content__heading">Similar items you might like</h2>
                                    <div
                                        class="row row-cols-6 row-cols-xl-4 row-cols-lg-3 row-cols-md-2 row-cols-sm-1 g-2"
                                    >
                                        <!-- Product card 1 -->
                                        <div class="col">
                                            <article class="product-card">
                                                <div class="product-card__img-wrap">
                                                    <a href="./product-detail.php">
                                                        <img
                                                            src="./assets/img/product/item-1.png"
                                                            alt=""
                                                            class="product-card__thumb"
                                                        />
                                                    </a>
                                                    <button class="like-btn product-card__like-btn">
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
                                                <h3 class="product-card__title">
                                                    <a href="./product-detail.php"
                                                        >Coffee Beans - Espresso Arabica and Robusta Beans</a
                                                    >
                                                </h3>
                                                <p class="product-card__brand">Lavazza</p>
                                                <div class="product-card__row">
                                                    <span class="product-card__price">$47.00</span>
                                                    <img
                                                        src="./assets/icons/star.svg"
                                                        alt=""
                                                        class="product-card__star"
                                                    />
                                                    <span class="product-card__score">4.3</span>
                                                </div>
                                            </article>
                                        </div>

                                        <!-- Product card 2 -->
                                        <div class="col">
                                            <article class="product-card">
                                                <div class="product-card__img-wrap">
                                                    <a href="./product-detail.php">
                                                        <img
                                                            src="./assets/img/product/item-2.png"
                                                            alt=""
                                                            class="product-card__thumb"
                                                        />
                                                    </a>
                                                    <button class="like-btn product-card__like-btn">
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
                                                <h3 class="product-card__title">
                                                    <a href="./product-detail.php"
                                                        >Lavazza Coffee Blends - Try the Italian Espresso</a
                                                    >
                                                </h3>
                                                <p class="product-card__brand">Lavazza</p>
                                                <div class="product-card__row">
                                                    <span class="product-card__price">$53.00</span>
                                                    <img
                                                        src="./assets/icons/star.svg"
                                                        alt=""
                                                        class="product-card__star"
                                                    />
                                                    <span class="product-card__score">3.4</span>
                                                </div>
                                            </article>
                                        </div>

                                        <!-- Product card 3 -->
                                        <div class="col">
                                            <article class="product-card">
                                                <div class="product-card__img-wrap">
                                                    <a href="./product-detail.php">
                                                        <img
                                                            src="./assets/img/product/item-3.png"
                                                            alt=""
                                                            class="product-card__thumb"
                                                        />
                                                    </a>
                                                    <button class="like-btn like-btn--liked product-card__like-btn">
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
                                                <h3 class="product-card__title">
                                                    <a href="./product-detail.php"
                                                        >Lavazza - Caffè Espresso Black Tin - Ground coffee</a
                                                    >
                                                </h3>
                                                <p class="product-card__brand">Welikecoffee</p>
                                                <div class="product-card__row">
                                                    <span class="product-card__price">$99.99</span>
                                                    <img
                                                        src="./assets/icons/star.svg"
                                                        alt=""
                                                        class="product-card__star"
                                                    />
                                                    <span class="product-card__score">5.0</span>
                                                </div>
                                            </article>
                                        </div>

                                        <!-- Product card 4 -->
                                        <div class="col">
                                            <article class="product-card">
                                                <div class="product-card__img-wrap">
                                                    <a href="./product-detail.php">
                                                        <img
                                                            src="./assets/img/product/item-4.png"
                                                            alt=""
                                                            class="product-card__thumb"
                                                        />
                                                    </a>
                                                    <button class="like-btn product-card__like-btn">
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
                                                <h3 class="product-card__title">
                                                    <a href="./product-detail.php"
                                                        >Qualità Oro Mountain Grown - Espresso Coffee Beans</a
                                                    >
                                                </h3>
                                                <p class="product-card__brand">Lavazza</p>
                                                <div class="product-card__row">
                                                    <span class="product-card__price">$38.65</span>
                                                    <img
                                                        src="./assets/icons/star.svg"
                                                        alt=""
                                                        class="product-card__star"
                                                    />
                                                    <span class="product-card__score">4.4</span>
                                                </div>
                                            </article>
                                        </div>
                                        <!-- Product card 5 -->
                                        <div class="col">
                                            <article class="product-card">
                                                <div class="product-card__img-wrap">
                                                    <a href="./product-detail.php">
                                                        <img
                                                            src="./assets/img/product/item-1.png"
                                                            alt=""
                                                            class="product-card__thumb"
                                                        />
                                                    </a>
                                                    <button class="like-btn product-card__like-btn">
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
                                                <h3 class="product-card__title">
                                                    <a href="./product-detail.php"
                                                        >Coffee Beans - Espresso Arabica and Robusta Beans</a
                                                    >
                                                </h3>
                                                <p class="product-card__brand">Lavazza</p>
                                                <div class="product-card__row">
                                                    <span class="product-card__price">$47.00</span>
                                                    <img
                                                        src="./assets/icons/star.svg"
                                                        alt=""
                                                        class="product-card__star"
                                                    />
                                                    <span class="product-card__score">4.3</span>
                                                </div>
                                            </article>
                                        </div>

                                        <!-- Product card 6 -->
                                        <div class="col">
                                            <article class="product-card">
                                                <div class="product-card__img-wrap">
                                                    <a href="./product-detail.php">
                                                        <img
                                                            src="./assets/img/product/item-2.png"
                                                            alt=""
                                                            class="product-card__thumb"
                                                        />
                                                    </a>
                                                    <button class="like-btn product-card__like-btn">
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
                                                <h3 class="product-card__title">
                                                    <a href="./product-detail.php"
                                                        >Lavazza Coffee Blends - Try the Italian Espresso</a
                                                    >
                                                </h3>
                                                <p class="product-card__brand">Lavazza</p>
                                                <div class="product-card__row">
                                                    <span class="product-card__price">$53.00</span>
                                                    <img
                                                        src="./assets/icons/star.svg"
                                                        alt=""
                                                        class="product-card__star"
                                                    />
                                                    <span class="product-card__score">3.4</span>
                                                </div>
                                            </article>
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
        <!-- Phần chọn gram nhảy giá -->
        <script>
            const gia250 = <?= (float)$product['price'] ?>;
            const thue = <?= (int)$product['tax_percent'] ?>;
            function dinhDangGia(vnd) {
                return vnd.toLocaleString('vi-VN') + ' ₫';
            }
            document.getElementById('weightSelect').addEventListener('change', function () {
                const gram = parseInt(this.value);
                const gia = gia250 * (gram / 250);
                const giaSauThue = gia * (1 + thue / 100);

                document.getElementById('gia-goc').innerText = dinhDangGia(gia);
                document.getElementById('gia-sau-thue').innerText = dinhDangGia(giaSauThue);
            });
        </script>
        <!-- Trái tim -->
        <script>
            document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', function () {
            this.classList.toggle('like-btn--liked');
                });
            });
        </script>
    </body>
</html>
