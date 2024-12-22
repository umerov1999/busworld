<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function get_db(): mysqli {
   return new mysqli("localhost", "root", "", "bus_world");
}

#mysql -u root -pPASSWORD bus_world
#CREATE DATABASE bus_world DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;

date_default_timezone_set('Europe/Moscow');

$db = get_db();
if($db->connect_error)	{
    die("Ошибка: " . $conn->connect_error);
}

session_start();
setcookie('session_key', "null", time() - 3600);
session_unset();
session_destroy();
session_write_close();

$db->query("DROP TABLE IF EXISTS user_auths;");
$db->query("DROP TABLE IF EXISTS stocks;");
$db->query("DROP TABLE IF EXISTS routes_reservation_info;");
$db->query("DROP TABLE IF EXISTS routes_reservation;");
$db->query("DROP TABLE IF EXISTS users;");
$db->query("DROP TABLE IF EXISTS routes;");

$db->query("CREATE TABLE IF NOT EXISTS users (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, lastname VARCHAR(60), firstname VARCHAR(60), surname VARCHAR(60), bdate DATE, passport VARCHAR(12), phone VARCHAR(15) NOT NULL UNIQUE, mail VARCHAR(30) NOT NULL UNIQUE, password VARCHAR(64) NOT NULL);");

$db->query("CREATE TABLE IF NOT EXISTS user_auths (user_id INTEGER, session VARCHAR(64), valid_until INTEGER, FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE);");
$db->query("CREATE TABLE IF NOT EXISTS routes (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, bus_brand VARCHAR(25), place_from VARCHAR(60), place_to VARCHAR(60), departure_date DATE, departure_time TIME, arrival_date DATE, arrival_time TIME, travel_time INTEGER NOT NULL, price INTEGER NOT NULL, place_count INTEGER NOT NULL);");
$db->query("CREATE TABLE IF NOT EXISTS routes_reservation (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, user_id INTEGER NOT NULL, route_id INTEGER NOT NULL, place_reserved INTEGER NOT NULL, price INTEGER NOT NULL, FOREIGN KEY (route_id) REFERENCES routes (id) ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE);");
$db->query("CREATE TABLE IF NOT EXISTS routes_reservation_info (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, reservation_id INTEGER NOT NULL, lastname VARCHAR(60), firstname VARCHAR(60), surname VARCHAR(60), bdate DATE, passport VARCHAR(12), FOREIGN KEY (reservation_id) REFERENCES routes_reservation (id) ON DELETE CASCADE ON UPDATE CASCADE);");
$db->query("CREATE TABLE IF NOT EXISTS stocks (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, user_id INTEGER NOT NULL, title VARCHAR(40) NOT NULL, description VARCHAR(60) NOT NULL, value_percent INTEGER NOT NULL, FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE);");

$db->query("INSERT INTO routes (bus_brand,place_from,place_to,departure_date,departure_time,arrival_date,arrival_time,travel_time,price,place_count) VALUES ('Газель', 'Москва', 'Видное', '" . date('Y-m-d', time()) . "', '07:30:00', '" . date('Y-m-d', time()) . "', '08:15:00', 45, 70, 18)");
$db->query("INSERT INTO routes (bus_brand,place_from,place_to,departure_date,departure_time,arrival_date,arrival_time,travel_time,price,place_count) VALUES ('Урал', 'Москва', 'Видное', '" . date('Y-m-d', time() + 24 * 60 * 60) . "', '09:35:00', '" . date('Y-m-d', time() + 24 * 60 * 60) . "', '10:10:00', 35, 85, 1)");
$db->query("INSERT INTO routes (bus_brand,place_from,place_to,departure_date,departure_time,arrival_date,arrival_time,travel_time,price,place_count) VALUES ('Газель', 'Видное', 'Москва', '" . date('Y-m-d', time() + 48 * 60 * 60) . "', '15:42:00', '" . date('Y-m-d', time() + 48 * 60 * 60) . "', '16:37:00', 55, 63, 7)");

$db->close();
?>
