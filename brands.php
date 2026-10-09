<?php
session_start();
require_once 'db.php';

$stmt = $pdo->query("SELECT make, COUNT(*) as cnt FROM cars GROUP BY make ORDER BY make ASC");
$brands = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CarDex - 전체 브랜드 목록</title>
    <style>
        * { 
            box-sizing: border-box; 
        }
        
        body { 
            background-color: #121212; 
            color: #fff; 
            margin: 0; 
            padding: 0; 
            min-width: 320px; 
            overflow-x: hidden; 
        }

        a { 
            text-decoration: none; 
            color: inherit; 
        }

        .container { 
            max-width: 1200px; 
            margin: 40px auto; 
            padding: 0 20px; 
            width: 100%; 
        }
        
        .page-title { 
            margin-bottom: 40px; 
        }

        .page-title h1 { 
            font-size: 2.2rem; 
            font-weight: 800; 
            color: #00e5ff; 
            margin-bottom: 5px; 
        }

        .page-title p { 
            color: #a0a0a0; 
            font-size: 0.95rem; 
            margin: 0; 
        }

        .brands-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 60px; 
        }

        .brand-card { 
            background: #1e1e24; 
            border: 1px solid #2a2a2f; 
            border-radius: 16px; 
            padding: 30px 20px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            gap: 15px; 
            cursor: pointer; 
            transition: 0.3s; 
        }

        .brand-card:hover { 
            transform: translateY(-5px); 
            border-color: #00e5ff; 
            box-shadow: 0 10px 20px rgba(0, 229, 255, 0.1); 
            background: rgba(0, 229, 255, 0.05); 
        }
        
        .b-icon { 
            width: 70px; 
            height: 70px; 
            background: #121212; 
            border-radius: 50%; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            font-size: 1.8rem; 
            font-weight: 800; 
            color: #fff; 
            border: 2px solid #333; 
            transition: 0.3s; 
        }

        .brand-card:hover .b-icon { 
            border-color: #00e5ff; 
            color: #00e5ff; 
            transform: scale(1.05); 
        }
        
        .b-info { 
            text-align: center; 
        }

        .b-name { 
            font-size: 1.2rem; 
            font-weight: 700; 
            text-transform: uppercase; 
            margin-bottom: 8px; 
            display: block; 
        }

        .b-count { 
            font-size: 0.8rem; 
            color: #888; 
            background: #121212; 
            padding: 4px 10px; 
            border-radius: 12px; 
            border: 1px solid #333; 
        }

        @media (max-width: 768px) {
            .container { 
                margin: 20px auto; 
            }

            .page-title { 
                margin-bottom: 25px; 
            }

            .page-title h1 { 
                font-size: 1.8rem; 
            }
            
            .brands-grid { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 12px; 
                margin-bottom: 40px; 
            }
            
            .brand-card { 
                padding: 20px 10px; 
                gap: 10px; 
            }

            .b-icon { 
                width: 50px; 
                height: 50px; 
                font-size: 1.3rem; 
            }

            .b-name { 
                font-size: 1rem; 
                margin-bottom: 5px; 
            }
            
            .b-count { 
                font-size: 0.7rem; 
                padding: 3px 8px; 
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="page-title">
        <h1>전체 브랜드 탐색</h1>
        <p>CarDex 데이터베이스에 등록된 모든 자동차 브랜드를 한눈에 확인하세요.</p>
    </div>

    <div class="brands-grid">
        <?php foreach ($brands as $b): 
            $make = htmlspecialchars($b['make']);
            $firstLetter = strtoupper(substr($make, 0, 1));
        ?>
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