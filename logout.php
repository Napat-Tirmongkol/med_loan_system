<?php
// เริ่ม Session ก่อนเสมอ
session_start();

// 1. ล้างข้อมูล Session ทั้งหมด (ทำลาย "บัตรพนักงาน")
session_unset();
session_destroy();

// 2. ส่งผู้ใช้กลับไปหน้า Log in
header("Location: login.php");
exit;
?>