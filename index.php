<?php
require_once 'db.php';

$searchTerm = $_GET['q'] ?? '';
$cars = [];

try {
    if ($searchTerm) {
        // 1. 내 DB에서 먼저 찾아보기
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE make LIKE :term1 OR model LIKE :term2 ORDER BY created_at DESC");
        $stmt->execute([
            'term1' => '%' . $searchTerm . '%',
            'term2' => '%' . $searchTerm . '%'
        ]);
        $cars = $stmt->fetchAll();

        // 2. 마법 시작! DB에 없다면? API Ninjas에 물어보고 DB에 몰래 저장하기
        if (empty($cars)) {
            $apiKey = $_ENV['API_NINJAS_KEY'] ?? '';
            
            // API 호출
            // API 호출
            $url = 'https://api.api-ninjas.com/v1/cars?model=' . urlencode($searchTerm);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ★ XAMPP 환경 SSL 강제 차단 해제!
            
            $response = curl_exec($ch);
            
            // 만약 서버 통신 자체가 완전히 실패했다면 원인을 화면에 띄우기
            if ($response === false) {
                die("🚨 서버 통신 에러 발생: " . curl_error($ch));
            }
            
            curl_close($ch);

            $apiCars = json_decode($response, true);

            // ★ 추가된 안전장치: API가 자동차 데이터 대신 '에러'를 보냈는지 확인
            if (isset($apiCars['error']) || isset($apiCars['message'])) {
                die("<div style='background:#1e293b; color:#f8fafc; padding:30px; text-align:center; font-family:sans-serif;'>
                        <h2 style='color:#ef4444;'>🚨 API 통신 에러 발생!</h2>
                        <p>API 서버에서 데이터를 주지 않고 아래와 같은 메시지를 보냈습니다:</p>
                        <code style='color:#38bdf8; font-size:1.2rem;'>" . htmlspecialchars($response) . "</code>
                    </div>");
            }

            // API에서 결과가 잘 왔다면 내 DB에 저장 (캐싱)
            if (!empty($apiCars) && is_array($apiCars)) {
                $insertStmt = $pdo->prepare("INSERT INTO cars (make, model, year, vehicle_class, drive, fuel_type, city_mpg, highway_mpg) VALUES (:make, :model, :year, :vehicle_class, :drive, :fuel_type, :city_mpg, :highway_mpg)");
                
                foreach ($apiCars as $apiCar) {
                    // 한 번 더 확인: 값이 제대로 있는 경우에만 저장
                    if (is_array($apiCar) && isset($apiCar['make'])) { 
                        $insertStmt->execute([
                            'make' => $apiCar['make'],
                            'model' => $apiCar['model'],
                            'year' => $apiCar['year'],
                            'vehicle_class' => $apiCar['class'] ?? '',
                            'drive' => $apiCar['drive'] ?? '',
                            'fuel_type' => $apiCar['fuel_type'] ?? '',
                            'city_mpg' => $apiCar['city_mpg'] ?? 0,
                            'highway_mpg' => $apiCar['highway_mpg'] ?? 0
                        ]);
                    }
                }
                
                // 3. 내 DB에 저장이 끝났으니, 다시 검색해서 화면에 뿌려줄 준비 완료!
                $stmt->execute([
                    'term1' => '%' . $searchTerm . '%',
                    'term2' => '%' . $searchTerm . '%'
                ]);
                $cars = $stmt->fetchAll();
            }
        }
    } else {
        // 검색어가 없으면 전체 최신순 출력
        $stmt = $pdo->query("SELECT * FROM cars ORDER BY created_at DESC LIMIT 50");
        $cars = $stmt->fetchAll();
    }
} catch (\PDOException $e) {
    die("데이터베이스 에러: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarDex - 스마트 자동차 백과사전</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;700;900&display=swap" rel="stylesheet">
    <style>
        /* 모던한 CSS로 싹 갈아엎기! */
        body { font-family: 'Noto Sans KR', sans-serif; background-color: #0f172a; margin: 0; color: #f8fafc; }
        
        /* 상단 헤더 & 검색 영역 */
        .hero { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 60px 20px; text-align: center; border-bottom: 1px solid #334155; }
        .hero h1 { font-size: 3rem; color: #38bdf8; margin: 0 0 10px 0; font-weight: 900; letter-spacing: -1px; }
        .hero p { color: #94a3b8; font-size: 1.2rem; margin-bottom: 30px; }
        
        /* 검색창 디자인 */
        .search-form { max-width: 500px; margin: 0 auto; display: flex; gap: 10px; }
        .search-input { flex: 1; padding: 15px 20px; font-size: 1.1rem; border: 2px solid #334155; border-radius: 30px; background-color: #1e293b; color: white; outline: none; transition: border-color 0.3s; }
        .search-input:focus { border-color: #38bdf8; }
        .search-btn { padding: 0 25px; font-size: 1.1rem; font-weight: bold; background-color: #38bdf8; color: #0f172a; border: none; border-radius: 30px; cursor: pointer; transition: transform 0.2s, background-color 0.2s; }
        .search-btn:hover { background-color: #7dd3fc; transform: translateY(-2px); }

        /* 카드 그리드 영역 */
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        
        /* 카드 디자인 */
        .car-card { background: #1e293b; border-radius: 15px; padding: 25px; border: 1px solid #334155; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .car-card:hover { transform: translateY(-8px); border-color: #38bdf8; box-shadow: 0 12px 20px rgba(0,0,0,0.5); }
        .car-make { font-size: 0.85rem; color: #38bdf8; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; }
        .car-model { font-size: 1.6rem; color: #f8fafc; margin: 5px 0 20px 0; text-transform: capitalize; font-weight: 700; }
        
        /* 뱃지 및 디테일 */
        .info-group { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
        .badge { background: #334155; color: #cbd5e1; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .mpg-info { background: #0f172a; border-radius: 10px; padding: 15px; font-size: 0.9rem; color: #94a3b8; display: flex; justify-content: space-between; }
        .mpg-info strong { color: #f8fafc; font-size: 1.1rem; }
        
        /* 검색 결과 없음 */
        .no-data { grid-column: 1 / -1; text-align: center; padding: 50px; background: #1e293b; border-radius: 15px; color: #94a3b8; font-size: 1.2rem; border: 1px dashed #475569; }
        .no-data span { color: #38bdf8; font-weight: bold; }
    </style>
</head>
<body>

    <div class="hero">
        <h1>CarDex</h1>
        <p>검색 한 번으로 끝나는 자동차 스펙 조회</p>
        
        <!-- 검색 폼 추가! -->
        <form class="search-form" method="GET" action="index.php">
            <input type="text" name="q" class="search-input" placeholder="차량 모델명을 영어로 입력하세요 (예: 911, m3)" value="<?= htmlspecialchars($searchTerm) ?>" autocomplete="off">
            <button type="submit" class="search-btn">검색</button>
        </form>
    </div>

    <div class="container">
        <div class="car-grid">
            <?php if (!empty($cars)): ?>
                <?php foreach ($cars as $car): ?>
                    <div class="car-card">
                        <div class="car-make"><?= htmlspecialchars($car['make']) ?></div>
                        <h2 class="car-model"><?= htmlspecialchars($car['model']) ?> (<?= htmlspecialchars($car['year']) ?>)</h2>
                        
                        <div class="info-group">
                            <span class="badge"><?= htmlspecialchars($car['vehicle_class']) ?></span>
                            <span class="badge"><?= htmlspecialchars(strtoupper($car['drive'])) ?></span>
                            <span class="badge"><?= htmlspecialchars(ucfirst($car['fuel_type'])) ?></span>
                        </div>

                        <div class="mpg-info">
                            <div>도심: <strong><?= htmlspecialchars($car['city_mpg']) ?></strong> mpg</div>
                            <div>고속: <strong><?= htmlspecialchars($car['highway_mpg']) ?></strong> mpg</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>앗, 내 DB에 <span>'<?= htmlspecialchars($searchTerm) ?>'</span> 데이터가 없습니다!</p>
                    <p>조금만 기다려주세요. 곧 API가 자동으로 수집해 올 테니까요. 😎</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>