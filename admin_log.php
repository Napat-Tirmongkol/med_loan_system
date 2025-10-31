<?php
// admin_log.php
// หน้าสำหรับแสดง Log การดำเนินการของ Admin

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin 
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// 3. (Query) ดึงข้อมูล Admin Log
$admin_logs = [];
try {
    // (JOIN ตาราง med_users เพื่อเอาชื่อ Admin)
    $sql_logs = "SELECT l.*, u.full_name as admin_name 
                 FROM med_logs l
                 LEFT JOIN med_users u ON l.user_id = u.id
                 ORDER BY l.timestamp DESC"; // (ดึงมาทั้งหมด)
                 
    $stmt_logs = $pdo->prepare($sql_logs);
    $stmt_logs->execute();
    $admin_logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $log_error = "เกิดข้อผิดพลาดในการดึงข้อมูล Log: " . $e->getMessage();
}

// 4. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "บันทึก Log (Admin)";
$current_page = "admin_log"; // ◀️ (ชื่อใหม่สำหรับ Active Menu)

// 5. เรียกใช้ Header
include('includes/header.php');
?>

<div class="header-row">
    <h2><i class="fas fa-history"></i> 📜 บันทึกการดำเนินการ (Admin Log)</h2>
</div>

<?php if (isset($log_error)) echo "<p style='color: red;'>$log_error</p>"; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="width: 160px;">เวลา</th>
                <th style="width: 150px;">ผู้ดำเนินการ (Admin)</th>
                <th style="width: 150px;">การกระทำ (Action)</th>
                <th>รายละเอียด</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($admin_logs)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">ยังไม่มีข้อมูลการดำเนินการใน Log</td>
                </tr>
            <?php else: ?>
                <?php foreach ($admin_logs as $log): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?></td>
                        <td><?php echo htmlspecialchars($log['admin_name'] ?? '[N/A]'); ?></td>
                        <td>
                            <span class="status-badge grey"><?php echo htmlspecialchars($log['action']); ?></span>
                        </td>
                        <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($log['description']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// 6. เรียกใช้ Footer
include('includes/footer.php');
?>