<?php
session_start();
require_once 'db_connect.php';

/* ===============================
1. CHECK LOGIN
=============================== */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId    = $_SESSION['user_id']; // test = 9
$productId = (int)($_GET['product_id'] ?? 0);
$error     = '';

if ($productId <= 0) {
    die("Thiếu product_id");
}

/* ===============================
2. CHECK PRODUCT TỒN TẠI
=============================== */
$stmt = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Sản phẩm không tồn tại");
}

/* ===============================
3. CHECK QUYỀN ĐÁNH GIÁ
=============================== */
$sql = "
    SELECT COUNT(*) AS total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
      AND oi.product_id = ?
      AND o.status = 'completed'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userId, $productId);
$stmt->execute();
$canReview = $stmt->get_result()->fetch_assoc()['total'];

if ($canReview == 0) {
    die("Bạn chưa mua hoặc chưa nhận sản phẩm này.");
}

/* ===============================
4. CHECK REVIEW TRÙNG
=============================== */
$stmt = $conn->prepare("
    SELECT id FROM product_reviews 
    WHERE user_id = ? AND product_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $userId, $productId);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    die("Bạn đã đánh giá sản phẩm này rồi.");
}

/* ===============================
5. HANDLE SUBMIT
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = "Vui lòng chọn số sao từ 1 đến 5.";
    } else {

        /* ===============================
        LẤY order_id CỦA ĐƠN completed
        =============================== */
        $stmtOrder = $conn->prepare("
            SELECT o.id 
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
              AND oi.product_id = ?
              AND o.status = 'completed'
            LIMIT 1
        ");
        $stmtOrder->bind_param("ii", $userId, $productId);
        $stmtOrder->execute();
        $orderRow = $stmtOrder->get_result()->fetch_assoc();

        if (!$orderRow) {
            $error = "Không tìm thấy đơn hàng hợp lệ.";
        } else {

            $orderId = $orderRow['id'];

            $conn->begin_transaction();

            try {

                /* ===============================
                INSERT REVIEW (ĐÃ THÊM order_id)
                =============================== */
                $stmt = $conn->prepare("
                    INSERT INTO product_reviews 
                    (product_id, user_id, order_id, rating, comment)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiiis", $productId, $userId, $orderId, $rating, $comment);
                $stmt->execute();
                $reviewId = $stmt->insert_id;

                /* ===============================
                UPLOAD ẢNH (KHÔNG BẮT BUỘC)
                =============================== */
                if (!empty($_FILES['images']['name'][0])) {

                    $imageNames = array_filter($_FILES['images']['name']);

                    if (count($imageNames) > 3) {
                        throw new Exception("Chỉ được upload tối đa 3 ảnh.");
                    }

                    $allowExt  = ['jpg','jpeg','png','webp'];
                    $uploadDir = "./uploads/reviews/";
                    $maxSize   = 2 * 1024 * 1024;

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {

                        if ($tmpName === '') continue;

                        $size = $_FILES['images']['size'][$key];
                        $name = $_FILES['images']['name'][$key];

                        if ($size > $maxSize) {
                            throw new Exception("Mỗi ảnh tối đa 2MB.");
                        }

                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowExt)) {
                            throw new Exception("Chỉ chấp nhận jpg, jpeg, png, webp.");
                        }

                        $mime = mime_content_type($tmpName);
                        if (strpos($mime, 'image/') !== 0) {
                            throw new Exception("File không hợp lệ.");
                        }

                        $fileName = uniqid("review_") . "." . $ext;
                        $filePath = $uploadDir . $fileName;

                        if (!move_uploaded_file($tmpName, $filePath)) {
                            throw new Exception("Upload ảnh thất bại.");
                        }

                        $stmtImg = $conn->prepare("
                            INSERT INTO review_images (review_id, image_path)
                            VALUES (?, ?)
                        ");
                        $stmtImg->bind_param("is", $reviewId, $filePath);
                        $stmtImg->execute();
                    }
                }

                /* ===============================
                UPDATE ĐIỂM TRUNG BÌNH
                =============================== */
                $stmt = $conn->prepare("
                    SELECT ROUND(AVG(rating),1) AS avg_rating
                    FROM product_reviews
                    WHERE product_id = ?
                ");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $avgRating = $stmt->get_result()->fetch_assoc()['avg_rating'] ?? 0;

                $stmt = $conn->prepare("
                    UPDATE products SET average_score = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("di", $avgRating, $productId);
                $stmt->execute();

                $conn->commit();

                header("Location: product-detail.php?id=$productId#reviews");
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đánh giá sản phẩm</title>

<!-- Favicon -->
<link rel="icon" href="./assets/favicon/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="./assets/favicon/favicon-16x16.png">
<link rel="apple-touch-icon" href="./assets/favicon/apple-touch-icon.png">

<!-- Fonts + Core CSS -->
<link rel="stylesheet" href="./assets/fonts/stylesheet.css" />
<link rel="stylesheet" href="./assets/css/main.css" />
<link rel="stylesheet" href="./assets/css/panagition.css" />

<!-- Scripts (load header + icons) -->
<script src="./assets/js/scripts.js"></script>

<style>
/* ==== CHỈ STYLE FORM REVIEW (KHÔNG ĐỤNG HEADER) ==== */

.review-wrap{
    padding:50px 0;
}

.review-box{
    max-width:600px;
    margin:0 auto;
    background:#fff;
    border-radius:16px;
    padding:30px;
    box-shadow:0 12px 35px rgba(0,0,0,0.08);
}

/* Title */
.review-title{
    font-size:20px;
    font-weight:600;
    margin-bottom:18px;
}

/* Stars */
.star-row{
    display:flex;
    gap:8px;
    margin-bottom:14px;
}

.star{
    font-size:30px;
    color:#ddd;
    cursor:pointer;
    transition:.2s;
}

.star:hover{
    transform:scale(1.2);
}

.star.active{
    color:#ffc107;
}

/* Textarea */
.review-box textarea{
    width:100%;
    border:1px solid #e0e0e0;
    border-radius:12px;
    padding:14px;
    font-size:14px;
    resize:none;
    transition:.2s;
}

.review-box textarea:focus{
    border-color:#1677ff;
    box-shadow:0 0 0 3px rgba(22,119,255,.1);
    outline:none;
}

/* Upload */
.upload-label{
    margin-top:16px;
    font-size:13px;
    color:#666;
}

/* Preview */
.preview{
    display:flex;
    gap:10px;
    margin-top:10px;
    margin-bottom:18px;
}

.preview img{
    width:70px;
    height:70px;
    border-radius:10px;
    object-fit:cover;
    border:1px solid #eee;
}

/* Button */
.btn-review{
    width:100%;
    padding:13px;
    border:none;
    border-radius:12px;
    background:linear-gradient(90deg,#1677ff,#0f5edb);
    color:#fff;
    font-weight:600;
    font-size:15px;
    cursor:pointer;
    transition:.2s;
}

.btn-review:hover{
    transform:translateY(-1px);
    box-shadow:0 6px 18px rgba(22,119,255,.35);
}
</style>
</head>

<body>

<!-- HEADER GIỮ NGUYÊN -->
<header id="header" class="header"></header>
<script>
document.addEventListener("DOMContentLoaded", function () {
    load("#header", "./templates/header-logined.php");
});
</script>

<!-- REVIEW -->
<div class="review-wrap">
<div class="review-box">

<div class="review-title">
Đánh giá: <?= htmlspecialchars($product['name']) ?>
</div>

<?php if ($error): ?>
<div style="color:#d60000;margin-bottom:12px;">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

<!-- STARS -->
<div class="star-row">
<?php for ($i=1;$i<=5;$i++): ?>
<span class="star">★</span>
<?php endfor; ?>
<input type="hidden" name="rating" id="rating" required>
</div>

<!-- COMMENT -->
<textarea name="comment" rows="4"
placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."></textarea>

<!-- UPLOAD -->
<div class="upload-label">
Thêm ảnh (tối đa 3 ảnh, mỗi ảnh ≤ 2MB)
</div>
<input type="file" name="images[]" multiple id="imgInput">

<div class="preview" id="preview"></div>

<button class="btn-review">Gửi đánh giá</button>

</form>
</div>
</div>

<script>
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('rating');

stars.forEach((star,index)=>{
    star.onclick=()=>{
        ratingInput.value=index+1;
        stars.forEach(s=>s.classList.remove('active'));
        for(let i=0;i<=index;i++){
            stars[i].classList.add('active');
        }
    };
});

/* Preview ảnh */
imgInput.onchange=e=>{
    preview.innerHTML='';
    [...e.target.files].slice(0,3).forEach(file=>{
        const img=document.createElement('img');
        img.src=URL.createObjectURL(file);
        preview.appendChild(img);
    });
};
</script>

</body>
</html>