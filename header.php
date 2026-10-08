<?php
// 현재 페이지의 파일명 가져오기
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* 1. 마이페이지와 동일한 폰트(Pretendard) 강제 불러오기 */
    @import url('https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css');
    
    /* 2. 스크롤바 밀림 방지 */
    html { overflow-y: scroll !important; }

    /* 3. 💡 [수정됨] 바디 기본 여백/줄간격 리셋 & fixed 헤더 높이만큼 본문 내리기 */
    body { 
        margin: 0 !important; 
        padding: 0 !important; 
        padding-top: 75px !important; /* 💡 헤더가 본문을 가리지 않게 75px 여백 확보 */
        font-family: 'Pretendard', sans-serif !important;
        line-height: 1.6 !important;
        min-width: 320px !important;
        overflow-x: hidden !important; 
    }

    /* 4. 헤더 철벽 방어 */
    .common-header, .common-header * {
        box-sizing: border-box !important;
        margin: 0; 
        padding: 0;
    }

    /* 5. 💡 [수정됨] 헤더 완벽 고정 (Sticky -> Fixed로 변경하여 overflow 충돌 해결) */
    .common-header { 
        display: flex !important; 
        justify-content: space-between !important; 
        align-items: center !important; 
        padding: 0 40px !important; 
        height: 75px !important;    
        border-bottom: 1px solid #333 !important; 
        
        position: fixed !important; /* 💡 화면에 완전히 고정 */
        top: 0 !important;
        left: 0 !important;
        width: 100% !important; 
        z-index: 9999 !important;
        background-color: rgba(26, 26, 26, 0.85) !important; 
        backdrop-filter: blur(12px) !important; 
        -webkit-backdrop-filter: blur(12px) !important; 
    }
    
    .common-header .logo { font-size: 1.5rem !important; font-weight: 800 !important; color: #fff !important; text-decoration: none !important; display: flex !important; align-items: center !important; gap: 8px !important; line-height: 1 !important; z-index: 10002; position: relative; }
    .common-header .nav-links { display: flex !important; gap: 20px !important; font-weight: 600 !important; font-size: 0.95rem !important; align-items: center !important; }
    .common-header .nav-links a { color: #888 !important; text-decoration: none !important; transition: 0.3s !important; line-height: 1 !important; }
    .common-header .nav-links a:hover, .common-header .nav-links a.active { color: #fff !important; }
    
    .common-header .nav-icons { display: flex !important; align-items: center !important; gap: 15px !important; }
    .common-header .user-avatar { 
        background: #00e5ff !important; 
        color: #000 !important; 
        font-weight: bold !important; 
        border-radius: 20px !important; 
        padding: 5px 15px !important; 
        font-size: 0.9rem !important; 
        display: inline-flex !important; 
        align-items: center !important; 
        justify-content: center !important; 
        line-height: 1.2 !important; 
        width: auto !important;         
        height: auto !important;        
        white-space: nowrap !important; 
    }
    .common-header .nav-icons a { line-height: 1 !important; }

    /* 모바일 전용 UI 요소 기본 숨김 처리 */
    .hamburger { display: none; background: none; border: none; color: #fff; font-size: 1.8rem; cursor: pointer; z-index: 10002; position: relative; }
    .mobile-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0,0,0,0.6); z-index: 9998; opacity: 0; transition: opacity 0.3s; }

    /* 모바일(768px 이하) 반응형 구현 */
    @media (max-width: 768px) {
        .common-header { padding: 0 20px !important; }
        .hamburger { display: block; } 
        
        /* 기존 메뉴들을 우측 사이드바로 강제 이동 및 세로 정렬 */
        .common-header .nav-links, .common-header .nav-icons {
            position: fixed; right: -250px; 
            background: #1a1a1a; z-index: 10001; 
            width: 250px; 
            transition: right 0.3s ease-in-out;
            flex-direction: column !important; 
            align-items: flex-start !important;
        }
        
        .common-header .nav-links {
            top: 0; height: 60vh; padding: 80px 30px 20px;
            gap: 30px !important;
        }
        .common-header .nav-links a { font-size: 1.2rem !important; }
        
        .common-header .nav-icons {
            top: 60vh; height: 40vh; padding: 20px 30px;
            border-top: 1px solid #333; gap: 20px !important;
        }

        .common-header .nav-links.active, .common-header .nav-icons.active { right: 0; }
        .mobile-overlay.active { display: block; opacity: 1; }
    }
</style>

<div class="mobile-overlay" id="mobileOverlay"></div>

<header class="common-header">
    <a href="index.php" class="logo">🚘 CarDex</a>
    
    <nav class="nav-links" id="navLinks">
        <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">검색 (Search)</a>
        <a href="category.php" class="<?= $current_page == 'category.php' ? 'active' : '' ?>">카테고리 (Category)</a>
        <a href="compare.php" class="<?= $current_page == 'compare.php' ? 'active' : '' ?>">비교함 (Compare)</a>
        <a href="mypage.php" class="<?= $current_page == 'mypage.php' ? 'active' : '' ?>">마이페이지 (My Page)</a>
    </nav>
    <div class="nav-icons" id="navIcons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span style="cursor:pointer; font-size: 1.1rem;">🔔</span>
            <div class="user-avatar"><?= htmlspecialchars($_SESSION['username']) ?></div>
            <a href="logout.php" style="font-size:0.8rem; color:#a0a0a0; font-weight:bold; text-decoration:none;">로그아웃</a>
        <?php else: ?>
            <a href="login.php" style="font-size:0.9rem; color:#fff; font-weight:bold; text-decoration:none;">로그인</a>
            <a href="register.php" style="background:#00e5ff; color:#000; padding: 6px 15px; border-radius: 20px; font-weight:bold; font-size:0.9rem; text-decoration:none;">회원가입</a>
        <?php endif; ?>
    </div>

    <button class="hamburger" id="hamburgerBtn">☰</button>
</header>

<script>
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const navLinks = document.getElementById('navLinks');
    const navIcons = document.getElementById('navIcons');
    const mobileOverlay = document.getElementById('mobileOverlay');

    function toggleMenu() {
        navLinks.classList.toggle('active');
        navIcons.classList.toggle('active');
        mobileOverlay.classList.toggle('active');
        hamburgerBtn.textContent = navLinks.classList.contains('active') ? '✕' : '☰';
    }

    hamburgerBtn.addEventListener('click', toggleMenu);
    mobileOverlay.addEventListener('click', toggleMenu);
</script>