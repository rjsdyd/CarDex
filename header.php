<?php
// 현재 페이지의 파일명 가져오기
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* 1. 마이페이지와 동일한 폰트(Pretendard) 강제 불러오기 */
    @import url('https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css');
    
    /* 2. [핵심] 스크롤바 밀림 완벽 방지! (모든 페이지에 우측 스크롤바 공간을 항상 열어둠) */
    html { overflow-y: scroll !important; }

    /* 3. 각 페이지의 기본 여백/줄간격 기준을 똑같이 덮어씌움 */
    body { 
        margin: 0 !important; 
        padding: 0 !important; 
        font-family: 'Pretendard', sans-serif !important;
        line-height: 1.6 !important;
    }

    /* 4. 헤더와 그 내부 요소들 철벽 방어 (여백, 줄간격 싹 다 초기화) */
    .common-header, .common-header * {
        box-sizing: border-box !important;
        margin: 0; 
        padding: 0;
    }

    /* 5. 공통 헤더 완벽 고정 스타일 */
    .common-header { 
        display: flex !important; 
        justify-content: space-between !important; 
        align-items: center !important; 
        padding: 0 40px !important; 
        height: 75px !important;    
        background-color: #1a1a1a !important; 
        border-bottom: 1px solid #333 !important; 
        width: 100% !important; 
    }
    
    /* 요소별 높낮이 흔들림 방지를 위해 line-height 고정 및 flex align 정렬 */
    .common-header .logo { font-size: 1.5rem !important; font-weight: 800 !important; color: #fff !important; text-decoration: none !important; display: flex !important; align-items: center !important; gap: 8px !important; line-height: 1 !important; }
    .common-header .nav-links { display: flex !important; gap: 20px !important; font-weight: 600 !important; font-size: 0.95rem !important; align-items: center !important; }
    .common-header .nav-links a { color: #888 !important; text-decoration: none !important; transition: 0.3s !important; line-height: 1 !important; }
    .common-header .nav-links a:hover, .common-header .nav-links a.active { color: #fff !important; }
    
    .common-header .nav-icons { display: flex !important; align-items: center !important; gap: 15px !important; }
    .common-header .user-avatar { background: #00e5ff !important; color: #000 !important; font-weight: bold !important; border-radius: 20px !important; padding: 5px 15px !important; font-size: 0.9rem !important; display: flex !important; align-items: center !important; justify-content: center !important; line-height: 1.2 !important; }
    .common-header .nav-icons a { line-height: 1 !important; }
</style>

<header class="common-header">
    <a href="index.php" class="logo">🚘 CarDex</a>
    <nav class="nav-links">
        <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">검색 (Search)</a>
        <a href="compare.php" class="<?= $current_page == 'compare.php' ? 'active' : '' ?>">비교함 (Compare)</a>
        <a href="mypage.php" class="<?= $current_page == 'mypage.php' ? 'active' : '' ?>">마이페이지 (My Page)</a>
    </nav>
    <div class="nav-icons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span style="cursor:pointer; font-size: 1.1rem;">🔔</span>
            <div class="user-avatar"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <a href="logout.php" style="font-size:0.8rem; color:#a0a0a0; font-weight:bold; text-decoration:none;">로그아웃</a>
        <?php else: ?>
            <a href="login.php" style="font-size:0.9rem; color:#fff; font-weight:bold; text-decoration:none;">로그인</a>
            <a href="register.php" style="background:#00e5ff; color:#000; padding: 6px 15px; border-radius: 20px; font-weight:bold; font-size:0.9rem; text-decoration:none;">회원가입</a>
        <?php endif; ?>
    </div>
</header>