<?php
$error = "";
$success = "";
$email_value = ""; // Biến để lưu lại email người dùng nhập

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $conn = new mysqli("localhost", "root", "", "grocery_mart_db");
    if ($conn->connect_error) {
        die("Kết nối CSDL thất bại: " . $conn->connect_error);
    }

    $email = trim($_POST['email'] ?? '');
    $email_value = $email; // Giữ lại giá trị email để hiển thị lại form
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Kiểm tra rỗng
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Vui lòng nhập đầy đủ thông tin";
    }

    // 2. Kiểm tra định dạng email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không đúng định dạng";
    }

    // 3. Kiểm tra độ mạnh mật khẩu
    elseif (
        strlen($password) < 6 ||
        !preg_match('/[A-Z]/', $password) ||      // chữ hoa
        !preg_match('/[a-z]/', $password) ||      // chữ thường
        !preg_match('/[0-9]/', $password) ||      // số
        !preg_match('/[\W]/', $password)          // ký tự đặc biệt
    ) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt";
    }

    // 4. Kiểm tra xác nhận mật khẩu
    elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp";
    }

    else {
        // 5. Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email này đã được đăng ký";
        } else {
            // 6. Lưu mật khẩu đã mã hóa bằng MD5 
            $hashedPassword = md5($password);

            $stmt = $conn->prepare(
                "INSERT INTO users (email, password) VALUES (?, ?)"
            );
            $stmt->bind_param("ss", $email, $hashedPassword);


            if ($stmt->execute()) {
                $success = "Đăng ký thành công! Đang chuyển sang trang đăng nhập...";
                // Dòng này sẽ tự động chuyển trang sau 2 giây
                header("refresh:2;url=sign-in.php"); 
            } else {
                $error = "Đăng ký không thành công: " . $conn->error;
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
            .alert {
                padding: 12px;
                border-radius: 8px;
                font-size: 1.4rem;
                font-weight: 500;
                margin-bottom: 20px;
                text-align: center;
            }
            .alert-danger {
                background-color: #ffe5e5;
                color: #d32f2f;
                border: 1px solid #ffcccc;
            }
            .alert-success {
                background-color: #e5ffe5;
                color: #2e7d32;
                border: 1px solid #ccffcc;
            }
        </style>
    </head>
    <body>
        <main class="auth">
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

            <div id="auth-content" class="auth__content hide">
                <div class="auth__content-inner">
                    <a href="./" class="logo">
                        <img src="./assets/icons/logo.svg" alt="grocerymart" class="logo__img" />
                        <h1 class="logo__title">Cửa hàng tạp hóa</h1>
                    </a>
                    <h1 class="auth__heading">Đăng ký</h1>
                    <p class="auth__desc">Hãy tạo tài khoản của bạn và mua sắm như một chuyên gia đồng thời tiết kiệm tiền.</p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" class="form auth__form">
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
                                    value="<?php echo htmlspecialchars($email_value); ?>" 
                                />
                                <img src="./assets/icons/message.svg" alt="" class="form__input-icon" />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            </div>
                        <div class="form__group">
                            <div class="form__text-input">
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    placeholder="Password"
                                    class="form__input"
                                    required
                                    minlength="6"
                                />
                                <img src="./assets/icons/lock.svg" alt="" class="form__input-icon" />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
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