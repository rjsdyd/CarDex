<?php
session_start();
require_once 'db.php';

// 로그인 안 한 유저는 로그인 페이지로 튕겨내기!
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인이 필요한 페이지입니다.'); window.location.href='login.php';</script>";
    exit;
}

// 1. 내 찜 목록 가져오기 (JOIN을 써서 wishlist와 cars 테이블 합치기)
$stmt = $pdo->prepare("
    SELECT c.*, w.created_at as saved_at 
    FROM wishlist w 
    JOIN cars c ON w.car_id = c.car_id 
    WHERE w.user_id = :user_id 
    ORDER BY w.created_at DESC
");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$myCars = $stmt->fetchAll();

// 2. 통계 계산 로직 (가장 선호하는 브랜드, 연료 타입 추출)
$totalSaved = count($myCars);
$brandCounts = [];
$fuelCounts = [];

foreach ($myCars as $car) {
    $make = $car['make'] ?: 'Unknown';
    $fuel = $car['fuel_type'] ?: 'Unknown';
    
    $brandCounts[$make] = ($brandCounts[$make] ?? 0) + 1;
    $fuelCounts[$fuel] = ($fuelCounts[$fuel] ?? 0) + 1;
}

// 배열을 내림차순(가장 많은 것부터) 정렬
arsort($brandCounts);
arsort($fuelCounts);

$topBrand = $totalSaved > 0 ? array_key_first($brandCounts) : '-';
$topFuel = $totalSaved > 0 ? array_key_first($fuelCounts) : '-';
$topFuelPercent = $totalSaved > 0 ? round(($fuelCounts[$topFuel] / $totalSaved) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CarDex - 마이페이지</title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Pretendard', sans-serif; }
        body { background-color: #121212; color: #fff; line-height: 1.6; }
        a { text-decoration: none; color: inherit; }
        
        /* 공통 헤더 */
        header { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; background-color: #1a1a1a; border-bottom: 1px solid #333; }
        .logo { font-size: 1.5rem; font-weight: 800; color: #fff; }
        .nav-links { display: flex; gap: 20px; font-weight: 600; font-size: 0.95rem; }
        .nav-links a { color: #888; transition: 0.3s; }
        .nav-links a.active, .nav-links a:hover { color: #fff; }
        
        .nav-icons { display: flex; align-items: center; gap: 15px; }
        .user-avatar { background: #00e5ff; color: #000; font-weight: bold; border-radius: 20px; padding: 5px 15px; font-size: 0.9rem; }

        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        .page-title { margin-bottom: 30px; }
        .page-title h1 { font-size: 2rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .page-title p { color: #a0a0a0; font-size: 0.95rem; margin-top: 5px; }

        /* 대시보드 통계 카드 */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #1e1e24; border-radius: 12px; padding: 25px; border: 1px solid #2a2a2f; }
        .stat-card small { display: block; color: #a0a0a0; font-size: 0.85rem; margin-bottom: 8px; font-weight: 600; }
        .stat-card strong { font-size: 1.8rem; font-weight: 800; color: #fff; text-transform: capitalize; }
        .text-cyan { color: #00e5ff !important; }
        
        .stat-card.action-card { background: rgba(0, 229, 255, 0.05); border-color: rgba(0, 229, 255, 0.2); cursor: pointer; transition: 0.2s; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
        .stat-card.action-card:hover { background: rgba(0, 229, 255, 0.1); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,229,255,0.1); }

        /* 컨트롤 영역 */
        .controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 15px; }
        .controls label { display: flex; align-items: center; gap: 8px; color: #a0a0a0; cursor: pointer; }
        .controls select { background: #1e1e24; color: #fff; border: 1px solid #333; padding: 8px 15px; border-radius: 8px; outline: none; font-family: inherit; }

        /* 내 차고(Bento) 그리드 */
        .garage-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
        .car-card { background: #1e1e24; border-radius: 16px; border: 1px solid #2a2a2f; padding: 20px; display: flex; flex-direction: column; position: relative; transition: 0.2s; }
        .car-card:hover { border-color: #555; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        
        .card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .card-top input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #00e5ff; }
        .btn-delete { background: none; border: none; color: #666; cursor: pointer; font-size: 1.2rem; transition: 0.2s; }
        .btn-delete:hover { color: #ff4b4b; }

        .car-icon-wrap { text-align: center; margin-bottom: 20px; }
        .car-icon { width: 80px; height: 80px; background: #121212; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; font-size: 2rem; border: 2px solid #2a2a2f; margin: 0 auto; }
        
        .car-info { text-align: center; margin-bottom: 20px; flex-grow: 1; }
        .car-year { font-size: 0.8rem; background: #333; padding: 2px 8px; border-radius: 4px; color: #ccc; display: inline-block; margin-bottom: 5px; }
        .car-brand { font-size: 0.85rem; color: #00e5ff; text-transform: uppercase; font-weight: 700; margin-bottom: 2px; }
        .car-model { font-size: 1.4rem; font-weight: 800; text-transform: capitalize; }

        .car-badges { display: flex; justify-content: center; gap: 5px; margin-bottom: 15px; flex-wrap: wrap; }
        .badge { background: #121212; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; color: #a0a0a0; border: 1px solid #333; text-transform: capitalize; }
        .badge.electric { border-color: rgba(0, 229, 255, 0.4); color: #00e5ff; }

        .memo-area { border-top: 1px solid #2a2a2f; padding-top: 15px; font-size: 0.85rem; color: #888; display: flex; align-items: center; justify-content: center; gap: 8px; text-transform: capitalize; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="page-title">
        <h1>내 차고 🚘</h1>
        <p>관심 있는 차량을 모아보고, 통계를 확인하세요.</p>
    </div>

    <!-- 대시보드 통계 영역 -->
    <div class="stats-grid">
        <div class="stat-card">
            <small>저장된 차량</small>
            <strong><?= $totalSaved ?> 대</strong>
        </div>
        <div class="stat-card">
            <small>가장 선호하는 브랜드</small>
            <strong class="text-cyan"><?= htmlspecialchars($topBrand) ?></strong>
        </div>
        <div class="stat-card">
            <small>관심 연료 타입</small>
            <strong><?= htmlspecialchars($topFuel) ?> <span style="font-size:1rem; color:#888;">(<?= $topFuelPercent ?>%)</span></strong>
        </div>
        <div class="stat-card action-card" onclick="goToCompare()">
            <strong class="text-cyan" style="font-size: 1.3rem;">비교함으로 이동 🔄</strong>
        </div>
    </div>

    <!-- 리스트 컨트롤 영역 -->
    <div class="controls">
        <!-- 👇 여기 input 태그에 id="selectAll" 을 추가했어! -->
        <label><input type="checkbox" id="selectAll"> 전체 선택</label>
        <select>
            <option>최근 저장순</option>
            <option>브랜드순</option>
            <option>연식순</option>
        </select>
    </div>

    <!-- 내 차고 차량 리스트 -->
    <div class="garage-grid">
        <?php if ($totalSaved > 0): ?>
            <?php foreach ($myCars as $car): ?>
                <!-- 삭제 시 화면에서 지우기 위해 ID 부여 -->
                <div class="car-card" id="card-<?= $car['car_id'] ?>">
                    <div class="card-top">
                        <input type="checkbox" name="compare_select" value="<?= $car['car_id'] ?>" data-search="<?= htmlspecialchars($car['model']) ?>">
                        <!-- 휴지통 버튼 -->
                        <button class="btn-delete" onclick="removeWish(<?= $car['car_id'] ?>)" title="차고에서 삭제">🗑️</button>
                    </div>
                    
                    <div class="car-icon-wrap">
                        <div class="car-icon">
                            <?= strtolower($car['fuel_type']) === 'electricity' ? '⚡' : '🚗' ?>
                        </div>
                    </div>

                    <div class="car-info">
                        <div class="car-year"><?= htmlspecialchars($car['year']) ?></div>
                        <div class="car-brand"><?= htmlspecialchars($car['make']) ?></div>
                        <div class="car-model"><?= htmlspecialchars($car['model']) ?></div>
                    </div>

                    <div class="car-badges">
                        <span class="badge <?= strtolower($car['fuel_type']) === 'electricity' ? 'electric' : '' ?>">
                            <?= htmlspecialchars($car['fuel_type']) ?>
                        </span>
                        <span class="badge"><?= htmlspecialchars($car['city_mpg']) ?> mpg</span>
                        <span class="badge"><?= htmlspecialchars(strtoupper($car['drive'])) ?></span>
                    </div>

                    <div class="memo-area">
                        <span>🏷️</span> <?= htmlspecialchars($car['vehicle_class'] ?: 'Standard') ?> Class
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666; background: #1e1e24; border-radius: 12px; border: 1px dashed #333;">
                <div style="font-size: 3rem; margin-bottom: 15px;">텅~</div>
                아직 차고에 저장된 차량이 없습니다.<br>검색을 통해 관심 있는 차량을 하트로 찜해보세요!
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// 휴지통 버튼 누르면 toggle_wishlist.php를 호출해서 삭제하는 로직
function removeWish(carId) {
    if(confirm('이 차량을 차고에서 삭제하시겠습니까?')) {
        fetch('toggle_wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ car_id: carId })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success' && data.action === 'removed') {
                // 상단 통계를 다시 계산해야 하므로 페이지를 새로고침하는 게 깔끔함!
                location.reload(); 
            } else if(data.status === 'error') {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('삭제 중 오류가 발생했습니다.');
        });
    }
}
// 👇 전체 선택/해제 기능
document.getElementById('selectAll').addEventListener('change', function() {
    // 모든 개별 차량 체크박스를 찾아서
    const checkboxes = document.querySelectorAll('input[name="compare_select"]');
    // 전체 선택 버튼의 체크 상태(true/false)와 똑같이 맞춰줌!
    checkboxes.forEach(cb => {
        cb.checked = this.checked;
    });
});
// 👇 선택된 차량 비교함으로 넘기기
function goToCompare() {
    // 체크된 체크박스들만 싹 다 모아오기
    const selected = document.querySelectorAll('input[name="compare_select"]:checked');
    
    // 2대가 아니면 빠꾸!
    if (selected.length !== 2) {
        alert('1:1 비교를 위해 차량을 정확히 2대 선택해 주세요! (현재 ' + selected.length + '대 선택됨)');
        return;
    }
    
    // 정확히 2대라면 각각에 숨겨둔 검색어(모델명) 꺼내기
    const car1 = selected[0].getAttribute('data-search');
    const car2 = selected[1].getAttribute('data-search');
    
    // 비교 페이지로 검색어 달아서 날려버리기!
    window.location.href = 'compare.php?search1=' + encodeURIComponent(car1) + '&search2=' + encodeURIComponent(car2);
}
</script>

</body>
</html>