
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
        <title>Shipping | Grocery Mart</title>

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
                            <a href="./checkout.php" class="breadcrumbs__link">
                                Checkout
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link breadcrumbs__link--current">Shipping</a>
                        </li>
                    </ul>
                </div>

                <!-- Checkout content -->
                <div class="checkout-container">
                    <div class="row gy-xl-3">
                        <div class="col-8 col-xl-12">
                            <div class="cart-info">
                                <h1 class="cart-info__heading">1. Shipping, arrives between Mon, May 16—Tue, May 24</h1>
                                <div class="cart-info__separate"></div>

                                <!-- Checkout address -->
                                <div class="user-address">
                                    <div class="user-address__top">
                                        <div>
                                            <h2 class="user-address__title">Shipping address</h2>
                                            <p class="user-address__desc">Where should we deliver your order?</p>
                                        </div>
                                        <button
                                            class="user-address__btn btn btn--primary btn--rounded btn--small js-toggle"
                                            toggle-target="#add-new-address"
                                        >
                                            <img src="./assets/icons/plus.svg" alt="" />
                                            Add a new address
                                        </button>
                                    </div>
                                    <div class="user-address__list">
                                        <!-- Empty message -->
                                        <!-- <p class="user-address__message">
                                            Not address yet.
                                            <a class="user-address__link js-toggle" href="#!" toggle-target="#add-new-address">Add a new address</a>
                                        </p> -->

                                        <!-- Address card 1 -->
                                        <article class="address-card">
                                            <div class="address-card__left">
                                                <div class="address-card__choose">
                                                    <label class="cart-info__checkbox">
                                                        <input
                                                            type="radio"
                                                            name="shipping-adress"
                                                            checked
                                                            class="cart-info__checkbox-input"
                                                        />
                                                    </label>
                                                </div>
                                                <div class="address-card__info">
                                                    <h3 class="address-card__title">Imran Khan</h3>
                                                    <p class="address-card__desc">
                                                        Museum of Rajas, Sylhet Sadar, Sylhet 3100.
                                                    </p>
                                                    <ul class="address-card__list">
                                                        <li class="address-card__list-item">Shipping</li>
                                                        <li class="address-card__list-item">Delivery from store</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="address-card__right">
                                                <div class="address-card__ctrl">
                                                    <button
                                                        class="cart-info__edit-btn js-toggle"
                                                        toggle-target="#add-new-address"
                                                    >
                                                        <img class="icon" src="./assets/icons/edit.svg" alt="" />
                                                        Edit
                                                    </button>
                                                </div>
                                            </div>
                                        </article>

                                        <!-- Address card 2 -->
                                        <article class="address-card">
                                            <div class="address-card__left">
                                                <div class="address-card__choose">
                                                    <label class="cart-info__checkbox">
                                                        <input
                                                            type="radio"
                                                            name="shipping-adress"
                                                            class="cart-info__checkbox-input"
                                                        />
                                                    </label>
                                                </div>
                                                <div class="address-card__info">
                                                    <h3 class="address-card__title">Imran Khan</h3>
                                                    <p class="address-card__desc">
                                                        Al Hamra City (10th Floor), Hazrat Shahjalal Road, Sylhet,
                                                        Sylhet, Bangladesh
                                                    </p>
                                                    <ul class="address-card__list">
                                                        <li class="address-card__list-item">Shipping</li>
                                                        <li class="address-card__list-item">Delivery from store</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="address-card__right">
                                                <div class="address-card__ctrl">
                                                    <button
                                                        class="cart-info__edit-btn js-toggle"
                                                        toggle-target="#add-new-address"
                                                    >
                                                        <img class="icon" src="./assets/icons/edit.svg" alt="" />
                                                        Edit
                                                    </button>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                </div>

                                <div class="cart-info__separate"></div>

                                <h2 class="cart-info__sub-heading">Chi tiết sản phẩm mua</h2>
                                <div class="cart-info__list">
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
                                                <span>Subtotal:</span>
                                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                                            </div>
                                            <div class="cart-info__row">
                                                <span>Shipping:</span>
                                            </div>
                                            <div class="cart-info__separate"></div>
                                            <div class="cart-info__row cart-info__row--bold">
                                                <span>Total:</span>
                                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-xl-12">
                            <div class="cart-info">
                                <div class="cart-info__row">
                                    <span>Subtotal <span class="cart-info__sub-label">(items)</span></span>
                                    <span>3</span>
                                </div>
                                <div class="cart-info__row">
                                    <span>Price <span class="cart-info__sub-label">(Total)</span></span>
                                    <span>191.65 VND</span>
                                </div>
                                <div class="cart-info__row">
                                    <span>Shipping</span>
                                    <span>10.00 VND</span>
                                </div>
                                <div class="cart-info__separate"></div>
                                <div class="cart-info__row">
                                    <span>Estimated Total</span>
                                    <span>201.65 VND</span>
                                </div>
                                <a href="./payment.php" class="cart-info__next-btn btn btn--primary btn--rounded">
                                   Thanh Toán
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

        <!-- Modal: confirm remove shopping cart item -->
        <div id="delete-confirm" class="modal modal--small hide">
            <div class="modal__content">
                <p class="modal__text">Do you want to remove this item from shopping cart?</p>
                <div class="modal__bottom">
                    <button class="btn btn--small btn--outline modal__btn js-toggle" toggle-target="#delete-confirm">
                        Cancel
                    </button>
                    <button
                        class="btn btn--small btn--danger btn--primary modal__btn btn--no-margin js-toggle"
                        toggle-target="#delete-confirm"
                    >
                        Delete
                    </button>
                </div>
            </div>
            <div class="modal__overlay js-toggle" toggle-target="#delete-confirm"></div>
        </div>

        <!-- Modal: address new shipping address -->
        <div id="add-new-address" class="modal hide" style="--content-width: 650px">
            <div class="modal__content">
                <form action="" class="form">
                    <h2 class="modal__heading">Add new shipping address</h2>
                    <div class="modal__body">
                        <div class="form__row">
                            <div class="form__group">
                                <label for="name" class="form__label form__label--small">Name</label>
                                <div class="form__text-input form__text-input--small">
                                    <input
                                        type="text"
                                        name="name"
                                        id="name"
                                        placeholder="Name"
                                        class="form__input"
                                        required
                                        minlength="2"
                                    />
                                    <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                                </div>
                                <p class="form__error">Name must be at least 2 characters</p>
                            </div>
                            <div class="form__group">
                                <label for="phone" class="form__label form__label--small">Phone</label>
                                <div class="form__text-input form__text-input--small">
                                    <input
                                        type="tel"
                                        name="phone"
                                        id="phone"
                                        placeholder="Phone"
                                        class="form__input"
                                        required
                                        minlength="10"
                                    />
                                    <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                                </div>
                                <p class="form__error">Phone must be at least 10 characters</p>
                            </div>
                        </div>
                        <div class="form__group">
                            <label for="address" class="form__label form__label--small">Address</label>
                            <div class="form__text-area">
                                <textarea
                                    name="address"
                                    id="address"
                                    placeholder="Address (Area and street)"
                                    class="form__text-area-input"
                                    required
                                ></textarea>
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />
                            </div>
                            <p class="form__error">Address not empty</p>
                        </div>
                        <div class="form__group">
                            <label for="city" class="form__label form__label--small">City/District/Town</label>
                            <div class="form__text-input form__text-input--small">
                                <input
                                    type="text"
                                    name=""
                                    placeholder="City/District/Town"
                                    id="city"
                                    readonly
                                    class="form__input js-toggle"
                                    toggle-target="#city-dialog"
                                />
                                <img src="./assets/icons/form-error.svg" alt="" class="form__input-icon-error" />

                                <!-- Select dialog -->
                                <div id="city-dialog" class="form__select-dialog hide">
                                    <h2 class="form__dialog-heading d-none d-sm-block">City/District/Town</h2>
                                    <button
                                        class="form__close-dialog d-none d-sm-block js-toggle"
                                        toggle-target="#city-dialog"
                                    >
                                        &times
                                    </button>
                                    <div class="form__search">
                                        <input type="text" placeholder="Search" class="form__search-input" />
                                        <img src="./assets/icons/search.svg" alt="" class="form__search-icon icon" />
                                    </div>
                                    <ul class="form__options-list">
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option form__option--current">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                        <li class="form__option">Ha Noi</li>
                                        <li class="form__option">Ho Chi Minh</li>
                                        <li class="form__option">Da Nang</li>
                                    </ul>
                                </div>
                            </div>
                            <p class="form__error">Phone must be at least 11 characters</p>
                        </div>
                        <div class="form__group form__group--inline">
                            <label class="form__checkbox">
                                <input type="checkbox" name="" id="" class="form__checkbox-input d-none" />
                                <span class="form__checkbox-label">Set as default address</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal__bottom">
                        <button class="btn btn--small btn--text modal__btn js-toggle" toggle-target="#add-new-address">
                            Cancel
                        </button>
                        <button
                            class="btn btn--small btn--primary modal__btn btn--no-margin js-toggle"
                            toggle-target="#add-new-address"
                        >
                            Create
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal__overlay"></div>
        </div>
    </body>
</html>
