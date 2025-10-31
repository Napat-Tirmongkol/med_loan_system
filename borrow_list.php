<?php
// borrow_list.php (หน้าสำหรับยืม - รายการของที่ว่าง)

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
// (⚠️ โค้ดสำหรับ Development Mode ⚠️)
@session_start(); 
// include('includes/check_student_session.php'); 
$_SESSION['student_id'] = 1; 
$_SESSION['student_full_name'] = "ผู้ใช้ทดสอบ";
// (⚠️ จบส่วน Development Mode ⚠️)

require_once('db_connect.php'); //

// 2. ดึง ID ของผู้ใช้งาน
$student_id = $_SESSION['student_id']; 

// 3. (Query เฉพาะของที่ว่าง)
try {
    $stmt_equip = $pdo->prepare("SELECT * FROM med_equipment WHERE status = 'available' ORDER BY name ASC");
    $stmt_equip->execute();
    $equipments = $stmt_equip->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $equipments = [];
    $equip_error = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

// 4. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "ยืมอุปกรณ์";
$active_page = 'borrow'; // ◀️ (สำคัญ) บอก Footer ว่าเมนูไหน Active
include('includes/student_header.php');
?>

<div class="equipment-grid">
            
            <?php if (empty($equipments)): ?>
                <p style="grid-column: 1 / -1; text-align: center;">ไม่มีอุปกรณ์ที่ว่างในขณะนี้</p>
            <?php else: ?>
                <?php foreach ($equipments as $row): ?>
                    
                    <div class="equipment-card">
                        
                        <?php
                            // --- ⬇️ นี่คือตรรกะใหม่ ⬇️ ---
                            // 1. ตรวจสอบว่ามี image_url หรือไม่
                            if (!empty($row['image_url'])):
                                // ◀️ ถ้ามี: ให้แสดงแท็ก <img>
                                $image_to_show = $row['image_url'];
                        ?>
                                <img src="<?php echo htmlspecialchars($image_to_show); ?>" 
                                     alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                     class="equipment-card-image"
                                     onerror="this.parentElement.innerHTML = '<div class=\'equipment-card-image-placeholder\'><i class=\'fas fa-image-slash\'></i></div>';"> 
                                     <?php
                            else:
                                // ◀️ ถ้าไม่มี: ให้แสดง Placeholder ที่เป็นไอคอน (ไม่เกิด 404)
                        ?>
                                <div class="equipment-card-image-placeholder">
                                    <i class="fas fa-camera"></i> </div>
                        <?php
                            endif;
                            // --- ⬆️ จบตรรกะรูปภาพ ⬆️ ---
                        ?>

                        <div class="equipment-card-content">
                            <h3 class="equipment-card-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="equipment-card-desc"><?php echo htmlspecialchars($row['description'] ?? 'ไม่มีรายละเอียด'); ?></p>
                        </div>
                        
                        <div class="equipment-card-footer">
                            <span class="equipment-card-price" style="font-weight: bold; color: var(--color-primary);">
                                <?php echo htmlspecialchars($row['serial_number'] ?? 'N/A'); ?>
                            </span>

                            <button type="button" 
                                    class="btn-loan" 
                                    title="ส่งคำขอยืม"
                                    onclick="openRequestPopup(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')">+</button>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openRequestPopup(equipmentId, equipmentName) {
    Swal.fire({
        title: 'กำลังโหลดข้อมูล...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    fetch(`get_staff_list.php`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                throw new Error(data.message || 'ไม่สามารถดึงรายชื่อพนักงานได้');
            }
            let staffOptions = '<option value="">--- กรุณาเลือก ---</option>';
            if (data.staff.length > 0) {
                data.staff.forEach(staff => {
                    staffOptions += `<option value="${staff.id}">${staff.full_name}</option>`;
                });
            } else {
                staffOptions = '<option value="" disabled>ไม่มีข้อมูลพนักงาน</option>';
            }
            const formHtml = `
                <form id="swalRequestForm" style="text-align: left; margin-top: 20px;">
                    <input type="hidden" name="equipment_id" value="${equipmentId}">
                    <div style="margin-bottom: 15px;">
                        <label for="swal_reason" style="font-weight: bold; display: block; margin-bottom: 5px;">1. เหตุผลการยืม: <span style="color:red;">*</span></label>
                        <textarea name="reason_for_borrowing" id="swal_reason" rows="3" required 
                                  style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;"></textarea>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="swal_staff_id" style="font-weight: bold; display: block; margin-bottom: 5px;">2. ระบุพนักงานผู้ให้ยืม (ผู้อนุมัติ): <span style="color:red;">*</span></label>
                        <select name="lending_staff_id" id="swal_staff_id" required 
                                style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                            ${staffOptions}
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="swal_due_date" style="font-weight: bold; display: block; margin-bottom: 5px;">3. วันที่กำหนดคืน: <span style="color:red;">*</span></label>
                        <input type="date" name="due_date" id="swal_due_date" required 
                               style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                </form>`;

            Swal.fire({
                title: `📝 ส่งคำขอยืม: ${equipmentName}`,
                html: formHtml,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'ยืนยันส่งคำขอ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: 'var(--color-success, #16a34a)',
                focusConfirm: false,
                preConfirm: () => {
                    const form = document.getElementById('swalRequestForm');
                    const reason = form.querySelector('#swal_reason').value;
                    const staffId = form.querySelector('#swal_staff_id').value;
                    const dueDate = form.querySelector('#swal_due_date').value;
                    if (!reason || !staffId || !dueDate) {
                        Swal.showValidationMessage('กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบถ้วน');
                        return false;
                    }
                    return fetch('request_borrow_process.php', { 
                        method: 'POST',
                        body: new FormData(form)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status !== 'success') {
                            throw new Error(data.message);
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('ส่งคำขอสำเร็จ!', 'คำขอของคุณถูกส่งไปให้ Admin พิจารณาแล้ว', 'success')
                    .then(() => location.href = 'request_history.php'); // (เปลี่ยนให้เด้งไปหน้า "ประวัติ")
                }
            });
        })
        .catch(error => {
            Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        });
}
</script>

<?php
// 5. เรียกใช้ Footer
include('includes/student_footer.php'); 
?>