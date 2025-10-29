<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ***** ตรวจสอบสิทธิ์ Admin *****
//    (ป้องกันอีกชั้นเผื่อมีคนส่งข้อมูลมาหน้านี้ตรงๆ)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("คุณไม่มีสิทธิ์ดำเนินการ <a href='index.php'>กลับหน้าหลัก</a>");
}

// 3. ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. รับข้อมูลจากฟอร์ม (จาก <input type="hidden">)
    $equipment_id   = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
    $transaction_id = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;

    // 5. ตรวจสอบข้อมูลเบื้องต้น
    if ($equipment_id == 0 || $transaction_id == 0) {
        die("ข้อมูลไม่ครบถ้วน <a href='index.php'>กลับหน้าหลัก</a>");
    }

    // 6. เริ่ม Transaction
    try {
        // 6.1 เริ่มการ "ห่อ" คำสั่ง
        $pdo->beginTransaction();

        // 6.2 คำสั่งที่ 1: UPDATE สถานะอุปกรณ์ (med_equipment)
        //     เปลี่ยนสถานะกลับเป็น 'available'
        $sql_update_equip = "UPDATE med_equipment 
                             SET status = 'available' 
                             WHERE id = ? AND status = 'borrowed'"; // คืนได้เฉพาะของที่ถูกยืม
        $stmt_update_equip = $pdo->prepare($sql_update_equip);
        $stmt_update_equip->execute([$equipment_id]);

        // 6.3 คำสั่งที่ 2: UPDATE ประวัติการยืม (med_transactions)
        //     เปลี่ยนสถานะเป็น 'returned' และบันทึกวันที่คืน (NOW())
        $sql_update_trans = "UPDATE med_transactions 
                             SET status = 'returned', return_date = NOW()
                             WHERE id = ? AND status = 'borrowed'";
        $stmt_update_trans = $pdo->prepare($sql_update_trans);
        $stmt_update_trans->execute([$transaction_id]);

        // 6.4 ตรวจสอบว่าสำเร็จทั้งคู่หรือไม่
        // (ถ้าแถวใดแถวหนึ่งไม่ถูกอัปเดต เช่น ของไม่ได้ถูกยืมอยู่)
        if ($stmt_update_equip->rowCount() == 0 || $stmt_update_trans->rowCount() == 0) {
            // ถ้ามีบางอย่างผิดปกติ (เช่น อุปกรณ์ไม่ได้ถูกยืม)
            throw new Exception("ไม่สามารถคืนอุปกรณ์ได้ อาจมีข้อผิดพลาดของข้อมูล");
        }

        // 6.5 ถ้าทุกอย่างสำเร็จ
        $pdo->commit();

        // 7. ส่งผู้ใช้กลับไปหน้าหลัก
        header("Location: index.php?return=success");
        exit;

    } catch (Exception $e) {
        // 8. หากเกิดข้อผิดพลาด
        $pdo->rollBack(); // "ย้อนกลับ" การเปลี่ยนแปลงทั้งหมด

        die("เกิดข้อผิดพลาดในการรับคืน: " . $e->getMessage() . " <a href='index.php'>กลับหน้าหลัก</a>");
    }

} else {
    // ถ้าไม่ได้เข้ามาหน้านี้ผ่านการ POST
    header("Location: index.php");
    exit;
}
?>