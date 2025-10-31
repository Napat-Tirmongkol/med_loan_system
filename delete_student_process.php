<?php
// delete_student_process.php
// (อัปเกรต: เพิ่มการบันทึก Log)

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');
require_once('includes/log_function.php'); // ◀️ (ใหม่) เรียกใช้ Log

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("คุณไม่มีสิทธิ์ดำเนินการ <a href='index.php'>กลับหน้าหลัก</a>");
}

// 3. รับ ID ผู้ใช้งาน
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id == 0) {
    header("Location: manage_students.php?error=no_id");
    exit;
}

// 4. ตรวจสอบ Foreign Key
try {
    $sql_check = "SELECT COUNT(*) FROM med_transactions WHERE borrower_student_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$student_id]);
    $transaction_count = $stmt_check->fetchColumn();

    if ($transaction_count > 0) {
        header("Location: manage_students.php?error=fk_constraint&id=" . $student_id);
        exit;
    }

    // ◀️ --- (ใหม่: ส่วน Log) --- ◀️
    // (ดึงข้อมูลผู้ใช้ "ก่อน" ที่จะลบ)
    $stmt_get = $pdo->prepare("SELECT full_name, line_user_id FROM med_students WHERE id = ?");
    $stmt_get->execute([$student_id]);
    $student_info = $stmt_get->fetch(PDO::FETCH_ASSOC);
    $student_name_for_log = $student_info ? $student_info['full_name'] : "ID: {$student_id}";
    // (แยประเภท Log ระหว่าง User ที่ Admin เพิ่มเอง หรือ User ที่มาจาก LINE)
    $log_action_type = $student_info && $student_info['line_user_id'] ? 'delete_user_line' : 'delete_user_staff';
    // ◀️ --- (จบส่วนดึงข้อมูล Log) --- ◀️

    // 6. ดำเนินการลบ
    $sql_delete = "DELETE FROM med_students WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$student_id]);

    // 7. ตรวจสอบ
    if ($stmt_delete->rowCount() > 0) {
        
        // ◀️ --- (ใหม่: บันทึก Log) --- ◀️
        $admin_user_id = $_SESSION['user_id'] ?? null;
        $admin_user_name = $_SESSION['full_name'] ?? 'System';
        $log_desc = "Admin '{$admin_user_name}' (ID: {$admin_user_id}) ได้ลบผู้ใช้งาน: '{$student_name_for_log}' (SID: {$student_id})";
        log_action($pdo, $admin_user_id, $log_action_type, $log_desc);
        // ◀️ --- (จบส่วน Log) --- ◀️

        header("Location: manage_students.php?delete=success");
        exit;
    } else {
        header("Location: manage_students.php?error=not_found&id=" . $student_id);
        exit;
    }

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage() . " <a href='manage_students.php'>กลับหน้าหลัก</a>");
}
?>