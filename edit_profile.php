<?php
// student_dashboard.php (หน้าหลัก - รายการที่ยืมอยู่ - Layout ใหม่)

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
@session_start(); 
include('includes/check_student_session.php'); // (◀️ เปิดยาม)
// (⚠️ ลบส่วน Development Mode ออกแล้ว ⚠️)

require_once('db_connect.php'); //

<<<<<<< HEAD
=======
// 2. ดึง ID ของผู้ใช้งาน<?php
// edit_profile.php (หน้าตั้งค่า/แก้ไขโปรไฟล์)

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
@session_start(); 
include('includes/check_student_session.php'); // (◀️ เปิดยาม)
// (⚠️ ลบส่วน Development Mode ออกแล้ว ⚠️)

require_once('db_connect.php'); //

>>>>>>> 8440e8f921da6f7d864c435f03e6c4936b0735b5
// 1. "ยามเฝ้าประตู"
if (empty($_SESSION['student_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: line_login.php");
    exit;
<<<<<<< HEAD
=======
}

// 2. ดึง ID นักศึกษาจาก Session
$student_id = $_SESSION['student_id'];

// 3. รับข้อมูลจากฟอร์ม
$full_name = trim($_POST['full_name']);
$department = trim($_POST['department']);
$student_personnel_id = trim($_POST['student_personnel_id']);
$phone_number = trim($_POST['phone_number']);

// 4. ตรวจสอบข้อมูลบังคับ
if (empty($full_name)) {
    header("Location: edit_profile.php?status=error&message=" . urlencode("กรุณากรอกชื่อ-สกุล"));
    exit;
}

// 5. บันทึกลงฐานข้อมูล (med_students)
try {
    $sql = "UPDATE med_students 
            SET full_name = ?, 
                department = ?, 
                student_personnel_id = ?, 
                phone_number = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $full_name,
        $department,
        $student_personnel_id,
        $phone_number,
        $student_id
    ]);

    // 6. (สำคัญ) อัปเดต Session 'student_full_name' ด้วย
    $_SESSION['student_full_name'] = $full_name;

    // 7. บันทึกสำเร็จ! ส่งกลับไปหน้าเดิมพร้อมข้อความ
    header("Location: edit_profile.php?status=success");
    exit;

} catch (PDOException $e) {
    header("Location: edit_profile.php?status=error&message=" . urlencode($e->getMessage())); // ◀️ (แก้ไข)
    exit;
}
?>
$student_id = $_SESSION['student_id']; 

// 3. (Query ข้อมูลผู้ใช้ปัจจุบัน)
try {
    $stmt = $pdo->prepare("SELECT * FROM med_students WHERE id = ?");
    $stmt->execute([$student_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user_data) {
        throw new Exception("ไม่พบข้อมูลผู้ใช้งาน");
    }
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage()); // ◀️ (แก้ไข)
>>>>>>> 8440e8f921da6f7d864c435f03e6c4936b0735b5
}

// 2. ดึง ID นักศึกษาจาก Session
$student_id = $_SESSION['student_id'];

// 3. รับข้อมูลจากฟอร์ม
$full_name = trim($_POST['full_name']);
$department = trim($_POST['department']);
$student_personnel_id = trim($_POST['student_personnel_id']);
$phone_number = trim($_POST['phone_number']);

// 4. ตรวจสอบข้อมูลบังคับ
if (empty($full_name)) {
    header("Location: edit_profile.php?status=error&message=" . urlencode("กรุณากรอกชื่อ-สกุล"));
    exit;
}

// 5. บันทึกลงฐานข้อมูล (med_students)
try {
    $sql = "UPDATE med_students 
            SET full_name = ?, 
                department = ?, 
                student_personnel_id = ?, 
                phone_number = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $full_name,
        $department,
        $student_personnel_id,
        $phone_number,
        $student_id
    ]);

    // 6. (สำคัญ) อัปเดต Session 'student_full_name' ด้วย
    $_SESSION['student_full_name'] = $full_name;

    // 7. บันทึกสำเร็จ! ส่งกลับไปหน้าเดิมพร้อมข้อความ
    header("Location: edit_profile.php?status=success");
    exit;

} catch (PDOException $e) {
    header("Location: edit_profile.php?status=error&message=" . urlencode($e->getMessage())); // ◀️ (แก้ไข)
    exit;
}
?>