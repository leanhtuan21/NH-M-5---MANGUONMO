<?php
session_start();
require_once __DIR__ . "/db_connect.php";

$message = "";
$message_type = "";
$old_email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $old_email = $email;

    // 1. Kiểm tra rỗng
    if ($email === "") {
        $message = "Vui lòng nhập email.";
        $message_type = "error";
    }
    // 2. Kiểm tra định dạng email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email không đúng định dạng.";
        $message_type = "error";
    }
    // 2.1 Email phải chứa ít nhất 1 chữ số
    elseif (!preg_match('/[0-9]/', $email)) {
        $message = "Email phải chứa ít nhất 1 chữ số.";
        $message_type = "error";
    }
    // 3. Kiểm tra email tồn tại trong hệ thống
    else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($result)) {
            header("Location: reset-password.php?email=" . urlencode($email));
            exit;
        } else {
            $message = "Email không tồn tại trong hệ thống.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quên mật khẩu | Grocery Mart</title>

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
    <!-- Giới thiệu -->
    <div class="auth__intro d-md-none">
        <img
            src="./assets/img/auth/forgot-password.png"
            alt=""
            class="auth__intro-img"
        />
    </div>

    <!-- Nội dung -->
    <div class="auth__content">
        <div class="auth__content-inner">
            <a href="./" class="logo">
                <img
                    src="./assets/icons/logo.svg"
                    alt="grocerymart"
                    class="logo__img"
                />
                <h2 class="logo__title">Grocerymart</h2>
            </a>

            <h1 class="auth__heading">Quên mật khẩu</h1>

            <p class="auth__desc">
                Nhập email bạn đã dùng để đăng ký tài khoản.
                Chúng tôi sẽ xác nhận email và cho phép bạn tạo mật khẩu mới.
            </p>

            <?php if (!empty($message)): ?>
                <div class="auth__message-wrapper">
                    <div
                        class="auth__message message message--<?= $message_type ?>"
                        style="
                            margin-top: 12px;
                            padding: 10px 14px;
                            border-radius: 8px;
                            font-size: 14px;
                            border: 1px solid <?= $message_type === 'error' ? '#f5c2c7' : '#abefc6' ?>;
                            background-color: <?= $message_type === 'error' ? '#fdecea' : '#ecfdf3' ?>;
                            color: <?= $message_type === 'error' ? '#b42318' : '#027a48' ?>;
                            text-align: center;
                        "
                    >
                        <?= htmlspecialchars($message) ?>
                    </div>
                </div>
            <?php endif; ?>


            <form method="POST" class="form auth__form auth__form-forgot">
                <div class="form__group">
                    <div class="form__text-input">
                        <input
                            type="email"
                            name="email"
                            placeholder="Email đã đăng ký"
                            class="form__input"
                            value="<?= htmlspecialchars($old_email) ?>"
                            required
                        />
                        <img src="./assets/icons/message.svg" class="form__input-icon" />
                    </div>
                </div>

                <div class="form__group auth__btn-group">
                    <button
                        type="submit"
                        class="btn btn--primary auth__btn form__submit-btn"
                        onclick="this.disabled=true; this.form.submit();"
                    >
                        Tiếp tục
                    </button>
                </div>
            </form>

            <p class="auth__text">
                <a href="./sign-in.php" class="auth__link auth__text-link">
                    Quay lại đăng nhập
                </a>
            </p>
        </div>
    </div>
</main>

<script>
    window.dispatchEvent(new Event("template-loaded"));
</script>
</body>
</html>
