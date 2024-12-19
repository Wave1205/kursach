<?php
require 'db.php';
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Инициализация переменной для поиска
$search_query = '';

// Проверка, был ли отправлен запрос на поиск
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search_query = trim($_POST['search']);
}

// Запрос для получения всех деталей с учетом поиска и сортировки по опыту продавца
$query = "SELECT AutoParts.part_id, AutoParts.part_name, AutoParts.part_number, 
                 AutoParts.created_at, users.name AS seller_name, users.experience,
                 COALESCE(SUM(UserInteractions.interaction_count), 0) AS interaction_count
          FROM AutoParts 
          JOIN users ON AutoParts.seller_id = users.id
          LEFT JOIN UserInteractions ON users.id = UserInteractions.seller_id AND UserInteractions.user_id = ?
          GROUP BY AutoParts.part_id, users.id";

if ($search_query) {
    // Изменяем запрос на основе поискового запроса
    $query .= " WHERE AutoParts.part_name LIKE ?";
}

$query .= " ORDER BY users.experience DESC"; // Сортировка по опыту продавца в порядке убывания

// Подготовка и выполнение запроса
$stmt = $conn->prepare($query);
if ($search_query) {
    $search_param = '%' . $search_query . '%'; // Для реализации поиска по частичному совпадению
    $stmt->bind_param('si', $search_param, $user_id);
} else {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

// Обработка добавления товаров в корзину
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_parts'])) {
    $selected_parts = $_POST['selected_parts'];
    
    // Массив для хранения идентификаторов продавцов
    $seller_ids = [];
    
    foreach ($selected_parts as $part_id) {
        // Получаем детали о товаре
        $stmt = $conn->prepare("SELECT seller_id FROM AutoParts WHERE part_id = ?");
        $stmt->bind_param("i", $part_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $seller_id = $row['seller_id'];
            $seller_ids[] = $seller_id; // Сохраняем идентификатор продавца

            // Проверяем, есть ли уже данный товар в корзине
            $stmt_check = $conn->prepare("SELECT quantity FROM Cart WHERE user_id = ? AND part_id = ?");
            $stmt_check->bind_param("ii", $user_id, $part_id);
            $stmt_check->execute();
            $check_result = $stmt_check->get_result();

            if ($check_result->num_rows > 0) {
                // Товар уже существует в корзине, обновляем количество
                $existing_row = $check_result->fetch_assoc();
                $new_quantity = $existing_row['quantity'] + 1;

                // Обновляем количество в корзине
                $stmt_update = $conn->prepare("UPDATE Cart SET quantity = ? WHERE user_id = ? AND part_id = ?");
                $stmt_update->bind_param("iii", $new_quantity, $user_id, $part_id);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // Добавляем запись в таблицу Cart
                $quantity = 1;

                $stmt_insert = $conn->prepare("INSERT INTO Cart (user_id, part_id, seller_id, quantity) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("iiii", $user_id, $part_id, $seller_id, $quantity);
                $stmt_insert->execute();
                $stmt_insert->close();
            }

            // Увеличиваем счетчик взаимодействий с текущим продавцом
            $stmt_interaction = $conn->prepare("INSERT INTO UserInteractions (user_id, seller_id, interaction_count) VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE interaction_count = interaction_count + 1");
            $stmt_interaction->bind_param("ii", $user_id, $seller_id);
            $stmt_interaction->execute();
            $stmt_interaction->close();
        }
        $stmt->close();
    }

    // Перенаправляем на страницу корзины
    header("Location: you_cart.php");
    exit;
}


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


$query = "
    SELECT AutoParts.part_id, AutoParts.part_number, AutoParts.part_name, 
           users.name AS seller_name, users.experience, 
           COALESCE(SUM(UserInteractions.interaction_count), 0) AS interaction_count, 
           AutoParts.created_at,
           CASE WHEN COALESCE(SUM(UserInteractions.interaction_count), 0) = ? THEN 1 ELSE 0 END AS is_highlighted
    FROM AutoParts
    LEFT JOIN users ON AutoParts.seller_id = users.id
    LEFT JOIN UserInteractions ON users.id = UserInteractions.seller_id
    GROUP BY users.id, AutoParts.part_id
    ORDER BY users.experience DESC, is_highlighted DESC"; 

$seller_stmt = $conn->prepare($query);
$seller_stmt->bind_param("i", $max_interaction);
$seller_stmt->execute();
$result = $seller_stmt->get_result();

if ($result === false) {
    die('Ошибка выполнения запроса: ' . $seller_stmt->error);
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Все товары</title>
    <link rel="stylesheet" type="text/css" href="style.css">
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

        p {
            text-align: center;
            color: #555; /* Цвет текста */
        }

        form {
            max-width: 80%; /* изменено для лучшего восприятия */
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%; /* Ширина таблицы */
            border-collapse: collapse;
            margin: 20px 0; /* Отступ сверху и снизу */
            background: white; /* Белый фон для таблицы */
            border-radius: 8px; /* Скругленные углы */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Тень для таблицы */
        }

        th, td {
            padding: 10px;
            text-align: center;
            white-space: nowrap; /* Предотвращает перенос строки */
            overflow: hidden; /* Прячет переполнение */
            text-overflow: ellipsis; /* Добавляет многоточие при переполнении */
            border-bottom: 1px solid #ddd; /* Легкая разделительная линия между строками */
        }

        th {
            background-color: #0d47a1; /* Фон заголовков таблицы */
            color: white; /* Цвет текста заголовков таблицы */
        }

        td:first-child {
            width: 50px; /* Устанавливаем фиксированную ширину для чекбокса */
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #0d47a1; /* Цвет кнопки */
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px; /* Отступ сверху от кнопки */
        }

        button:hover {
            background-color: #0b3c85; /* Цвет кнопки при наведении */
        }

        a {
            color: #0d47a1; /* Цвет ссылок */
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline; /* Подчеркивание при наведении */
        }

        .highlight {
            background-color: #ffeb3b; /* Подсветка для продавцов, к которым добавлено более 3 товаров */
        }
    </style>
</head>
<body>
    <h2>Все доступные товары</h2>

    <!-- Форма для поиска товаров -->
    <form action="" method="POST">
        <input type="text" name="search" placeholder="Введите название товара" 
               value="<?= htmlspecialchars($search_query); ?>">
        <button type="submit">Поиск</button>
    </form>

    <form action="" method="POST">
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Выбрать</th>
                <th>Уникальный номер</th>
                <th>Имя товара</th>
                <th>Имя продавца</th>
                <th>Опыт продавца (годы)</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <input type="checkbox" name="selected_parts[]" value="<?= htmlspecialchars($row['part_id']); ?>">
                    </td>
                    <td><?= htmlspecialchars($row['part_number']); ?></td>
                    <td><?= htmlspecialchars($row['part_name']); ?></td>
                    <td class="<?= ($row['interaction_count'] == $max_interaction) ? 'highlight' : ''; ?>">
                        <?= htmlspecialchars($row['seller_name']); ?>
                    </td>
                    <td><?= htmlspecialchars($row['experience']); ?></td>
                    <td><?= htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <a href="view_part.php?part_number=<?= urlencode($row['part_number']); ?>">Просмотреть подробнее</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <button type="submit">Добавить в корзину</button>
    <?php else: ?>
        <p>Нет доступных товаров для отображения.</p>
    <?php endif; ?>
    </form>

    <p><a href="index.php">На главную</a></p>
</body>
</html>

<?php
$conn->close();
