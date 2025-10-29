<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
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
    $borrower_id  = isset($_POST['borrower_id']) ? (int)$_POST['borrower_id'] : 0;
    $full_name    = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $contact_info = isset($_POST['contact_info']) ? trim($_POST['contact_info']) : null;

    if ($borrower_id == 0 || empty($full_name)) {
        $response['message'] = 'ข้อมูลไม่ครบถ้วน (ID หรือ ชื่อ-สกุล)';
        echo json_encode($response);
        exit;
    }

    if (empty($contact_info)) $contact_info = null;

    // 6. ดำเนินการ UPDATE
    try {
        $sql = "UPDATE med_borrowers
                SET full_name = ?, contact_info = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $contact_info, $borrower_id]);

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