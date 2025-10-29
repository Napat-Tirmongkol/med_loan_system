<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ระบบยืมคืนอุปกรณ์การแพทย์'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .header .user-info {
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 0.9em;
        }
        .header .user-info a {
            color: #ffc107; /* สีเหลือง */
            text-decoration: none;
            margin-left: 10px;
            font-weight: bold;
        }
        .header .user-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <header class="header">
        <h1>MedLoan - ระบบยืมคืนอุปกรณ์การแพทย์</h1>
        
        <?php // ***** ส่วนที่ 1: อัปเดตตรงนี้ ***** ?>
        <?php // แสดงชื่อผู้ใช้, Role และปุ่ม Logout ?>
        <div class="user-info">
            สวัสดี, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            (<?php echo htmlspecialchars($_SESSION['role']); ?>)
            <a href="logout.php">[ ออกจากระบบ ]</a>
        </div>
    </header>

    <nav class="sidebar">
        <ul>
            <li class="<?php echo ($current_page == 'index') ? 'active' : ''; ?>">
                <a href="index.php">Dashboard (ภาพรวม)</a>
            </li>
            <li class="<?php echo ($current_page == 'borrow') ? 'active' : ''; ?>">
                <a href="#">ยืมอุปกรณ์</a>
            </li>
            <li class="<?php echo ($current_page == 'return') ? 'active' : ''; ?>">
                <a href="#">คืนอุปกรณ์</a>
            </li>
            <li class="<?php echo ($current_page == 'manage_equip') ? 'active' : ''; ?>">
                <a href="#">จัดการอุปกรณ์</a>
            </li>

            <?php // ***** ส่วนที่ 2: อัปเดตตรงนี้ ***** ?>
            <?php // ตรวจสอบว่า Role เป็น 'admin' หรือไม่ ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            
                <?php // ถ้าเป็น 'admin' ให้แสดงเมนูเหล่านี้ ?>
                <li class="<?php echo ($current_page == 'manage_user') ? 'active' : ''; ?>">
                    <a href="#">จัดการผู้ยืม</a>
                </li>
                <li class="<?php echo ($current_page == 'report') ? 'active' : ''; ?>">
                    <a href="#">รายงาน</a>
                </li>
            
            <?php endif; ?>
            <?php // ***** จบส่วนของ Admin ***** ?>
            
        </ul>
    </nav>

    <main class="content">