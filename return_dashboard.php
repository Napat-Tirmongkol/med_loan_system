<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin (หน้านี้เฉพาะ Admin)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// 3. ดึงข้อมูล (เหมือน report_borrowed.php แต่เพิ่ม equipment_id)
$borrowed_items = [];
try {
    $sql = "SELECT 
                t.equipment_id, -- เราต้องการ ID นี้สำหรับปุ่ม
                e.name as equipment_name, 
                e.serial_number as equipment_serial,
                b.full_name as borrower_name,
                t.borrow_date, 
                t.due_date
            FROM med_transactions t
            JOIN med_equipment e ON t.equipment_id = e.id
            JOIN med_borrowers b ON t.borrower_id = b.id
            WHERE t.status = 'borrowed'
            ORDER BY t.due_date ASC"; // เรียงตามวันที่ "กำหนดคืน" (ใกล้สุดก่อน)

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $borrowed_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}

// 4. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "คืนอุปกรณ์";
$current_page = "return"; // เพื่อให้เมนู "คืนอุปกรณ์" active

// 5. เรียกใช้ Header
include('includes/header.php');
?>

<div class="container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>📦 รายการอุปกรณ์ที่ต้องรับคืน</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>อุปกรณ์</th>
                <th>เลขซีเรียล</th>
                <th>ผู้ยืม</th>
                <th>วันที่ยืม</th>
                <th>วันที่กำหนดคืน</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($borrowed_items)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">ไม่มีอุปกรณ์ที่กำลังถูกยืมในขณะนี้</td>
                </tr>
            <?php else: ?>
                <?php foreach ($borrowed_items as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['equipment_serial']); ?></td>
                        <td><?php echo htmlspecialchars($row['borrower_name']); ?></td>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($row['borrow_date'])); ?>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($row['due_date'])); ?>
                        </td>
                        <td>
                            <button type="button" 
                                    class="btn btn-return" 
                                    onclick="openReturnPopup(<?php echo $row['equipment_id']; ?>)">รับคืน</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// 7. เรียกใช้ Footer (ซึ่งตอนนี้มี JavaScript อยู่ข้างในแล้ว)
include('includes/footer.php'); 
?>