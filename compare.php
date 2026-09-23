<?php
require_once 'db.php';
// API 키 설정 (본인의 API 키로 변경 필요!)
$apiKey = getenv('API_NINJAS_KEY') ?: $_ENV['API_NINJAS_KEY'] ?? '';

// 초기 상태는 아무 차도 선택되지 않은 상태
$car1 = null;
$car2 = null;

// TODO: 나중에 여기에 API로 두 차량 데이터를 검색해서 $car1, $car2에 채워 넣는 로직이 들어갈 예정!
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarDex - 1:1 비교함</title>
    <!-- 기존 index.php, detail.php에서 쓰던 CSS 재활용 -->
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* 기본 리셋 및 다크 모드 (detail.php와 동일) */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Pretendard', sans-serif; }
        body { background-color: #121212; color: #fff; line-height: 1.6; }
        a { text-decoration: none; color: inherit; }
        
        /* 상단 네비게이션 (공통) */
        header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 40px; background-color: #1a1a1a;
            border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 100;
        }
        .logo { font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 10px; color: #fff; }
        .nav-links { display: flex; gap: 20px; font-weight: 600; font-size: 0.95rem; }
        .nav-links a { color: #888; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { color: #fff; }

        /* 메인 컨테이너 */
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        .page-title {
            text-align: center; margin-bottom: 40px;
        }
        .page-title h1 {
            font-size: 2.5rem; font-weight: 800; margin-bottom: 10px;
        }
        .page-title p {
            color: #888; font-size: 1.1rem;
        }

        /* 1:1 비교 레이아웃 (Grid 활용) */
        .compare-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* 정확히 반반(50% 50%) 나누기 */
            gap: 30px;
        }

        /* 각 차의 구역 박스 */
        .car-slot {
            background-color: #1e1e24;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border: 1px dashed #333;
        }

        /* 검색창 (차량 고르기) */
        .search-box { margin-bottom: 20px; }
        .search-box input {
            width: 80%; padding: 12px 20px; border-radius: 20px;
            border: 1px solid #444; background: #2a2a30; color: #fff;
            font-size: 1rem; margin-bottom: 10px;
        }
        .search-box button {
            padding: 12px 24px; border-radius: 20px; border: none;
            background: var(--primary-cyan, #00f2fe); color: #000;
            font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        .search-box button:hover { opacity: 0.8; }
        
        .empty-state { color: #666; font-size: 1.2rem; }
    </style>
</head>
<body>

<!-- 상단 헤더 -->
<header>
    <a href="index.php" class="logo">🚘 CarDex</a>
    <nav class="nav-links">
        <a href="index.php">검색 (Search)</a>
        <a href="compare.php" class="active">비교함 (Compare)</a>
        <a href="#">마이페이지 (My Page)</a>
    </nav>
</header>

<div class="container">
    <div class="page-title">
        <h1>자동차 1:1 비교</h1>
        <p>두 대의 차량을 선택하고 스펙을 한눈에 비교해 보세요.</p>
    </div>

    <!-- 나란히 비교하는 그리드 영역 -->
    <div class="compare-grid">
        
        <!-- 왼쪽 차 (Car 1) -->
        <div class="car-slot">
            <div class="search-box">
                <form action="" method="GET">
                    <input type="text" name="search1" placeholder="차량 1 검색 (예: camry)">
                    <!-- 차 2의 검색 상태도 잃어버리지 않게 숨겨서 같이 보냄 -->
                    <input type="hidden" name="search2" value="<?= htmlspecialchars($_GET['search2'] ?? '') ?>">
                    <br><button type="submit">검색하여 등록</button>
                </form>
            </div>
            
            <?php if ($car1): ?>
                <!-- 차 1 데이터가 있을 때 보여줄 화면 (내일 추가) -->
                <h2><?= strtoupper($car1['make'] . ' ' . $car1['model']) ?></h2>
            <?php else: ?>
                <!-- 차 1 데이터가 없을 때 -->
                <div class="empty-state">
                    왼쪽 차량을<br>검색해 주세요.
                </div>
            <?php endif; ?>
        </div>

        <!-- 오른쪽 차 (Car 2) -->
        <div class="car-slot">
            <div class="search-box">
                <form action="" method="GET">
                    <input type="text" name="search2" placeholder="차량 2 검색 (예: sonata)">
                    <!-- 차 1의 검색 상태도 잃어버리지 않게 숨겨서 같이 보냄 -->
                    <input type="hidden" name="search1" value="<?= htmlspecialchars($_GET['search1'] ?? '') ?>">
                    <br><button type="submit">검색하여 등록</button>
                </form>
            </div>

            <?php if ($car2): ?>
                <!-- 차 2 데이터가 있을 때 보여줄 화면 (내일 추가) -->
                <h2><?= strtoupper($car2['make'] . ' ' . $car2['model']) ?></h2>
            <?php else: ?>
                <!-- 차 2 데이터가 없을 때 -->
                <div class="empty-state">
                    오른쪽 차량을<br>검색해 주세요.
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

</body>
</html>