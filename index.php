<?php
session_start();
require_once 'db_connect.php';

// --- 1. CẤU HÌNH PHÂN TRANG (Thêm mới) ---
$limit = 10; // Số sản phẩm hiển thị trên 1 trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- 2. KIỂM TRA ĐĂNG NHẬP (Giữ nguyên) ---
if (isset($_SESSION['user_id']) && (!isset($_SESSION['is_logged_out']) || $_SESSION['is_logged_out'] === false)) {
    $_SESSION['message'] = "Bạn muốn tiếp tục sử dụng tài khoản này không?";
    header("Location: index-logined.php");
    exit;
}

// --- 3. LẤY DỮ LIỆU TỪ URL (Giữ nguyên) ---
$keyword      = trim($_GET['keyword'] ?? '');
$min_price    = $_GET['min_price'] ?? '';
$max_price    = $_GET['max_price'] ?? '';
$weight       = $_GET['weight'] ?? '';
$brand_filter = trim($_GET['brand_filter'] ?? '');

$isSearching = !empty($keyword);
$isFiltering = (!empty($min_price) || !empty($max_price) || !empty($weight) || !empty($brand_filter));

// --- 4. SQL CƠ BẢN (Dùng bảng product_images để lấy ảnh) ---
$sql = "SELECT p.*, pi.image_url AS image 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE 1=1";
$params = [];
$types = "";

// --- 5. ÁP DỤNG BỘ LỌC (Giữ nguyên) ---
if ($isSearching) {
    $sql .= " AND (REPLACE(p.name, ' ', '') LIKE ? OR REPLACE(p.brand, ' ', '') LIKE ?)";
    $clean_keyword = "%" . str_replace(' ', '', $keyword) . "%";
    $params[] = $clean_keyword; 
    $params[] = $clean_keyword;
    $types .= "ss";
}
if ($min_price !== '') {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price; $types .= "d";
}
if ($max_price !== '') {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price; $types .= "d";
}
if (!empty($weight)) {
    $sql .= " AND p.weight_unit = ?";
    $params[] = $weight; $types .= "s";
}
if (!empty($brand_filter)) {
    $sql .= " AND REPLACE(p.brand, ' ', '') LIKE ?";
    $clean_brand = "%" . str_replace(' ', '', $brand_filter) . "%";
    $params[] = $clean_brand; 
    $types .= "s";
}

// --- 6. TÍNH TỔNG SỐ TRANG (Logic mới thêm vào) ---
// Thay thế đoạn SELECT để đếm tổng số dòng
$sql_count = str_replace("SELECT p.*, pi.image_url AS image", "SELECT COUNT(*) as total", $sql);

$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$row_count = $count_result->fetch_assoc();
$total_records = $row_count['total']; // Tổng số kết quả
$total_page = ceil($total_records / $limit); // Tổng số trang
$stmt_count->close();

// --- 7. THÊM LIMIT & OFFSET ĐỂ LẤY DỮ LIỆU TRANG HIỆN TẠI ---
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$productList = $result;

// --- 8. THÔNG BÁO ---
$message = "";
if ($total_records > 0) {
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
    // Không tìm thấy -> Gợi ý ngẫu nhiên
    $total_page = 0; // Ẩn phân trang
    $sqlRandom = "SELECT p.*, pi.image_url AS image 
                  FROM products p 
                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                  ORDER BY RAND() LIMIT 4";
    $productList = $conn->query($sqlRandom);
    
    if ($isSearching) {
        $message = "Không tìm thấy sản phẩm nào cho từ khóa '" . htmlspecialchars($keyword) . "'. Gợi ý cho bạn:";
    } else {
        $message = "Không có sản phẩm nào khớp bộ lọc. Gợi ý cho bạn:";
    }
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
        <script>
            load("#header", "./templates/header.php");
        </script>

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
                                        <label for="" class="form__label">Giá bán</label>
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
                                        <label for="" class="form__label">Trọng lượng</label>
                                        <div class="filter__form-group">
                                            <div class="form__select-wrap">
                                                <select name="weight" class="form__select" style="--width: 158px;">
                                                    <option value="">Tất cả</option>
                                                    <option value="100g" <?php if($weight == '100g') echo 'selected'; ?>>100g</option>
                                                    <option value="500g" <?php if($weight == '500g') echo 'selected'; ?>>500g</option>
                                                    <option value="1kg"  <?php if($weight == '1kg') echo 'selected'; ?>>1kg</option>
                                                    <option value="2kg"  <?php if($weight == '2kg') echo 'selected'; ?>>2kg</option>
                                                    <option value="3kg"  <?php if($weight == '3kg') echo 'selected'; ?>>3kg</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="filter__separate"></div>
                                    <div class="filter__col">
                                        <label for="" class="form__label">Thương hiệu</label>
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
                                    <button class="like-btn product-card__like-btn">
                                        <img src="./assets/icons/heart.svg" alt="" class="like-btn__icon icon" />
                                    </button>
                                </div>
                                
                                <h3 class="product-card__title">
                                    <a href="./product-detail.php?id=<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </a>
                                </h3>
                                <p class="product-card__brand">
                                    <?php echo htmlspecialchars($row['brand']); ?>
                                </p>
                                <p class="product-card__weight" style="font-size: 1.2rem; color: #777;">
                                    Trọng lượng: <?php echo htmlspecialchars($row['weight_unit']); ?>
                                </p>
                                <div class="product-card__row">
                                    <span class="product-card__price">
                                        <?php echo number_format($row['price'], 0, ',', '.'); ?> VNĐ
                                    </span>
                                    <img src="./assets/icons/star.svg" alt="" class="product-card__star" />
                                    <span class="product-card__score"><?php echo number_format($row['average_score'], 1); ?></span>
                                </div>
                            </article>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 d-flex justify-content-center">
                    <?php include "./panigation.php"; ?>
                </div>

        </main>

        <footer id="footer" class="footer"></footer>
        <script>
            load("#footer", "./templates/footer.php");
        </script>
        <script>
            const searchBox = document.querySelector(".search-box");
            const searchBtn = document.querySelector(".search-btn");

            searchBtn.addEventListener("click", function (e) {
                if (!searchBox.classList.contains("active")) {
                    e.preventDefault(); 
                    searchBox.classList.add("active");
                    searchBox.querySelector(".search-input").focus();
                }
            });
        </script>
    </body>
</html>