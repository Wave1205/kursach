<?php
session_start();

function getDBConnection() {
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'php_laba3';

    try {
        $conn = new mysqli($host, $user, $password, $database);

        // Проверка на наличие ошибки подключения
        if ($conn->connect_error) {
            throw new Exception("Ошибка подключения: " . $conn->connect_error);
        }

        return $conn;
    } catch (Exception $e) {
        // Логируем ошибку, если это необходимо
        // error_log($e->getMessage());

        // Перенаправление на страницу с ошибкой
        header("Location: error.php");
        exit();
    }
}

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

// Обработка поиска
$search_result = [];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $part_number = $_POST['part_number'];
    
    // Валидация номера детали
    if (!is_numeric($part_number)) {
        $message = "Номер детали должен содержать только цифры.";
    } elseif ($part_number < 0) {
        $message = "Номер детали должен быть положительным числом.";
    }elseif($part_number<= 0 ){
        $message = "Номер детали должен быть больше нулю";
    } else {
        $part_number = htmlspecialchars($part_number);
        
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT * FROM AutoParts WHERE part_number = ? AND seller_id = ?");
        $stmt->bind_param("ss", $part_number, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $search_result = $result->fetch_assoc();
        } else {
            $message = "Деталь не найдена или она не принадлежит вам.";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Поиск детали</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h2>Поиск детали</h2>
    <form method="POST" action="">
        <input type="number" name="part_number" placeholder="Уникальный номер детали" required>
        <button type="submit">Поиск</button>
    </form>
    
    <?php if ($search_result): ?>
        <h3>Детали:</h3>
        <p>Название: <?= htmlspecialchars($search_result['part_name']); ?></p>
        <p>Производитель: <?= htmlspecialchars($search_result['manufacturer']); ?></p>
        <p>Цена: <?= htmlspecialchars($search_result['price']); ?> Руб.</p>
        <p>Количество на складе: <?= htmlspecialchars($search_result['quantity_in_stock']); ?></p>
    <?php else: ?>
        <p><?= htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <button onclick="window.location.href='index.php';">На главную</button>
</body>
</html>