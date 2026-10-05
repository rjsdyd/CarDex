<?php
session_start();
require_once 'db.php';

// 1. 로그인 체크
if (!isset($_SESSION['user_id'])) {
    die("<script>alert('로그인이 필요한 서비스입니다.'); location.href='login.php';</script>");
}
$userId = $_SESSION['user_id'];

// 2. 삭제 처리 로직 (선택 삭제 & 개별 삭제)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 다중(선택) 삭제
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && !empty($_POST['car_ids'])) {
        $carIds = $_POST['car_ids'];
        $placeholders = implode(',', array_fill(0, count($carIds), '?'));
        $params = array_merge([$userId], $carIds);
        
        $delStmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND car_id IN ($placeholders)");
        $delStmt->execute($params);
        
        echo "<script>location.replace('mypage.php');</script>";
        exit;
    }
    // 단일(개별) 삭제
    if (isset($_POST['action']) && $_POST['action'] === 'single_delete' && !empty($_POST['car_id'])) {
        $delStmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND car_id = ?");
        $delStmt->execute([$userId, $_POST['car_id']]);
        
        echo "<script>location.replace('mypage.php');</script>";
        exit;
    }
}

// 3. 내 차고 통계 데이터 가져오기
$stats = [
    'total' => 0,
    'top_brand' => '-',
    'top_fuel' => '-',
    'top_fuel_percent' => 0
];

// 총 저장 대수
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$countStmt->execute([$userId]);
$stats['total'] = $countStmt->fetchColumn();

if ($stats['total'] > 0) {
    // 가장 선호하는 브랜드
    $brandStmt = $pdo->prepare("SELECT c.make, COUNT(*) as cnt FROM wishlist w JOIN cars c ON w.car_id = c.car_id WHERE w.user_id = ? GROUP BY c.make ORDER BY cnt DESC LIMIT 1");
    $brandStmt->execute([$userId]);
    $brandRes = $brandStmt->fetch();
    if ($brandRes) $stats['top_brand'] = ucfirst($brandRes['make']);

    // 관심 연료 타입
    $fuelStmt = $pdo->prepare("SELECT c.fuel_type, COUNT(*) as cnt FROM wishlist w JOIN cars c ON w.car_id = c.car_id WHERE w.user_id = ? GROUP BY c.fuel_type ORDER BY cnt DESC LIMIT 1");
    $fuelStmt->execute([$userId]);
    $fuelRes = $fuelStmt->fetch();
    if ($fuelRes) {
        $stats['top_fuel'] = ucfirst($fuelRes['fuel_type'] ?: 'Unknown');
        $stats['top_fuel_percent'] = round(($fuelRes['cnt'] / $stats['total']) * 100);
    }
}

// 👇 4. 정렬 방식 파라미터 받기 및 SQL 조건 동적 변경
$currentSort = $_GET['sort'] ?? 'recent';

$orderBySql = "ORDER BY w.created_at DESC"; // 기본값: 최근 저장순
if ($currentSort === 'newest') {
    $orderBySql = "ORDER BY c.year DESC, w.created_at DESC"; // 최신 연식순
}

// 저장된 차량 목록 가져오기 (적용된 정렬 기준 사용)
$listStmt = $pdo->prepare("SELECT c.*, w.created_at as saved_at FROM wishlist w JOIN cars c ON w.car_id = c.car_id WHERE w.user_id = ? $orderBySql");
$listStmt->execute([$userId]);
$savedCars = $listStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CarDex - 마이페이지</title>
    <style>
        body { background-color: #121212; color: #fff; font-family: 'Noto Sans KR', sans-serif; margin: 0; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        .page-title { margin-bottom: 40px; }
        .page-title h1 { font-size: 2rem; font-weight: 800; margin: 0 0 10px 0; display: flex; align-items: center; gap: 10px; }
        .page-title p { color: #a0a0a0; margin: 0; font-size: 0.95rem; }

        /* 통계 위젯 영역 */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 12px; padding: 25px; display: flex; flex-direction: column; justify-content: center; }
        .stat-card span { font-size: 0.8rem; color: #888; margin-bottom: 10px; }
        .stat-card strong { font-size: 1.8rem; font-weight: 800; }
        .stat-card strong.cyan { color: #00e5ff; }
        .stat-card small { font-size: 1rem; color: #666; font-weight: normal; margin-left: 5px; }
        
        .link-card { background: rgba(0, 229, 255, 0.05); border: 1px solid rgba(0, 229, 255, 0.2); cursor: pointer; transition: 0.2s; align-items: center; text-align: center; }
        .link-card:hover { background: rgba(0, 229, 255, 0.1); transform: translateY(-3px); }
        .link-card strong { font-size: 1.2rem; color: #00e5ff; }

        /* 컨트롤 툴바 */
        .toolbar { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding-bottom: 15px; margin-bottom: 25px; }
        .toolbar-left { display: flex; align-items: center; gap: 15px; }
        .checkbox-wrap { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #ccc; cursor: pointer; }
        .checkbox-wrap input { width: 16px; height: 16px; cursor: pointer; accent-color: #00e5ff; }
        
        .btn-delete-selected { background: #ff4b4b; color: #fff; border: none; padding: 6px 15px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; cursor: pointer; display: none; transition: 0.2s; }
        .btn-delete-selected:hover { background: #ff3333; }

        .sort-select { background: #1e1e24; color: #fff; border: 1px solid #333; padding: 6px 12px; border-radius: 6px; outline: none; cursor: pointer; font-size: 0.85rem; }

        /* 차량 카드 그리드 */
        .car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 60px; }
        .car-card { background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 16px; padding: 25px; position: relative; transition: 0.2s; text-align: center; display: flex; flex-direction: column; }
        .car-card:hover { border-color: #555; }
        
        .card-top { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .card-top input { width: 16px; height: 16px; cursor: pointer; accent-color: #00e5ff; }
        .btn-trash { background: none; border: none; color: #666; cursor: pointer; font-size: 1.1rem; transition: 0.2s; padding: 0; }
        .btn-trash:hover { color: #ff4b4b; }

        .car-icon-circle { width: 60px; height: 60px; background: #121212; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 15px auto; border: 1px solid #333; font-size: 1.5rem; }
        
        .c-year { font-size: 0.75rem; background: rgba(255,255,255,0.05); padding: 3px 8px; border-radius: 10px; color: #ccc; display: inline-block; margin-bottom: 10px; border: 1px solid #333; }
        .c-brand { font-size: 0.85rem; color: #00e5ff; font-weight: 800; text-transform: uppercase; margin-bottom: 5px; }
        .c-model { font-size: 1.4rem; font-weight: 800; margin-bottom: 20px; text-transform: capitalize; }
        
        .tags { display: flex; justify-content: center; gap: 8px; margin-bottom: 25px; }
        .tag { background: #121212; border: 1px solid #333; padding: 5px 12px; border-radius: 12px; font-size: 0.75rem; color: #888; text-transform: capitalize; }
        
        .c-class { font-size: 0.8rem; color: #666; margin-top: auto; border-top: 1px solid #333; padding-top: 15px; }
        .c-class span { color: #dda0dd; margin-right: 5px; }
        
        .empty-state { grid-column: 1/-1; text-align: center; padding: 80px 20px; background: #1e1e24; border-radius: 16px; border: 1px dashed #333; color: #888; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="page-title">
        <h1>내 차고 🚘</h1>
        <p>관심 있는 차량을 모아보고, 통계를 확인하세요.</p>
    </div>

    <!-- 통계 위젯 -->
    <div class="stats-grid">
        <div class="stat-card">
            <span>저장된 차량</span>
            <strong><?= $stats['total'] ?> 대</strong>
        </div>
        <div class="stat-card">
            <span>가장 선호하는 브랜드</span>
            <strong class="cyan"><?= htmlspecialchars($stats['top_brand']) ?></strong>
        </div>
        <div class="stat-card">
            <span>관심 연료 타입</span>
            <strong><?= htmlspecialchars($stats['top_fuel']) ?> <?= $stats['total'] > 0 ? "<small>({$stats['top_fuel_percent']}%)</small>" : '' ?></strong>
        </div>
        <a href="compare.php" class="stat-card link-card">
            <strong>비교함으로 이동 🔄</strong>
        </a>
    </div>

    <!-- 폼 시작 -->
    <form id="garageForm" method="POST" action="mypage.php">
        <input type="hidden" name="action" id="formAction" value="">
        <input type="hidden" name="car_id" id="singleDeleteId" value="">

        <div class="toolbar">
            <div class="toolbar-left">
                <label class="checkbox-wrap">
                    <input type="checkbox" id="checkAll"> 전체 선택
                </label>
                <button type="button" id="btnDeleteSelected" class="btn-delete-selected" onclick="submitBulkDelete()">선택 삭제</button>
            </div>
            
            <!-- 👇 정렬 기능 추가 (자바스크립트로 URL 즉시 변경) -->
            <select class="sort-select" onchange="const urlParams = new URLSearchParams(window.location.search); urlParams.set('sort', this.value); window.location.search = urlParams.toString();">
                <option value="recent" <?= $currentSort === 'recent' ? 'selected' : '' ?>>최근 저장순</option>
                <option value="newest" <?= $currentSort === 'newest' ? 'selected' : '' ?>>최신 연식순</option>
            </select>
        </div>

        <div class="car-grid">
            <?php if (!empty($savedCars)): ?>
                <?php foreach ($savedCars as $car): 
                    $cid = $car['car_id'];
                    $isEv = (strtolower($car['fuel_type']) === 'electricity' || strpos(strtolower($car['fuel_type']), 'electric') !== false);
                ?>
                    <div class="car-card">
                        <div class="card-top">
                            <input type="checkbox" name="car_ids[]" value="<?= $cid ?>" class="car-checkbox">
                            <button type="button" class="btn-trash" onclick="submitSingleDelete(<?= $cid ?>)">🗑️</button>
                        </div>
                        
                        <div class="car-icon-circle">
                            <?= $isEv ? '⚡' : '🚙' ?>
                        </div>
                        
                        <div>
                            <div class="c-year"><?= htmlspecialchars($car['year']) ?></div>
                            <div class="c-brand"><?= htmlspecialchars($car['make']) ?></div>
                            <div class="c-model">
                                <a href="detail.php?make=<?= urlencode($car['make']) ?>&model=<?= urlencode($car['model']) ?>&year=<?= urlencode($car['year']) ?>">
                                    <?= htmlspecialchars($car['model']) ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="tags">
                            <div class="tag"><?= htmlspecialchars($car['fuel_type'] ?: 'N/A') ?></div>
                            <div class="tag"><?= htmlspecialchars($car['combination_mpg'] ?: '0') ?> Mpg</div>
                            <div class="tag"><?= htmlspecialchars(strtoupper($car['drive'] ?: 'N/A')) ?></div>
                        </div>
                        
                        <div class="c-class">
                            <span>🏷️</span> <?= htmlspecialchars($car['vehicle_class'] ?: 'Unknown Class') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 15px;">텅</div>
                    차고가 비어있습니다. 메인 화면에서 마음에 드는 차량을 추가해 보세요!
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- 체크박스 및 삭제 처리 스크립트 -->
<script>
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.car-checkbox');
    const btnDeleteSelected = document.getElementById('btnDeleteSelected');
    const form = document.getElementById('garageForm');
    const formAction = document.getElementById('formAction');
    const singleDeleteId = document.getElementById('singleDeleteId');

    function updateUI() {
        if(checkboxes.length === 0) return;
        const checkedCount = document.querySelectorAll('.car-checkbox:checked').length;
        
        checkAll.checked = (checkedCount === checkboxes.length);
        btnDeleteSelected.style.display = checkedCount > 0 ? 'inline-block' : 'none';
    }

    if(checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateUI();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateUI);
    });

    function submitBulkDelete() {
        if(confirm('선택한 차량을 차고에서 삭제하시겠습니까?')) {
            formAction.value = 'bulk_delete';
            form.submit();
        }
    }

    function submitSingleDelete(id) {
        if(confirm('이 차량을 차고에서 삭제하시겠습니까?')) {
            formAction.value = 'single_delete';
            singleDeleteId.value = id;
            form.submit();
        }
    }
</script>

</body>
</html>