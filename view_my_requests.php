<?php session_start(); require 'db.php';

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 2) {
    header("Location: index.php"); 
    exit;
}


$buyer_id = $_SESSION['user_id'];


$request_query = "
SELECT Requests.request_id, AutoParts.part_name, AutoParts.price,  
       seller.name AS seller_name, seller.email AS seller_email, seller.experience, 
       Requests.created_at, Responses.response_type, Requests.status  
FROM Requests 
JOIN AutoParts ON Requests.part_id = AutoParts.part_id 
JOIN users AS seller ON AutoParts.seller_id = seller.id 
LEFT JOIN Responses ON Requests.request_id = Responses.request_id
WHERE Requests.user_id = ? AND Requests.is_deleted = 0";

$request_stmt = $conn->prepare($request_query);
$request_stmt->bind_param("i", $buyer_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои заявки</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<h2>Мои заявки</h2>
<?php if ($request_result->num_rows > 0): ?>
    <table>
        <tr>
            <th>Название товара</th>
            <th>Цена</th>
            <th>Имя продавца</th>
            <th>Email продавца</th>
            <th>Опыт работы продавца</th>
            <th>Дата заявки</th>
            <th>Статус</th>
        </tr>
        <?php while ($row = $request_result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['part_name']); ?></td>
                <td><?= htmlspecialchars($row['price']); ?> руб.</td>
                <td><?= htmlspecialchars($row['seller_name']); ?></td>
                <td><?= htmlspecialchars($row['seller_email']); ?></td>
                <td><?= htmlspecialchars($row['experience']); ?> лет</td>
                <td><?= htmlspecialchars($row['created_at']); ?></td>
                <td>
    <?php if ($row['status'] === 'dropped'): ?>
        <span>Вы отклонили заявку</span>
    <?php elseif ($row['response_type'] === null): ?>
        <span>Ждите ответа</span>
        <form action="reject_request.php" method="post" onsubmit="return confirm('Вы уверены, что хотите отклонить эту заявку?')">
            <input type="hidden" name="request_id" value="<?= $row['request_id']; ?>">
            <button type="submit" style="background-color: orange; color: white;">отклонить заявку</button>
        </form>
    <?php else: ?>
        <form action="view_my_response.php" method="get">
            <input type="hidden" name="request_id" value="<?= $row['request_id']; ?>">
            <button type="submit" style="background-color: <?= $row['response_type'] === 'accept' ? 'green' : 'red'; ?>; color: white;"><?= $row['response_type'] === 'accept' ? 'Принята' : 'Отклонена'; ?></button>
        </form>
        <?php if ($row['response_type'] === 'reject'): ?>
            <form action="delete_request.php" method="post" onsubmit="return confirm('Вы уверены, что хотите удалить эту заявку?')">
                <input type="hidden" name="request_id" value="<?= $row['request_id']; ?>">
                <button type="submit" style="background-color: orange; color: white;">Удалить заявку</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>У вас нет заявок.</p>
<?php endif; ?>

<p><a href="index.php">На главную</a></p>
</body>
</html>

<?php
$request_stmt->close();
$conn->close();
?>