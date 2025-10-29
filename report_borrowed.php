<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php'); //
require_once('db_connect.php'); //

// 2. ตรวจสอบสิทธิ์ Admin (หน้านี้เฉพาะ Admin)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// 3. ดึงข้อมูลรายงาน (เฉพาะที่กำลังถูกยืม 'borrowed')
$report_data = [];
try {
    // นี่คือ SQL ที่ JOIN 3 ตาราง
    // 1. med_transactions (t)
    // 2. med_equipment (e)
    // 3. med_borrowers (b)
    $sql = "SELECT 
                t.borrow_date, 
                t.due_date,
                e.name as equipment_name, 
                e.serial_number as equipment_serial,
                b.full_name as borrower_name
            FROM med_transactions t
            JOIN med_equipment e ON t.equipment_id = e.id
            JOIN med_borrowers b ON t.borrower_id = b.id
            WHERE t.status = 'borrowed'
            ORDER BY t.due_date ASC"; // เรียงตามวันที่ "กำหนดคืน" (ใกล้สุดก่อน)

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาดในการดึงข้อมูลรายงาน: " . $e->getMessage();
}

// 4. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "รายงานอุปกรณ์ที่ถูกยืม";
$current_page = "report"; // เพื่อให้เมนู "รายงาน" active

// 5. เรียกใช้ Header
include('includes/header.php');
?>

<div class="container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>📊 รายงานอุปกรณ์ที่กำลังถูกยืม</h2>
        </div>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>อุปกรณ์</th>
                <th>เลขซีเรียล</th>
                <th>ผู้ยืม</th>
                <th>วันที่ยืม</th>
                <th>วันที่กำหนดคืน</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($report_data)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">ไม่มีอุปกรณ์ที่กำลังถูกยืมในขณะนี้</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; ?>
                <?php foreach ($report_data as $row): ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['equipment_serial']); ?></td>
                        <td><?php echo htmlspecialchars($row['borrower_name']); ?></td>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($row['borrow_date'])); ?>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($row['due_date'])); ?>
                        </td>
                    </tr>
                <?php $i++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// 7. เรียกใช้ Footer
include('includes/footer.php'); 
?>