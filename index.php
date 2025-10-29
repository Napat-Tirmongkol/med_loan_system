<?php
// 1. ตั้งค่าตัวแปรสำหรับหน้านี้ (เพื่อให้ header.php นำไปใช้)
$page_title = "Dashboard - ภาพรวม";
$current_page = "index"; // ใช้สำหรับไฮไลท์เมนูใน sidebar

// 2. เรียกใช้ไฟล์ Header (ส่วนหัว + Sidebar)
include('includes/header.php'); 

/* * ตรงนี้ เราสามารถเริ่มเขียน PHP เพื่อดึงข้อมูลจาก Database ได้
 * แต่ตอนนี้เราจะใช้ข้อมูลจำลองไปก่อน
 */

?>

<div class="container">
    <h2>รายการอุปกรณ์ทั้งหมด</h2>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่ออุปกรณ์</th>
                <th>เลขซีเรียล</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>รถเข็นวีลแชร์ (Wheelchair)</td>
                <td>WC-001</td>
                <td><span class="status status-available">ว่าง</span></td>
                <td><a href="#" class="btn btn-borrow">ยืม</a></td>
            </tr>
            <tr>
                <td>2</td>
                <td>ไม้เท้าสามขา (Tripod Cane)</td>
                <td>TC-015</td>
                <td><span class="status status-borrowed">ถูกยืม</span></td>
                <td><a href="#" class="btn btn-return">รับคืน</a></td>
            </tr>
            <tr>
                <td>3</td>
                <td>เครื่องวัดความดัน (BP Monitor)</td>
                <td>BP-005</td>
                <td><span class="status status-maintenance">ซ่อมบำรุง</span></td>
                <td><a href="#" class="btn btn-manage">แก้ไข</a></td>
            </tr>
            <tr>
                <td>4</td>
                <td>เครื่องผลิตออกซิเจน (Oxygen Concentrator)</td>
                <td>OC-002</td>
                <td><span class="status status-available">ว่าง</span></td>
                <td><a href="#" class="btn btn-borrow">ยืม</a></td>
            </tr>
        </tbody>
    </table>
</div>
<?php
// 1. "จ้างยามมาเฝ้าประตู"
//    ไฟล์นี้จะตรวจสอบว่า Log in หรือยัง ถ้ายัง จะเด้งไปหน้า login.php
include('includes/check_session.php'); 

// 2. ตั้งค่าตัวแปรสำหรับหน้านี้ (ของเดิม)
$page_title = "Dashboard - ภาพรวม";
$current_page = "index"; 

// 3. เรียกใช้ไฟล์ Header (ของเดิม)
include('includes/header.php'); 
?>

<?php
// 4. เรียกใช้ไฟล์ Footer (ของเดิม)
include('includes/footer.php'); 
?>