<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    return; // Nếu chưa đăng nhập thì không render phần này
}

$uid = (int)$_SESSION['user_id'];

/* ====== 1. LẤY SẢN PHẨM YÊU THÍCH ====== */
$wishlist = [];
$sql_wish = "
    SELECT 
        p.id AS product_id,
        p.name,
        p.price,
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image_url
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
    LIMIT 6
";

$stmt_wish = mysqli_prepare($conn, $sql_wish);
mysqli_stmt_bind_param($stmt_wish, "i", $uid);
mysqli_stmt_execute($stmt_wish);
$res_wish = mysqli_stmt_get_result($stmt_wish);

while ($row = mysqli_fetch_assoc($res_wish)) {
    $wishlist[] = $row;
}
$so_yeu_thich = count($wishlist);

/* ====== 2. LẤY THÔNG TIN NGƯỜI DÙNG (Tối ưu truy vấn 1 lần) ====== */
$sql_user = "SELECT full_name, email, avatar FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($conn, $sql_user);
mysqli_stmt_bind_param($stmt_user, "i", $uid);
mysqli_stmt_execute($stmt_user);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));

if (!$user) return;

// Xử lý logic hiển thị Avatar
$raw_avatar = $user['avatar'];
if (empty($raw_avatar)) {
    $display_avatar = "avatar-3.png";
} elseif (strpos($raw_avatar, 'assets/') !== false) {
    // Nếu lỡ lưu cả đường dẫn thì chỉ lấy tên file
    $display_avatar = basename($raw_avatar);
} else {
    $display_avatar = $raw_avatar;
}
?>

<div class="container">
    <div class="top-bar">
        <button class="top-bar__more d-none d-lg-block js-toggle" toggle-target="#navbar">
            <img src="./assets/icons/more.svg" alt="" class="icon top-bar__more-icon" />
        </button>

        <a href="index-logined.php" class="logo top-bar__logo">
            <img src="./assets/icons/logo.svg" alt="grocerymart" class="logo__img top-bar__logo-img" />
            <h1 class="logo__title top-bar__logo-title">Coffee Shop</h1>
        </a>

        <nav id="navbar" class="navbar hide">
            <button class="navbar__close-btn js-toggle" toggle-target="#navbar">
                <img class="icon" src="./assets/icons/arrow-left.svg" alt="" />
            </button>

            <a href="./checkout.php" class="nav-btn d-none d-md-flex">
                <img src="./assets/icons/buy.svg" alt="" class="nav-btn__icon icon" />
                <span class="nav-btn__title">Cart</span>
                <span class="nav-btn__qnt">0</span> </a>

            <a href="./favourite.php" class="nav-btn d-none d-md-flex">
                <img src="./assets/icons/heart.svg" alt="" class="nav-btn__icon icon" />
                <span class="nav-btn__title">Favorite</span>
                <span class="nav-btn__qnt"><?= $so_yeu_thich ?></span>
            </a>

            <ul class="navbar__list js-dropdown-list">
                 <li class="navbar__item">
                    <a href="#!" class="navbar__link">
                        Coffee <img src="./assets/icons/arrow-down.svg" alt="" class="icon navbar__arrow" />
                    </a>
                    </li>
            </ul>
        </nav>

        <div class="navbar__overlay js-toggle" toggle-target="#navbar"></div>

        <div class="top-act">
            <div class="search-box">
                <form action="index-logined.php" method="GET" class="search-box">
                    <input type="text" name="keyword" class="search-input" placeholder="Tìm kiếm ..." value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
                    <button type="submit" class="search-btn">
                        <img src="./assets/icons/search.svg" alt="" class="icon" />
                    </button>
                </form>
            </div>

            <div class="top-act__group d-md-none">
                <div class="top-act__btn-wrap">
                    <button class="top-act__btn" id="wishlistBtn">
                        <img src="./assets/icons/heart.svg" alt="" class="icon top-act__icon" />
                        <span class="top-act__title" id="wishlistCount">
                            <span><?= str_pad($so_yeu_thich, 2, '0', STR_PAD_LEFT) ?></span>
                        </span>
                    </button>

                    <div class="act-dropdown">
                        <div class="act-dropdown__inner">
                            <img src="./assets/icons/arrow-up.png" alt="" class="act-dropdown__arrow" />
                            <div class="act-dropdown__top">
                                <h3 class="act-dropdown__title">Yêu thích</h3>
                                <a href="./favourite.php" class="act-dropdown__view-all">Tất cả</a>
                            </div>
                            <div class="row row-cols-3 gx-2 act-dropdown__list">
                                <?php if (empty($wishlist)): ?>
                                    <p style="padding: 15px; font-size: 13px; text-align: center; width: 100%;">Trống</p>
                                <?php else: ?>
                                    <?php foreach ($wishlist as $item): ?>
                                        <div class="col">
                                            <article class="cart-preview-item">
                                                <div class="cart-preview-item__img-wrap">
                                                    <img src="./assets/img/product/<?= htmlspecialchars($item['image_url'] ?? 'item-1.png') ?>" 
                                                         class="cart-preview-item__thumb" alt="" 
                                                         onerror="this.src='./assets/img/product/item-1.png'"/>
                                                </div>
                                                <h3 class="cart-preview-item__title"><?= htmlspecialchars($item['name']) ?></h3>
                                                <p class="cart-preview-item__price"><?= number_format($item['price'], 0, ',', '.') ?> ₫</p>
                                            </article>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="top-act__separate"></div>

                <div class="top-act__btn-wrap">
                    <a href="./checkout.php" class="top-act__btn">
                        <img src="./assets/icons/buy.svg" alt="" class="icon top-act__icon">
                        <span class="top-act__title">Cart</span> 
                    </a>
                </div>
            </div>

            <div class="top-act__user">
                <img src="./assets/img/avatar/<?= htmlspecialchars($display_avatar) ?>" 
                     alt="Avatar" class="top-act__avatar" 
                     onerror="this.src='./assets/img/avatar/avatar-3.png'" />

                <div class="act-dropdown top-act__dropdown">
                    <div class="act-dropdown__inner user-menu">
                        <img src="./assets/icons/arrow-up.png" alt="" class="act-dropdown__arrow top-act__dropdown-arrow" />

                        <div class="user-menu__top">
                            <img src="./assets/img/avatar/<?= htmlspecialchars($display_avatar) ?>" 
                                 alt="Avatar" class="user-menu__avatar" 
                                 onerror="this.src='./assets/img/avatar/avatar-3.png'"/>
                            <div class="header-user__info">
                                <p class="header-user__name"><?= htmlspecialchars($user['full_name']) ?></p>
                                <p class="header-user__email"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                        </div>

                        <ul class="user-menu__list">
                            <li><a href="./profile.php" class="user-menu__link">Profile</a></li>
                            <li><a href="./favourite.php" class="user-menu__link">Favourite list</a></li>
                            <li class="user-menu__separate">
                                <a href="#!" class="user-menu__link" id="switch-theme-btn">
                                    <span>Dark mode</span>
                                    <img src="./assets/icons/sun.svg" alt="" class="icon user-menu__icon" />
                                </a>
                            </li>
                            <li><a href="#!" class="user-menu__link">Settings</a></li>
                            <li class="user-menu__separate"><a href="./logout.php" class="user-menu__link">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>