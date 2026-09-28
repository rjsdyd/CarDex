<?php
// 제일 중요! 로그인을 유지하려면 무조건 파일 맨 꼭대기에 session_start()가 있어야 해.
session_start();
require_once 'db.php';

$error = '';

// 만약 이미 로그인된 상태라면 굳이 로그인 창을 볼 필요 없이 메인으로 튕겨냄
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '아이디와 비밀번호를 입력해주세요.';
    } else {
        // 1. 입력한 아이디로 DB에서 유저 정보 가져오기
        $stmt = $pdo->prepare("SELECT user_id, username, password FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // 2. 유저가 존재하고, 비밀번호가 맞는지 확인 (password_verify로 암호화된 비번 해독)
        if ($user && password_verify($password, $user['password'])) {
            // 🎉 로그인 성공! 세션(VIP 출입증) 발급
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            
            // 로그인 성공 시 메인 화면(index.php)으로 이동
            header('Location: index.php');
            exit;
        } else {
            // 실패 시 에러 메시지
            $error = '아이디 또는 비밀번호가 일치하지 않습니다.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CarDex - 로그인</title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* register.php와 동일한 다크 모드 디자인 재활용 */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Pretendard', sans-serif; }
        body { background-color: #121212; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; }
        
        .auth-container { background: #1e1e24; padding: 40px; border-radius: 20px; width: 100%; max-width: 400px; border: 1px solid #333; text-align: center; }
        .auth-container h1 { font-size: 2rem; font-weight: 800; margin-bottom: 20px; color: #00e5ff; }
        .auth-container p { color: #a0a0a0; margin-bottom: 30px; }
        
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #ccc; }
        .form-group input { width: 100%; padding: 12px 15px; border-radius: 10px; border: 1px solid #444; background: #121212; color: #fff; font-size: 1rem; outline: none; transition: 0.3s; }
        .form-group input:focus { border-color: #00e5ff; }
        
        .btn-auth { width: 100%; padding: 14px; background: #00e5ff; color: #000; border: none; border-radius: 10px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,229,255,0.3); }
        
        .msg-error { background: rgba(255, 75, 75, 0.1); color: #ff4b4b; border: 1px solid #ff4b4b; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: bold; }
        
        .auth-links { margin-top: 25px; font-size: 0.9rem; color: #a0a0a0; }
        .auth-links a { color: #00e5ff; text-decoration: none; font-weight: bold; margin-left: 5px; }
    </style>
</head>
<body>

<div class="auth-container">
    <h1>CarDex</h1>
    <p>환영합니다! 계속하려면 로그인해주세요.</p>

    <?php if ($error): ?>
        <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>아이디 (Username)</label>
            <input type="text" name="username" placeholder="아이디 입력" required>
        </div>
        <div class="form-group">
            <label>비밀번호 (Password)</label>
            <input type="password" name="password" placeholder="비밀번호 입력" required>
        </div>
        <button type="submit" class="btn-auth">로그인</button>
    </form>

    <div class="auth-links">
        아직 계정이 없으신가요? <a href="register.php">회원가입</a>
    </div>
</div>

</body>
</html>