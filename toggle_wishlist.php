<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status'   => 'error', 
        'message'  => '로그인이 필요합니다.', 
        'redirect' => 'login.php'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$car_id = $data['car_id'] ?? null;

if (!$car_id) {
    echo json_encode([
        'status'  => 'error', 
        'message' => '차량 ID가 없습니다.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM wishlist WHERE user_id = :user_id AND car_id = :car_id");
    $stmt->execute([
        'user_id' => $user_id, 
        'car_id'  => $car_id
    ]);
    $exists = $stmt->fetch();

    if ($exists) {
        $delStmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = :user_id AND car_id = :car_id");
        $delStmt->execute([
            'user_id' => $user_id, 
            'car_id'  => $car_id
        ]);
        echo json_encode([
            'status' => 'success', 
            'action' => 'removed'
        ]);
    } else {
        $insStmt = $pdo->prepare("INSERT INTO wishlist (user_id, car_id) VALUES (:user_id, :car_id)");
        $insStmt->execute([
            'user_id' => $user_id, 
            'car_id'  => $car_id
        ]);
        echo json_encode([
            'status' => 'success', 
            'action' => 'added'
        ]);
    }
} catch (\PDOException $e) {
    echo json_encode([
        'status'  => 'error', 
        'message' => $e->getMessage()
    ]);
}
?>