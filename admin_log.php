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

// 3. (Query ที่ 1) ดึงข้อมูล Sign-in Log
$signin_logs = [];
try {
    $sql_signin = "SELECT l.*, u.full_name as admin_name 
                   FROM med_logs l
                   LEFT JOIN med_users u ON l.user_id = u.id
                   WHERE l.action IN ('login_password', 'login_line')
                   ORDER BY l.timestamp DESC";
                 
    $stmt_signin = $pdo->prepare($sql_signin);
    $stmt_signin->execute();
    $signin_logs = $stmt_signin->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $log_error = "เกิดข้อผิดพลาดในการดึงข้อมูล Log (Sign-in): " . $e->getMessage();
}

// 4. (Query ที่ 2) ดึงข้อมูล Admin Log (อื่นๆ)
$admin_logs = [];
try {
    $sql_logs = "SELECT l.*, u.full_name as admin_name 
                 FROM med_logs l
                 LEFT JOIN med_users u ON l.user_id = u.id
                 WHERE l.action NOT IN ('login_password', 'login_line')
                 ORDER BY l.timestamp DESC";
                 
    $stmt_logs = $pdo->prepare($sql_logs);
    $stmt_logs->execute();
    $admin_logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $log_error = (isset($log_error) ? $log_error . "<br>" : "") . "เกิดข้อผิดพลาดในการดึงข้อมูล Log (Admin): " . $e->getMessage();
}


// 5. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "บันทึก Log (Admin)";
$current_page = "admin_log"; 

// 6. เรียกใช้ Header
include('includes/header.php');
?>

<div class="header-row">
    <h2><i class="fas fa-history"></i> 📜 บันทึก Log (Admin)</h2>
</div>

<?php if (isset($log_error)) echo "<p style='color: red;'>$log_error</p>"; ?>

<div class="header-row" style="margin-top: 2rem;">
    <h2><i class="fas fa-sign-in-alt"></i> ประวัติการเข้าสู่ระบบ (Sign-in Log)</h2>
</div>
<div class="table-container" style="margin-bottom: 2rem;">
    <table>
        <thead>
            <tr>
                <th style="width: 160px;">เวลา</th>
                <th style="width: 150px;">ผู้ดำเนินการ</th>
                <th style="width: 150px;">การกระทำ (Action)</th>
                <th>รายละเอียด</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($signin_logs)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">ยังไม่มีข้อมูลการเข้าสู่ระบบ</td>
                </tr>
            <?php else: ?>
                <?php foreach ($signin_logs as $log): ?>
                    <tr>
                        <td><?php echo date('d/m/Y h:i:s A', strtotime($log['timestamp'])); ?></td>
                        <td><?php echo htmlspecialchars($log['admin_name'] ?? '[N/A]'); ?></td>
                        <td>
                            <?php if ($log['action'] == 'login_line'): ?>
                                <span class="status-badge" style="background-color: #00B900; color: white;">
                                    <i class="fab fa-line"></i> <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge grey">
                                    <i class="fas fa-key"></i> <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($log['description']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="header-row">
    <h2><i class="fas fa-user-shield"></i> บันทึกการดำเนินการอื่นๆ (Admin Actions)</h2>
</div>
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
                        <td><?php echo date('d/m/Y h:i:s A', strtotime($log['timestamp'])); ?></td>
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
// 7. เรียกใช้ Footer
include('includes/footer.php');
?>