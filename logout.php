<?php
session_start();
session_destroy(); // 찢어버리기! (세션 삭제)
header('Location: index.php'); // 메인 화면으로 돌려보내기
exit;
?>