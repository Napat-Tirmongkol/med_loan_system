<?php
// student_dashboard.php (หน้าหลัก - รายการที่ยืมอยู่)

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
// (⚠️ โค้ดสำหรับ Development Mode - ใช้สำหรับตกแต่งหน้าเว็บ ⚠️)
@session_start(); 
// include('includes/check_student_session.php'); // (ปิดยาม)
$_SESSION['student_id'] = 1; // (จำลองว่าล็อกอินเป็น User ID 1)
$_SESSION['student_full_name'] = "ผู้ใช้ทดสอบ";
// (⚠️ จบส่วน Development Mode - อย่าลืมเอากลับคืนตอนใช้งานจริง ⚠️)

require_once('db_connect.php'); //

// 2. ดึง ID ของผู้ใช้งาน
$student_id = $_SESSION['student_id']; 

// 3. (Query เฉพาะส่วนที่ยืมอยู่)
try {
    $sql_borrowed = "SELECT t.*, e.name as equipment_name, e.serial_number 
                     FROM med_transactions t
                     JOIN med_equipment e ON t.equipment_id = e.id
                     WHERE t.borrower_student_id = ? 
                       AND t.status = 'borrowed'
                       AND t.approval_status IN ('approved', 'staff_added')
                     ORDER BY t.due_date ASC";
    
    $stmt_borrowed = $pdo->prepare($sql_borrowed);
    $stmt_borrowed->execute([$student_id]);
    $currently_borrowed = $stmt_borrowed->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $currently_borrowed = [];
    $borrowed_error = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

// 4. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "อุปกรณ์ที่ยืมอยู่";
$active_page = 'home'; // ◀️ (สำคัญ) บอก Footer ว่าเมนูไหน Active
include('includes/student_header.php');
?>

<div class="main-container">

    <div class="section-card">
        <h2 class="section-title">อุปกรณ์ที่ฉันกำลังยืม (รายการที่ต้องคืน)</h2>
        
        <?php if (isset($borrowed_error)) echo "<p style='color: red;'>$borrowed_error</p>"; ?>
        
        <table>
            <thead>
                <tr>
                    <th>อุปกรณ์</th>
                    <th>เลขซีเรียล</th>
                    <th>วันที่ยืม</th>
                    <th>วันที่กำหนดคืน</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($currently_borrowed)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">คุณไม่มีอุปกรณ์ที่กำลังยืมอยู่</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($currently_borrowed as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['serial_number'] ?? '-'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['borrow_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['due_date'])); ?></td>
                            <td>
                                <?php // (เช็คว่าเลยกำหนดหรือยัง)
                                    $due_date_time = strtotime($row['due_date']);
                                    if ($due_date_time < time()) {
                                        echo '<span class="status-badge red">เกินกำหนดคืน</span>';
                                    } else {
                                        echo '<span class="status-badge blue">กำลังยืม</span>';
                                    }
                                ?>
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