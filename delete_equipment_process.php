<?php
// delete_equipment_process.php

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');
require_once('includes/log_function.php'); // ◀️ (เพิ่ม) เรียกใช้ Log

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("คุณไม่มีสิทธิ์ดำเนินการ <a href='index.php'>กลับหน้าหลัก</a>");
}

// 3. รับ ID อุปกรณ์
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($equipment_id == 0) {
    header("Location: manage_equipment.php?error=no_id");
    exit;
}

// 4. ตรวจสอบ Foreign Key และ ดำเนินการ
try {
    // (เช็ค Constraint ... )
    $sql_check = "SELECT COUNT(*) FROM med_transactions WHERE equipment_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$equipment_id]);
    $transaction_count = $stmt_check->fetchColumn();

    if ($transaction_count > 0) {
        header("Location: manage_equipment.php?error=fk_constraint&id=" . $equipment_id);
        exit;
    }

    // ◀️ --- (เพิ่มส่วน Log) --- ◀️
    // (ดึงข้อมูลอุปกรณ์ "ก่อน" ที่จะลบ)
    $stmt_get = $pdo->prepare("SELECT name, serial_number FROM med_equipment WHERE id = ?");
    $stmt_get->execute([$equipment_id]);
    $equip_info = $stmt_get->fetch(PDO::FETCH_ASSOC);
    $equip_name_for_log = $equip_info ? "{$equip_info['name']} (SN: {$equip_info['serial_number']})" : "ID: {$equipment_id}";
    // ◀️ --- (จบส่วนดึงข้อมูล Log) --- ◀️


    // 6. ดำเนินการลบ
    $sql_delete = "DELETE FROM med_equipment WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$equipment_id]);

    // 7. ตรวจสอบว่าลบสำเร็จหรือไม่
    if ($stmt_delete->rowCount() > 0) {
        
        // ◀️ --- (เพิ่มส่วน Log) --- ◀️
        $admin_user_id = $_SESSION['user_id'] ?? null;
        $admin_user_name = $_SESSION['full_name'] ?? 'System';
        $log_desc = "Admin '{$admin_user_name}' (ID: {$admin_user_id}) ได้ลบอุปกรณ์: '{$equip_name_for_log}'";
        log_action($pdo, $admin_user_id, 'delete_equipment', $log_desc);
        // ◀️ --- (จบส่วน Log) --- ◀️

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