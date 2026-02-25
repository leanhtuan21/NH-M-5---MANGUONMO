<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['name']) || empty($_POST['brand']) || empty($_POST['description'])) {
        $msg = "<div class='alert alert-danger'>Vui lòng điền đầy đủ thông tin chung!</div>";
    } else {
        $name = $_POST['name'];
        $brand = $_POST['brand'];
        $desc = $_POST['description'];
        $tax = !empty($_POST['tax_percent']) ? $_POST['tax_percent'] : 10;
        
        // 1. Xử lý danh mục
        $cat_id = !empty($_POST['category_id']) ? $_POST['category_id'] : NULL;
        if (!empty($_POST['new_category_name'])) {
            $new_cat = trim($_POST['new_category_name']);
            $check = $conn->query("SELECT id FROM categories WHERE name = '$new_cat'");
            if ($check->num_rows > 0) {
                $cat_id = $check->fetch_assoc()['id'];
            } else {
                $conn->query("INSERT INTO categories (name) VALUES ('$new_cat')");
                $cat_id = $conn->insert_id;
            }
        }

        // 2. XỬ LÝ BIẾN THỂ (ĐỘNG)
        // Lấy dữ liệu mảng từ form
        $v_weights = $_POST['var_weight'] ?? []; // Giá trị (vd: 500, 1)
        $v_units   = $_POST['var_unit'] ?? [];   // Đơn vị (vd: g, kg)
        $v_prices  = $_POST['var_price'] ?? [];
        $v_stocks  = $_POST['var_stock'] ?? [];

        $valid_variants = [];
        $total_stock = 0;
        $min_price = 0;

        // Duyệt qua các dòng đã nhập
        for ($i = 0; $i < count($v_weights); $i++) {
            $val = (float)$v_weights[$i];
            $unit = $v_units[$i];
            // Tiền Việt dùng số nguyên
            $price = (int)$v_prices[$i];
            $stock = (int)$v_stocks[$i];

            // Chỉ lấy dòng có dữ liệu hợp lệ
            if ($val > 0 && $price > 0) {
                // Quy đổi ra Gram để lưu thống nhất (1kg = 1000g)
                $gram_value = ($unit == 'kg') ? ($val * 1000) : $val;
                
                $valid_variants[] = [
                    'gram' => $gram_value,
                    'price' => $price,
                    'stock' => $stock
                ];

                $total_stock += $stock;
                if ($min_price == 0 || $price < $min_price) $min_price = $price;
            }
        }

        if (empty($valid_variants)) {
            $msg = "<div class='alert alert-danger'>Vui lòng thêm ít nhất một loại khối lượng và nhập giá!</div>";
        } else {
            // 3. INSERT SẢN PHẨM CHÍNH
            $stmt = $conn->prepare("INSERT INTO products (category_id, name, brand, price, tax_percent, stock_quantity, description, average_score) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("issdiiss", $cat_id, $name, $brand, $min_price, $tax, $total_stock, $desc);

            if ($stmt->execute()) {
                $product_id = $stmt->insert_id;
                
                // 4. INSERT BIẾN THỂ VÀO BẢNG product_weights
                $stmt_w = $conn->prepare("INSERT INTO product_weights (product_id, weight_gram, price, stock_quantity) VALUES (?, ?, ?, ?)");
                
                foreach ($valid_variants as $v) {
                    $stmt_w->bind_param("iidi", $product_id, $v['gram'], $v['price'], $v['stock']);
                    $stmt_w->execute();
                }

                // 5. XỬ LÝ ẢNH (Giữ nguyên)
                $target_dir = "../assets/img/product/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

                if (isset($_FILES['image_main']) && $_FILES['image_main']['error'] == 0) {
                    $filename = time() . "_main_" . basename($_FILES["image_main"]["name"]);
                    if (move_uploaded_file($_FILES["image_main"]["tmp_name"], $target_dir . $filename)) {
                        $db_path = "assets/img/product/" . $filename;
                        $conn->query("INSERT INTO product_images (product_id, image_url, is_main) VALUES ($product_id, '$db_path', 1)");
                    }
                }
                
                if (isset($_FILES['images_extra']) && !empty($_FILES['images_extra']['name'][0])) {
                    $total_files = count($_FILES['images_extra']['name']);
                    for ($k = 0; $k < $total_files; $k++) {
                        if ($_FILES['images_extra']['error'][$k] == 0) {
                            $filename = time() . "_extra_{$k}_" . basename($_FILES["images_extra"]["name"][$k]);
                            if (move_uploaded_file($_FILES["images_extra"]["tmp_name"][$k], $target_dir . $filename)) {
                                $db_path = "assets/img/product/" . $filename;
                                $conn->query("INSERT INTO product_images (product_id, image_url, is_main) VALUES ($product_id, '$db_path', 0)");
                            }
                        }
                    }
                }
                $msg = "<div class='alert alert-success'>Thêm sản phẩm thành công!</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Lỗi hệ thống: " . $conn->error . "</div>";
            }
        }
    }
}
?>

<div class="header-bar mb-4">
    <h4 class="fw-bold text-dark">Thêm sản phẩm mới</h4>
    <a href="products.php" class="btn btn-light border shadow-sm px-4"><i class="fas fa-arrow-left me-2"></i>Quay lại</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <?php echo $msg; ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data" id="productForm">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <h6 class="fw-bold text-muted mb-3 font-size-sm">THÔNG TIN CHUNG</h6>
                            <div class="mb-3">
                                <label class="fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="VD: Cà phê Arabica Cầu Đất">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-bold">Danh mục <span class="text-danger">*</span></label>
                                    <div id="select-cat-box">
                                        <select name="category_id" class="form-select mb-2" required>
                                            <option value="">-- Chọn --</option>
                                            <?php while($c = $cats->fetch_assoc()): ?>
                                                <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="text-end"><a href="javascript:void(0)" onclick="toggleNewCat()" class="text-primary small fw-bold text-decoration-none">Thêm mới</a></div>
                                    </div>
                                    <div id="new-cat-box" style="display:none;">
                                        <input type="text" name="new_category_name" class="form-control mb-2" placeholder="Tên danh mục...">
                                        <div class="text-end"><a href="javascript:void(0)" onclick="toggleNewCat()" class="text-danger small">Hủy</a></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold">Thương hiệu</label>
                                    <input type="text" name="brand" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Mô tả chi tiết</label>
                                <textarea name="description" class="form-control" rows="5" required></textarea>
                            </div>
                            <div class="mb-3 bg-light p-3 rounded border border-dashed">
                                <label class="fw-bold d-block mb-2">Ảnh phụ (Gallery)</label>
                                <input type="file" name="images_extra[]" class="form-control" multiple accept="image/*" onchange="previewGallery(this)">
                                <div id="gallery-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>
                        </div>

                        <div class="col-md-5 border-start">
                            <h6 class="fw-bold text-muted mb-3 font-size-sm">CÁC LOẠI ĐÓNG GÓI & GIÁ</h6>
                            
                            <div class="alert alert-info py-2 small">
                                <i class="fas fa-info-circle"></i> Nhập khối lượng, chọn đơn vị (g/kg), giá và tồn kho.
                            </div>

                            <div class="mb-3">
                                <table class="table table-bordered table-sm align-middle text-center" id="variantTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 35%">Khối lượng</th>
                                            <th style="width: 30%">Giá (Đ)</th>
                                            <th style="width: 25%">Kho</th>
                                            <th style="width: 10%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="variantBody">
                                        <tr>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="var_weight[]" class="form-control text-center px-1" placeholder="VD: 500" required>
                                                    <select name="var_unit[]" class="form-select px-1 bg-light" style="max-width: 50px;">
                                                        <option value="g">g</option>
                                                        <option value="kg">kg</option>
                                                    </select>
                                                </div>
                                            </td>
                                            <td><input type="number" name="var_price[]" class="form-control form-control-sm text-center fw-bold text-success" placeholder="VD: 100000" required></td>
                                            <td><input type="number" name="var_stock[]" class="form-control form-control-sm text-center" placeholder="0" required></td>
                                            <td><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 dashed-border" onclick="addVariantRow()">
                                    <i class="fas fa-plus-circle me-1"></i> Thêm loại khác
                                </button>
                            </div>

                            <div class="mb-3">
                                <label class="fw-bold">Thuế VAT (%)</label>
                                <input type="number" name="tax_percent" class="form-control" value="10">
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="fw-bold">Ảnh đại diện <span class="text-danger">*</span></label>
                                <input type="file" name="image_main" class="form-control" accept="image/*" onchange="previewMain(this)" required>
                                <div class="mt-2 text-center bg-light border border-dashed rounded p-2" style="min-height: 150px;">
                                    <img id="main-preview" style="max-width: 100%; max-height: 150px; object-fit: contain; display: none;">
                                    <div id="main-placeholder" class="text-muted mt-4"><i class="fas fa-camera fa-2x mb-2"></i><br>Xem trước</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Lưu sản phẩm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Hàm thêm dòng mới
function addVariantRow() {
    const tbody = document.getElementById('variantBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <div class="input-group input-group-sm">
                <input type="number" name="var_weight[]" class="form-control text-center px-1" placeholder="KL" required>
                <select name="var_unit[]" class="form-select px-1 bg-light" style="max-width: 50px;">
                    <option value="g">g</option>
                    <option value="kg">kg</option>
                </select>
            </div>
        </td>
        <td><input type="number" name="var_price[]" class="form-control form-control-sm text-center fw-bold text-success" placeholder="Giá" required></td>
        <td><input type="number" name="var_stock[]" class="form-control form-control-sm text-center" placeholder="Kho" required></td>
        <td><button type="button" class="btn btn-sm text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(row);
}

// Hàm xóa dòng
function removeRow(btn) {
    const tbody = document.getElementById('variantBody');
    // Giữ lại ít nhất 1 dòng
    if (tbody.rows.length > 1) {
        btn.closest('tr').remove();
    } else {
        alert("Phải có ít nhất một loại sản phẩm!");
    }
}

function previewMain(input) {
    if (input.files && input.files[0]) {
        var r = new FileReader();
        r.onload = function (e) { 
            document.getElementById('main-preview').src = e.target.result; 
            document.getElementById('main-preview').style.display = 'block';
            document.getElementById('main-placeholder').style.display = 'none';
        }
        r.readAsDataURL(input.files[0]);
    }
}
function previewGallery(input) {
    var p = document.getElementById('gallery-preview'); p.innerHTML = '';
    if (input.files) Array.from(input.files).forEach(f => {
        var r = new FileReader();
        r.onload = function(e) { p.innerHTML += `<img src="${e.target.result}" style="width:50px;height:50px;object-fit:cover;" class="rounded border me-1">`; }
        r.readAsDataURL(f);
    });
}
function toggleNewCat() { 
    var s=document.getElementById('select-cat-box'), n=document.getElementById('new-cat-box');
    var iNew=document.querySelector('input[name="new_category_name"]'), sOld=document.querySelector('select[name="category_id"]');
    if(n.style.display==='none'){ n.style.display='block'; s.style.display='none'; sOld.removeAttribute('required'); iNew.setAttribute('required','required'); }
    else { n.style.display='none'; s.style.display='block'; iNew.removeAttribute('required'); sOld.setAttribute('required','required'); } 
}
</script>
<?php require_once 'includes/footer.php'; ?>