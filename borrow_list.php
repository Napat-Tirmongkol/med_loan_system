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

<div class="main-container">

    <div class="section-card">
        <h2 class="section-title">อุปกรณ์ที่พร้อมให้ยืม</h2>
        <p class="text-muted">เลือกอุปกรณ์ที่คุณต้องการส่งคำขอยืม</p>
        
        <?php if (isset($equip_error)) echo "<p style='color: red;'>$equip_error</p>"; ?>
        
        <table>
            <thead>
                <tr>
                    <th>อุปกรณ์</th>
                    <th>เลขซีเรียล</th>
                    <th>รายละเอียด</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipments)): ?>
                    <tr><td colspan="4" style="text-align: center;">ไม่มีอุปกรณ์ที่ว่างในขณะนี้</td></tr>
                <?php else: ?>
                    <?php foreach ($equipments as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['serial_number'] ?? '-'); ?></td>
                            <td style="white-space: pre-wrap; min-width: 200px;"><?php echo htmlspecialchars($row['description'] ?? '-'); ?></td>
                            <td>
                                <button type="button" 
                                        class="btn-loan" 
                                        onclick="openRequestPopup(<?php echo $row['id']; ?>)">ส่งคำขอยืม</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div> 

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openRequestPopup(equipmentId) {
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
                title: '📝 ส่งคำขอยืมอุปกรณ์',
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