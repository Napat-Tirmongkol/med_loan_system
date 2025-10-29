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
    $equipment_id  = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
    $name          = isset($_POST['name']) ? trim($_POST['name']) : '';
    $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
    $status        = isset($_POST['status']) ? $_POST['status'] : '';

    if (empty($serial_number)) $serial_number = null; // อนุญาตให้ Serial ว่าง

    // 6. ตรวจสอบข้อมูล
    if ($equipment_id == 0 || empty($name) || empty($status)) {
        $response['message'] = 'ข้อมูลไม่ครบถ้วน (ID, Name, Status)';
        echo json_encode($response);
        exit;
    }

    // 7. ตรวจสอบว่า status ที่ส่งมาถูกต้อง
    // (ป้องกันการพยายามเปลี่ยนเป็น 'borrowed' จากหน้านี้)
    if ($status != 'available' && $status != 'maintenance' && $status != 'borrowed') {
         $response['message'] = 'สถานะที่เลือกไม่ถูกต้อง';
         echo json_encode($response);
         exit;
    }
    
    // (ตรรกะป้องกันการแก้ไขสถานะ 'borrowed' ถ้ามันไม่ได้ 'borrowed' มาก่อน)
    try {
        $stmt_check = $pdo->prepare("SELECT status FROM med_equipment WHERE id = ?");
        $stmt_check->execute([$equipment_id]);
        $current_status = $stmt_check->fetchColumn();

        if ($current_status != 'borrowed' && $status == 'borrowed') {
            throw new Exception("ไม่สามารถเปลี่ยนสถานะเป็น 'ถูกยืม' จากหน้านี้ได้");
        }
        
        // 8. ดำเนินการ UPDATE
        $sql = "UPDATE med_equipment 
                SET name = ?, serial_number = ?, status = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $serial_number, $status, $equipment_id]);

        $response['status'] = 'success';
        $response['message'] = 'บันทึกการเปลี่ยนแปลงสำเร็จ';

    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
             $response['message'] = 'เลขซีเรียล (Serial Number) นี้มีในระบบแล้ว';
        } else {
             $response['message'] = 'เกิดข้อผิดพลาด DB: ' . $e->getMessage();
        }
    } catch (Exception $e) {
         $response['message'] = $e->getMessage();
    }

} else {
    $response['message'] = 'ต้องใช้วิธี POST เท่านั้น';
}

// 9. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>