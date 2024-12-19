<?php
require 'db.php';

$error = ""; // Переменная для хранения сообщений об ошибках
$roleId = 2; // Установите ID роли "продавец" (1) или "покупатель" (2). Измените на 1 для продавца.
$showExperience = false; // Добавляем переменную для управления выводом опыта

function isValidName1($name) {
    return preg_match('/^\p{Lu}\p{Ll}+$/u', $name);
}

function isValidName($name) {
    if (mb_strlen($name, 'UTF-8') > 1) {
        return true;
    }
    return false; // Имя должно содержать более одного символа
}

function isValidEmail($email) {
    return preg_match("/^[a-zA-Z][a-zA-Z0-9._%+-]*@[a-zA-Z][a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $email);
}

if ($roleId == 1) {
    $showExperience = true; // Устанавливаем переменную для показа опыта, если роль продавец
}

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $name = $_POST['name']; 
    $email = $_POST['email']; 
    $password = $_POST['password']; 
    $confirmPassword = $_POST['confirm_password']; 

    // Проверка имени 
    if (!isValidName($name)) { 
        $error = "Имя должно быть больше одного символа."; 
    } elseif (!isValidEmail($email)) { 
        $error = "Введите корректный адрес электронной почты, пример |artem.mekhanikov@mail.ru|."; 
    } elseif ($password !== $confirmPassword) { 
        $error = "Пароли не совпадают."; 
    } elseif(!isValidName1($name)){ 
        $error = "Имя должно начинаться с заглавной буквы и не может содержать цифр или специальных символов."; 
    } elseif(mb_strlen($password , 'UTF-8') > 50){
        $error = "Вы ввели пароль больше 50 символов!";
    } else { 
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?"); 
        $stmt->bind_param("s", $email); 
        $stmt->execute(); 
        $stmt->bind_result($count); 
        $stmt->fetch(); 
        if ($count > 0) { 
            $error = "Этот email уже зарегистрирован. Пожалуйста, используйте другой email."; 
        } else { 
            $stmt->close(); 
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT); 
            $experience = null;
            if ($showExperience) { // если продавец
                $experience = $_POST['experience'];
            }

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id, experience) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $email, $hashedPassword, $roleId, $experience);

            if ($stmt->execute()) { 
                header("Location: login.php"); // Перенаправление на страницу авторизации после успешной регистрации 
                exit(); 
            } else { 
                $error = "Ошибка: " . $stmt->error; 
            } 
            $stmt->close(); 
        } 
    } 
} 

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" type="text/css" href="style.css"> <!-- Подключение CSS -->
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
        select {
        width: 100%; /* Полная ширина */
        padding: 10px; /* Внутренние отступы */
        margin-bottom: 15px; /* Отступы снизу */
        border: 1px solid #ccc; /* Рамка */
        border-radius: 4px; /* Закругленные углы */
        box-sizing: border-box; /* Учет отступов в ширину */
        font-size: 16px; /* Размер шрифта */
    }
    select:focus {
        border-color: #66afe9; /* Цвет рамки при фокусе */
        outline: none; /* Убираем стандартный контур */
    }
    </style>
</head>
<body>
    <h2>Регистрация</h2>
    <?php if ($error): ?>
        <div class="error-message" id="error-message">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <form method="post" action=""> 
    <label>Имя:</label><br> 
    <input type="text" name="name" required><br> 
    <label>Email:</label><br> 
    <input type="email" name="email" required><br> 
    <label>Пароль:</label><br> 
    <input type="password" name="password" required><br> 
    <label>Подтверждение пароля:</label><br> 
    <input type="password" name="confirm_password" required><br><br> 

    <?php if ($showExperience): ?> 
        <label>Опыт работы:</label><br> 
        <select name="experience" required>
            <option value="">Выберите опыт</option>
            <option value="1">1 год</option>
            <option value="2">2 года</option>
            <option value="3">3 года</option>
            <option value="4">4 года</option>
            <option value="5">Более 5 лет</option>
        </select><br>
    <?php endif; ?>

    <button type="submit" class="login-button">Зарегистрироваться</button> 
    <button type="button" onclick="window.location.href='index.php';">На начальную страницу</button> 
</form> 
    
    <script>
        // Если есть сообщение об ошибке, показываем его
        <?php if ($error): ?>
            document.getElementById('error-message').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>