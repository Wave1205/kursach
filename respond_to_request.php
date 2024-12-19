<?php
session_start();
require 'db.php';

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Получаем ID заявки
$request_id = $_GET['request_id'];

// Получаем информацию о заявке
$request_query = "SELECT Requests.request_id, users.email AS buyer_email,
                         AutoParts.part_name, AutoParts.price
                  FROM Requests
                  JOIN AutoParts ON Requests.part_id = AutoParts.part_id
                  JOIN users ON Requests.user_id = users.id
                  WHERE Requests.request_id = ? LIMIT 1";
$request_stmt = $conn->prepare($request_query);
$request_stmt->bind_param("i", $request_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();
$row = $request_result->fetch_assoc();
$message = ''; // Переменная для вывода сообщения об ошибке или успехе

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response_message = $_POST['response_message'];
    $response_type = $_POST['response_type'];
    $phone = $_POST['phone'];
    $contact_time = $_POST['contact_time'];

    // Валидация номера телефона и времени связи
    if ($response_type === 'accept') {
        $phone_pattern = '/^\+375 \(\d{2}\) \d{3}-\d{2}-\d{2}$/';

        if (!preg_match($phone_pattern, $phone)) {
            $message = "Ошибка: Номер телефона должен быть в формате +375 (xx) xxx-xx-xx.";
        } elseif (empty($contact_time)) {
            $message = "Ошибка: Время связи обязательно для заполнения.";
        } else {
            // Код для сохранения ответа в базу данных
            $response_query = "INSERT INTO Responses (request_id, message, response_type, phone, contact_time) 
                               VALUES (?, ?, ?, ?, ?)";
            $response_stmt = $conn->prepare($response_query);

            $response_stmt->bind_param("issss", $request_id, $response_message, $response_type, $phone, $contact_time);
            
            if ($response_stmt->execute()) {
                $message = "Ответ на заявку успешно отправлен.";
            } else {
                $message = "Ошибка: не удалось сохранить ответ. Попробуйте еще раз.";
            }
        }
    }

    if ($response_type === 'reject') {
        // Здесь вы можете сохранить ответ на отклонение.
        $response_query = "INSERT INTO Responses (request_id, message, response_type) 
                           VALUES (?, ?, ?)";
        $response_stmt = $conn->prepare($response_query);
        $response_stmt->bind_param("iss", $request_id, $response_message, $response_type);
        
        if ($response_stmt->execute()) {
            $message = "Ответ на заявку успешно отправлен.";
        } else {
            $message = "Ошибка: не удалось сохранить ответ. Попробуйте еще раз.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ответ на заявку</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        /* Ваши стили */
        label {
            font-weight: bold;
            display: block;
            margin: 10px 0 5px;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: none;
        }

        input[type="text"],
        input[type="datetime-local"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #0056b3;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
        }

        button:hover {
            background-color: #004494;
        }

        .hidden-fields {
            display: none;
            margin-top: 10px;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #0056b3;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ответ на заявку от покупателя <?= htmlspecialchars($row['buyer_email']); ?></h2>
        <p>Товар: <strong><?= htmlspecialchars($row['part_name']); ?></strong></p>
        <p>Цена: <strong><?= htmlspecialchars($row['price']); ?> руб.</strong></p>

        <?php if (!empty($message)): ?>
            <p style="color: red;"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="post" action="" class="response-form">
            <input type="hidden" name="request_id" value="<?= $request_id; ?>">
            <label for="response_message">Ваше сообщение:</label>
            <textarea name="response_message" id="response_message" rows="5" required></textarea>

            <label for="response_type">Тип ответа:</label>
            <select name="response_type" required onchange="toggleFields(this.value)">
                <option value="" disabled selected>Ничего не выбрано</option>
                <option value="accept">Принять заявку</option>
                <option value="reject">Отказать в заявке</option>
            </select>

            <div id="accept_fields" class="hidden-fields" style="<?= isset($_POST['response_type']) && $_POST['response_type'] === 'accept' ? '' : 'display:none;' ?>">
                <label for="phone">Номер телефона:</label>
                <input type="text" name="phone" id="phone" placeholder="375 (xx)-xxx-xx-xx" maxlength="19" value="<?= htmlspecialchars($phone ?? ''); ?>"><br>

                <label for="contact_time">Время связи:</label>
                <input type="datetime-local" name="contact_time" id="contact_time" value="<?= htmlspecialchars($contact_time ?? ''); ?>"><br>
            </div>

            <button type="submit">Отправить ответ</button>
        </form>

        <p><a href="index.php">На главную</a></p>
    </div>

    <script>
        function toggleFields(value) {
            const acceptFields = document.getElementById('accept_fields');
            if (value === 'accept') {
                acceptFields.style.display = 'block';
            } else {
                acceptFields.style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php
$request_stmt->close();
$conn->close();
?>
