<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

if (!isset($_GET['id'])) header("Location: products.php");
$id = (int)$_GET['id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $desc = $_POST['description'];
    $tax = $_POST['tax_percent'];
    
    // 1. Xử lý danh mục
    $cat_id = !empty($_POST['category_id']) ? $_POST['category_id'] : NULL;
    if (!empty($_POST['new_category_name'])) {
        $new_cat = trim($_POST['new_category_name']);
        $check = $conn->query("SELECT id FROM categories WHERE name = '$new_cat'");
        if ($check->num_rows > 0) $cat_id = $check->fetch_assoc()['id'];
        else { $conn->query("INSERT INTO categories (name) VALUES ('$new_cat')"); $cat_id = $conn->insert_id; }
    }

    // 2. XỬ LÝ BIẾN THỂ (WEIGHTS)
    $v_weights = $_POST['var_weight'] ?? [];
    $v_units   = $_POST['var_unit'] ?? [];
    $v_prices  = $_POST['var_price'] ?? [];
    $v_stocks  = $_POST['var_stock'] ?? [];

    $valid_variants = [];
    $total_stock = 0;
    $min_price = 0;

    for ($i = 0; $i < count($v_weights); $i++) {
        $val = (float)$v_weights[$i];
        $unit = $v_units[$i];
        $price = (float)$v_prices[$i];
        $stock = (int)$v_stocks[$i];

        if ($val > 0 && $price > 0) {
            $gram_value = ($unit == 'kg') ? ($val * 1000) : $val;
            $valid_variants[] = ['gram' => $gram_value, 'price' => $price, 'stock' => $stock];
            $total_stock += $stock;
            if ($min_price == 0 || $price < $min_price) $min_price = $price;
        }
    }

    if (empty($valid_variants)) {
        $msg = "<div class='alert alert-danger'>Phải có ít nhất một loại trọng lượng hợp lệ!</div>";
    } else {
        // 3. CẬP NHẬT BẢNG CHÍNH (products)
        $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, brand=?, price=?, tax_percent=?, stock_quantity=?, description=? WHERE id=?");
        $stmt->bind_param("issdissi", $cat_id, $name, $brand, $min_price, $tax, $total_stock, $desc, $id);

        if ($stmt->execute()) {
            // 4. CẬP NHẬT BIẾN THỂ (Xóa cũ -> Thêm mới)
            $conn->query("DELETE FROM product_weights WHERE product_id = $id");
            $stmt_w = $conn->prepare("INSERT INTO product_weights (product_id, weight_gram, price, stock_quantity) VALUES (?, ?, ?, ?)");
            foreach ($valid_variants as $v) {
                $stmt_w->bind_param("iidi", $id, $v['gram'], $v['price'], $v['stock']);
                $stmt_w->execute();
            }

            // 5. XỬ LÝ ẢNH
            $target_dir = "../assets/img/product/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            // Ảnh chính
            if (isset($_FILES['image_main']) && $_FILES['image_main']['error'] == 0) {
                $filename = time() . "_main_" . basename($_FILES["image_main"]["name"]);
                if (move_uploaded_file($_FILES["image_main"]["tmp_name"], $target_dir . $filename)) {
                    $db_path = "assets/img/product/" . $filename;
                    $conn->query("DELETE FROM product_images WHERE product_id=$id AND is_main=1");
                    $conn->query("INSERT INTO product_images (product_id, image_url, is_main) VALUES ($id, '$db_path', 1)");
                }
            }
            // Ảnh phụ (Thêm mới)
            if (isset($_FILES['images_extra']) && !empty($_FILES['images_extra']['name'][0])) {
                $total = count($_FILES['images_extra']['name']);
                for ($k = 0; $k < $total; $k++) {
                    if ($_FILES['images_extra']['error'][$k] == 0) {
                        $filename = time() . "_extra_{$k}_" . basename($_FILES["images_extra"]["name"][$k]);
                        if (move_uploaded_file($_FILES["images_extra"]["tmp_name"][$k], $target_dir . $filename)) {
                            $db_path = "assets/img/product/" . $filename;
                            $conn->query("INSERT INTO product_images (product_id, image_url, is_main) VALUES ($id, '$db_path', 0)");
                        }
                    }
                }
            }
            $msg = "<div class='alert alert-success'>Cập nhật thành công!</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Lỗi: " . $conn->error . "</div>";
        }
    }
}

// --- LẤY DỮ LIỆU ĐỂ HIỂN THỊ ---
$prod = $conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Lấy danh sách biến thể cũ để điền vào form
$variants = $conn->query("SELECT * FROM product_weights WHERE product_id=$id ORDER BY weight_gram ASC");
$variant_list = [];
while($row = $variants->fetch_assoc()) {
    $variant_list[] = $row;
}

$main_img = $conn->query("SELECT image_url FROM product_images WHERE product_id=$id AND is_main=1 LIMIT 1")->fetch_assoc();
$main_src = ($main_img) ? '../' . $main_img['image_url'] : '../assets/img/product/no-image.png';
$extra_imgs = $conn->query("SELECT * FROM product_images WHERE product_id=$id AND is_main=0");
?>

<div class="header-bar mb-4">
    <div><h4 class="fw-bold mb-1 text-dark">Sửa sản phẩm #<?php echo $id; ?></h4></div>
    <a href="products.php" class="btn btn-light border shadow-sm px-4"><i class="fas fa-arrow-left me-2"></i>Quay lại</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <?php echo $msg; ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <h6 class="fw-bold text-muted mb-3 font-size-sm">THÔNG TIN CHUNG</h6>
                            <div class="mb-3"><label class="fw-bold">Tên sản phẩm</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($prod['name']); ?>" required></div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-bold">Danh mục</label>
                                    <div id="select-cat-box">
                                        <select name="category_id" class="form-select mb-2">
                                            <option value="">-- Chọn --</option>
                                            <?php $cats->data_seek(0); while($c = $cats->fetch_assoc()): ?>
                                                <option value="<?php echo $c['id']; ?>" <?php echo ($prod['category_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="text-end"><a href="javascript:void(0)" onclick="toggleNewCat()" class="text-primary small">Thêm mới</a></div>
                                    </div>
                                    <div id="new-cat-box" style="display: none;">
                                        <input type="text" name="new_category_name" class="form-control mb-2" placeholder="Tên danh mục...">
                                        <div class="text-end"><a href="javascript:void(0)" onclick="toggleNewCat()" class="text-danger small">Hủy</a></div>
                                    </div>
                                </div>
                                <div class="col-md-6"><label class="fw-bold">Thương hiệu</label><input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($prod['brand']); ?>"></div>
                            </div>
                            <div class="mb-3"><label class="fw-bold">Mô tả</label><textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($prod['description']); ?></textarea></div>

                            <div class="mb-3 bg-light p-3 rounded border border-dashed">
                                <label class="fw-bold d-block mb-2">Thư viện ảnh</label>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php while($img = $extra_imgs->fetch_assoc()): ?>
                                        <div class="position-relative border rounded p-1 bg-white" style="width: 60px; height: 60px;">
                                            <img src="../<?php echo $img['image_url']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <a href="product_image_delete.php?id=<?php echo $img['id']; ?>&pid=<?php echo $id; ?>" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" onclick="return confirm('Xóa ảnh này?')" style="font-size: 0.6rem;">x</a>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <input type="file" name="images_extra[]" class="form-control mt-1" multiple accept="image/*" onchange="previewGallery(this)">
                                <div id="gallery-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>
                        </div>

                        <div class="col-md-5 border-start">
                            <h6 class="fw-bold text-muted mb-3 font-size-sm">CÁC LOẠI ĐÓNG GÓI & GIÁ</h6>
                            <div class="mb-3">
                                <table class="table table-bordered table-sm align-middle text-center" id="variantTable">
                                    <thead class="bg-light">
                                        <tr><th style="width: 35%">Khối lượng</th><th style="width: 30%">Giá (Đ)</th><th style="width: 25%">Kho</th><th style="width: 10%"></th></tr>
                                    </thead>
                                    <tbody id="variantBody">
                                        <?php if (!empty($variant_list)): ?>
                                            <?php foreach ($variant_list as $var): 
                                                $gram = (int)$var['weight_gram'];
                                                $val = ($gram >= 1000) ? ($gram/1000) : $gram;
                                                $u = ($gram >= 1000) ? 'kg' : 'g';
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="var_weight[]" value="<?php echo $val; ?>" class="form-control text-center px-1" required>
                                                        <select name="var_unit[]" class="form-select px-1 bg-light" style="max-width: 50px;">
                                                            <option value="g" <?php if($u=='g') echo 'selected'; ?>>g</option>
                                                            <option value="kg" <?php if($u=='kg') echo 'selected'; ?>>kg</option>
                                                        </select>
                                                    </div>
                                                </td>
                                                <td><input type="number" name="var_price[]" value="<?php echo (int)$var['price']; ?>" class="form-control form-control-sm text-center fw-bold text-success" required></td>
                                                <td><input type="number" name="var_stock[]" value="<?php echo $var['stock_quantity']; ?>" class="form-control form-control-sm text-center" required></td>
                                                <td><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="var_weight[]" class="form-control text-center px-1" placeholder="500" required>
                                                        <select name="var_unit[]" class="form-select px-1 bg-light" style="max-width: 50px;"><option value="g">g</option><option value="kg">kg</option></select>
                                                    </div>
                                                </td>
                                                <td><input type="number" name="var_price[]" class="form-control form-control-sm text-center fw-bold text-success" required></td>
                                                <td><input type="number" name="var_stock[]" class="form-control form-control-sm text-center" required></td>
                                                <td><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 dashed-border" onclick="addVariantRow()"><i class="fas fa-plus-circle me-1"></i> Thêm loại khác</button>
                            </div>

                            <div class="mb-3"><label class="fw-bold">Thuế VAT (%)</label><input type="number" name="tax_percent" class="form-control" value="<?php echo $prod['tax_percent']; ?>"></div>
                            <hr class="my-4">
                            <div class="mb-3">
                                <label class="fw-bold">Ảnh đại diện (Chính)</label>
                                <input type="file" name="image_main" class="form-control" accept="image/*" onchange="previewMain(this)">
                                <div class="mt-2 text-center bg-light border border-dashed rounded p-2" style="min-height: 150px;">
                                    <img id="main-preview" src="<?php echo $main_src; ?>" style="max-width: 100%; max-height: 150px; object-fit: contain;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4 pt-3 border-top"><button type="submit" class="btn btn-primary px-4 fw-bold">Lưu thay đổi</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function addVariantRow() {
    const tbody = document.getElementById('variantBody');
    const row = document.createElement('tr');
    // Bỏ step="0.01" trong template JS
    row.innerHTML = `
        <td><div class="input-group input-group-sm"><input type="number" name="var_weight[]" class="form-control text-center px-1" required><select name="var_unit[]" class="form-select px-1 bg-light" style="max-width: 50px;"><option value="g">g</option><option value="kg">kg</option></select></div></td>
        <td><input type="number" name="var_price[]" class="form-control form-control-sm text-center fw-bold text-success" required></td>
        <td><input type="number" name="var_stock[]" class="form-control form-control-sm text-center" required></td>
        <td><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>`;
    tbody.appendChild(row);
}
function removeRow(btn) {
    const tbody = document.getElementById('variantBody');
    if (tbody.rows.length > 1) btn.closest('tr').remove();
    else alert("Phải giữ lại ít nhất một dòng!");
}
function previewMain(input) { if(input.files && input.files[0]){ var r=new FileReader(); r.onload=function(e){document.getElementById('main-preview').src=e.target.result;}; r.readAsDataURL(input.files[0]); } }
function previewGallery(input) {
    var p = document.getElementById('gallery-preview'); p.innerHTML = '';
    if (input.files) Array.from(input.files).forEach(f => { var r = new FileReader(); r.onload = function(e) { p.innerHTML += `<img src="${e.target.result}" style="width:50px;height:50px;object-fit:cover;" class="rounded border me-1">`; }; r.readAsDataURL(f); });
}
function toggleNewCat() { var s=document.getElementById('select-cat-box'); var n=document.getElementById('new-cat-box'); if(n.style.display==='none'){n.style.display='block';s.style.display='none';}else{n.style.display='none';s.style.display='block';} }
</script>
<?php require_once 'includes/footer.php'; ?>