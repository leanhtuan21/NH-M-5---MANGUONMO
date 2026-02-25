<?php
require_once 'includes/auth.php';
require_once '../db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit;
}
$id = (int)$_GET['id'];

// 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI & PHÍ SHIP (Đặt lên đầu trang)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $new_payment = $_POST['payment_status'];
    $new_shipping_fee = (float)$_POST['shipping_fee']; // Lấy phí ship Admin nhập

    // Tính lại Tổng tiền mới = (Tổng tiền hàng) + (Phí ship mới)
    // Truy vấn tổng tiền hàng từ bảng order_items
    $res_items = $conn->query("SELECT SUM(quantity * price_at_purchase) as subtotal FROM order_items WHERE order_id = $id");
    $subtotal = $res_items->fetch_assoc()['subtotal'] ?? 0;
    $new_total_amount = $subtotal + $new_shipping_fee;
    
    // Cập nhật Database: status, payment_status, shipping_fee và total_amount
    $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = ?, shipping_fee = ?, total_amount = ? WHERE id = ?");
    $stmt->bind_param("ssddi", $new_status, $new_payment, $new_shipping_fee, $new_total_amount, $id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Cập nhật đơn hàng thành công!'); window.location.href='order_view.php?id=$id';</script>";
        exit;
    } else {
        echo "<script>alert('Lỗi hệ thống: " . $conn->error . "');</script>";
    }
}

// 2. LẤY THÔNG TIN ĐƠN HÀNG SAU KHI CẬP NHẬT
$order_sql = "SELECT o.*, u.full_name, u.email, u.phone, u.address as user_address 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.id = $id";
$order = $conn->query($order_sql)->fetch_assoc();

if (!$order) die("Không tìm thấy đơn hàng");

// LẤY DANH SÁCH SẢN PHẨM TRONG ĐƠN
$items_sql = "SELECT oi.*, p.name, p.brand, 
              (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as thumbnail
              FROM order_items oi
              LEFT JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = $id";
$items = $conn->query($items_sql);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="header-bar mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Chi tiết đơn hàng #<?php echo $id; ?></h4>
        <p class="text-muted mb-0">Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
    </div>
    <a href="orders.php" class="btn btn-light border shadow-sm px-4">
        <i class="fas fa-arrow-left me-2"></i>Quay lại
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0"><i class="fas fa-shopping-basket me-2 text-primary"></i>Danh sách sản phẩm</h6>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Sản phẩm</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end pe-4">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_items_price = 0; // Để hiển thị tạm tính
                        while($item = $items->fetch_assoc()): 
                            $img = $item['thumbnail'] ? '../' . $item['thumbnail'] : '../assets/img/product/no-image.png';
                            $subtotal = $item['quantity'] * $item['price_at_purchase'];
                            $total_items_price += $subtotal;
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $img; ?>" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div class="ms-3">
                                        <div class="fw-bold text-dark"><?php echo $item['name']; ?></div>
                                        <small class="text-muted"><?php echo $item['brand']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center fw-bold">x<?php echo $item['quantity']; ?></td>
                            <td class="text-end"><?php echo number_format($item['price_at_purchase'], 0, ',', '.'); ?> Đ</td>
                            <td class="text-end pe-4 fw-bold"><?php echo number_format($subtotal, 0, ',', '.'); ?> Đ</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold py-3">Tiền hàng:</td>
                            <td class="text-end pe-4 fw-bold py-3"><?php echo number_format($total_items_price, 0, ',', '.'); ?> Đ</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-bold py-3 text-secondary">Phí vận chuyển:</td>
                            <td class="text-end pe-4 fw-bold py-3 text-secondary"><?php echo number_format($order['shipping_fee'], 0, ',', '.'); ?> Đ</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-bold py-3 text-dark h6 mb-0">TỔNG CỘNG:</td>
                            <td class="text-end pe-4 fw-bold py-3 text-primary h5 mb-0"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> Đ</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0"><i class="fas fa-cog me-2 text-warning"></i>Xử lý đơn hàng</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="order_view.php?id=<?php echo $id; ?>">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phí vận chuyển (VNĐ)</label>
                        <input type="number" name="shipping_fee" class="form-control fw-bold text-primary" value="<?php echo (int)$order['shipping_fee']; ?>" min="0">
                        <small class="text-muted">Admin tự nhập phí ship thực tế.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Trạng thái đơn hàng</label>
                        <select name="status" class="form-select">
                            <option value="pending" <?php if($order['status']=='pending') echo 'selected'; ?>>Chờ xử lý</option>
                            <option value="paid" <?php if($order['status']=='paid') echo 'selected'; ?>>Đã thanh toán (Chờ giao)</option>
                            <option value="processing" <?php if($order['status']=='processing') echo 'selected'; ?>>Đang xử lý (Đóng gói)</option>
                            <option value="shipping" <?php if($order['status']=='shipping') echo 'selected'; ?>>Đang giao hàng</option>
                            <option value="completed" <?php if($order['status']=='completed') echo 'selected'; ?>>Đã giao thành công</option>
                            <option value="cancelled" <?php if($order['status']=='cancelled') echo 'selected'; ?>>Đã hủy</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Thanh toán</label>
                        <select name="payment_status" class="form-select">
                            <option value="unpaid" <?php if($order['payment_status']=='unpaid') echo 'selected'; ?>>Chưa thanh toán</option>
                            <option value="pending_confirmation" <?php if($order['payment_status']=='pending_confirmation') echo 'selected'; ?>>Chờ xác nhận (QR/CK)</option>
                            <option value="paid" <?php if($order['payment_status']=='paid') echo 'selected'; ?>>Đã thanh toán</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">Cập nhật đơn hàng</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0"><i class="fas fa-user me-2 text-info"></i>Thông tin khách hàng</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-user text-secondary"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="fw-bold mb-0 text-dark"><?php echo $order['full_name']; ?></h6>
                        <small class="text-muted">ID: #<?php echo $order['user_id']; ?></small>
                    </div>
                </div>
                
                <hr class="border-light">

                <div class="mb-3">
                    <label class="small text-muted fw-bold text-uppercase">Email</label>
                    <div class="text-dark"><?php echo $order['email']; ?></div>
                </div>

                <div class="mb-3">
                    <label class="small text-muted fw-bold text-uppercase">Số điện thoại</label>
                    <div class="text-dark"><?php echo $order['phone'] ?? 'Chưa cập nhật'; ?></div>
                </div>

                <div class="mb-3">
                    <label class="small text-muted fw-bold text-uppercase">Địa chỉ giao hàng</label>
                    <div class="text-dark"><?php echo $order['address'] ?? $order['user_address']; ?></div>
                </div>
                
                <div class="mb-0">
                    <label class="small text-muted fw-bold text-uppercase">Phương thức vận chuyển</label>
                    <div class="text-dark badge bg-light text-dark border"><?php echo $order['shipping_method'] ?? 'Tiêu chuẩn'; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>