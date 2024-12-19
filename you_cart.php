<?php
session_start();
require 'db.php';

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Обработка запроса на удаление записи из корзины
if (isset($_POST['delete'])) {
    $cart_id = $_POST['cart_id'];
    $delete_query = "DELETE FROM Cart WHERE cart_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $cart_id, $user_id);
    if ($delete_stmt->execute()) {
        echo "<p>Заказ успешно удален из корзины.</p>";
    } else {
        echo "<p>Ошибка при удалении заказа.</p>";
    }
    $delete_stmt->close();
}

// Если пользователь нажимает кнопку отправки заявки
if (isset($_POST['submit_request'])) {
    // Получаем данные о товарах в корзине
    $request_query = "SELECT part_id FROM Cart WHERE user_id = ?";
    $request_stmt = $conn->prepare($request_query);
    $request_stmt->bind_param("i", $user_id);
    $request_stmt->execute();
    $request_result = $request_stmt->get_result();

    while ($row = $request_result->fetch_assoc()) {
        // Вставляем заявку для каждого товара в запросе
        $insert_request = "INSERT INTO Requests (user_id, part_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_request);
        $insert_stmt->bind_param("ii", $user_id, $row['part_id']);
        $insert_stmt->execute();
        $insert_stmt->close();
    }

    // Удаляем товары из корзины после отправки заявки
    $delete_query = "DELETE FROM Cart WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    echo "<p>Заявка успешно отправлена.</p>";
    header("Refresh: 0");
}


// Запрос для получения данных из корзины
$query = "SELECT Cart.cart_id, AutoParts.part_name, AutoParts.price, 
                 users.name AS seller_name, users.email AS seller_email, 
                 Cart.quantity
          FROM Cart 
          JOIN AutoParts ON Cart.part_id = AutoParts.part_id 
          JOIN users ON Cart.seller_id = users.id
          WHERE Cart.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корзина</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>

        .delete-button {
            background-color: #e74c3c; /* Красный цвет */
            color: white; /* Белый текст */
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 5px; /* Сглаженные углы */
            cursor: pointer;
            transition: background-color 0.3s ease; /* Плавный переход */
        }
        .delete-button:hover {
            background-color: #c0392b; /* Темнее при наведении */
        }
    </style>
</head>
<body>
    <h2>Ваша корзина</h2>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Название товара</th>
                <th>Цена</th>
                <th>Имя продавца</th>
                <th>Email продавца</th>
                <th>Количество</th>
                <th>Действия</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['part_name']); ?></td>
                    <td><?= htmlspecialchars($row['price']); ?> руб.</td>
                    <td><?= htmlspecialchars($row['seller_name']); ?></td>
                    <td><?= htmlspecialchars($row['seller_email']); ?></td>
                    <td><?= htmlspecialchars($row['quantity']); ?></td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="cart_id" value="<?= $row['cart_id']; ?>">
                            <input type="submit" class="delete-button" name="delete" value="Удалить">
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <form method="post" action="">
            <button type="submit" name="submit_request">Отправить заявку</button>
        </form>
    <?php else: ?>
        <p>Ваша корзина пуста.</p>
    <?php endif; ?>

    <p><a href="index.php">На главную</a></p>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>