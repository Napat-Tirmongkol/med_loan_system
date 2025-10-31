<?php
// get_borrow_form_data.php
// (อัปเดต: ดึงข้อมูลจาก med_students)
// ดึงข้อมูลสำหรับ Popup "ยืม" (ที่ Admin กด)

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
// (เราใช้ 'check_session.php' เพราะนี่คือฟังก์ชันของ Admin/Staff)
include('includes/check_session_ajax.php');
require_once('db_connect.php'); //

// 2. ตั้งค่า Header ให้ตอบกลับเป็น JSON
header('Content-Type: application/json');

// 3. สร้างตัวแปรสำหรับเก็บคำตอบ
$response = [
    'status' => 'error', 
    'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ',
    'equipment' => null, 
    'borrowers' => []  // (***สำคัญ: เรายังใช้ชื่อ 'borrowers' เหมือนเดิม)
];

// 4. รับ ID อุปกรณ์จาก URL
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($equipment_id == 0) {
    $response['message'] = 'ไม่ได้ระบุ ID อุปกรณ์';
    echo json_encode($response);
    exit;
}

try {
    // 5.1 ดึงข้อมูลอุปกรณ์ (เหมือนเดิม)
    $stmt_equip = $pdo->prepare("SELECT id, name, serial_number FROM med_equipment WHERE id = ? AND status = 'available'");
    $stmt_equip->execute([$equipment_id]);
    $equipment = $stmt_equip->fetch(PDO::FETCH_ASSOC);

    if (!$equipment) {
        $response['message'] = 'ไม่พบอุปกรณ์ หรืออุปกรณ์นี้ไม่พร้อมให้ยืม';
        echo json_encode($response);
        exit;
    }
    $response['equipment'] = $equipment;

    // 5.2 (SQL ใหม่) ดึงรายชื่อผู้ใช้ทั้งหมดจาก med_students
    $stmt_borrowers = $pdo->prepare("SELECT id, full_name, phone_number FROM med_students ORDER BY full_name ASC");
    $stmt_borrowers->execute();
    $borrowers_list = $stmt_borrowers->fetchAll(PDO::FETCH_ASSOC);
    
    // (เปลี่ยน 'phone_number' เป็น 'contact_info' เพื่อให้ JS เดิมทำงานได้)
    $borrowers_formatted = [];
    foreach ($borrowers_list as $person) {
        $borrowers_formatted[] = [
            'id' => $person['id'],
            'full_name' => $person['full_name'],
            'contact_info' => $person['phone_number'] // (แปลงชื่อคอลัมน์)
        ];
    }
    
    $response['borrowers'] = $borrowers_formatted; // (ส่งกลับในชื่อ 'borrowers')
    $response['status'] = 'success';
    $response['message'] = 'ดึงข้อมูลสำเร็จ';

} catch (PDOException $e) {
    $response['message'] = 'เกิดข้อผิดพลาด DB: ' . $e->getMessage();
}

// 6. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>