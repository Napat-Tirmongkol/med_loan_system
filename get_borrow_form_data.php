<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตั้งค่า Header ให้ตอบกลับเป็น JSON
header('Content-Type: application/json');

// 3. สร้างตัวแปรสำหรับเก็บคำตอบ
$response = [
    'status' => 'error', 
    'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ',
    'equipment' => null, // สำหรับเก็บข้อมูลอุปกรณ์
    'borrowers' => []  // สำหรับเก็บรายชื่อผู้ยืม
];

// 4. รับ ID อุปกรณ์จาก URL
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($equipment_id == 0) {
    $response['message'] = 'ไม่ได้ระบุ ID อุปกรณ์';
    echo json_encode($response);
    exit;
}

try {
    // 5.1 ดึงข้อมูลอุปกรณ์ที่กำลังจะยืม (ต้อง "ว่าง" เท่านั้น)
    $stmt_equip = $pdo->prepare("SELECT id, name, serial_number FROM med_equipment WHERE id = ? AND status = 'available'");
    $stmt_equip->execute([$equipment_id]);
    $equipment = $stmt_equip->fetch(PDO::FETCH_ASSOC);

    if (!$equipment) {
        $response['message'] = 'ไม่พบอุปกรณ์ หรืออุปกรณ์นี้ไม่พร้อมให้ยืม (อาจถูกยืมไปแล้ว)';
        echo json_encode($response);
        exit;
    }
    
    $response['equipment'] = $equipment;

    // 5.2 ดึงรายชื่อผู้ยืมทั้งหมด (จาก med_borrowers)
    $stmt_borrowers = $pdo->prepare("SELECT id, full_name, contact_info FROM med_borrowers ORDER BY full_name ASC");
    $stmt_borrowers->execute();
    $borrowers = $stmt_borrowers->fetchAll(PDO::FETCH_ASSOC);
    
    $response['borrowers'] = $borrowers;
    $response['status'] = 'success';
    $response['message'] = 'ดึงข้อมูลสำเร็จ';

} catch (PDOException $e) {
    $response['message'] = 'เกิดข้อผิดพลาด DB: ' . $e->getMessage();
}

// 6. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>