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
    $name          = isset($_POST['name']) ? trim($_POST['name']) : '';
    // ถ้า serial_number ว่าง ให้เก็บเป็น NULL (เพราะใน DB เราตั้งค่าเป็น UNIQUE)
    $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
    $description   = isset($_POST['description']) ? trim($_POST['description']) : null;

    if (empty($name)) {
        die("ข้อมูลไม่ครบถ้วน (Name) <a href='index.php'>กลับหน้าหลัก</a>");
    }
    
    // ถ้า serial ว่าง ให้เปลี่ยนเป็น NULL
    if (empty($serial_number)) $serial_number = null;
    if (empty($description)) $description = null;

    // 5. ดำเนินการ INSERT
    try {
        // สถานะ (status) จะเป็น 'available' ตามค่า DEFAULT ที่เราตั้งใน DB
        $sql = "INSERT INTO med_equipment (name, serial_number, description) 
                VALUES (?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $serial_number, $description]);

        // 6. ส่งผู้ใช้กลับไปหน้าหลัก
        // (เราควรจะเพิ่ม SweetAlert "สำเร็จ" ที่หน้า index.php ทีหลัง)
        header("Location: index.php?add=success");
        exit;

    } catch (PDOException $e) {
        // 7. หากเกิดข้อผิดพลาด
        // Error code '23000' คือการละเมิด Unique constraint (เช่น Serial Number ซ้ำ)
        if ($e->getCode() == '23000') {
             die("เกิดข้อผิดพลาด: เลขซีเรียล (Serial Number) นี้มีในระบบแล้ว <a href='add_equipment_form.php'>กลับไปแก้ไข</a>");
        } else {
            die("เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage() . " <a href='index.php'>กลับหน้าหลัก</a>");
        }
    }

} else {
    // ถ้าไม่ได้เข้ามาหน้านี้ผ่านการ POST
    header("Location: index.php");
    exit;
}
?>