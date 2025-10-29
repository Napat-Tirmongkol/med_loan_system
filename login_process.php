<?php
// 1. เริ่ม Session เสมอ
session_start();

// 2. ตรวจสอบว่ามีการส่งข้อมูลมาแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
    require_once('db_connect.php');

    // 4. รับค่าจากฟอร์ม
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // 5. เตรียมคำสั่ง SQL
        $stmt = $pdo->prepare("SELECT * FROM med_users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        // 6. ดึงข้อมูลผู้ใช้
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 7. ตรวจสอบว่า: (1) เจอผู้ใช้ และ (2) รหัสผ่านถูกต้อง
        if ($user && password_verify($password, $user['password_hash'])) {

            // 8. Log in สำเร็จ! "แจกบัตรพนักงาน"
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role']; 

            // 9. ส่งกลับไปหน้า index.php
            header("Location: index.php");
            exit;

        } else {
            // 10. Log in ไม่สำเร็จ
            header("Location: login.php?error=1");
            exit;
        }

    } catch (PDOException $e) {
        die("เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้: " . $e->getMessage());
    }

} else {
    // ถ้าเข้ามาหน้านี้ตรงๆ
    header("Location: login.php");
    exit;
}
?>