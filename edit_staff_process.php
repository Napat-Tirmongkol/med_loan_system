<?php
// edit_staff_process.php
// (ไฟล์ใหม่)

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

    // 5. รับข้อมูลจากฟอร์ม AJAX
    $user_id      = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $username     = isset($_POST['username']) ? trim($_POST['username']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    
    // (ตัวแปรที่อาจจะถูก disable)
    $full_name    = isset($_POST['full_name']) ? trim($_POST['full_name']) : null;
    $role         = isset($_POST['role']) ? trim($_POST['role']) : null;


    if ($user_id == 0 || empty($username)) {
        $response['message'] = 'ข้อมูลที่ส่งมาไม่ครบถ้วน (ID หรือ Username)';
        echo json_encode($response);
        exit;
    }

    // 6. ดำเนินการ UPDATE
    try {
        // 6.1 ดึงข้อมูลเดิม (เพื่อเช็คว่าเป็นบัญชีที่ผูก LINE หรือไม่)
        $stmt_get = $pdo->prepare("SELECT username, full_name, role, linked_line_user_id FROM med_users WHERE id = ?");
        $stmt_get->execute([$user_id]);
        $current_data = $stmt_get->fetch(PDO::FETCH_ASSOC);

        if (!$current_data) {
            throw new Exception("ไม่พบบัญชีพนักงาน ID: $user_id");
        }

        // 6.2 ตรวจสอบ Username ซ้ำ (กรณีที่เปลี่ยน Username และ Username ใหม่ไม่ตรงกับของเดิม)
        if ($current_data['username'] != $username) {
            $stmt_check = $pdo->prepare("SELECT id FROM med_users WHERE username = ?");
            $stmt_check->execute([$username]);
            if ($stmt_check->fetch()) {
                throw new Exception("Username '$username' นี้ถูกใช้งานแล้ว");
            }
        }

        // 6.3 (ตรรกะ) ถ้าเป็นบัญชีที่ผูกกับ LINE (Linked)
        if ($current_data['linked_line_user_id']) {
            // (ถ้าผูก LINE อยู่) จะอัปเดตได้แค่ Username และ Password
            // (ป้องกันการแก้ชื่อ/Role มั่ว)
            $sql = "UPDATE med_users SET username = ?";
            $params = [$username];
        } 
        // (ถ้าเป็นบัญชีปกติ ไม่ได้ผูก LINE)
        else {
            // (ถ้าไม่ผูก LINE) อัปเดตได้ 3 อย่าง
            // (ตรวจสอบ Role ที่ส่งมาด้วย)
            if ($role != 'admin' && $role != 'employee') {
                throw new Exception("สิทธิ์ (Role) ที่ส่งมาไม่ถูกต้อง");
            }
            $sql = "UPDATE med_users SET username = ?, full_name = ?, role = ?";
            $params = [$username, $full_name, $role];
        }
        
        // 6.4 (ตรรกะ) ถ้ามีการกรอก "รหัสผ่านใหม่"
        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql .= ", password_hash = ?"; // (เพิ่มการอัปเดต password)
            $params[] = $password_hash;
        }

        // 6.5 (รวมร่าง)
        $sql .= " WHERE id = ?";
        $params[] = $user_id;
        
        $stmt_update = $pdo->prepare($sql);
        $stmt_update->execute($params);

        // 7. ถ้าสำเร็จ ให้เปลี่ยนคำตอบ
        $response['status'] = 'success';
        $response['message'] = 'บันทึกการเปลี่ยนแปลงสำเร็จ';

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

} else {
    $response['message'] = 'ต้องใช้วิธี POST เท่านั้น';
}

// 8. ส่งคำตอบ (JSON) กลับไปให้ JavaScript
echo json_encode($response);
exit;
?>