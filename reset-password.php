<?php
session_start();
require_once __DIR__ . "/db_connect.php";

$message = "";
$message_type = "";

/* ===== EMAIL LẤY TỪ URL ===== */
$email = trim($_GET['email'] ?? '');

/* ===== I. CHẶN TRUY CẬP TRỰC TIẾP ===== */
if ($email !== "") {
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    $rs = mysqli_stmt_get_result($check);

    if (!mysqli_fetch_assoc($rs)) {
        header("Location: reset-password-emailed.php");
        exit;
    }
    mysqli_stmt_close($check);
} else {
    header("Location: reset-password-emailed.php");
    exit;
}

/* ===== II. XỬ LÝ POST ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = trim($_POST["password"] ?? "");
    $confirm_password = trim($_POST["confirm_password"] ?? "");

    if ($password === "" || $confirm_password === "") {
        $message = "Vui lòng nhập đầy đủ mật khẩu.";
        $message_type = "error";
    }
    elseif (
        strlen($password) < 6 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W]/', $password)
    ) {
        $message = "Mật khẩu phải có ít nhất 6 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt.";
        $message_type = "error";
    }
    elseif ($password !== $confirm_password) {
        $message = "Xác nhận mật khẩu không khớp.";
        $message_type = "error";
    }
    else {
        $hashed_password = md5($password);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users SET password = ? WHERE email = ?"
        );
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_errno($stmt) === 0 && mysqli_stmt_affected_rows($stmt) > 0) {
            header("Location: sign-in.php");
            exit;
        } else {
            $message = "Không thể cập nhật mật khẩu. Vui lòng thử lại.";
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
    <title>Đặt lại mật khẩu | Grocery Mart</title>

    <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />
    <link rel="stylesheet" href="./assets/css/main.css" />
    <script src="./assets/js/scripts.js"></script>
</head>
<body>
<main class="auth">
    <div class="auth__intro d-md-none">
        <img src="./assets/img/auth/reset-password.png" alt="" class="auth__intro-img" />
    </div>

    <div class="auth__content">
        <div class="auth__content-inner">
            <a href="./" class="logo">
                <img src="./assets/icons/logo.svg" alt="grocerymart" class="logo__img" />
                <h2 class="logo__title">Grocerymart</h2>
            </a>

            <h1 class="auth__heading">Đặt lại mật khẩu</h1>

            <p class="auth__desc">
                Vui lòng tạo mật khẩu mới cho tài khoản của bạn.
                Mật khẩu mới sẽ được sử dụng cho các lần đăng nhập tiếp theo.
            </p>

            <?php if (!empty($message)): ?>
                <div
                    style="
                        margin: 16px 0 20px;
                        padding: 12px 16px;
                        border-radius: 8px;
                        text-align: center;
                        font-size: 14px;
                        line-height: 1.5;
                        border: 1px solid <?= $message_type === 'error' ? '#f5c2c7' : '#abefc6' ?>;
                        background-color: <?= $message_type === 'error' ? '#fdecea' : '#ecfdf3' ?>;
                        color: <?= $message_type === 'error' ? '#b42318' : '#027a48' ?>;
                    "
                >
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>


            <form action="" method="POST" class="form auth__form">
                <div class="form__group">
                    <div class="form__text-input">
                        <input
                            type="email"
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="Email tài khoản"
                            class="form__input"
                            readonly
                        />
                        <img src="./assets/icons/message.svg" class="form__input-icon" />
                    </div>
                </div>

                <div class="form__group">
                    <div class="form__text-input">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Mật khẩu mới"
                            class="form__input"
                            required
                        />
                        <img
                            src="./assets/icons/lock.svg"
                            class="form__input-icon js-toggle-password"
                            data-target="password"
                            style="cursor: pointer;"
                        />
                    </div>
                </div>

                <div class="form__group">
                    <div class="form__text-input">
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            placeholder="Xác nhận mật khẩu mới"
                            class="form__input"
                            required
                        />
                        <img
                            src="./assets/icons/lock.svg"
                            class="form__input-icon js-toggle-password"
                            data-target="confirm_password"
                            style="cursor: pointer;"
                        />
                    </div>
                </div>

                <!-- Ẩn hiện mật khẩu -->
                <script>
                    document.querySelectorAll('.js-toggle-password').forEach(icon => {
                        icon.addEventListener('click', () => {
                            const input = document.getElementById(icon.dataset.target);
                            if (!input) return;
                            input.type = input.type === 'password' ? 'text' : 'password';
                        });
                    });
                </script>

                <div class="form__group auth__btn-group">
                    <button
                        type="submit"
                        class="btn btn--primary auth__btn"
                        onclick="this.disabled=true; this.form.submit();"
                    >
                        Cập nhật mật khẩu
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