<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// 3. ดึงข้อมูลอุปกรณ์เดิม (เหมือนเดิม)
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$equipment = null;
if ($equipment_id == 0) {
    die("ไม่ได้ระบุ ID อุปกรณ์ <a href='index.php'>กลับหน้าหลัก</a>");
}
try {
    $stmt = $pdo->prepare("SELECT * FROM med_equipment WHERE id = ?");
    $stmt->execute([$equipment_id]);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}
if (!$equipment) {
    echo "<h1>ไม่พบอุปกรณ์ที่ต้องการแก้ไข</h1>";
    echo "<a href='index.php'>กลับไปหน้าหลัก</a>";
    exit;
}
// ----- สิ้นสุดส่วนดึงข้อมูล -----

$page_title = "แก้ไขข้อมูลอุปกรณ์";
$current_page = "manage_equip"; 
include('includes/header.php');
?>

<div class="container">
    <h2>🔧 แก้ไขข้อมูลอุปกรณ์</h2>

    <form action="edit_process.php" method="POST" id="editForm">
        
        <input type="hidden" name="equipment_id" value="<?php echo $equipment['id']; ?>">

        <div style="margin-bottom: 15px;">
            <label for="name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่ออุปกรณ์:</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($equipment['name']); ?>" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="serial_number" style="font-weight: bold; display: block; margin-bottom: 5px;">เลขซีเรียล:</label>
            <input type="text" name="serial_number" id="serial_number" value="<?php echo htmlspecialchars($equipment['serial_number']); ?>" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="status" style="font-weight: bold; display: block; margin-bottom: 5px;">สถานะ:</label>
            <select name="status" id="status" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                <option value="available" <?php echo ($equipment['status'] == 'available') ? 'selected' : ''; ?>>
                    ว่าง (Available)
                </option>
                <option value="maintenance" <?php echo ($equipment['status'] == 'maintenance') ? 'selected' : ''; ?>>
                    ซ่อมบำรุง (Maintenance)
                </option>
                <?php if ($equipment['status'] == 'borrowed'): ?>
                    <option value="borrowed" selected disabled>
                        ถูกยืม (Borrowed) - (ต้องรับคืนก่อน)
                    </option>
                <?php endif; ?>
            </select>
        </div>

        <div>
            <button type="button" onclick="confirmEdit()" class="btn btn-manage" style="font-size: 16px; background-color: #007bff; color: white;">
                บันทึกการเปลี่ยนแปลง
            </button>
            <a href="index.php" class="btn" style="background-color: #6c757d;">ยกเลิก</a>
        </div>
    </form>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmEdit() {
    Swal.fire({
        title: "ยืนยันการบันทึก?",
        text: "ข้อมูลอุปกรณ์จะถูกแก้ไขตามฟอร์มนี้",
        icon: "warning", // ไอคอนสีเหลือง (warning)
        showCancelButton: true,
        confirmButtonColor: "#3085d6", // สีน้ำเงิน
        cancelButtonColor: "#d33",
        confirmButtonText: "ใช่, บันทึก",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            // ถ้ากดยืนยัน ให้สั่ง Submit ฟอร์ม
            document.getElementById('editForm').submit();
        }
    });
}
</script>


<?php
// 8. เรียกใช้ Footer
include('includes/footer.php'); 
?>