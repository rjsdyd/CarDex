<?php
session_start();
require_once 'db.php';

// API 키 세팅
$apiKey = getenv('API_NINJAS_KEY') ?: $_ENV['API_NINJAS_KEY'] ?? ''; 

$search1 = $_GET['search1'] ?? '';
$search2 = $_GET['search2'] ?? '';

// 🚀 자동차 데이터를 가져오는 마법의 함수 (DB 먼저, 없으면 API 통신)
function getCarData($pdo, $searchTerm, $apiKey) {
    $searchTerm = trim($searchTerm); // 혹시 모를 띄어쓰기 방어
    if (!$searchTerm) return null;
    
    // 1. 로컬 DB(캐시)에서 가장 먼저 검색
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE make LIKE :term1 OR model LIKE :term2 ORDER BY year DESC LIMIT 1");
    $stmt->execute(['term1' => "%$searchTerm%", 'term2' => "%$searchTerm%"]);
    $car = $stmt->fetch();
    
    // 2. DB에 없으면 API 호출해서 가져오기
    if (!$car && $apiKey) {
        $url = 'https://api.api-ninjas.com/v1/cars?model=' . urlencode($searchTerm);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $apiCars = json_decode($response, true);
        
        // API 자체 에러가 났을 때
        if (isset($apiCars['error'])) {
            echo "<script>console.error('🚨 API 연동 에러: " . addslashes($apiCars['error']) . "');</script>";
        } 
        // 정상적으로 차를 찾았을 때
        elseif (!empty($apiCars) && is_array($apiCars) && isset($apiCars[0])) {
            $apiCar = $apiCars[0];
            
            try {
                $insertStmt = $pdo->prepare("INSERT INTO cars (make, model, year, vehicle_class, drive, fuel_type, transmission, cylinders, displacement, city_mpg, highway_mpg) VALUES (:make, :model, :year, :vehicle_class, :drive, :fuel_type, :transmission, :cylinders, :displacement, :city_mpg, :highway_mpg)");
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
                
                // 방금 저장한 데이터 다시 불러오기
                $stmt->execute(['term1' => "%$searchTerm%", 'term2' => "%$searchTerm%"]);
                $car = $stmt->fetch();
            } catch (PDOException $e) {
                // 👇 DB 저장 실패 시 범인을 콘솔에 띄움!
                echo "<script>console.error('🚨 DB 저장 실패: " . addslashes($e->getMessage()) . "');</script>";
            }
        } else {
            echo "<script>console.error('🚨 API에서 해당 차량을 찾을 수 없음: $searchTerm');</script>";
        }
    }
    return $car;
}

$car1 = getCarData($pdo, $search1, $apiKey);
$car2 = getCarData($pdo, $search2, $apiKey);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CarDex - 1:1 비교함</title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Pretendard', sans-serif; }
        body { background-color: #121212; color: #fff; line-height: 1.6; }
        a { text-decoration: none; color: inherit; }
        
        /* 네비게이션바 (메인과 동일) */
        header { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; background-color: #1a1a1a; border-bottom: 1px solid #333; }
        .logo { font-size: 1.5rem; font-weight: 800; color: #fff; }
        .nav-links { display: flex; gap: 20px; font-weight: 600; font-size: 0.95rem; }
        .nav-links a { color: #888; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { color: #fff; }
        
        /* 유저 UI 추가 */
        .nav-icons { display: flex; align-items: center; gap: 15px; }
        .user-avatar { background: var(--primary-cyan, #00e5ff); color: #000; font-weight: bold; border-radius: 20px; padding: 5px 15px; font-size: 0.9rem; }

        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-title { text-align: center; margin-bottom: 40px; }
        .page-title h1 { font-size: 2.5rem; font-weight: 800; color: #00e5ff; }
        
        .compare-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .car-slot { background-color: #1e1e24; border-radius: 16px; padding: 30px; text-align: center; min-height: 500px; border: 1px solid #333; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .search-box input { width: 80%; padding: 12px 20px; border-radius: 20px; border: 1px solid #444; background: #2a2a30; color: #fff; margin-bottom: 10px; font-size: 1rem; }
        .search-box button { padding: 10px 24px; border-radius: 20px; border: none; background: #00e5ff; color: #000; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .search-box button:hover { transform: translateY(-2px); }
        
        /* 제원표 스타일 */
        .spec-table { margin-top: 30px; text-align: left; background: #121212; border-radius: 12px; padding: 20px; }
        .spec-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #2a2a2f; font-size: 0.95rem; }
        .spec-row:last-child { border-bottom: none; }
        .spec-row span { color: #888; }
        .spec-row strong { color: #fff; text-transform: capitalize; }
        
        .car-header h2 { color: #00e5ff; font-size: 1.2rem; text-transform: uppercase; margin-bottom: 5px; margin-top: 20px; }
        .car-header h3 { font-size: 2rem; text-transform: capitalize; }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo">🚘 CarDex</a>
    <nav class="nav-links">
        <a href="index.php">검색 (Search)</a>
        <a href="compare.php" class="active">비교함 (Compare)</a>
    </nav>
    <div class="nav-icons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-avatar"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <a href="logout.php" style="font-size: 0.8rem; color: #a0a0a0; font-weight: bold;">로그아웃</a>
        <?php else: ?>
            <div class="user-avatar" style="padding: 5px 10px; border-radius: 50%;">U</div>
            <a href="login.php" style="font-size: 0.8rem; color: #00e5ff; font-weight: bold;">로그인</a>
        <?php endif; ?>
    </div>
</header>

<div class="container">
    <div class="page-title">
        <h1>VS 비교 매치업</h1>
        <p>글로벌 자동차 스펙을 한눈에 나란히 비교하세요.</p>
    </div>

    <div class="compare-grid">
        <!-- 왼쪽 차량 (Car 1) -->
        <div class="car-slot">
            <form class="search-box" action="" method="GET">
                <input type="text" name="search1" placeholder="차량 1 검색 (예: camry)" value="<?= htmlspecialchars($search1) ?>" autocomplete="off">
                <input type="hidden" name="search2" value="<?= htmlspecialchars($search2) ?>">
                <br><button type="submit">검색</button>
            </form>
            
            <?php if ($car1): ?>
                <div class="car-header">
                    <h2><?= htmlspecialchars($car1['make']) ?></h2>
                    <h3><?= htmlspecialchars($car1['model']) ?> (<?= htmlspecialchars($car1['year']) ?>)</h3>
                </div>
                <div class="spec-table">
                    <div class="spec-row"><span>클래스</span> <strong><?= htmlspecialchars($car1['vehicle_class']) ?></strong></div>
                    <div class="spec-row"><span>구동 방식</span> <strong><?= htmlspecialchars(strtoupper($car1['drive'])) ?></strong></div>
                    <div class="spec-row"><span>연료 타입</span> <strong><?= htmlspecialchars($car1['fuel_type']) ?></strong></div>
                    <div class="spec-row"><span>변속기</span> <strong><?= htmlspecialchars($car1['transmission']) ?></strong></div>
                    <div class="spec-row"><span>엔진</span> <strong><?= htmlspecialchars($car1['displacement']) ?>L / <?= htmlspecialchars($car1['cylinders']) ?>기통</strong></div>
                    <div class="spec-row"><span>도심 연비</span> <strong style="color: #00e5ff;"><?= htmlspecialchars($car1['city_mpg']) ?> mpg</strong></div>
                    <div class="spec-row"><span>고속 연비</span> <strong style="color: #00ff66;"><?= htmlspecialchars($car1['highway_mpg']) ?> mpg</strong></div>
                </div>
            <?php else: ?>
                <div style="margin-top: 50px; color: #666;">왼쪽 차량을 검색해 주세요.</div>
            <?php endif; ?>
        </div>

        <!-- 오른쪽 차량 (Car 2) -->
        <div class="car-slot">
            <form class="search-box" action="" method="GET">
                <input type="text" name="search2" placeholder="차량 2 검색 (예: sonata)" value="<?= htmlspecialchars($search2) ?>" autocomplete="off">
                <input type="hidden" name="search1" value="<?= htmlspecialchars($search1) ?>">
                <br><button type="submit">검색</button>
            </form>

            <?php if ($car2): ?>
                <div class="car-header">
                    <h2><?= htmlspecialchars($car2['make']) ?></h2>
                    <h3><?= htmlspecialchars($car2['model']) ?> (<?= htmlspecialchars($car2['year']) ?>)</h3>
                </div>
                <div class="spec-table">
                    <div class="spec-row"><span>클래스</span> <strong><?= htmlspecialchars($car2['vehicle_class']) ?></strong></div>
                    <div class="spec-row"><span>구동 방식</span> <strong><?= htmlspecialchars(strtoupper($car2['drive'])) ?></strong></div>
                    <div class="spec-row"><span>연료 타입</span> <strong><?= htmlspecialchars($car2['fuel_type']) ?></strong></div>
                    <div class="spec-row"><span>변속기</span> <strong><?= htmlspecialchars($car2['transmission']) ?></strong></div>
                    <div class="spec-row"><span>엔진</span> <strong><?= htmlspecialchars($car2['displacement']) ?>L / <?= htmlspecialchars($car2['cylinders']) ?>기통</strong></div>
                    <div class="spec-row"><span>도심 연비</span> <strong style="color: #00e5ff;"><?= htmlspecialchars($car2['city_mpg']) ?> mpg</strong></div>
                    <div class="spec-row"><span>고속 연비</span> <strong style="color: #00ff66;"><?= htmlspecialchars($car2['highway_mpg']) ?> mpg</strong></div>
                </div>
            <?php else: ?>
                <div style="margin-top: 50px; color: #666;">오른쪽 차량을 검색해 주세요.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>