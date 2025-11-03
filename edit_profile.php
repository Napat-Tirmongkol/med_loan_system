<?php
// edit_profile.php (หน้าตั้งค่า/แก้ไขโปรไฟล์)

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
// (⚠️ โค้ดสำหรับ Development Mode ⚠️)
@session_start(); 
// include('includes/check_student_session.php'); 
$_SESSION['student_id'] = 9; 
$_SESSION['student_full_name'] = "ผู้ใช้ทดสอบ";
// (⚠️ จบส่วน Development Mode ⚠️)

require_once('db_connect.php'); //

// 2. ดึง ID ของผู้ใช้งาน
$student_id = $_SESSION['student_id']; 

// 3. (Query ข้อมูลผู้ใช้ปัจจุบัน)
try {
    $stmt = $pdo->prepare("SELECT * FROM med_students WHERE id = ?");
    $stmt->execute([$student_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user_data) {
        throw new Exception("ไม่พบข้อมูลผู้ใช้งาน");
    }
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage()); // ◀️ (แก้ไข)
}

// 4. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "ตั้งค่าโปรไฟล์";
$active_page = 'settings'; // ◀️ (สำคัญ) บอก Footer ว่าเมนูไหน Active
include('includes/student_header.php');
?>

<div class="main-container">
    <div class="section-card">
        <h2 class="section-title">แก้ไขโปรไฟล์</h2>
        <p class="text-muted">คุณสามารถแก้ไขข้อมูลติดต่อของคุณได้ที่นี่</p>
        
        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <p style="color: green; font-weight: bold; background: #e6ffed; padding: 10px; border-radius: 8px;">
                ✔ บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว!
            </p>
        <?php elseif(isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <p style="color: red; font-weight: bold; background: #ffeeee; padding: 10px; border-radius: 8px;">
                ❌ <?php echo htmlspecialchars($_GET['message'] ?? 'เกิดข้อผิดพลาด'); ?>
            </p>
        <?php endif; ?>


        <form action="edit_profile_process.php" method="POST" id="profileForm" style="margin-top: 1.5rem;">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="full_name" style="font-weight: bold; display: block; margin-bottom: 5px;">1. ชื่อ-สกุล <span style="color:red;">*</span></label>
                <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="department" style="font-weight: bold; display: block; margin-bottom: 5px;">2. คณะ/หน่วยงาน/สถาบัน</label>
                <input type="text" name="department" id="department" value="<?php echo htmlspecialchars($user_data['department'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="status" style="font-weight: bold; display: block; margin-bottom: 5px;">3. สถานภาพ (ปัจจุบัน: <?php echo htmlspecialchars($user_data['status']); ?>)</label>
                </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="student_personnel_id" style="font-weight: bold; display: block; margin-bottom: 5px;">4. รหัสผู้ใช้งาน/บุคลากร</label>
                <input type="text" name="student_personnel_id" id="student_personnel_id" value="<?php echo htmlspecialchars($user_data['student_personnel_id'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="phone_number" style="font-weight: bold; display: block; margin-bottom: 5px;">5. เบอร์โทรศัพท์</label>
                <input type="text" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; box-sizing: border-box;">
            </div>

            <button type="submit" class="btn-loan" style="width: 100%; padding: 12px; font-size: 1.1em; font-weight: bold; margin-top: 10px;">
                บันทึกการเปลี่ยนแปลง
            </button>

        </form>
    </div>
</div> 

<?php
// 5. เรียกใช้ Footer
include('includes/student_footer.php'); 
?>