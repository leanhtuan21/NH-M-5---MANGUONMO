<?php
session_start();
require_once 'db_connect.php';

// --- PHẦN 1: KIỂM SOÁT ĐĂNG NHẬP & THÔNG BÁO (GỘP VÀO) ---
if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit;
}

if (isset($_SESSION['message'])) {
    $msg = addslashes($_SESSION['message']);
    echo "<script>
        if (confirm('$msg')) {
            // OK
        } else {
            window.location.href = 'logout.php?redirect=index.php';
        }
    </script>";
    unset($_SESSION['message']);
}

// --- PHẦN 2: CẤU HÌNH PHÂN TRANG (GỘP VÀO) ---
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- GIỮ NGUYÊN CÁC BIẾN LẤY TỪ URL ---
$keyword      = trim($_GET['keyword'] ?? '');
$min_price    = $_GET['min_price'] ?? '';
$max_price    = $_GET['max_price'] ?? '';
$weight       = $_GET['weight'] ?? '';
$brand_filter = trim($_GET['brand_filter'] ?? '');

/* === LẤY DANH SÁCH WISHLIST CỦA USER === */
$likedMap = [];
$uid = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT product_id FROM wishlists WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $likedMap[$row['product_id']] = true;
}

$isSearching = !empty($keyword);
$isFiltering = (!empty($min_price) || !empty($max_price) || !empty($weight) || !empty($brand_filter));

// --- XÂY DỰNG SQL (GIỮ NGUYÊN LOGIC REPLACE VÀ LIKE CỦA BẠN) ---
$sql_base = " FROM products p 
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
              WHERE 1=1";
$params = [];
$types = "";

if ($isSearching) {
    $sql_base .= " AND (REPLACE(p.name, ' ', '') LIKE ? OR REPLACE(p.brand, ' ', '') LIKE ?)";
    $clean_keyword = "%" . str_replace(' ', '', $keyword) . "%";
    $params[] = $clean_keyword; 
    $params[] = $clean_keyword;
    $types .= "ss";
}

if ($min_price !== '') {
    $sql_base .= " AND p.price >= ?";
    $params[] = $min_price; $types .= "d";
}
if ($max_price !== '') {
    $sql_base .= " AND p.price <= ?";
    $params[] = $max_price; $types .= "d";
}
if (!empty($weight)) {
    $sql_base .= " AND p.weight_unit LIKE ?"; 
    $params[] = "%" . $weight . "%"; 
    $types .= "s";
}
if (!empty($brand_filter)) {
    $sql_base .= " AND REPLACE(p.brand, ' ', '') LIKE ?";
    $clean_brand = "%" . str_replace(' ', '', $brand_filter) . "%";
    $params[] = $clean_brand; 
    $types .= "s";
}

// --- TÍNH TỔNG SỐ TRANG ĐỂ PHÂN TRANG (GỘP VÀO) ---
$stmt_count = $conn->prepare("SELECT COUNT(*) as total " . $sql_base);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_page = ceil($total_records / $limit);

// --- TRUY VẤN CHÍNH CÓ LIMIT OFFSET (GỘP VÀO) ---
$sql_main = "SELECT p.*, pi.image_url AS image " . $sql_base . " LIMIT ? OFFSET ?";
$stmt_main = $conn->prepare($sql_main);
$final_params = array_merge($params, [$limit, $offset]);
$final_types = $types . "ii";
$stmt_main->bind_param($final_types, ...$final_params);
$stmt_main->execute();
$result = $stmt_main->get_result();

$message = "";
if ($result->num_rows > 0) {
    $productList = $result;
    if ($isSearching && $isFiltering) {
        $message = "Tìm thấy " . $total_records . " sản phẩm cho từ khóa <strong>'" . htmlspecialchars($keyword) . "'</strong> kèm bộ lọc";
    } elseif ($isSearching) {
        $message = "Kết quả tìm kiếm cho: <strong>'" . htmlspecialchars($keyword) . "'</strong> (" . $total_records . " sản phẩm)";
    } elseif ($isFiltering) {
        $message = "Tìm thấy " . $total_records . " sản phẩm theo bộ lọc";
    } else {
        $message = "Tất cả sản phẩm";
    }
} else {
    $total_page = 0;
    $productList = $conn->query("SELECT p.*, pi.image_url AS image FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 ORDER BY RAND() LIMIT 4");
    if ($isSearching) { $message = "Không tìm thấy sản phẩm nào cho từ khóa '" . htmlspecialchars($keyword) . "'. Gợi ý cho bạn:"; } 
    else { $message = "Không có sản phẩm nào khớp bộ lọc. Gợi ý cho bạn:"; }
}

/* === XỬ LÝ AJAX WISHLIST (GIỮ NGUYÊN TOÀN BỘ) === */
if (isset($_POST['ajax_wishlist'])) {
    $uid = $_SESSION['user_id'];
    $product_id = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($product_id > 0) {
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
    }
    $items = [];
    $stmt = mysqli_prepare($conn, "SELECT p.id, p.name, p.price, pi.image_url FROM wishlists w JOIN products p ON w.product_id = p.id LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 WHERE w.user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $items[] = ['id' => $row['id'], 'name' => $row['name'], 'price' => $row['price'], 'image' => $row['image_url'] ?? 'default-product.png'];
    }
    echo json_encode(['status' => 'ok', 'count' => count($items), 'items' => $items]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Grocery Mart</title>
        <link rel="apple-touch-icon" sizes="76x76" href="./assets/favicon/apple-touch-icon.png" />
        <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon/favicon-32x32.png" />
        <link rel="icon" type="image/png" sizes="16x16" href="./assets/favicon/favicon-16x16.png" />
        <link rel="manifest" href="./assets/favicon/site.webmanifest" />
        <meta name="msapplication-TileColor" content="#da532c" />
        <meta name="theme-color" content="#ffffff" />
        <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />
        <link rel="stylesheet" href="./assets/css/main.css" />
        <link rel="stylesheet" href="./assets/css/panagition.css" />
        <script src="./assets/js/scripts.js"></script>
        <style>
            .search-box { position: relative; }
            .search-input { width: 0; opacity: 0; transition: 0.3s; padding: 6px 10px; }
            .search-box.active .search-input { width: 200px; opacity: 1; }
        </style>
    </head>
    <body>
        <header id="header" class="header"></header>
        <script>load("#header", "./templates/header-logined.php");</script>

        <main class="container home">
            <div class="home__container">
                <div class="slideshow">
                    <div class="slideshow__inner">
                        <div class="slideshow__item">
                            <a href="#!" class="slideshow__link">
                                <picture>
                                    <source media="(max-width: 767.98px)" srcset="./assets/img/slideshow/item-1-md.png" />
                                    <img src="./assets/img/slideshow/item-1.png" alt="" class="slideshow__img" />
                                </picture>
                            </a>
                        </div>
                    </div>
                    <div class="slideshow__page">
                        <span class="slideshow__num">1</span>
                        <span class="slideshow__slider"></span>
                        <span class="slideshow__num">5</span>
                    </div>
                </div>
            </div>

            <section class="home__container">
                <div class="home__row">
                    <h2 class="home__heading"><?php echo $message; ?></h2>
                    <div class="filter-wrap">
                        <button class="filter-btn js-toggle" toggle-target="#home-filter">
                            Lọc
                            <img src="./assets/icons/filter.svg" alt="" class="filter-btn__icon icon" />
                        </button>

                        <div id="home-filter" class="filter hide">
                            <img src="./assets/icons/arrow-up.png" alt="" class="filter__arrow" />
                            <h3 class="filter__heading">Bộ lọc</h3>
                            <form action="" method="GET" class="filter__form form">
                                <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
                                <div class="filter__row filter__content">
                                    <div class="filter__col">
                                        <label class="form__label">Giá bán</label>
                                        <div class="filter__form-group">
                                            <div class="filter__form-slider" style="--min-value: 10%; --max-value: 60%"></div>
                                        </div>
                                        <div class="filter__form-group filter__form-group--inline">
                                            <div>
                                                <label class="form__label form__label--small">Thấp nhất</label>
                                                <div class="filter__form-text-input filter__form-text-input--small">
                                                    <input type="number" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" class="filter__form-input" placeholder="0" />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form__label form__label--small">Cao nhất</label>
                                                <div class="filter__form-text-input filter__form-text-input--small">
                                                    <input type="number" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" class="filter__form-input" placeholder="1000" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="filter__separate"></div>

                                    <div class="filter__col">
                                        <label class="form__label">Trọng lượng</label>
                                        <div class="filter__form-group">
                                            <div class="form__select-wrap">
                                                <select name="weight" class="form__select" style="--width: 158px;">
                                                    <option value="">Tất cả</option>
                                                    <option value="100" <?= ($weight == '100') ? 'selected' : '' ?>>100g</option>
                                                    <option value="200" <?= ($weight == '200') ? 'selected' : '' ?>>200g</option>
                                                    <option value="300" <?= ($weight == '300') ? 'selected' : '' ?>>300g</option>
                                                    <option value="400" <?= ($weight == '400') ? 'selected' : '' ?>>400g</option>
                                                    <option value="500" <?= ($weight == '500') ? 'selected' : '' ?>>500g</option>
                                                    <option value="1000" <?= ($weight == '1000') ? 'selected' : '' ?>>1kg</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="filter__separate"></div>

                                    <div class="filter__col">
                                        <label class="form__label">Thương hiệu</label>
                                        <div class="filter__form-group">
                                            <div class="filter__form-text-input">
                                                <input type="text" name="brand_filter" value="<?php echo htmlspecialchars($brand_filter); ?>" placeholder="Nhập tên hãng..." class="filter__form-input" />
                                                <img src="./assets/icons/search.svg" alt="" class="filter__form-input-icon icon" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="filter__row filter__footer">
                                    <button class="btn btn--text filter__cancel js-toggle" toggle-target="#home-filter">Huỷ bỏ</button>
                                    <button type="submit" class="btn btn--primary filter__submit">Hiển thị</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row row-cols-5 row-cols-lg-2 row-cols-sm-1 g-3">
                    <?php if ($productList && $productList->num_rows > 0): ?>
                        <?php while($row = $productList->fetch_assoc()): ?>
                            <div class="col">
                                <article class="product-card">
                                    <div class="product-card__img-wrap">
                                        <a href="./product-detail.php?id=<?php echo $row['id']; ?>">
                                            <img src="<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-card__thumb" />
                                        </a>
                                        <button type="button" class="like-btn product-card__like-btn <?= isset($likedMap[$row['id']]) ? 'like-btn--liked' : '' ?>" data-product-id="<?= $row['id'] ?>">
                                            <img src="./assets/icons/heart.svg" alt="" class="like-btn__icon icon" />
                                            <img src="./assets/icons/heart-red.svg" alt="" class="like-btn__icon--liked" />
                                        </button>
                                    </div>
                                    <h3 class="product-card__title">
                                        <a href="./product-detail.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></a>
                                    </h3>
                                    <p class="product-card__brand"><?php echo htmlspecialchars($row['brand']); ?></p>
                                    
                                    <div class="product-card__row">
                                        <span class="product-card__price"><?php echo number_format($row['price'], 0, ',', '.'); ?> Đ</span>
                                        <img src="./assets/icons/star.svg" alt="" class="product-card__star" />
                                        <span class="product-card__score"><?php echo number_format($row['average_score'], 1); ?></span>
                                    </div>
                                </article>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_page > 1): ?>
                    <div class="mt-4 d-flex justify-content-center">
                        <?php include "./panigation.php"; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <footer id="footer" class="footer"></footer>
        <script>load("#footer", "./templates/footer.php");</script>

        <script>
            const searchBox = document.querySelector(".search-box");
            const searchBtn = document.querySelector(".search-btn");
            if (searchBtn) {
                searchBtn.addEventListener("click", function (e) {
                    if (!searchBox.classList.contains("active")) {
                        e.preventDefault();
                        searchBox.classList.add("active");
                        searchBox.querySelector(".search-input").focus();
                    }
                });
            }
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.like-btn[data-product-id]');
                if (!btn) return;
                e.preventDefault(); e.stopPropagation();
                const productId = btn.dataset.productId;
                const liked = btn.classList.toggle('like-btn--liked');
                const action = liked ? 'add' : 'remove';
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `ajax_wishlist=1&product_id=${productId}&action=${action}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status !== 'ok') return;
                    const countEl = document.getElementById('wishlistCount');
                    if (countEl) { countEl.innerText = String(data.count).padStart(2, '0'); }
                    const list = document.querySelector('.act-dropdown__list');
                    if (!list) return;
                    list.innerHTML = '';
                    if (data.items.length === 0) {
                        list.innerHTML = '<p style="padding:12px">Chưa có sản phẩm yêu thích</p>';
                        return;
                    }
                    data.items.forEach(item => {
                        list.insertAdjacentHTML('beforeend', `
                            <div class="col">
                                <article class="cart-preview-item">
                                    <div class="cart-preview-item__img-wrap">
                                        <img src="${item.image}" class="cart-preview-item__thumb">
                                    </div>
                                    <h3 class="cart-preview-item__title">${item.name}</h3>
                                    <p class="cart-preview-item__price">${Number(item.price).toLocaleString('vi-VN')} ₫</p>
                                </article>
                            </div>
                        `);
                    });
                });
            });
        </script>
    </body>
</html>