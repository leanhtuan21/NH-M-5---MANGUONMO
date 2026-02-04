<?php
$error = "";
$success = "";
$email_value = ""; 
$full_name_value = ""; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    require_once __DIR__ . "/db_connect.php";

    $full_name = trim($_POST['full_name'] ?? ''); 
    $full_name_value = $full_name;  
    $email = trim($_POST['email'] ?? '');
    $email_value = $email; 

    // --- CẬP NHẬT: Thêm trim() cho mật khẩu ---
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    $terms = $_POST['terms'] ?? '';

    // 1. Kiểm tra rỗng và Checkbox
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Vui lòng nhập đầy đủ thông tin";
    } 
    // 2. Kiểm tra định dạng email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không đúng định dạng";
    }
    //Kiểm tra email phải chứa số
    elseif (!preg_match('/[0-9]/', $email)) {
        $error = "Email phải chứa ít nhất 1 chữ số";
    }
    // 3. Kiểm tra độ mạnh mật khẩu
    elseif (
        strlen($password) < 6 ||
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[0-9]/', $password) || 
        !preg_match('/[\W]/', $password) 
    ) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt";
    }
    // 4. Kiểm tra xác nhận mật khẩu
    elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp";
    }
    else {
        // 5. Kiểm tra email đã tồn tại
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email này đã được đăng ký";
        } else {
            // 6. Xử lý lưu mật khẩu và role
            $hashedPassword = md5($password);
            
            // Định nghĩa role mặc định
            $role_default = 'user'; 

            // CÂU LỆNH INSERT
            $stmt = $conn->prepare(
                "INSERT INTO users (full_name, email, password, role)
                VALUES (?, ?, ?, ?)"
            );

            // BIND_PARAM
            $stmt->bind_param("ssss", $full_name, $email, $hashedPassword, $role_default);

            if ($stmt->execute()) {
                $success = "Đăng ký thành công! Đang chuyển sang trang đăng nhập...";
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
    <title>Sign up | Coffee Shop</title>
    <link rel="stylesheet" href="./assets/css/main.css" />

    <style>
        .alert { padding: 12px; border-radius: 8px; font-size: 1.4rem; font-weight: 500; margin-bottom: 20px; text-align: center; }
        .alert-danger { background-color: #ffe5e5; color: #d32f2f; border: 1px solid #ffcccc; }
        .alert-success { background-color: #e5ffe5; color: #2e7d32; border: 1px solid #ccffcc; }
        .form__text-input { position: relative; display: flex; align-items: center; }
        .form__input { padding-right: 45px !important; }
        .form__input-icon-eye { position: absolute; right: 12px; padding: 10px; z-index: 2; cursor: pointer; width: 20px; }
    </style>
</head>
<body>
    <main class="auth">
        <div class="auth__intro">
            <img src="./assets/img/auth/intro.svg" alt="" class="auth__intro-img" />
            <p class="auth__intro-text">
                    Khám phá hương vị cà phê nguyên chất, sản phẩm thượng hạng và trải nghiệm mua sắm tuyệt vời nhất
            </p>
        </div>

        <div id="auth-content" class="auth__content hide">
            <div class="auth__content-inner">
                <a href="./" class="logo">
                    <img src="./assets/icons/logo.svg" alt="grocerymart" class="logo__img" />
                    <h2 class="logo__title">Coffee Shop</h2>
                </a>
                <h1 class="auth__heading">Đăng ký</h1>
                <p class="auth__desc">Bắt đầu hành trình khám phá hương vị cà phê nguyên bản.</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" class="form auth__form">
                    <div class="form__group">
                        <div class="form__text-input">
                            <input type="text" name="full_name" placeholder="Họ và tên" class="form__input" required value="<?php echo htmlspecialchars($full_name_value); ?>" />
                            <img src="./assets/icons/message.svg" alt="" class="form__input-icon" style="filter: brightness(0) saturate(100%) invert(50%);"/>
                        </div>
                    </div>

                    <div class="form__group">
                        <div class="form__text-input">
                            <input type="email" name="email" placeholder="Email" class="form__input" required value="<?php echo htmlspecialchars($email_value); ?>" />
                            <img src="./assets/icons/message.svg" alt="" class="form__input-icon" />
                        </div>
                    </div>

                    <div class="form__group">
                        <div class="form__text-input">
                            <input type="password" name="password" id="password" placeholder="Mật khẩu" class="form__input" required />
                            <img src="./assets/icons/lock.svg" alt="" class="form__input-icon" />
                            <img src="./assets/icons/eye.svg" alt="" class="form__input-icon-eye js-toggle-password" data-target="password" />
                        </div>
                    </div>

                    <div class="form__group">
                        <div class="form__text-input">
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Xác nhận mật khẩu" class="form__input" required />
                            <img src="./assets/icons/lock.svg" alt="" class="form__input-icon" />
                            <img src="./assets/icons/eye.svg" alt="" class="form__input-icon-eye js-toggle-password" data-target="confirm_password" />
                        </div>
                    </div>
                    <div class="form__group auth__btn-group">
                        <button class="btn btn--primary auth__btn">Đăng ký</button>
                    </div>
                </form>

                <p class="auth__text">
                    Bạn đã có tài khoản chưa? <a href="./sign-in.php" class="auth__link">Đăng nhập</a>
                </p>
            </div>
        </div>
    </main>

    <script>
        document.querySelectorAll('.js-toggle-password').forEach(item => {
            item.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });
    </script>
</body>
</html>