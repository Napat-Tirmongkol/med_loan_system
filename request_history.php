<?php
// request_history.php (หน้าประวัติคำขอ)

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
// (⚠️ โค้ดสำหรับ Development Mode ⚠️)
@session_start(); 
// include('includes/check_student_session.php'); 
$_SESSION['student_id'] = 1; 
$_SESSION['student_full_name'] = "ผู้ใช้ทดสอบ";
// (⚠️ จบส่วน Development Mode ⚠️)

require_once('db_connect.php'); //

// 2. ดึง ID ของผู้ใช้งาน
$student_id = $_SESSION['student_id']; 

// 3. (Query เฉพาะประวัติ)
try {
    $sql_history = "SELECT t.*, e.name as equipment_name, e.serial_number 
                    FROM med_transactions t
                    JOIN med_equipment e ON t.equipment_id = e.id
                    WHERE t.borrower_student_id = ? 
                      AND (t.status = 'returned' OR t.approval_status IN ('pending', 'rejected'))
                    ORDER BY t.borrow_date DESC, t.id DESC";
    
    $stmt_history = $pdo->prepare($sql_history);
    $stmt_history->execute([$student_id]);
    $history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $history = [];
    $history_error = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

// 4. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "ประวัติคำขอ";
$active_page = 'history'; // ◀️ (สำคัญ) บอก Footer ว่าเมนูไหน Active
include('includes/student_header.php');
?>

<div class="main-container">

    <div class="section-card">
        <h2 class="section-title">ประวัติคำขอที่ผ่านมา</h2>

        <?php if (isset($history_error)) echo "<p style='color: red;'>" . htmlspecialchars($history_error) . "</p>"; ?>
        
        <table>
            <thead>
                <tr>
                    <th>อุปกรณ์</th>
                    <th>วันที่ส่งคำขอ</th>
                    <th>วันที่กำหนดคืน</th>
                    <th>สถานะคำขอ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">คุณยังไม่มีประวัติการยืม</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['borrow_date'])); ?></td>
                            <td><?php echo $row['due_date'] ? date('d/m/Y', strtotime($row['due_date'])) : '-'; ?></td>
                            <td>
                                <?php if ($row['status'] == 'returned'): ?>
                                    <span class="status-badge green">คืนแล้ว</span>
                                <?php elseif ($row['approval_status'] == 'pending'): ?>
                                    <span class="status-badge yellow">รอดำเนินการ</span>
                                <?php elseif ($row['approval_status'] == 'rejected'): ?>
                                    <span class="status-badge grey">ถูกปฏิเสธ</span>
                                <?php else: ?>
                                    <span class="status-badge grey"><?php echo htmlspecialchars($row['approval_status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div> 

<?php
// 5. เรียกใช้ Footer
include('includes/student_footer.php'); 
?>