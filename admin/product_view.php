<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

if (!isset($_GET['id'])) header("Location: products.php");
$id = (int)$_GET['id'];

// 1. Lấy thông tin sản phẩm chung
$sql = "SELECT p.*, c.name as cat_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = $id";
$result = $conn->query($sql);
$prod = $result->fetch_assoc();

if (!$prod) die("Không tìm thấy sản phẩm");

// 2. Lấy danh sách biến thể từ bảng product_weights (Mới)
// Sắp xếp theo trọng lượng tăng dần để dễ nhìn
$var_sql = "SELECT * FROM product_weights WHERE product_id = $id ORDER BY weight_gram ASC";
$variants = $conn->query($var_sql);

// 3. Lấy danh sách ảnh
$img_res = $conn->query("SELECT * FROM product_images WHERE product_id = $id ORDER BY is_main DESC");
$images = [];
while($row = $img_res->fetch_assoc()) {
    $images[] = $row;
}
?>

<div class="header-bar mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Chi tiết sản phẩm</h4>
        <p class="text-muted mb-0">ID: #<?php echo $id; ?> - <?php echo htmlspecialchars($prod['name']); ?></p>
    </div>
    <div>
        <a href="products.php" class="btn btn-light border shadow-sm me-2">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
        <a href="product_edit.php?id=<?php echo $id; ?>" class="btn btn-primary px-4">
            <i class="fas fa-pen me-2"></i>Chỉnh sửa
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold text-uppercase text-muted mb-3 font-size-sm">Thư viện hình ảnh</h6>
                
                <?php if (count($images) > 0): ?>
                    <div class="mb-3 p-2 border rounded bg-light text-center">
                        <img id="main-view-img" src="../<?php echo $images[0]['image_url']; ?>" 
                             style="max-width: 100%; max-height: 350px; object-fit: contain;">
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <?php foreach($images as $img): ?>
                            <img src="../<?php echo $img['image_url']; ?>" 
                                 class="rounded border cursor-pointer hover-shadow" 
                                 style="width: 70px; height: 70px; object-fit: cover; cursor: pointer;"
                                 onclick="changeImage(this.src)">
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-image fa-3x mb-3 opacity-25"></i>
                        <p>Sản phẩm này chưa có hình ảnh.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-primary bg-opacity-10 text-primary mb-2 border border-primary border-opacity-25">
                            <?php echo $prod['cat_name'] ?? 'Chưa phân loại'; ?>
                        </span>
                        <h3 class="fw-bold text-dark mb-1"><?php echo $prod['name']; ?></h3>
                        <p class="text-muted mb-0">Thương hiệu: <span class="fw-bold text-dark"><?php echo $prod['brand']; ?></span></p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Giá khởi điểm từ</small>
                        <h3 class="fw-bold text-success mb-0">$<?php echo number_format($prod['price'], 2); ?></h3>
                        <small class="text-muted">Thuế VAT: <?php echo $prod['tax_percent']; ?>%</small>
                    </div>
                </div>

                <hr class="bg-light">

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4">
                        <div class="p-3 bg-light rounded border-start border-4 border-primary">
                            <small class="text-muted d-block text-uppercase font-size-sm fw-bold">Tổng Tồn kho</small>
                            <span class="h5 fw-bold text-dark mb-0"><?php echo $prod['stock_quantity']; ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 bg-light rounded border-start border-4 border-success">
                            <small class="text-muted d-block text-uppercase font-size-sm fw-bold">Đánh giá</small>
                            <span class="h5 fw-bold text-dark mb-0"><?php echo $prod['average_score']; ?> <i class="fas fa-star text-warning small"></i></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 bg-light rounded border-start border-4 border-info">
                            <small class="text-muted d-block text-uppercase font-size-sm fw-bold">Ngày tạo</small>
                            <span class="h6 fw-bold text-dark mb-0"><?php echo date('d/m/Y', strtotime($prod['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-uppercase text-muted font-size-sm mb-3">Chi tiết giá & Tồn kho theo khối lượng</h6>
                <?php if ($variants->num_rows > 0): ?>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm align-middle text-center mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th style="width: 30%">Loại</th>
                                    <th style="width: 40%">Giá bán</th>
                                    <th style="width: 30%">Tồn kho</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($var = $variants->fetch_assoc()): 
                                    // Xử lý hiển thị Gram/Kg
                                    $gram = (int)$var['weight_gram'];
                                    $display_weight = ($gram >= 1000) ? ($gram / 1000) . ' kg' : $gram . ' g';
                                ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo $display_weight; ?></td>
                                    <td class="text-success fw-bold">$<?php echo number_format($var['price'], 2); ?></td>
                                    <td>
                                        <?php if($var['stock_quantity'] > 0): ?>
                                            <span class="text-dark fw-bold"><?php echo $var['stock_quantity']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger">Hết hàng</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning small mb-4">Sản phẩm này chưa có thông tin biến thể.</div>
                <?php endif; ?>

                <h6 class="fw-bold text-uppercase text-muted font-size-sm mb-2">Mô tả sản phẩm</h6>
                <div class="bg-light p-3 rounded text-secondary" style="min-height: 150px; line-height: 1.6; white-space: pre-line;">
                    <?php echo $prod['description']; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Hàm đổi ảnh khi click vào ảnh nhỏ
function changeImage(src) {
    document.getElementById('main-view-img').src = src;
}
</script>

<?php require_once 'includes/footer.php'; ?>