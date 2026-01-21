<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: reset-password-emailed.php");
    exit;
}

$email = trim($_POST["email"] ?? "");

if (empty($email)) {
    $_SESSION['otp_notice'] = "Vui lòng nhập email.";
    header("Location: reset-password-emailed.php");
    exit;
}

$conn = mysqli_connect("localhost", "root", "", "grocery_mart_db");
if (!$conn) {
    die("Không thể kết nối CSDL");
}


$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);


if (!$user) {
    $_SESSION['otp_notice'] = "Nếu email tồn tại trong hệ thống, mã OTP đã được gửi.";
    header("Location: reset-password.php?email=" . urlencode($email));
    exit;
}

$otp = random_int(100000, 999999);
$expired_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));


$update = mysqli_prepare(
    $conn,
    "UPDATE users 
     SET reset_otp = ?, reset_otp_expired_at = ?
     WHERE email = ?"
);
mysqli_stmt_bind_param($update, "sss", $otp, $expired_at, $email);
mysqli_stmt_execute($update);


$_SESSION['otp_notice'] = "Mã OTP đã được gửi về email của bạn. Vui lòng nhập OTP để đặt lại mật khẩu.";

header("Location: reset-password.php?email=" . urlencode($email));
exit;
