<?php
session_start();
require_once 'db.php';

// DB에서 모든 브랜드 이름과 해당 브랜드의 차량 개수 가져오기 (알파벳순 정렬)
$stmt = $pdo->query("SELECT make, COUNT(*) as cnt FROM cars GROUP BY make ORDER BY make ASC");
$brands = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <!-- 💡 [추가됨] 모바일 기기 필수 뷰포트 태그 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CarDex - 전체 브랜드 목록</title>
    <style>
        /* 💡 [추가됨] 모든 요소 화면 이탈 방지 */
        * { box-sizing: border-box; }
        
        /* 💡 [추가됨] 최소 너비 320px 고정 및 스크롤 덜렁거림 방지 */
        body { background-color: #121212; color: #fff; margin: 0; padding: 0; min-width: 320px; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; width: 100%; }
        
        .page-title { margin-bottom: 40px; }
        .page-title h1 { font-size: 2.2rem; font-weight: 800; color: #00e5ff; margin-bottom: 5px; }
        .page-title p { color: #a0a0a0; font-size: 0.95rem; margin: 0; }

        /* 브랜드 카드 그리드 (반응형 벤토 스타일) */
        .brands-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-bottom: 60px; }
        .brand-card { background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 16px; padding: 30px 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px; cursor: pointer; transition: 0.3s; }
        .brand-card:hover { transform: translateY(-5px); border-color: #00e5ff; box-shadow: 0 10px 20px rgba(0, 229, 255, 0.1); background: rgba(0, 229, 255, 0.05); }
        
        .b-icon { width: 70px; height: 70px; background: #121212; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 1.8rem; font-weight: 800; color: #fff; border: 2px solid #333; transition: 0.3s; }
        .brand-card:hover .b-icon { border-color: #00e5ff; color: #00e5ff; transform: scale(1.05); }
        
        .b-info { text-align: center; }
        .b-name { font-size: 1.2rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; display: block; }
        .b-count { font-size: 0.8rem; color: #888; background: #121212; padding: 4px 10px; border-radius: 12px; border: 1px solid #333; }

        /* 💡 [추가됨] 모바일(768px 이하) 전용 반응형 CSS */
        @media (max-width: 768px) {
            .container { margin: 20px auto; }
            .page-title { margin-bottom: 25px; }
            .page-title h1 { font-size: 1.8rem; }
            
            /* 모바일에서는 스크롤이 너무 길어지지 않게 2열 바둑판 배열로 꽉 채움 */
            .brands-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 40px; }
            
            /* 모바일 화면에 맞춰 카드 안쪽 여백과 아이콘 크기 축소 */
            .brand-card { padding: 20px 10px; gap: 10px; }
            .b-icon { width: 50px; height: 50px; font-size: 1.3rem; }
            .b-name { font-size: 1rem; margin-bottom: 5px; }
            .b-count { font-size: 0.7rem; padding: 3px 8px; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="page-title">
        <h1>전체 브랜드 탐색 🚗</h1>
        <p>CarDex 데이터베이스에 등록된 모든 자동차 브랜드를 한눈에 확인하세요.</p>
    </div>

    <div class="brands-grid">
        <?php foreach ($brands as $b): 
            $make = htmlspecialchars($b['make']);
            $firstLetter = strtoupper(substr($make, 0, 1));
        ?>
            <!-- 클릭하면 메인 화면(index.php)으로 해당 브랜드 검색어를 들고 넘어감 -->
            <a href="index.php?q=<?= urlencode($make) ?>" class="brand-card">
                <div class="b-icon"><?= $firstLetter ?></div>
                <div class="b-info">
                    <span class="b-name"><?= $make ?></span>
                    <span class="b-count">보유 데이터: <?= $b['cnt'] ?>대</span>
                </div>
            </a>
        <?php endforeach; ?>
        
        <?php if (empty($brands)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666; background: #1e1e24; border-radius: 16px; border: 1px dashed #333;">
                <div style="font-size: 3rem; margin-bottom: 15px;">텅~</div>
                현재 DB에 등록된 브랜드가 없습니다.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>