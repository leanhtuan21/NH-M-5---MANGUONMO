<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: sign-in.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$full_name = $_SESSION['user_name'];

/* 3. Lấy thông tin user từ CSDL + câu hỏi xác minh */
$sql = "SELECT id, full_name, email, phone, address, avatar,
               security_answer_1, security_answer_2
        FROM users
        WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'avatar-3.png';

/* 5. Gán dữ liệu ra form */
$full_name = $user['full_name'] ?? '';
$phone     = $user['phone'] ?? '';
$address   = $user['address'] ?? '';
$has_security_info =
    !empty($user['security_answer_1']) &&
    !empty($user['security_answer_2']);


/* 6. Xử lý cập nhật */
if (isset($_POST['update_profile'])) {

    $phone = trim($_POST['phone'] ?? '');
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo "<script>alert('Số điện thoại phải gồm đúng 10 chữ số');</script>";
        exit;
    }

    $address = trim($_POST['address'] ?? '');

    // LẤY CÂU TRẢ LỜI XÁC MINH
    $ans1 = trim($_POST['security_answer_1'] ?? '');
    $ans2 = trim($_POST['security_answer_2'] ?? '');

    // NẾU USER NHẬP ĐỦ 2 CÂU → LƯU
    if ($ans1 !== '' && $ans2 !== '') {
        $ans1 = strtolower($ans1);
        $ans2 = strtolower($ans2);

        $sql_update = "UPDATE users 
                        SET phone = ?, address = ?, 
                            security_answer_1 = ?, security_answer_2 = ?
                        WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $phone,
            $address,
            $ans1,
            $ans2,
            $user_id
        );
    } else {
        // KHÔNG NHẬP → CHỈ UPDATE INFO THƯỜNG
        $sql_update = "UPDATE users 
                        SET phone = ?, address = ?
                        WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $phone,
            $address,
            $user_id
        );
    }

    mysqli_stmt_execute($stmt);
    header("Location: profile.php");
    exit;
}

// Ảnh đại diện
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {

    $allow_ext = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
        echo "<script>alert('Ảnh tối đa 2MB');</script>";
        exit;
    }
    if (!in_array($ext, $allow_ext)) {
        echo "<script>alert('Chỉ chấp nhận JPG, PNG, WEBP');</script>";
    } else {

        $avatar_name = 'avatar_' . time() . '.' . $ext;
        $upload_dir = __DIR__ . '/assets/img/avatar/';
        $upload_path = $upload_dir . $avatar_name;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {

            // cập nhật DB
            $sql = "UPDATE users SET avatar = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $avatar_name, $user_id);
            mysqli_stmt_execute($stmt);
            // CẬP NHẬT SESSION
            $_SESSION['avatar'] = $avatar_name;
            header("Location: profile.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Chỉnh sửa thông tin cá nhân | Grocery Mart</title>

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
    <style>
        .avatar-wrapper {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }
    /* ảnh avatar */
    .profile-user__avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
    }
    /* dấu cộng nằm trong ảnh */
    .avatar-plus {
        position: absolute;
        inset: 0; /* phủ toàn ảnh */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        font-weight: bold;
        color: #000;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
    }
    </style>
    <body>
        <!-- Header -->
        <header id="header" class="header"></header>
        <script>
            load("#header", "./templates/header-logined.php");
        </script>

        <!-- MAIN -->
        <main class="profile">
            <div class="container">
                <!-- Search bar -->
                <div class="profile-container">
                    <div class="search-bar d-none d-md-flex">
                        <input type="text" placeholder="Tìm kiếm sản phẩm" class="search-bar__input" />
                        <button class="search-bar__submit">
                            <img src="./assets/icons/search.svg" alt="" class="search-bar__icon icon" />
                        </button>
                    </div>
                </div>

                <!-- Profile content -->
                <div class="profile-container">
                    <div class="row gy-md-3">
                        <div class="col-3 col-xl-4 d-lg-none">
                            <aside class="profile__sidebar">
                                <!-- User -->
                                <div class="profile-user">
                                    <form method="POST" enctype="multipart/form-data">
                                        <label for="upload-avatar" class="avatar-wrapper">
                                            <img
                                                src="./assets/img/avatar/<?= htmlspecialchars($avatar) ?>"
                                                class="profile-user__avatar"
                                                alt="Avatar"
                                                onerror="this.src='./assets/img/avatar/avatar-3.png'"
                                            >
                                            <span class="avatar-plus">+</span>
                                        </label>

                                        <input
                                            type="file"
                                            id="upload-avatar"
                                            name="avatar"
                                            accept="image/*"
                                            hidden
                                            onchange="this.form.submit()"
                                        >
                                    </form>
                                    <h1 class="header-user__name">
                                        <?= htmlspecialchars($user['full_name']) ?>
                                    </h1>
                                    <p class="header-user__email">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </p>
                                </div>

                                <!-- Menu 1 -->
                                <div class="profile-menu">
                                    <h3 class="profile-menu__title">Quản lý tài khoản</h3>
                                    <ul class="profile-menu__list">
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/profile.svg" alt="" class="icon" />
                                                </span>
                                                Thông tin cá nhân
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/message-2.svg" alt="" class="icon" />
                                                </span>
                                                Quyền riêng tư & liên hệ
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Menu 2 -->
                                <div class="profile-menu">
                                    <h3 class="profile-menu__title">Sản phẩm của tôi</h3>
                                    <ul class="profile-menu__list">
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/download.svg" alt="" class="icon" />
                                                </span>
                                                Đặt lại đơn hàng
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/heart.svg" alt="" class="icon" />
                                                </span>
                                                Danh sách yêu thích
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/gift-2.svg" alt="" class="icon" />
                                                </span>
                                                Quà tặng
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Menu 3 -->
                                <div class="profile-menu">
                                    <h3 class="profile-menu__title">Gói & đăng ký</h3>
                                    <ul class="profile-menu__list">
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/shield.svg" alt="" class="icon" />
                                                </span>
                                                Gói bảo vệ
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Menu 4 -->
                                <div class="profile-menu">
                                    <h3 class="profile-menu__title">Hỗ trợ khách hàng</h3>
                                    <ul class="profile-menu__list">
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/info.svg" alt="" class="icon" />
                                                </span>
                                                Trợ giúp
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/danger.svg" alt="" class="icon" />
                                                </span>
                                                Điều khoản sử dụng
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </aside>
                        </div>

                        <div class="col-9 col-xl-8 col-lg-12">
                            <div class="cart-info">
                                <div class="row gy-3">
                                    <div class="col-12">
                                        <h2 class="cart-info__heading">
                                            <a href="./profile.php">
                                                <img
                                                    src="./assets/icons/arrow-left.svg"
                                                    alt=""
                                                    class="icon cart-info__back-arrow"
                                                />
                                            </a>
                                            Thông tin cá nhân
                                        </h2>

                                        <form method="post" class="form form-card">
                                            <!-- Form row 1 -->
                                            <div class="form__row">
                                                <div class="form__group">
                                                    <label for="full-name" class="form__label form-card__label">
                                                        Họ và tên
                                                    </label>
                                                    <div class="form__text-input">
                                                        <input
                                                            type="text"
                                                            name="full_name"
                                                            value="<?= htmlspecialchars($user['full_name']) ?>"
                                                            id="full-name"
                                                            placeholder="Nhập họ và tên"
                                                            class="form__input"
                                                            readonly
                                                        />
                                                        <img
                                                            src="./assets/icons/form-error.svg"
                                                            alt=""
                                                            class="form__input-icon-error"
                                                        />
                                                    </div>
                                                    <p class="form__error">Vui lòng nhập họ và tên</p>
                                                </div>

                                                <div class="form__group">
                                                    <label for="email-adress" class="form__label form-card__label">
                                                        Địa chỉ email
                                                    </label>
                                                    <div class="form__text-input">
                                                        <input
                                                            type="text"
                                                            id="email-adress"
                                                            value="<?= htmlspecialchars($user['email']) ?>"
                                                            placeholder="Nhập email"
                                                            class="form__input"
                                                            disabled
                                                        />
                                                        <img
                                                            src="./assets/icons/form-error.svg"
                                                            alt=""
                                                            class="form__input-icon-error"
                                                        />
                                                    </div>
                                                    <p class="form__error">Vui lòng nhập email hợp lệ</p>
                                                </div>
                                            </div>

                                            <!-- Form row 2 -->
                                            <div class="form__row">
                                                <div class="form__group">
                                                    <label for="phone-number" class="form__label form-card__label">
                                                        Số điện thoại
                                                    </label>
                                                    <div class="form__text-input">
                                                        <input
                                                            type="text"
                                                            name="phone"
                                                            value="<?= htmlspecialchars($phone) ?>"
                                                            id="phone-number"
                                                            placeholder="Nhập số điện thoại (10 chữ số)"
                                                            class="form__input"
                                                            required
                                                            pattern="[0-9]{10}"
                                                            title="Số điện thoại phải gồm đúng 10 chữ số"
                                                        />

                                                        <img
                                                            src="./assets/icons/form-error.svg"
                                                            alt=""
                                                            class="form__input-icon-error"
                                                        />
                                                    </div>
                                                    <p class="form__error">Vui lòng nhập số điện thoại hợp lệ</p>
                                                </div>

                                                <div class="form__group">
                                                    <label for="password" class="form__label form-card__label">
                                                        Địa chỉ
                                                    </label>
                                                    <div class="form__text-input">
                                                        <input
                                                            type="text"
                                                            name="address"
                                                            value="<?= htmlspecialchars($address) ?>"
                                                            placeholder="Nhập mật địa chỉ"
                                                            class="form__input"
                                                            required
                                                        />
                                                        <img
                                                            src="./assets/icons/form-error.svg"
                                                            alt=""
                                                            class="form__input-icon-error"
                                                        />
                                                    </div>
                                                    <p class="form__error">Vui lòng nhập địa chỉ mới</p>
                                                </div>
                                            </div>

                                            <!-- ===== SECURITY QUESTIONS (XÁC MINH CHÍNH CHỦ) ===== -->
                                            <div class="form__row">
                                                <div class="form__group">
                                                    <label class="form__label form-card__label">
                                                        Sở thích
                                                    </label>
                                                    <div class="form__text-input">
                                                        <input
                                                            type="text"
                                                            name="security_answer_1"
                                                            value="<?= htmlspecialchars($user['security_answer_1'] ?? '') ?>"
                                                            placeholder="Sở thích"
                                                            class="form__input"
                                                            required
                                                        />
                                                    </div>
                                                    <p class="form__error">Vui lòng nhập câu trả lời</p>
                                                </div>

                                                <div class="form__group">
                                                    <label class="form__label form-card__label">
                                                        Năng khiếu
                                                    </label>
                                                    <div class="form__text-input">
                                                        <input
                                                            type="text"
                                                            name="security_answer_2"
                                                            value="<?= htmlspecialchars($user['security_answer_2'] ?? '') ?>"
                                                            placeholder="Năng khiếu"
                                                            class="form__input"
                                                            required
                                                        />
                                                    </div>
                                                    <p class="form__error">Vui lòng nhập câu trả lời</p>
                                                </div>
                                            </div>

                                            <div class="form-card__bottom">
                                                <a class="btn btn--text" href="./profile.php">Hủy</a>
                                                <button type="submit" name="update_profile" class="btn btn--primary btn--rounded">Lưu thay đổi</button>
                                            </div>


                                        </form>
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

        <!-- Modal -->
        <div id="delete-confirm" class="modal modal--small hide">
            <div class="modal__content">
                <p class="modal__text">Bạn có muốn xóa sản phẩm này khỏi giỏ hàng không?</p>
                <div class="modal__bottom">
                    <button class="btn btn--small btn--outline modal__btn js-toggle" toggle-target="#delete-confirm">
                        Hủy
                    </button>
                    <button
                        class="btn btn--small btn--danger btn--primary modal__btn btn--no-margin js-toggle"
                        toggle-target="#delete-confirm"
                    >
                        Xóa
                    </button>
                </div>
            </div>
            <div class="modal__overlay js-toggle" toggle-target="#delete-confirm"></div>
        </div>
    </body>
</html>
