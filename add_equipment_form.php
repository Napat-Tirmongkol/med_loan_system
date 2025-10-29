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
$page_title = "เพิ่มอุปกรณ์ใหม่";
$current_page = "manage_equip"; // (ตั้งชื่อเมนูสมมติ)

// 4. เรียกใช้ Header
include('includes/header.php');
?>

<div class="container">
    <h2>➕ เพิ่มอุปกรณ์ใหม่เข้าสู่ระบบ</h2>
    <p>อุปกรณ์ใหม่จะถูกเพิ่มในสถานะ "ว่าง" (Available) โดยอัตโนมัติ</p>

    <form action="add_equipment_process.php" method="POST" id="addForm">
        
        <div style="margin-bottom: 15px;">
            <label for="name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่ออุปกรณ์: (เช่น รถเข็นวีลแชร์)</label>
            <input type="text" name="name" id="name" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="serial_number" style="font-weight: bold; display: block; margin-bottom: 5px;">เลขซีเรียล (Serial Number): (ถ้ามี)</label>
            <input type="text" name="serial_number" id="serial_number" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="description" style="font-weight: bold; display: block; margin-bottom: 5px;">รายละเอียด (หมายเหตุ):</label>
            <textarea name="description" id="description" rows="3" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;"></textarea>
        </div>

        <div>
            <button type="button" onclick="confirmAdd()" class="btn btn-borrow" style="font-size: 16px;">
                บันทึกอุปกรณ์ใหม่
            </button>
            <a href="index.php" class="btn" style="background-color: #6c757d;">ยกเลิก</a>
        </div>
    </form>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmAdd() {
    // ตรวจสอบว่ากรอกชื่อหรือยัง
    if (document.getElementById('name').value.trim() === '') {
        Swal.fire('ข้อผิดพลาด', 'กรุณากรอกชื่ออุปกรณ์', 'error');
        return;
    }

    Swal.fire({
        title: "ยืนยันการเพิ่มอุปกรณ์?",
        text: "คุณกำลังจะเพิ่มอุปกรณ์ใหม่เข้าระบบ",
        icon: "info",
        showCancelButton: true,
        confirmButtonColor: "#28a745", // สีเขียว
        cancelButtonColor: "#d33",
        confirmButtonText: "ใช่, บันทึก",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('addForm').submit();
        }
    });
}
</script>

<?php
// 6. เรียกใช้ Footer
include('includes/footer.php'); 
?>