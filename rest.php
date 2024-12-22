<?php

session_start();
date_default_timezone_set('Europe/Moscow');

function get_db(): mysqli {
   return new mysqli("localhost", "root", "", "bus_world");
}

function unset_session_key($value) {
    if (isset($_SESSION[$value])) {
        unset($_SESSION[$value]);
    }
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

interface IJsonResponce {
    public function jsonSerialize(): string;
}

class ErrorReport implements IJsonResponce {
    private $name;
    private $code;
    private $msg;

    public function __construct($errorName, $errorCode, $errorMSG) {
        $this->name = $errorName;
        $this->code = $errorCode;
        $this->msg = $errorMSG;
    }

    public function jsonSerialize(): string {
        return json_encode(array("error" => get_object_vars($this)), JSON_PRETTY_PRINT);
    }
}

class PageStatement implements IJsonResponce {
    private string $page_active;
    private string $htmlPage;
    private bool $has_auth;
    private string $full_name;

    public function __construct($page_active) {
        $this->page_active = $page_active;
        $_SESSION ['current_page'] = $page_active;
        $this->has_auth = isset($_SESSION['user_id']);
        $this->full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : "";

        $this->htmlPage = file_get_contents("./parts/$page_active.html");
    }

    public function jsonSerialize(): string {
        return json_encode(get_object_vars($this), JSON_PRETTY_PRINT);
    }
}

class PlacesResponce implements IJsonResponce {
    private string $place_from;
    private string $place_to;

    public function __construct($place_from, $place_to) {
        $this->place_from = $place_from;
        $this->place_to = $place_to;
    }

    public function jsonSerialize(): string {
        return json_encode(get_object_vars($this), JSON_PRETTY_PRINT);
    }
}

class MyInfoResponce implements IJsonResponce {
    public int $total_reserved = 0;
    public string $lastname = "";
    public string $firstname = "";
    public string $surname = "";
    public string $bdate = "";
    public string $passport = "";
    public string $phone = "";
    public string $mail = "";

    public function __construct() {
    }

    public function jsonSerialize(): string {
        return json_encode(get_object_vars($this), JSON_PRETTY_PRINT);
    }
}

class PriceResponce implements IJsonResponce {
    public int $price = 0;
    public int $percent = 100;

    public function __construct() {
    }

    public function jsonSerialize(): string {
        return json_encode(get_object_vars($this), JSON_PRETTY_PRINT);
    }
}

class ResultsResponce implements IJsonResponce {
    private string $result;

    public function __construct($result) {
        $this->result = $result;
    }

    public function jsonSerialize(): string {
        return json_encode(get_object_vars($this), JSON_PRETTY_PRINT);
    }
}

function exceptions_error_handler($severity, $message, $filename, $lineno) {
    header("HTTP/1.1 200 OK");
    die((new ErrorReport("php error", 500, "$filename: $lineno> $severity $message"))->jsonSerialize());
}

set_error_handler('exceptions_error_handler');

register_shutdown_function(function () {
    $err = error_get_last();
    if (!is_null($err)) {
        exceptions_error_handler("", $err['message'], $err['file'], $err['line']);
    }
});

function hash_password($value): string {
    $salt = file_get_contents("./salt");
    return hash('sha256', "$salt$value$salt");
}

function do_update_password() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("need_auth", 4, "Требуется авторизация!"))->jsonSerialize());
    } else if(!isset($_POST['repassword']) || $_POST['repassword'] == '') {
        die((new ErrorReport("update_password_error", 2, "Повторите пароль!"))->jsonSerialize());
    } else if($_POST['password'] != $_POST['repassword']) {
        die((new ErrorReport("update_password_error", 2, "Пароли не совпадают!"))->jsonSerialize());
    } else if(strlen($_POST['password']) < 5) {
        die((new ErrorReport("update_password_error", 2, "Пароль слишком простой!"))->jsonSerialize());
    }
    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("update_password_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }
    $stmt = $db->prepare("UPDATE users SET users.password = ? WHERE users.id = ?");
    if (!$stmt) {
        $db->close();
        die((new ErrorReport("update_password_error", 3, "Ошибка db"))->jsonSerialize());
    }
    $pswd = hash_password($_POST['password']);
    $stmt->bind_param("si", $pswd, $_SESSION['user_id']);
    $stmt->execute();
    $count = $stmt->affected_rows;
    $stmt->close();
    if ($count <= 0) {
        die((new ErrorReport("update_password_error", 3, "Ошибка db"))->jsonSerialize());
    } else {
        echo (new PageStatement("profile_page"))->jsonSerialize();
    }
}

function do_register() {
    if(!isset($_POST['phone']) || $_POST['phone'] == '') {
        die((new ErrorReport("register_error", 2, "Телефон не задан!"))->jsonSerialize());
    } else if(!isset($_POST['password']) || $_POST['password'] == '') {
        die((new ErrorReport("register_error", 2, "Пароль не задан!"))->jsonSerialize());
    } else if(!isset($_POST['repassword']) || $_POST['repassword'] == '') {
        die((new ErrorReport("register_error", 2, "Повторите пароль!"))->jsonSerialize());
    } else if($_POST['password'] != $_POST['repassword']) {
        die((new ErrorReport("register_error", 2, "Пароли не совпадают!"))->jsonSerialize());
    } else if(strlen($_POST['password']) < 5) {
        die((new ErrorReport("register_error", 2, "Пароль слишком простой!"))->jsonSerialize());
    }
    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("register_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }

    $currtime = time();
    $db->query("DELETE FROM user_auths WHERE user_auths.valid_until <= $currtime");

    $stmt = $db->prepare("SELECT COUNT(*) FROM users where users.phone = ?");
    if (!$stmt) {
        $db->close();
        die((new ErrorReport("register_error", 3, "Ошибка db"))->jsonSerialize());
    }
    $stmt->bind_param("s", $_POST['phone']);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count != 0) {
        $db->close();
        die((new ErrorReport("register_error", 3, "Пользователь с таким именем существует!"))->jsonSerialize());
    }
    $fst = $db->prepare("INSERT INTO users (lastname,firstname,surname,bdate,passport,phone,mail,password) VALUES (?,?,?,?,?,?,?,?)");
    if (!$fst) {
        $db->close();
        die((new ErrorReport("register_error", 3, "Ошибка db"))->jsonSerialize());
    }
    $pswd = hash_password($_POST['password']);
    $fst->bind_param("ssssssss", $_POST['lastname'], $_POST['firstname'], $_POST['surname'], $_POST['bdate'], $_POST['passport'], $_POST['phone'], $_POST['mail'], $pswd);
    if($fst->execute()) {
        $ast = $db->prepare("INSERT INTO user_auths (user_id,session,valid_until) VALUES (?,?,?)");
        if (!$ast) {
            $fst->close();
            $db->close();
            die((new ErrorReport("register_error", 3, "Ошибка db"))->jsonSerialize());
        }
        $user_id = $db->insert_id;
        $db->query('INSERT INTO stocks (user_id,title,description,value_percent) VALUES ('.$user_id.', "Первая поездка", "Скидка на первую поездку 15%", 15)');
        $session = generateRandomString(64);
        $valid_until = $currtime + 30 * 24 * 60 * 60;
        $ast->bind_param("isi", $user_id, $session, $valid_until);
        $fst->close();
        $ast->execute();
        $ast->close();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = ''.$_POST['firstname'].' '.$_POST['lastname'].' '.$_POST['surname'].'';
        $_SESSION['phone'] = $_POST['phone'];
        $db->close();
        setcookie('session_key', $session, $valid_until);
        echo (new PageStatement("main_page"))->jsonSerialize();
    } else {
        $fst->close();
        $db->close();
        die((new ErrorReport("register_error", 3, "Ошибка db"))->jsonSerialize());
    }
}

function do_auth() {
    if(!isset($_POST['phone']) || $_POST['phone'] == '') {
        die((new ErrorReport("auth_error", 2, "Телефон не задан!"))->jsonSerialize());
    } else if(!isset($_POST['password']) || $_POST['password'] == '') {
        die((new ErrorReport("auth_error", 2, "Пароль не задан!"))->jsonSerialize());
    }
    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("auth_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }

    $currtime = time();
    $db->query("DELETE FROM user_auths WHERE user_auths.valid_until <= $currtime");

    $pswd = hash_password($_POST['password']);
    $stmt = $db->prepare("SELECT users.id, users.phone, users.firstname, users.lastname, users.surname FROM users where users.phone = ? AND users.password = ?");
    if (!$stmt) {
        $db->close();
        die((new ErrorReport("auth_error", 3, "Ошибка db"))->jsonSerialize());
    }
    $stmt->bind_param("ss", $_POST['phone'], $pswd);
    $stmt->execute();
    $stmt->bind_result($user_id, $phone, $firstname, $lastname, $surname);
    $stmt->fetch();
    $stmt->close();
    if (!$user_id) {
        $db->close();
        die((new ErrorReport("auth_error", 4, "Учётные данные не верны!"))->jsonSerialize());
    }
    $ast = $db->prepare("INSERT INTO user_auths (user_id,session,valid_until) VALUES (?,?,?)");
    if (!$ast) {
        $db->close();
        die((new ErrorReport("auth_error", 3, "Ошибка db"))->jsonSerialize());
    }
    $session = generateRandomString(64);
    $valid_until = $currtime + 30 * 24 * 60 * 60;
    $ast->bind_param("isi", $user_id, $session, $valid_until);
    $ast->execute();
    $ast->close();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['full_name'] = ''.$firstname.' '.$lastname.' '.$surname.'';
    $_SESSION['phone'] = $phone;
    $db->close();
    setcookie('session_key', $session, $valid_until);
    echo (new PageStatement("main_page"))->jsonSerialize();
}

function do_unregister() {
    session_unset();
    session_destroy();
    session_write_close();

    $db = get_db();
    if ($db->connect_error)	{
        die((new ErrorReport("unregister_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }

    $currtime = time();
    $db->query("DELETE FROM user_auths WHERE user_auths.valid_until <= $currtime");

    if(isset($_COOKIE['session_key'])) {
        $fst = $db->prepare("DELETE FROM user_auths WHERE user_auths.session=?");
        if (!$fst) {
            $db->close();
            die((new ErrorReport("unregister_error", 3, "Ошибка db"))->jsonSerialize());
        }
        $fst->bind_param("s", $_COOKIE['session_key']);
        $fst->execute();
        $fst->close();
    }
    $db->close();
    setcookie('session_key', "null", time() - 3600);

    echo (new PageStatement("main_page"))->jsonSerialize();
}

function do_remove_account() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("need_auth", 4, "Требуется авторизация!"))->jsonSerialize());
    }

    $db = get_db();
    if ($db->connect_error)	{
        die((new ErrorReport("remove_account", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }

    $currtime = time();
    $db->query("DELETE FROM user_auths WHERE user_auths.valid_until <= $currtime");

    if(isset($_COOKIE['session_key'])) {
        $fst = $db->prepare("DELETE FROM user_auths WHERE user_auths.session=?");
        if (!$fst) {
            $db->close();
            die((new ErrorReport("remove_account", 3, "Ошибка db"))->jsonSerialize());
        }
        $fst->bind_param("s", $_COOKIE['session_key']);
        $fst->execute();
        $fst->close();
    }
    $fst = $db->prepare("DELETE FROM users WHERE users.id=?");
    if (!$fst) {
        $db->close();
        die((new ErrorReport("remove_account", 3, "Ошибка db"))->jsonSerialize());
    }
    $fst->bind_param("i", $_SESSION['user_id']);
    $fst->execute();
    $fst->close();
    $db->close();
    setcookie('session_key', "null", time() - 3600);

    session_unset();
    session_destroy();
    session_write_close();

    echo (new PageStatement("main_page"))->jsonSerialize();
}

function do_fetch_last_page() {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['session_key'])) {
        $db = get_db();
        if ($db->connect_error)	{
            die((new ErrorReport("fetch_last_page", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
        }
        $stmt = $db->prepare("SELECT user_auths.user_id, users.phone, users.firstname, users.lastname, users.surname FROM user_auths left join users on user_auths.user_id = users.id WHERE user_auths.session = ?");
        if (!$stmt) {
            $db->close();
            die((new ErrorReport("fetch_last_page", 3, "Ошибка db"))->jsonSerialize());
        }
        $stmt->bind_param("s", $_COOKIE['session_key']);
        $stmt->execute();
        $stmt->bind_result($user_id, $phone, $firstname, $lastname, $surname);
        $stmt->fetch();
        $stmt->close();
        if ($user_id) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = ''.$firstname.' '.$lastname.' '.$surname.'';
            $_SESSION['phone'] = $phone;
        }
        $db->close();
    }
    echo (new PageStatement(isset($_SESSION['current_page']) ? $_SESSION['current_page'] : "main_page"))->jsonSerialize();
}

function do_fetch_places() {
    $place_from = "";
    $place_to = "";

    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("fetch_places_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }
    $result = $db->query("SELECT DISTINCT routes.place_from, routes.place_to FROM routes");
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if ($row["place_from"]) {
                $place_from = ''.$place_from.'<option>'.$row["place_from"].'</option>';
            }
            if ($row["place_to"]) {
                $place_to = ''.$place_to.'<option>'.$row["place_to"].'</option>';
            }
        }
    }
    $result->close();
    $db->close();
    echo (new PlacesResponce($place_from, $place_to))->jsonSerialize();
}

function do_fetch_my_info() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("need_auth", 4, "Требуется авторизация!"))->jsonSerialize());
    }
    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("fetch_my_info", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }
    $stmt = $db->prepare("SELECT COALESCE(SUM(routes_reservation.place_reserved), 0) as total_reserved, users.lastname, users.firstname, users.surname, users.bdate, users.passport, users.phone, users.mail FROM users LEFT JOIN routes_reservation ON routes_reservation.user_id = users.id WHERE users.id = ? GROUP BY users.lastname, users.firstname, users.surname, users.bdate, users.passport, users.phone, users.mail");
    if (!$stmt) {
        $db->close();
        die((new ErrorReport("fetch_my_info", 3, "Ошибка db"))->jsonSerialize());
    }
    $ret = new MyInfoResponce();
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($ret->total_reserved, $ret->lastname, $ret->firstname, $ret->surname, $ret->bdate, $ret->passport, $ret->phone, $ret->mail);
    $stmt->fetch();
    $stmt->close();
    $db->close();
    echo $ret->jsonSerialize();
}

function do_search() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("need_auth", 4, "Требуется авторизация!"))->jsonSerialize());
    } else if(!isset($_POST['place_from']) || $_POST['place_from'] == '') {
        die((new ErrorReport("search_error", 2, "Место отправления не задан!"))->jsonSerialize());
    } else if(!isset($_POST['place_to']) || $_POST['place_to'] == '') {
        die((new ErrorReport("search_error", 2, "Место назначения не задан!"))->jsonSerialize());
    } else if(!isset($_POST['departure_date']) || $_POST['departure_date'] == '') {
        die((new ErrorReport("search_error", 2, "Дата поездки не задана!"))->jsonSerialize());
    } else {
        $_SESSION['place_from'] = $_POST['place_from'];
        $_SESSION['place_to'] = $_POST['place_to'];
        $_SESSION['departure_date'] = $_POST['departure_date'];
        echo (new PageStatement("search"))->jsonSerialize();
    }
}

function do_fetch_price_info() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("fetch_price_info", 4, "Требуется авторизация!"))->jsonSerialize());
    } else if (!isset($_SESSION['reservation_route_id'])) {
        die((new ErrorReport("fetch_price_info", 4, "Требуется маршрут!"))->jsonSerialize());
    }
    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("fetch_price_info", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }

    $res = new PriceResponce();

    if (isset($_SESSION['use_stock_id'])) {
        $result = $db->execute_query("SELECT stocks.id, stocks.title, stocks.description, stocks.value_percent FROM stocks WHERE stocks.id = ? AND stocks.user_id = ?", [$_SESSION['use_stock_id'], $_SESSION['user_id']]);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if($row) {
                $res->percent = 100 - $row['value_percent'];
                $result->close();
            }
        } else {
            $result->close();
        }
    }
    $result = $db->execute_query("SELECT routes.price FROM routes WHERE routes.id = ?", [$_SESSION['reservation_route_id']]);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if($row) {
            $res->price = $row['price'];
            $result->close();
        }
    } else {
        $result->close();
    }
    $db->close();
    echo $res->jsonSerialize();
}

function do_fetch_stocks() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("fetch_stocks", 4, "Требуется авторизация!"))->jsonSerialize());
    }
    $table = '<thead><tr><th scope="col">#</th><th scope="col">ID</th><th scope="col">Наименование</th><th scope="col">Описание</th><th scope="col">Скидка</th><th scope="col">Использование</th></tr></thead><tbody>';
    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("fetch_routes_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }
    $result = $db->execute_query("SELECT stocks.id, stocks.title, stocks.description, stocks.value_percent FROM stocks WHERE stocks.user_id = ?", [$_SESSION['user_id']]);

    $iter = 0;
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $iter++;
            if (isset($_SESSION['use_stock_id']) && $row["id"] == $_SESSION['use_stock_id']) {
                $table = ''.$table.' <tr><th class="table-primary row">'.$iter.'</th><td>AC'.$row["id"].'</td><td>'.$row["title"].'</td><td>'.$row["description"].'</td><td>'.$row["value_percent"].'%</td><td>Используется</td></tr>';
            } else {
                $table = ''.$table.' <tr><th class="table-primary row">'.$iter.'</th><td>AC'.$row["id"].'</td><td>'.$row["title"].'</td><td>'.$row["description"].'</td><td>'.$row["value_percent"].'%</td><td><button class="btn btn-outline-primary" onclick="use_stock('.$row["id"].')">Использовать</button></td></tr>';
            }
        }
    }
    $table = ''.$table.'</tbody>';
    $result->close();
    $db->close();
    echo (new ResultsResponce($table))->jsonSerialize();
}

function do_fetch_my_reservation_info() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("fetch_my_reservation_info", 4, "Требуется авторизация!"))->jsonSerialize());
    } else if (!isset($_SESSION['reservation_info_route_id'])) {
        die((new ErrorReport("fetch_my_reservation_info", 4, "Требуется выбрать бронированный маршрут!"))->jsonSerialize());
    }
    $table = '<thead><tr><th scope="col">#</th><th scope="col">ID</th><th scope="col">Фамилия</th><th scope="col">Имя</th><th scope="col">Отчество</th><th scope="col">Дата рождения</th><th scope="col">Паспорт</th></tr></thead><tbody>';
    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("fetch_my_reservation_info", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }
    $result = $db->execute_query("SELECT routes_reservation_info.id, routes_reservation_info.lastname, routes_reservation_info.firstname, routes_reservation_info.surname, routes_reservation_info.bdate, routes_reservation_info.passport FROM routes_reservation_info WHERE routes_reservation_info.reservation_id = ?", [$_SESSION['reservation_info_route_id']]);

    $iter = 0;
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $iter++;
            $table = ''.$table.' <tr><th class="table-primary row">'.$iter.'</th><td>PS'.$row["id"].'</td><td>'.$row["lastname"].'</td><td>'.$row["firstname"].'</td><td>'.$row["surname"].'</td><td>'.$row["bdate"].'</td><td>'.$row["passport"].'</td></tr>';
        }
    }
    $table = ''.$table.'</tbody>';
    $result->close();
    $db->close();
    echo (new ResultsResponce($table))->jsonSerialize();
}

function do_fetch_my_reservations() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("fetch_my_reservations", 4, "Требуется авторизация!"))->jsonSerialize());
    }
    $table = '<thead><tr><th scope="col">#</th><th scope="col">Рейс</th><th scope="col">Марка</th><th scope="col">Отпр.</th><th scope="col">Назн.</th><th scope="col">Д. отпр.</th><th scope="col">В. отпр.</th><th scope="col">Д. приб.</th><th scope="col">В. приб.</th><th scope="col">Длительность</th><th scope="col">Стоимость</th><th scope="col">Забр.</th><th scope="col">Удаление</th><th scope="col">Инф.</th></tr></thead><tbody>';

    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("fetch_my_reservations", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }
    $result = $db->execute_query("SELECT routes.id, routes_reservation.id as routes_reservation_id, routes.bus_brand, routes.place_from, routes.place_to, routes.departure_date, routes.departure_time, routes.arrival_date, routes.arrival_time, routes.travel_time, routes_reservation.price, routes_reservation.place_reserved FROM routes_reservation left join routes on routes_reservation.route_id = routes.id WHERE routes_reservation.user_id = ?", [$_SESSION['user_id']]);
    $iter = 0;
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $iter++;
            $table = ''.$table.' <tr><th class="table-primary row">'.$iter.'</th><td>РС'.$row["id"].'</td><td>'.$row["bus_brand"].'</td><td>'.$row["place_from"].'</td><td>'.$row["place_to"].'</td><td>'.$row["departure_date"].'</td><td>'.$row["departure_time"].'</td><td>'.$row["arrival_date"].'</td><td>'.$row["arrival_time"].'</td><td>'.$row["travel_time"].' мин.</td><td>'.$row["price"].'₽</td><td>'.$row["place_reserved"].' мест</td><td><button class="btn btn-outline-danger" onclick="go_to_unreservation('.$row["id"].')">Отмена</button></td><td><button class="btn btn-outline-primary" onclick="go_to_reservation_info('.$row["routes_reservation_id"].')">i</button></td></tr>';
        }
    }
    $table = ''.$table.'</tbody>';
    $result->close();
    $db->close();
    echo (new ResultsResponce($table))->jsonSerialize();
}

function do_fetch_routes() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("fetch_routes_error", 4, "Требуется авторизация!"))->jsonSerialize());
    }
    $table = '<thead><tr><th scope="col">#</th><th scope="col">Рейс</th><th scope="col">Марка</th><th scope="col">Отправление</th><th scope="col">Назначение</th><th scope="col">Дата отпр.</th><th scope="col">Время отпр.</th><th scope="col">Дата приб.</th><th scope="col">Время приб.</th><th scope="col">Длительность</th><th scope="col">Стоимость</th><th scope="col">Вместимость</th><th scope="col">Осталось</th><th scope="col">Вероятность</th><th scope="col">Бронирование</th></tr></thead><tbody>';

    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("fetch_routes_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }
    if (isset($_SESSION['place_from']) && isset($_SESSION['place_to']) && isset($_SESSION['departure_date'])) {
        $result = $db->execute_query("SELECT routes.id, routes.bus_brand, routes.place_from, routes.place_to, routes.departure_date, routes.departure_time, routes.arrival_date, routes.arrival_time, routes.travel_time, routes.price, routes.place_count, COALESCE(SUM(routes_reservation.place_reserved), 0) as place_reserved FROM routes left join routes_reservation on routes.id = routes_reservation.route_id WHERE routes.place_from = ? AND routes.place_to = ? AND routes.departure_date = ? GROUP BY routes.id, routes.place_from, routes.place_to, routes.departure_date, routes.travel_time, routes.price, routes.place_count", [$_SESSION['place_from'], $_SESSION['place_to'], $_SESSION['departure_date']]);
    } else {
        $result = $db->query("SELECT routes.id, routes.bus_brand, routes.place_from, routes.place_to, routes.departure_date, routes.departure_time, routes.arrival_date, routes.arrival_time, routes.travel_time, routes.price, routes.place_count, COALESCE(SUM(routes_reservation.place_reserved), 0) as place_reserved FROM routes left join routes_reservation on routes.id = routes_reservation.route_id
GROUP BY routes.id, routes.bus_brand, routes.place_from, routes.place_to, routes.departure_date, routes.departure_time, routes.arrival_date, routes.arrival_time, routes.travel_time, routes.price, routes.place_count");
    }
    $iter = 0;
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $iter++;
            $place_free = $row["place_count"] - $row["place_reserved"];
            $maybe = floor(($row["place_reserved"] / ($row["place_count"] == 0 ? 1 : $row["place_count"])) * 100);
            if ($place_free <= 0) {
                $table = ''.$table.' <tr><th class="text-danger row">'.$iter.'</th><td class="text-danger">РС'.$row["id"].'</td><td class="text-danger">'.$row["bus_brand"].'</td><td class="text-danger">'.$row["place_from"].'</td><td class="text-danger">'.$row["place_to"].'</td><td class="text-danger">'.$row["departure_date"].'</td><td class="text-danger">'.$row["departure_time"].'</td><td class="text-danger">'.$row["arrival_date"].'</td><td class="text-danger">'.$row["arrival_time"].'</td><td class="text-danger">'.$row["travel_time"].' мин.</td><td class="text-danger">'.$row["price"].'₽</td><td class="text-danger">'.$row["place_count"].' мест</td><td class="text-danger">Выкуплено!</td><td class="text-danger">'.$maybe.'%</td><td></td></tr>';
            } else {
                $table = ''.$table.' <tr><th class="table-primary row">'.$iter.'</th><td>РС'.$row["id"].'</td><td>'.$row["bus_brand"].'</td><td>'.$row["place_from"].'</td><td>'.$row["place_to"].'</td><td>'.$row["departure_date"].'</td><td>'.$row["departure_time"].'</td><td>'.$row["arrival_date"].'</td><td>'.$row["arrival_time"].'</td><td>'.$row["travel_time"].' мин.</td><td>'.$row["price"].'₽</td><td>'.$row["place_count"].' мест</td><td>'.$place_free.' мест</td><td>'.$maybe.'%</td><td><button class="btn btn-outline-primary" onclick="go_to_reservation('.$row["id"].')">Бронь</button></td></tr>';
            }
        }
    }
    $table = ''.$table.'</tbody>';
    $result->close();
    $db->close();
    echo (new ResultsResponce($table))->jsonSerialize();
}

function go_use_stock_id() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("stock", 4, "Требуется авторизация!"))->jsonSerialize());
    } else if(!isset($_POST['use_stock_id']) || $_POST['use_stock_id'] == '') {
        die((new ErrorReport("stock", 2, "Акция не задана!"))->jsonSerialize());
    } else {
        $db = get_db();
        if($db->connect_error)	{
            die((new ErrorReport("auth_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
        }
        $result = $db->execute_query("SELECT COUNT(stocks.id) as stocks_count FROM stocks WHERE stocks.id = ? AND stocks.user_id = ?", [intval($_POST['use_stock_id']), $_SESSION['user_id']]);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if($row && $row['stocks_count'] > 0) {
                $result->close();
                $db->close();
                $_SESSION['use_stock_id'] = intval($_POST['use_stock_id']);
                echo (new PageStatement("main_page"))->jsonSerialize();
                return;
            }
        }
        $result->close();
        $db->close();
        unset_session_key('use_stock_id');
        die((new ErrorReport("stock", 5, "Ошибка!"))->jsonSerialize());
    }
}

function do_go_reservation() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("reservation", 4, "Требуется авторизация!"))->jsonSerialize());
    } else if(!isset($_POST['reservation_route_id']) || $_POST['reservation_route_id'] == '') {
        die((new ErrorReport("reservation", 2, "Маршрут не задан!"))->jsonSerialize());
    } else {
        $db = get_db();
        if($db->connect_error)	{
            die((new ErrorReport("auth_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
        }
        $result = $db->execute_query("SELECT COUNT(routes_reservation.place_reserved) as reserved FROM routes_reservation WHERE routes_reservation.route_id = ? AND routes_reservation.user_id = ?", [intval($_POST['reservation_route_id']), $_SESSION['user_id']]);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if($row && $row['reserved'] > 0) {
                $result->close();
                $db->close();
                die((new ErrorReport("reservation", 5, "Уже забронирован!"))->jsonSerialize());
                return;
            }
        }
        $result->close();
        $db->close();
        $_SESSION['reservation_route_id'] = intval($_POST['reservation_route_id']);
        echo (new PageStatement("reservation"))->jsonSerialize();
    }
}

function do_go_unreservation() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("unreservation", 4, "Требуется авторизация!"))->jsonSerialize());
    } else if(!isset($_POST['reservation_route_id']) || $_POST['reservation_route_id'] == '') {
        die((new ErrorReport("unreservation", 2, "Маршрут не задан!"))->jsonSerialize());
    } else {
        $db = get_db();
        if($db->connect_error)	{
            die((new ErrorReport("auth_error", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
        }
        $db->execute_query("DELETE FROM routes_reservation WHERE routes_reservation.route_id = ? AND routes_reservation.user_id = ?", [intval($_POST['reservation_route_id']), $_SESSION['user_id']]);
        $db->close();
        echo (new PageStatement("reservations_page"))->jsonSerialize();
    }
}

function do_go_reservation_info() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("go_reservation_info", 4, "Требуется авторизация!"))->jsonSerialize());
    } else if(!isset($_POST['reservation_route_id']) || $_POST['reservation_route_id'] == '') {
        die((new ErrorReport("go_reservation_info", 2, "Маршрут не задан!"))->jsonSerialize());
    } else {
        $_SESSION['reservation_info_route_id'] = intval($_POST['reservation_route_id']);
        echo (new PageStatement("reservation_info_page"))->jsonSerialize();
    }
}

function make_reservation() {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("reservation", 4, "Требуется авторизация!"))->jsonSerialize());
    }
    if (!isset($_SESSION['reservation_route_id'])) {
        die((new ErrorReport("reservation", 4, "Нужно выбрать маршрут!"))->jsonSerialize());
    }
    $db = get_db();
    if($db->connect_error)	{
        die((new ErrorReport("reservation", 3, "Ошибка: " . $conn->connect_error))->jsonSerialize());
    }

    $json = json_decode(file_get_contents('php://input'), true);
    if (!$json || count($json) <= 0 || count($json) >= 100) {
        die((new ErrorReport("reservation", 5, "Не корректный json"))->jsonSerialize());
    }

    $id_r = $_SESSION['reservation_route_id'];
    $user_id_r = $_SESSION['user_id'];
    $result = $db->execute_query("SELECT COUNT(routes_reservation.place_reserved) as reserved FROM routes_reservation WHERE routes_reservation.route_id = ? AND routes_reservation.user_id = ?", [$id_r, $user_id_r]);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if($row && $row['reserved'] > 0) {
            $result->close();
            $db->close();
            die((new ErrorReport("reservation", 5, "Уже забронирован!"))->jsonSerialize());
            return;
        }
    }
    $result->close();

    $result1 = $db->execute_query("SELECT routes.price, routes.place_count, COALESCE(SUM(routes_reservation.place_reserved), 0) as place_reserved FROM routes left join routes_reservation on routes.id = routes_reservation.route_id WHERE routes.id = ? GROUP BY routes.price, routes.place_count", [$id_r]);
    $price = 0;
    if ($result1->num_rows > 0) {
        $row = $result1->fetch_assoc();
        if($row) {
            $price = $row['price'] * count($json);
            if($row['place_count'] < $row['place_reserved'] + count($json)) {
                $result1->close();
                $db->close();
                die((new ErrorReport("reservation", 6, "Мест нет!"))->jsonSerialize());
                return;
            }
        }
    }
    $result1->close();

    if($price <= 0) {
        $db->close();
        die((new ErrorReport("reservation", 7, "Внутренняя ошибка цен!"))->jsonSerialize());
        return;
    }

    $percent = 100;
    if (isset($_SESSION['use_stock_id'])) {
        $sto = $db->execute_query("SELECT stocks.id, stocks.title, stocks.description, stocks.value_percent FROM stocks WHERE stocks.id = ? AND stocks.user_id = ?", [$_SESSION['use_stock_id'], $_SESSION['user_id']]);
        if ($sto->num_rows > 0) {
            $row = $sto->fetch_assoc();
            if($row) {
                $percent = 100 - $row['value_percent'];
                $sto->close();

                $db->execute_query("DELETE FROM stocks WHERE stocks.id = ? AND stocks.user_id = ?", [$_SESSION['use_stock_id'], $_SESSION['user_id']]);
            }
        } else {
            $sto->close();
        }
        unset_session_key('use_stock_id');
    }

    $fst = $db->prepare("INSERT INTO routes_reservation (user_id,route_id,place_reserved,price) VALUES (?,?,?,?)");
    if (!$fst) {
        $db->close();
        die((new ErrorReport("reservation", 3, "Ошибка db"))->jsonSerialize());
    }
    $resprice = $price * ($percent / 100);
    $rescount = count($json);
    $fst->bind_param("iiii", $user_id_r, $id_r, $rescount, $resprice);
    if(!$fst->execute()) {
        $db->close();
        die((new ErrorReport("reservation", 3, "Ошибка db"))->jsonSerialize());
    }

    $res_id = $db->insert_id;
    foreach ($json as $tmps) {
        $scnd = $db->prepare("INSERT INTO routes_reservation_info (reservation_id,lastname,firstname,surname,bdate,passport) VALUES (?,?,?,?,?,?)");
        if (!$scnd) {
            $db->close();
            die((new ErrorReport("reservation", 3, "Ошибка db"))->jsonSerialize());
        }
        $scnd->bind_param("isssss", $res_id, $tmps["lastname"], $tmps["firstname"], $tmps["surname"], $tmps["bdate"], $tmps["passport"]);
        if(!$scnd->execute()) {
            $db->close();
            die((new ErrorReport("reservation", 3, "Ошибка db"))->jsonSerialize());
        }
    }
    $db->close();
    echo (new PageStatement("main_page"))->jsonSerialize();
}

function reset_state() {
    unset_session_key('place_from');
    unset_session_key('place_to');
    unset_session_key('departure_date');
    unset_session_key('reservation_route_id');
    unset_session_key('reservation_info_route_id');
}

if(!isset($_GET['action'])) {
    die((new ErrorReport("args_mistmatch", 1, "Не заполнен action!"))->jsonSerialize());
} else if ($_GET['action'] == "fetch_register_page") {
    echo (new PageStatement("register"))->jsonSerialize();
} else if ($_GET['action'] == "fetch_auth_page") {
    echo (new PageStatement("auth"))->jsonSerialize();
} else if ($_GET['action'] == "fetch_main_page") {
    reset_state();
    echo (new PageStatement("main_page"))->jsonSerialize();
} else if ($_GET['action'] == "fetch_stocks_page") {
    reset_state();
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("need_auth", 4, "Требуется авторизация!"))->jsonSerialize());
    } else {
        echo (new PageStatement("stocks"))->jsonSerialize();
    }
} else if ($_GET['action'] == "fetch_reservations_page") {
    reset_state();
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("need_auth", 4, "Требуется авторизация!"))->jsonSerialize());
    } else {
        echo (new PageStatement("reservations_page"))->jsonSerialize();
    }
} else if ($_GET['action'] == "fetch_profile_page") {
    reset_state();
    echo (new PageStatement("profile_page"))->jsonSerialize();
} else if ($_GET['action'] == "fetch_last_page") {
    do_fetch_last_page();
} else if ($_GET['action'] == "fetch_search_page") {
    if (!isset($_SESSION['user_id'])) {
        die((new ErrorReport("need_auth", 4, "Требуется авторизация!"))->jsonSerialize());
    } else {
        echo (new PageStatement("search"))->jsonSerialize();
    }
} else if ($_GET['action'] == "register") {
    do_register();
} else if ($_GET['action'] == "auth") {
    do_auth();
} else if ($_GET['action'] == "unregister") {
    reset_state();
    do_unregister();
} else if ($_GET['action'] == "remove_account") {
    reset_state();
    do_remove_account();
} else if ($_GET['action'] == "update_password") {
    do_update_password();
} else if ($_GET['action'] == "fetch_places") {
    do_fetch_places();
} else if ($_GET['action'] == "fetch_routes") {
    do_fetch_routes();
} else if ($_GET['action'] == "fetch_stocks") {
    do_fetch_stocks();
} else if ($_GET['action'] == "fetch_my_reservations") {
    do_fetch_my_reservations();
} else if ($_GET['action'] == "fetch_my_reservation_info") {
    do_fetch_my_reservation_info();
} else if ($_GET['action'] == "fetch_my_info") {
    do_fetch_my_info();
} else if ($_GET['action'] == "fetch_price_info") {
    do_fetch_price_info();
} else if ($_GET['action'] == "search_route") {
    do_search();
} else if ($_GET['action'] == "go_reservation") {
    unset_session_key('place_from');
    unset_session_key('place_to');
    unset_session_key('departure_date');

    do_go_reservation();
} else if ($_GET['action'] == "go_unreservation") {
    do_go_unreservation();
} else if ($_GET['action'] == "go_reservation_info") {
    do_go_reservation_info();
} else if ($_GET['action'] == "use_stock") {
    go_use_stock_id();
} else if ($_GET['action'] == "make_reservation") {
    make_reservation();
}
?>
