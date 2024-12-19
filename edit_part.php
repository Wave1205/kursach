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

$existing_part = null; // Инициализируем переменную для существующей детали
$message = '';

// Функция для проверки, начинается ли строка с заглавной буквы и содержит ли только разрешённые символы
function validateName($name) {
    return preg_match('/^[A-ZА-ЯЁ][a-zа-яё0-9\s\-]*$/u', $name); // Начинается с заглавной, затем буквы, цифры, пробелы и дефисы
}

// Получение данных детали по уникальному номеру
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['part_number']) && !isset($_POST['update_part'])) {
    $part_number = htmlspecialchars($_POST['part_number']);
    
    // Проверка, чтобы номер детали состоял только из цифр
    if (!preg_match('/^[1-9][0-9]*$/', $part_number)) {
        $message = "Номер детали должен быть положительным целым числом.";
    } else {
        $conn = getDBConnection();
    
        // Извлечение детали и seller_id для проверки
        $stmt = $conn->prepare("SELECT part_name, manufacturer, price, quantity_in_stock, seller_id FROM AutoParts WHERE part_number = ?");
        $stmt->bind_param("s", $part_number);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['seller_id'] !== $_SESSION['user_id']) { // Проверка на принадлежность
                $message = "Ошибка: это не ваш уникальный номер. Пожалуйста, введите свой уникальный номер.";
            } else {
                $existing_part = $row;
                $existing_part['part_number'] = $part_number; // Добавляем part_number для использования в форме
            }
        } else {
            $message = "Деталь с уникальным номером '$part_number' не найдена.";
        }
    
        $stmt->close();
        $conn->close();
    }
}

// Обновление детали
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_part'])) {
    $part_number = htmlspecialchars($_POST['part_number']);
    $part_name = htmlspecialchars($_POST['part_name']);
    $manufacturer = htmlspecialchars($_POST['manufacturer']);
    $price = (float)$_POST['price'];
    $quantity_in_stock = (int)$_POST['quantity_in_stock'];

    // Валидация названия, производителя, цены и количества на складе
    if (!validateName($part_name)) {
        $message = "Название должно начинаться с заглавной буквы(без пробелов) и содержать только буквы, цифры, пробелы и дефисы.";
    } elseif (!validateName($manufacturer)) {
        $message = "Производитель должен начинаться с заглавной буквы(без пробелов) и содержать только буквы, цифры, пробелы и дефисы.";
    } elseif ($price < 0) {
        $message = "Цена должна быть положительным числом.";
    } elseif ($quantity_in_stock < 0) {
        $message = "Количество на складе не может быть отрицательным числом.";
    } elseif($price <= 0){
        $message = "Цена должна быть больше нуля.";
    } elseif($quantity_in_stock <= 0){
        $message = "Количество на складе должно быть больше нуля.";
    } else {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("UPDATE AutoParts SET part_name = ?, manufacturer = ?, price = ?, quantity_in_stock = ? WHERE part_number = ? AND seller_id = ?");
        $stmt->bind_param("ssdsii", $part_name, $manufacturer, $price, $quantity_in_stock, $part_number, $_SESSION['user_id']);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Деталь с уникальным номером '$part_number' успешно обновлена.";
            $existing_part = null; // Сбросим существующую деталь после обновления
        } else {
            $message = "Не удалось обновить деталь. Убедитесь, что данные введены корректно.";
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
    <title>Редактировать деталь</title>
    <link rel="stylesheet" type="text/css" href="style.css"> <!-- Подключение CSS -->
</head>
<body>
    <h2>Редактировать деталь</h2>
    
    <?php if (isset($_SESSION['email'])): ?>
        <p>Пользователь: <?php echo htmlspecialchars($_SESSION['email']); ?>.</p>
    <?php endif; ?>

    <?php if (!$existing_part): ?>
        <form method="POST" action="" name="partForm" onsubmit="return validateForm();">
            <input type="text" name="part_number" placeholder="Введите уникальный номер детали" required>
            <button type="submit" class="login-button">Найти</button>
            <button onclick="window.location.href='index.php';">На главную</button>
        </form>
    <?php else: ?>
        <form method="POST" action="" name="partForm" onsubmit="return validateForm();">
            <input type="hidden" name="part_number" value="<?php echo htmlspecialchars($existing_part['part_number']); ?>">
            <input type="text" name="part_name" placeholder="Название" value="<?php echo htmlspecialchars($existing_part['part_name']); ?>" required>
            <input type="text" name="manufacturer" placeholder="Производитель" value="<?php echo htmlspecialchars($existing_part['manufacturer']); ?>">
            <input type="number" name="price" placeholder="Цена" value="<?php echo htmlspecialchars($existing_part['price']); ?>" required step="0.01">
            <input type="number" name="quantity_in_stock" placeholder="Количество на складе" value="<?php echo htmlspecialchars($existing_part['quantity_in_stock']); ?>" required>
            <button type="submit" name="update_part">Обновить деталь</button>
        </form>
    <?php endif; ?>

    <?php if (isset($message)): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    
</body>
</html>