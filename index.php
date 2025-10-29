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
$current_page = "index"; 
// 5. เรียกใช้ไฟล์ Header
include('includes/header.php'); 
// 6. เตรียมดึงข้อมูลอุปกรณ์ (สำหรับตารางด้านล่าง)
try {
    $stmt = $pdo->prepare("SELECT * FROM med_equipment ORDER BY name ASC");
    $stmt->execute();
    $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    $equipments = []; 
}
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-top: 1px solid #ddd; padding-top: 20px; margin-top: 20px;">
        <h2>รายการอุปกรณ์ทั้งหมด</h2>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <a href="add_equipment_form.php" class="btn btn-borrow" style="font-size: 16px;">
                ➕ เพิ่มอุปกรณ์ใหม่
            </a>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่ออุปกรณ์</th>
                <th>เลขซีเรียล</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($equipments)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">ยังไม่มีอุปกรณ์ในระบบ</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; ?>
                <?php foreach ($equipments as $row): ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                        <td>
                            <?php if ($row['status'] == 'available'): ?>
                                <span class="status status-available">ว่าง</span>
                            <?php elseif ($row['status'] == 'borrowed'): ?>
                                <span class="status status-borrowed">ถูกยืม</span>
                            <?php else: // 'maintenance' ?>
                                <span class="status status-maintenance">ซ่อมบำรุง</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'available'): ?>
                                <button type="button" class="btn btn-borrow" onclick="openBorrowPopup(<?php echo $row['id']; ?>)">ยืม</button>
                            <?php elseif ($row['status'] == 'borrowed'): ?>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <button type="button" class="btn btn-return" onclick="openReturnPopup(<?php echo $row['id']; ?>)">รับคืน</button>
                                <?php else: ?>
                                    <span style="color: #6c757d;">(ถูกยืมอยู่)</span>
                                <?php endif; ?>
                            <?php else: // 'maintenance' ?>
                                <span style="color: #6c757d;">(ซ่อมบำรุง)</span>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <button type="button" class="btn btn-manage" style="margin-left: 5px;" onclick="openEditPopup(<?php echo $row['id']; ?>)">แก้ไข</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php $i++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// 8. เรียกใช้ไฟล์ Footer (ซึ่งมี JavaScript popups อยู่)
include('includes/footer.php'); 
?>