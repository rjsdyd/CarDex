<?php
session_start();
require_once 'db.php';

// 1. 선택된 차량 정보 받기
$make = $_GET['make'] ?? '';
$model = $_GET['model'] ?? '';
$year = $_GET['year'] ?? '';

if (!$make || !$model) {
    die("<script>alert('잘못된 접근입니다.'); history.back();</script>");
}

// 2. DB에서 해당 차량 상세 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM cars WHERE make = ? AND model = ? AND year = ? LIMIT 1");
$stmt->execute([$make, $model, $year]);
$car = $stmt->fetch();

if (!$car) {
    die("<script>alert('차량 정보를 찾을 수 없습니다.'); history.back();</script>");
}

// 3. 찜하기(차고에 저장) 상태 확인
$isWished = false;
$carIdColumn = isset($car['car_id']) ? 'car_id' : (isset($car['id']) ? 'id' : null);
if ($carIdColumn && isset($_SESSION['user_id'])) {
    $wishStmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND car_id = ?");
    $wishStmt->execute([$_SESSION['user_id'], $car[$carIdColumn]]);
    $isWished = (bool)$wishStmt->fetch();
}

// 4. 유사한 클래스의 경쟁 차종 가져오기
$compStmt = $pdo->prepare("SELECT * FROM cars WHERE vehicle_class = ? AND model != ? ORDER BY RAND() LIMIT 3");
$compStmt->execute([$car['vehicle_class'], $car['model']]);
$competitors = $compStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CarDex - <?= htmlspecialchars(ucfirst($car['make']) . ' ' . ucfirst($car['model'])) ?></title>
    <style>
        /* 💡 추가됨: 화면 잘림 방지용 기본 설정 */
        * { box-sizing: border-box; }
        body { background-color: #121212; color: #fff; font-family: 'Noto Sans KR', sans-serif; margin: 0; min-width: 320px; overflow-x: hidden; }
        
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; width: 100%; }

        /* 상단 네비게이션 & 타이틀 */
        .breadcrumb { color: #888; font-size: 0.85rem; margin-bottom: 20px; word-break: break-all; }
        .breadcrumb strong { color: #ccc; }
        
        .title-section { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; gap: 30px; }
        .title-left { flex: 1; min-width: 0; }
        
        .badges { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .badge { border: 1px solid #333; padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; }
        .badge-year { background: #1e1e24; color: #ccc; }
        .badge-fuel { background: rgba(0, 255, 136, 0.1); color: #00ff88; border-color: rgba(0, 255, 136, 0.3); }
        
        .main-title { font-size: 3rem; font-weight: 900; margin: 0; text-transform: uppercase; word-wrap: break-word; line-height: 1.2; }
        .main-title span { color: #00e5ff; }

        .action-btns { display: flex; gap: 15px; flex-shrink: 0; }
        .btn-compare { background: #121212; border: 1px solid #333; color: #ccc; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap; }
        .btn-compare:hover { border-color: #fff; color: #fff; }
        .btn-wish { background: #00e5ff; border: none; color: #000; padding: 12px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap; }
        .btn-wish:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,229,255,0.3); }

        /* 중앙 벤토 그리드 */
        .bento-grid { display: grid; grid-template-columns: 2fr 1.2fr 1.2fr; gap: 20px; margin-bottom: 20px; }
        .bento-card { background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 16px; padding: 30px; position: relative; overflow: hidden; display: flex; flex-direction: column; }
        
        /* 카드 1: 퍼포먼스 */
        .perf-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 10px; }
        .perf-desc { color: #888; font-size: 0.9rem; margin-bottom: 40px; }
        .perf-boxes { display: flex; gap: 15px; margin-top: auto; }
        .p-box { background: #121212; border: 1px solid #2a2a2f; padding: 15px; border-radius: 12px; display: flex; align-items: center; gap: 15px; flex: 1; }
        .p-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 8px; display: flex; justify-content: center; align-items: center; font-size: 1.2rem; flex-shrink: 0; }
        .p-info { overflow: hidden; }
        .p-info span { display: block; font-size: 0.7rem; color: #666; margin-bottom: 3px; text-transform: uppercase; }
        .p-info strong { font-size: 1rem; text-transform: capitalize; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }

        /* 카드 2: 파워트레인 */
        .card-header { font-size: 0.9rem; color: #a0a0a0; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; }
        .spec-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; flex: 1; }
        .spec-item { background: #121212; border: 1px solid #2a2a2f; border-radius: 12px; padding: 15px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
        .spec-icon { font-size: 1.5rem; margin-bottom: 8px; opacity: 0.8; }
        .spec-item span { display: block; font-size: 0.7rem; color: #666; margin-bottom: 5px; text-transform: uppercase; }
        .spec-item strong { font-size: 1.1rem; font-weight: 800; text-transform: capitalize; }

        /* 카드 3: 연비/엔진 분석 */
        .eco-content { text-align: center; margin-top: auto; margin-bottom: auto; }
        .eco-icon { font-size: 2.5rem; margin-bottom: 15px; opacity: 0.5; }
        .eco-text { color: #888; font-size: 0.85rem; line-height: 1.6; margin-bottom: 30px; }
        .eco-source { font-size: 0.75rem; color: #555; text-align: right; margin-top: auto; }
        .eco-source span { background: rgba(255,255,255,0.05); padding: 3px 8px; border-radius: 4px; }

        /* 하단 경쟁 차종 */
        .comp-section { background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 16px; padding: 30px; }
        .comp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .comp-header h3 { margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 8px; line-height: 1.4; }
        .comp-link { color: #00e5ff; font-size: 0.85rem; font-weight: bold; white-space: nowrap; }
        
        .comp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .comp-card { background: #121212; border: 1px solid #333; border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
        .comp-card:hover { border-color: #555; transform: translateY(-2px); }
        .comp-info span { display: block; font-size: 0.75rem; color: #888; margin-bottom: 3px; }
        .comp-info strong { font-size: 1.1rem; font-weight: 800; text-transform: capitalize; }
        .comp-spec { text-align: right; flex-shrink: 0; margin-left: 10px; }
        .comp-spec strong { display: block; font-size: 1rem; }
        .comp-spec span { font-size: 0.7rem; color: #666; text-transform: uppercase; }

        /* 💡 추가됨: 모바일 반응형 CSS */
        @media (max-width: 768px) {
            /* 컨테이너 패딩 조절 */
            .container { padding: 0 15px; margin: 20px auto; }

            /* 타이틀 영역 세로 정렬 및 폰트 크기 축소 */
            .title-section { flex-direction: column; align-items: flex-start; gap: 20px; margin-bottom: 20px; }
            .main-title { font-size: 2.2rem; }
            
            /* 액션 버튼들을 가로 꽉 차게 세로로 나열 (글씨 잘림 방지) */
            .action-btns { width: 100%; flex-direction: column; gap: 10px; }
            .btn-compare, .btn-wish { width: 100%; padding: 15px; font-size: 1rem; }

            /* 벤토 그리드 1열 세로 배열 */
            .bento-grid { grid-template-columns: 1fr; gap: 15px; }
            .bento-card { padding: 20px; }
            
            /* 퍼포먼스 박스 내부 1열 배열 */
            .perf-boxes { flex-direction: column; }
            .p-box { width: 100%; }

            /* 하단 경쟁 차종 영역 세로 정렬 */
            .comp-section { padding: 20px; }
            .comp-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .comp-grid { grid-template-columns: 1fr; gap: 15px; }
            .comp-card { padding: 15px; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="breadcrumb">
        Home > <?= htmlspecialchars(ucfirst($car['make'])) ?> > <strong><?= htmlspecialchars(strtoupper($car['model'])) ?></strong>
    </div>

    <div class="title-section">
        <div class="title-left">
            <div class="badges">
                <div class="badge badge-year"><?= htmlspecialchars($car['year']) ?></div>
                <div class="badge badge-fuel"><?= htmlspecialchars(ucfirst($car['fuel_type'] ?: 'Unknown')) ?></div>
            </div>
            <h1 class="main-title"><?= htmlspecialchars(strtoupper($car['make'])) ?> <span><?= htmlspecialchars(strtoupper($car['model'])) ?></span></h1>
        </div>
        
        <div class="action-btns">
            <!-- 비교함 담기 버튼 -->
            <a href="compare.php?search1=<?= urlencode($car['model']) ?>" class="btn-compare">
                ⚖️ 비교함 담기
            </a>
            <!-- 찜하기 버튼 -->
            <button id="detail-wish-btn" class="btn-wish" onclick="toggleDetailWish(<?= $car[$carIdColumn] ?>)">
                <?= $isWished ? '❤️ 차고에 저장됨' : '🤍 차고에 저장' ?>
            </button>
        </div>
    </div>

    <!-- 중앙 벤토 박스 영역 -->
    <div class="bento-grid">
        <!-- 1. 퍼포먼스 박스 -->
        <div class="bento-card">
            <div class="perf-title">완벽한 퍼포먼스와 스펙</div>
            <div class="perf-desc">글로벌 기준에 맞춘 상세 제원 분석</div>
            
            <div class="perf-boxes">
                <div class="p-box">
                    <div class="p-icon">🚙</div>
                    <div class="p-info">
                        <span>Class</span>
                        <strong><?= htmlspecialchars($car['vehicle_class'] ?: 'N/A') ?></strong>
                    </div>
                </div>
                <div class="p-box">
                    <div class="p-icon">🏢</div>
                    <div class="p-info">
                        <span>Make</span>
                        <strong><?= htmlspecialchars($car['make']) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. 파워트레인 박스 -->
        <div class="bento-card">
            <div class="card-header">⚙️ 파워트레인</div>
            <div class="spec-grid">
                <div class="spec-item">
                    <div class="spec-icon">⛽</div>
                    <span>Fuel Type</span>
                    <strong><?= htmlspecialchars($car['fuel_type'] ?: 'N/A') ?></strong>
                </div>
                <div class="spec-item">
                    <div class="spec-icon">🛠️</div>
                    <span>Drive</span>
                    <strong><?= htmlspecialchars(strtoupper($car['drive'] ?: 'N/A')) ?></strong>
                </div>
                <div class="spec-item">
                    <div class="spec-icon">📅</div>
                    <span>Year</span>
                    <strong><?= htmlspecialchars($car['year']) ?></strong>
                </div>
                <div class="spec-item">
                    <div class="spec-icon">🕹</div>
                    <span>Trans</span>
                    <strong><?= htmlspecialchars($car['transmission'] === 'a' ? 'Auto' : ($car['transmission'] === 'm' ? 'Manual' : 'A/M')) ?></strong>
                </div>
            </div>
        </div>

        <!-- 3. 연비 분석 / 엔진 상세 -->
        <div class="bento-card">
            <?php if ($car['combination_mpg'] > 0): ?>
                <div class="card-header">⛽ 연비 분석 (Fuel Economy)</div>
                <div class="eco-content">
                    <div class="eco-icon" style="opacity: 1;">🌿</div>
                    <h2 style="font-size: 2.5rem; margin: 10px 0; color: #00ff88;"><?= htmlspecialchars($car['combination_mpg']) ?> <span style="font-size: 1rem; color: #888;">MPG</span></h2>
                    <p class="eco-text">도심 <?= htmlspecialchars($car['city_mpg']) ?> / 고속도로 <?= htmlspecialchars($car['highway_mpg']) ?></p>
                </div>
            <?php else: ?>
                <div class="card-header">⚙️ 엔진 상세 스펙 (Engine Specs)</div>
                <div class="eco-content">
                    <?php 
                    $fuelStr = strtolower($car['fuel_type']);
                    if (strpos($fuelStr, 'electric') !== false || $fuelStr === 'electricity'): 
                    ?>
                        <div class="eco-icon" style="opacity: 1;">⚡</div>
                        <h2 style="font-size: 2rem; margin: 10px 0; color: #00e5ff;">Pure Electric</h2>
                        <p class="eco-text">순수 전기 및 전동화 모델입니다.<br><span style="font-size: 0.8rem; color: #666;">내연기관 배기량/연비 데이터 미제공</span></p>
                    <?php else: ?>
                        <div class="eco-icon" style="opacity: 1;">🔥</div>
                        <h2 style="font-size: 2.5rem; margin: 10px 0; color: #ff4b4b;"><?= htmlspecialchars($car['cylinders'] ?: '0') ?> <span style="font-size: 1rem; color: #888;">기통</span></h2>
                        <p class="eco-text">배기량(Displacement) : <?= htmlspecialchars($car['displacement'] ?: '0') ?> L<br><span style="font-size: 0.75rem; color: #666;">연비 데이터 미제공 모델</span></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="eco-source">데이터 출처 <span>API Ninjas</span></div>
        </div>
    </div>

    <!-- 하단 경쟁 차종 박스 -->
    <div class="comp-section">
        <div class="comp-header">
            <h3>🚙 유사한 클래스(<?= htmlspecialchars($car['vehicle_class'] ?: 'Unknown') ?>)의 경쟁 차종</h3>
            <a href="category.php?body_style=all" class="comp-link">비교함으로 이동 ➔</a>
        </div>
        
        <div class="comp-grid">
            <?php if (!empty($competitors)): ?>
                <?php foreach ($competitors as $comp): ?>
                    <a href="detail.php?make=<?= urlencode($comp['make']) ?>&model=<?= urlencode($comp['model']) ?>&year=<?= urlencode($comp['year']) ?>" class="comp-card">
                        <div class="comp-info">
                            <span><?= htmlspecialchars(ucfirst($comp['make'])) ?></span>
                            <strong><?= htmlspecialchars(strtolower($comp['model'])) ?></strong>
                        </div>
                        <div class="comp-spec">
                            <strong><?= htmlspecialchars($comp['combination_mpg'] > 0 ? $comp['combination_mpg'] . ' MPG' : '- MPG') ?></strong>
                            <span><?= htmlspecialchars(strtoupper($comp['drive'] ?: 'N/A')) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; padding: 20px; color: #666; text-align: center;">유사한 클래스의 경쟁 차종 데이터가 부족합니다.</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- 찜하기(AJAX) 스크립트 -->
<script>
    function toggleDetailWish(carId) {
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
                const btn = document.getElementById('detail-wish-btn');
                if(data.action === 'added') {
                    btn.innerHTML = '❤️ 차고에 저장됨';
                } else {
                    btn.innerHTML = '🤍 차고에 저장';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('오류가 발생했습니다. 다시 시도해주세요.');
        });
    }
</script>

</body>
</html>