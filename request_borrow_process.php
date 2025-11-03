<?php
// request_borrow_process.php
// (อัปเดต V5 - รองรับระบบ Types/Items)

include('includes/check_student_session.php'); 
require_once('db_connect.php'); 

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ◀️ (แก้ไข) รับ Type ID
    $type_id = isset($_POST['type_id']) ? (int)$_POST['type_id'] : 0; 
    
    $student_id = $_SESSION['student_id']; 
    $quantity = 1; 
    $reason = isset($_POST['reason_for_borrowing']) ? trim($_POST['reason_for_borrowing']) : null;
    $staff_id = isset($_POST['lending_staff_id']) ? (int)$_POST['lending_staff_id'] : 0;
    $due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : null;

    if ($type_id == 0 || $student_id == 0 || empty($reason) || $staff_id == 0 || empty($due_date)) {
        $response['message'] = 'ข้อมูลที่ส่งมาไม่ครบถ้วน (เหตุผล, เจ้าหน้าที่, หรือวันที่คืน)';
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. ค้นหา "ชิ้น" อุปกรณ์ (Item) ที่ว่าง
        $stmt_find = $pdo->prepare("SELECT id FROM med_equipment_items 
                                    WHERE type_id = ? AND status = 'available' 
                                    LIMIT 1 FOR UPDATE"); 
        $stmt_find->execute([$type_id]);
        $available_item = $stmt_find->fetch(PDO::FETCH_ASSOC);

        if (!$available_item) {
            $stmt_check_type = $pdo->prepare("SELECT available_quantity FROM med_equipment_types WHERE id = ?");
            $stmt_check_type->execute([$type_id]);
            $avail_count = $stmt_check_type->fetchColumn();
            if ($avail_count <= 0) {
                throw new Exception("อุปกรณ์ประเภทนี้หมด (ไม่มีชิ้นที่ว่าง)");
            } else {
                throw new Exception("เกิดข้อผิดพลาด: จำนวนคงเหลือไม่ตรงกัน กรุณาติดต่อ Admin");
            }
        }
        
        $item_id_to_borrow = $available_item['id']; 

        // 2. อัปเดตสถานะ "ชิ้น" อุปกรณ์ (items) เป็น 'borrowed' (เพื่อจอง)
        $stmt_item = $pdo->prepare("UPDATE med_equipment_items SET status = 'borrowed' WHERE id = ? AND status = 'available'");
        $stmt_item->execute([$item_id_to_borrow]);

        if ($stmt_item->rowCount() == 0) {
            throw new Exception("ไม่สามารถจองอุปกรณ์ได้ (อาจถูกยืมไปแล้ว)");
        }
        
        // 3. อัปเดตจำนวนคงเหลือใน "ประเภท" (types)
        $stmt_type = $pdo->prepare("UPDATE med_equipment_types SET available_quantity = available_quantity - 1 WHERE id = ? AND available_quantity > 0");
        $stmt_type->execute([$type_id]);

        // 4. INSERT คำขอ (Transaction) ใหม่
        //    (เราจะ INSERT ทั้ง type_id และ item_id)
        //    (และสมมติว่าคุณได้ DROP equipment_id ออกไปแล้ว)
        $sql = "INSERT INTO med_transactions 
                    (type_id, item_id, borrower_student_id, quantity, reason_for_borrowing, lending_staff_id, due_date, status, approval_status) 
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, 'borrowed', 'pending')";
        
        $stmt_trans = $pdo->prepare($sql);
        $stmt_trans->execute([
            $type_id, 
            $item_id_to_borrow, 
            $student_id, 
            $quantity, 
            $reason, 
            $staff_id, 
            $due_date
        ]);
        
        $pdo->commit();
        $response['status'] = 'success';
        $response['message'] = 'ส่งคำขอสำเร็จ';

    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = $e->getMessage(); // ◀️ แก้ไข .getMessage
    }

} else {
    $response['message'] = 'ต้องใช้วิธี POST เท่านั้น';
}

echo json_encode($response);
exit;
?>