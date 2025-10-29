<?php
// 1. เริ่ม Session เสมอ
session_start();

// 2. ตรวจสอบว่ามีการส่งข้อมูลมาแบบ POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล (ต้องใช้ $pdo)
    // *** สำคัญ: ต้องแน่ใจว่าคุณมีไฟล์ db_connect.php อยู่ในโฟลเดอร์หลัก ***
    require_once('db_connect.php');

    // 4. รับค่าจากฟอร์ม
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // 5. เตรียมคำสั่ง SQL เพื่อค้นหา username จากตาราง med_users
        $stmt = $pdo->prepare("SELECT * FROM med_users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        // 6. ดึงข้อมูลผู้ใช้
        
        echo "<pre style='font-family: monospace; font-size: 16px; background: #f4f4f4; padding: 20px;'>";
        
        echo "<b>--- ข้อมูลที่ได้รับจากฟอร์ม ---</b><br>";
        echo "Username ที่กรอก: ";
        var_dump($username);
        echo "Password ที่กรอก: ";
        var_dump($password);
        
        echo "<hr><b>--- ข้อมูลที่ดึงได้จากฐานข้อมูล ---</b><br>";
        echo "ผลลัพธ์ของ \$user: <br>";
        var_dump($user);
        
        echo "<hr><b>--- ผลการตรวจสอบรหัสผ่าน ---</b><br>";
        if ($user) {
            echo "Hash ที่อ่านได้จาก DB: " . $user['password_hash'] . "<br>";
            echo "ผลลัพธ์จาก password_verify(): ";
            var_dump(password_verify($password, $user['password_hash']));
        } else {
            echo "ไม่พบผู้ใช้นี้ในฐานข้อมูล!";
        }
        
        echo "</pre>";
        exit; // <-- !! สำคัญมาก: สั่งให้หยุดทำงานตรงนี้เลย !!

        // 7. ตรวจสอบว่า: (1) เจอผู้ใช้ และ (2) รหัสผ่านถูกต้อง
        // เราใช้ password_verify() เทียบรหัสผ่านที่กรอก กับ Hash ในฐานข้อมูล
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // 8. Log in สำเร็จ! "แจกบัตรพนักงาน" (เก็บข้อมูลลง Session)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role']; // นี่คือส่วนที่สำคัญที่สุด (admin หรือ user)

            // 9. ส่งกลับไปหน้า index.php (หน้าตารางหลัก)
            header("Location: index.php");
            exit;

        } else {
            // 10. Log in ไม่สำเร็จ (Username หรือ Password ผิด)
            // ส่งกลับไปหน้า login.php พร้อมส่งสัญญาณ ?error=1
            header("Location: login.php?error=1");
            exit;
        }

    } catch (PDOException $e) {
        die("เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้: " . $e->getMessage());
    }

} else {
    // ถ้าเข้ามาหน้านี้ตรงๆ (ไม่ได้กดปุ่ม Log in มา)
    header("Location: login.php");
    exit;
}
?>