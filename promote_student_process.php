<?php
// promote_student_process.php
// รับข้อมูลจาก Popup "เลื่อนขั้น"

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session_ajax.php');
require_once('db_connect.php'); //

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
    $student_id   = isset($_POST['student_id_to_promote']) ? (int)$_POST['student_id_to_promote'] : 0;
    $line_user_id = isset($_POST['line_user_id_to_link']) ? trim($_POST['line_user_id_to_link']) : '';
    $new_username = isset($_POST['new_username']) ? trim($_POST['new_username']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $new_role     = isset($_POST['new_role']) ? trim($_POST['new_role']) : 'employee'; // (default เป็น employee)

    if ($student_id == 0 || empty($line_user_id) || empty($new_username) || empty($new_password)) {
        $response['message'] = 'ข้อมูลที่ส่งมาไม่ครบถ้วน';
        echo json_encode($response);
        exit;
    }
    if ($new_role != 'admin' && $new_role != 'employee') {
        $response['message'] = 'สิทธิ์ (Role) ไม่ถูกต้อง';
        echo json_encode($response);
        exit;
    }

    // 6. ดำเนินการ "เลื่อนขั้น"
    try {
        // 6.1 ดึง "ชื่อเต็ม" จาก med_students
        $stmt_get = $pdo->prepare("SELECT full_name FROM med_students WHERE id = ? AND line_user_id = ?");
        $stmt_get->execute([$student_id, $line_user_id]);
        $student_full_name = $stmt_get->fetchColumn();

        if (!$student_full_name) {
            throw new Exception("ไม่พบข้อมูลผู้ใช้งานที่ตรงกัน หรือผู้ใช้งานไม่มี LINE ID");
        }

        // 6.2 (เช็คซ้ำ) ตรวจสอบว่า Username นี้ถูกใช้ไปหรือยัง
        $stmt_check_user = $pdo->prepare("SELECT id FROM med_users WHERE username = ?");
        $stmt_check_user->execute([$new_username]);
        if ($stmt_check_user->fetch()) {
            throw new Exception("Username '$new_username' นี้ถูกใช้งานแล้ว");
        }

        // 6.3 (เช็คซ้ำ) ตรวจสอบว่า LINE ID นี้ถูกผูกไปหรือยัง
        $stmt_check_line = $pdo->prepare("SELECT id FROM med_users WHERE linked_line_user_id = ?");
        $stmt_check_line->execute([$line_user_id]);
        if ($stmt_check_line->fetch()) {
            throw new Exception("LINE ID นี้ถูกเชื่อมโยงกับบัญชีพนักงานอื่นแล้ว");
        }
        
        // 6.4 เข้ารหัสรหัสผ่านใหม่
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // 6.5 (SQL) INSERT ข้อมูลเข้า med_users
        $sql = "INSERT INTO med_users (username, password_hash, full_name, role, linked_line_user_id) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_username, $password_hash, $student_full_name, $new_role, $line_user_id]);

        // 7. ถ้าสำเร็จ ให้เปลี่ยนคำตอบ
        $response['status'] = 'success';
        $response['message'] = 'เลื่อนขั้นผู้ใช้งานสำเร็จ';

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