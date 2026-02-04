<?php
session_start();
include 'db_connect.php';

$message = '';
if (isset($_GET['added']) && $_GET['added'] == '1') {
    $message = 'Sản phẩm đã được thêm vào giỏ hàng!';
}

$product = null;
$images = [];

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    // Query thông tin sản phẩm
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    // Query ảnh sản phẩm
    $img_sql = "SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_main DESC";
    $img_stmt = $conn->prepare($img_sql);
    $img_stmt->bind_param("i", $product_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    while ($img = $img_result->fetch_assoc()) {
        $images[] = $img['image_url'];
    }
    $img_stmt->close();
}

$conn->close();

if (!$product) {
    echo "Sản phẩm không tồn tại.";
    exit();
}

// Tính giá sau thuế
$tax_amount = $product['price'] * ($product['tax_percent'] / 100);
$total_price = $product['price'] + $tax_amount;
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="Pragma" content="no-cache" />
        <meta http-equiv="Expires" content="0" />
        <title><?php echo htmlspecialchars($product['name']); ?> - Grocery Mart</title>

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
        <script>
            console.log("scripts.js loaded, load function:", typeof load);
        </script>
    </head>
    <body>
        <!-- Header -->
        <header id="header" class="header"></header>
        <script>
            load("#header", "./templates/header-logined.php");
        </script>

        <!-- MAIN -->
        <main class="product-page">
            <div class="container">
                <!-- Debug info (Xóa sau khi test xong) -->
                <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; font-size: 12px;">
                    <strong>Debug Info:</strong> Session user_id = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'; ?>
                    <br>isLoggedIn var = <span id="loggedInStatus">checking...</span>
                </div>
                <script>
                    document.getElementById('loggedInStatus').textContent = (<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) ? 'true' : 'false';
                </script>
                
                <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <!-- Search bar -->
                <div class="product-container">
                    <div class="search-bar d-none d-md-flex">
                        <input type="text" name="" id="" placeholder="Search for item" class="search-bar__input" />
                        <button class="search-bar__submit">
                            <img src="./assets/icons/search.svg" alt="" class="search-bar__icon icon" />
                        </button>
                    </div>
                </div>

                <!-- Breadcrumbs -->
                <div class="product-container">
                    <ul class="breadcrumbs">
                        <li>
                            <a href="#!" class="breadcrumbs__link">
                                Departments
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link">
                                Coffee
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link">
                                Coffee Beans
                                <img src="./assets/icons/arrow-right.svg" alt="" />
                            </a>
                        </li>
                        <li>
                            <a href="#!" class="breadcrumbs__link breadcrumbs__link--current"><?php echo htmlspecialchars($product['brand']); ?></a>
                        </li>
                    </ul>
                </div>

                <!-- Product info -->
                <div class="product-container prod-info-content">
                    <div class="row">
                        <div class="col-5 col-xl-6 col-lg-12">
                            <div class="prod-preview">
                                <div class="prod-preview__list">
                                    <?php foreach ($images as $image): ?>
                                    <div class="prod-preview__item">
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="" class="prod-preview__img" />
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="prod-preview__thumbs">
                                    <?php foreach ($images as $index => $image): ?>
                                    <img
                                        src="<?php echo htmlspecialchars($image); ?>"
                                        alt=""
                                        class="prod-preview__thumb-img <?php echo $index == 0 ? 'prod-preview__thumb-img--current' : ''; ?>"
                                    />
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-7 col-xl-6 col-lg-12">
                            <form action="javascript:void(0)" method="POST" class="form" onsubmit="handleAddToCart(event)">
                                <section class="prod-info">
                                    <h1 class="prod-info__heading">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h1>
                                    <div class="row">
                                        <div class="col-5 col-xxl-6 col-xl-12">
                                            <div class="prod-prop">
                                                <img src="./assets/icons/star.svg" alt="" class="prod-prop__icon" />
                                                <h4 class="prod-prop__title">(<?php echo number_format($product['average_score'], 1); ?>) reviews</h4>
                                            </div>
                                            <label for="" class="form__label prod-info__label">Size/Weight</label>
                                            <div class="filter__form-group">
                                                <div class="form__select-wrap">
                                                    <div class="form__select" style="--width: 146px">
                                                        <?php echo htmlspecialchars($product['weight_unit']); ?>
                                                        <img
                                                            src="./assets/icons/select-arrow.svg"
                                                            alt=""
                                                            class="form__select-arrow icon"
                                                        />
                                                    </div>
                                                    <div class="form__select">
                                                        Gram
                                                        <img
                                                            src="./assets/icons/select-arrow.svg"
                                                            alt=""
                                                            class="form__select-arrow icon"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="filter__form-group">
                                                <div class="form__tags">
                                                    <button class="form__tag prod-info__tag">Small</button>
                                                    <button class="form__tag prod-info__tag">Medium</button>
                                                    <button class="form__tag prod-info__tag">Large</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-7 col-xxl-6 col-xl-12">
                                            <div class="prod-props">
                                                <div class="prod-prop">
                                                    <img
                                                        src="./assets/icons/document.svg"
                                                        alt=""
                                                        class="prod-prop__icon icon"
                                                    />
                                                    <h4 class="prod-prop__title">Compare</h4>
                                                </div>
                                                <div class="prod-prop">
                                                    <img
                                                        src="./assets/icons/buy.svg"
                                                        alt=""
                                                        class="prod-prop__icon icon"
                                                    />
                                                    <div>
                                                        <h4 class="prod-prop__title">Delivery</h4>
                                                        <p class="prod-prop__desc">From $6 for 1-3 days</p>
                                                    </div>
                                                </div>
                                                <div class="prod-prop">
                                                    <img
                                                        src="./assets/icons/bag.svg"
                                                        alt=""
                                                        class="prod-prop__icon icon"
                                                    />
                                                    <div>
                                                        <h4 class="prod-prop__title">Pickup</h4>
                                                        <p class="prod-prop__desc">Out of 2 store, today</p>
                                                    </div>
                                                </div>
                                                <div class="prod-info__card">
                                                    <div class="prod-info__row">
                                                        <span class="prod-info__price">$<?php echo number_format($product['price'], 2); ?></span>
                                                        <span class="prod-info__tax"><?php echo $product['tax_percent']; ?>%</span>
                                                    </div>
                                                    <p class="prod-info__total-price">$<?php echo number_format($total_price, 2); ?></p>
                                                    <div class="form__label">Quantity</div>
                                                <div class="cart-item__input" style="width: 120px; margin-bottom: 20px;">
                                                    <button class="cart-item__input-btn" type="button" onclick="decreaseQuantity()">
                                                        <img class="icon" src="./assets/icons/minus.svg" alt="" />
                                                    </button>
                                                    <input type="number" name="product_quantity" id="quantityInput" value="1" min="1" class="form__input" style="text-align: center; width: 50px;">
                                                    <button class="cart-item__input-btn" type="button" onclick="increaseQuantity()">
                                                        <img class="icon" src="./assets/icons/plus.svg" alt="" />
                                                    </button>
                                                </div>

                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                
                                                <div class="prod-info__row">
                                                    <button type="submit" class="btn btn--primary prod-info__add-to-cart">
                                                        Add to cart
                                                    </button>
                                                   
                                                    <button class="like-btn prod-info__like-btn" type="button">
                                                        <img
                                                            src="./assets/icons/heart.svg"
                                                            alt=""
                                                            class="like-btn__icon icon"
                                                        />
                                                        <img
                                                            src="./assets/icons/heart-red.svg"
                                                            alt=""
                                                            class="like-btn__icon--liked"
                                                        />
                                                    </button>
                                                </div>
                                                
                                                <script>
                                                    function increaseQuantity() {
                                                        const input = document.getElementById('quantityInput');
                                                        input.value = parseInt(input.value) + 1;
                                                    }
                                                    function decreaseQuantity() {
                                                        const input = document.getElementById('quantityInput');
                                                        if (parseInt(input.value) > 1) {
                                                            input.value = parseInt(input.value) - 1;
                                                        }
                                                    }
                                                </script>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Product content -->
                <div class="product-container">
                    <div class="prod-tab js-tabs">
                        <ul class="prod-tab__list">
                            <li class="prod-tab__item prod-tab__item--current">Description</li>
                            <li class="prod-tab__item">Review</li>
                            <li class="prod-tab__item">Similar</li>
                        </ul>
                        <div class="prod-tab__contents">
                            <div class="prod-tab__content prod-tab__content--current">
                                <div class="row">
                                    <div class="col-8 col-xl-10 col-lg-12">
                                        <div class="text-content prod-tab__text-content">
                                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="prod-tab__content">
                                <div class="prod-content">
                                    <h2 class="prod-content__heading">What our customers are saying</h2>
                                    <!-- Reviews can be added later -->
                                </div>
                            </div>
                            <div class="prod-tab__content">
                                <div class="prod-content">
                                    <h2 class="prod-content__heading">Similar items you might like</h2>
                                    <!-- Similar products can be added later -->
                                </div>
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

        <!-- Login Modal -->
        <div id="loginModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
            <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <h2 style="margin-bottom: 20px; color: #333; font-size: 24px; font-weight: bold;">Vui lòng đăng nhập</h2>
                <p style="margin-bottom: 30px; color: #666; font-size: 16px;">Bạn cần đăng nhập tài khoản để mua hàng</p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button onclick="redirectToLogin()" style="background: #ed4337; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 500;">
                        Đăng nhập
                    </button>
                    <button onclick="closeLoginModal()" style="background: #f0f0f0; color: #333; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 500;">
                        Hủy
                    </button>
                </div>
            </div>
        </div>

        <script>
            function handleAddToCart(event) {
                event.preventDefault();
                
                // Kiểm tra giá trị thực tế trong Console (F12)
                var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
                console.log("Trạng thái đăng nhập:", isLoggedIn);
                console.log("Kiểm tra modal:", document.getElementById('loginModal'));
                
                if (!isLoggedIn) {
                    var modal = document.getElementById('loginModal');
                    if (modal) {
                        console.log("Modal tìm thấy, hiển thị modal...");
                        modal.style.display = 'flex';
                    } else {
                        console.error("Không tìm thấy ID loginModal trong HTML");
                    }
                    return false;
                }
                
                console.log("User đã đăng nhập, submit form...");
                
                // Nếu đã đăng nhập, submit form
                var form = event.target;
                var formData = new FormData(form);
                
                fetch('add_to_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    console.log("Phản hồi từ server:", data);
                    // Redirect to checkout after successful addition
                    window.location.href = 'checkout.php';
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            function closeLoginModal() {
                var modal = document.getElementById('loginModal');
                modal.style.display = 'none';
            }

            function redirectToLogin() {
                window.location.href = './sign-in.php';
            }

            // Đóng modal khi click ngoài modal
            document.addEventListener('DOMContentLoaded', function() {
                console.log("DOMContentLoaded triggered");
                var modal = document.getElementById('loginModal');
                if (modal) {
                    modal.addEventListener('click', function(event) {
                        if (event.target === this) {
                            closeLoginModal();
                        }
                    });
                }
            });
        </script>
    </body>
</html>
