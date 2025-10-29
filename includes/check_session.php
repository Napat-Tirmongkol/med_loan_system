<?php
// 1. เริ่ม Session ก่อนเสมอ (เพื่อตรวจสอบ $_SESSION)
//    เราจะใช้ @session_start() เพื่อป้องกันการ error หาก session_start() ถูกเรียกไปแล้ว
@session_start();

// 2. ตรวจสอบว่า "บัตรพนักงาน" (Session 'user_id') ถูกสร้างหรือยัง
if (!isset($_SESSION['user_id'])) {
    
    // 3. ถ้ายังไม่มี (ยังไม่ Log in)
    //    ให้ส่งกลับไปหน้า Log in ทันที
    header("Location: login.php");
    exit; // จบการทำงานของสคริปต์ทันที
}

// 4. ถ้ามี Session 'user_id' อยู่แล้ว (Log in แล้ว)
//    สคริปต์ก็จะทำงานข้ามไป และอนุญาตให้แสดงเนื้อหาของหน้าต่อไปได้
?>