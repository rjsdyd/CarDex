<?php
require_once 'db.php';

// 1. URL에서 브랜드, 모델명, 연식을 받아옴
$make = $_GET['make'] ?? '';
$model = $_GET['model'] ?? '';
$year = $_GET['year'] ?? '';

if (!$make || !$model) {
    die("<script>alert('잘못된 접근입니다.'); location.href='index.php';</script>");
}

// 2. 해당 자동차 데이터를 DB에서 꺼내오기 (id 대신 3가지 조합으로 검색)
$stmt = $pdo->prepare("SELECT * FROM cars WHERE make = :make AND model = :model AND year = :year LIMIT 1");
$stmt->execute([
    'make' => $make, 
    'model' => $model, 
    'year' => $year
]);
$car = $stmt->fetch();

if (!$car) {
    die("<script>alert('데이터를 찾을 수 없습니다.'); location.href='index.php';</script>");
}

// 3. 같은 클래스(차종)의 다른 자동차 3대 자동으로 가져오기
$simStmt = $pdo->prepare("SELECT * FROM cars WHERE vehicle_class = :class AND model != :model LIMIT 3");
$simStmt->execute([
    'class' => $car['vehicle_class'],
    'model' => $car['model']
]);
$similarCars = $simStmt->fetchAll();

// 4. DB 데이터를 화면에 뿌리기 좋게 변수 정리
$make = $car['make'];
$model = $car['model'];
$year = $car['year'];
$fuelType = ucfirst($car['fuel_type']);
$class = $car['vehicle_class'];
$drive = strtoupper($car['drive']);

// 연비 계산 로직 (1 MPG = 약 0.425 km/L)
$city_mpg = (int)$car['city_mpg'];
$hwy_mpg = (int)$car['highway_mpg'];

$city_kml = $city_mpg > 0 ? number_format($city_mpg * 0.425, 1) : '-';
$hwy_kml = $hwy_mpg > 0 ? number_format($hwy_mpg * 0.425, 1) : '-';

$city_percent = $city_mpg > 0 ? min(($city_mpg / 50) * 100, 100) : 0;
$hwy_percent = $hwy_mpg > 0 ? min(($hwy_mpg / 50) * 100, 100) : 0;
// 5. 현재 이 자동차가 내 차고(wishlist)에 있는지 확인
$wishStmt = $pdo->prepare("SELECT * FROM wishlist WHERE car_id = :car_id");
$wishStmt->execute(['car_id' => $car['car_id']]);
$isWished = $wishStmt->fetch(); // 데이터가 있으면 true(배열), 없으면 false
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarDex - <?= htmlspecialchars(ucfirst($make) . ' ' . $model) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo" style="text-decoration: none; color: inherit;">
            <span class="logo-icon">🚘</span> CarDex
        </a>
        <div class="nav-links">
            <a href="index.php">검색 (Search)</a>
            <a href="#">비교함 (Compare)</a>
            <a href="#">마이페이지 (My Page)</a>
        </div>
        <div class="nav-icons">
            <span style="cursor:pointer;">🔔</span>
            <div class="user-avatar">U</div>
        </div>
    </nav>

    <div class="detail-container">
        
        <!-- 상단 헤더 -->
        <div class="detail-header">
            <div class="breadcrumb">Home > <?= htmlspecialchars(ucfirst($make)) ?> > <strong><?= htmlspecialchars(ucfirst($model)) ?></strong></div>
            
            <div class="title-action-wrap">
                <div class="title-area">
                    <div class="tags">
                        <span class="tag tag-outline"><?= htmlspecialchars($year) ?></span>
                        <span class="tag tag-green"><?= htmlspecialchars($fuelType) ?></span>
                    </div>
                    <h1><?= htmlspecialchars(strtoupper($make)) ?> <span class="text-cyan"><?= htmlspecialchars(strtoupper($model)) ?></span></h1>
                </div>
                
                <div class="action-area">
                    <button class="btn btn-outline">⚖️ 비교함 담기</button>
                    <button class="btn btn-primary" id="wishBtn" onclick="toggleWish(<?= $car['car_id'] ?>)"
                            style="<?= $isWished ? 'background: #ff4b4b; color: #fff;' : '' ?>">
                        <?= $isWished ? '❤️ 차고에 저장됨' : '🤍 차고에 저장' ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="detail-bento-grid">
            
            <!-- 1. 히어로 카드 -->
            <div class="card d-card-hero">
                <h2>완벽한 퍼포먼스와 스펙</h2>
                <p>글로벌 기준에 맞춘 상세 제원 분석</p>
                <div class="hero-bg-car">🚙</div> 

                <div class="hero-badges">
                    <div class="h-badge">
                        <span class="icon">🚘</span>
                        <div><small>CLASS</small><br><strong><?= htmlspecialchars($class) ?></strong></div>
                    </div>
                    <div class="h-badge">
                        <span class="icon">🏢</span>
                        <div><small>MAKE</small><br><strong><?= htmlspecialchars(ucfirst($make)) ?></strong></div>
                    </div>
                </div>
            </div>

            <!-- 2. 파워트레인 카드 -->
            <div class="card d-card-powertrain">
                <div class="card-title"><span class="text-cyan">⚙️</span> 파워트레인</div>
                <div class="pt-grid">
                    <div>
                        <small>FUEL TYPE</small>
                        <strong><?= htmlspecialchars($fuelType) ?></strong>
                    </div>
                    <div>
                        <small>DRIVE</small>
                        <strong><?= htmlspecialchars($drive) ?></strong>
                    </div>
                    <div>
                        <small>YEAR</small>
                        <strong><?= htmlspecialchars($year) ?> 년식</strong>
                    </div>
                    <div>
                        <small>TRANSMISSION</small>
                        <strong>Auto / Manual</strong>
                    </div>
                </div>
            </div>

            <!-- 3. 연비 카드 -->
            <div class="card d-card-fuel">
                <div class="card-title"><span class="text-cyan">⛽</span> 연비 분석 (Fuel Economy)</div>
                
                <?php if ($city_mpg > 0 || $hwy_mpg > 0): ?>
                    <!-- 정상적으로 연비 데이터가 있을 때 보여줄 화면 -->
                    <div class="fuel-item">
                        <div class="f-label">도심 (City) <span><?= $city_kml ?> <small>km/L</small></span></div>
                        <div class="f-bar-bg"><div class="f-bar-fill city" style="width: <?= $city_percent ?>%;"></div></div>
                        <div class="f-sub"><?= $city_mpg ?> MPG (미국 기준)</div>
                    </div>

                    <div class="fuel-item">
                        <div class="f-label">고속 (Highway) <span><?= $hwy_kml ?> <small>km/L</small></span></div>
                        <div class="f-bar-bg"><div class="f-bar-fill highway" style="width: <?= $hwy_percent ?>%;"></div></div>
                        <div class="f-sub"><?= $hwy_mpg ?> MPG (미국 기준)</div>
                    </div>
                <?php else: ?>
                    <!-- API가 연비(0)를 안 줬을 때 보여줄 방어 화면 -->
                    <div style="flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; color:#a0a0a0; font-size:0.9rem; text-align:center; padding: 20px 0;">
                        <span style="font-size:2rem; margin-bottom:10px; opacity:0.5;">📉</span>
                        해당 차량은 데이터 제공사(API)의 사정으로<br>연비 상세 스펙이 제공되지 않습니다.
                    </div>
                <?php endif; ?>

                <div class="data-source">데이터 출처 <span>API Ninjas</span></div>
            </div>

            <!-- 4. 유사 차종 (동적 렌더링) -->
            <div class="card d-card-similar">
                <div class="similar-header">
                    <div class="card-title"><span class="text-cyan">🚙</span> 유사한 클래스(<?= htmlspecialchars($class) ?>)의 경쟁 차종</div>
                    <a href="index.php?q=<?= urlencode($class) ?>" class="text-cyan">비교함으로 이동 →</a>
                </div>
                
                <div class="similar-list">
                    <?php if (count($similarCars) > 0): ?>
                        <?php foreach($similarCars as $sim): ?>
                            <?php 
                                $sim_kml = $sim['city_mpg'] > 0 ? number_format($sim['city_mpg'] * 0.425, 1) : '-'; 
                            ?>
                            <!-- 👇 유사 차종 클릭 시에도 브랜드, 모델명, 연식을 들고 이동! 👇 -->
                            <div class="sim-item" onclick="location.href='detail.php?make=<?= urlencode($sim['make']) ?>&model=<?= urlencode($sim['model']) ?>&year=<?= urlencode($sim['year']) ?>'">
                                <div>
                                    <div class="sim-brand"><?= htmlspecialchars(ucfirst($sim['make'])) ?></div>
                                    <div class="sim-name"><?= htmlspecialchars($sim['model']) ?></div>
                                </div>
                                <div class="sim-spec"><strong><?= $sim_kml ?></strong> km/L<br><small><?= htmlspecialchars(strtoupper($sim['drive'])) ?></small></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#a0a0a0; font-size:0.9rem; padding: 10px;">데이터베이스에 아직 비슷한 스펙의 차량이 수집되지 않았습니다.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleWish(carId) {
            const btn = document.getElementById('wishBtn');
            
            // 찜하기 백엔드 파일로 몰래 데이터 쏘기
            fetch('toggle_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ car_id: carId })
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    if(data.action === 'added') {
                        btn.innerHTML = '❤️ 차고에 저장됨';
                        btn.style.background = '#ff4b4b'; // 빨간색으로 변경
                        btn.style.color = '#fff';
                    } else {
                        btn.innerHTML = '🤍 차고에 저장';
                        btn.style.background = 'var(--primary-cyan)'; // 원래 민트색으로
                        btn.style.color = '#000';
                    }
                } else {
                    alert('오류가 발생했습니다: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>