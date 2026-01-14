<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

// --- KẾT NỐI CSDL ---
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "grocery_mart_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

$error_message = "";
$max_attempts = 5;
$lockout_time = 60; // 1 phút

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // --- KIỂM TRA KHÓA ---
    if (isset($_SESSION['lockout_until']) && time() < $_SESSION['lockout_until']) {

        $remaining = $_SESSION['lockout_until'] - time();
        $error_message = "Tài khoản đang bị tạm khóa. Vui lòng thử lại sau $remaining giây.";

    } else {

        // Hết thời gian khóa → reset
        if (isset($_SESSION['lockout_until']) && time() >= $_SESSION['lockout_until']) {
            unset($_SESSION['lockout_until']);
            unset($_SESSION['login_attempts']);
        }

        // --- TRUY VẤN USER ---
        $sql = "SELECT id, full_name, password, role FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);

            // --- SO KHỚP MẬT KHẨU BẰNG MD5 ---
            if (md5($password) === $user['password']) {

                // ĐÚNG → RESET ĐẾM
                unset($_SESSION['login_attempts']);
                unset($_SESSION['lockout_until']);

                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];

                // PHÂN QUYỀN (CHỐNG LỖI HOA / THƯỜNG)
                if (strtolower($user['role']) === 'admin') {
                    header("Location: admin-dashboard.php");
                } else {
                    header("Location: index-logined.php");
                }
                exit();

            } else {
                // SAI MẬT KHẨU
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

                if ($_SESSION['login_attempts'] >= $max_attempts) {
                    $_SESSION['lockout_until'] = time() + $lockout_time;
                    $error_message = "Nhập sai quá 5 lần. Tài khoản bị khóa 1 phút.";
                } else {
                    $remaining_tries = $max_attempts - $_SESSION['login_attempts'];
                    $error_message = "Email hoặc mật khẩu không chính xác! Bạn còn $remaining_tries lần thử.";
                }
            }

        } else {
            // EMAIL KHÔNG TỒN TẠI → VẪN TÍNH LÀ SAI
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

            if ($_SESSION['login_attempts'] >= $max_attempts) {
                $_SESSION['lockout_until'] = time() + $lockout_time;
                $error_message = "Nhập sai quá 5 lần. Tài khoản bị khóa 1 phút.";
            } else {
                $remaining_tries = $max_attempts - $_SESSION['login_attempts'];
                $error_message = "Email hoặc mật khẩu không chính xác! Bạn còn $remaining_tries lần thử.";
            }
        }

        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
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
                    <?php if ($error_message != ""): ?>
                        <p style="color: #ff4d4f; background: #fff1f0; padding: 10px; border-radius: 5px; text-align: center; font-size: 1.4rem; margin-bottom: 20px;">
                            ⚠️ <?php echo $error_message; ?>
                        </p>
                    <?php endif; ?>


                    <form action="" method="POST" class="form auth__form">
                        <div class="form__group">
                            <div class="form__text-input">
                                <input
                                    type="email"
                                    name="email"     
                                    placeholder="Email"
                                    class="form__input"
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
                                <img src="./assets/icons/lock.svg" alt="" class="form__input-icon" />
                                <img 
                                    src="./assets/icons/eye.svg" 
                                    alt="Toggle Password" 
                                    id="toggle-password"
                                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; width: 20px; opacity: 0.5;"
                                />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Mật khẩu phải có ít nhất 6 kí tự</p>
                        </div>
                        <script>
                            const togglePassword = document.querySelector('#toggle-password');
                            const passwordInput = document.querySelector('#password-input');

                            togglePassword.addEventListener('click', function () {
                                // Kiểm tra loại input hiện tại
                                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                                passwordInput.setAttribute('type', type);
                                
                                // Thay đổi độ mờ của icon để người dùng biết là đang kích hoạt
                                this.style.opacity = type === 'text' ? '1' : '0.5';
                            });
                        </script>
                        
                        <div class="form__group form__group--inline">
                            <label class="form__checkbox">
                                <input type="checkbox" name="" id="" class="form__checkbox-input d-none" />
                                <span class="form__checkbox-label">Ghi nhớ đăng nhập</span>
                            </label>
                            <a href="./reset-password.php" class="auth__link form__pull-right">Quên mật khẩu?</a>
                        </div>
                        
                        <div class="form__group auth__btn-group">
                            <button type="submit" class="btn btn--primary auth__btn form__submit-btn">Đăng nhập</button>
                            
                            <button type="button" class="btn btn--outline auth__btn btn--no-margin">
                                <img src="./assets/icons/google.svg" alt="" class="btn__icon icon" />
                                Đăng nhập với Google
                            </button>
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
