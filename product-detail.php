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

// Xử lý khối lượng (weight unit)
$ds_khoi_luong = [];
if (!empty($product['weight_unit'])) {
    $ds_khoi_luong = array_map('trim', explode(',', $product['weight_unit']));
}

/** * 5. TÍNH TOÁN GIÁ CẢ BAN ĐẦU 
 */
$gia_goc = (float)$product['price'];
$thue = (int)$product['tax_percent'];
$gram_chon = isset($ds_khoi_luong[0]) ? (int)$ds_khoi_luong[0] : 100;
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
        .prod-info__price { font-size: 32px; font-weight: 700; color: #e53935; padding: 4px 8px; }
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
                                   <div class="prod-info__price" id="gia-sau-thue">
                                        <?= number_format($gia_sau_thue, 0, ',', '.') ?> ₫
                                    </div>
                                    <div class="prod-info__total-price" id="gia-goc">
                                        <?= number_format($gia_theo_gram, 0, ',', '.') ?> ₫
                                    </div>
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
                                                        <?php foreach ($ds_khoi_luong as $khoi_luong): ?>
                                                            <option value="<?= $khoi_luong ?>">
                                                                <?= $khoi_luong >= 1000 ? ($khoi_luong / 1000) . 'kg' : $khoi_luong . 'g' ?>
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
                                    <div class="text-content prod-tab__text-content"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="prod-tab__content">
                            <div class="prod-content">
                                <h2 class="prod-content__heading">What our customers are saying</h2>
                                <div class="row row-cols-3 gx-lg-2 row-cols-md-1 gy-md-3">
                                    <div class="col">
                                        <div class="review-card">
                                            <div class="review-card__content">
                                                <img src="./assets/img/avatar/avatar-1.png" alt="" class="review-card__avatar" />
                                                <div class="review-card__info">
                                                    <h4 class="review-card__title">Jakir Hussen</h4>
                                                    <p class="review-card__desc">Great product, I love this Coffee Beans</p>
                                                </div>
                                            </div>
                                            <div class="review-card__rating">
                                                <div class="review-card__star-list">
                                                    <img src="./assets/icons/star.svg" class="review-card__star" />
                                                    <img src="./assets/icons/star.svg" class="review-card__star" />
                                                    <img src="./assets/icons/star.svg" class="review-card__star" />
                                                    <img src="./assets/icons/star-half.svg" class="review-card__star" />
                                                    <img src="./assets/icons/star-blank.svg" class="review-card__star" />
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
                                                            <img src="<?= $rp['image_url'] ?? 'default-product.png' ?>" class="product-card__thumb" />
                                                        </a>
                                                    </div>
                                                    <h3 class="product-card__title">
                                                        <a href="product-detail.php?id=<?= $rp['id'] ?>"><?= htmlspecialchars($rp['name']) ?></a>
                                                    </h3>
                                                    <p class="product-card__brand"><?= htmlspecialchars($rp['brand']) ?></p>
                                                    <div class="product-card__row">
                                                        <span class="product-card__price"><?= number_format($rp['price'], 0, ',', '.') ?> ₫</span>
                                                        <img src="./assets/icons/star.svg" class="product-card__star" />
                                                        <span class="product-card__score"><?= $rp['average_score'] ?></span>
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
        const MAX_STOCK = <?= (int)$product['stock_quantity'] ?>;
        function increaseQuantity() {
            const input = document.getElementById('quantityInput');
            let current = parseInt(input.value) || 1;
            if (current < MAX_STOCK) { input.value = current + 1; } 
            else { alert('❌ Số lượng vượt quá tồn kho (' + MAX_STOCK + ')'); }
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
        function dinhDangGia(vnd) { return vnd.toLocaleString('vi-VN') + ' ₫'; }
        document.getElementById('weightSelect').addEventListener('change', function () {
            const gram = parseInt(this.value);
            const gia = gia100 * (gram / 100);
            const giaSauThue = gia * (1 + thue / 100);
            document.getElementById('gia-goc').innerText = dinhDangGia(gia);
            document.getElementById('gia-sau-thue').innerText = dinhDangGia(giaSauThue);
        });
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
        var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        if (!isLoggedIn) {
            var modal = document.getElementById('loginModal');
            if (modal) modal.style.display = 'flex';
            return false;
        }

        var form = event.target.closest('form');
        var formData = new FormData(form);

        fetch('add_to_product.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showToast(data.message, 'error', 2200);
                return;
            }
            // Cập nhật số lượng giỏ hàng trên header
            const cartCountEl = document.getElementById('cartCount');
            if (cartCountEl && typeof data.cart_count !== 'undefined') {
                cartCountEl.innerText = String(data.cart_count).padStart(2, '0');
            }
            showToast(data.message, 'success', 1500);
            setTimeout(() => { window.location.href = 'checkout.php'; }, 1200);
        })
        .catch(err => {
            showToast('Có lỗi xảy ra, vui lòng thử lại', 'error', 2000);
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
</body>
</html>