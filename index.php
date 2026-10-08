<?php
session_start();
require_once 'db.php';

$searchTerm = $_GET['q'] ?? '';
$cars = [];
$totalRowsFormatted = '0';

try {
    // 1. Local DB Cache 전체 데이터 개수 구하기
    $countStmt = $pdo->query("SELECT COUNT(*) FROM cars");
    $totalRows = $countStmt->fetchColumn();
    $totalRowsFormatted = number_format($totalRows);

    // 2. 검색 로직 & 캐싱
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
                            'transmission' => $apiCar['transmission'] ?? '', 
                            'cylinders' => $apiCar['cylinders'] ?? 0,        
                            'displacement' => $apiCar['displacement'] ?? 0,  
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
// 메인 화면 인기 차량 1대 불러오기
$popStmt = $pdo->query("SELECT * FROM cars ORDER BY created_at DESC LIMIT 1");
$popularCar = $popStmt->fetch();

$isPopWished = false;
if ($popularCar && isset($_SESSION['user_id'])) {
    $checkWish = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND car_id = ?");
    $checkWish->execute([$_SESSION['user_id'], $popularCar['car_id']]);
    $isPopWished = (bool)$checkWish->fetch();
}

// 메인 화면 자주 검색하는 브랜드 TOP 4 불러오기
$brandStmt = $pdo->query("SELECT make, COUNT(*) as cnt FROM cars GROUP BY make ORDER BY cnt DESC LIMIT 4");
$popularBrands = $brandStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CarDex - 글로벌 자동차 데이터 플랫폼</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    
    <!-- 💡 [추가됨] 메인 화면 모바일 반응형 강제 덮어쓰기 CSS -->
    <style>
        @media (max-width: 768px) {
            /* 1. 대시보드 벤토 그리드를 1열 세로 배열로 변경 */
            .dashboard-container {
                display: flex !important;
                flex-direction: column !important;
                gap: 20px !important;
                padding: 20px !important;
            }

            /* 2. 메인 검색 카드 타이틀 크기 조절 */
            .card-search h1 { font-size: 1.8rem !important; }
            .card-search p { font-size: 0.9rem !important; margin-bottom: 20px !important; }

            /* 3. 검색창 모바일 배열 (입력칸과 버튼을 세로로 쌓기) */
            .search-box {
                flex-direction: column !important;
                gap: 10px !important;
                background: transparent !important;
                padding: 0 !important;
            }
            .search-input {
                width: 100% !important;
                border-radius: 12px !important;
                background: rgba(255, 255, 255, 0.1) !important;
                padding: 15px !important;
                box-sizing: border-box !important;
            }
            .btn-search {
                width: 100% !important;
                border-radius: 12px !important;
                padding: 15px !important;
            }

            /* 4. 인기 차종 텍스트 오버플로우 방지 */
            .card-popular h2 { font-size: 2rem !important; word-break: keep-all; }

            /* 5. 브랜드 리스트 가로 스크롤 허용 또는 줄바꿈 */
            .brand-list-wrapper {
                flex-wrap: wrap !important;
                justify-content: flex-start !important;
                gap: 15px !important;
            }
            
            /* 검색 결과 영역 패딩 조절 */
            .search-results-section {
                padding: 20px 20px 60px 20px !important;
            }
            .search-results-section h2 { font-size: 1.2rem !important; }
        }
    </style>
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
                <input type="text" name="make" class="search-input input-make" placeholder="브랜드 (예: Genesis)" autocomplete="off">
                <input type="text" name="q" class="search-input input-model" placeholder="모델명 입력 (예: elantra)" value="<?= htmlspecialchars($searchTerm) ?>" required autocomplete="off">
                <button type="submit" class="btn-search">🔍 검색</button>
            </form>
        </div>

        <!-- 2. 인기 차종 카드 -->
        <div class="card card-popular">
            <?php if ($popularCar): ?>
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
            <div class="db-row-count"><?= $totalRowsFormatted ?> <span>Row</span></div>
            <div class="db-desc">API 호출 최소화 및 로딩 최적화</div>
        </div>
        
        <!-- 4. 제원 비교하기 버튼 카드 -->
        <a href="compare.php" class="card card-compare">
            <span class="compare-icon">🔄</span>
            <h3>제원 비교하기</h3>
            <p>선택한 차량의 스펙을<br>나란히 비교하세요</p>
        </a>

        <!-- 5. 자주 검색하는 브랜드 카드 -->
        <div class="card" style="background: #1e1e24; border-radius: 16px; padding: 25px 30px; height: auto !important; min-height: 190px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="color: #a0a0a0; font-size: 0.95rem; font-weight: 600; margin: 0;">자주 검색하는 브랜드</h3>
                <a href="brands.php" style="color: #00e5ff; font-size: 0.85rem; font-weight: bold; text-decoration: none;">더보기 →</a>
            </div>
            
            <!-- 💡 [추가됨] brand-list-wrapper 클래스를 추가하여 모바일 제어 -->
            <div class="brand-list-wrapper" style="display: flex; gap: 25px; align-items: center;">
                <?php 
                $brandMap = [
                    'hyundai' => ['icon' => 'H', 'name' => '현대'],
                    'kia'     => ['icon' => 'K', 'name' => '기아'],
                    'tesla'   => ['icon' => '⚡', 'name' => '테슬라'],
                    'bmw'     => ['icon' => 'B', 'name' => 'BMW'],
                    'genesis' => ['icon' => 'G', 'name' => '제네시스'],
                    'toyota'  => ['icon' => 'T', 'name' => '토요타'],
                    'honda'   => ['icon' => 'H', 'name' => '혼다'] 
                ];

                if (!empty($popularBrands)): 
                    foreach ($popularBrands as $b): 
                        $rawMake = strtolower($b['make']);
                        if (isset($brandMap[$rawMake])) {
                            $displayIcon = $brandMap[$rawMake]['icon'];
                            $displayName = $brandMap[$rawMake]['name'];
                        } else {
                            $displayIcon = strtoupper(substr($b['make'], 0, 1));
                            $displayName = htmlspecialchars(ucfirst($b['make']));
                        }
                ?>
                    <div onclick="location.href='index.php?q=<?= urlencode($b['make']) ?>'" 
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
                
                <div onclick="location.href='brands.php'" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 12px; margin-left: 5px; transition: 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="width: 60px; height: 60px; background: #2a2a30; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 1.3rem;">
                        🚗
                    </div>
                    <span style="font-size: 0.85rem; color: #a0a0a0; font-weight: 500;">전체보기</span>
                </div>
            </div>
        </div>

    </div>

    <!-- 검색 결과 영역 -->
    <?php if ($searchTerm): ?>
        <div class="search-results-section" style="max-width: 1200px; margin: 40px auto 0; padding: 40px 20px 60px; border-top: 1px solid #2a2a2f; width: 100%; box-sizing: border-box;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px;">
                <h2 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: #fff;">
                    🔍 <span style="color: #00e5ff;">'<?= htmlspecialchars($searchTerm) ?>'</span> 검색 결과
                </h2>
                <span style="background: #1e1e24; border: 1px solid #333; padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; color: #a0a0a0;">
                    총 <strong style="color: #fff;"><?= count($cars) ?></strong>건
                </span>
            </div>

            <?php if (!empty($cars)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding-bottom: 60px;">
                    <?php foreach ($cars as $car): ?>
                        <div style="background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 16px; padding: 25px; transition: 0.2s; display: flex; flex-direction: column;" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='#555'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#2a2a2f'; this.style.boxShadow='none';">
                            
                            <div style="font-size: 0.8rem; color: #00e5ff; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;"><?= htmlspecialchars($car['make']) ?></div>
                            <div style="font-size: 1.4rem; font-weight: 800; margin-bottom: 5px; text-transform: capitalize;"><?= htmlspecialchars($car['model']) ?></div>
                            <div style="font-size: 0.85rem; color: #888; margin-bottom: 20px;"><?= htmlspecialchars($car['year']) ?></div>
                            
                            <div style="display: flex; gap: 10px; margin-bottom: 20px; margin-top: auto;">
                                <div style="background: #121212; padding: 10px; border-radius: 8px; flex: 1; text-align: center; border: 1px solid #2a2a2f;">
                                    <span style="display: block; font-size: 0.7rem; color: #666; margin-bottom: 3px;">Fuel</span>
                                    <strong style="font-size: 0.9rem; text-transform: capitalize;"><?= htmlspecialchars($car['fuel_type'] ?: 'N/A') ?></strong>
                                </div>
                                <div style="background: #121212; padding: 10px; border-radius: 8px; flex: 1; text-align: center; border: 1px solid #2a2a2f;">
                                    <span style="display: block; font-size: 0.7rem; color: #666; margin-bottom: 3px;">Drive</span>
                                    <strong style="font-size: 0.9rem; text-transform: uppercase;"><?= htmlspecialchars(strtoupper($car['drive'] ?: 'N/A')) ?></strong>
                                </div>
                            </div>
                            
                            <a href="detail.php?make=<?= urlencode($car['make']) ?>&model=<?= urlencode($car['model']) ?>&year=<?= urlencode($car['year']) ?>" style="background: rgba(255,255,255,0.05); border: 1px solid #333; color: #ccc; text-align: center; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; display: block; text-decoration: none;" onmouseover="this.style.background='#fff'; this.style.color='#000'; this.style.borderColor='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='#ccc'; this.style.borderColor='#333';">제원 상세보기</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #666; background: #1e1e24; border-radius: 16px; border: 1px dashed #333; margin-bottom: 60px;">
                    <div style="font-size: 3rem; margin-bottom: 15px;">텅~</div>
                    검색 결과가 없습니다. 다른 키워드로 검색해 보세요!
                </div>
            <?php endif; ?>
        </div>
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