<?php
// delete_equipment_process.php
// รับ ID อุปกรณ์จาก URL (GET) เพื่อลบออกจากตาราง med_equipment

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php'); //
require_once('db_connect.php'); //

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("คุณไม่มีสิทธิ์ดำเนินการ <a href='index.php'>กลับหน้าหลัก</a>");
}

// 3. รับ ID อุปกรณ์จาก URL ($_GET)
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($equipment_id == 0) {
    header("Location: manage_equipment.php?error=no_id");
    exit;
}

// 4. *** ตรวจสอบ Foreign Key Constraint ***
//    เช็คว่าอุปกรณ์นี้มีประวัติใน med_transactions หรือไม่
try {
    $sql_check = "SELECT COUNT(*) FROM med_transactions WHERE equipment_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$equipment_id]);
    $transaction_count = $stmt_check->fetchColumn();

    // 5. ถ้ามีประวัติการยืม (มากกว่า 0) -> ห้ามลบ
    if ($transaction_count > 0) {
        header("Location: manage_equipment.php?error=fk_constraint&id=" . $equipment_id);
        exit;
    }

    // 6. ถ้าไม่มีประวัติการยืม -> ดำเนินการลบ
    $sql_delete = "DELETE FROM med_equipment WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$equipment_id]);

    // 7. ตรวจสอบว่าลบสำเร็จหรือไม่
    if ($stmt_delete->rowCount() > 0) {
        header("Location: manage_equipment.php?delete=success");
        exit;
    } else {
        header("Location: manage_equipment.php?error=not_found&id=" . $equipment_id);
        exit;
    }

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage() . " <a href='manage_equipment.php'>กลับหน้าหลัก</a>");
}
?>