<?php
session_start();
require_once 'db_connect.php';

/* =========================
   1. CHẶN TRUY CẬP TRỰC TIẾP
   ========================= */
if (
    !isset($_SESSION['reset_email']) ||
    !isset($_SESSION['allow_new_password']) ||
    $_SESSION['allow_new_password'] !== true
) {
    header("Location: reset-password-emailed.php");
    exit;
}

$email = $_SESSION['reset_email'];

/* =========================
   2. KIỂM TRA EMAIL TỒN TẠI
   ========================= */
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: reset-password-emailed.php");
    exit;
}

/* =========================
   3. XỬ LÝ SUBMIT FORM
   ========================= */
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if ($password === '' || $confirm === '') {
        $error = "Vui lòng nhập đầy đủ mật khẩu.";
    }
    elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    }
    elseif (
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W_]/', $password)
    ) {
        $error = "Mật khẩu phải chứa chữ hoa, chữ thường, số và ít nhất 1 ký tự đặc biệt.";
    }
    elseif ($password !== $confirm) {
        $error = "Xác nhận mật khẩu không khớp.";
    }
    else {
        // ❗ MD5 theo yêu cầu
        $hashed_password = md5($password);

        $update = $conn->prepare(
            "UPDATE users SET password = ? WHERE email = ? LIMIT 1"
        );
        $update->bind_param("ss", $hashed_password, $email);

        if ($update->execute() && $update->affected_rows > 0) {

            // RESET XONG → XÓA SESSION
            unset(
                $_SESSION['reset_email'],
                $_SESSION['allow_new_password'],
                $_SESSION['verify_attempts'],
                $_SESSION['lock_until']
            );

            header("Location: sign-in.php");
            exit;
        } else {
            $error = "Không thể cập nhật mật khẩu. Vui lòng thử lại.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tạo mật khẩu mới | Grocery Mart</title>

    <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />
    <link rel="stylesheet" href="./assets/css/main.css" />
    <script src="./assets/js/scripts.js"></script>
</head>
<body>
<main class="auth">
    <div class="auth__intro d-md-none">
        <img src="./assets/img/auth/forgot-password.png" alt="" class="auth__intro-img" />
    </div>

    <div class="auth__content">
        <div class="auth__content-inner">
            <a href="./" class="logo">
                <img src="./assets/icons/logo.svg" alt="grocerymart" class="logo__img" />
                <h2 class="logo__title">Grocerymart</h2>
            </a>

            <h1 class="auth__heading">Tạo mật khẩu mới</h1>
    
            <?php if (!empty($error)): ?>
                <p class="form__error" style="display:block;">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <form method="post" class="form auth__form auth__form-forgot">

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
                            style="cursor:pointer"
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
                            style="cursor:pointer"
                        />
                    </div>
                </div>

                <div class="form__group auth__btn-group">
                    <button class="btn btn--primary auth__btn form__submit-btn">
                        Đặt lại mật khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- HIỆN / ẨN MẬT KHẨU -->
<script>
document.querySelectorAll('.js-toggle-password').forEach(icon => {
    icon.addEventListener('click', () => {
        const input = document.getElementById(icon.dataset.target);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
    });
});
</script>

<script>
    window.dispatchEvent(new Event("template-loaded"));
</script>
</body>
</html>
