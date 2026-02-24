
<?php
session_start();
include 'db_connect.php'; // Đảm bảo bạn đã có file kết nối DB

if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php'); // Chuyển hướng nếu chưa đăng nhập
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách sản phẩm trong giỏ hàng của user từ bảng cart
$sql = "SELECT c.id, c.quantity, c.price, c.product_name, p.id AS product_id, pi.image_url 
        FROM cart c
        LEFT JOIN products p ON c.product_name = p.name 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
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
    $subtotal += $row['price'] * $row['quantity'];
    $total_quantity += $row['quantity'];
}


$shipping_fee = 0; // Phí ship cố định hoặc tính toán tùy ý
$total_all = $subtotal + $shipping_fee;

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Checkout | Grocery Mart</title>

        <!-- Favicon -->
        <link rel="apple-touch-icon" sizes="76x76" href="./assets/favicon/apple-touch-icon.png" />
        <link rel="icon" type="image/png" sizes="32x32" href="./assets/favicon/favicon-32x32.png" />
        <link rel="icon" type="image/png" sizes="16x16" href="./assets/favicon/favicon-16x16.png" />
        <link rel="manifest" href="./assets/favicon/site.webmanifest" />
        <meta name="msapplication-TileColor" content="#da532c" />
        <meta name="theme-color" content="#ffffff" />

        <!-- Fonts -->
        <link rel="stylesheet" href="./assets/fonts/stylesheet.css" />

        <!-- Styles -->
        <link rel="stylesheet" href="./assets/css/main.css" />

        <!-- Scripts -->
        <script src="./assets/js/scripts.js"></script>
    </head>
    <body>
        <!-- Header -->
        <header id="header" class="header"></header>
        <script>
            load("#header", "./templates/header-logined.php");
        </script>

        <!-- MAIN -->
        <main class="checkout-page">
            <div class="container">
                <!-- Search bar -->
                <div class="checkout-container">
                    <div class="search-bar d-none d-md-flex">
                        <input type="text" name="" id="" placeholder="Search for item" class="search-bar__input" />
                        <button class="search-bar__submit">
                            <img src="./assets/icons/search.svg" alt="" class="search-bar__icon icon" />
                        </button>
                    </div>
                </div>

                <!-- Breadcrumbs -->
                <div class="checkout-container">
                    <ul class="breadcrumbs checkout-page__breadcrumbs">
                        <li>
                            <a href="./" class="breadcrumbs__link">
                                Home
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link breadcrumbs__link--current">Checkout</a>
                        </li>
                    </ul>
                </div>

                <!-- Checkout content -->
                <div class="checkout-container">
                    <!-- Debug Info -->
                    <div style="background: #fff3cd; padding: 15px; margin-bottom: 20px; border: 1px solid #ffc107; border-radius: 5px;">
                        <strong style="color: #856404;">🔍 Thông tin tổng quan giỏ hàng:</strong>
                        <br><br>
                        <br>Tổng sản phẩm trong giỏ: <strong><?php echo count($cart_items); ?></strong>
                        <!-- <br>Người dùng ID: <strong><?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?></strong> -->
                    </div>

                    <div class="row gy-xl-3">
                        <div class="col-8 col-xl-12">
                            <div class="cart-info">
                                <div class="cart-info__list">
                                    <?php if (count($cart_items) > 0): ?>
                                        <?php foreach ($cart_items as $item): ?>
                                            <!-- Cart item -->
                                            <article class="cart-item">
                                                <a href="./product-detail.php?id=<?php echo $item['product_id']; ?>">
                                                    <img
                                                        src="<?php echo $item['image_url'] ?? './assets/img/product/item-1.png'; ?>"
                                                        alt=""
                                                        class="cart-item__thumb"
                                                    />
                                                </a>
                                                <div class="cart-item__content">
                                                    <div class="cart-item__content-left">
                                                        <h3 class="cart-item__title">
                                                            <a href="./product-detail.php?id=<?php echo $item['product_id']; ?>">
                                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                                            </a>
                                                        </h3>
                                                        <p class="cart-item__price-wrap">
                                                            $<?php echo number_format($item['price'], 2); ?> | <span class="cart-item__status">In Stock</span>
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
                                                        <p class="cart-item__total-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                                        <div class="cart-item__ctrl">
                                                            <button class="cart-item__ctrl-btn">
                                                                <img src="./assets/icons/heart-2.svg" alt="" />
                                                                Save
                                                            </button>
                                                            <button
                                                                class="cart-item__ctrl-btn delete-cart-btn"
                                                                data-cart-id="<?php echo $item['id']; ?>"
                                                                type="button"
                                                            >
                                                                <img src="./assets/icons/trash.svg" alt="" />
                                                                Delete
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
                                <div class="cart-info__bottom d-md-none">
                                    <div class="row">
                                        <div class="col-8 col-xxl-7">
                                            <div class="cart-info__continue">
                                                <a href="./" class="cart-info__continue-link">
                                                    <img
                                                        class="cart-info__continue-icon icon"
                                                        src="./assets/icons/arrow-down-2.svg"
                                                        alt=""
                                                    />
                                                    Continue Shopping
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-4 col-xxl-5">
                                            <div class="cart-info__row">
                                                <span>Giá:</span>
                                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                                            </div>
                                            <div class="cart-info__row">
                                

                                                <span>Phí ship: Tính theo đơn vị vận chuyển (Miễn phí cho đơn trên 200k)</span>
                                            </div>
                                            <div class="cart-info__separate"></div>
                                            <div class="cart-info__row cart-info__row--bold">
                                                <span>Tổng thanh toán:</span>
                                                <span>$<?php echo number_format($total_all, 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-xl-12">
                            <div class="cart-info">
                                <div class="cart-info__row">
                                    <span>Giá <span class="cart-info__sub-label">(items)</span></span>
                                    <span><?php echo $total_quantity; ?></span>
                                </div>
                                <div class="cart-info__row">
                                    <span>Giá<span class="cart-info__sub-label">(Total)</span></span>
                                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="cart-info__row">
                                    <span>Phí vận chuyển</span>
                                    <span>$<?php echo number_format($shipping_fee, 2); ?></span>
                                </div>
                                <div class="cart-info__separate"></div>
                                <div class="cart-info__row">
                                    <span>Estimated Total</span>
                                    <span>$<?php echo number_format($total_all, 2); ?></span>
                                </div>
                                <a href="./shipping.php" class="cart-info__next-btn btn btn--primary btn--rounded">
                                    Continue to checkout
                                </a>
                            </div>
                            <div class="cart-info">
                                <a href="#!">
                                    <article class="gift-item">
                                        <div class="gift-item__icon-wrap">
                                            <img src="./assets/icons/gift.svg" alt="" class="gift-item__icon" />
                                        </div>
                                        <div class="gift-item__content">
                                            <h3 class="gift-item__title">Send this order as a gift.</h3>
                                            <p class="gift-item__desc">
                                                Available items will be shipped to your gift recipient.
                                            </p>
                                        </div>
                                    </article>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer id="footer" class="footer"></footer>
        <script>
            load("#footer", "./templates/footer.php");
        </script>

        <!-- Modal: confirm remove shopping cart item -->
        <!-- (Không sử dụng modal nữa - dùng confirm() dialog thay thế) -->

        <script>
            // ===== CHỨC NĂNG XÓA SẢN PHẨM TRONG GIỎ HÀNG =====
            
            // Lấy tất cả các nút xóa sản phẩm
            const deleteButtons = document.querySelectorAll('.delete-cart-btn');
            console.log('✅ Tìm thấy ' + deleteButtons.length + ' nút xóa');
            
            // Gắn sự kiện click cho từng nút xóa
            deleteButtons.forEach((button) => {
                button.addEventListener('click', function() {
                    // Lấy ID của sản phẩm trong giỏ hàng
                    const cartItemId = this.getAttribute('data-cart-id');
                    console.log('👆 Click nút xóa, cart ID: ' + cartItemId);
                    
                    // Lấy tên sản phẩm để hiển thị trong thông báo
                    const productName = this.closest('.cart-item').querySelector('.cart-item__title a').textContent;
                    console.log('📦 Tên sản phẩm: ' + productName);
                    
                    // Hiển thị hộp thoại xác nhận
                    const confirmDelete = confirm('❌ Bạn có muốn xóa sản phẩm này không?\n\n' + productName);
                    
                    if (confirmDelete) {
                        console.log('✅ Người dùng xác nhận xóa, đang gửi request...');
                        
                        // Gửi request đến server để xóa khỏi database
                        fetch('delete_cart_item.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: parseInt(cartItemId)
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('📥 Response từ server:', data);
                            
                            if (data.success) {
                                // ✅ Xóa thành công từ database
                                console.log('✅ Xóa từ database thành công');
                                
                                // Xóa phần tử sản phẩm khỏi giao diện ngay lập tức
                                const cartItemElement = document.querySelector('[data-cart-id="' + cartItemId + '"]').closest('.cart-item');
                                cartItemElement.style.opacity = '0'; // Làm mờ dần
                                
                                setTimeout(() => {
                                    // Xóa hoàn toàn phần tử khỏi DOM sau 300ms
                                    cartItemElement.remove();
                                    console.log('🗑️ Đã xóa khỏi giao diện');
                                    
                                    // Hiển thị thông báo thành công
                                    alert('✅ Xóa sản phẩm thành công!');
                                    
                                    // Reload trang để cập nhật tổng tiền
                                    setTimeout(() => {
                                        location.reload();
                                    }, 500);
                                }, 300);
                            } else {
                                // ❌ Xóa thất bại
                                console.error('❌ Lỗi từ server:', data.message);
                                alert('❌ Lỗi: ' + data.message);
                            }
                        })
                        .catch(error => {
                            // ❌ Lỗi kết nối
                            console.error('❌ Lỗi fetch:', error);
                            alert('❌ Không thể kết nối với server!');
                        });
                    } else {
                        // Người dùng nhấn "Không" hoặc "Hủy"
                        console.log('❌ Người dùng hủy xóa');
                    }
                });
            });

            // ===== CHỨC NĂNG TĂNG/GIẢM SỐ LƯỢNG SẢN PHẨM =====
            
            // Lấy tất cả các nút tăng/giảm số lượng
            const qtyButtons = document.querySelectorAll('.js-qty-change');
            console.log('✅ Tìm thấy ' + qtyButtons.length + ' nút tăng/giảm');
            
            // Gắn sự kiện click cho từng nút tăng/giảm
            qtyButtons.forEach((button) => {
                button.addEventListener('click', function() {
                    // Lấy ID của sản phẩm trong giỏ hàng
                    const cartItemId = this.getAttribute('data-id');
                    // Lấy hành động (increase hoặc decrease)
                    const action = this.getAttribute('data-action');
                    
                    console.log('👆 Click nút ' + action + ', cart ID: ' + cartItemId);
                    
                    // Gửi request đến server để cập nhật số lượng
                    fetch('update_cart_quantity.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: parseInt(cartItemId),
                            action: action
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('📥 Response từ server:', data);
                        
                        if (data.success) {
                            // ✅ Cập nhật thành công từ database
                            console.log('✅ Cập nhật số lượng thành công');
                            
                            // Cập nhật số lượng trên giao diện
                            const qtyElement = document.getElementById('qty-' + cartItemId);
                            if (qtyElement) {
                                qtyElement.textContent = data.new_quantity;
                                console.log('✏️ Cập nhật số lượng hiển thị: ' + data.new_quantity);
                            }
                            
                            // Reload trang để cập nhật tổng tiền
                            setTimeout(() => {
                                location.reload();
                            }, 500);
                        } else {
                            // ❌ Cập nhật thất bại
                            console.error('❌ Lỗi từ server:', data.message);
                            alert('❌ Lỗi: ' + data.message);
                        }
                    })
                    .catch(error => {
                        // ❌ Lỗi kết nối
                        console.error('❌ Lỗi fetch:', error);
                        alert('❌ Không thể kết nối với server!');
                    });
                });
            });
        </script>
    </body>
</html>
