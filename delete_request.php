<?php
session_start();
require 'db.php';

if (!isset($_SESSION['email']) || $_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    // Обновление статуса заявки на удаленный
    $delete_query = "UPDATE Requests SET status = 'deleted', is_deleted = 1 WHERE request_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);
    
    // Получаем ID покупателя
    $buyer_id = $_SESSION['user_id'];
    
    $stmt->bind_param("ii", $request_id, $buyer_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "Заявка успешно удалена.";
        } else {
            echo "Ошибка: Заявка не найдена или уже удалена.";
        }
    } else {
        echo "Ошибка выполнения запроса.";
    }

    $stmt->close();
}
$conn->close();

// Перенаправление обратно на страницу заявок
header("Location: view_my_requests.php");
exit;
?>