<?php
// 1. "จ้างยามมาเฝ้าประตู"
include('includes/check_session.php'); //
// 2. เรียกใช้ไฟล์เชื่อมต่อ DB
require_once('db_connect.php'); //

// 3. ดึงข้อมูล KPI (กล่องสรุป)
try {
    $stmt_borrowed = $pdo->query("SELECT COUNT(*) FROM med_equipment WHERE status = 'borrowed'");
    $count_borrowed = $stmt_borrowed->fetchColumn();
    $stmt_available = $pdo->query("SELECT COUNT(*) FROM med_equipment WHERE status = 'available'");
    $count_available = $stmt_available->fetchColumn();
    $stmt_maintenance = $pdo->query("SELECT COUNT(*) FROM med_equipment WHERE status = 'maintenance'");
    $count_maintenance = $stmt_maintenance->fetchColumn();
    $stmt_overdue = $pdo->query("SELECT COUNT(*) FROM med_transactions WHERE status = 'borrowed' AND approval_status IN ('approved', 'staff_added') AND due_date < CURDATE()");
    $count_overdue = $stmt_overdue->fetchColumn();
} catch (PDOException $e) {
    $count_borrowed = $count_available = $count_maintenance = $count_overdue = 0;
    $kpi_error = "เกิดข้อผิดพลาดในการดึงข้อมูล KPI: " . $e->getMessage();
}

// 4. ดึงข้อมูล "รายการรออนุมัติ" (Pending Requests) 
$pending_requests = [];
try {
    $sql_pending = "SELECT 
                        t.id as transaction_id,
                        t.borrow_date, 
                        t.due_date,
                        t.reason_for_borrowing,
                        e.name as equipment_name,
                        s.full_name as student_name,
                        u.full_name as staff_name
                    FROM med_transactions t
                    JOIN med_equipment e ON t.equipment_id = e.id
                    LEFT JOIN med_students s ON t.borrower_student_id = s.id
                    LEFT JOIN med_users u ON t.lending_staff_id = u.id
                    WHERE t.approval_status = 'pending'
                    ORDER BY t.borrow_date ASC"; 
    
    $stmt_pending = $pdo->prepare($sql_pending);
    $stmt_pending->execute();
    $pending_requests = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $pending_error = "เกิดข้อผิดพลาดในการดึงข้อมูลคำขอ: " . $e->getMessage();
}

// 5. ดึงข้อมูล "รายการที่เกินกำหนดคืน"
$overdue_items = [];
try {
    $sql_overdue = "SELECT 
                        t.due_date,
                        t.equipment_id,
                        e.name as equipment_name,
                        s.full_name as student_name,
                        s.phone_number
                    FROM med_transactions t
                    JOIN med_equipment e ON t.equipment_id = e.id
                    LEFT JOIN med_students s ON t.borrower_student_id = s.id
                    WHERE t.status = 'borrowed' 
                      AND t.approval_status IN ('approved', 'staff_added') 
                      AND t.due_date < CURDATE()
                    ORDER BY t.due_date ASC"; 
    $stmt_overdue = $pdo->prepare($sql_overdue);
    $stmt_overdue->execute();
    $overdue_items = $stmt_overdue->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $overdue_error = "เกิดข้อผิดพลาดในการดึงข้อมูลเกินกำหนด: " . $e->getMessage();
}

// 6. ดึงข้อมูล "รายการเคลื่อนไหวล่าสุด" (5 รายการ)
$recent_activity = [];
try {
    $sql_activity = "SELECT 
                        t.approval_status, t.status, t.borrow_date, t.return_date,
                        e.name as equipment_name,
                        s.full_name as student_name
                    FROM med_transactions t
                    JOIN med_equipment e ON t.equipment_id = e.id
                    LEFT JOIN med_students s ON t.borrower_student_id = s.id
                    ORDER BY t.id DESC
                    LIMIT 5";
    $stmt_activity = $pdo->prepare($sql_activity);
    $stmt_activity->execute();
    $recent_activity = $stmt_activity->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $activity_error = "เกิดข้อผิดพลาดในการดึงข้อมูลเคลื่อนไหว: " . $e->getMessage();
}


// 7. ตั้งค่าตัวแปรสำหรับหน้านี้
$page_title = "Dashboard - ภาพรวม";
$current_page = "index";
// 8. เรียกใช้ไฟล์ Header
include('includes/header.php'); 
?>

<?php if (isset($kpi_error)) echo "<p style='color: red;'>$kpi_error</p>"; ?>

<div class="kpi-grid">
    <div class="stat-card kpi-borrowed">
        <div class="stat-card-info"><p class="title">กำลังถูกยืม</p><p class="value"><?php echo $count_borrowed; ?></p></div>
        <div class="stat-card-icon icon-borrowed"><i class="fas fa-box-open"></i></div>
    </div>
    <div class="stat-card kpi-available">
        <div class="stat-card-info"><p class="title">พร้อมใช้งาน</p><p class="value"><?php echo $count_available; ?></p></div>
        <div class="stat-card-icon icon-available"><i class="fas fa-check-circle"></i></div>
    </div>
    <div class="stat-card kpi-maintenance">
        <div class="stat-card-info"><p class="title">ซ่อมบำรุง</p><p class="value"><?php echo $count_maintenance; ?></p></div>
        <div class="stat-card-icon icon-maintenance"><i class="fas fa-tools"></i></div>
    </div>
    <div class="stat-card kpi-overdue">
        <div class="stat-card-info"><p class="title">เกินกำหนดคืน</p><p class="value"><?php echo $count_overdue; ?></p></div>
        <div class="stat-card-icon icon-overdue"><i class="fas fa-calendar-times"></i></div>
    </div>
</div>

<div class="dashboard-grid">

    <div class="container">
        <h2><i class="fas fa-hourglass-half" style="color: #fd7e14;"></i> รายการคำขอยืมที่รอดำเนินการ</h2>
        <div class="container-content">
            <?php if (isset($pending_error)) echo "<p style='color: red;'>$pending_error</p>"; ?>
            <table>
                <thead>
                    <tr>
                        <th>ผู้ขอ (ผู้ใช้งาน)</th>
                        <th>อุปกรณ์</th>
                        <th>เหตุผล</th>
                        <th>กำหนดคืน</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_requests)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">ไม่มีคำขอยืมที่รอดำเนินการ</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pending_requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['student_name'] ?? '[N/A]'); ?></td>
                                <td><?php echo htmlspecialchars($request['equipment_name']); ?></td>
                                <td style="white-space: pre-wrap; min-width: 150px;"><?php echo htmlspecialchars($request['reason_for_borrowing']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($request['due_date'])); ?></td>
                                <td style="white-space: nowrap;">
                                    <button type="button" 
                                            class="btn btn-borrow" 
                                            onclick="openApprovePopup(<?php echo $request['transaction_id']; ?>)">อนุมัติ</button>
                                    
                                    <button type="button" 
                                            class="btn" 
                                            style="background-color: #dc3545; margin-left: 5px;" 
                                            onclick="openRejectPopup(<?php echo $request['transaction_id']; ?>)">ปฏิเสธ</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="container">
        <h2><i class="fas fa-calendar-times" style="color: var(--color-danger);"></i> รายการที่เกินกำหนดคืน</h2>
        <div class="container-content">
            <?php if (isset($overdue_error)) echo "<p style='color: red;'>$overdue_error</p>"; ?>
            <table>
                <thead>
                    <tr>
                        <th>อุปกรณ์</th>
                        <th>ผู้ยืม</th>
                        <th>เบอร์โทร</th>
                        <th>เลยกำหนด</th>
                        <th>(รับคืน)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($overdue_items)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">ไม่มีรายการที่เกินกำหนด</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($overdue_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['student_name'] ?? '[N/A]'); ?></td>
                                <td><?php echo htmlspecialchars($item['phone_number'] ?? '[N/A]'); ?></td>
                                <td style="white-space: nowrap; color: #dc3545; font-weight: bold;">
                                    <?php echo date('d/m/Y', strtotime($item['due_date'])); ?>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-return" 
                                            onclick="openReturnPopup(<?php echo $item['equipment_id']; ?>)">รับคืน</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><div class="container activity-log">
    <h2><i class="fas fa-history" style="color: var(--color-primary);"></i> รายการเคลื่อนไหวล่าสุด</h2>
    
    <div class="container-content">
        <?php if (isset($activity_error)) echo "<p style='color: red;'>$activity_error</p>"; ?>
        
        <div class="activity-list">
            <?php if (empty($recent_activity)): ?>
                <div class="activity-item">
                    <p style="text-align: center; width: 100%;">ยังไม่มีความเคลื่อนไหว</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_activity as $act): ?>
                    <?php
                        // (ตรรกะแปลงสถานะเป็นข้อความและไอคอน)
                        $status_icon = '';
                        $status_text = '';
                        $student_name = htmlspecialchars($act['student_name'] ?? 'N/A');
                        $equip_name = htmlspecialchars($act['equipment_name']);

                        if ($act['approval_status'] == 'pending') {
                            $status_icon = '🟡'; 
                            $status_text = "<strong>{$student_name}</strong> ได้ส่งคำขอยืม <strong>{$equip_name}</strong>";
                        } elseif ($act['approval_status'] == 'rejected') {
                            $status_icon = '⚪'; 
                            $status_text = "<strong>คุณ</strong> ได้ปฏิเสธคำขอยืม <strong>{$equip_name}</strong> ของ <strong>{$student_name}</strong>";
                        } elseif ($act['status'] == 'returned') {
                            $status_icon = '🟢'; 
                            $status_text = "<strong>{$student_name}</strong> ได้คืน <strong>{$equip_name}</strong> (เมื่อ " . date('d/m/Y H:i', strtotime($act['return_date'])) . ")";
                        } elseif ($act['approval_status'] == 'approved') {
                            $status_icon = '🔵'; 
                            $status_text = "<strong>คุณ</strong> ได้อนุมัติคำขอยืม <strong>{$equip_name}</strong> ให้ <strong>{$student_name}</strong>";
                        } elseif ($act['approval_status'] == 'staff_added') {
                            $status_icon = '🟣'; 
                            $status_text = "<strong>คุณ</strong> ได้บันทึกการยืม <strong>{$equip_name}</strong> ให้ <strong>{$student_name}</strong>";
                        }
                    ?>
                    <div class="activity-item">
                        <span class="activity-icon" title="<?php echo $act['approval_status'] . '/' . $act['status']; ?>">
                            <?php echo $status_icon; ?>
                        </span>
                        <p><?php echo $status_text; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php
// 9. เรียกใช้ไฟล์ Footer
include('includes/footer.php'); 
?>