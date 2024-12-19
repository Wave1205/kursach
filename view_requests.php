<?php
session_start();
require 'db.php';

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit;
}

$seller_id = $_SESSION['user_id'];


$request_query = "SELECT Requests.request_id, users.email AS buyer_email, 
                         AutoParts.part_number, AutoParts.part_name, 
                         Requests.created_at, Requests.status
                  FROM Requests
                  JOIN AutoParts ON Requests.part_id = AutoParts.part_id
                  JOIN users ON Requests.user_id = users.id
                  WHERE AutoParts.seller_id = ?";
$request_stmt = $conn->prepare($request_query);
$request_stmt->bind_param("i", $seller_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    $delete_request_query = "DELETE FROM Requests WHERE request_id = ?";
    $delete_response_query = "DELETE FROM Responses WHERE request_id = ?";
    
    $delete_response_stmt = $conn->prepare($delete_response_query);
    $delete_response_stmt->bind_param("i", $request_id);
    $delete_response_stmt->execute();
    $delete_response_stmt->close();

    $delete_request_stmt = $conn->prepare($delete_request_query);
    $delete_request_stmt->bind_param("i", $request_id);
    
    if ($delete_request_stmt->execute()) {
        echo "<p>Заявка успешно удалена.</p>";
    } else {
        echo "<p>Ошибка при удалении заявки.</p>";
    }

    $delete_request_stmt->close();

    $request_stmt->execute();
    $request_result = $request_stmt->get_result();

    $max_interaction_query = "
        SELECT MAX(interaction_count) AS max_interaction
        FROM (
            SELECT COALESCE(SUM(UserInteractions.interaction_count), 0) AS interaction_count
            FROM users
            LEFT JOIN UserInteractions ON users.id = UserInteractions.seller_id
            GROUP BY users.id
        ) AS interaction_counts";

    $max_stmt = $conn->prepare($max_interaction_query);
    $max_stmt->execute();
    $max_result = $max_stmt->get_result();
    $max_row = $max_result->fetch_assoc();
    $max_interaction = $max_row['max_interaction'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заявки</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h2>Заявки на ваши товары</h2>
    <?php if ($request_result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Покупатель (Email)</th>
                <th>Уникальный номер товара</th>
                <th>Название товара</th>
                <th>Дата заявки</th>
                <th>Действия</th>
                <th>Статус</th>
            </tr>
            <?php while ($row = $request_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['buyer_email']); ?></td>
                    <td><?= htmlspecialchars($row['part_number']); ?></td>
                    <td><?= htmlspecialchars($row['part_name']); ?></td>
                    <td><?= htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <?php if ($row['status'] === 'deleted'): ?>
                            <span>Покупатель удалил заявку</span>
                            <form action="" method="post" onsubmit="return confirm('Вы уверены, что хотите удалить эту заявку?')">
                                <input type="hidden" name="request_id" value="<?= $row['request_id']; ?>">
                                <button type="submit" style="background-color: red; color: white;">Удалить заявку</button>
                            </form>
                        <?php elseif ($row['status'] === 'dropped'): ?>
                            <span>Покупатель отклонил заявку</span>
                            <form action="" method="post" onsubmit="return confirm('Вы уверены, что хотите удалить эту заявку?')">
                                <input type="hidden" name="request_id" value="<?= $row['request_id']; ?>">
                                <button type="submit" style="background-color: red; color: white;">Удалить заявку</button>
                            </form>
                        <?php else: 
                            
                            $response_check_query = "SELECT * FROM Responses WHERE request_id = ?";
                            $response_check_stmt = $conn->prepare($response_check_query);
                            $response_check_stmt->bind_param("i", $row['request_id']);
                            $response_check_stmt->execute();
                            $response_check_result = $response_check_stmt->get_result();
                            
                            if ($response_check_result->num_rows > 0): 
                                echo '<a href="view_response.php?request_id=' . $row['request_id'] . '">Просмотреть ответ</a>';
                            else: 
                                echo '<a href="respond_to_request.php?request_id=' . $row['request_id'] . '">Ответить</a>';
                            endif;

                            $response_check_stmt->close(); 
                        endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>У вас нет новых заявок.</p>
    <?php endif; ?>

    <p><a href="index.php">На главную</a></p>
</body>
</html>

<?php
$request_stmt->close();
$conn->close();
?>