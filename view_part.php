<?php
require 'db.php';
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 2) {
    // Если пользователь не авторизован или не покупатель, перенаправляем на главную страницу
    header("Location: index.php");
    exit;
}

if (!isset($_GET['part_number'])) {
    // Если уникальный номер товара не передан, перенаправляем на главную страницу
    header("Location: view_all_parts.php");
    exit;
}

$part_number = $_GET['part_number'];


$query = "SELECT AutoParts.part_id, AutoParts.part_name, AutoParts.part_number, 
                 AutoParts.price, AutoParts.created_at, AutoParts.updated_at ,
                 users.name AS seller_name, users.email AS seller_email
          FROM AutoParts 
          JOIN users ON AutoParts.seller_id = users.id
          WHERE AutoParts.part_number = ?";
          
$stmt = $conn->prepare($query);


if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}

$stmt->bind_param("s", $part_number);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подробная информация о товаре</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h2>Подробная информация о товаре</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php $row = $result->fetch_assoc(); ?>
        <p><strong>Имя товара:</strong> <?= htmlspecialchars($row['part_name']); ?></p>
        <p><strong>Уникальный номер:</strong> <?= htmlspecialchars($row['part_number']); ?></p>
        <p><strong>Цена:</strong> <?= htmlspecialchars($row['price']); ?> руб.</p>
        <p><strong>Имя продавца:</strong> <?= htmlspecialchars($row['seller_name']); ?></p>
        <p><strong>Email продавца:</strong> <?= htmlspecialchars($row['seller_email']); ?></p>
        <p><strong>Дата создания:</strong> <?= htmlspecialchars($row['created_at']); ?></p>
        <p><strong>Дата обновления:</strong> <?= htmlspecialchars($row['updated_at']); ?></p>
    <?php else: ?>
        <p>Нет информации о товаре.</p>
    <?php endif; ?>

    <p><a href="view_all_parts.php">Вернуться к списку товаров</a></p>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>