<?php
// request_borrow_process.php
// (เวอร์ชันสะอาด - สำหรับใช้งานจริง)

include('includes/check_student_session.php'); // "ยาม" นักศึกษา
require_once('db_connect.php'); //

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];

// 1. ตรวจสอบว่า POST มาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. รับข้อมูลจากฟอร์ม AJAX
    $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
    
    // (สำคัญ) ดึง ID นักศึกษาจาก Session ที่ถูกต้อง
    $student_id = $_SESSION['student_id']; 
    
    $quantity = 1; // (*** แก้ไข: กำหนดค่า "จำนวน" เป็น 1 เสมอ ***)
    $reason = isset($_POST['reason_for_borrowing']) ? trim($_POST['reason_for_borrowing']) : null;
    $staff_id = isset($_POST['lending_staff_id']) ? (int)$_POST['lending_staff_id'] : 0;
    $due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : null;

    // (แปลง staff_id เป็น int)
    $staff_id_int = (int)$staff_id;

    // 3. ตรวจสอบข้อมูล
    if ($equipment_id == 0 || $student_id == 0 || empty($reason) || $staff_id_int == 0 || empty($due_date)) {
        $response['message'] = 'ข้อมูลที่ส่งมาไม่ครบถ้วน (เหตุผล, เจ้าหน้าที่, หรือวันที่คืน)';
        echo json_encode($response);
        exit;
    }

    // 4. บันทึกลงฐานข้อมูล (med_transactions)
    try {
        // ตรวจสอบก่อนว่าอุปกรณ์ยัง "ว่าง" (available) หรือไม่
        $stmt_check = $pdo->prepare("SELECT status FROM med_equipment WHERE id = ?");
        $stmt_check->execute([$equipment_id]);
        $current_status = $stmt_check->fetchColumn();

        if ($current_status != 'available') {
            throw new Exception("อุปกรณ์นี้ไม่พร้อมให้ยืม (อาจถูกยืมไปแล้ว)");
        }

        // 5. INSERT คำขอ (Transaction) ใหม่
        $sql = "INSERT INTO med_transactions 
                    (equipment_id, borrower_student_id, quantity, reason_for_borrowing, lending_staff_id, due_date, status, approval_status) 
                VALUES 
                    (?, ?, ?, ?, ?, ?, 'borrowed', 'pending')";
        
        $stmt = $pdo->prepare($sql);
        // (ใช้ $student_id ที่ดึงจาก Session)
        $stmt->execute([
            $equipment_id, $student_id, $quantity, $reason, $staff_id, $due_date // (ส่ง $quantity ที่เป็น 1)
    ]);

        $response['status'] = 'success';
        $response['message'] = 'ส่งคำขอสำเร็จ';

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

} else {
    $response['message'] = 'ต้องใช้วิธี POST เท่านั้น';
}

// 6. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>