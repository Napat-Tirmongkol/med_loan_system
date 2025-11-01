<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ระบบยืมคืนอุปกรณ์การแพทย์'; ?></title>
    
    <link rel="stylesheet" href="CSS/style.css?v=2.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body style="padding-bottom: 70px;"> <header class="header"> 
        <h1>MedLoan - (Admin)</h1>
        
        <div class="user-info"> สวัสดี, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            (<?php 
                if ($_SESSION['role'] == 'admin') {
                    echo '<span style="color: var(--color-warning); font-weight: bold;">Admin <i class="fa-solid fa-crown"></i></span>';
                } elseif ($_SESSION['role'] == 'employee') {
                    echo '<span style="color: #B7E5CD;">Employee</span>';
                } else {
                    echo htmlspecialchars($_SESSION['role']);
                }
            ?>)
            <a href="logout.php" class="btn btn-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> ออกจากระบบ
            </a>
        </div>
    </header>

    <main class="content" style="margin-top: 80px;">