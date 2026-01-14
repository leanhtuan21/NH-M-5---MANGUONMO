<?php
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $conn = new mysqli("localhost", "root", "", "grocery_mart_db");
    if ($conn->connect_error) {
        die("Kết nối CSDL thất bại");
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Vui lòng nhập đầy đủ thông tin";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp";
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email đã được đăng ký";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (email, password, role, created_at)
                 VALUES (?, ?, 'user', NOW())"
            );
            $stmt->bind_param("ss", $email, $hashedPassword);

            if ($stmt->execute()) {
                header("Location: sign-in.php");
                exit;
            } else {
                $error = "Đăng ký thất bại";
            }
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Sign up | Grocery Mart</title>

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
            <div class="auth__intro">
                <a href="./" class="logo auth__intro-logo d-none d-md-flex">
                    <img src="./assets/icons/logo.svg" alt="grocerymart" class="logo__img" />
                    <h1 class="logo__title">grocerymart</h1>
                </a>
                <img src="./assets/img/auth/intro.svg" alt="" class="auth__intro-img" />
                <p class="auth__intro-text">
                Hội tụ những giá trị tốt nhất của thương hiệu xa xỉ, sản phẩm chất lượng cao và dịch vụ sáng tạo
                </p>
                <button class="auth__intro-next d-none d-md-flex js-toggle" toggle-target="#auth-content">
                    <img src="./assets/img/auth/intro-arrow.svg" alt="" />
                </button>
            </div>

            <!-- Auth content -->
            <div id="auth-content" class="auth__content hide">
                <div class="auth__content-inner">
                    <a href="./" class="logo">
                        <img src="./assets/icons/logo.svg" alt="grocerymart" class="logo__img" />
                        <h1 class="logo__title">Cửa hàng tạp hóa</h1>
                    </a>
                    <h1 class="auth__heading">Đăng ký</h1>
                    <p class="auth__desc">Hãy tạo tài khoản của bạn và mua sắm như một chuyên gia đồng thời tiết kiệm tiền.</p>
                    <form  method="POST" class="form auth__form">
                        <div class="form__group">
                            <div class="form__text-input">
                                <input
                                    type="email"
                                    name="email"
                                    id=""
                                    placeholder="Email"
                                    class="form__input"
                                    autofocus
                                    required
                                />
                                <img src="./assets/icons/message.svg" alt="" class="form__input-icon" />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Email không đúng định dạng</p>
                        </div>
                        <div class="form__group">
                            <div class="form__text-input">
                                <input
                                    type="password"
                                    name="password"
                                    id=""
                                    placeholder="Password"
                                    class="form__input"
                                    required
                                    minlength="6"
                                />
                                <img src="./assets/icons/lock.svg" alt="" class="form__input-icon" />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Mật khẩu phải có ít nhất 6 ký tự</p>
                        </div>
                        <div class="form__group">
                            <div class="form__text-input">
                                <input
                                    type="password"
                                    name="confirm_password"
                                    id=""
                                    placeholder="Confirm password"
                                    class="form__input"
                                    required
                                    minlength="6"
                                />
                                <img src="./assets/icons/lock.svg" alt="" class="form__input-icon" />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Mật khẩu phải có ít nhất 6 ký tự</p>
                        </div>
                        <div class="form__group form__group--inline">
                            <label class="form__checkbox">
                                <input type="checkbox" name="" id="" class="form__checkbox-input d-none" />
                                <span class="form__checkbox-label">Tôi đồng ý với điều khoản sử dụng</span>
                            </label>
                        </div>
                        <div class="form__group auth__btn-group">
                            <button class="btn btn--primary auth__btn form__submit-btn">Đăng ký</button>
                            <button class="btn btn--outline auth__btn btn--no-margin">
                                <img src="./assets/icons/google.svg" alt="" class="btn__icon icon" />
                                Đăng ký bằng Google
                            </button>
                        </div>
                    </form>

                    <p class="auth__text">
                    Bạn đã có tài khoản chưa? 
                        <a href="./sign-in.php" class="auth__link auth__text-link">Đăng nhập</a>
                    </p>
                </div>
            </div>
        </main>
        <script>
            window.dispatchEvent(new Event("template-loaded"));
        </script>
        
    </body>
</html>