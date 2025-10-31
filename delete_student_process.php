<?php
// delete_student_process.php
// รับ ID ผู้ใช้งานจาก URL (GET) เพื่อลบออกจากตาราง med_students

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php'); //
require_once('db_connect.php'); //

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("คุณไม่มีสิทธิ์ดำเนินการ <a href='index.php'>กลับหน้าหลัก</a>");
}

// 3. รับ ID ผู้ใช้งาน (Student ID) จาก URL ($_GET)
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id == 0) {
    // ถ้าไม่มี ID ส่งมา ให้เด้งกลับ
    header("Location: manage_students.php?error=no_id");
    exit;
}

// 4. *** (SQL) ตรวจสอบ Foreign Key Constraint ***
//    เช็คว่าผู้ใช้งานคนนี้มีประวัติใน med_transactions หรือไม่
try {
    $sql_check = "SELECT COUNT(*) FROM med_transactions WHERE borrower_student_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$student_id]);
    $transaction_count = $stmt_check->fetchColumn();

    // 5. ถ้ามีประวัติการยืม (มากกว่า 0) -> ห้ามลบ
    if ($transaction_count > 0) {
        // เด้งกลับพร้อมข้อความ Error
        header("Location: manage_students.php?error=fk_constraint&id=" . $student_id);
        exit;
    }

    // 6. (SQL) ถ้าไม่มีประวัติการยืม -> ดำเนินการลบจาก med_students
    $sql_delete = "DELETE FROM med_students WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$student_id]);

    // 7. ตรวจสอบว่าลบสำเร็จหรือไม่
    if ($stmt_delete->rowCount() > 0) {
        // ส่งกลับพร้อมข้อความ Success
        header("Location: manage_students.php?delete=success");
        exit;
    } else {
        // กรณีไม่พบ ID ที่ต้องการลบ
        header("Location: manage_students.php?error=not_found&id=" . $student_id);
        exit;
    }

} catch (PDOException $e) {
    // หากเกิด Error อื่นๆ
    die("เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage() . " <a href='manage_students.php'>กลับหน้าหลัก</a>");
}
?>