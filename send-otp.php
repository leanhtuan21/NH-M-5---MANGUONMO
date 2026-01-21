<?php
session_start();

/* ===== CHỈ CHO PHÉP POST ===== */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: reset-password-emailed.php");
    exit;
}

/* ===== LẤY EMAIL ===== */
$email = trim($_POST["email"] ?? "");
if ($email === "") {
    $_SESSION['otp_notice'] = "Vui lòng nhập email.";
    header("Location: reset-password-emailed.php");
    exit;
}

/* ===== KẾT NỐI DB ===== */
$conn = mysqli_connect("localhost", "root", "", "grocery_mart_db");
if (!$conn) {
    die("Không thể kết nối CSDL");
}

/* ===== KIỂM TRA USER TỒN TẠI ===== */
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

/* ===== KHÔNG TIẾT LỘ EMAIL ===== */
if (!$user) {
    $_SESSION['otp_notice'] =
        "Nếu email tồn tại trong hệ thống, mã OTP sẽ được hiển thị tại đây.";
    header("Location: reset-password.php?email=" . urlencode($email));
    exit;
}

/* ===== SINH OTP MỚI ===== */
$otp = random_int(100000, 999999);

/* OTP hết hạn sau 1 phút */
$expired_at = date("Y-m-d H:i:s", time() + 60);

/* ===== UPDATE OTP VÀO DB ===== */
$update = mysqli_prepare(
    $conn,
    "UPDATE users
     SET reset_otp = ?, reset_otp_expired_at = ?
     WHERE email = ?"
);

/* i = INT (otp), s = STRING */
mysqli_stmt_bind_param($update, "iss", $otp, $expired_at, $email);
mysqli_stmt_execute($update);

/* ===== KIỂM TRA UPDATE ===== */
if (mysqli_stmt_affected_rows($update) !== 1) {
    $_SESSION['otp_notice'] = "Không thể tạo OTP. Vui lòng thử lại.";
    header("Location: reset-password-emailed.php");
    exit;
}

/* ===== DEV MODE: HIỂN THỊ OTP ===== */
$_SESSION['otp_notice'] =
    "Mã OTP của bạn là: <strong>$otp</strong><br>
     Mã có hiệu lực trong <strong>1 phút</strong>.";

/* ===== DỌN DẸP ===== */
mysqli_stmt_close($stmt);
mysqli_stmt_close($update);
mysqli_close($conn);

/* ===== CHUYỂN SANG TRANG NHẬP OTP ===== */
header("Location: reset-password.php?email=" . urlencode($email));
exit;
