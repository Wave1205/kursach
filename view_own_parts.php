<?php
session_start();
function getDBConnection() {
    $host = 'localhost'; 
    $user = 'root'; 
    $password = ''; 
    $database = 'php_laba3';

    // Подавление ошибок подключения
    $conn = @new mysqli($host, $user, $password, $database);

    // Проверка на наличие ошибки подключения
    if ($conn->connect_error) {
        // Перенаправление на страницу с ошибкой
        header("Location: error.php");
        exit();
    }
    
    return $conn;
}

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 1) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Просмотреть свои товары</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h2>Ваши товары</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Номер детали</th>
            <th>Производитель</th>
            <th>Цена</th>
            <th>Количество в наличии</th>
        </tr>
        
        <?php
        $conn = getDBConnection();
        $email = $_SESSION['email'];
        
        // Извлечение товаров, связанных с текущим пользователем
        $stmt = $conn->prepare("SELECT AutoParts.part_id, AutoParts.part_name, AutoParts.part_number, AutoParts.manufacturer, AutoParts.price, AutoParts.quantity_in_stock 
                                  FROM AutoParts 
                                  JOIN users ON AutoParts.seller_id = users.id
                                  WHERE users.email = ?");
                                  
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['part_id']}</td>
                        <td>" . htmlspecialchars($row['part_name']) . "</td>
                        <td>" . htmlspecialchars($row['part_number']) . "</td>
                        <td>" . htmlspecialchars($row['manufacturer']) . "</td>
                        <td>" . htmlspecialchars($row['price']) . "</td>
                        <td>" . htmlspecialchars($row['quantity_in_stock']) . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>У вас нет товаров.</td></tr>";
        }
        
        $stmt->close();
        $conn->close();
        ?>
    </table>
    
    <button onclick="window.location.href='index.php';">На главную</button>
</body>
</html>