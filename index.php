<?php
// 1. "จ้างยามมาเฝ้าประตู" (ต้องอยู่บนสุดเสมอ!)
include('includes/check_session.php'); 

// 2. เรียกใช้ไฟล์เชื่อมต่อ DB (*** เพิ่มไฟล์นี้เข้ามา ***)
//    เราต้องการตัวแปร $pdo เพื่อใช้ดึงข้อมูล
require_once('db_connect.php');

// 3. ตั้งค่าตัวแปรสำหรับหน้านี้
$page_title = "Dashboard - ภาพรวม";
$current_page = "index"; 

// 4. เรียกใช้ไฟล์ Header (ส่วนหัว + Sidebar)
include('includes/header.php'); 

// 5. เตรียมดึงข้อมูลอุปกรณ์จากฐานข้อมูล
try {
    // เตรียมคำสั่ง SQL
    $stmt = $pdo->prepare("SELECT * FROM med_equipment ORDER BY name ASC");
    // รันคำสั่ง
    $stmt->execute();
    // ดึงข้อมูลทั้งหมดมาเก็บใน $equipments
    $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // กรณีดึงข้อมูลไม่สำเร็จ
    echo "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    $equipments = []; // กำหนดให้เป็นค่าว่าง
}
?>

<div class="container">
    <h2>รายการอุปกรณ์ทั้งหมด</h2>

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
                <?php $i = 1; // ตัวแปรสำหรับนับลำดับ ?>
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
                                <a href="borrow_form.php?id=<?php echo $row['id']; ?>" class="btn btn-borrow">ยืม</a>
                            <?php elseif ($row['status'] == 'borrowed'): ?>
                                <a href="return_form.php?id=<?php echo $row['id']; ?>" class="btn btn-return">รับคืน</a>
                            <?php else: // 'maintenance' ?>
                                <a href="edit_form.php?id=<?php echo $row['id']; ?>" class="btn btn-manage">แก้ไข</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php $i++; // เพิ่มค่าลำดับ ?>
                <?php endforeach; ?>
            <?php endif; ?>

        </tbody>
    </table>
</div>

<?php
// 8. เรียกใช้ไฟล์ Footer (ส่วนท้าย)
include('includes/footer.php'); 
?>