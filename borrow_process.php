<?php
// borrow_process.php
// บันทึกการยืมที่ Admin/Staff เป็นคนกดให้

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session_ajax.php');
require_once('db_connect.php');
require_once('includes/log_function.php'); // ◀️ (เพิ่ม) เรียกใช้ Log

// 2. ตั้งค่า Header
header('Content-Type: application/json');

// 3. สร้างตัวแปรสำหรับเก็บคำตอบ
$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];

// 4. ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 5. รับข้อมูลจากฟอร์ม
    $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0; 
    $borrower_student_id = isset($_POST['borrower_id']) ? (int)$_POST['borrower_id'] : 0;
    $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : null;

    if ($equipment_id == 0 || $borrower_student_id == 0 || $due_date == null) {
        $response['message'] = 'ข้อมูลไม่ครบถ้วน (ผู้ยืม หรือ วันที่คืน)';
        echo json_encode($response);
        exit;
    }

    // 6. เริ่ม Transaction (การยืม)
    try {
        $pdo->beginTransaction();

        // 6.1 UPDATE อุปกรณ์เป็น 'borrowed'
        $sql_update = "UPDATE med_equipment SET status = 'borrowed' WHERE id = ? AND status = 'available'";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$equipment_id]);

        if ($stmt_update->rowCount() == 0) {
            throw new Exception("ไม่สามารถยืมอุปกรณ์ได้ อุปกรณ์อาจถูกยืมไปแล้ว (สถานะไม่ 'available')");
        }

        // 6.2 INSERT ประวัติการยืม
        $sql_insert = "INSERT INTO med_transactions 
                        (equipment_id, borrower_student_id, due_date, status, approval_status, quantity, lending_staff_id) 
                       VALUES 
                        (?, ?, ?, 'borrowed', 'staff_added', 1, ?)"; // ◀️ (เพิ่ม lending_staff_id)
        $stmt_insert = $pdo->prepare($sql_insert);
        
        $admin_user_id = $_SESSION['user_id'] ?? null; // ◀️ (ดึง ID Admin ที่กดยืม)
        
        $stmt_insert->execute([$equipment_id, $borrower_student_id, $due_date, $admin_user_id]);

        // ◀️ --- (เพิ่มส่วน Log) --- ◀️
        if ($stmt_insert->rowCount() > 0) {
            $admin_user_name = $_SESSION['full_name'] ?? 'System';
            $log_desc = "Admin '{$admin_user_name}' (ID: {$admin_user_id}) ได้บันทึกการยืม (EID: {$equipment_id}) ให้กับผู้ใช้ (SID: {$borrower_student_id})";
            log_action($pdo, $admin_user_id, 'create_borrow_staff', $log_desc);
        }
        // ◀️ --- (จบส่วน Log) --- ◀️

        $pdo->commit();

        // 7. ถ้าสำเร็จ
        $response['status'] = 'success';
        $response['message'] = 'บันทึกการยืมสำเร็จ';

    } catch (Exception $e) {
        $pdo->rollBack(); 
        $response['message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }

} else {
    $response['message'] = 'ต้องใช้วิธี POST เท่านั้น';
}

// 8. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>