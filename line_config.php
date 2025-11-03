<?php
// line_config.php
// เก็บค่าลับสำหรับ LINE Login

// *** กรุณากรอกค่าจริงที่คุณได้จาก LINE Developers Console ***
define('LINE_LOGIN_CHANNEL_ID', '2008395562');
define('LINE_LOGIN_CHANNEL_SECRET', 'a1d7944f38e90819d043313e76a2f4f9');

// *** กรุณากรอก URL ที่คุณตั้งค่าไว้ใน LINE Developers Console ***
// (ต้องตรงกันเป๊ะๆ ทั้ง http/https)
define('LINE_LOGIN_CALLBACK_URL', 'https://healthycampus.rsu.ac.th/medloan_systemV2/line_callback.php');

// (สำหรับ Admin/เจ้าหน้าที่)
define('STAFF_LOGIN_URL', 'login.php'); 
?>