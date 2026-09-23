<?php
require_once 'db.php';
header('Content-Type: application/json');

// 프론트엔드(JS)에서 보낸 데이터 받기
$data = json_decode(file_get_contents('php://input'), true);
$car_id = $data['car_id'] ?? null;

if (!$car_id) {
    echo json_encode(['status' => 'error', 'message' => '차량 ID가 없습니다.']);
    exit;
}

try {
    // 1. 이미 찜한 차인지 DB에서 확인
    $stmt = $pdo->prepare("SELECT * FROM wishlist WHERE car_id = :car_id");
    $stmt->execute(['car_id' => $car_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // 2. 이미 찜했다면 -> 차고에서 빼기 (Delete)
        $delStmt = $pdo->prepare("DELETE FROM wishlist WHERE car_id = :car_id");
        $delStmt->execute(['car_id' => $car_id]);
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        // 3. 아직 찜하지 않았다면 -> 차고에 넣기 (Insert)
        $insStmt = $pdo->prepare("INSERT INTO wishlist (car_id) VALUES (:car_id)");
        $insStmt->execute(['car_id' => $car_id]);
        echo json_encode(['status' => 'success', 'action' => 'added']);
    }
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>