<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ระบบยืมคืนอุปกรณ์'; ?></title>
    
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark-mode');
                    // (สำหรับ CSS ที่อาจจะใช้ body.dark-mode)
                    document.addEventListener('DOMContentLoaded', function() {
                        document.body.classList.add('dark-mode');
                    });
                } else if (theme === 'light') {
                    // ไม่ต้องทำอะไร (Default)
                } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    // ถ้าไม่ได้ตั้งค่าไว้ ให้ใช้ค่าของ OS
                    document.documentElement.classList.add('dark-mode');
                    document.addEventListener('DOMContentLoaded', function() {
                        document.body.classList.add('dark-mode');
                    });
                }
            } catch (e) {
                // (กัน Error หาก localStorage ใช้งานไม่ได้)
                console.error('Failed to apply theme', e);
            }
        })();
    </script>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="CSS/style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body style="padding-bottom: 70px;"> <header class="header"> 
        <h1>MedLoan - (Admin)</h1>
        
        <div class="user-info"> 
            
            <div class="user-greeting">
                สวัสดี, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                (<?php 
                    if ($_SESSION['role'] == 'admin') {
                        echo '<span style="color: var(--color-warning); font-weight: bold;">Admin <i class="fa-solid fa-crown"></i></span>';
                    } elseif ($_SESSION['role'] == 'employee') {
                        echo '<span style="color: #B7E5CD;">Employee</span>';
                    } else {
                        echo htmlspecialchars($_SESSION['role']);
                    }
                ?>)
            </div>

            <button type="button" class="theme-toggle-btn" id="theme-toggle-btn" title="สลับโหมด">
                <i class="fas fa-moon"></i>
                <i class="fas fa-sun"></i>
            </button>
            
            <a href="logout.php" class="btn btn-logout" title="ออกจากระบบ">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span class="logout-text">ออกจากระบบ</span>
            </a>
        </div>
    </header>

    <main class="content" style="margin-top: 80px;">