<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu giỏ hàng
$sql = "SELECT 
    c.id, 
    c.quantity, 
    c.price, 
    c.product_name, 
    c.weight_gram,
    c.selected,
    pi.image_url,
    p.id AS product_id,
    pw.stock_quantity AS stock_quantity
FROM cart c
JOIN products p 
    ON c.product_name = p.name
LEFT JOIN product_images pi 
    ON p.id = pi.product_id AND pi.is_main = 1
LEFT JOIN product_weights pw 
    ON pw.product_id = p.id 
    AND pw.weight_gram = c.weight_gram
WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$subtotal = 0;
$total_quantity = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    
    // PHP chỉ tính tổng ban đầu cho những món ĐÃ TÍCH
    if ($row['selected'] == 1) {
        $subtotal += $row['price'] * $row['quantity'];
        $total_quantity += $row['quantity'];
    }
}

$total_all = $subtotal; // Phí ship = 0
////////////// Nút tiếp tục thanh toán
/* Lấy các sản phẩm ĐÃ CHỌN */
$sql = "
SELECT 
    c.id,
    c.product_name,
    c.weight_gram,
    c.price,
    c.quantity,
    (c.price * c.quantity) AS line_total,
    p.id AS product_id,
    pi.image_url
FROM cart c
JOIN products p 
    ON c.product_name = p.name
LEFT JOIN product_images pi 
    ON p.id = pi.product_id AND pi.is_main = 1
WHERE c.user_id = ? AND c.selected = 1
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$total_qty = 0;
$total_price = 0;

while ($row = $res->fetch_assoc()) {
    $items[] = $row;
    $total_qty += $row['quantity'];
    $total_price += $row['line_total'];
}

$stmt->close();
$conn->close();

/* LƯU SESSION */
$_SESSION['checkout'] = [
    'items' => $items,
    'total_qty' => $total_qty,
    'total_price' => $total_price
];
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Checkout | Grocery Mart</title>

        <link rel="apple-touch-icon" sizes="76x76" href="./assets/favicon/apple-touch-icon.png" />
        <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon/favicon-32x32.png" />
        <link rel="icon" type="image/png" sizes="16x16" href="./assets/favicon/favicon-16x16.png" />
        <link rel="manifest" href="./assets/favicon/site.webmanifest" />
        <meta name="theme-color" content="#ffffff" />

        <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />
        <link rel="stylesheet" href="./assets/css/main.css" />
        <script src="./assets/js/scripts.js"></script>
    </head>
    <body>
        <header id="header" class="header"></header>
        <script>load("#header", "./templates/header-logined.php");</script>

        <main class="checkout-page">
            <div class="container">
                <div class="checkout-container">
                    <div class="search-bar d-none d-md-flex">
                        <input type="text" placeholder="Search for item" class="search-bar__input" />
                        <button class="search-bar__submit"><img src="./assets/icons/search.svg" class="search-bar__icon icon" /></button>
                    </div>
                </div>
                <div class="checkout-container">
                    <ul class="breadcrumbs checkout-page__breadcrumbs">
                        <li><a href="index-logined.php" class="breadcrumbs__link">Home <img src="./assets/icons/arrow-right.svg" /></a></li>
                        <li><a href="#!" class="breadcrumbs__link breadcrumbs__link--current">Checkout</a></li>
                    </ul>
                </div>

                <div style="background: #fff3cd; padding: 15px; margin-bottom: 20px; border: 1px solid #ffc107; border-radius: 5px;">
                        <strong style="color: #856404;">🔍 Thông tin tổng quan giỏ hàng:</strong>
                        <br><br>
                        <br>Tổng sản phẩm trong giỏ: <strong id="cart-total-count"><?php echo count($cart_items); ?></strong>
                </div>

                <div class="checkout-container">
                    <div class="row gy-xl-3">
                        <div class="col-8 col-xl-12">
                            <div class="cart-info">
                                <div class="cart-info__list">
                                    <?php if (count($cart_items) > 0): ?>
                                        <?php foreach ($cart_items as $item): ?>
                                            <article class="cart-item" id="item-row-<?= $item['id'] ?>">
                                                <div style="padding: 0 10px; display: flex; align-items: center;">
                                                    <input type="checkbox" 
                                                           class="cart-checkbox" 
                                                           style="width: 20px; height: 20px; cursor: pointer;"
                                                           data-id="<?= $item['id'] ?>"
                                                           data-price="<?= $item['price'] ?>"
                                                           <?= $item['selected'] == 1 ? 'checked' : '' ?> 
                                                           onchange="toggleItem(this)">
                                                </div>
                                                
                                                <a href="./product-detail.php?id=<?php echo $item['product_id']; ?>">
                                                    <img src="<?php echo $item['image_url'] ?? './assets/img/product/item-1.png'; ?>" class="cart-item__thumb" />
                                                </a>
                                                
                                                <div class="cart-item__content">
                                                    <div class="cart-item__content-left">
                                                        <h3 class="cart-item__title">
                                                            <a href="./product-detail.php?id=<?php echo $item['product_id']; ?>">
                                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                                            </a>
                                                        </h3>
                                                        <p>
                                                            Khối lượng: 
                                                            <strong><?= $item['weight_gram'] >= 1000 ? ($item['weight_gram']/1000) . 'kg' : $item['weight_gram'] . 'g' ?></strong>
                                                        </p>
                                                        <p class="cart-item__price-wrap">
                                                            <?php echo number_format($item['price'], 0, ',', '.'); ?>Đ | 
                                                            <?php $inStock = ($item['stock_quantity'] >= $item['quantity']);?>
                                                            <span class="cart-item__status" style="color: <?= $inStock ? '#1a7f37' : '#d1242f' ?>;">
                                                                <?= $inStock ? 'Còn hàng' : 'Hết hàng' ?>
                                                            </span>
                                                        </p>
                                                        <div class="cart-item__ctrl cart-item__ctrl--md-block">
                                                            <div class="cart-item__input">
                                                                <button class="cart-item__input-btn js-qty-change" data-id="<?php echo $item['id']; ?>" data-action="decrease">
                                                                    <img class="icon" src="./assets/icons/minus.svg" alt="" />
                                                                </button>
                                                                <span id="qty-<?php echo $item['id']; ?>"><?php echo $item['quantity']; ?></span>
                                                                <button class="cart-item__input-btn js-qty-change" data-id="<?php echo $item['id']; ?>" data-action="increase">
                                                                    <img class="icon" src="./assets/icons/plus.svg" alt="" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="cart-item__content-right">
                                                        <p class="cart-item__total-price" id="line-total-<?= $item['id'] ?>">
                                                            <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>Đ
                                                        </p>
                                                        <div class="cart-item__ctrl">
                                                            <button class="cart-item__ctrl-btn delete-cart-btn" data-cart-id="<?php echo $item['id']; ?>" type="button">
                                                                <img src="./assets/icons/trash.svg" alt="" /> Xoá
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>Giỏ hàng của bạn đang trống.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-4 col-xl-12">
                            <div class="cart-info">
                                <div class="cart-info__row">
                                    <span>Tổng số lượng <span class="cart-info__sub-label">sản phẩm : </span></span>
                                    <span id="summary-quantity"><?php echo $total_quantity; ?></span>
                                </div>
                                <div class="cart-info__row">
                                    <span>Tổng tiền hàng : <span class="cart-info__sub-label">(Total)</span></span>
                                    <span id="summary-subtotal"><?php echo number_format($subtotal, 0, ',', '.'); ?>Đ</span>
                                </div>
                                <div class="cart-info__separate"></div>
                                <div class="cart-info__row">
                                    <span>Thanh toán : </span>
                                    <span id="summary-total"><?php echo number_format($total_all, 0, ',', '.'); ?>Đ</span>
                                </div>
                                <a href="./shipping.php" id="checkout-btn" class="cart-info__next-btn btn btn--primary btn--rounded">
                                    Tiếp tục thanh toán
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer id="footer" class="footer"></footer>
        <script>load("#footer", "./templates/footer.php");</script>

        <div id="toast" style="position: fixed; top: 24px; right: 24px; min-width: 320px; max-width: 420px; padding: 18px 22px; background: #333; color: #fff; border-radius: 12px; box-shadow: 0 10px 28px rgba(0,0,0,0.35); font-size: 16px; font-weight: 600; line-height: 1.4; z-index: 99999; opacity: 0; transform: translateY(-12px); transition: all .25s ease; pointer-events: none;"></div>

        <script>
            // === 1. CẤU HÌNH & UTILS ===
            const SHIPPING_FEE = 0; 

            function formatMoney(amount) {
                return amount.toLocaleString('vi-VN', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + 'Đ';
            }

            function showToast(message, type = 'success', duration = 2500) {
                const toast = document.getElementById('toast');
                if (!toast) return;
                toast.innerText = message;
                toast.style.background = type === 'error' ? '#d32f2f' : '#2e7d32';
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-10px)';
                }, duration);
            }

            // === 2. HÀM TÍNH TỔNG TIỀN ===
            function calculateTotal() {
                let subtotal = 0;
                let totalQty = 0;
                let hasItemChecked = false;

                const checkboxes = document.querySelectorAll('.cart-checkbox');
                
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        hasItemChecked = true;
                        const price = parseFloat(cb.dataset.price);
                        const id = cb.dataset.id;
                        
                        const qtyElement = document.getElementById('qty-' + id);
                        const quantity = qtyElement ? parseInt(qtyElement.innerText) : 0;
                        
                        subtotal += price * quantity;
                        totalQty += quantity;
                    }
                });

                const total = hasItemChecked ? (subtotal + SHIPPING_FEE) : 0;

                const elSubtotal = document.getElementById('summary-subtotal');
                const elTotal = document.getElementById('summary-total');
                const elQty = document.getElementById('summary-quantity');
                const btnCheckout = document.getElementById('checkout-btn');

                if (elSubtotal) elSubtotal.innerText = formatMoney(subtotal);
                if (elTotal) elTotal.innerText = formatMoney(total);
                if (elQty) elQty.innerText = totalQty;

                if (btnCheckout) {
                    if (!hasItemChecked) {
                        btnCheckout.style.opacity = '0.5';
                        btnCheckout.style.pointerEvents = 'none';
                        btnCheckout.innerText = 'Vui lòng chọn sản phẩm';
                    } else {
                        btnCheckout.style.opacity = '1';
                        btnCheckout.style.pointerEvents = 'auto';
                        btnCheckout.innerText = 'Tiếp tục thanh toán';
                    }
                }
            }

            // === 3. XỬ LÝ SỰ KIỆN CLICK CHECKBOX ===
            function toggleItem(checkbox) {
                calculateTotal();
                const id = checkbox.dataset.id;
                const isSelected = checkbox.checked ? 1 : 0;

                fetch('update_cart_selected.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: id, selected: isSelected })
                }).catch(err => console.error(err));
            }

            // === 4. EVENTS ===
            document.addEventListener('DOMContentLoaded', function() {
                calculateTotal();

                // Nút tăng giảm
                const qtyButtons = document.querySelectorAll('.js-qty-change');
                qtyButtons.forEach((button) => {
                    button.addEventListener('click', function() {
                        const cartItemId = this.getAttribute('data-id');
                        const action = this.getAttribute('data-action');
                        
                        fetch('update_cart_quantity.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({ id: parseInt(cartItemId), action: action })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const qtyElement = document.getElementById('qty-' + cartItemId);
                                if (qtyElement) qtyElement.textContent = data.new_quantity;
                                
                                const lineTotalElement = document.getElementById('line-total-' + cartItemId);
                                if (lineTotalElement) {
                                     const cb = document.querySelector(`.cart-checkbox[data-id="${cartItemId}"]`);
                                     if(cb) {
                                         const price = parseFloat(cb.dataset.price);
                                         lineTotalElement.innerText = formatMoney(price * data.new_quantity);
                                     }
                                }
                                calculateTotal();
                            } else {
                                showToast(data.message, 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showToast('Lỗi kết nối server', 'error');
                        });
                    });
                });

                // Nút xóa (ĐÃ CẬP NHẬT ĐỂ TRỪ SỐ LƯỢNG TỔNG)
                const deleteButtons = document.querySelectorAll('.delete-cart-btn');
                deleteButtons.forEach((button) => {
                    button.addEventListener('click', function() {
                        const cartItemId = this.getAttribute('data-cart-id');
                        if (confirm('Bạn có muốn xóa sản phẩm này không?')) {
                            fetch('delete_cart_item.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ id: parseInt(cartItemId) })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    showToast('Đã xóa sản phẩm', 'success');
                                    
                                    // 1. Xóa dòng HTML
                                    const row = document.getElementById('item-row-' + cartItemId);
                                    if(row) row.remove();
                                    
                                    // 2. Tính lại tiền tổng thanh toán
                                    calculateTotal();

                                    // 3. QUAN TRỌNG: Cập nhật "Tổng sản phẩm trong giỏ" (khung vàng)
                                    const totalCountEl = document.getElementById('cart-total-count');
                                    if(totalCountEl) {
                                        // Đếm số dòng .cart-item còn lại
                                        const remainingItems = document.querySelectorAll('.cart-item').length;
                                        totalCountEl.innerText = remainingItems;
                                    }
                                    
                                } else {
                                    showToast(data.message, 'error');
                                }
                            });
                        }
                    });
                });
            });
        </script>
    </body>
</html>