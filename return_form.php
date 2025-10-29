<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// ----- ส่วนดึงข้อมูล (เหมือนเดิม) -----
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$transaction_data = null;

if ($equipment_id == 0) {
    die("ไม่ได้ระบุ ID อุปกรณ์ <a href='index.php'>กลับหน้าหลัก</a>");
}

try {
    $sql = "SELECT 
                t.id as transaction_id, t.borrow_date, t.due_date,
                e.name as equipment_name, e.serial_number as equipment_serial,
                b.full_name as borrower_name, b.contact_info as borrower_contact
            FROM med_transactions t
            JOIN med_equipment e ON t.equipment_id = e.id
            JOIN med_borrowers b ON t.borrower_id = b.id
            WHERE t.equipment_id = ? AND t.status = 'borrowed'
            ORDER BY t.borrow_date DESC 
            LIMIT 1"; 
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$equipment_id]);
    $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

if (!$transaction_data) {
    echo "<h1>ไม่พบข้อมูลการยืมสำหรับอุปกรณ์นี้</h1>";
    echo "<a href='index.php'>กลับไปหน้าหลัก</a>";
    exit;
}
// ----- สิ้นสุดส่วนดึงข้อมูล -----

$page_title = "รับคืนอุปกรณ์";
$current_page = "return"; 
include('includes/header.php');
?>

<div class="container">
    <h2>📦 ฟอร์มรับคืนอุปกรณ์</h2>

    <div style="background: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <h3>ข้อมูลการยืม</h3>
        <p><strong>ชื่ออุปกรณ์:</strong> <?php echo htmlspecialchars($transaction_data['equipment_name']); ?></p>
        <p><strong>เลขซีเรียล:</strong> <?php echo htmlspecialchars($transaction_data['equipment_serial']); ?></p>
        <hr>
        <p><strong>ผู้ยืม:</strong> <?php echo htmlspecialchars($transaction_data['borrower_name']); ?> (<?php echo htmlspecialchars($transaction_data['borrower_contact']); ?>)</p>
        <p><strong>วันที่ยืม:</strong> <?php echo date('d/m/Y H:i', strtotime($transaction_data['borrow_date'])); ?></p>
        <p><strong>วันที่กำหนดคืน:</strong> <?php echo date('d/m/Y', strtotime($transaction_data['due_date'])); ?></p>
    </div>

    <form action="return_process.php" method="POST" id="returnForm">
        
        <input type="hidden" name="transaction_id" value="<?php echo $transaction_data['transaction_id']; ?>">
        <input type="hidden" name="equipment_id" value="<?php echo $equipment_id; ?>">

        <p style="font-weight: bold; color: #dc3545;">
            กรุณาตรวจสอบอุปกรณ์ว่าอยู่ในสภาพสมบูรณ์ก่อนกดยืนยันการรับคืน
        </p>

        <div>
            <button type="button" onclick="confirmReturn()" class="btn btn-return" style="font-size: 16px;">
                ยืนยันการรับคืน
            </button>
            <a href="index.php" class="btn" style="background-color: #6c757d;">ยกเลิก</a>
        </div>
    </form>
</div>


<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmReturn() {
    // นี่คือโค้ดที่คุณส่งมา (ผมปรับแต่งข้อความเล็กน้อยให้เข้ากับบริบท)
    Swal.fire({
        title: "ยืนยันการรับคืน?",
        text: "คุณได้ตรวจสอบอุปกรณ์แล้วใช่หรือไม่",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "ใช่, ยืนยัน",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            // ถ้ากดยืนยัน ให้สั่ง Submit ฟอร์มที่มี id 'returnForm'
            document.getElementById('returnForm').submit();
        }
    });
}
</script>

<?php
// 6. เรียกใช้ Footer
include('includes/footer.php'); 
?>