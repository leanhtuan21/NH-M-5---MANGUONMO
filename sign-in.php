<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

/* === KẾT NỐI CƠ SỞ DỮ LIỆU === */
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "grocery_mart_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

/* === LẤY EMAIL ĐÃ GHI NHỚ (ĐỂ ĐIỀN VÀO INPUT === */
if (isset($_COOKIE['remember_email'])) {
    $remembered_email = $_COOKIE['remember_email'];
} else {
    $remembered_email = '';
}

/* === TỰ ĐĂNG NHẬP KHI LOAD TRANG (AUTO LOGIN) === */
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_login'])) {

    $data = json_decode(base64_decode($_COOKIE['remember_login']), true);

    if (is_array($data) && isset($data['email'])) {

        $email = $data['email'];

        $sql = "SELECT id, full_name, role FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {

            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['email']     = $email;
            $_SESSION['user_role'] = $user['role'];

            header("Location: index-logined.php");
            exit();
        }
    }
}

/* === BIẾN KIỂM SOÁT ĐĂNG NHẬP === */
$error_message = "";
$max_attempts = 3;
$lockout_time = 30;
$remaining_seconds = 0;

/* === HIỂN THỊ TRẠNG THÁI KHÓA KHI LOAD TRANG === */
if ($_SERVER["REQUEST_METHOD"] === "GET" && !empty($_SESSION['last_email'])) {

    $email = $_SESSION['last_email'];
    $lock_key = 'lockout_until_' . md5($email);

    if (isset($_SESSION[$lock_key]) && time() < $_SESSION[$lock_key]) {
        $remaining_seconds = $_SESSION[$lock_key] - time();
        $error_message =
            "Tài khoản đang bị tạm khóa. Vui lòng thử lại sau 
            <span id='countdown'>$remaining_seconds</span> giây.";
    }
}

/* === XỬ LÝ ĐĂNG NHẬP (POST) === */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error_message = "Vui lòng nhập đầy đủ email và mật khẩu";
    } else {

        /* RESET KHI ĐỔI EMAIL */
        if (isset($_SESSION['last_email']) && $_SESSION['last_email'] !== $email) {
            unset($_SESSION['login_attempts_' . md5($_SESSION['last_email'])]);
            unset($_SESSION['lockout_until_' . md5($_SESSION['last_email'])]);
        }
        $_SESSION['last_email'] = $email;

        $attempt_key = 'login_attempts_' . md5($email);
        $lock_key    = 'lockout_until_' . md5($email);

        /* KIỂM TRA KHÓA */
        if (isset($_SESSION[$lock_key]) && time() < $_SESSION[$lock_key]) {

            $remaining_seconds = $_SESSION[$lock_key] - time();
            $error_message =
                "Tài khoản đang bị tạm khóa. Vui lòng thử lại sau 
                <span id='countdown'>$remaining_seconds</span> giây.";

        } else {

            /* RESET SAU KHI HẾT KHÓA */
            if (isset($_SESSION[$lock_key])) {
                unset($_SESSION[$lock_key], $_SESSION[$attempt_key]);
            }

            /* TRUY VẤN USER */
            $sql = "SELECT id, full_name, password, role FROM users WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {

                $user = mysqli_fetch_assoc($result);

                if (md5($password) === $user['password']) {

                    /* ================= LOGIN THÀNH CÔNG ================= */
                    unset($_SESSION[$attempt_key], $_SESSION[$lock_key]);

                    session_regenerate_id(true);

                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['email']     = $email;
                    $_SESSION['user_role'] = $user['role'];

                    /* ===== GHI NHỚ ĐĂNG NHẬP (COOKIE) ===== */
                    if (isset($_POST['remember'])) {

                        // Cookie điền email
                        setcookie(
                            'remember_email',
                            $email,
                            time() + 86400 * 3,
                            '/'
                        );

                        // Cookie auto login
                        setcookie(
                            'remember_login',
                            base64_encode(json_encode([
                                'email' => $email
                            ])),
                            time() + 86400 * 3,
                            '/',
                            '',
                            false,
                            true
                        );

                    } else {
                        //chỉ xóa cookie khi login THÀNH CÔNG mà KHÔNG tick remember
                        if (isset($_COOKIE['remember_email'])) {
                            setcookie('remember_email', '', time() - 3600, '/');
                        }
                        if (isset($_COOKIE['remember_login'])) {
                            setcookie('remember_login', '', time() - 3600, '/');
                        }
                    }


                    /* PHÂN QUYỀN THEO ROLE */
                    if (strtolower($user['role']) === 'admin') {
                        header("Location: admin-dashboard.php");
                    } else {
                        header("Location: index-logined.php");
                    }
                    exit();

                } else {
                    $_SESSION[$attempt_key] = ($_SESSION[$attempt_key] ?? 0) + 1;                }
            } else {
                $_SESSION[$attempt_key] = ($_SESSION[$attempt_key] ?? 0) + 1;
            }

            /* ===== GIỚI HẠN SAI ===== */
             if ($_SESSION[$attempt_key] >= $max_attempts) {

                $_SESSION[$attempt_key] = 0;
                $_SESSION[$lock_key] = time() + $lockout_time;
                $remaining_seconds = $lockout_time;
                $error_message =
                    "Nhập sai quá $max_attempts lần. Tài khoản bị khóa 
                    <span id='countdown'>$remaining_seconds</span> giây.";
            } else {
                $remaining_tries = $max_attempts - $_SESSION[$attempt_key];
                $error_message =
                    "Email hoặc mật khẩu không chính xác! Bạn còn $remaining_tries lần thử.";
            }

            if (isset($stmt)) {
                mysqli_stmt_close($stmt);
            }
        }
    }
}

mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Đăng nhập | Grocery Mart</title>

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

      <style>
            /* TẮT ICON CON MẮT MẶC ĐỊNH CỦA TRÌNH DUYỆT (Chrome / Edge) */
            input[type="password"]::-ms-reveal,
            input[type="password"]::-ms-clear {
                display: none;
            }

            input[type="password"]::-webkit-textfield-decoration-container {
                display: none !important;
            }
        </style>

    </head>
   
    <body>
        <main class="auth">
            <!-- Auth intro -->
            <div class="auth__intro d-md-none">
                <img src="./assets/img/auth/intro.svg" alt="" class="auth__intro-img" />
                <p class="auth__intro-text">
                    Khám phá hương vị cà phê nguyên chất, sản phẩm thượng hạng và trải nghiệm mua sắm tuyệt vời nhất
                </p>
            </div>

            <!-- Auth content -->
            <div class="auth__content">
                <div class="auth__content-inner">
                    <a href="./" class="logo">
                        <img src="./assets/icons/logo.svg" alt="grocerymart" class="logo__img" />
                        <h2 class="logo__title">Grocerymart</h2>
                    </a>
                    <h1 class="auth__heading">Chào mừng bạn trở lại!</h1>
                    <p class="auth__desc">
                        Đăng nhập để tiếp tục hành trình khám phá hương vị cà phê
                    </p>
                    
                    <!-- Thông báo lỗi từ PHP -->
                    <?php if (!empty($error_message)): ?>
                        <p
                            id="lock-message"
                            data-remaining="<?= $remaining_seconds ?>"
                            style="color: #ff4d4f; background: #fff1f0; padding: 10px; border-radius: 5px; text-align: center; font-size: 1.4rem; margin-bottom: 20px;"
                        >
                            <?= $error_message ?>
                        </p>
                    <?php endif; ?>

                    <script>
                        const lockMsg = document.getElementById('lock-message');
                        const countdownEl = document.getElementById('countdown');

                        if (lockMsg && countdownEl) {

                            let seconds = parseInt(lockMsg.dataset.remaining);

                            if (!isNaN(seconds) && seconds > 0) {

                                const timer = setInterval(() => {
                                    seconds--;
                                    countdownEl.innerText = seconds;

                                    if (seconds <= 0) {
                                        clearInterval(timer);
                                        // Reload 1 lần duy nhất sau khi hết khóa
                                        window.location.href = window.location.pathname;
                                    }
                                }, 1000);

                            }
                        }
                    </script>

                    <form action="" method="POST" class="form auth__form">
                        <div class="form__group">
                            <div class="form__text-input">
                                <input
                                    type="email"
                                    name="email"     
                                    placeholder="Email"
                                    class="form__input"
                                    value="<?= htmlspecialchars($remembered_email) ?>"
                                    autofocus
                                    required
                                />
                                <img src="./assets/icons/message.svg" alt="" class="form__input-icon" />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Địa chỉ email không hợp lệ</p>
                        </div>
                        
                        <div class="form__group">
                            <div class="form__text-input" style="position: relative;">
                                <input
                                    type="password"
                                    name="password"
                                    id="password-input"
                                    placeholder="Mật khẩu"
                                    class="form__input"
                                    required
                                    minlength="6"
                                />
                               
                                <!-- Icon khóa: dùng để ẩn / hiện mật khẩu -->
                                <img
                                    src="./assets/icons/lock.svg"
                                    alt="Ẩn / hiện mật khẩu"
                                    class="form__input-icon"
                                    id="toggle-password"
                                    style="cursor: pointer;"
                                />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Mật khẩu phải có ít nhất 6 kí tự</p>
                        </div>

                        <!-- Chức năng ẩn hiện để xem mật khẩu khi nhập -->
                       <script>
                            const togglePassword = document.getElementById('toggle-password');
                            const passwordInput = document.getElementById('password-input');

                            if (togglePassword && passwordInput) {
                                togglePassword.addEventListener('click', function () {
                                    passwordInput.type =
                                        passwordInput.type === 'password' ? 'text' : 'password';
                                });
                            }
                        </script>


                        <div class="form__group form__group--inline">
                            <label class="form__checkbox">
                                <input type="checkbox" name="remember" value="1" class="form__checkbox-input d-none" />
                                <span class="form__checkbox-label">Ghi nhớ đăng nhập</span>
                            </label>
                            <a href="./reset-password.php" class="auth__link form__pull-right">Quên mật khẩu?</a>
                        </div>
                        
                        <div class="form__group auth__btn-group">
                            <button type="submit" class="btn btn--primary auth__btn form__submit-btn">Đăng nhập</button>
                        </div>
                    </form>

                    <p class="auth__text">
                        Bạn chưa có tài khoản ?
                        <a href="./sign-up.php" class="auth__link auth__text-link">Đăng ký</a>
                    </p>
                </div>
            </div>
        </main>
        <script>
            window.dispatchEvent(new Event("template-loaded"));
        </script>
    </body>
</html>
