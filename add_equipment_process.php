<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session_ajax.php'); // <-- เปลี่ยนชื่อ "ยาม"
require_once('db_connect.php');

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

    // 5. รับข้อมูลจากฟอร์ม
    $name          = isset($_POST['name']) ? trim($_POST['name']) : '';
    $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
    $description   = isset($_POST['description']) ? trim($_POST['description']) : null;

    if (empty($name)) {
        $response['message'] = 'กรุณากรอกชื่ออุปกรณ์';
        echo json_encode($response);
        exit;
    }

    if (empty($serial_number)) $serial_number = null;
    if (empty($description)) $description = null;

    // 6. ดำเนินการ INSERT
    try {
        // สถานะ 'available' เป็นค่า DEFAULT
        $sql = "INSERT INTO med_equipment (name, serial_number, description)
                VALUES (?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $serial_number, $description]);

        // 7. ถ้าสำเร็จ ให้เปลี่ยนคำตอบ
        $response['status'] = 'success';
        $response['message'] = 'เพิ่มอุปกรณ์ใหม่สำเร็จ';

    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
             $response['message'] = 'เลขซีเรียล (Serial Number) นี้มีในระบบแล้ว';
        } else {
             $response['message'] = 'เกิดข้อผิดพลาด DB: ' . $e->getMessage();
        }
    }

} else {
    $response['message'] = 'ต้องใช้วิธี POST เท่านั้น';
}

// 8. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>