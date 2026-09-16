<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 2. 금고(.env)에서 정보 꺼내기
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'] ?? ''; // 비밀번호가 비어있을 수 있으므로 기본값 빈칸('') 처리
$charset = 'utf8mb4';

// 3. PDO 연결 주소(DSN) 만들기
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4. 실제 연결 시도
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "데이터베이스 연결 성공! 🚀\n";
} catch (\PDOException $e) {
    echo "데이터베이스 연결 실패: " . $e->getMessage() . "\n";
}
?>