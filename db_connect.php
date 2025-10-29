<?php
/*
 * db_connect.php
 * ไฟล์สำหรับเชื่อมต่อฐานข้อมูล
 */

// 1. ตั้งค่าตัวแปรเชื่อมต่อ
$db_host = "localhost";      // หรือ 127.0.0.1
$db_user = "root";           // username ของ MySQL (ค่าเริ่มต้นมักจะเป็น root)
$db_pass = "";               // password ของ MySQL (ถ้าใช้ XAMPP/MAMP ค่าเริ่มต้นมักจะเป็นค่าว่าง)
$db_name = "medical_equipment_db"; // ชื่อฐานข้อมูลที่คุณสร้าง

// 2. พยายามเชื่อมต่อ
try {
    // สร้างการเชื่อมต่อแบบ PDO (PHP Data Objects)
    // PDO เป็นวิธีเชื่อมต่อฐานข้อมูลที่ทันสมัยและปลอดภัยครับ
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // เปิดโหมดแสดง error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // ให้ดึงข้อมูลเป็น array แบบ [key => value]
        PDO::ATTR_EMULATE_PREPARES   => false,                  // ปิดการจำลอง prepare (เพื่อความปลอดภัย)
    ];

    // สร้างตัวแปร $pdo เพื่อเก็บการเชื่อมต่อ
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
} catch (PDOException $e) {
    // หากเชื่อมต่อล้มเหลว ให้หยุดทำงานและแสดงข้อผิดพลาด
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}

// ถ้าโค้ดรันมาถึงบรรทัดนี้ได้ แสดงว่า $pdo ถูกสร้างสำเร็จแล้ว
// ไฟล์อื่น (เช่น login_process.php) ที่ 'require' ไฟล์นี้ไป 
// ก็จะสามารถใช้งานตัวแปร $pdo นี้ได้ทันที
?>