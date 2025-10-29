<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. รับข้อมูลจากฟอร์ม
    // (int) เพื่อความปลอดภัย (แปลงเป็นตัวเลข)
    $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0; 
    $borrower_id  = isset($_POST['borrower_id']) ? (int)$_POST['borrower_id'] : 0;
    $due_date     = isset($_POST['due_date']) ? $_POST['due_date'] : null;

    // 4. ตรวจสอบข้อมูลเบื้องต้น
    if ($equipment_id == 0 || $borrower_id == 0 || $due_date == null) {
        die("ข้อมูลไม่ครบถ้วน <a href='index.php'>กลับหน้าหลัก</a>");
    }

    // 5. เริ่ม Transaction (นี่คือหัวใจสำคัญ)
    try {
        // 5.1 เริ่มการ "ห่อ" คำสั่ง
        $pdo->beginTransaction();

        // 5.2 คำสั่งที่ 1: UPDATE สถานะอุปกรณ์ (med_equipment)
        // เราจะอัปเดตเฉพาะ id ที่ตรงกัน และต้องมีสถานะเป็น 'available' เท่านั้น
        // (ป้องกันการยืมซ้อน)
        $sql_update = "UPDATE med_equipment 
                       SET status = 'borrowed' 
                       WHERE id = ? AND status = 'available'";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$equipment_id]);

        // 5.3 ตรวจสอบว่า UPDATE สำเร็จหรือไม่ (สำคัญมาก!)
        // rowCount() จะคืนค่า 1 ถ้าอัปเดตสำเร็จ (เจอแถวที่ตรงเงื่อนไข)
        // ถ้าได้ 0 แสดงว่าอุปกรณ์นั้นไม่ได้ "ว่าง" (อาจถูกยืมไปก่อนหน้า)
        if ($stmt_update->rowCount() == 0) {
            // ถ้าอัปเดตไม่สำเร็จ (เช่น มีคนยืมตัดหน้า)
            throw new Exception("ไม่สามารถยืมอุปกรณ์ได้ อุปกรณ์อาจถูกยืมไปแล้ว");
        }

        // 5.4 คำสั่งที่ 2: INSERT ประวัติการยืม (med_transactions)
        $sql_insert = "INSERT INTO med_transactions (equipment_id, borrower_id, due_date, status) 
                       VALUES (?, ?, ?, 'borrowed')";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$equipment_id, $borrower_id, $due_date]);

        // 5.5 ถ้าทุกอย่างสำเร็จ (ทั้ง 1 และ 2)
        $pdo->commit();

        // 6. ส่งผู้ใช้กลับไปหน้าหลัก พร้อมข้อความ "สำเร็จ"
        // (เราจะสร้างระบบแจ้งเตือนทีหลัง ตอนนี้แค่เด้งกลับไปก่อน)
        header("Location: index.php?borrow=success");
        exit;

    } catch (Exception $e) {
        // 7. หากเกิดข้อผิดพลาด (ไม่ว่าจาก 5.3 หรือ 5.4)
        $pdo->rollBack(); // "ย้อนกลับ" การเปลี่ยนแปลงทั้งหมด

        // แสดงข้อผิดพลาด
        die("เกิดข้อผิดพลาดในการยืม: " . $e->getMessage() . " <a href='index.php'>กลับหน้าหลัก</a>");
    }

} else {
    // ถ้าไม่ได้เข้ามาหน้านี้ผ่านการ POST (เช่น พิมพ์ URL ตรงๆ)
    header("Location: index.php"); // เด้งกลับหน้าหลัก
    exit;
}
?>