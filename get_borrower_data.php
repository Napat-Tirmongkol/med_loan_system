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
$response = [
    'status' => 'error',
    'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ',
    'borrower' => null
];

// 4. รับ ID ผู้ยืมจาก URL
$borrower_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($borrower_id == 0) {
    $response['message'] = 'ไม่ได้ระบุ ID ผู้ยืม';
    echo json_encode($response);
    exit;
}

try {
    // 5. ดึงข้อมูลผู้ยืม
    $stmt = $pdo->prepare("SELECT * FROM med_borrowers WHERE id = ?");
    $stmt->execute([$borrower_id]);
    $borrower = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($borrower) {
        $response['status'] = 'success';
        $response['borrower'] = $borrower;
        $response['message'] = 'ดึงข้อมูลสำเร็จ';
    } else {
        $response['message'] = 'ไม่พบข้อมูลผู้ยืม';
    }

} catch (PDOException $e) {
    $response['message'] = 'เกิดข้อผิดพลาด DB: ' . $e->getMessage();
}

// 6. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>