<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("คุณไม่มีสิทธิ์ดำเนินการ <a href='index.php'>กลับหน้าหลัก</a>");
}

// 3. ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. รับข้อมูลจากฟอร์ม
    $equipment_id  = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
    $name          = isset($_POST['name']) ? trim($_POST['name']) : '';
    $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : '';
    $status        = isset($_POST['status']) ? $_POST['status'] : '';

    // 5. ตรวจสอบข้อมูลเบื้องต้น
    if ($equipment_id == 0 || empty($name) || empty($status)) {
        die("ข้อมูลไม่ครบถ้วน (ID, Name, Status) <a href='index.php'>กลับหน้าหลัก</a>");
    }

    // 6. ตรวจสอบว่า status ที่ส่งมาถูกต้อง (ป้องกันการแก้ไข HTML)
    //    เราจะไม่อนุญาตให้เปลี่ยนเป็น 'borrowed' จากหน้านี้
    if ($status != 'available' && $status != 'maintenance') {
        // (ยกเว้นกรณีที่ของมัน 'borrowed' อยู่แล้ว และผู้ใช้ไม่ได้เปลี่ยนค่า)
        // เราจะตรวจสอบว่าค่าเดิมมัน 'borrowed' หรือไม่
        $stmt_check = $pdo->prepare("SELECT status FROM med_equipment WHERE id = ?");
        $stmt_check->execute([$equipment_id]);
        $current_status = $stmt_check->fetchColumn();

        if ($status != $current_status || $status == 'borrowed') {
             die("สถานะที่ส่งมาไม่ถูกต้อง <a href='index.php'>กลับหน้าหลัก</a>");
        }
    }


    // 7. ดำเนินการ UPDATE
    try {
        $sql = "UPDATE med_equipment 
                SET name = ?, serial_number = ?, status = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $serial_number, $status, $equipment_id]);

        // 8. ส่งผู้ใช้กลับไปหน้าหลัก
        header("Location: index.php?edit=success");
        exit;

    } catch (PDOException $e) {
        // 9. หากเกิดข้อผิดพลาด (เช่น Serial Number ซ้ำ)
        die("เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage() . " <a href='index.php'>กลับหน้าหลัก</a>");
    }

} else {
    // ถ้าไม่ได้เข้ามาหน้านี้ผ่านการ POST
    header("Location: index.php");
    exit;
}
?>