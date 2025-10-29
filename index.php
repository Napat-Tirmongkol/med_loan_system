<?php
// 1. "จ้างยามมาเฝ้าประตู"
include('includes/check_session.php'); 

// 2. เรียกใช้ไฟล์เชื่อมต่อ DB
require_once('db_connect.php');

// 3. ตั้งค่าตัวแปรสำหรับหน้านี้
$page_title = "Dashboard - ภาพรวม";
$current_page = "index"; 

// 4. เรียกใช้ไฟล์ Header
include('includes/header.php'); 

// 5. เตรียมดึงข้อมูลอุปกรณ์
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
                                <a href="borrow_form.php?id=<?php echo $row['id']; ?>" class="btn btn-borrow">ยืม</a>
                            
                            <?php elseif ($row['status'] == 'borrowed'): ?>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <a href="return_form.php?id=<?php echo $row['id']; ?>" class="btn btn-return">รับคืน</a>
                                <?php else: ?>
                                    <span style="color: #6c757d;">(ถูกยืมอยู่)</span>
                                <?php endif; ?>
                                
                            <?php else: // 'maintenance' ?>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <a href="edit_form.php?id=<?php echo $row['id']; ?>" class="btn btn-manage">แก้ไข</a>
                                <?php else: ?>
                                    <span style="color: #6c757d;">(ซ่อมบำรุง)</span>
                                <?php endif; ?>
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
// 8. เรียกใช้ไฟล์ Footer
include('includes/footer.php'); 
?>