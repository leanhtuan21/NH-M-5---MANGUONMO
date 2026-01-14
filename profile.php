<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Hồ sơ cá nhân | Grocery Mart</title>

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
        <main class="profile">
            <div class="container">
                <!-- Search bar -->
                <div class="profile-container">
                    <div class="search-bar d-none d-md-flex">
                        <input type="text" placeholder="Tìm kiếm sản phẩm" class="search-bar__input" />
                        <button class="search-bar__submit">
                            <img src="./assets/icons/search.svg" alt="" class="search-bar__icon icon" />
                        </button>
                    </div>
                </div>

                <!-- Profile content -->
                <div class="profile-container">
                    <div class="row gy-md-3">
                        <div class="col-3 col-xl-4 col-lg-5 col-md-12">
                            <aside class="profile__sidebar">
                                <!-- User -->
                                <div class="profile-user">
                                    <img src="./assets/img/avatar/avatar-3.png" alt="" class="profile-user__avatar" />
                                    <h1 class="profile-user__name">Imran Khan</h1>
                                    <p class="profile-user__desc">Ngày đăng ký: 17/05/2022</p>
                                </div>

                                <!-- Menu 1 -->
                                <div class="profile-menu">
                                    <h3 class="profile-menu__title">Quản lý tài khoản</h3>
                                    <ul class="profile-menu__list">
                                        <li>
                                            <a href="./edit-personal-info.php" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/profile.svg" alt="" class="icon" />
                                                </span>
                                                Thông tin cá nhân
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/message-2.svg" alt="" class="icon" />
                                                </span>
                                                Quyền riêng tư & liên hệ
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Menu 2 -->
                                <div class="profile-menu">
                                    <h3 class="profile-menu__title">Sản phẩm của tôi</h3>
                                    <ul class="profile-menu__list">
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/download.svg" alt="" class="icon" />
                                                </span>
                                                Đặt lại đơn hàng
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/heart.svg" alt="" class="icon" />
                                                </span>
                                                Danh sách yêu thích
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/gift-2.svg" alt="" class="icon" />
                                                </span>
                                                Quà tặng
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Menu 3 -->
                                <div class="profile-menu">
                                    <h3 class="profile-menu__title">Gói & đăng ký</h3>
                                    <ul class="profile-menu__list">
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/shield.svg" alt="" class="icon" />
                                                </span>
                                                Gói bảo vệ
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Menu 4 -->
                                <div class="profile-menu">
                                    <h3 class="profile-menu__title">Hỗ trợ khách hàng</h3>
                                    <ul class="profile-menu__list">
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/info.svg" alt="" class="icon" />
                                                </span>
                                                Trợ giúp
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#!" class="profile-menu__link">
                                                <span class="profile-menu__icon">
                                                    <img src="./assets/icons/danger.svg" alt="" class="icon" />
                                                </span>
                                                Điều khoản sử dụng
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </aside>
                        </div>

                        <div class="col-9 col-xl-8 col-lg-7 col-md-12">
                            <div class="cart-info">
                                <div class="row gy-3">

                                    <!-- Account info -->
                                    <div class="col-12">
                                        <h2 class="cart-info__heading">Thông tin tài khoản</h2>
                                        <p class="cart-info__desc profile__desc">
                                            Địa chỉ, thông tin liên hệ và mật khẩu
                                        </p>

                                        <div class="row gy-md-2 row-cols-2 row-cols-lg-1">
                                            <div class="col">
                                                <a href="./edit-personal-info.php">
                                                    <article class="account-info">
                                                        <div class="account-info__icon">
                                                            <img src="./assets/icons/message.svg" alt="" class="icon" />
                                                        </div>
                                                        <div>
                                                            <h3 class="account-info__title">Email</h3>
                                                            <p class="account-info__desc">tarek97.ta@gmail.com</p>
                                                        </div>
                                                    </article>
                                                </a>
                                            </div>

                                            <div class="col">
                                                <a href="./edit-personal-info.php">
                                                    <article class="account-info">
                                                        <div class="account-info__icon">
                                                            <img src="./assets/icons/calling.svg" alt="" class="icon" />
                                                        </div>
                                                        <div>
                                                            <h3 class="account-info__title">Số điện thoại</h3>
                                                            <p class="account-info__desc">+000 11122 2345 657</p>
                                                        </div>
                                                    </article>
                                                </a>
                                            </div>

                                            <div class="col">
                                                <a href="./edit-personal-info.php">
                                                    <article class="account-info">
                                                        <div class="account-info__icon">
                                                            <img src="./assets/icons/location.svg" alt="" class="icon" />
                                                        </div>
                                                        <div>
                                                            <h3 class="account-info__title">Địa chỉ</h3>
                                                            <p class="account-info__desc">
                                                                Đại sứ quán Bangladesh, Washington, DC 20008
                                                            </p>
                                                        </div>
                                                    </article>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <h2 class="cart-info__heading">Danh sách yêu thích</h2>
                                        <p class="cart-info__desc profile__desc">2 sản phẩm - Chính</p>

                                        <article class="favourite-item">
                                            <img src="./assets/img/product/item-1.png" alt="" class="favourite-item__thumb" />
                                            <div>
                                                <h3 class="favourite-item__title">
                                                    Hạt cà phê Espresso Arabica & Robusta
                                                </h3>
                                                <div class="favourite-item__content">
                                                    <span class="favourite-item__price">$47.00</span>
                                                    <button class="btn btn--primary btn--rounded">
                                                        Thêm vào giỏ hàng
                                                    </button>
                                                </div>
                                            </div>
                                        </article>

                                        <div class="separate" style="--margin: 20px"></div>

                                        <article class="favourite-item">
                                            <img src="./assets/img/product/item-2.png" alt="" class="favourite-item__thumb" />
                                            <div>
                                                <h3 class="favourite-item__title">
                                                    Cà phê Lavazza – Hương vị Espresso Ý
                                                </h3>
                                                <div class="favourite-item__content">
                                                    <span class="favourite-item__price">$53.00</span>
                                                    <button class="btn btn--primary btn--rounded">
                                                        Thêm vào giỏ hàng
                                                    </button>
                                                </div>
                                            </div>
                                        </article>
                                    </div>

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

        <!-- Modal -->
        <div id="delete-confirm" class="modal modal--small hide">
            <div class="modal__content">
                <p class="modal__text">Bạn có muốn xóa sản phẩm này khỏi giỏ hàng không?</p>
                <div class="modal__bottom">
                    <button class="btn btn--small btn--outline modal__btn js-toggle" toggle-target="#delete-confirm">
                        Hủy
                    </button>
                    <button
                        class="btn btn--small btn--danger btn--primary modal__btn btn--no-margin js-toggle"
                        toggle-target="#delete-confirm"
                    >
                        Xóa
                    </button>
                </div>
            </div>
            <div class="modal__overlay js-toggle" toggle-target="#delete-confirm"></div>
        </div>
    </body>
</html>
