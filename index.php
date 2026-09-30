<?php
session_start();
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
// 👇 메인 화면 인기 차량 1대 불러오기 (가장 최근에 추가된 차)
$popStmt = $pdo->query("SELECT * FROM cars ORDER BY created_at DESC LIMIT 1");
$popularCar = $popStmt->fetch();

// 내가 이 인기 차량을 찜했는지 확인
$isPopWished = false;
// ... (인기 차량 $popularCar 불러오는 코드 아래) ...
if ($popularCar && isset($_SESSION['user_id'])) {
    $checkWish = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND car_id = ?");
    $checkWish->execute([$_SESSION['user_id'], $popularCar['car_id']]);
    $isPopWished = (bool)$checkWish->fetch();
}

// 👇 메인 화면 자주 검색하는 브랜드 TOP 4 불러오기
$brandStmt = $pdo->query("SELECT make, COUNT(*) as cnt FROM cars GROUP BY make ORDER BY cnt DESC LIMIT 4");
$popularBrands = $brandStmt->fetchAll();
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
    <?php include 'header.php'; ?>

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

        <!-- 2. 인기 차종 카드 (동적 연동 완료) -->
        <div class="card card-popular">
            <?php if ($popularCar): ?>
                <!-- 찜하기 하트 버튼 -->
                <div class="heart-icon" 
                    id="main-heart"
                    style="color: <?= $isPopWished ? '#ff4b4b' : '#fff' ?>; cursor: pointer; position: absolute; right: 20px; top: 20px; font-size: 1.5rem; transition: 0.2s; z-index: 10;" 
                    onclick="toggleMainWish(<?= $popularCar['car_id'] ?>)">
                    <?= $isPopWished ? '❤️' : '🤍' ?>
                </div>
                
                <h3>최근 인기 검색 차량</h3>
                <h2><?= htmlspecialchars(ucwords($popularCar['model'])) ?></h2>
                <div class="brand" style="color: #00e5ff; font-weight: bold; margin-bottom: 20px;">
                    <?= htmlspecialchars(strtoupper($popularCar['make'])) ?>
                </div>
                
                <div style="position: absolute; right: -20px; bottom: 80px; font-size: 8rem; opacity: 0.05; transform: rotate(-15deg); pointer-events: none;">🚗</div>

                <div class="popular-specs" style="display: flex; gap: 10px; margin-top: auto;">
                    <div class="spec-box" style="background: #121212; padding: 10px 15px; border-radius: 8px; flex: 1;">
                        <span style="font-size: 0.75rem; color: #888; display: block;">FUEL TYPE</span>
                        <strong><?= htmlspecialchars(ucfirst($popularCar['fuel_type'] ?: 'Unknown')) ?></strong>
                    </div>
                    <div class="spec-box" style="background: #121212; padding: 10px 15px; border-radius: 8px; flex: 1;">
                        <span style="font-size: 0.75rem; color: #888; display: block;">DRIVE</span>
                        <strong><?= htmlspecialchars(strtoupper($popularCar['drive'] ?: 'Unknown')) ?></strong>
                    </div>
                </div>
            <?php else: ?>
                <h3>데이터 없음</h3>
                <p>검색을 시작해보세요!</p>
            <?php endif; ?>
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
        <a href="compare.php" class="card card-compare">
            <span class="compare-icon">🔄</span>
            <h3>제원 비교하기</h3>
            <p>선택한 차량의 스펙을<br>나란히 비교하세요</p>
        </a>

        <div class="card" style="background: #1e1e24; border-radius: 16px; padding: 25px 30px; height: auto !important; min-height: 190px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="color: #a0a0a0; font-size: 0.95rem; font-weight: 600; margin: 0;">자주 검색하는 브랜드</h3>
                <a href="javascript:void(0)" onclick="alert('전체 브랜드 목록 페이지는 업데이트 예정입니다! 🚗\n상단의 검색 기능을 이용해 주세요.')" style="color: #00e5ff; font-size: 0.85rem; font-weight: bold; text-decoration: none;">더보기 →</a>
            </div>
            
            <div style="display: flex; gap: 25px; align-items: center;">
                <?php 
                // 원본 디자인 감성을 살리기 위한 커스텀 아이콘 & 한글 이름 매핑
                $brandMap = [
                    'hyundai' => ['icon' => 'H', 'name' => '현대'],
                    'kia'     => ['icon' => 'K', 'name' => '기아'],
                    'tesla'   => ['icon' => '⚡', 'name' => '테슬라'],
                    'bmw'     => ['icon' => 'B', 'name' => 'BMW'],
                    'genesis' => ['icon' => 'G', 'name' => '제네시스'],
                    'toyota'  => ['icon' => 'T', 'name' => '토요타'],
                    'honda'   => ['icon' => 'H', 'name' => '혼다'] // 혼다 추가!
                ];

                if (!empty($popularBrands)): 
                    foreach ($popularBrands as $b): 
                        $rawMake = strtolower($b['make']);
                        // 매핑된 브랜드면 커스텀 디자인 적용, 아니면 이니셜 자동 추출
                        if (isset($brandMap[$rawMake])) {
                            $displayIcon = $brandMap[$rawMake]['icon'];
                            $displayName = $brandMap[$rawMake]['name'];
                        } else {
                            $displayIcon = strtoupper(substr($b['make'], 0, 1));
                            $displayName = htmlspecialchars(ucfirst($b['make']));
                        }
                ?>
                    <!-- 개별 브랜드 (클릭 시 비교함 이동) -->
                    <div onclick="location.href='compare.php?search1=<?= urlencode($b['make']) ?>'" 
                         style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 12px; transition: 0.2s;"
                         onmouseover="this.style.transform='translateY(-3px)'" 
                         onmouseout="this.style.transform='translateY(0)'">
                        <div style="width: 60px; height: 60px; background: #121212; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 1.3rem; font-weight: 800; color: #fff;">
                            <?= $displayIcon ?>
                        </div>
                        <span style="font-size: 0.85rem; color: #a0a0a0; font-weight: 500;"><?= $displayName ?></span>
                    </div>
                <?php 
                    endforeach; 
                endif; 
                ?>
                
                <!-- 변경된 전체보기 아이콘 -->
                <div onclick="alert('전체 브랜드 목록 페이지는 업데이트 예정입니다! 🚗\n상단의 검색 기능을 이용해 주세요.')" 
                    style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 12px; margin-left: 5px; transition: 0.2s;"
                    onmouseover="this.style.transform='translateY(-3px)'" 
                    onmouseout="this.style.transform='translateY(0)'">
                    <div style="width: 60px; height: 60px; background: #2a2a30; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 1.3rem;">
                        🚗
                    </div>
                    <span style="font-size: 0.85rem; color: #a0a0a0; font-weight: 500;">전체보기</span>
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
    <script>
        function toggleMainWish(carId) {
            fetch('toggle_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ car_id: carId })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'error' && data.redirect) {
                    alert(data.message);
                    window.location.href = data.redirect;
                    return;
                }
                if(data.status === 'success') {
                    const heart = document.getElementById('main-heart');
                    if(data.action === 'added') {
                        heart.innerHTML = '❤️';
                        heart.style.color = '#ff4b4b';
                    } else {
                        heart.innerHTML = '🤍';
                        heart.style.color = '#fff';
                    }
                }
            });
        }
    </script>
</body>
</html>