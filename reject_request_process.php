<?php
// reject_request_process.php
// รับคำขอปฏิเสธจาก Admin

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

// 2. รับ ID ของ Transaction
$transaction_id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
if ($transaction_id == 0) {
    $response['message'] = 'Invalid Transaction ID';
    echo json_encode($response);
    exit;
}

try {
    // 3. อัปเดตสถานะเป็น 'rejected'
    $stmt = $pdo->prepare("UPDATE med_transactions 
                          SET approval_status = 'rejected', status = 'returned' 
                          WHERE id = ? AND approval_status = 'pending'");
    $stmt->execute([$transaction_id]);
    
    if ($stmt->rowCount() > 0) {
        
        // ◀️ --- (เพิ่มส่วน Log) --- ◀️
        $admin_user_id = $_SESSION['user_id'] ?? null;
        $admin_user_name = $_SESSION['full_name'] ?? 'System';
        $log_desc = "Admin '{$admin_user_name}' (ID: {$admin_user_id}) ได้ปฏิเสธคำขอ (TID: {$transaction_id})";
        log_action($pdo, $admin_user_id, 'reject_request', $log_desc);
        // ◀️ --- (จบส่วน Log) --- ◀️

        $response = ['status' => 'success', 'message' => 'ปฏิเสธคำขอเรียบร้อย'];
    } else {
        throw new Exception("ไม่พบคำขอ (หรือถูกดำเนินการไปแล้ว)");
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>