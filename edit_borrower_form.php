<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// 3. ดึงข้อมูลผู้ยืมเดิม
$borrower_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$borrower = null;

if ($borrower_id == 0) {
    die("ไม่ได้ระบุ ID ผู้ยืม <a href='manage_borrowers.php'>กลับหน้าหลัก</a>");
}

try {
    $stmt = $pdo->prepare("SELECT * FROM med_borrowers WHERE id = ?");
    $stmt->execute([$borrower_id]);
    $borrower = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// 4. ตรวจสอบว่าพบผู้ยืมหรือไม่
if (!$borrower) {
    echo "<h1>ไม่พบข้อมูลผู้ยืมที่ต้องการแก้ไข</h1>";
    echo "<a href='manage_borrowers.php'>กลับหน้าหลัก</a>";
    exit;
}

// 5. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "แก้ไขข้อมูลผู้ยืม";
$current_page = "manage_user"; // เพื่อให้เมนู "จัดการผู้ยืม" active

// 6. เรียกใช้ Header
include('includes/header.php');
?>

<div class="container">
    <h2>🔧 แก้ไขข้อมูลผู้ยืม</h2>

    <form action="edit_borrower_process.php" method="POST" id="editBorrowerForm">
        
        <input type="hidden" name="borrower_id" value="<?php echo $borrower['id']; ?>">

        <div style="margin-bottom: 15px;">
            <label for="full_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อ-สกุล ผู้ยืม:</label>
            <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($borrower['full_name']); ?>" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="contact_info" style="font-weight: bold; display: block; margin-bottom: 5px;">ข้อมูลติดต่อ (เบอร์โทร, HN, หรืออื่นๆ):</label>
            <input type="text" name="contact_info" id="contact_info" value="<?php echo htmlspecialchars($borrower['contact_info']); ?>" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
        </div>

        <div>
            <button type="button" onclick="confirmEditBorrower()" class="btn btn-manage" style="font-size: 16px; background-color: #007bff; color: white;">
                บันทึกการเปลี่ยนแปลง
            </button>
            <a href="manage_borrowers.php" class="btn" style="background-color: #6c757d;">ยกเลิก</a>
        </div>
    </form>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmEditBorrower() {
    // 1. ตรวจสอบว่ากรอกชื่อหรือยัง
    if (document.getElementById('full_name').value.trim() === '') {
        Swal.fire('ข้อผิดพลาด', 'กรุณากรอก ชื่อ-สกุล ผู้ยืม', 'error');
        return;
    }

    // 2. แสดง Pop-up ยืนยัน
    Swal.fire({
        title: "ยืนยันการบันทึก?",
        text: "ข้อมูลผู้ยืมจะถูกแก้ไข",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6", // สีน้ำเงิน
        cancelButtonColor: "#d33",
        confirmButtonText: "ใช่, บันทึก",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            // 3. ถ้ากดยืนยัน ให้ส่งฟอร์ม
            document.getElementById('editBorrowerForm').submit();
        }
    });
}
</script>

<?php
// 8. เรียกใช้ Footer
include('includes/footer.php'); 
?>