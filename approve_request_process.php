<?php
// approve_request_process.php
// รับคำขออนุมัติจาก Admin

include('includes/check_session_ajax.php');
require_once('db_connect.php');
require_once('includes/log_function.php'); // ◀️ (เพิ่ม) เรียกใช้ Log

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request'];

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

// 2. รับ ID ของ Transaction (คำขอ)
$transaction_id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
if ($transaction_id == 0) {
    $response['message'] = 'Invalid Transaction ID';
    echo json_encode($response);
    exit;
}

try {
    // 3. เริ่ม Database Transaction
    $pdo->beginTransaction();

    // 4. ดึง ID อุปกรณ์ และสถานะคำขอ
    $stmt_get = $pdo->prepare("SELECT equipment_type_id, approval_status FROM med_transactions WHERE id = ?");
    $stmt_get->execute([$transaction_id]);
    $transaction = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception("ไม่พบคำขอนี้");
    }
    if ($transaction['approval_status'] != 'pending') {
        throw new Exception("คำขอนี้ถูกดำเนินการไปแล้ว (ไม่ใช่ Pending)");
    }
    
    $type_id = $transaction['equipment_type_id'];

    // 5. หา item ที่ว่างจาก type นี้
    $stmt_find_item = $pdo->prepare("SELECT id FROM med_equipment_items WHERE type_id = ? AND status = 'available' LIMIT 1 FOR UPDATE");
    $stmt_find_item->execute([$type_id]);
    $available_item_id = $stmt_find_item->fetchColumn();

    if (!$available_item_id) {
        $stmt_reject = $pdo->prepare("UPDATE med_transactions SET approval_status = 'rejected', status = 'returned' WHERE id = ?");
        $stmt_reject->execute([$transaction_id]);
        $pdo->commit();
        throw new Exception("ไม่อนุมัติ: อุปกรณ์ประเภทนี้ไม่ว่างแล้ว");
    }

    // 6. (อนุมัติ) อัปเดต med_equipment_items
    $stmt_item = $pdo->prepare("UPDATE med_equipment_items SET status = 'borrowed' WHERE id = ?");
    $stmt_item->execute([$available_item_id]);

    // 7. (อนุมัติ) อัปเดต med_transactions
    $stmt_trans = $pdo->prepare("UPDATE med_transactions SET approval_status = 'approved', borrow_date = NOW(), equipment_id = ? WHERE id = ?");
    $stmt_trans->execute([$available_item_id, $transaction_id]);

    // ◀️ --- (เพิ่มส่วน Log) --- ◀️
    if ($stmt_item->rowCount() > 0 && $stmt_trans->rowCount() > 0) {
        $admin_user_id = $_SESSION['user_id'] ?? null;
        $admin_user_name = $_SESSION['full_name'] ?? 'System';
        $log_desc = "Admin '{$admin_user_name}' (ID: {$admin_user_id}) ได้อนุมัติคำขอ (TID: {$transaction_id}) สำหรับอุปกรณ์ (Type ID: {$type_id}, Item ID: {$available_item_id})";
        log_action($pdo, $admin_user_id, 'approve_request', $log_desc);
    }
    // ◀️ --- (จบส่วน Log) --- ◀️

    // 8. ยืนยัน
    $pdo->commit();
    $response = ['status' => 'success', 'message' => 'อนุมัติคำขอเรียบร้อย!'];

} catch (Exception $e) {
    // 9. ย้อนกลับ
    $pdo->rollBack();
    $response['message'] = $e->getMessage(); // ◀️ (แก้ไข)
}

echo json_encode($response);
exit;
?>