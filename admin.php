<?php
session_start();
require_once 'db.php';

// 1. 기본 로그인 및 관리자 권한 철벽 방어
if (!isset($_SESSION['user_id'])) {
    die("<script>alert('로그인이 필요한 서비스입니다.'); location.href='login.php';</script>");
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser || $currentUser['role'] !== 'admin') {
    die("<script>alert('접근 권한이 없습니다. (관리자 전용)'); history.back();</script>");
}

// 2. 관리자 전용 액션 처리 (권한 승격, 권한 회수, 강제 탈퇴)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetUserId = $_POST['target_user_id'] ?? '';

    // 자기 자신을 건드리지 못하도록 방어
    if ($targetUserId && $targetUserId != $_SESSION['user_id']) {
        if ($action === 'promote') {
            // 관리자로 승격
            $updateStmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
            $updateStmt->execute([$targetUserId]);
            echo "<script>alert('해당 유저를 관리자로 승격했습니다.'); location.replace('admin.php');</script>";
            exit;
        } elseif ($action === 'demote') {
            // 일반 유저로 강등
            $updateStmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE user_id = ?");
            $updateStmt->execute([$targetUserId]);
            echo "<script>alert('해당 유저의 관리자 권한을 회수했습니다.'); location.replace('admin.php');</script>";
            exit;
        } elseif ($action === 'delete') {
            // 강제 탈퇴 처리 (외래키 제약조건 방지를 위해 찜 목록부터 먼저 삭제)
            $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?")->execute([$targetUserId]);
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$targetUserId]);
            echo "<script>alert('해당 회원을 강제 탈퇴 처리했습니다.'); location.replace('admin.php');</script>";
            exit;
        }
    } else if ($targetUserId == $_SESSION['user_id']) {
        echo "<script>alert('최고 관리자(본인) 계정은 변경하거나 삭제할 수 없습니다.'); location.replace('admin.php');</script>";
        exit;
    }
}

// 3. 대시보드 데이터 가져오기
$userStmt = $pdo->query("SELECT user_id, username, role, created_at FROM users ORDER BY created_at DESC");
$allUsers = $userStmt->fetchAll();

$carCount = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <!-- 💡 [추가됨] 모바일 기기 필수 뷰포트 태그 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CarDex - 관리자 센터</title>
    <style>
        /* 💡 [추가됨] 모든 요소 화면 이탈 방지 */
        * { box-sizing: border-box; }
        
        body { background-color: #121212; color: #fff; font-family: 'Noto Sans KR', sans-serif; margin: 0; padding: 0; min-width: 320px; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .page-header { margin-bottom: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
        .page-title h1 { font-size: 2.2rem; font-weight: 800; color: #ff4b4b; margin: 0 0 10px 0; display: flex; align-items: center; gap: 10px; }
        .page-title p { color: #a0a0a0; margin: 0; font-size: 0.95rem; }
        
        .admin-grid { display: grid; grid-template-columns: 2.5fr 1fr; gap: 30px; }
        
        .admin-card { background: #1e1e24; border: 1px solid #2a2a2f; border-radius: 16px; padding: 30px; width: 100%; overflow: hidden; }
        .card-title { font-size: 1.2rem; font-weight: 800; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #333; padding-bottom: 15px; }

        /* 💡 [추가됨] 테이블 가로 스크롤 래퍼 */
        .table-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 10px; }
        /* 스크롤바 디자인 */
        .table-wrapper::-webkit-scrollbar { height: 6px; }
        .table-wrapper::-webkit-scrollbar-track { background: #121212; border-radius: 4px; }
        .table-wrapper::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }

        /* 테이블 스타일 */
        table { width: 100%; border-collapse: collapse; min-width: 600px; /* 모바일에서 표가 너무 찌그러지지 않도록 최소 너비 보장 */ }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #333; font-size: 0.95rem; vertical-align: middle; white-space: nowrap; }
        th { color: #888; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .role-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; display: inline-block; text-align: center; width: 60px; }
        .role-admin { background: rgba(255, 75, 75, 0.1); color: #ff4b4b; border: 1px solid rgba(255, 75, 75, 0.3); }
        .role-user { background: rgba(0, 229, 255, 0.1); color: #00e5ff; border: 1px solid rgba(0, 229, 255, 0.3); }

        /* 액션 버튼 스타일 */
        .action-form { display: inline-flex; gap: 8px; margin: 0; }
        .btn-action { padding: 8px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; cursor: pointer; border: none; transition: 0.2s; color: #000; }
        .btn-promote { background: #00e5ff; }
        .btn-promote:hover { background: #00b3cc; }
        .btn-demote { background: #ffaa00; }
        .btn-demote:hover { background: #cc8800; }
        .btn-delete { background: #ff4b4b; color: #fff; }
        .btn-delete:hover { background: #cc0000; }

        .stat-box { background: #121212; border: 1px solid #333; border-radius: 12px; padding: 25px; text-align: center; margin-bottom: 20px; }
        .stat-box span { display: block; color: #888; font-size: 0.85rem; margin-bottom: 5px; }
        .stat-box strong { font-size: 2.5rem; color: #00ff88; font-weight: 900; }

        .btn-seed { display: block; width: 100%; background: #00e5ff; color: #000; text-align: center; padding: 20px; border-radius: 12px; font-weight: 900; font-size: 1.1rem; border: none; cursor: pointer; transition: 0.2s; margin-top: 10px; box-sizing: border-box; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-seed:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 229, 255, 0.2); }

        /* 💡 [추가됨] 모바일(768px 이하) 전용 반응형 CSS */
        @media (max-width: 768px) {
            .container { margin: 20px auto; }
            .page-header { margin-bottom: 25px; }
            .page-title h1 { font-size: 1.8rem; }
            
            /* 좌우 2단 분리를 상하 1열로 변경 */
            .admin-grid { grid-template-columns: 1fr; gap: 20px; }
            
            /* 모바일 패딩 축소 */
            .admin-card { padding: 20px 15px; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>👑 관리자 센터</h1>
            <p>플랫폼 회원 관리 및 데이터 수집을 총괄하는 공간입니다.</p>
        </div>
    </div>

    <div class="admin-grid">
        <!-- 좌측: 유저 명단 -->
        <div class="admin-card">
            <div class="card-title">👥 가입자 명단 (총 <?= count($allUsers) ?>명)</div>
            
            <!-- 💡 [수정됨] 모바일 스크롤 대응을 위한 테이블 래퍼 추가 -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>NO.</th>
                            <th>유저 닉네임</th>
                            <th>권한 (ROLE)</th>
                            <th>가입일시</th>
                            <th>관리 (ACTION)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($allUsers as $index => $u): ?>
                        <tr>
                            <td style="color:#666; font-weight:bold;"><?= $index + 1 ?></td>
                            <td style="font-weight:bold; font-size: 1.05rem;"><?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <span class="role-badge <?= ($u['role'] ?? 'user') === 'admin' ? 'role-admin' : 'role-user' ?>">
                                    <?= strtoupper($u['role'] ?? 'user') ?>
                                </span>
                            </td>
                            <td style="color:#888; font-size: 0.85rem;"><?= date('Y-m-d H:i', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                    <!-- 타인 계정일 경우 액션 버튼 노출 -->
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                        
                                        <?php if (($u['role'] ?? 'user') !== 'admin'): ?>
                                            <button type="submit" name="action" value="promote" class="btn-action btn-promote" onclick="return confirm('이 유저를 관리자로 승격하시겠습니까?');">승격</button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="demote" class="btn-action btn-demote" onclick="return confirm('이 유저의 관리자 권한을 회수하시겠습니까?');">권한해제</button>
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="action" value="delete" class="btn-action btn-delete" onclick="return confirm('정말 이 회원을 강제 탈퇴 처리하시겠습니까?\n이 회원이 차고에 저장한 데이터도 함께 삭제됩니다.');">강제탈퇴</button>
                                    </form>
                                <?php else: ?>
                                    <!-- 내 계정일 경우 조작 방지 -->
                                    <span style="font-size: 0.75rem; color: #555; font-weight: bold; padding: 6px 0; display: inline-block;">내 계정 (조작불가)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 우측: 시스템 컨트롤 -->
        <div class="admin-card">
            <div class="card-title">⚙️ 시스템 제어</div>
            
            <div class="stat-box">
                <span>현재 적재된 차량 데이터</span>
                <strong><?= number_format($carCount) ?> 대</strong>
            </div>
            
            <a href="seed.php" class="btn-seed">
                🚀 데이터 수집 툴 실행
            </a>
            <p style="color:#666; font-size:0.8rem; text-align:center; margin-top:15px; line-height:1.5;">
                클릭 시 seed.php가 실행되며<br>서버 리소스를 사용하여 데이터를 긁어옵니다.
            </p>
        </div>
    </div>
</div>

</body>
</html>