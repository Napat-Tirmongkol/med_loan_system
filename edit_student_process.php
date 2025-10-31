<?php
// edit_student_process.php
// (ไฟล์ใหม่ที่เปลี่ยนชื่อมาจาก edit_borrower_process.php)
// รับข้อมูลจาก Popup 'แก้ไขข้อมูลผู้ใช้'

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
    $student_id   = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $full_name    = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
    $student_personnel_id = isset($_POST['student_personnel_id']) ? trim($_POST['student_personnel_id']) : null; // ◀️ เพิ่ม 1

    if ($student_id == 0 || empty($full_name)) {
        $response['message'] = 'ข้อมูลไม่ครบถ้วน (ID หรือ ชื่อ-สกุล)';
        echo json_encode($response);
        exit;
    }

    if (empty($phone_number)) $phone_number = null;
    if (empty($student_personnel_id)) $student_personnel_id = null; // ◀️ เพิ่ม 2

    // 6. (SQL ใหม่) ดำเนินการ UPDATE ตาราง med_students
    try {
        $sql = "UPDATE med_students 
                SET full_name = ?, phone_number = ?, student_personnel_id = ?
                WHERE id = ?"; // ◀️ เพิ่ม 3
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $phone_number, $student_personnel_id, $student_id]); // ◀️ เพิ่ม 4

        // 7. ถ้าสำเร็จ ให้เปลี่ยนคำตอบ
        $response['status'] = 'success';
        $response['message'] = 'บันทึกการเปลี่ยนแปลงสำเร็จ';

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