<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить деталь</title>
    <link rel="stylesheet" type="text/css" href="style.css"> <!-- Подключение CSS -->
</head>
<body>
    <h2>Добавить новую деталь</h2>
    
    <?php if (isset($_SESSION['email'])): ?>
        <p>Пользователь: <?php echo htmlspecialchars($_SESSION['email']); ?>.</p>
        
        <form method="POST" action="">
            <input type="text" name="part_name" placeholder="Название детали" required>
            <input type="text" name="part_number" placeholder="Уникальный номер детали" required>
            <input type="text" name="manufacturer" placeholder="Производитель" required>
            <input type="number" step="0.01" name="price" placeholder="Цена" required>
            <input type="number" name="quantity_in_stock" placeholder="Количество на складе" value="0" required>
            <button type="submit">Добавить деталь</button>
        </form>
        <form action="index.php">
                <button type="submit">Назад на главную</button>
        </form>

        <?php
        // Обработка формы
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $part_name = htmlspecialchars($_POST['part_name']);
            $part_number = htmlspecialchars($_POST['part_number']);
            $manufacturer = htmlspecialchars($_POST['manufacturer']);
            $price = htmlspecialchars($_POST['price']);
            $quantity_in_stock = htmlspecialchars($_POST['quantity_in_stock']);

            // Валидация: название детали должно начинаться с заглавной буквы и содержать только буквы, цифры, пробелы и дефисы
            if (!preg_match('/^[А-ЯЁA-Z].*/u', $part_name)) {
                echo "<p>Ошибка: название детали должно начинаться с заглавной буквы и без пробелов.</p>";
            } else if (!preg_match('/^[А-ЯЁA-Zа-яёA-z0-9\s\-]+$/u', $part_name)) {
                echo "<p>Ошибка: название детали может содержать только буквы, цифры, пробелы и дефисы.</p>";
            }
            // Валидация: производитель должен начинаться с заглавной буквы и содержать только буквы, цифры, пробелы и дефисы
            else if (!preg_match('/^[А-ЯЁA-Z].*/u', $manufacturer)) {
                echo "<p>Ошибка: производитель должен начинаться с заглавной буквы и без пробелов.</p>";
            } else if (!preg_match('/^[А-ЯЁA-Zа-яёA-z0-9\s\-]+$/u', $manufacturer)) {
                echo "<p>Ошибка: производитель может содержать только буквы, цифры, пробелы и дефисы.</p>";
            }
            // Валидация: номер детали должен быть положительным целым числом без пробелов и символов
            else if (!preg_match('/^\d+$/', $part_number) || intval($part_number) <= 0) {
                echo "<p>Ошибка: номер детали должен быть положительным целым числом и не может содержать пробелов или других символов.</p>";
            }
            // Валидация: цена должна быть неотрицательным числом
            else if (!is_numeric($price) || $price < 0) {
                echo "<p>Ошибка: цена должна быть неотрицательным числом.</p>";
            } else if(!is_numeric($price) || $price<=0){
                echo "<p>Ошибка: цена должна быть больше нуля.</p>";
            } 
            // Валидация: количество на складе должно быть неотрицательным целым числом
            else if (!preg_match('/^\d+$/', $quantity_in_stock) || $quantity_in_stock < 0) {
                echo "<p>Ошибка: количество на складе должно быть неотрицательным целым числом.</p>";
            } else if(!preg_match('/^\d+$/', $quantity_in_stock) || $quantity_in_stock <= 0){
                echo "<p>Ошибка: количество на складе должно быть больше нуля.</p>";
            }
            else {
             // Подключение к базе данных в блоке try
             $host = 'localhost';
             $user = 'root';
             $password = '';
             $database = 'php_laba3';

             try {
                 $conn = new mysqli($host, $user, $password, $database);
                 // Проверка на наличие ошибки подключения
                 if ($conn->connect_error) {
                     throw new Exception('Ошибка подключения: ' . $conn->connect_error);
                 }

                 // Проверка уникальности номера детали
                 $check_stmt = $conn->prepare("SELECT COUNT(*) FROM AutoParts WHERE part_number = ?");
                 $check_stmt->bind_param("s", $part_number);
                 $check_stmt->execute();
                 $check_stmt->bind_result($count);
                 $check_stmt->fetch();
                 $check_stmt->close();

                 if ($count > 0) {
                     echo "<p>Ошибка: уже существует деталь с таким номером! Пожалуйста, введите другой номер детали.</p>";
                 } else {
                     // Запрос для вставки новой детали с seller_id
                     $stmt = $conn->prepare("INSERT INTO AutoParts (part_name, part_number, manufacturer, price, quantity_in_stock, seller_id) VALUES (?, ?, ?, ?, ?, ?)");
                     $stmt->bind_param("sssssi", $part_name, $part_number, $manufacturer, $price, $quantity_in_stock, $_SESSION['user_id']);

                     if ($stmt->execute()) {
                         echo "<p>Деталь успешно добавлена!</p>";
                         header('Refresh: 2; URL=index.php'); // Перенаправление через 2 секунды на главную страницу
                     } else {
                         echo "<p>Ошибка при добавлении детали: " . $stmt->error . "</p>";
                     }

                     // Закрываем соединение
                     $stmt->close();
                 }

                 $conn->close();
             } catch (Exception $e) {
                 // Перенаправление на страницу с ошибкой
                 header("Location: error.php");
                 exit();
             }
         }
     }
        ?>
    <?php else: ?>
        <p><a href="register.php">Регистрация</a> | <a href="login.php">Войти</a></p>
    <?php endif; ?>
</body>
</html>