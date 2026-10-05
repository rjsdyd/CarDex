<?php
session_start();
require_once 'db.php';

// 1. 좌/우 검색어 받기
$search1 = $_GET['search1'] ?? '';
$search2 = $_GET['search2'] ?? '';

// 2. 차량 정보 가져오는 함수
function getCarData($pdo, $term) {
    if (!$term) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE LOWER(make) = LOWER(?) OR LOWER(model) = LOWER(?) ORDER BY year DESC LIMIT 1");
    $stmt->execute([$term, $term]);
    $car = $stmt->fetch();
    
    if(!$car) {
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE LOWER(model) LIKE ? ORDER BY year DESC LIMIT 1");
        $stmt->execute(['%' . strtolower($term) . '%']);
        $car = $stmt->fetch();
    }
    return $car;
}

$car1 = getCarData($pdo, $search1);
$car2 = getCarData($pdo, $search2);

// 3. 추천 차량 8대 랜덤 추출
$recStmt = $pdo->query("SELECT make, model FROM cars ORDER BY RAND() LIMIT 8");
$recCars = $recStmt->fetchAll();
$recCars1 = array_slice($recCars, 0, 4);
$recCars2 = array_slice($recCars, 4, 4);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CarDex - VS 비교 매치업</title>
    <style>
        body { background-color: #121212; color: #fff; font-family: 'Noto Sans KR', sans-serif; margin: 0; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1000px; margin: 60px auto; padding: 0 20px; }

        .page-header { text-align: center; margin-bottom: 50px; }
        .page-header h1 { font-size: 2.5rem; font-weight: 900; color: #00e5ff; margin-bottom: 10px; }
        .page-header p { color: #a0a0a0; font-size: 1rem; }

        .compare-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        
        .compare-card { background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 16px; padding: 40px; text-align: center; transition: 0.3s; display: flex; flex-direction: column; justify-content: center; min-height: 480px; }
        .compare-card:hover { border-color: #444; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }

        /* 검색 폼 UI */
        .search-input { width: 80%; padding: 15px 20px; border-radius: 30px; border: 1px solid #333; background: #121212; color: #fff; font-size: 1rem; margin-bottom: 20px; outline: none; text-align: center; }
        .search-input:focus { border-color: #00e5ff; }
        .btn-search { background: #00e5ff; color: #000; border: none; padding: 12px 30px; border-radius: 20px; font-weight: 800; cursor: pointer; transition: 0.2s; font-size: 1rem; }
        .btn-search:hover { transform: scale(1.05); }
        .empty-text { color: #666; margin-top: 20px; font-size: 0.9rem; }

        /* 추천 차량 뱃지 UI */
        .rec-section { margin-top: 40px; border-top: 1px dashed #333; padding-top: 25px; }
        .rec-title { color: #888; font-size: 0.85rem; margin-bottom: 15px; font-weight: 600; }
        .rec-badges { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .rec-badge { background: #121212; border: 1px solid #333; padding: 10px 15px; border-radius: 20px; color: #ccc; font-size: 0.85rem; cursor: pointer; transition: 0.2s; font-weight: 500; }
        .rec-badge:hover { border-color: #00e5ff; color: #00e5ff; background: rgba(0,229,255,0.05); transform: translateY(-2px); }

        /* 제원 결과 렌더링 UI */
        /* 👇 내부 요소들이 높이를 꽉 채우도록 설정 */
        .inner-wrap { display: flex; flex-direction: column; height: 100%; }
        
        /* 👇 타이틀 영역의 최소 높이를 120px로 고정해서 글자가 길어져도 제원표 시작 위치를 맞춤 */
        .car-title-wrapper { min-height: 120px; display: flex; flex-direction: column; justify-content: center; margin-bottom: auto; }
        
        .c-brand { color: #00e5ff; font-weight: 800; font-size: 1rem; text-transform: uppercase; margin-bottom: 5px; }
        .c-model { font-size: 2.2rem; font-weight: 900; margin-bottom: 5px; text-transform: capitalize; line-height: 1.2; }
        .c-year { font-size: 1rem; color: #888; margin-bottom: 0; }
        
        .spec-list { text-align: left; background: #121212; border: 1px solid #2a2a2f; border-radius: 12px; padding: 20px; margin-top: 20px; margin-bottom: 20px; }
        .spec-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #222; }
        .spec-row:last-child { border-bottom: none; }
        .spec-label { color: #888; font-size: 0.9rem; }
        .spec-value { color: #fff; font-weight: 700; font-size: 1rem; text-transform: capitalize; text-align: right; }

        .btn-change { background: rgba(255,255,255,0.05); border: 1px solid #333; color: #ccc; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; display: block; transition: 0.2s; cursor: pointer; }
        .btn-change:hover { background: #fff; color: #000; border-color: #fff; }

    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>VS 비교 매치업</h1>
        <p>글로벌 자동차 스펙을 한눈에 나란히 비교하세요.</p>
    </div>

    <div class="compare-container">
        
        <!-- ================= 왼쪽 차량 (Card 1) ================= -->
        <div class="compare-card">
            <?php if ($car1): ?>
                <div class="inner-wrap">
                    <!-- 👇 타이틀 영역을 묶어서 높이 고정 -->
                    <div class="car-title-wrapper">
                        <div class="c-brand"><?= htmlspecialchars($car1['make']) ?></div>
                        <div class="c-model"><?= htmlspecialchars($car1['model']) ?></div>
                        <div class="c-year"><?= htmlspecialchars($car1['year']) ?></div>
                    </div>
                    
                    <div class="spec-list">
                        <div class="spec-row">
                            <span class="spec-label">차급 (Class)</span>
                            <span class="spec-value"><?= htmlspecialchars($car1['vehicle_class'] ?: '- -') ?></span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">연료 (Fuel)</span>
                            <span class="spec-value"><?= htmlspecialchars($car1['fuel_type'] ?: '- -') ?></span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">구동 방식 (Drive)</span>
                            <span class="spec-value"><?= htmlspecialchars(strtoupper($car1['drive'] ?: '- -')) ?></span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">복합 연비 (MPG)</span>
                            <span class="spec-value"><?= htmlspecialchars($car1['combination_mpg'] ?: '0') ?> mpg</span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">배기량 / 기통</span>
                            <span class="spec-value"><?= htmlspecialchars($car1['displacement'] ?: '0') ?>L / <?= htmlspecialchars($car1['cylinders'] ?: '0') ?>기통</span>
                        </div>
                    </div>
                    
                    <a href="compare.php?search2=<?= urlencode($search2) ?>" class="btn-change">🔄 다른 차량 선택</a>
                </div>
            <?php else: ?>
                <form method="GET" id="form1">
                    <input type="hidden" name="search2" value="<?= htmlspecialchars($search2) ?>">
                    <input type="text" name="search1" id="input1" class="search-input" placeholder="차량 1 검색 (예: camry)" autocomplete="off">
                    <button type="submit" class="btn-search">검색</button>
                    <div class="empty-text">왼쪽 차량을 검색해 주세요.</div>
                </form>
                
                <div class="rec-section">
                    <div class="rec-title">💡 이 차량은 어떠세요?</div>
                    <div class="rec-badges">
                        <?php foreach($recCars1 as $rc): ?>
                            <div class="rec-badge" onclick="document.getElementById('input1').value='<?= $rc['model'] ?>'; document.getElementById('form1').submit();">
                                <?= ucfirst($rc['make']) ?> <?= ucfirst($rc['model']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ================= 오른쪽 차량 (Card 2) ================= -->
        <div class="compare-card">
            <?php if ($car2): ?>
                <div class="inner-wrap">
                    <!-- 👇 타이틀 영역을 묶어서 높이 고정 -->
                    <div class="car-title-wrapper">
                        <div class="c-brand"><?= htmlspecialchars($car2['make']) ?></div>
                        <div class="c-model"><?= htmlspecialchars($car2['model']) ?></div>
                        <div class="c-year"><?= htmlspecialchars($car2['year']) ?></div>
                    </div>
                    
                    <div class="spec-list">
                        <div class="spec-row">
                            <span class="spec-label">차급 (Class)</span>
                            <span class="spec-value"><?= htmlspecialchars($car2['vehicle_class'] ?: '- -') ?></span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">연료 (Fuel)</span>
                            <span class="spec-value"><?= htmlspecialchars($car2['fuel_type'] ?: '- -') ?></span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">구동 방식 (Drive)</span>
                            <span class="spec-value"><?= htmlspecialchars(strtoupper($car2['drive'] ?: '- -')) ?></span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">복합 연비 (MPG)</span>
                            <span class="spec-value"><?= htmlspecialchars($car2['combination_mpg'] ?: '0') ?> mpg</span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">배기량 / 기통</span>
                            <span class="spec-value"><?= htmlspecialchars($car2['displacement'] ?: '0') ?>L / <?= htmlspecialchars($car2['cylinders'] ?: '0') ?>기통</span>
                        </div>
                    </div>
                    
                    <a href="compare.php?search1=<?= urlencode($search1) ?>" class="btn-change">🔄 다른 차량 선택</a>
                </div>
            <?php else: ?>
                <form method="GET" id="form2">
                    <input type="hidden" name="search1" value="<?= htmlspecialchars($search1) ?>">
                    <input type="text" name="search2" id="input2" class="search-input" placeholder="차량 2 검색 (예: sonata)" autocomplete="off">
                    <button type="submit" class="btn-search">검색</button>
                    <div class="empty-text">오른쪽 차량을 검색해 주세요.</div>
                </form>
                
                <div class="rec-section">
                    <div class="rec-title">💡 이 차량은 어떠세요?</div>
                    <div class="rec-badges">
                        <?php foreach($recCars2 as $rc): ?>
                            <div class="rec-badge" onclick="document.getElementById('input2').value='<?= $rc['model'] ?>'; document.getElementById('form2').submit();">
                                <?= ucfirst($rc['make']) ?> <?= ucfirst($rc['model']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>