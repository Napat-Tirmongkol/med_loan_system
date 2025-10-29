<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // ตอบกลับเป็น JSON Error
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ']);
    exit;
}

// 3. ตั้งค่า Header ให้ตอบกลับเป็น JSON
header('Content-Type: application/json');

// 4. สร้างตัวแปรสำหรับเก็บคำตอบ
$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];

// 5. ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name    = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $contact_info = isset($_POST['contact_info']) ? trim($_POST['contact_info']) : null;

    if (empty($full_name)) {
        $response['message'] = 'กรุณากรอก ชื่อ-สกุล';
        echo json_encode($response);
        exit;
    }
    
    if (empty($contact_info)) $contact_info = null;

    // 6. ดำเนินการ INSERT
    try {
        $sql = "INSERT INTO med_borrowers (full_name, contact_info) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $contact_info]);

        // 7. ถ้าสำเร็จ ให้เปลี่ยนคำตอบ
        $response['status'] = 'success';
        $response['message'] = 'บันทึกข้อมูลสำเร็จ';

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