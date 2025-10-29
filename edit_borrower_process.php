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
    $borrower_id  = isset($_POST['borrower_id']) ? (int)$_POST['borrower_id'] : 0;
    $full_name    = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $contact_info = isset($_POST['contact_info']) ? trim($_POST['contact_info']) : null;

    if ($borrower_id == 0 || empty($full_name)) {
        die("ข้อมูลไม่ครบถ้วน (ID หรือ Full Name) <a href='manage_borrowers.php'>กลับไปแก้ไข</a>");
    }
    
    if (empty($contact_info)) $contact_info = null;

    // 5. ดำเนินการ UPDATE
    try {
        $sql = "UPDATE med_borrowers 
                SET full_name = ?, contact_info = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $contact_info, $borrower_id]);

        // 6. ส่งผู้ใช้กลับไปหน้า "จัดการผู้ยืม"
        header("Location: manage_borrowers.php?edit=success");
        exit;

    } catch (PDOException $e) {
        // 7. หากเกิดข้อผิดพลาด
        die("เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage() . " <a href='manage_borrowers.php'>กลับไปแก้ไข</a>");
    }

} else {
    // ถ้าไม่ได้เข้ามาหน้านี้ผ่านการ POST
    header("Location: manage_borrowers.php");
    exit;
}
?>