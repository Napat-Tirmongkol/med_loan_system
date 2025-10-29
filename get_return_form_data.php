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
    'transaction' => null // สำหรับเก็บข้อมูลการยืม
];

// 4. รับ ID อุปกรณ์จาก URL
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($equipment_id == 0) {
    $response['message'] = 'ไม่ได้ระบุ ID อุปกรณ์';
    echo json_encode($response);
    exit;
}

try {
    // 5. ดึงข้อมูลการยืม (Transaction) ที่ยัง "active" (status='borrowed')
    //    (เรา JOIN 3 ตารางเพื่อเอาชื่อมาแสดง)
    $sql = "SELECT 
                t.id as transaction_id, 
                t.borrow_date, 
                t.due_date,
                e.name as equipment_name, 
                e.serial_number as equipment_serial,
                b.full_name as borrower_name,
                b.contact_info as borrower_contact
            FROM med_transactions t
            JOIN med_equipment e ON t.equipment_id = e.id
            JOIN med_borrowers b ON t.borrower_id = b.id
            WHERE t.equipment_id = ? AND t.status = 'borrowed'
            ORDER BY t.borrow_date DESC 
            LIMIT 1"; // เอาเฉพาะรายการล่าสุดที่ยังไม่คืน

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$equipment_id]);
    $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction_data) {
        $response['status'] = 'success';
        $response['transaction'] = $transaction_data;
        $response['message'] = 'ดึงข้อมูลสำเร็จ';
    } else {
        $response['message'] = 'ไม่พบข้อมูลการยืม (อาจถูกคืนไปแล้ว)';
    }

} catch (PDOException $e) {
    $response['message'] = 'เกิดข้อผิดพลาด DB: ' . $e->getMessage();
}

// 6. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>