<?php
// 1. DB 연결 (db.php를 불러오면 $pdo 객체를 쓸 수 있음)
require_once 'db.php';

try {
    // 2. cars 테이블에서 모든 데이터를 가져오는 SQL 쿼리 작성 (최신순으로 정렬)
    $stmt = $pdo->query("SELECT * FROM cars ORDER BY created_at DESC");
    
    // 3. 쿼리 실행 결과를 배열 형태로 모두 가져오기
    $cars = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("데이터를 불러오는 중 에러가 발생했습니다: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarDex - 자동차 백과사전</title>
    <!-- 구글 폰트 (Noto Sans KR) 추가 -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        /* 기본 CSS 스타일링 */
        body { font-family: 'Noto Sans KR', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { font-size: 2.5rem; color: #2c3e50; margin-bottom: 10px; }
        
        /* 카드 그리드 레이아웃 */
        .car-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; }
        
        /* 개별 자동차 카드 스타일 */
        .car-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .car-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.15); }
        .car-make { font-size: 0.9rem; color: #7f8c8d; text-transform: uppercase; font-weight: bold; }
        .car-model { font-size: 1.5rem; color: #2c3e50; margin: 5px 0 15px 0; text-transform: capitalize; }
        
        /* 세부 정보 뱃지 스타일 */
        .info-group { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
        .badge { background: #e8f4f8; color: #2980b9; padding: 5px 10px; border-radius: 15px; font-size: 0.85rem; font-weight: bold; }
        
        /* 연비 정보 스타일 */
        .mpg-info { background: #fdfbf7; border: 1px solid #e1d8c1; padding: 10px; border-radius: 5px; font-size: 0.9rem; }
        .mpg-info strong { color: #d35400; }
        
        /* 데이터가 없을 때 */
        .no-data { text-align: center; grid-column: 1 / -1; font-size: 1.2rem; color: #7f8c8d; }
    </style>
</head>
<body>

    <div class="header">
        <h1>🚗 CarDex</h1>
        <p>내 손 안의 자동차 백과사전</p>
    </div>

    <div class="car-grid">
        <?php if (!empty($cars)): ?>
            <!-- DB에서 가져온 $cars 배열을 하나씩 꺼내서 카드(HTML)로 만들기 -->
            <?php foreach ($cars as $car): ?>
                <div class="car-card">
                    <div class="car-make"><?= htmlspecialchars($car['make']) ?></div>
                    <h2 class="car-model"><?= htmlspecialchars($car['model']) ?> (<?= htmlspecialchars($car['year']) ?>)</h2>
                    
                    <div class="info-group">
                        <span class="badge">분류: <?= htmlspecialchars($car['vehicle_class']) ?></span>
                        <span class="badge">구동: <?= htmlspecialchars(strtoupper($car['drive'])) ?></span>
                        <span class="badge">연료: <?= htmlspecialchars(ucfirst($car['fuel_type'])) ?></span>
                    </div>

                    <div class="mpg-info">
                        <div>도심 연비: <strong><?= htmlspecialchars($car['city_mpg']) ?></strong> mpg</div>
                        <div>고속 연비: <strong><?= htmlspecialchars($car['highway_mpg']) ?></strong> mpg</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <p>아직 수집된 자동차 데이터가 없습니다.<br>터미널에서 <code>php seed.php</code>를 실행하여 데이터를 수집해주세요!</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>