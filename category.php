<?php
session_start();
require_once 'db.php';

// 1. 상단 배지용 DB 전체 Row 수 가져오기
$countStmt = $pdo->query("SELECT COUNT(*) FROM cars");
$totalRows = number_format($countStmt->fetchColumn());

// 2. 카테고리 필터값 받기
$currentCountry = $_GET['country'] ?? 'ALL'; 
$currentPowertrain = $_GET['powertrain'] ?? 'all';
$currentBodyStyle = $_GET['body_style'] ?? 'all'; 
$currentBrand = $_GET['brand'] ?? ''; 
$currentSort = $_GET['sort'] ?? 'popular'; // 👇 정렬 파라미터 추가!

$brandParam = $currentBrand ? '&brand=' . urlencode($currentBrand) : '';
$sortParam = '&sort=' . urlencode($currentSort);

// 국가별 대표 브랜드 설정
$countryBrands = [
    'ALL' => [], 
    'DE' => ['bmw', 'mercedes-benz', 'audi', 'porsche', 'volkswagen'],
    'KR' => ['hyundai', 'kia', 'genesis', 'chevrolet'],
    'US' => ['tesla', 'ford', 'jeep', 'cadillac'],
    'JP' => ['toyota', 'honda', 'nissan', 'lexus', 'subaru']
];

$countryNames = [
    'ALL' => '전체', 'DE' => '독일', 'KR' => '한국', 'US' => '미국', 'JP' => '일본'
];

$targetBrands = $countryBrands[$currentCountry] ?? [];
$displayBrands = $currentCountry === 'ALL' ? ['bmw', 'hyundai', 'tesla', 'toyota', 'porsche', 'genesis'] : $targetBrands;

// 3. 필터 조건에 맞는 차량들 DB에서 불러오기
$categoryCars = [];

// 파워트레인 조건식
$powertrainSql = "";
if ($currentPowertrain === 'electric') {
    $powertrainSql = " AND LOWER(fuel_type) = 'electricity'";
} elseif ($currentPowertrain === 'hybrid') {
    $powertrainSql = " AND LOWER(fuel_type) LIKE '%hybrid%'";
} elseif ($currentPowertrain === 'combustion') {
    $powertrainSql = " AND LOWER(fuel_type) != 'electricity' AND LOWER(fuel_type) NOT LIKE '%hybrid%'";
}

// 차급별(Body Style) 조건식
$bodyStyleSql = "";
if ($currentBodyStyle === 'suv') {
    $bodyStyleSql = " AND (LOWER(vehicle_class) LIKE '%sport utility%' OR LOWER(vehicle_class) LIKE '%suv%')";
} elseif ($currentBodyStyle === 'sedan') {
    $bodyStyleSql = " AND LOWER(vehicle_class) LIKE '%car%' AND LOWER(vehicle_class) NOT LIKE '%sport utility%'";
} elseif ($currentBodyStyle === 'sports') {
    $bodyStyleSql = " AND (LOWER(vehicle_class) LIKE '%two seater%' OR LOWER(vehicle_class) LIKE '%sports%')";
}

// 👇 정렬 조건식 (인기순은 기본 DB 등록순, 최신 연식순은 year 기준 내림차순)
$orderBySql = " ORDER BY created_at DESC";
if ($currentSort === 'newest') {
    $orderBySql = " ORDER BY year DESC, created_at DESC";
}

// 👇 LIMIT 12를 제거하여 전체 데이터가 출력되도록 수정
if ($currentBrand) {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE LOWER(make) = ? $powertrainSql $bodyStyleSql $orderBySql");
    $stmt->execute([strtolower($currentBrand)]);
    $categoryCars = $stmt->fetchAll();
    $sectionTitle = ucfirst($currentBrand) . " 탐색";
} else {
    if ($currentCountry === 'ALL') {
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE 1=1 $powertrainSql $bodyStyleSql $orderBySql");
        $stmt->execute();
        $categoryCars = $stmt->fetchAll();
    } else {
        if (!empty($targetBrands)) {
            $placeholders = implode(',', array_fill(0, count($targetBrands), '?'));
            $stmt = $pdo->prepare("SELECT * FROM cars WHERE LOWER(make) IN ($placeholders) $powertrainSql $bodyStyleSql $orderBySql");
            $stmt->execute($targetBrands);
            $categoryCars = $stmt->fetchAll();
        }
    }
    $sectionTitle = ($currentCountry === 'ALL' ? '글로벌 전체' : $currentCountry . ' ' . $countryNames[$currentCountry]) . ' 브랜드 탐색';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CarDex - 카테고리 탐색</title>
    <style>
        body { background-color: #121212; color: #fff; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        /* 상단 타이틀 영역 */
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .page-title h1 { font-size: 2.2rem; font-weight: 800; color: #00e5ff; margin-bottom: 5px; }
        .page-title p { color: #a0a0a0; font-size: 0.95rem; }
        .db-badge { background: #1e1e24; border: 1px solid #333; padding: 8px 15px; border-radius: 8px; font-size: 0.85rem; font-weight: bold; display: flex; align-items: center; gap: 8px; }
        .db-badge span { color: #00ff88; font-size: 1.2rem; }

        /* 필터 벤토(Bento) 그리드 */
        .filter-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .filter-card { background: #1e1e24; border-radius: 16px; border: 1px solid #2a2a2f; padding: 25px; }
        .filter-title { font-size: 0.9rem; color: #a0a0a0; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

        /* 1. 국가별 그리드 */
        .country-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .country-btn { background: #121212; border: 1px solid #333; border-radius: 12px; padding: 20px; text-align: center; display: block; transition: 0.2s; }
        .country-btn:hover { border-color: #555; }
        .country-btn.active { border-color: rgba(0, 229, 255, 0.5); background: rgba(0, 229, 255, 0.05); }
        .country-btn strong { display: block; font-size: 1.4rem; font-weight: 800; margin-bottom: 5px; }
        .country-btn span { display: block; font-size: 0.8rem; color: #888; }

        /* 2 & 3. 파워트레인 / 차급별 버튼 리스트 */
        .list-btn-group { display: flex; flex-direction: column; gap: 12px; }
        .list-btn { background: #121212; border: 1px solid #333; border-radius: 12px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: 0.2s; font-weight: 600; font-size: 0.95rem; }
        .list-btn:hover { border-color: #555; transform: translateY(-2px); }
        .icon-wrap { display: flex; align-items: center; gap: 10px; }
        .list-btn.active { border-color: #00e5ff; background: rgba(0, 229, 255, 0.05); }

        /* 하단 브랜드 바 */
        .brand-bar { background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 16px; padding: 20px 30px; display: flex; align-items: center; gap: 20px; margin-bottom: 40px; }
        .brand-bar-title { font-size: 0.85rem; color: #666; font-weight: 800; letter-spacing: 1px; }
        .brand-icons { display: flex; gap: 15px; }
        
        .b-icon { width: 45px; height: 45px; background: #121212; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: 800; font-size: 1rem; border: 1px solid #333; color: #ccc; transition: 0.3s; cursor: pointer; }
        .b-icon:hover { border-color: #00e5ff; color: #00e5ff; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0, 229, 255, 0.15); }
        .b-icon.active-brand { border-color: #00e5ff; color: #00e5ff; background: rgba(0, 229, 255, 0.1); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0, 229, 255, 0.15); }

        /* 차량 리스트 결과 영역 */
        .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 15px; }
        .section-header h2 { font-size: 1.3rem; margin: 0; display: flex; align-items: center; gap: 10px; }
        .sort-select { background: #1e1e24; color: #fff; border: 1px solid #333; padding: 8px 15px; border-radius: 8px; outline: none; cursor: pointer; transition: 0.2s; }
        .sort-select:hover { border-color: #555; }

        .car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 60px; }
        .car-card { background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 16px; padding: 25px; transition: 0.2s; display: flex; flex-direction: column; }
        .car-card:hover { transform: translateY(-3px); border-color: #555; box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        .c-brand { font-size: 0.8rem; color: #00e5ff; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .c-model { font-size: 1.4rem; font-weight: 800; margin-bottom: 5px; text-transform: capitalize; }
        .c-year { font-size: 0.85rem; color: #888; margin-bottom: 20px; }
        
        .c-specs { display: flex; gap: 10px; margin-bottom: 20px; margin-top: auto; }
        .c-spec-box { background: #121212; padding: 10px; border-radius: 8px; flex: 1; text-align: center; border: 1px solid #2a2a2f; }
        .c-spec-box span { display: block; font-size: 0.7rem; color: #666; margin-bottom: 3px; }
        .c-spec-box strong { font-size: 0.9rem; text-transform: capitalize; }

        .btn-detail { background: rgba(255,255,255,0.05); border: 1px solid #333; color: #ccc; text-align: center; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; cursor: pointer; display: block; }
        .btn-detail:hover { background: #fff; color: #000; border-color: #fff; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>카테고리 탐색</h1>
            <p>국가, 파워트레인, 차급별로 정리된 방대한 차량 데이터를 검색해보세요.</p>
        </div>
        <div class="db-badge">
            <span>●</span> Local DB Rows: <?= $totalRows ?>
        </div>
    </div>

    <!-- 카테고리 필터 영역 -->
    <div class="filter-grid">
        <!-- 1. 생산 국가별 (필터 이동 시 정렬 상태도 유지 $sortParam) -->
        <div class="filter-card">
            <div class="filter-title">🌍 생산 국가별 (Heritage & Country)</div>
            <div class="country-grid">
                <a href="?country=ALL&powertrain=<?= $currentPowertrain ?>&body_style=<?= $currentBodyStyle ?><?= $sortParam ?>" class="country-btn <?= $currentCountry == 'ALL' ? 'active' : '' ?>" style="grid-column: 1 / -1; display: flex; justify-content: center; align-items: baseline; gap: 8px; padding: 15px;">
                    <strong style="margin-bottom: 0;">ALL</strong><span>Global</span>
                </a>
                <a href="?country=DE&powertrain=<?= $currentPowertrain ?>&body_style=<?= $currentBodyStyle ?><?= $sortParam ?>" class="country-btn <?= $currentCountry == 'DE' ? 'active' : '' ?>">
                    <strong>DE</strong><span>Germany</span>
                </a>
                <a href="?country=KR&powertrain=<?= $currentPowertrain ?>&body_style=<?= $currentBodyStyle ?><?= $sortParam ?>" class="country-btn <?= $currentCountry == 'KR' ? 'active' : '' ?>">
                    <strong>KR</strong><span>Korea</span>
                </a>
                <a href="?country=US&powertrain=<?= $currentPowertrain ?>&body_style=<?= $currentBodyStyle ?><?= $sortParam ?>" class="country-btn <?= $currentCountry == 'US' ? 'active' : '' ?>">
                    <strong>US</strong><span>USA</span>
                </a>
                <a href="?country=JP&powertrain=<?= $currentPowertrain ?>&body_style=<?= $currentBodyStyle ?><?= $sortParam ?>" class="country-btn <?= $currentCountry == 'JP' ? 'active' : '' ?>">
                    <strong>JP</strong><span>Japan</span>
                </a>
            </div>
        </div>

        <!-- 2. 파워트레인 -->
        <div class="filter-card">
            <div class="filter-title">⚡ 파워트레인 (Powertrain)</div>
            <div class="list-btn-group">
                <a href="?country=<?= $currentCountry ?>&powertrain=all&body_style=<?= $currentBodyStyle ?><?= $brandParam ?><?= $sortParam ?>" class="list-btn <?= $currentPowertrain == 'all' ? 'active' : '' ?>">
                    <div class="icon-wrap"><span style="color:#ccc; font-size:1.2rem;">🔍</span> All Types</div>
                </a>
                <a href="?country=<?= $currentCountry ?>&powertrain=electric&body_style=<?= $currentBodyStyle ?><?= $brandParam ?><?= $sortParam ?>" class="list-btn <?= $currentPowertrain == 'electric' ? 'active' : '' ?>">
                    <div class="icon-wrap"><span style="color:#00e5ff; font-size:1.2rem;">⚡</span> Pure Electric</div>
                </a>
                <a href="?country=<?= $currentCountry ?>&powertrain=hybrid&body_style=<?= $currentBodyStyle ?><?= $brandParam ?><?= $sortParam ?>" class="list-btn <?= $currentPowertrain == 'hybrid' ? 'active' : '' ?>">
                    <div class="icon-wrap"><span style="color:#00ff88; font-size:1.2rem;">🌿</span> Hybrid</div>
                </a>
                <a href="?country=<?= $currentCountry ?>&powertrain=combustion&body_style=<?= $currentBodyStyle ?><?= $brandParam ?><?= $sortParam ?>" class="list-btn <?= $currentPowertrain == 'combustion' ? 'active' : '' ?>">
                    <div class="icon-wrap"><span style="color:#ff4b4b; font-size:1.2rem;">⛽</span> Combustion</div>
                </a>
            </div>
        </div>

        <!-- 3. 차급별 (Body Style) -->
        <div class="filter-card">
            <div class="filter-title">🚙 차급별 (Body Style)</div>
            <div class="list-btn-group">
                <a href="?country=<?= $currentCountry ?>&powertrain=<?= $currentPowertrain ?>&body_style=all<?= $brandParam ?><?= $sortParam ?>" class="list-btn <?= $currentBodyStyle == 'all' ? 'active' : '' ?>">
                    <span>All Styles</span> <span style="font-size:1.2rem;">🔍</span>
                </a>
                <a href="?country=<?= $currentCountry ?>&powertrain=<?= $currentPowertrain ?>&body_style=suv<?= $brandParam ?><?= $sortParam ?>" class="list-btn <?= $currentBodyStyle == 'suv' ? 'active' : '' ?>">
                    <span>SUV</span> <span style="font-size:1.2rem; filter: grayscale(1);">🚙</span>
                </a>
                <a href="?country=<?= $currentCountry ?>&powertrain=<?= $currentPowertrain ?>&body_style=sedan<?= $brandParam ?><?= $sortParam ?>" class="list-btn <?= $currentBodyStyle == 'sedan' ? 'active' : '' ?>">
                    <span>Sedan</span> <span style="font-size:1.2rem; filter: grayscale(1);">🚗</span>
                </a>
                <a href="?country=<?= $currentCountry ?>&powertrain=<?= $currentPowertrain ?>&body_style=sports<?= $brandParam ?><?= $sortParam ?>" class="list-btn <?= $currentBodyStyle == 'sports' ? 'active' : '' ?>">
                    <span>Sports</span> <span style="font-size:1.2rem; filter: grayscale(1);">🏎️</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 하단 브랜드 바 -->
    <div class="brand-bar">
        <div class="brand-bar-title">TOP BRANDS</div>
        <div class="brand-icons">
            <?php foreach ($displayBrands as $brand): ?>
                <a href="?country=<?= $currentCountry ?>&powertrain=<?= $currentPowertrain ?>&body_style=<?= $currentBodyStyle ?>&brand=<?= urlencode($brand) ?><?= $sortParam ?>" 
                   class="b-icon <?= $currentBrand === $brand ? 'active-brand' : '' ?>" 
                   title="<?= htmlspecialchars(ucfirst($brand)) ?>">
                    <?= strtoupper(substr($brand, 0, 1)) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 차량 리스트 영역 -->
    <div class="section-header">
        <h2><?= $sectionTitle ?> <span style="font-size: 0.8rem; color:#666; font-weight:normal;">총 <?= count($categoryCars) ?>건</span></h2>
        
        <!-- 👇 JavaScript로 URL 파라미터를 조작하여 즉시 정렬되도록 구현 -->
        <select class="sort-select" onchange="const urlParams = new URLSearchParams(window.location.search); urlParams.set('sort', this.value); window.location.search = urlParams.toString();">
            <option value="popular" <?= $currentSort === 'popular' ? 'selected' : '' ?>>인기순 정렬</option>
            <option value="newest" <?= $currentSort === 'newest' ? 'selected' : '' ?>>최신 연식순</option>
        </select>
    </div>

    <div class="car-grid">
        <?php if (!empty($categoryCars)): ?>
            <?php foreach ($categoryCars as $car): ?>
                <div class="car-card">
                    <div class="c-brand"><?= htmlspecialchars($car['make']) ?></div>
                    <div class="c-model"><?= htmlspecialchars($car['model']) ?></div>
                    <div class="c-year"><?= htmlspecialchars($car['year']) ?></div>
                    
                    <div class="c-specs">
                        <div class="c-spec-box">
                            <span>Fuel</span>
                            <strong><?= htmlspecialchars($car['fuel_type'] ?: 'N/A') ?></strong>
                        </div>
                        <div class="c-spec-box">
                            <span>Drive</span>
                            <strong><?= htmlspecialchars(strtoupper($car['drive'] ?: 'N/A')) ?></strong>
                        </div>
                    </div>
                    
                    <a href="detail.php?make=<?= urlencode($car['make']) ?>&model=<?= urlencode($car['model']) ?>&year=<?= urlencode($car['year']) ?>" class="btn-detail">제원 상세보기</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #666; background: #1e1e24; border-radius: 12px;">
                선택하신 필터 조건에 맞는 차량 데이터가 없습니다.<br>
                필터를 조정하거나 데이터를 추가해 주세요!
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
        // 새로고침이나 정렬 변경 시 스크롤 위치 유지
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });

        window.addEventListener('load', function() {
            const scrollPos = sessionStorage.getItem('scrollPosition');
            if (scrollPos !== null) {
                window.scrollTo(0, parseInt(scrollPos));
                sessionStorage.removeItem('scrollPosition'); 
            }
        });
</script>

</body>
</html>