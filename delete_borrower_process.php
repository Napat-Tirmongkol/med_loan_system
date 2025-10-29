<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("คุณไม่มีสิทธิ์ดำเนินการ <a href='index.php'>กลับหน้าหลัก</a>");
}

// 3. รับ ID ผู้ยืมจาก URL ($_GET)
$borrower_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($borrower_id == 0) {
    // ถ้าไม่มี ID ส่งมา ให้เด้งกลับ
    header("Location: manage_borrowers.php?error=no_id");
    exit;
}

// 4. *** ตรวจสอบ Foreign Key Constraint ***
//    เช็คว่าผู้ยืมคนนี้มีประวัติใน med_transactions หรือไม่
try {
    $sql_check = "SELECT COUNT(*) FROM med_transactions WHERE borrower_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$borrower_id]);
    $transaction_count = $stmt_check->fetchColumn();

    // 5. ถ้ามีประวัติการยืม (มากกว่า 0) -> ห้ามลบ
    if ($transaction_count > 0) {
        // เด้งกลับพร้อมข้อความ Error
        header("Location: manage_borrowers.php?error=fk_constraint&id=" . $borrower_id);
        exit;
    }

    // 6. ถ้าไม่มีประวัติการยืม -> ดำเนินการลบได้
    $sql_delete = "DELETE FROM med_borrowers WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$borrower_id]);

    // 7. ตรวจสอบว่าลบสำเร็จหรือไม่ (rowCount จะเป็น 1 ถ้าลบได้)
    if ($stmt_delete->rowCount() > 0) {
        // ส่งกลับพร้อมข้อความ Success
        header("Location: manage_borrowers.php?delete=success");
        exit;
    } else {
        // กรณีไม่พบ ID ที่ต้องการลบ (อาจมีคนลบไปแล้ว)
        header("Location: manage_borrowers.php?error=not_found&id=" . $borrower_id);
        exit;
    }

} catch (PDOException $e) {
    // หากเกิด Error อื่นๆ
    die("เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage() . " <a href='manage_borrowers.php'>กลับหน้าหลัก</a>");
}
?>