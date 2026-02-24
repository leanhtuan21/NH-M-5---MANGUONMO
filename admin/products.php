<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// --- 1. LẤY DỮ LIỆU ĐẦU VÀO ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Lấy tham số lọc từ URL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// --- 2. XÂY DỰNG CÂU TRUY VẤN (SQL BUILDER) ---
$where_clauses = [];

// Tìm kiếm
if (!empty($search)) {
    $s = $conn->real_escape_string($search); 
    $where_clauses[] = "(p.name LIKE '%$s%' OR p.brand LIKE '%$s%')";
}

// Lọc theo danh mục
if (!empty($cat_filter)) {
    $where_clauses[] = "p.category_id = $cat_filter";
}

// Gộp các điều kiện WHERE
$sql_where = "";
if (count($where_clauses) > 0) {
    $sql_where = "WHERE " . implode(' AND ', $where_clauses);
}

// Xử lý Sắp xếp (ORDER BY)
switch ($sort) {
    case 'price_asc': $sql_order = "ORDER BY p.price ASC"; break;
    case 'price_desc': $sql_order = "ORDER BY p.price DESC"; break;
    case 'name_asc': $sql_order = "ORDER BY p.name ASC"; break;
    case 'name_desc': $sql_order = "ORDER BY p.name DESC"; break;
    default: $sql_order = "ORDER BY p.id DESC"; // Mặc định: Mới nhất
}

// --- 3. THỰC THI TRUY VẤN ---

// Đếm tổng số dòng (để phân trang)
$count_sql = "SELECT COUNT(*) as total FROM products p $sql_where";
$total_rows = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Lấy dữ liệu hiển thị
$sql = "SELECT p.*, c.name as cat_name, 
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as thumbnail 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $sql_where
        $sql_order
        LIMIT $offset, $limit";
$result = $conn->query($sql);

// Lấy danh sách danh mục để đổ vào Dropdown lọc
$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Lấy message từ session (nếu có)
$msg = '';
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>

<div class="header-bar mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Danh sách sản phẩm</h4>
        <p class="text-muted mb-0">Tìm thấy <?php echo $total_rows; ?> kết quả</p>
    </div>
    <a href="product_add.php" class="btn btn-primary shadow-sm px-4">
        <i class="fas fa-plus me-2"></i>Thêm sản phẩm
    </a>
</div>

<?php echo $msg; ?>

<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0" placeholder="Tên sản phẩm, thương hiệu..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>

            <div class="col-md-3">
                <select name="cat" class="form-select bg-light border-0">
                    <option value="">-- Tất cả danh mục --</option>
                    <?php 
                    $cats->data_seek(0); 
                    while($c = $cats->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($cat_filter == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo $c['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-3">
                <select name="sort" class="form-select bg-light border-0">
                    <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                    <option value="price_asc" <?php echo ($sort == 'price_asc') ? 'selected' : ''; ?>>Giá: Thấp -> Cao</option>
                    <option value="price_desc" <?php echo ($sort == 'price_desc') ? 'selected' : ''; ?>>Giá: Cao -> Thấp</option>
                    <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Tên: A -> Z</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-filter"></i> Lọc
                </button>
                <?php if(!empty($search) || !empty($cat_filter) || $sort != 'newest'): ?>
                    <a href="products.php" class="btn btn-light border w-auto" title="Xóa bộ lọc">
                        <i class="fas fa-undo text-danger"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0 align-middle table-hover">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4 py-3">Sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá bán</th>
                    <th>Tồn kho</th>
                    <th class="text-end pe-4">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $img = $row['thumbnail'] ? '../' . $row['thumbnail'] : '../assets/img/product/no-image.png';
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded border p-1 me-3 bg-white" style="width: 48px; height: 48px;">
                                    <img src="<?php echo $img; ?>" width="100%" height="100%" style="object-fit: contain;">
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark"><?php echo $row['name']; ?></h6>
                                    <small class="text-muted"><?php echo $row['brand']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border fw-normal">
                                <?php echo $row['cat_name'] ?? 'Chưa phân loại'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-success">$<?php echo number_format($row['price'], 2); ?></span>
                        </td>
                        <td>
                            <?php if($row['stock_quantity'] > 10): ?>
                                <span class="fw-bold text-dark"><?php echo $row['stock_quantity']; ?></span>
                            <?php elseif($row['stock_quantity'] > 0): ?>
                                <span class="fw-bold text-warning"><?php echo $row['stock_quantity']; ?> (Thấp)</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger">Hết hàng</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <a href="product_view.php?id=<?php echo $row['id']; ?>" class="btn btn-light text-info btn-sm me-1" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>

                            <a href="product_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-light text-primary btn-sm me-1" title="Sửa">
                                <i class="fas fa-pen"></i>
                            </a>

                            <a href="product_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-light text-danger btn-sm" title="Xóa" onclick="return confirm('Xóa sản phẩm này?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted opacity-50">
                                <i class="fas fa-search fa-3x mb-3"></i>
                                <p>Không tìm thấy sản phẩm phù hợp.</p>
                                <a href="products.php" class="btn btn-sm btn-outline-secondary mt-2">Xóa bộ lọc</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): 
        // Tạo chuỗi query string để giữ lại bộ lọc khi chuyển trang
        $query_params = $_GET;
        unset($query_params['page']); // Bỏ page cũ đi
        $query_str = http_build_query($query_params);
    ?>
    <div class="card-footer bg-white py-3 border-top-0 d-flex justify-content-end">
        <nav>
            <ul class="pagination pagination-sm mb-0 gap-1">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link rounded <?php echo ($page == $i) ? 'bg-primary border-primary text-white' : 'text-dark border-0 bg-light'; ?>" 
                           href="?page=<?php echo $i; ?>&<?php echo $query_str; ?>" 
                           style="min-width: 32px; text-align: center; font-weight: 600;">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>