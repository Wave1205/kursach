<!-- error.php -->
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ошибка подключения</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h2>Ошибка подключения к базе данных</h2>
    <p>Невозможно подключиться к базе данных. Пожалуйста, проверьте, чем это может быть вызвано:</p>
    <ul>
        <li>Запущен ли сервер базы данных?</li>
        <li>Правильные ли учетные данные (пользователь, пароль)?</li>
        <li>Правильный ли хост (localhost или другой)?</li>
    </ul>
    <form method="post" action="logout.php">
        <button type="submit">Вернуться назад</button>
    </form>
</body>
</html>