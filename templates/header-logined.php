<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit;
}
/* ====== LẤY SẢN PHẨM YÊU THÍCH TỪ CSDL ====== */
$uid = $_SESSION['user_id'];

$wishlist = [];
$so_yeu_thich = 0;

$sql = "
    SELECT 
        p.id AS product_id,
        p.name,
        p.price,
        pi.image_url
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN product_images pi 
        ON p.id = pi.product_id AND pi.is_main = 1
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
    LIMIT 6
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($res)) {
    $wishlist[] = [
        'id'    => $row['product_id'],
        'name'  => $row['name'],
        'price' => $row['price'],
        'image' => $row['image_url']
    ];
}

$so_yeu_thich = count($wishlist);

/* ========ảnh đại diện=========== */
$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT full_name, email, avatar FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

if (!$user) {
    return; // tránh lỗi
}
$avatar = (!empty($user['avatar'])) ? $user['avatar'] : 'avatar.png';
    //ẢNH ĐẠI DIỆN 
    $email = $_SESSION['email'] ?? null;

    $avatar = 'avatar-3.png';
    $user_name = 'User';

    if ($email) {
        $sql = "SELECT email, full_name, avatar FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            $user_name = $user['full_name'];
            // Nếu có avatar và file tồn tại thì dùng
            if (!empty($user['avatar']) &&
                file_exists(__DIR__ . '/../assets/img/avatar/' . $user['avatar'])
            ) {
                $avatar = $user['avatar'];
            }
        }
    }
    //
?>

<div class="container">
    <div class="top-bar">
        <!-- More -->
        <button class="top-bar__more d-none d-lg-block js-toggle" toggle-target="#navbar">
            <img src="./assets/icons/more.svg" alt="" class="icon top-bar__more-icon" />
        </button>

        <!-- Logo -->
        <a href="./" class="logo top-bar__logo">
            <img src="./assets/icons/logo.svg" alt="grocerymart" class="logo__img top-bar__logo-img" />
            <h1 class="logo__title top-bar__logo-title">Coffee Shop</h1>
        </a>

        <!-- Navbar -->
        <nav id="navbar" class="navbar hide">
            <button class="navbar__close-btn js-toggle" toggle-target="#navbar">
                <img class="icon" src="./assets/icons/arrow-left.svg" alt="" />
            </button>

            <a href="./checkout.php" class="nav-btn d-none d-md-flex">
                <img src="./assets/icons/buy.svg" alt="" class="nav-btn__icon icon" />
                <span class="nav-btn__title">Cart</span>
                <span class="nav-btn__qnt">3</span>
            </a>

            <a href="#!" class="nav-btn d-none d-md-flex">
                <img src="./assets/icons/heart.svg" alt="" class="nav-btn__icon icon" />
                <span class="nav-btn__title">Favorite</span>
                <span class="nav-btn__qnt">3</span>
            </a>

            <ul class="navbar__list js-dropdown-list">
                <li class="navbar__item">
                    <a href="#!" class="navbar__link">
                        Coffee
                        <img src="./assets/icons/arrow-down.svg" alt="" class="icon navbar__arrow" />
                    </a>
                    <div class="dropdown js-dropdown">
                        <div class="dropdown__inner">
                            <div class="top-menu">
                                <div class="top-menu__main">
                                    <!-- Menu column -->
                                    <div class="menu-column">
                                        <div class="menu-column__icon d-lg-none">
                                            <img
                                                src="./assets/img/category/cate-1.1.svg"
                                                alt=""
                                                class="menu-column__icon-1"
                                            />
                                            <img
                                                src="./assets/img/category/cate-1.2.svg"
                                                alt=""
                                                class="menu-column__icon-2"
                                            />
                                        </div>
                                        <div class="menu-column__content">
                                            <h2 class="menu-column__heading d-lg-none">Tìm kiếm chung</h2>
                                            <ul class="menu-column__list js-menu-list">
                                                <li class="menu-column__item">
                                                    <a href="#!" class="menu-column__link">
                                                        Loại Coffee
                                                    </a>
                                                    <!-- Sub menu for "Loại Coffee" -->
                                                    <div class="sub-menu">
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-4.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-13.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!"> Coffee rang hạt  </a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Hạt Robusta
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Hạt Mix
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Hạt Arabica
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Hạt Typica
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Hạt Bourbon
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <!-- Menu column 2 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-2.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-14.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Coffee viên nén</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Dolce Gusto pods
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Nespresso Compatible Coffee Pods Mixed
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            K-Cups (Keurig)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            L'Or Espresso Sublime Rosé capsules
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Purio Coffee
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-1.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-11.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Coffee bột (xay sẵn)</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cà Phê Rang PREMIUM ROBUSTA
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Thom Coffee Blend
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cà Phê Bột Robusta
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            TRUNG NGUYÊN – Cà Phê Rang Xay Sáng Tạo 5 Arabica Ground Coffee
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Early Morning Robusta Coffee
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <!-- Menu column 2 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-5.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-16.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Coffee đặc sản</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Ethiopia Specialty
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Geisha (Gesha) – Panama
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Colombia Specialty
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Brazil Specialty
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Kenya AA
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-6.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-13.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Coffee hoà tan </a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            G7 3‑in‑1 Instant Coffee
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Nescafé Taster’s Choice French Roast
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Maxwell House Soluble Coffee
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Folgers Classic Roast Instant Coffee Crys
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Taster’s Choice Instant Coffee
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>

                                                <li class="menu-column__item">
                                                    <a href="#!" class="menu-column__link">Công cụ , dụng cụ pha Coffee</a>
                                                    <!-- Sub menu for "Công cụ , dụng cụ pha Coffee" -->
                                                    <div class="sub-menu">
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-2.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Pha truyền thống </a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Phin cà phê
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Ấm đun nước
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <!-- Menu column 2 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-3.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Pha thủ công (Hand Brew)</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Dripper (phễu lọc)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Giấy lọc cà phê
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bình rót cổ ngỗng (Gooseneck Kettle), Server / Bình đựng cà phê
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <!-- Menu column 3 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-6.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Bảo quản và phụ kiện</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Hũ / hộp đựng cà phê chuyên dụng
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Khay đựng / lọ chia liều (Dose Container)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Ngăn bảo quản khô – mát
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Muỗng đong – thìa khuấy
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Ly – tách cà phê
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Thẻ ghi chú / nhãn dán
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-4.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Xay và định lượng</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Máy xay cà phê điện
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cân điện tử
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Muỗng đong cà phê
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cốc đong nước
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Portafilter dosing cup (cốc đong bột)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Hộp đựng hạt
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-5.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Dụng cụ hỗ trợ</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Tamper (dụng cụ nén cà phê)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            WDT Tool (kim phá vón cà phê)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Khăn lau pha chế
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bàn chải vệ sinh
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Hộp gõ bã cà phê (Knock Box)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Nhiệt kế , Đồng hồ bấm giờ
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>

                                                <li class="menu-column__item">
                                                    <a href="#!" class="menu-column__link">
                                                        Máy pha chế Coffee
                                                    </a>
                                                    <!-- Sub menu for "Máy pha chế Coffee" -->
                                                    <div class="sub-menu">
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-5.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Máy Espresso</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Máy Espresso thủ công
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Máy Espresso tự động
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Máy Single Boiler
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Máy Dual Boiler
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Máy Espresso có PID
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <!-- Menu column 2 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-4.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Máy viên nén</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Máy nén cà phê tự động M‑Line Q (Gen 6) PUQPRESS
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Starseeker Zero – Máy nén cà phê tự động
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Dụng cụ phân bổ & nén cà phê Tamper OCD
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Dụng cụ nén cà phê cao cấp Tamper 49mm
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-3.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Máy pha gia đình </a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Philips 2200 series EP2220/10
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Melitta Caffeo Passione OT
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Winci EM58 Espresso Machine
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            SMEG Espresso Machine EGF03
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <!-- Menu column 2 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-2.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Máy pha cho quán Coffee</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Rancilio Classe 5 USB Automatic 2 Group
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Victoria Arduino Eagle One
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Saeco AREA FOCUS Espresso Machine
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Kalerm K95LT Automatic Coffee Machine
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-6.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-7.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Máy pha tự động</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Delonghi EC9155.MB Automatic Coffee Machine
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Panasonic Máy pha cà phê tự động
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Delonghi ECP33.21 Espresso Machine
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>

                                                <li class="menu-column__item">
                                                    <a href="#!" class="menu-column__link">
                                                    Bánh ngọt ăn kèm Coffee
                                                    </a>
                                                    <!-- Sub menu for "Bánh ngọt ăn kèm cà phê" -->
                                                    <div class="sub-menu">
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-6.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-8.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Nhóm Bánh ngàn lớp và bơ </a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bánh sừng bò (Croissant)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bánh ngàn lớp (Mille-feuille)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bánh ngàn lớp nhân mứt hoa quả khô
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bánh hoa sen ngàn lớp
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bánh Crepe Dâu tây ngàn lớp 
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-4.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-12.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Nhóm Bánh nướng </a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cookies (bánh quy)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bánh hạnh nhân nướng
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Shortbread
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bánh táo nướng
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Pie nướng
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-3.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-10.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Nhóm Bánh kem và Bánh mềm</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Tiramisu
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bánh su kem (Choux cream)
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cupcake kem
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Mousse cake
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cheesecake
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>

                                                <li class="menu-column__item">
                                                    <a href="#!" class="menu-column__link">Đối tượng sử dụng</a>
                                                    <!-- Sub menu for "Đối tượng sử dụng" -->
                                                    <div class="sub-menu">
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-2.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-19.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Nhân viên văn phòng - Học sinh/Sinh viên </a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Latte
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cappuccino
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Mocha
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Caramel latte
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Vanilla latte
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <!-- Menu column 2 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-4.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-19.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Người sành coffee</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Espresso
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Long Black
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Micro-lot coffee
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Specialty Coffee
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Nitro Cold Brew
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-3.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-19.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Giới trẻ/Check in</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Latte nghệ thuật
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Caramel đá xay
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cold brew cam / chanh
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Coffee smoothie
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Bạc xỉu
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <!-- Menu column 2 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-5.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-19.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Người lớn tuổi</a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cà phê phin đen nóng
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cà phê rang mộc
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cà phê đậu nành
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cà phê hoà tan không đường
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Arabica rang nhạt
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="sub-menu__column">
                                                            <!-- Menu column 1 -->
                                                            <div class="menu-column">
                                                                <div class="menu-column__icon">
                                                                    <img
                                                                        src="./assets/img/category/cate-6.1.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-1"
                                                                    />
                                                                    <img
                                                                        src="./assets/img/category/cate-19.2.svg"
                                                                        alt=""
                                                                        class="menu-column__icon-2"
                                                                    />
                                                                </div>
                                                                <div class="menu-column__content">
                                                                    <h2 class="menu-column__heading">
                                                                        <a href="#!">Hiện đại và Năng động </a>
                                                                    </h2>
                                                                    <ul class="menu-column__list">
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cà phê capsule không đường
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cold brew trái cây nhẹ
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cold brew chai
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Espresso shot
                                                                            </a>
                                                                        </li>
                                                                        <li class="menu-column__item">
                                                                            <a href="#!" class="menu-column__link">
                                                                            Cà phê pha sẵn
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="navbar__item">
                    <a href="#!" class="navbar__link">
                        Công thức pha chế
                        <img src="./assets/icons/arrow-down.svg" alt="" class="icon navbar__arrow" />
                    </a>
                    <div class="dropdown js-dropdown">
                        <div class="dropdown__inner">
                            <div class="top-menu">
                                <div class="sub-menu sub-menu--not-main">
                                    <div class="sub-menu__column">
                                        <!-- Menu column 1 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-7.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-16.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Coffee sữa </a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Nguyên liệu : 1 phin cà phê đen , 25–30ml sữa đặc</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Cách pha : Pha cà phê phin → khuấy sữa → đổ ra ly đá</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <!-- Menu column 2 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-8.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-8.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Iced Latte</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Nguyên liệu : 1 shot espresso , 150ml sữa tươi lạnh , Đá viên .</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Cách pha : Pha một shot espresso -> sau đó đổ sữa tươi lạnh vào ly và thêm đá viên -> Khuấy nhẹ và thưởng thức.</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sub-menu__column">
                                        <!-- Menu column 1 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-9.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-9.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Dalgona Latte</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Nguyên liệu : 2 muỗng cà phê hòa tan , 2 muỗng đường , 2 muỗng nước nóng , 150ml sữa tươi lạnh , Đá viên .  </a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Cách pha : Đánh bông cà phê hòa tan, đường và nước nóng cho đến khi hỗn hợp mịn -> Đổ sữa tươi vào ly, thêm đá và múc lớp cà phê bông lên trên.</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <!-- Menu column 2 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-10.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-10.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Capuchino</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Nguyên liệu : 1 shot cà phê đậm , 120ml sữa tươi không đường , Đường (tuỳ thích) , Bột cacao / bột quế (tuỳ chọn) .</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Cách pha : Pha 1 shot cà phê đậm (espresso hoặc phin đặc) -> Hâm nóng sữa, đánh bọt mịn -> Rót vào ly theo tỉ lệ 1/3 cà phê – 1/3 sữa nóng – 1/3 bọt sữa -> Rắc cacao/quế (tuỳ thích) .</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sub-menu__column">
                                        <!-- Menu column 1 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-11.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-11.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Americano đá</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Nguyên liệu : 1 shot espresso , 150ml nước lạnh , Đá viên</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Cách pha : Pha một shot espresso và thêm nước lạnh vào ly -> Sau đó cho thêm đá viên và thưởng thức.</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <!-- Menu column 2 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-12.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-12.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Coffe kem tươi</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Nguyên liệu : 1 shot espresso , 2 muỗng kem tươi (whipped cream) , Đường tùy ý .</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Cách pha : Pha một shot espresso, thêm kem tươi lên trên -> Bạn có thể thêm đường tùy theo sở thích.</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sub-menu__column">
                                        <!-- Menu column 1 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-13.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-13.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Matcha Shot Latte</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Nguyên liệu : 1 muỗng bột matcha , 1 shot espresso , 150ml sữa tươi , Đá viên .</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Cách pha : Pha bột matcha với nước nóng, sau đó đổ sữa tươi vào ly và thêm đá -> Rót espresso lên trên cùng.</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <!-- Menu column 2 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-14.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-14.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Whipped Coffee</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Nguyên liệu : 2 muỗng cà phê hoà tan , 2 muỗng đường , 2 muỗng nước nóng , 200ml sữa tươi không đường , Đá viên .</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Cách pha : Đánh bông cà phê hòa tan, đường và nước nóng cho đến khi hỗn hợp trở nên mịn, bông xốp ->  đổ hỗn hợp bọt cà phê lên trên lớp sữa lạnh và đá viên.</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="navbar__item">
                    <a href="#!" class="navbar__link">
                        Loại Coffee phổ biến
                        <img src="./assets/icons/arrow-down.svg" alt="" class="icon navbar__arrow" />
                    </a>
                    <div class="dropdown js-dropdown">
                        <div class="dropdown__inner">
                            <div class="top-menu">
                                <div class="sub-menu sub-menu--not-main">
                                    <div class="sub-menu__column">
                                        <!-- Menu column 1 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-7.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-5.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Mocha</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">
                                                        Mocha Harrar
                                                        </a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Mocha Yemen</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Mocha Yirgacheffe</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Mocha Blend</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sub-menu__column">
                                        <!-- Menu column 1 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-16.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-16.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Bourbon</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Red Bourbon</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Yellow Bourbon</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Orange Bourbon</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Bourbon cổ</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sub-menu__column">
                                        <!-- Menu column 1 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-18.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-18.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Typica</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Blue Mountain Typica</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Java Typica</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Maragogipe (Typica đột biến)</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Criollo (Typica cổ hiếm)</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Sumatra Typica</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sub-menu__column">
                                        <!-- Menu column 1 -->
                                        <div class="menu-column">
                                            <div class="menu-column__icon">
                                                <img
                                                    src="./assets/img/category/cate-19.1.svg"
                                                    alt=""
                                                    class="menu-column__icon-1"
                                                />
                                                <img
                                                    src="./assets/img/category/cate-13.2.svg"
                                                    alt=""
                                                    class="menu-column__icon-2"
                                                />
                                            </div>
                                            <div class="menu-column__content">
                                                <h2 class="menu-column__heading">
                                                    <a href="#!">Catimor</a>
                                                </h2>
                                                <ul class="menu-column__list">
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Catimor truyền thống</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Catimor T-5175</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Catimor T-8667</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Catimor T-5296</a>
                                                    </li>
                                                    <li class="menu-column__item">
                                                        <a href="#!" class="menu-column__link">Catimor H-528</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>

        <!--  -->
        <div class="navbar__overlay js-toggle" toggle-target="#navbar"></div>

        <!-- Actions -->
        <div class="top-act">
            <!-- Tìm kiếm sản phẩm -->
        <div class="search-box">
            <form action="index-logined.php" method="GET" class="search-box">
                <input 
                    type="text" 
                    name="keyword" 
                    class="search-input" 
                    placeholder="Tìm kiếm ..."
                    value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>"
                >
                <button type="submit" class="search-btn">
                    <img src="./assets/icons/search.svg" alt="" class="icon" />
                </button>
            </form>
        </div>
            <div class="top-act__group d-md-none" >
                <div class="top-act__btn-wrap" >
                    <button class="top-act__btn" id="wishlistBtn">
                </div>
            <!-- Sản phẩm được yêu thích -->
            <div class="top-act__group d-md-none">
                <div class="top-act__btn-wrap">
                    <button class="top-act__btn">
                        <img src="./assets/icons/heart.svg" alt="" class="icon top-act__icon" />
                        <span class="top-act__title" id="wishlistCount">
                            <span><?= str_pad($so_yeu_thich, 2, '0', STR_PAD_LEFT) ?></span>
                        </span>
                    </button>

                    <!-- Dropdown -->
                    <div class="act-dropdown">
                        <div class="act-dropdown__inner">
                            <img src="./assets/icons/arrow-up.png" alt="" class="act-dropdown__arrow" />
                            <div class="act-dropdown__top">
                                <h2 class="act-dropdown__title">
                                    Bạn có <span id="wishlistCountText"><?= $so_yeu_thich ?></span> sản phẩm
                                </h2>
                                <a href="./favourite.php" class="act-dropdown__view-all">Tất cả</a>
                            </div>
                            <div class="row row-cols-3 gx-2 act-dropdown__list" id="wishlistPreview">
                                <?php if (empty($wishlist)): ?>
                                    <p style="padding: 12px; font-size: 14px;">Chưa có sản phẩm yêu thích</p>
                                <?php else: ?>
                                    <?php foreach ($wishlist as $item): ?>
                                        <div class="col">
                                            <article class="cart-preview-item">
                                                <div class="cart-preview-item__img-wrap">
                                                    <img
                                                        src="<?= htmlspecialchars($item['image'] ?? '') ?>"
                                                        class="cart-preview-item__thumb"
                                                        alt=""
                                                    />
                                                </div>

                                                <h3 class="cart-preview-item__title">
                                                    <?= htmlspecialchars($item['name'] ?? 'Không có tên') ?>
                                                </h3>

                                                <p class="cart-preview-item__price">
                                                    <?= number_format((float)($item['price'] ?? 0), 0, ',', '.') ?> ₫
                                                </p>
                                            </article>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="act-dropdown__separate"></div>
                        </div>
                    </div>
                </div>

                <div class="top-act__separate"></div>

                <div class="top-act__btn-wrap">
                    <a href="./checkout.php" class="top-act__btn" style="text-decoration: none; display: flex; align-items: center;">
    <img src="./assets/icons/buy.svg" alt="" class="icon top-act__icon">
    <span class="top-act__title" style="color: inherit;">Cart</span> 
</a>
                </div>
            </div>
            <!-- Thông tin người dùng -->
            <div class="top-act__user">
                <img
                    src="./assets/img/avatar/<?= htmlspecialchars($user['avatar'] ) ?>"
                    class="top-act__avatar"
                    alt="Avatar"
                    onerror="this.src='./assets/img/avatar/avatar-3.png'"
                >
                <!-- Dropdown -->
                <div class="act-dropdown top-act__dropdown">
                    <div class="act-dropdown__inner user-menu">
                         <img
                            src="./assets/icons/arrow-up.png"
                            alt=""
                            class="act-dropdown__arrow top-act__dropdown-arrow"
                        />
                        <div class="user-menu__top">
                             <img
                                src="./assets/icons/arrow-up.png"
                                alt=""
                                class="act-dropdown__arrow top-act__dropdown-arrow"
                            />
                            <div class="header-user__info">
                                <img
                                    src="./assets/img/avatar/<?= htmlspecialchars($user['avatar'] ) ?>"
                                    class="user-menu__avatar"
                                    alt="Avatar"
                                    onerror="this.src='./assets/img/avatar/avatar-3.png'"
                                >
                                <h1 class="header-user__name">
                                    <?= htmlspecialchars($user['full_name']) ?>
                                </h1>
                                <p class="header-user__email">
                                    <?= htmlspecialchars($user['email']) ?>
                                </p>
                            </div>
                        </div>

                        <ul class="user-menu__list">
                            <li>
                                <a href="./profile.php" class="user-menu__link">Profile</a>
                            </li>
                            <li>
                                <a href="./favourite.php" class="user-menu__link">Favourite list</a>
                            </li>
                            <li class="user-menu__separate">
                                <a href="#!" class="user-menu__link" id="switch-theme-btn">
                                    <span>Dark mode</span>
                                    <img src="./assets/icons/sun.svg" alt="" class="icon user-menu__icon" />
                                </a>
                            </li>
                            <li>
                                <a href="#!" class="user-menu__link">Settings</a>
                            </li>
                            <li class="user-menu__separate">
                                <a href="./logout.php" class="user-menu__link">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
