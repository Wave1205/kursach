<?php
$host = 'localhost'; // или IP-адрес вашего сервера
$user = 'root'; // ваше имя пользователя
$password = ''; // ваш пароль
$database = 'php_laba3';

$conn = new mysqli($host, $user, $password, $database);

 // Проверка на наличие ошибки подключения
 if ($conn->connect_error) {
    // Перенаправление на страницу с ошибкой
    header("Location: error.php");
    exit();
}
?>