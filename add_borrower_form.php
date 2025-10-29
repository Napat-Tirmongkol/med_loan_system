<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// 3. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "เพิ่มผู้ยืมใหม่";
$current_page = "manage_user"; // เพื่อให้เมนู "จัดการผู้ยืม" active

// 4. เรียกใช้ Header
include('includes/header.php');
?>

<div class="container">
    <h2>➕ เพิ่มข้อมูลผู้ยืมใหม่</h2>
    <p>เพิ่มรายชื่อผู้ป่วย ญาติ หรือผู้ที่ต้องการยืมอุปกรณ์</p>

    <form action="add_borrower_process.php" method="POST" id="addBorrowerForm">
        
        <div style="margin-bottom: 15px;">
            <label for="full_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อ-สกุล ผู้ยืม:</label>
            <input type="text" name="full_name" id="full_name" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="contact_info" style="font-weight: bold; display: block; margin-bottom: 5px;">ข้อมูลติดต่อ (เบอร์โทร, HN, หรืออื่นๆ):</label>
            <input type="text" name="contact_info" id="contact_info" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
        </div>

        <div>
            <button type="button" onclick="confirmAddBorrower()" class="btn btn-borrow" style="font-size: 16px;">
                บันทึกข้อมูลผู้ยืม
            </button>
            <a href="manage_borrowers.php" class="btn" style="background-color: #6c757d;">ยกเลิก</a>
        </div>
    </form>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmAddBorrower() {
    // 1. ตรวจสอบว่ากรอกชื่อหรือยัง
    if (document.getElementById('full_name').value.trim() === '') {
        Swal.fire('ข้อผิดพลาด', 'กรุณากรอก ชื่อ-สกุล ผู้ยืม', 'error');
        return;
    }

    // 2. แสดง Pop-up ยืนยัน
    Swal.fire({
        title: "ยืนยันการบันทึก?",
        text: "คุณกำลังจะเพิ่มผู้ยืมใหม่เข้าระบบ",
        icon: "info",
        showCancelButton: true,
        confirmButtonColor: "#28a745", // สีเขียว
        cancelButtonColor: "#d33",
        confirmButtonText: "ใช่, บันทึก",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            // 3. ถ้ากดยืนยัน ให้ส่งฟอร์ม
            document.getElementById('addBorrowerForm').submit();
        }
    });
}
</script>

<?php
// 6. เรียกใช้ Footer
include('includes/footer.php'); 
?>