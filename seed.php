<?php
// 1. 방금 성공한 DB 연결 파일 불러오기 (다리 연결)
require_once 'db.php';

// 2. 수집할 자동차 목록 (원하는 차종을 자유롭게 추가 가능!)
$carList = [
    ['make' => 'porsche', 'model' => '911'],
    ['make' => 'mercedes-benz', 'model' => 'g-class'],
    ['make' => 'mercedes-benz', 'model' => 's-class'],
    ['make' => 'audi', 'model' => 'r8'],
    ['make' => 'bmw', 'model' => 'i8'],
    ['make' => 'lamborghini', 'model' => 'huracan'],
    ['make' => 'lamborghini', 'model' => 'aventador'],
    ['make' => 'toyota', 'model' => 'supra'],
    ['make' => 'genesis', 'model' => 'g90'],
    ['make' => 'genesis', 'model' => 'gv80']
];

// 3. API 키 설정
$apiKey = $_ENV['API_NINJAS_KEY'];

echo "데이터 수집 로봇 작동 시작...\n\n";

// 배열에 등록된 차종만큼 반복해서 API를 호출
foreach ($carList as $target) {
    $make = urlencode($target['make']);
    $model = urlencode($target['model']);
    
    $url = "https://api.api-ninjas.com/v1/cars?make={$make}&model={$model}";
    
    // cURL 통신 셋업
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    // JSON 응답을 PHP 배열로 변환
    $carsData = json_decode($response, true);

    // 데이터가 없으면 건너뛰기
    if (!is_array($carsData) || empty($carsData)) {
        echo "[실패] {$target['make']} {$target['model']} 데이터가 없습니다.\n";
        continue;
    }

    $insertedCount = 0; // DB에 저장된 개수 카운트

    // 한 모델(예: 아반떼)에도 여러 연식의 데이터가 딸려오므로 각각 DB에 저장
    foreach ($carsData as $car) {
        // ★ 핵심: 무료 계정이라 연비 데이터에 문자열 에러가 오면 숫자로 에러 나지 않게 0으로 처리
        $cityMpg = is_numeric($car['city_mpg']) ? $car['city_mpg'] : 0;
        $hwyMpg = is_numeric($car['highway_mpg']) ? $car['highway_mpg'] : 0;
        $combMpg = is_numeric($car['combination_mpg']) ? $car['combination_mpg'] : 0;

        // DB에 삽입하는 SQL 쿼리 (INSERT IGNORE를 써서 이미 있는 데이터면 에러 없이 무시함)
        $sql = "INSERT IGNORE INTO cars 
                (make, model, year, vehicle_class, drive, transmission, fuel_type, cylinders, displacement, city_mpg, highway_mpg, combination_mpg) 
                VALUES 
                (:make, :model, :year, :vehicle_class, :drive, :transmission, :fuel_type, :cylinders, :displacement, :city_mpg, :highway_mpg, :combination_mpg)";
        
        $stmt = $pdo->prepare($sql);
        
        // 데이터 바인딩 (안전하게 값을 매칭)
        $stmt->execute([
            ':make' => $car['make'],
            ':model' => $car['model'],
            ':year' => $car['year'],
            ':vehicle_class' => $car['class'], // API는 class, 우리 DB는 vehicle_class로 이름 맞춤
            ':drive' => $car['drive'],
            ':transmission' => $car['transmission'],
            ':fuel_type' => $car['fuel_type'],
            ':cylinders' => $car['cylinders'] ?? 0, // 전기차(테슬라)는 기통수가 없으므로 0
            ':displacement' => $car['displacement'] ?? 0.0,
            ':city_mpg' => $cityMpg,
            ':highway_mpg' => $hwyMpg,
            ':combination_mpg' => $combMpg
        ]);
        
        // 방금 실행한 쿼리로 실제로 행(row)이 추가되었다면 카운트 증가
        if ($stmt->rowCount() > 0) {
            $insertedCount++;
        }
    }
    
    echo "✅ [{$target['make']} {$target['model']}] 총 {$insertedCount}대 DB 저장 완료!\n";
    
    // API 서버가 공격으로 오해하지 않게 1초 쉬어줌 (매너콜)
    sleep(1);
}

echo "\n모든 데이터 수집이 성공적으로 완료되었습니다! 🚗\n";
?>