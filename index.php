<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Начальная страница</title>
    <link rel="stylesheet" type="text/css" href="style.css"> <!-- Подключение CSS -->
</head>
<body>
    <h2>Добро пожаловать!</h2>
    <?php if (isset($_SESSION['email'])): ?>
        <p>Вы вошли как <?php echo htmlspecialchars($_SESSION['email']); ?>. Роль: <?php 
            // Выводим роль на основе role_id
            if ($_SESSION['role_id'] == 1) {
                echo "Продавец";
            } elseif ($_SESSION['role_id'] == 2) {
                echo "Покупатель";
            } else {
                echo "Неизвестная роль";
            }
        ?>.</p>
        <p><a href="logout.php">Выйти</a></p>

        <?php if ($_SESSION['role_id'] == 1): // Если роль продавца ?>
            <p><a href="view_own_parts.php">Просмотреть свои товары</a></p>
            <p><a href="add_part.php">Добавить деталь</a></p>
            <p><a href="edit_part.php">Редактировать деталь</a></p>
            <p><a href="delete_part.php">Удалить деталь</a></p>
            <p><a href="search_part.php">Поиск детали</a></p>
            <p><a href=" view_requests.php">Просмотреть заявки</a></p>
        <?php endif; ?>
        <?php if ($_SESSION['role_id'] == 2): // Если роль покупателя ?>
            <p><a href="view_all_parts.php">Просмотреть все товары</a></p>
            <p><a href = "you_cart.php">Посмотреть корзину</a></p>
            <p><a href="view_my_requests.php">Просмотреть свои заявки</a></p>
        <?php endif; ?>
    <?php else: ?>
        <p><a href="register.php">Регистрация</a> | <a href="login.php">Войти</a></p>
    <?php endif; ?>
</body>
</html>