<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session_ajax.php');
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
    $name          = isset($_POST['name']) ? trim($_POST['name']) : '';
    $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
    $description   = isset($_POST['description']) ? trim($_POST['description']) : null;

    if (empty($name)) {
        $response['message'] = 'กรุณากรอกชื่ออุปกรณ์';
        echo json_encode($response);
        exit;
    }

    if (empty($serial_number)) $serial_number = null;
    if (empty($description)) $description = null;

    // ◀️ (6. ใหม่!) ส่วนจัดการการอัปโหลดไฟล์
    $image_url_to_db = null; // (ค่าเริ่มต้นคือ NULL)

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $upload_dir = 'images/'; // โฟลเดอร์ที่เราสร้างไว้
        
        // (สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน)
        $file_extension = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('equip-', true) . '.' . strtolower($file_extension);
        $target_file = $upload_dir . $new_filename;

        // (ตรวจสอบว่าเป็นไฟล์รูปภาพจริงหรือไม่)
        $check = getimagesize($_FILES['image_file']['tmp_name']);
        if ($check !== false) {
            // (พยายามย้ายไฟล์)
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                $image_url_to_db = $target_file; // (ถ้าสำเร็จ ให้ใช้ Path นี้)
            } else {
                $response['message'] = 'ไม่สามารถอัปโหลดไฟล์รูปภาพได้ (ย้ายไฟล์ล้มเหลว)';
                echo json_encode($response);
                exit;
            }
        } else {
            $response['message'] = 'ไฟล์ที่แนบมาไม่ใช่ไฟล์รูปภาพ';
            echo json_encode($response);
            exit;
        }
    }
    // ◀️ (จบส่วนอัปโหลดไฟล์)


    // 7. ดำเนินการ INSERT
    try {
        // (แก้ไข SQL ให้เพิ่ม image_url)
        $sql = "INSERT INTO med_equipment (name, serial_number, description, image_url)
                VALUES (?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        // (เพิ่ม $image_url_to_db เข้าไป)
        $stmt->execute([$name, $serial_number, $description, $image_url_to_db]);

        // 8. ถ้าสำเร็จ
        $response['status'] = 'success';
        $response['message'] = 'เพิ่มอุปกรณ์ใหม่สำเร็จ';

    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
             $response['message'] = 'เลขซีเรียล (Serial Number) นี้มีในระบบแล้ว';
        } else {
             $response['message'] = 'เกิดข้อผิดพลาด DB: ' . $e->getMessage();
        }
    }

} else {
    $response['message'] = 'ต้องใช้วิธี POST เท่านั้น';
}

// 9. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>