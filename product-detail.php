<?php
session_start();
require_once 'db_connect.php';

$message = '';
if (isset($_GET['added']) && $_GET['added'] == '1') {
    $message = 'Sản phẩm đã được thêm vào giỏ hàng!';
}

$product = null;
$images = [];

/** * 2. XỬ LÝ AJAX WISHLIST (YÊU THÍCH)
 * Đặt ở đầu để khi gọi AJAX, script dừng lại và trả về JSON ngay lập tức, không load phần HTML bên dưới.
 */
if (isset($_POST['ajax_wishlist'])) {
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
        $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $uid, $product_id);
        mysqli_stmt_execute($stmt);
    }

    if ($action === 'remove') {
        $stmt = mysqli_prepare($conn, "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $uid, $product_id);
        mysqli_stmt_execute($stmt);
    }

    $check = mysqli_prepare($conn, "SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1");
    mysqli_stmt_bind_param($check, "ii", $uid, $product_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    $liked = mysqli_stmt_num_rows($check) > 0;

    $countRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM wishlists WHERE user_id = $uid");
    $count = mysqli_fetch_assoc($countRes)['total'];

    echo json_encode(['status' => 'ok', 'liked' => $liked, 'count' => $count]);
    exit;
}

/** * 3. KIỂM TRA ĐĂNG NHẬP & ID SẢN PHẨM 
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    die('Thiếu ID sản phẩm');
}

$product_id = (int) $_GET['id'];
$uid = $_SESSION['user_id'];

/** * 4. TRUY VẤN DỮ LIỆU SẢN PHẨM & TRẠNG THÁI YÊU THÍCH
 */
// Kiểm tra sản phẩm đã thích chưa
$isLiked = false;
$check = mysqli_prepare($conn, "SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1");
mysqli_stmt_bind_param($check, "ii", $uid, $product_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) { $isLiked = true; }

// Lấy thông tin chi tiết sản phẩm
$sql = "SELECT p.*, pi.image_url FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
        WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) { die('Sản phẩm không tồn tại'); }

// Lấy danh sách khối lượng + tồn kho từ bảng product_weights
$ds_khoi_luong = [];

$stmt_w = mysqli_prepare($conn, "
    SELECT weight_gram, stock_quantity 
    FROM product_weights 
    WHERE product_id = ?
    ORDER BY weight_gram ASC
");
mysqli_stmt_bind_param($stmt_w, "i", $product_id);
mysqli_stmt_execute($stmt_w);
$res_w = mysqli_stmt_get_result($stmt_w);

while ($row = mysqli_fetch_assoc($res_w)) {
    $ds_khoi_luong[] = $row;
}


/** * 5. TÍNH TOÁN GIÁ CẢ BAN ĐẦU 
 */
$gia_goc = (float)$product['price'];
$thue = (int)$product['tax_percent'];
$gram_chon = isset($ds_khoi_luong[0]) ? (int)$ds_khoi_luong[0]['weight_gram'] : 100;
$stock_mac_dinh = isset($ds_khoi_luong[0]) ? (int)$ds_khoi_luong[0]['stock_quantity'] : 0;

$gia_theo_gram = $gia_goc * ($gram_chon / 100);
$gia_sau_thue = $gia_theo_gram * (1 + $thue / 100);

/** * 6. LẤY DANH SÁCH ẢNH SẢN PHẨM 
 */
$stmt_img = mysqli_prepare($conn, "SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
mysqli_stmt_bind_param($stmt_img, "i", $product_id);
mysqli_stmt_execute($stmt_img);
$result_img = mysqli_stmt_get_result($stmt_img);
while ($row = mysqli_fetch_assoc($result_img)) { $images[] = $row['image_url']; }
if (empty($images)) { $images[] = 'default-product.png'; }

/** * 7. LẤY SẢN PHẨM TƯƠNG TỰ 
 */
$sql_related = "SELECT p.id, p.name, p.price, p.average_score, p.brand, pi.image_url 
                FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
                WHERE p.category_id = ? AND p.id != ? LIMIT 6";
$stmt_related = mysqli_prepare($conn, $sql_related);
mysqli_stmt_bind_param($stmt_related, "ii", $product['category_id'], $product['id']);
mysqli_stmt_execute($stmt_related);
$relatedProducts = mysqli_stmt_get_result($stmt_related);

/* ===============================
LẤY REVIEW SẢN PHẨM - từ trang add-review
=============================== */
$sqlReviews = "
    SELECT 
        r.id,
        r.rating,
        r.comment,
        r.created_at,
        u.full_name AS user_name,
        u.avatar
    FROM product_reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
";

// LẤY SỐ LƯỢNG ĐÁNH GIÁ
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM product_reviews
    WHERE product_id = ?
";
$stmtCount = mysqli_prepare($conn, $sqlCount);
mysqli_stmt_bind_param($stmtCount, "i", $product_id);
mysqli_stmt_execute($stmtCount);
$totalReviews = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount))['total'];

$stmtRv = mysqli_prepare($conn, $sqlReviews);
mysqli_stmt_bind_param($stmtRv, "i", $product_id);
mysqli_stmt_execute($stmtRv);
$reviews = mysqli_stmt_get_result($stmtRv);

/* Gom ảnh theo review_id */
$reviewImages = [];
$sqlImgs = "
    SELECT review_id, image_path
    FROM review_images
    WHERE review_id IN (
        SELECT id FROM product_reviews WHERE product_id = ?
    )
";
$stmtImg = mysqli_prepare($conn, $sqlImgs);
mysqli_stmt_bind_param($stmtImg, "i", $product_id);
mysqli_stmt_execute($stmtImg);
$resImg = mysqli_stmt_get_result($stmtImg);

while ($img = mysqli_fetch_assoc($resImg)) {
    $reviewImages[$img['review_id']][] = $img['image_path'];
}

/* ===============================
LẤY REVIEW + FILTER SAO (FIX CHUẨN)
=============================== */

$product_id = (int)($_GET['id'] ?? 0);
$starFilter = 0;

/* ===============================
   QUERY REVIEW (HIỆN 5 MỚI NHẤT)
=============================== */
$sqlReviews = "
    SELECT 
        r.id,
        r.rating,
        r.comment,
        r.created_at,
        u.full_name AS user_name,
        COALESCE(NULLIF(u.avatar, ''), 'avatar_1.jpg') AS avatar
    FROM product_reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
";

if ($starFilter >= 1 && $starFilter <= 5) {
    $sqlReviews .= " AND r.rating = $starFilter";
}

$sqlReviews .= " ORDER BY r.created_at DESC LIMIT 5";

$stmtRv = mysqli_prepare($conn, $sqlReviews);
mysqli_stmt_bind_param($stmtRv, "i", $product_id);
mysqli_stmt_execute($stmtRv);
$reviews = mysqli_stmt_get_result($stmtRv);


/* ===============================
   ĐẾM TỔNG REVIEW
=============================== */
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM product_reviews
    WHERE product_id = ?
";
$stmtCount = mysqli_prepare($conn, $sqlCount);
mysqli_stmt_bind_param($stmtCount, "i", $product_id);
mysqli_stmt_execute($stmtCount);
$totalReviews = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount))['total'];


/* ===============================
   ĐẾM THEO SAO
=============================== */
$starCounts = array_fill(1, 5, 0);

$sqlStar = "
    SELECT rating, COUNT(*) as total
    FROM product_reviews
    WHERE product_id = ?
    GROUP BY rating
";

$stmtStar = mysqli_prepare($conn, $sqlStar);
mysqli_stmt_bind_param($stmtStar, "i", $product_id);
mysqli_stmt_execute($stmtStar);
$resStar = mysqli_stmt_get_result($stmtStar);

while ($row = mysqli_fetch_assoc($resStar)) {
    $starCounts[$row['rating']] = $row['total'];
}


/* ===============================
   LẤY ẢNH REVIEW
=============================== */
$reviewImages = [];

$sqlImgs = "
    SELECT review_id, image_path
    FROM review_images
    WHERE review_id IN (
        SELECT id FROM product_reviews WHERE product_id = ?
    )
";

$stmtImg = mysqli_prepare($conn, $sqlImgs);
mysqli_stmt_bind_param($stmtImg, "i", $product_id);
mysqli_stmt_execute($stmtImg);
$resImg = mysqli_stmt_get_result($stmtImg);

while ($img = mysqli_fetch_assoc($resImg)) {
    $reviewImages[$img['review_id']][] = $img['image_path'];
}


/* ===============================
   AJAX FILTER REVIEW
=============================== */
if (isset($_POST['ajax_filter_review'])) {
    header('Content-Type: application/json');

    $product_id = (int)($_POST['product_id'] ?? 0);
    $star = (int)($_POST['star'] ?? 0);

    $sql = "
        SELECT 
            r.id, 
            r.rating, 
            r.comment, 
            r.created_at, 
            u.full_name AS user_name,
            COALESCE(NULLIF(u.avatar, ''), 'avatar_1.jpg') AS avatar
        FROM product_reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
    ";

    if ($star >= 1 && $star <= 5) {
        $sql .= " AND r.rating = $star";
    }

    $sql .= " ORDER BY r.created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $data = [];

    while ($row = mysqli_fetch_assoc($res)) {

        // LẤY ẢNH REVIEW
        $imgStmt = mysqli_prepare($conn, "
            SELECT image_path FROM review_images WHERE review_id = ?
        ");
        mysqli_stmt_bind_param($imgStmt, "i", $row['id']);
        mysqli_stmt_execute($imgStmt);
        $imgRes = mysqli_stmt_get_result($imgStmt);

        $images = [];
        while ($img = mysqli_fetch_assoc($imgRes)) {
            $images[] = $img['image_path'];
        }

        $row['images'] = $images;
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Grocery Mart - <?= htmlspecialchars($product['name']) ?></title>

    <link rel="apple-touch-icon" sizes="76x76" href="./assets/favicon/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="./assets/favicon/favicon-16x16.png" />
    <link rel="manifest" href="./assets/favicon/site.webmanifest" />
    <meta name="msapplication-TileColor" content="#da532c" />
    <meta name="theme-color" content="#ffffff" />

    <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />
    <link rel="stylesheet" href="./assets/css/main.css" />

    <script src="./assets/js/scripts.js"></script>

    <style>
        .prod-info__row:has(#gia-goc) { flex-direction: column; align-items: flex-start; gap: 6px; }
        .prod-info__price { font-size: 32px; font-weight: 700; color: #e53935; }
        .prod-info__tax { font-size: 14px; color: #2e7d32; background: #e8f5e9; padding: 4px 8px; border-radius: 6px; }
        .prod-info__total-price { font-size: 18px; font-weight: 600; }
        .like-btn--liked .like-btn__icon { display: none; }
        .like-btn--liked .like-btn__icon--liked { display: inline-block; }
        .like-btn__icon--liked { display: none; }
        .prod-preview__item { display: none; }
        .prod-preview__item--current { display: block; }

        .cart-item__input {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 4px 6px;
            width: fit-content;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 6px;
            background: #f2f2f2;
            font-size: 30px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            background: #e0e0e0;
        }

        .qty-input {
            width: 40px;          /* QUAN TRỌNG: đủ chỗ cho 2-3 chữ số */
            text-align: center;
            border: none;
            outline: none;
            font-size: 20px;
            font-weight: 600;
        }

        /* Ẩn mũi tên tăng giảm mặc định của input number */
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .qty-input[type=number] {
            -moz-appearance: textfield;
        }

       /* ===== FILTER SAO ===== */
        .review-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0 16px;
        }

        .filter-btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
        }

        .filter-btn.active {
            background: #ff6a00;
            color: #fff;
            border-color: #ff6a00;
        }

        /* ===== CONTAINER ===== */
        .prod-content {
            padding-top: 12px;
        }

        /* ===== GRID FIX ===== */
        #review-list .row > div {
            display: flex;
        }

       /* ===== REVIEW CARD ===== */
        .review-card {
            background: #f7f7f7;
            border-radius: 16px;
            padding: 20px;
            height: 100%;

            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;

            transition: transform .15s ease, box-shadow .15s ease;
        }

        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }

        /* ===== AVATAR CENTER ===== */
        .review-card__avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 12px;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        /* ===== USER NAME ===== */
        .review-card__title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #222;
        }

        /* ===== COMMENT (GIỮ CHIỀU CAO ĐỀU) ===== */
        .review-card__desc {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 12px;

            min-height: 48px; /* giữ card đều */
            word-break: break-word;
        }

        /* ===== REVIEW IMAGES CENTER ===== */
        .review-images {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .review-images img {
            width: 60px;
            height: 60px;
            object-fit: cover;++
            border-radius: 8px;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .review-images img:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        /* ===== RATING BOTTOM ===== */
        .review-card__rating {
            margin-top: auto;
            padding-top: 12px;
        }

        .review-card__star-list {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-bottom: 4px;
        }

        .review-card__star {
            width: 16px;
            height: 16px;
        }

        .review-card__rating-title {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        /* Thu khoảng cách giữa các review */
        #review-list .row {
            justify-content: left;
            gap: 16px;
        }

        /* Card không kéo giãn */
        #review-list .col {
            max-width: 260px;
        }
    </style>
</head>

<body>
    <header id="header" class="header"></header>
    <script>load("#header", "./templates/header-logined.php");</script>

    <main class="product-page">
        <div class="container">
            <div class="product-container">
                <div class="search-bar d-none d-md-flex">
                    <input type="text" placeholder="Search for item" class="search-bar__input" />
                    <button class="search-bar__submit">
                        <img src="./assets/icons/search.svg" alt="" class="search-bar__icon icon" />
                    </button>
                </div>
            </div>

            <div class="product-container">
                <ul class="breadcrumbs">
                    <li><a href="#!" class="breadcrumbs__link">Departments <img src="./assets/icons/arrow-right.svg" alt="" /></a></li>
                    <li><a href="#!" class="breadcrumbs__link">Coffee <img src="./assets/icons/arrow-right.svg" alt="" /></a></li>
                    <li><a href="#!" class="breadcrumbs__link">Coffee Beans <img src="./assets/icons/arrow-right.svg" alt="" /></a></li>
                    <li><a href="#!" class="breadcrumbs__link breadcrumbs__link--current">LavAzza</a></li>
                </ul>
            </div>

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
                                    <img src="<?= htmlspecialchars($img) ?>"
                                         class="prod-preview__thumb-img <?= $i === 0 ? 'prod-preview__thumb-img--current' : '' ?>"
                                         data-index="<?= $i ?>">
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-7 col-xl-6 col-lg-12">
                        <form method="POST" class="form" action="add_to_product.php">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <section class="prod-info">
                                <h1 class="prod-info__heading"><?= htmlspecialchars($product['name']) ?></h1>
                                
                                <div class="prod-info__row">
                                    <div class="prod-info__total-price" id="gia-sau-thue"><?= number_format($gia_sau_thue, 0, ',', '.') ?> ₫</div>
                                    <div class="prod-info__price" id="gia-goc"><?= number_format($gia_theo_gram, 0, ',', '.') ?> ₫</div>
                                    <div class="prod-info__tax">Thuế VAT: <?= $thue ?>%</div>
                                </div>

                                <div class="row">
                                    <div class="col-5 col-xxl-6 col-xl-12">
                                        <div class="prod-prop">
                                            <img src="./assets/icons/star.svg" alt="" class="prod-prop__icon" />
                                            <h4 class="prod-prop__title">(<?= $product['average_score'] ?>) Reviews</h4>
                                        </div>

                                        <label class="form__label prod-info__label">Khối lượng</label>
                                        <div class="filter__form-group">
                                            <div class="form__select-wrap">
                                                <div class="form__select" style="--width: 146px">
                                                <select name="weight_unit" id="weightSelect" class="prod-prop__title">
                                                    <?php foreach ($ds_khoi_luong as $row): ?>
                                                        <option 
                                                            value="<?= $row['weight_gram'] ?>" 
                                                            data-stock="<?= $row['stock_quantity'] ?>"
                                                        >
                                                            <?= $row['weight_gram'] >= 1000 
                                                                ? ($row['weight_gram'] / 1000) . 'kg' 
                                                                : $row['weight_gram'] . 'g' ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                </div>
                                            </div>
                                        </div>

                                        <label class="form__label prod-info__label">Số lượng</label>
                                        <div class="filter__form-group">
                                            <small id="stockText">Còn <?= (int)$stock_mac_dinh ?> sản phẩm</small>
                                        </div>
                                    </div>

                                    <div class="col-7 col-xxl-6 col-xl-12">
                                        <div class="prod-props">
                                            <div class="prod-prop">
                                                <img src="./assets/icons/buy.svg" alt="" class="prod-prop__icon icon" />
                                                <div>
                                                    <h4 class="prod-prop__title">Vận chuyển</h4>
                                                    <p class="prod-prop__desc">Thời gian vận chuyển sẽ từ 3 - 6 ngày</p>
                                                </div>
                                            </div>

                                            <div class="prod-info__card">
                                            <div class="cart-item__input">
                                                <button type="button" class="qty-btn" onclick="decreaseQuantity()">−</button>
                                                    <input type="number" name="product_quantity" id="quantityInput" value="1" min="1" class="qty-input">
                                                <button type="button" class="qty-btn" onclick="increaseQuantity()">+</button>
                                            </div>
                                                <div class="prod-info__row">
                                                    <button type="button" class="btn btn--primary prod-info__add-to-cart" onclick="handleAddToCart(event)">Thêm vào giỏ hàng</button>
                                                    <button type="button" class="like-btn <?= $isLiked ? 'like-btn--liked' : '' ?>" data-product-id="<?= $product['id'] ?>">
                                                        <img src="./assets/icons/heart.svg" class="like-btn__icon icon" />
                                                        <img src="./assets/icons/heart-red.svg" class="like-btn__icon--liked" />
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
           
            <!-- Phần hiển thị đánh giá + sản phẩm tương tự -->
            <div class="product-container">
                <div class="prod-tab js-tabs">

                    <!-- TAB TITLE -->
                    <ul class="prod-tab__list">
                        <li class="prod-tab__item prod-tab__item--current">Mô tả</li>
                        <li class="prod-tab__item">Đánh giá</li>
                        <li class="prod-tab__item">Sản phẩm tương tự</li>
                    </ul>

                    <!-- TAB 1: MÔ TẢ -->
                    <div class="prod-tab__content prod-tab__content--current">
                        <div class="prod-content">
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>
                    </div>

                    <!-- TAB 2: ĐÁNH GIÁ -->
<div class="prod-tab__content">
    <div class="prod-content">

        <!-- FILTER SAO -->
        <div class="review-filter">
            <button class="filter-btn active" data-star="0" id="filter-all">
                Tất cả (<?= $totalReviews ?>)
            </button>

            <?php for ($s = 5; $s >= 1; $s--): ?>
                <button class="filter-btn" data-star="<?= $s ?>">
                    <?= $s ?> ★ (<?= $starCounts[$s] ?>)
                </button>
            <?php endfor; ?>
        </div>

        <!-- REVIEW LIST -->
        <div id="review-list">

            <?php if (mysqli_num_rows($reviews) === 0): ?>
                <p>Chưa có đánh giá nào cho sản phẩm này.</p>
            <?php else: ?>

                <div class="row row-cols-2 row-cols-lg-3 row-cols-md-1 gy-3">

                    <?php while ($rv = mysqli_fetch_assoc($reviews)): ?>
                        <div class="col">

                            <div class="review-card">

                                <!-- AVATAR -->
                                <img 
                                    src="./assets/img/avatar/<?= htmlspecialchars($rv['avatar'] ?? 'avatar_1.jpg') ?>"
                                    class="review-card__avatar"
                                >

                                <!-- NAME -->
                                <h4 class="review-card__title">
                                    <?= htmlspecialchars($rv['user_name']) ?>
                                </h4>

                                <!-- COMMENT -->
                                <?php if (!empty($rv['comment'])): ?>
                                    <p class="review-card__desc">
                                        <?= nl2br(htmlspecialchars($rv['comment'])) ?>
                                    </p>
                                <?php endif; ?>

                                <!-- ẢNH REVIEW -->
                                <?php if (!empty($reviewImages[$rv['id']])): ?>
                                    <div class="review-images">
                                        <?php foreach ($reviewImages[$rv['id']] as $img): ?>
                                            <img 
                                                src="<?= htmlspecialchars($img) ?>" 
                                                style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin:4px;"
                                            >
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- RATING -->
                                <div class="review-card__rating">
                                    <div class="review-card__star-list">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <img 
                                                src="./assets/icons/<?= $i <= $rv['rating'] ? 'star.svg' : 'star-blank.svg' ?>"
                                                class="review-card__star"
                                            >
                                        <?php endfor; ?>
                                    </div>
                                    <span class="review-card__rating-title">
                                        (<?= $rv['rating'] ?>/5)
                                    </span>
                                </div>

                            </div>
                        </div>
                    <?php endwhile; ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

                    <!-- TAB 3: SẢN PHẨM TƯƠNG TỰ -->
                    <div class="prod-tab__content">
                        <div class="prod-content">
                            <h2 class="prod-content__heading">Sản phẩm tương tự</h2>

                            <div class="row row-cols-6 row-cols-xl-4 row-cols-lg-3 row-cols-md-2 row-cols-sm-1 g-2">

                                <?php if (mysqli_num_rows($relatedProducts) == 0): ?>
                                    <p>Không có sản phẩm tương tự</p>
                                <?php else: ?>
                                    <?php while ($rp = mysqli_fetch_assoc($relatedProducts)): ?>
                                        <div class="col">
                                            <article class="product-card">
                                                <a href="product-detail.php?id=<?= $rp['id'] ?>">
                                                    <img
                                                        src="<?= $rp['image_url'] ?? 'default-product.png' ?>"
                                                        class="product-card__thumb"
                                                    />
                                                </a>
                                                <h3 class="product-card__title">
                                                    <?= htmlspecialchars($rp['name']) ?>
                                                </h3>
                                            </article>
                                        </div>
                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>

    <footer id="footer" class="footer"></footer>
    <script>load("#footer", "./templates/footer.php");</script>

    <div id="loginModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <h2 style="margin-bottom: 20px; color: #333; font-size: 24px; font-weight: bold;">Vui lòng đăng nhập</h2>
            <p style="margin-bottom: 30px; color: #666; font-size: 16px;">Bạn cần đăng nhập tài khoản để mua hàng</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button onclick="redirectToLogin()" style="background: #ed4337; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 500;">Đăng nhập</button>
                <button onclick="closeLoginModal()" style="background: #f0f0f0; color: #333; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 500;">Hủy</button>
            </div>
        </div>
    </div>

    <div id="toast" style="position: fixed; top: 24px; right: 24px; min-width: 320px; max-width: 420px; padding: 18px 22px; background: #333; color: #fff; border-radius: 12px; box-shadow: 0 10px 28px rgba(0,0,0,0.35); font-size: 16px; font-weight: 600; line-height: 1.4; z-index: 99999; opacity: 0; transform: translateY(-12px); transition: all .25s ease; pointer-events: none;"></div>

    <script>
        let MAX_STOCK = <?= (int)$stock_mac_dinh ?>;

        function increaseQuantity() {
            const input = document.getElementById('quantityInput');
            let current = parseInt(input.value) || 1;
            if (current < MAX_STOCK) { 
                input.value = current + 1; 
            } else { 
                alert('❌ Số lượng vượt quá tồn kho (' + MAX_STOCK + ')'); 
            }
        }

        function decreaseQuantity() {
            const input = document.getElementById('quantityInput');
            let current = parseInt(input.value) || 1;
            if (current > 1) { input.value = current - 1; }
        }
    </script>


    <script>
        const gia100 = <?= (float)$product['price'] ?>;
        const thue = <?= (int)$product['tax_percent'] ?>;

        function dinhDangGia(vnd) { 
            return vnd.toLocaleString('vi-VN') + ' ₫'; 
        }

        const weightSelect = document.getElementById('weightSelect');
        const stockText = document.getElementById('stockText');
        const qtyInput = document.getElementById('quantityInput');

        function updatePriceAndStock() {
            const gram = parseInt(weightSelect.value);
            const selectedOption = weightSelect.options[weightSelect.selectedIndex];
            const stock = parseInt(selectedOption.dataset.stock) || 0;

            // Update giá
            const gia = gia100 * (gram / 100);
            const giaSauThue = gia * (1 + thue / 100);
            document.getElementById('gia-goc').innerText = dinhDangGia(gia);
            document.getElementById('gia-sau-thue').innerText = dinhDangGia(giaSauThue);

            // Update tồn kho
            stockText.innerText = 'Còn ' + stock + ' sản phẩm';
            MAX_STOCK = stock;

            // Reset số lượng nếu vượt tồn kho
            if (parseInt(qtyInput.value) > stock) {
                qtyInput.value = stock > 0 ? 1 : 0;
            }
        }

        weightSelect.addEventListener('change', updatePriceAndStock);
    </script>

    <script>
        const thumbs = document.querySelectorAll('.prod-preview__thumb-img');
        const bigImages = document.querySelectorAll('.prod-preview__item');
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', function () {
                const index = this.dataset.index;
                bigImages.forEach(img => img.classList.remove('prod-preview__item--current'));
                thumbs.forEach(t => t.classList.remove('prod-preview__thumb-img--current'));
                bigImages[index].classList.add('prod-preview__item--current');
                this.classList.add('prod-preview__thumb-img--current');
            });
        });
    </script>

    <script>
    function initWishlist() {
        document.querySelectorAll('.like-btn[data-product-id]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
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
                    if (data.liked) { this.classList.add('like-btn--liked'); } 
                    else { this.classList.remove('like-btn--liked'); }
                    // Update số lượng hiển thị trên header
                    const countEl = document.getElementById('wishlistCount');
                    if (countEl) countEl.innerText = String(data.count).padStart(2, '0');
                });
            });
        });
    }
    // Chờ header load xong để gắn event
    const waitWishlist = setInterval(() => {
        if (document.getElementById('wishlistCount')) {
            clearInterval(waitWishlist);
            initWishlist();
        }
    }, 100);
    </script>

    <script>
    function handleAddToCart(event) {
        event.preventDefault();

        // --- ĐOẠN MỚI THÊM: Kiểm tra tồn kho ngay lập tức ---
    if (typeof MAX_STOCK !== 'undefined' && MAX_STOCK <= 0) {
        showToast('Sản phẩm này đã hết hàng!', 'error', 2000);
        return false;
    }

        var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        if (!isLoggedIn) {
            var modal = document.getElementById('loginModal');
            if (modal) modal.style.display = 'flex';
            return false;
        }

        var form = event.target.closest('form');
        var formData = new FormData(form);

        fetch('add_to_product.php', { method: 'POST', body: formData })
            .then(res => {
                return res.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);

                    if (data.success) {
                        const cartCountEl = document.getElementById('cartCount');
                        if (cartCountEl && typeof data.cart_count !== 'undefined') {
                            cartCountEl.innerText = String(data.cart_count).padStart(2, '0');
                        }
                        showToast(data.message, 'success', 1500);
                        setTimeout(() => { window.location.href = 'checkout.php'; }, 1200);
                    } else {
                // --- PHẦN SỬA LỖI (SỬA TẠI ĐÂY) ---
                
                // Chuyển thông báo về chữ thường để so sánh cho chính xác
                const msg = data.message.toLowerCase();

                // Kiểm tra xem thông báo có chứa từ khóa liên quan đến tồn kho không
                if (msg.includes('chỉ còn') || msg.includes('trong kho') || msg.includes('hết hàng')) {
                    // Nếu là lỗi tồn kho -> Hiển thị thông báo thân thiện
                    showToast('Hãy kiểm tra lại giỏ hàng của bạn', 'error');
                } else {
                    // Nếu là lỗi khác (code sai, thiếu dữ liệu...) -> Hiển thị nguyên văn để debug
                    showToast(data.message, 'error');
                }
            }

        } catch (e) {
            // Trường hợp server trả về lỗi PHP (không phải JSON)
            console.error("Lỗi phản hồi:", text);
            alert("Lỗi hệ thống: " + text); // Hiện popup để bạn dễ copy lỗi sửa
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Lỗi kết nối server', 'error');
    });
    }
    </script>
    <script>
        function showToast(message, type = 'success', duration = 2000) {
            const toast = document.getElementById('toast');
            if (!toast) return;
            toast.innerText = message;
            toast.style.background = type === 'error' ? '#d32f2f' : '#2e7d32';
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
            }, duration);
        }
    </script>

    <script>
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {

        const star = this.dataset.star;
        const productId = <?= $product_id ?>;

        // active button
        document.querySelectorAll('.filter-btn')
            .forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `ajax_filter_review=1&product_id=${productId}&star=${star}`
        })
        .then(res => res.json())
        .then(data => {

            const container = document.getElementById('review-list');

            if (!data.length) {
                container.innerHTML = '<p>Không có đánh giá phù hợp</p>';
                return;
            }

            // ✅ GRID đồng bộ với HTML mới
            let html = '<div class="row row-cols-2 row-cols-lg-3 row-cols-md-1 gy-3">';

            data.forEach(rv => {

                // ⭐ render sao
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += `<img src="./assets/icons/${i <= rv.rating ? 'star.svg' : 'star-blank.svg'}" class="review-card__star">`;
                }

                // 🖼 render ảnh review
                let images = '';
                if (rv.images && rv.images.length) {
                    images += '<div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">';
                    rv.images.forEach(img => {
                        images += `<img src="${img}" style="width:80px;height:80px;object-fit:cover;border-radius:8px">`;
                    });
                    images += '</div>';
                }

                // ✅ FIX PATH AVATAR
                const avatarPath = rv.avatar 
                    ? `./assets/img/avatar/${rv.avatar}` 
                    : './assets/img/avatar/avatar_1.jpg';

                html += `
                <div class="col">
                    <div class="review-card">

                        <img 
                            src="${avatarPath}"
                            class="review-card__avatar"
                            onerror="this.src='./assets/img/avatar/avatar_1.jpg'"
                        >

                        <h4 class="review-card__title">${rv.user_name}</h4>

                        ${rv.comment ? `<p class="review-card__desc">${rv.comment}</p>` : ''}

                        ${images}

                        <div class="review-card__rating">
                            <div class="review-card__star-list">${stars}</div>
                            <span class="review-card__rating-title">(${rv.rating}/5)</span>
                        </div>

                    </div>
                </div>`;
            });

            html += '</div>';
            container.innerHTML = html;
        });
    });
});
</script>
</body>
</html>
