<?php
session_start();
require 'db.php';

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit;
}

$request_id = $_GET['request_id'];

$response_query = "SELECT Responses.message, Responses.response_type, Responses.phone, Responses.contact_time, AutoParts.part_name, AutoParts.price, users.email AS buyer_email FROM Responses JOIN Requests ON Responses.request_id = Requests.request_id JOIN AutoParts ON Requests.part_id = AutoParts.part_id JOIN users ON Requests.user_id = users.id WHERE Responses.request_id = ?";
$response_stmt = $conn->prepare($response_query);
$response_stmt->bind_param("i", $request_id);
$response_stmt->execute();
$response_result = $response_stmt->get_result();
$response = $response_result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ответ на заявку</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e3f2fd;  /* Светло-голубой цвет фона */
            margin: 0;
            padding: 0;
        }

        h2 {
            text-align: center;
            color: #0d47a1; /* Темно-синий цвет заголовка */
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        p {
            color: #555; /* Цвет текста */
            line-height: 1.5;
            margin: 10px 0;
        }

        .response-status {
            font-weight: bold;
            border: 1px solid #0d47a1;
            background-color: #e1f5fe;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 10px 0;
        }

        .contact-info {
            background-color: #e3f2fd;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            display: flex;
            align-items: center;
        }

        .error-message strong {
            margin-right: 10px; /* Пространство между заголовком и сообщением */
        }

        .back-button {
        display: inline-block;
        background-color: #0d47a1; /* Темно-синий цвет */
        color: #ffffff;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        text-align: center;
        margin-top: 20px;
    }

    .back-button:hover {
        background-color: #1565c0; /* Цвет при наведении */
    }

    </style>
</head>
<body>

<div class="container">
    <h2>Ответ на заявку</h2>

    <?php if ($response) : ?>
        <p>Покупатель: <strong><?php echo htmlspecialchars($response['buyer_email']); ?></strong></p>
        <p>Товар: <strong><?php echo htmlspecialchars($response['part_name']); ?></strong></p>
        <p>Цена: <strong><?php echo htmlspecialchars($response['price']); ?> руб.</strong></p>
        
        <div class="contact-info">
            <?php if (!empty($response['phone']) && !empty($response['contact_time'])) : ?>
                <p>Номер телефона: <strong><?php echo htmlspecialchars($response['phone']); ?></strong></p>
                <p>Время контакта: <strong><?php echo htmlspecialchars($response['contact_time']); ?></strong></p>
            <?php endif; ?>
        </div>
        
        <p>Текст ответа: <strong><?php echo htmlspecialchars($response['message']); ?></strong></p>
        
        <div class="response-status">
            Статус ответа: <strong><?php echo htmlspecialchars($response['response_type'] === 'accept' ? 'Принято' : 'Отказано'); ?></strong>
        </div>
    <?php else : ?>
        <div class="error-message">
            <strong>Ошибка:</strong> Ответ не найден.
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 20px;">
        <a href="index.php" class="back-button">Вернуться на главную страницу</a>
    </div>

</div>

</body>
</html>

<?php
$response_stmt->close();
$conn->close();
?>