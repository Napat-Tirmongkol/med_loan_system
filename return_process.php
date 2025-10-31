<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session_ajax.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ (อนุญาต Admin และ Employee) และตั้งค่า Header
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'employee'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ']);
    exit;
}
header('Content-Type: application/json');

// 3. สร้างตัวแปรสำหรับเก็บคำตอบ
$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];

// 4. ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 5. รับข้อมูลจากฟอร์ม (ที่ส่งมาจาก AJAX)
    $equipment_id   = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
    $transaction_id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;

    if ($equipment_id == 0 || $transaction_id == 0) {
        $response['message'] = 'ข้อมูล Transaction ID หรือ Equipment ID ไม่ครบถ้วน';
        echo json_encode($response);
        exit;
    }

    // 6. เริ่ม Transaction (การคืน)
    try {
        $pdo->beginTransaction();

        // 6.1 UPDATE อุปกรณ์เป็น 'available'
        $sql_update_equip = "UPDATE med_equipment SET status = 'available' WHERE id = ? AND status = 'borrowed'";
        $stmt_update_equip = $pdo->prepare($sql_update_equip);
        $stmt_update_equip->execute([$equipment_id]);
        
        // 6.2 UPDATE ประวัติการยืมเป็น 'returned'
        $sql_update_trans = "UPDATE med_transactions SET status = 'returned', return_date = NOW() WHERE id = ? AND status = 'borrowed'";
        $stmt_update_trans = $pdo->prepare($sql_update_trans);
        $stmt_update_trans->execute([$transaction_id]);
        
        // 6.3 ตรวจสอบว่าสำเร็จทั้งคู่
        if ($stmt_update_equip->rowCount() == 0 || $stmt_update_trans->rowCount() == 0) {
            throw new Exception("ไม่สามารถคืนอุปกรณ์ได้ (อาจถูกคืนไปแล้ว หรือข้อมูลผิดพลาด)");
        }
        
        $pdo->commit();
        
        // 7. ถ้าสำเร็จ ให้เปลี่ยนคำตอบ
        $response['status'] = 'success';
        $response['message'] = 'รับคืนอุปกรณ์เรียบร้อย';

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