<?php
session_start();

// --- 1. KẾT NỐI CƠ SỞ DỮ LIỆU ---
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "grocery_mart_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

$error_message = "";

// --- 2. XỬ LÝ ĐĂNG NHẬP ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // (6) Kiểm tra tồn tại POST
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // (2) Dùng prepared statement chống SQL Injection
    $sql = "SELECT id, full_name, password, role FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // (1) CHỈ kiểm tra mật khẩu bằng password_verify
        if (password_verify($password, $user['password'])) {

            // (5) Regenerate session ID
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] == 1) {
                header("Location: admin-dashboard.php");
            } else {
                header("Location: index-logined.php");
            }
            exit();
        } else {
            $error_message = "Mật khẩu không chính xác!";
        }
    } else {
        $error_message = "Email này chưa được đăng ký!";
    }

    mysqli_stmt_close($stmt);
}

// (7) Đóng kết nối CSDL
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
                            <div class="form__text-input">
                                <input
                                    type="password"
                                    name="password"
                                    placeholder="Mật khẩu"
                                    class="form__input"
                                    required
                                    minlength="6"
                                />
                                <img src="./assets/icons/lock.svg" alt="" class="form__input-icon" />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Mật khẩu phải có ít nhất 6 kí tự</p>
                        </div>
                        
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
