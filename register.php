<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '아이디와 비밀번호를 모두 입력해주세요.';
    } elseif ($password !== $password_confirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        // 1. 아이디 중복 체크
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            $error = '이미 존재하는 아이디입니다.';
        } else {
            // 2. 비밀번호 암호화 (해싱) - 실무 보안 필수!
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                // 3. DB에 유저 정보 저장
                $insertStmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
                $insertStmt->execute(['username' => $username, 'password' => $hashed_password]);
                $success = '회원가입이 완료되었습니다! 아래 버튼을 눌러 로그인해주세요.';
            } catch (\PDOException $e) {
                $error = '회원가입 중 오류가 발생했습니다: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CarDex - 회원가입</title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
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
        
        .msg { padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; font-weight: bold; }
        .msg-error { background: rgba(255, 75, 75, 0.1); color: #ff4b4b; border: 1px solid #ff4b4b; }
        .msg-success { background: rgba(0, 255, 102, 0.1); color: #00ff66; border: 1px solid #00ff66; }
        
        .auth-links { margin-top: 25px; font-size: 0.9rem; color: #a0a0a0; }
        .auth-links a { color: #00e5ff; text-decoration: none; font-weight: bold; margin-left: 5px; }
    </style>
</head>
<body>

<div class="auth-container">
    <h1>CarDex</h1>
    <p>새로운 계정을 만들어보세요.</p>

    <?php if ($error): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="msg msg-success"><?= htmlspecialchars($success) ?></div>
        <a href="login.php"><button class="btn-auth">로그인 하러 가기</button></a>
    <?php else: ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>아이디 (Username)</label>
                <input type="text" name="username" placeholder="영문, 숫자 조합" required>
            </div>
            <div class="form-group">
                <label>비밀번호 (Password)</label>
                <input type="password" name="password" placeholder="비밀번호 입력" required>
            </div>
            <div class="form-group">
                <label>비밀번호 확인</label>
                <input type="password" name="password_confirm" placeholder="비밀번호 다시 입력" required>
            </div>
            <button type="submit" class="btn-auth">회원가입 완료</button>
        </form>
    <?php endif; ?>

    <div class="auth-links">
        이미 계정이 있으신가요? <a href="login.php">로그인</a>
    </div>
</div>

</body>
</html>