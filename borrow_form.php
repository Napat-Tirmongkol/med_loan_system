<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// ----- ส่วนดึงข้อมูล (เหมือนเดิม) -----
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$equipment = null;
$borrowers = []; 
try {
    $stmt_equip = $pdo->prepare("SELECT * FROM med_equipment WHERE id = ? AND status = 'available'");
    $stmt_equip->execute([$equipment_id]);
    $equipment = $stmt_equip->fetch(PDO::FETCH_ASSOC);
    $stmt_borrowers = $pdo->prepare("SELECT * FROM med_borrowers ORDER BY full_name ASC");
    $stmt_borrowers->execute();
    $borrowers = $stmt_borrowers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}
if (!$equipment) {
    echo "<h1>ไม่พบอุปกรณ์ที่ต้องการยืม หรืออุปกรณ์นี้ไม่พร้อมใช้งาน</h1>";
    echo "<a href='index.php'>กลับไปหน้าหลัก</a>";
    exit;
}
// ----- สิ้นสุดส่วนดึงข้อมูล -----

$page_title = "ยืมอุปกรณ์";
$current_page = "borrow"; 
include('includes/header.php');
?>

<div class="container">
    <h2>📝 ฟอร์มยืมอุปกรณ์</h2>

    <div style="background: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <h3>ข้อมูลอุปกรณ์</h3>
        <p><strong>ชื่ออุปกรณ์:</strong> <?php echo htmlspecialchars($equipment['name']); ?></p>
        <p><strong>เลขซีเรียล:</strong> <?php echo htmlspecialchars($equipment['serial_number']); ?></p>
    </div>

    <form action="borrow_process.php" method="POST" id="borrowForm">
        
        <input type="hidden" name="equipment_id" value="<?php echo $equipment['id']; ?>">

        <div style="margin-bottom: 15px;">
            <label for="borrower_id" style="font-weight: bold; display: block; margin-bottom: 5px;">ผู้ยืม:</label>
            <select name="borrower_id" id="borrower_id" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                <option value="">--- กรุณาเลือกผู้ยืม ---</option>
                <?php foreach ($borrowers as $borrower): ?>
                    <option value="<?php echo $borrower['id']; ?>">
                        <?php echo htmlspecialchars($borrower['full_name']); ?> (<?php echo htmlspecialchars($borrower['contact_info']); ?>)
                    </option>
                <?php endforeach; ?>
                <?php if (empty($borrowers)): ?>
                    <option value="" disabled>ยังไม่มีข้อมูลผู้ยืมในระบบ</option>
                <?php endif; ?>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="due_date" style="font-weight: bold; display: block; margin-bottom: 5px;">วันที่กำหนดคืน:</label>
            <input type="date" name="due_date" id="due_date" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
        </div>

        <div>
            <button type="button" onclick="confirmBorrow()" class="btn btn-borrow" style="font-size: 16px;">
                ยืนยันการยืม
            </button>
            <a href="index.php" class="btn" style="background-color: #6c757d;">ยกเลิก</a>
        </div>
    </form>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmBorrow() {
    Swal.fire({
        title: "ยืนยันการยืม?",
        text: "คุณได้ตรวจสอบข้อมูลผู้ยืมถูกต้องแล้วใช่หรือไม่",
        icon: "info", // ไอคอนสีฟ้า (info) เหมาะกับการยืนยัน
        showCancelButton: true,
        confirmButtonColor: "#28a745", // สีเขียว
        cancelButtonColor: "#d33",
        confirmButtonText: "ใช่, ยืนยันการยืม",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            // ถ้ากดยืนยัน ให้สั่ง Submit ฟอร์ม
            document.getElementById('borrowForm').submit();
        }
    });
}
</script>

<?php
// 7. เรียกใช้ Footer
include('includes/footer.php'); 
?>