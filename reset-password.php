<?php
session_start();
require_once __DIR__ . "/db_connect.php";

$message = "";
$message_type = "";

// Giới hạn
$max_attempts = 3;
$lock_time = 30;

/* ===== CHẶN TRUY CẬP TRỰC TIẾP ===== */
if (!isset($_SESSION['reset_email']) || $_SESSION['reset_email'] === "") {
    header("Location: reset-password-emailed.php");
    exit;
}

$email = $_SESSION['reset_email'];

/* ===== XỬ LÝ POST ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['verify_owner'])) {

    // Nếu đang bị khóa
    if (isset($_SESSION['lock_until']) && time() < $_SESSION['lock_until']) {

        $remaining = $_SESSION['lock_until'] - time();
        $message = "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau $remaining giây.";
        $message_type = "error";

    } else {

        $ans1 = trim($_POST['security_answer_1'] ?? '');
        $ans2 = trim($_POST['security_answer_2'] ?? '');

        if ($ans1 === "" || $ans2 === "") {

            $message = "Vui lòng trả lời đầy đủ các câu hỏi xác minh.";
            $message_type = "error";

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "SELECT id FROM users
                 WHERE email = ?
                 AND security_answer_1 = ?
                 AND security_answer_2 = ?
                 LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, "sss", $email, $ans1, $ans2);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_fetch_assoc($result)) {

                // ĐÚNG → cho sang new-password
                unset($_SESSION['verify_attempts'], $_SESSION['lock_until']);
                $_SESSION['allow_new_password'] = true;

                header("Location: new-password.php");
                exit;

            } else {

                // SAI → tăng số lần thử
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
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Xác minh tài khoản | Grocery Mart</title>

    <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />
    <link rel="stylesheet" href="./assets/css/main.css" />
    <script src="./assets/js/scripts.js"></script>
</head>
<body>

<main class="auth">
    <div class="auth__intro d-md-none">
        <img src="./assets/img/auth/forgot-password.png" class="auth__intro-img" />
    </div>

    <div class="auth__content">
        <div class="auth__content-inner">
            <a href="./" class="logo">
                <img src="./assets/icons/logo.svg" class="logo__img" />
                <h2 class="logo__title">Grocerymart</h2>
            </a>

            <h1 class="auth__heading">Xác minh tài khoản</h1>

            <p class="auth__desc">
                Trả lời câu hỏi bảo mật để xác nhận bạn là chủ tài khoản.
            </p>

            <?php if ($message !== ""): ?>
                <div
                    style="margin:16px 0;padding:12px 16px;border-radius:8px;text-align:center;
                    border:1px solid <?= $message_type === 'error' ? '#f5c2c7' : '#abefc6' ?>;
                    background-color:<?= $message_type === 'error' ? '#fdecea' : '#ecfdf3' ?>;
                    color:<?= $message_type === 'error' ? '#b42318' : '#027a47' ?>;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form auth__form">
                <input type="hidden" name="verify_owner" value="1">

                <div class="form__group">
                    <div class="form__text-input">
                        <input
                            type="text"
                            name="security_answer_1"
                            placeholder="Sở thích của bạn là gì?"
                            class="form__input"
                            required
                        />
                    </div>
                </div>

                <div class="form__group">
                    <div class="form__text-input">
                        <input
                            type="text"
                            name="security_answer_2"
                            placeholder="Năng khiếu của bạn là gì?"
                            class="form__input"
                            required
                        />
                    </div>
                </div>

                <div class="form__group auth__btn-group">
                    <button
                        type="submit"
                        class="btn btn--primary auth__btn"
                        id="btn-verify"
                    >
                        Xác minh chính chủ
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

<?php if (isset($remaining) && $remaining > 0): ?>
<script>
let seconds = <?= (int)$remaining ?>;
let desc = document.querySelector('.auth__desc');
let btn = document.getElementById('btn-verify');
if (btn) btn.disabled = true;

let timer = setInterval(() => {
    seconds--;
    if (seconds <= 0) {
        clearInterval(timer);
        if (desc) desc.innerText = "Bạn có thể thử lại ngay bây giờ.";
        if (btn) btn.disabled = false;
    } else {
        if (desc) desc.innerText =
            "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau " + seconds + " giây.";
    }
}, 1000);
</script>
<?php endif; ?>

<script>window.dispatchEvent(new Event("template-loaded"));</script>
</body>
</html>
