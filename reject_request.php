<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $request_id = $_POST['request_id'];

    $update_query = "UPDATE Requests SET status = 'dropped' WHERE request_id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    
 
    $buyer_id = $_SESSION['user_id'];
    $stmt->bind_param("ii", $request_id, $buyer_id);
    
    if ($stmt->execute()) {
        header("Location: view_my_requests.php"); 
        exit;
    } else {

        echo "Ошибка: " . $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();
?>