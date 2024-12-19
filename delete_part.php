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

// Удаление детали
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $part_number = $_POST['part_number'];
    
    // Валидация номера детали
    if (!is_numeric($part_number)) {
        $message = "Ошибка: Номер детали должен содержать только цифры.";
    } elseif ($part_number < 0) {
        $message = "Ошибка: Номер детали должен быть положительным числом.";
    }elseif($part_number <= 0){
        $message = "Ошибка: Номер детали должен быть больше нуля.";
    } else {
        $part_number = htmlspecialchars($part_number);
        
        $conn = getDBConnection();

        $stmt = $conn->prepare("DELETE FROM AutoParts WHERE part_number = ? AND seller_id = ?");
        $stmt->bind_param("ss", $part_number, $_SESSION['user_id']);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Деталь с номером $part_number успешно удалена.";
        } else {
            $message = "Не удалось удалить деталь. Убедитесь, что деталь принадлежит вам и номер введён корректно.";
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
    <title>Удалить деталь</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h2>Удалить деталь</h2>
    <form method="POST" action="">
        <input type="number" name="part_number" placeholder="Уникальный номер детали" required>
        <button type="submit">Удалить</button>
    </form>
    
    <?php if (!empty($message)): ?>
        <p><?= htmlspecialchars($message); ?></p>
    <?php endif; ?>
    
    <button onclick="window.location.href='index.php';">На главную</button>
</body>
</html>