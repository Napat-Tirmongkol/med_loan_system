<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ระบบยืมคืนอุปกรณ์การแพทย์'; ?></title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    </head>

<body>

    <header class="header"> <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>
        <h1>MedLoan - ระบบยืมคืนอุปกรณ์การแพทย์</h1>
        
        <div class="user-info"> สวัสดี, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            (<?php 
                // (เปลี่ยนการแสดงผล Role)
                if ($_SESSION['role'] == 'admin') {
                    echo '<span style="color: var(--color-warning); font-weight: bold;">Admin <i class="fa-solid fa-crown"></i></span>';
                } elseif ($_SESSION['role'] == 'employee') {
                    echo '<span style="color: #B7E5CD;">Employee</span>'; // (ใช้สีเขียวมินต์)
                } else {
                    echo htmlspecialchars($_SESSION['role']);
                }
            ?>)
            <a href="logout.php" class="btn btn-logout"> <i class="fa-solid fa-arrow-right-from-bracket"></i> ออกจากระบบ
            </a>
        </div>
    </header>

    <nav class="sidebar">
        <ul>
            <li class="<?php echo ($current_page == 'index') ? 'active' : ''; ?>">
                <a href="index.php"><i class="fas fa-tachometer-alt fa-fw" style="margin-right: 8px;"></i>Dashboard (ภาพรวม)</a>
            </li>

            <li class="<?php echo ($current_page == 'return') ? 'active' : ''; ?>">
                <a href="return_dashboard.php"><i class="fas fa-undo-alt fa-fw" style="margin-right: 8px;"></i>คืนอุปกรณ์</a>
            </li>

            <li class="<?php echo ($current_page == 'manage_equip') ? 'active' : ''; ?>">
                <a href="manage_equipment.php"><i class="fas fa-tools fa-fw" style="margin-right: 8px;"></i>จัดการอุปกรณ์</a>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?> <li class="<?php echo ($current_page == 'manage_user') ? 'active' : ''; ?>">
                    <a href="manage_students.php"><i class="fas fa-users-cog fa-fw" style="margin-right: 8px;"></i>จัดการผู้ใช้งาน</a>
                </li>
                <li class="<?php echo ($current_page == 'report') ? 'active' : ''; ?>">
                    <a href="report_borrowed.php"><i class="fas fa-chart-line fa-fw" style="margin-right: 8px;"></i>รายงาน</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="content">