<?php
session_start();
require_once __DIR__ . "/db_connect.php";

$message = "";
$message_type = "";
$old_email = "";
$show_verify_form = false;

// Cấu hình giới hạn
$max_attempts = 3;
$lock_time = 30; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $old_email = $email;

    // 1. KIỂM TRA TRẠNG THÁI KHÓA
    if (isset($_SESSION['lock_until']) && time() < $_SESSION['lock_until']) {
        $remaining = $_SESSION['lock_until'] - time();
        $message = "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau $remaining giây.";
        $message_type = "error";
        $show_verify_form = isset($_POST['verify_owner']); 
    } 
    // 2. XỬ LÝ XÁC MINH CÂU HỎI (BƯỚC 2)
    elseif (isset($_POST['verify_owner'])) {
        $ans1 = trim($_POST['security_answer_1'] ?? '');
        $ans2 = trim($_POST['security_answer_2'] ?? '');
        $show_verify_form = true;

        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND security_answer_1 = ? AND security_answer_2 = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "sss", $email, $ans1, $ans2);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($result)) {
            unset($_SESSION['verify_attempts'], $_SESSION['lock_until']);
            header("Location: reset-password.php?email=" . urlencode($email));
            exit;
        } else {
            $_SESSION['verify_attempts'] = ($_SESSION['verify_attempts'] ?? 0) + 1;
            if ($_SESSION['verify_attempts'] >= $max_attempts) {
                $_SESSION['lock_until'] = time() + $lock_time;
                $remaining = $lock_time;
                $message = "Sai quá $max_attempts lần. Bạn bị khóa trong $lock_time giây.";
            } else {
                $remaining_tries = $max_attempts - $_SESSION['verify_attempts'];
                $message = "Thông tin xác minh không chính xác. Còn $remaining_tries lần thử.";
            }
            $message_type = "error";
        }
        mysqli_stmt_close($stmt);
    }
    // 3. KIỂM TRA EMAIL (BƯỚC 1)
    else {
        if ($email === "") {
            $message = "Vui lòng nhập email.";
            $message_type = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/[0-9]/', $email)) {
            $message = "Email phải đúng định dạng và chứa ít nhất 1 chữ số.";
            $message_type = "error";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                $show_verify_form = true;
                $message = "Vui lòng trả lời câu hỏi bảo mật.";
                $message_type = "success";
            } else {
                $message = "Email không tồn tại trong hệ thống.";
                $message_type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quên mật khẩu | Grocery Mart</title>
    <link rel="apple-touch-icon" sizes="76x76" href="./assets/favicon/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="./assets/favicon/favicon-16x16.png" />
    <link rel="manifest" href="./assets/favicon/site.webmanifest" />
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

            <h1 class="auth__heading">Quên mật khẩu</h1>

            <p class="auth__desc">
                <?= $show_verify_form ? "Xác minh danh tính của bạn để tiếp tục." : "Nhập email bạn đã dùng để đăng ký tài khoản. Chúng tôi sẽ xác nhận email và cho phép bạn tạo mật khẩu mới." ?>
            </p>

            <?php if (!empty($message)): ?>
                <div class="auth__message-wrapper">
                    <div class="auth__message message message--<?= $message_type ?>"
                        style="margin-top: 12px; padding: 10px 14px; border-radius: 8px; font-size: 14px; text-align: center;
                            border: 1px solid <?= $message_type === 'error' ? '#f5c2c7' : '#abefc6' ?>;
                            background-color: <?= $message_type === 'error' ? '#fdecea' : '#ecfdf3' ?>;
                            color: <?= $message_type === 'error' ? '#b42318' : '#027a47' ?>;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($show_verify_form): ?>
                <form method="POST" class="form auth__form" style="margin-top:16px;">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($old_email) ?>">
                    
                    <div class="form__group">
                        <div class="form__text-input">
                            <input type="text" name="security_answer_1" placeholder="Sở thích của bạn là gì?" class="form__input" required />
                        </div>
                    </div>

                    <div class="form__group">
                        <div class="form__text-input">
                            <input type="text" name="security_answer_2" placeholder="Năng khiếu của bạn là gì?" class="form__input" required />
                        </div>
                    </div>

                    <div class="form__group auth__btn-group">
                        <button type="submit" name="verify_owner" id="btn-verify" class="btn btn--primary auth__btn form__submit-btn">
                            Xác minh chính chủ
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" class="form auth__form auth__form-forgot">
                    <div class="form__group">
                        <div class="form__text-input">
                            <input type="email" name="email" placeholder="Email đã đăng ký" class="form__input"
                                value="<?= htmlspecialchars($old_email) ?>" required />
                            <img src="./assets/icons/message.svg" class="form__input-icon" />
                        </div>
                    </div>

                    <div class="form__group auth__btn-group">
                        <button type="submit" class="btn btn--primary auth__btn form__submit-btn">
                            Tiếp tục
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <p class="auth__text">
                <a href="./sign-in.php" class="auth__link auth__text-link">Quay lại đăng nhập</a>
            </p>
        </div>
    </div>
</main>

<?php if (isset($remaining) && $remaining > 0): ?>
<script>
    (function() {
        let seconds = <?= $remaining ?>;
        const msg = document.querySelector('.auth__message');
        const btn = document.getElementById('btn-verify');
        if (btn) btn.disabled = true;

        const timer = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
                clearInterval(timer);
                if (msg) msg.innerText = "Bạn có thể thử lại ngay bây giờ.";
                if (btn) btn.disabled = false;
            } else {
                if (msg) msg.innerText = `Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ${seconds} giây.`;
            }
        }, 1000);
    })();
</script>
<?php endif; ?>

<script>window.dispatchEvent(new Event("template-loaded"));</script>
</body>
</html>