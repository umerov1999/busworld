<?php
    session_start();
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="javascript:void(0)"> <img src="./common/icon.svg" alt="" width="48" height="48"> BusWorld</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" id="main" aria-current="page" href="javascript:void(0)">Главная</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="search" href="javascript:void(0)">Поиск рейса</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="stocks" href="javascript:void(0)">Акции</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="reservations" href="javascript:void(0)">Бронирования</a>
                </li>

            </ul>
            <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="lk" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Личный кабинет
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (!isset($_SESSION['user_id'])):?>
                            <li id="auth_view"><a class="dropdown-item" id="auth" href="javascript:void(0)">Вход</a></li>
                            <li id="register_view"><a class="dropdown-item" id="register" href="javascript:void(0)">Регистрация</a></li>

                            <li id="profile_view" style="display:none;"><a class="dropdown-item" id="profile" href="javascript:void(0)"></a></li>
                            <li id="logout_view" style="display:none;"><a class="dropdown-item" id="logout" href="javascript:void(0)">Выход</a></li>
                        <?php else: ?>
                            <li id="auth_view" style="display:none;"><a class="dropdown-item" id="auth" href="javascript:void(0)">Вход</a></li>
                            <li id="register_view" style="display:none;"><a class="dropdown-item" id="register" href="javascript:void(0)">Регистрация</a></li>

                            <li id="profile_view"><a class="dropdown-item" id="profile" href="javascript:void(0)"><?php echo $_SESSION['full_name']; ?></a></li>
                            <li id="logout_view"><a class="dropdown-item" id="logout" href="javascript:void(0)">Выход</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
        </div>
    </div>
</nav>
