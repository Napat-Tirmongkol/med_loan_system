<?php
// student_dashboard.php (อัปเดต V3 Layout)

// 1. "จ้างยาม" (ของ "ผู้ใช้งาน")
include('includes/check_student_session.php'); //
// 2. เรียกใช้ไฟล์เชื่อมต่อ DB
require_once('db_connect.php'); //

// 3. ดึง ID ของผู้ใช้งานที่ Log in อยู่
$student_id = $_SESSION['student_id']; 

// 4. (PHP ... ดึงข้อมูล ... เหมือนเดิม)
// (A) รายการอุปกรณ์ที่ "ว่าง" (Available)
try {
    $stmt_equip = $pdo->prepare("SELECT * FROM med_equipment WHERE status = 'available' ORDER BY name ASC");
    $stmt_equip->execute();
    $equipments = $stmt_equip->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $equipments = [];
    $equip_error = "เกิดข้อผิดพลาดในการดึงข้อมูลอุปกรณ์: " . $e->getMessage();
}

// (B) อุปกรณ์ที่ "ผู้ใช้งาน" กำลังยืมอยู่ (ต้องคืน)
try {
    $sql_borrowed = "SELECT t.*, e.name as equipment_name, e.serial_number 
                     FROM med_transactions t
                     JOIN med_equipment e ON t.equipment_id = e.id
                     WHERE t.borrower_student_id = ? 
                       AND t.status = 'borrowed'
                       AND t.approval_status IN ('approved', 'staff_added')
                     ORDER BY t.due_date ASC";
    
    $stmt_borrowed = $pdo->prepare($sql_borrowed);
    $stmt_borrowed->execute([$student_id]);
    $currently_borrowed = $stmt_borrowed->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $currently_borrowed = [];
    $borrowed_error = "เกิดข้อผิดพลาดในการดึงข้อมูลที่กำลังยืม: " . $e->getMessage();
}

// (C) ประวัติคำขอ "ที่จบไปแล้ว" (Pending, Rejected, Returned)
try {
    $sql_history = "SELECT t.*, e.name as equipment_name, e.serial_number 
                    FROM med_transactions t
                    JOIN med_equipment e ON t.equipment_id = e.id
                    WHERE t.borrower_student_id = ? 
                      AND (t.status = 'returned' OR t.approval_status IN ('pending', 'rejected'))
                    ORDER BY t.borrow_date DESC, t.id DESC";
    
    $stmt_history = $pdo->prepare($sql_history);
    $stmt_history->execute([$student_id]);
    $history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $history = [];
    $history_error = "เกิดข้อผิดพลาดในการดึงประวัติ: " . $e->getMessage();
}

// 5. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "Dashboard ผู้ใช้งาน";
// 6. เรียกใช้ Header (ของ "ผู้ใช้งาน")
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
    </div> <div class="section-card">
        <h2 class="section-title">อุปกรณ์ที่ฉันกำลังยืม (รายการที่ต้องคืน)</h2>
        
        <?php if (isset($borrowed_error)) echo "<p style='color: red;'>$borrowed_error</p>"; ?>
        
        <table>
            <thead>
                <tr>
                    <th>อุปกรณ์</th>
                    <th>เลขซีเรียล</th>
                    <th>วันที่ยืม</th>
                    <th>วันที่กำหนดคืน</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($currently_borrowed)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">คุณไม่มีอุปกรณ์ที่กำลังยืมอยู่</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($currently_borrowed as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['serial_number'] ?? '-'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['borrow_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['due_date'])); ?></td>
                            <td>
                                <?php if ($row['approval_status'] == 'approved'): ?>
                                    <span class="status-badge red">กำลังยืม</span>
                                <?php elseif ($row['approval_status'] == 'staff_added'): ?>
                                    <span class="status-badge blue">(พนักงานดำเนินการ)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div> <div class="section-card">
        <h2 class="section-title">ประวัติคำขอที่ผ่านมา</h2>

        <?php if (isset($history_error)) echo "<p style='color: red;'>" . htmlspecialchars($history_error) . "</p>"; ?>
        
        <table>
            <thead>
                <tr>
                    <th>อุปกรณ์</th>
                    <th>วันที่ส่งคำขอ</th>
                    <th>วันที่กำหนดคืน</th>
                    <th>สถานะคำขอ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">คุณยังไม่มีประวัติการยืม</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['borrow_date'])); ?></td>
                            <td><?php echo $row['due_date'] ? date('d/m/Y', strtotime($row['due_date'])) : '-'; ?></td>
                            <td>
                                <?php if ($row['status'] == 'returned'): ?>
                                    <span class="status-badge green">คืนแล้ว</span>
                                <?php elseif ($row['approval_status'] == 'pending'): ?>
                                    <span class="status-badge yellow">รอดำเนินการ</span>
                                <?php elseif ($row['approval_status'] == 'rejected'): ?>
                                    <span class="status-badge grey">ถูกปฏิเสธ</span>
                                <?php else: ?>
                                    <span class="status-badge grey"><?php echo htmlspecialchars($row['approval_status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div> </div> <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// (ฟังก์ชัน openRequestPopup() ... อัปเดตสีปุ่ม)
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
                confirmButtonColor: 'var(--color-success, #16a34a)', // (อัปเดตสี)
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
                    .then(() => location.reload()); // รีเฟรชหน้า
                }
            });
        })
        .catch(error => {
            Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        });
}
</script>

<?php
// 7. เรียกใช้ Footer (แบบง่ายๆ)
?>
</main> 
</body>
</html>