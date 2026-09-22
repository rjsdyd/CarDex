<?php
require_once 'db.php';

$searchTerm = $_GET['q'] ?? '';
$cars = [];
$totalRowsFormatted = '0';

try {
    // 1. Local DB Cache 전체 데이터 개수 구하기 (대시보드 우측 상단 카드용)
    $countStmt = $pdo->query("SELECT COUNT(*) FROM cars");
    $totalRows = $countStmt->fetchColumn();
    $totalRowsFormatted = number_format($totalRows);

    // 2. 검색 로직 & 캐싱 (어제 완성한 마법의 로직 그대로 유지!)
    if ($searchTerm) {
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE make LIKE :term1 OR model LIKE :term2 ORDER BY created_at DESC");
        $stmt->execute(['term1' => '%' . $searchTerm . '%', 'term2' => '%' . $searchTerm . '%']);
        $cars = $stmt->fetchAll();

        if (empty($cars)) {
            $apiKey = $_ENV['API_NINJAS_KEY'] ?? '';
            $url = 'https://api.api-ninjas.com/v1/cars?model=' . urlencode($searchTerm);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
            $response = curl_exec($ch);
            
            if ($response === false) { die("🚨 서버 통신 에러: " . curl_error($ch)); }
            curl_close($ch);

            $apiCars = json_decode($response, true);
            if (isset($apiCars['error']) || isset($apiCars['message'])) {
                die("<div style='background:#111; color:#fff; padding:30px; text-align:center;'><h2>🚨 API 에러</h2><code>" . htmlspecialchars($response) . "</code></div>");
            }

            if (!empty($apiCars) && is_array($apiCars)) {
                // 👇 누락되었던 transmission, cylinders, displacement, combination_mpg 전부 추가!
                $insertStmt = $pdo->prepare("INSERT INTO cars (make, model, year, vehicle_class, drive, fuel_type, transmission, cylinders, displacement, city_mpg, highway_mpg) VALUES (:make, :model, :year, :vehicle_class, :drive, :fuel_type, :transmission, :cylinders, :displacement, :city_mpg, :highway_mpg)");
                
                foreach ($apiCars as $apiCar) {
                    if (is_array($apiCar) && isset($apiCar['make'])) { 
                        $insertStmt->execute([
                            'make' => $apiCar['make'], 
                            'model' => $apiCar['model'], 
                            'year' => $apiCar['year'],
                            'vehicle_class' => $apiCar['class'] ?? '', 
                            'drive' => $apiCar['drive'] ?? '',
                            'fuel_type' => $apiCar['fuel_type'] ?? '', 
                            'transmission' => $apiCar['transmission'] ?? '', // 변속기 추가
                            'cylinders' => $apiCar['cylinders'] ?? 0,        // 기통 수 추가
                            'displacement' => $apiCar['displacement'] ?? 0,  // 배기량 추가
                            'city_mpg' => $apiCar['city_mpg'] ?? 0, 
                            'highway_mpg' => $apiCar['highway_mpg'] ?? 0
                        ]);
                    }
                }
                $stmt->execute(['term1' => '%' . $searchTerm . '%', 'term2' => '%' . $searchTerm . '%']);
                $cars = $stmt->fetchAll();
            }
        }
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
    <title>CarDex - 글로벌 자동차 데이터 플랫폼</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <!-- 👇 아까 <style> 태그가 있던 자리에 이 코드를 딱 한 줄 넣어줘! 👇 -->
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>

    <!-- 검색 로딩 화면 -->
    <div class="loader-overlay" id="loader">
        <div class="spinner"></div>
        <div class="loader-text">API 데이터 탐색 중...</div>
    </div>

    <!-- 네비게이션 바 -->
    <nav class="navbar">
        <a href="index.php" class="logo" style="text-decoration: none; color: inherit;">
            <span class="logo-icon">🚘</span> CarDex
        </a>
        <div class="nav-links">
            <a href="index.php" class="active">검색 (Search)</a>
            <a href="#">비교함 (Compare)</a>
            <a href="#">마이페이지 (My Page)</a>
        </div>
        <div class="nav-icons">
            <span style="cursor:pointer;">🔔</span>
            <div class="user-avatar">U</div>
        </div>
    </nav>

    <!-- 대시보드 (벤토 박스 레이아웃) -->
    <div class="dashboard-container">
        
        <!-- 1. 히어로 검색 영역 -->
        <div class="card card-search">
            <div class="badge-api">API Ninjas 연동 최적화</div>
            <h1>글로벌 자동차 데이터를<br>하나의 플랫폼에서.</h1>
            <p>전 세계 400여 개 제조사의 실시간 제원을 검색하고 비교하세요.</p>
            
            <form class="search-box" method="GET" action="index.php" onsubmit="document.getElementById('loader').style.display='flex'">
                <input type="text" name="make" class="search-input input-make" placeholder="Genesis" autocomplete="off">
                <input type="text" name="q" class="search-input input-model" placeholder="모델명 입력 (예: elantra)" value="<?= htmlspecialchars($searchTerm) ?>" required autocomplete="off">
                <button type="submit" class="btn-search">🔍 검색</button>
            </form>
        </div>

        <!-- 2. 인기 차종 카드 -->
        <div class="card card-popular">
            <div class="heart-icon" onclick="this.style.color='#ff4b4b'">🤍</div>
            <h3>인기 검색 차량</h3>
            <h2>Model 3</h2>
            <div class="brand">Tesla</div>
            
            <!-- 배경 자동차 실루엣 느낌을 위한 데코 -->
            <div style="position: absolute; right: -20px; bottom: 80px; font-size: 8rem; opacity: 0.05; transform: rotate(-15deg);">🚗</div>

            <div class="popular-specs">
                <div class="spec-box">
                    FUEL TYPE
                    <strong>Electricity</strong>
                </div>
                <div class="spec-box">
                    DRIVE
                    <strong>AWD</strong>
                </div>
            </div>
        </div>

        <!-- 3. 로컬 DB 상태 카드 -->
        <div class="card card-db">
            <div class="db-title">
                <div class="status-dot"></div> Local DB Cache
            </div>
            <!-- PHP로 연동한 실제 데이터베이스 Row 수 출력 -->
            <div class="db-row-count"><?= $totalRowsFormatted ?> <span>Row</span></div>
            <div class="db-desc">API 호출 최소화 및 로딩 최적화</div>
        </div>

        <!-- 4. 제원 비교하기 버튼 카드 -->
        <div class="card card-compare">
            <div class="compare-icon">🔄</div>
            <h3>제원 비교하기</h3>
            <p>선택한 차량의 스펙을<br>나란히 비교하세요</p>
        </div>

        <!-- 5. 자주 검색하는 브랜드 -->
        <div class="card card-brands">
            <div class="brands-header">
                <h3>자주 검색하는 브랜드</h3>
                <a href="#">더보기 →</a>
            </div>
            <div class="brand-list">
                <div class="brand-item">
                    <div class="brand-circle">H</div>
                    <span>현대</span>
                </div>
                <div class="brand-item">
                    <div class="brand-circle">K</div>
                    <span>기아</span>
                </div>
                <div class="brand-item">
                    <div class="brand-circle">⚡</div>
                    <span>테슬라</span>
                </div>
                <div class="brand-item">
                    <div class="brand-circle">B</div>
                    <span>BMW</span>
                </div>
                <div class="brand-item">
                    <div class="brand-circle" style="background:#333;">🚗</div>
                    <span>전체보기</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 검색 결과가 있을 때만 아래에 그리드로 보여주기 -->
    <?php if ($searchTerm && !empty($cars)): ?>
    <div class="search-results-section">
        <h2 class="search-results-title">결과: '<?= htmlspecialchars($searchTerm) ?>' (<?= count($cars) ?>건)</h2>
        <div class="car-grid">
            <?php foreach ($cars as $car): ?>
                <!-- 👇 기존의 ?id= 부분 대신 make, model, year를 들고 넘어가도록 수정! 👇 -->
                <a href="detail.php?make=<?= urlencode($car['make']) ?>&model=<?= urlencode($car['model']) ?>&year=<?= urlencode($car['year']) ?>" style="text-decoration: none; color: inherit;">
                    <div class="result-card">
                        <div class="result-make"><?= htmlspecialchars($car['make']) ?></div>
                        <div class="result-model"><?= htmlspecialchars($car['model']) ?> (<?= htmlspecialchars($car['year']) ?>)</div>
                        <div class="badge-group">
                            <span class="res-badge"><?= htmlspecialchars(strtoupper($car['drive'])) ?></span>
                            <span class="res-badge"><?= htmlspecialchars(ucfirst($car['fuel_type'])) ?></span>
                        </div>
                        <div style="font-size:0.9rem; color:#a0a0a0;">도심: <?= htmlspecialchars($car['city_mpg']) ?> mpg | 고속: <?= htmlspecialchars($car['highway_mpg']) ?> mpg</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($searchTerm && empty($cars)): ?>
        <!-- 결과 없을 때 방어 코드 -->
    <?php endif; ?>

</body>
</html>