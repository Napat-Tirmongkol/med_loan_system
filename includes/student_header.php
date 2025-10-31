<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ระบบยืมคืนอุปกรณ์'; ?></title>
    
    <link rel="stylesheet" href="CSS/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    </head>
<body>

    <header class="header">
        <h1>MedLoan (สำหรับผู้ใช้งาน)</h1>
        
        <div class="user-info">
            สวัสดี, <?php echo htmlspecialchars($_SESSION['student_full_name']); ?>
            
            <a href="student_logout.php" class="btn btn-logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> ออกจากระบบ
            </a>
        </div>
    </header>

    <main class="content" style="margin-left: 0; margin-top: 60px;">