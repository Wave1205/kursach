<?php
require 'db.php';

session_start();
$error_message = ""; // Создаем переменную для сообщения об ошибке

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id,password,role_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id,$hashed_password,$role_id);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['email'] = $email;
            $_SESSION['role_id'] = $role_id; // Сохраняем роль в сессии
            $_SESSION['user_id'] = $user_id; // Сохраняем id пользователя в сессии
            echo "<script>alert('Добро пожаловать, " . htmlspecialchars($email) . "!'); window.location.href = 'index.php';</script>";
        } else {
            $error_message = "Неверный пароль.";
        }
    } else {
        $error_message = "Пользователь не найден.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Авторизация</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
    .error-message {
        display: none;
        background-color: #f44336; /* Красный цвет */
        color: white;
        padding: 15px;
        margin: 20px auto; /* Центрирование по горизонтали */
        border-radius: 5px;
        width: 300px; /* Фиксированная ширина */
        text-align: center; /* Центрирование текста */
    }
</style>
</head>
<body>
    <h2>Авторизация</h2>

    <?php if (!empty($error_message)): ?>
        <div class="error-message" id="error-message">
            <?= htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <label>Email:</label><br>
        <input type="email" name="email" required><br>
        <label>Пароль:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit" class="login-button">Войти</button>
        <button type="button" onclick="window.location.href='index.php';">На начальную страницу</button>
    </form>
    
    <script>
        // Если есть сообщение об ошибке, показываем его
        <?php if (!empty($error_message)): ?>
            document.getElementById('error-message').style.display = 'block';
        <?php endif; ?>
    </script>

</body>
</html>