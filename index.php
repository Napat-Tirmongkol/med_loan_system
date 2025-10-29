<?php
// 1. "จ้างยามมาเฝ้าประตู"
include('includes/check_session.php'); 
// 2. เรียกใช้ไฟล์เชื่อมต่อ DB
require_once('db_connect.php');
// 3. ดึงข้อมูล KPI (กล่องสรุป)
try {
    $stmt_borrowed = $pdo->query("SELECT COUNT(*) FROM med_equipment WHERE status = 'borrowed'");
    $count_borrowed = $stmt_borrowed->fetchColumn();
    $stmt_available = $pdo->query("SELECT COUNT(*) FROM med_equipment WHERE status = 'available'");
    $count_available = $stmt_available->fetchColumn();
    $stmt_maintenance = $pdo->query("SELECT COUNT(*) FROM med_equipment WHERE status = 'maintenance'");
    $count_maintenance = $stmt_maintenance->fetchColumn();
    $stmt_overdue = $pdo->query("SELECT COUNT(*) FROM med_transactions WHERE status = 'borrowed' AND due_date < CURDATE()");
    $count_overdue = $stmt_overdue->fetchColumn();
} catch (PDOException $e) {
    $count_borrowed = $count_available = $count_maintenance = $count_overdue = 0;
    $kpi_error = "เกิดข้อผิดพลาดในการดึงข้อมูล KPI: " . $e->getMessage();
}

// 4. ตั้งค่าตัวแปรสำหรับหน้านี้
$page_title = "Dashboard - ภาพรวม";
$current_page = "index"; // หน้านี้คือ 'index'
// 5. เรียกใช้ไฟล์ Header
include('includes/header.php'); 
?>

<div class="container">

    <?php if (isset($kpi_error)) echo "<p style='color: red;'>$kpi_error</p>"; ?>
    <div class="kpi-grid">
        <div class="stat-card kpi-borrowed">
            <div class="stat-card-info">
                <p class="title">กำลังถูกยืม</p>
                <p class="value"><?php echo $count_borrowed; ?></p>
            </div>
            <div class="stat-card-icon icon-borrowed">
                <i class="fas fa-box-open"></i>
            </div>
        </div>
        <div class="stat-card kpi-available">
            <div class="stat-card-info">
                <p class="title">พร้อมใช้งาน</p>
                <p class="value"><?php echo $count_available; ?></p>
            </div>
            <div class="stat-card-icon icon-available">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="stat-card kpi-overdue">
            <div class="stat-card-info">
                <p class="title">เกินกำหนดคืน</p>
                <p class="value"><?php echo $count_overdue; ?></p>
            </div>
            <div class="stat-card-icon icon-overdue">
                <i class="fas fa-calendar-times"></i>
            </div>
        </div>
        <div class="stat-card kpi-maintenance">
            <div class="stat-card-info">
                <p class="title">ซ่อมบำรุง</p>
                <p class="value"><?php echo $count_maintenance; ?></p>
            </div>
            <div class="stat-card-icon icon-maintenance">
                <i class="fas fa-tools"></i>
            </div>
        </div>
    </div>

    </div>

<?php
// 8. เรียกใช้ไฟล์ Footer (ซึ่งมี JavaScript popups อยู่)
include('includes/footer.php'); 
?>