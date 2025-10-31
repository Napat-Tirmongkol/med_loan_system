<?php
// add_student_process.php
// รับข้อมูลจาก Popup 'เพิ่มผู้ใช้งาน (โดย Admin)'

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session_ajax.php');
require_once('db_connect.php'); //

// 2. ตรวจสอบสิทธิ์ Admin และตั้งค่า Header
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ']);
    exit;
}
header('Content-Type: application/json');

// 3. สร้างตัวแปรสำหรับเก็บคำตอบ
$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];

// 4. ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 5. รับข้อมูลจากฟอร์ม AJAX (ตามที่เราสร้างไว้ใน manage_students.php)
    $full_name    = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;

    if (empty($full_name)) {
        $response['message'] = 'กรุณากรอก ชื่อ-สกุล';
        echo json_encode($response);
        exit;
    }
    
    if (empty($phone_number)) $phone_number = null;

    // 6. (SQL ใหม่) ดำเนินการ INSERT ลง med_students
    try {
        // เราจะตั้ง line_user_id = NULL และ status = 'other' 
        // เพื่อบอกว่าคนนี้ Admin เป็นคนเพิ่มเอง
        $sql = "INSERT INTO med_students (full_name, phone_number, status, line_user_id, student_personnel_id) 
                VALUES (?, ?, 'other', NULL, '(Staff-Added)')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $phone_number]);

        // 7. ถ้าสำเร็จ ให้เปลี่ยนคำตอบ
        $response['status'] = 'success';
        $response['message'] = 'เพิ่มผู้ใช้งานใหม่สำเร็จ';

    } catch (PDOException $e) {
        $response['message'] = 'เกิดข้อผิดพลาด DB: ' . $e->getMessage();
    }

} else {
    $response['message'] = 'ต้องใช้วิธี POST เท่านั้น';
}

// 8. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>