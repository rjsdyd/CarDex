<?php
// PHP 스크립트가 중간에 타임아웃으로 꺼지는 것을 방지 (무제한 대기)
set_time_limit(0);
require_once 'db.php';

$carList = [
    // 한국 (KR)
    ['make' => 'hyundai', 'model' => 'sonata'], ['make' => 'hyundai', 'model' => 'elantra'], 
    ['make' => 'hyundai', 'model' => 'tucson'], ['make' => 'hyundai', 'model' => 'santa fe'], 
    ['make' => 'hyundai', 'model' => 'palisade'], ['make' => 'hyundai', 'model' => 'kona'], 
    ['make' => 'hyundai', 'model' => 'ioniq'], ['make' => 'hyundai', 'model' => 'veloster'],
    ['make' => 'kia', 'model' => 'k5'], ['make' => 'kia', 'model' => 'optima'], 
    ['make' => 'kia', 'model' => 'sorento'], ['make' => 'kia', 'model' => 'sportage'], 
    ['make' => 'kia', 'model' => 'telluride'], ['make' => 'kia', 'model' => 'stinger'], 
    ['make' => 'kia', 'model' => 'soul'], ['make' => 'kia', 'model' => 'carnival'],
    ['make' => 'genesis', 'model' => 'g70'], ['make' => 'genesis', 'model' => 'g80'], 
    ['make' => 'genesis', 'model' => 'g90'], ['make' => 'genesis', 'model' => 'gv70'], 
    ['make' => 'genesis', 'model' => 'gv80'],
    
    // 미국 (US)
    ['make' => 'tesla', 'model' => 'model 3'], ['make' => 'tesla', 'model' => 'model y'], 
    ['make' => 'tesla', 'model' => 'model s'], ['make' => 'tesla', 'model' => 'model x'],
    ['make' => 'ford', 'model' => 'mustang'], ['make' => 'ford', 'model' => 'explorer'], 
    ['make' => 'ford', 'model' => 'f-150'], ['make' => 'ford', 'model' => 'escape'], 
    ['make' => 'ford', 'model' => 'bronco'],
    ['make' => 'chevrolet', 'model' => 'malibu'], ['make' => 'chevrolet', 'model' => 'camaro'], 
    ['make' => 'chevrolet', 'model' => 'silverado'], ['make' => 'chevrolet', 'model' => 'equinox'], 
    ['make' => 'chevrolet', 'model' => 'tahoe'], ['make' => 'chevrolet', 'model' => 'corvette'],
    
    // 독일 (DE)
    ['make' => 'bmw', 'model' => '3 series'], ['make' => 'bmw', 'model' => '5 series'], 
    ['make' => 'bmw', 'model' => '7 series'], ['make' => 'bmw', 'model' => 'x3'], 
    ['make' => 'bmw', 'model' => 'x5'], ['make' => 'bmw', 'model' => 'm3'], 
    ['make' => 'bmw', 'model' => 'i8'],
    ['make' => 'mercedes-benz', 'model' => 'c-class'], ['make' => 'mercedes-benz', 'model' => 'e-class'], 
    ['make' => 'mercedes-benz', 'model' => 's-class'], ['make' => 'mercedes-benz', 'model' => 'glc'], 
    ['make' => 'mercedes-benz', 'model' => 'gle'], ['make' => 'mercedes-benz', 'model' => 'g-class'],
    ['make' => 'audi', 'model' => 'a4'], ['make' => 'audi', 'model' => 'a6'], 
    ['make' => 'audi', 'model' => 'q5'], ['make' => 'audi', 'model' => 'q7'], 
    ['make' => 'audi', 'model' => 'r8'],
    ['make' => 'porsche', 'model' => '911'], ['make' => 'porsche', 'model' => 'cayenne'], 
    ['make' => 'porsche', 'model' => 'macan'], ['make' => 'porsche', 'model' => 'panamera'], 
    ['make' => 'porsche', 'model' => 'taycan'],
    
    // 일본 (JP)
    ['make' => 'toyota', 'model' => 'camry'], ['make' => 'toyota', 'model' => 'corolla'], 
    ['make' => 'toyota', 'model' => 'rav4'], ['make' => 'toyota', 'model' => 'highlander'], 
    ['make' => 'toyota', 'model' => 'prius'], ['make' => 'toyota', 'model' => 'supra'],
    ['make' => 'honda', 'model' => 'civic'], ['make' => 'honda', 'model' => 'accord'], 
    ['make' => 'honda', 'model' => 'cr-v'], ['make' => 'honda', 'model' => 'pilot'], 
    ['make' => 'nissan', 'model' => 'altima'], ['make' => 'nissan', 'model' => 'rogue'], 
    ['make' => 'nissan', 'model' => 'gt-r']
];

// 👇 꼼수 적용: 각 모델별로 2019, 2022, 2024년식을 따로따로 3번씩 검색해서 5개 제한을 우회!
$targetYears = [2019, 2022, 2024]; 
$apiKey = $_ENV['API_NINJAS_KEY'];

echo "<div style='background:#121212; color:#fff; padding:40px; font-family:sans-serif; max-width:800px; margin:0 auto; border-radius:16px;'>";
echo "<h1 style='color:#00e5ff;'>🔥 CarDex 영혼까지 끌어모으는 수집 작동 중...</h1>";
echo "<p style='color:#a0a0a0;'>각 모델별로 연식을 쪼개서 한계치 이상을 수집합니다. <strong>약 3~4분 이상</strong> 소요되니 커피 한 잔 가져오세요!</p>";
echo "<pre style='background:#1e1e24; padding:20px; border-radius:12px; color:#ccc; font-size:1.05rem; line-height:1.8; border:1px solid #333; max-height: 500px; overflow-y: auto;'>";

$totalInserted = 0;

foreach ($carList as $target) {
    $make = urlencode($target['make']);
    $model = urlencode($target['model']);
    
    $modelTotalCount = 0;

    // 연식별로 3번 반복해서 찔러보기
    foreach ($targetYears as $year) {
        $url = "https://api.api-ninjas.com/v1/cars?make={$make}&model={$model}&year={$year}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $carsData = json_decode($response, true);

        if (isset($carsData['error']) || isset($carsData['message'])) {
            sleep(1);
            continue;
        }

        if (!is_array($carsData) || empty($carsData)) {
            sleep(1);
            continue;
        }

        foreach ($carsData as $car) {
            if (!is_array($car)) continue;

            $cityMpg = isset($car['city_mpg']) && is_numeric($car['city_mpg']) ? $car['city_mpg'] : 0;
            $hwyMpg = isset($car['highway_mpg']) && is_numeric($car['highway_mpg']) ? $car['highway_mpg'] : 0;
            $combMpg = isset($car['combination_mpg']) && is_numeric($car['combination_mpg']) ? $car['combination_mpg'] : 0;

            $sql = "INSERT IGNORE INTO cars 
                    (make, model, year, vehicle_class, drive, transmission, fuel_type, cylinders, displacement, city_mpg, highway_mpg, combination_mpg) 
                    VALUES 
                    (:make, :model, :year, :vehicle_class, :drive, :transmission, :fuel_type, :cylinders, :displacement, :city_mpg, :highway_mpg, :combination_mpg)";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':make' => $car['make'] ?? 'unknown',
                ':model' => $car['model'] ?? 'unknown',
                ':year' => $car['year'] ?? 0,
                ':vehicle_class' => $car['class'] ?? '', 
                ':drive' => $car['drive'] ?? '',
                ':transmission' => $car['transmission'] ?? '',
                ':fuel_type' => $car['fuel_type'] ?? '',
                ':cylinders' => $car['cylinders'] ?? 0, 
                ':displacement' => $car['displacement'] ?? 0.0,
                ':city_mpg' => $cityMpg,
                ':highway_mpg' => $hwyMpg,
                ':combination_mpg' => $combMpg
            ]);
            
            if ($stmt->rowCount() > 0) {
                $modelTotalCount++;
                $totalInserted++;
            }
        }
        
        // 연도별 검색 후 매너콜 1초 대기
        sleep(1);
    }
    
    if ($modelTotalCount > 0) {
        echo "<span style='color:#00ff88;'>✅ [{$target['make']} {$target['model']}]</span> {$modelTotalCount}대 신규 저장 (총 3개 연식 탐색)\n";
    } else {
        echo "<span style='color:#888;'>🔄 [{$target['make']} {$target['model']}]</span> 이미 최신화됨\n";
    }
    
    ob_flush(); 
    flush();
}

echo "</pre>";
echo "<h2 style='color:#00e5ff; margin-top:20px;'>🎉 대규모 수집 완료! (총 {$totalInserted}대 신규 적재)</h2>";
echo "<a href='index.php' style='display:inline-block; margin-top:10px; background:#00e5ff; color:#000; padding:15px 25px; text-decoration:none; font-weight:800; border-radius:8px;'>메인 화면으로 돌아가기</a>";
echo "</div>";
?>