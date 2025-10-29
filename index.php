<?php
// 1. "จ้างยามมาเฝ้าประตู"
include('includes/check_session.php'); 
// 2. เรียกใช้ไฟล์เชื่อมต่อ DB
require_once('db_connect.php');
// 3. ตั้งค่าตัวแปรสำหรับหน้านี้
$page_title = "Dashboard - ภาพรวม";
$current_page = "index"; 
// 4. เรียกใช้ไฟล์ Header
include('includes/header.php'); 
// 5. เตรียมดึงข้อมูลอุปกรณ์
try {
    $stmt = $pdo->prepare("SELECT * FROM med_equipment ORDER BY name ASC");
    $stmt->execute();
    $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    $equipments = []; 
}
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>รายการอุปกรณ์ทั้งหมด</h2>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <a href="add_equipment_form.php" class="btn btn-borrow" style="font-size: 16px;">
                ➕ เพิ่มอุปกรณ์ใหม่
            </a>
        <?php endif; ?>
    </div>
    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่ออุปกรณ์</th>
                <th>เลขซีเรียล</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($equipments)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">ยังไม่มีอุปกรณ์ในระบบ</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; ?>
                <?php foreach ($equipments as $row): ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                        <td>
                            <?php if ($row['status'] == 'available'): ?>
                                <span class="status status-available">ว่าง</span>
                            <?php elseif ($row['status'] == 'borrowed'): ?>
                                <span class="status status-borrowed">ถูกยืม</span>
                            <?php else: // 'maintenance' ?>
                                <span class="status status-maintenance">ซ่อมบำรุง</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'available'): ?>
                                <button type="button" 
                                        class="btn btn-borrow" 
                                        onclick="openBorrowPopup(<?php echo $row['id']; ?>)">ยืม</button>
                            <?php elseif ($row['status'] == 'borrowed'): ?>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <a href="return_form.php?id=<?php echo $row['id']; ?>" class="btn btn-return">รับคืน</a>
                                <?php else: ?>
                                    <span style="color: #6c757d;">(ถูกยืมอยู่)</span>
                                <?php endif; ?>
                            <?php else: // 'maintenance' ?>
                                <span style="color: #6c757d;">(ซ่อมบำรุง)</span>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <button type="button" 
                                        class="btn btn-manage" 
                                        style="margin-left: 5px;" 
                                        onclick="openEditPopup(<?php echo $row['id']; ?>)">แก้ไข</button>
                            <?php endif; ?>
                            </td>
                    </tr>
                <?php $i++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// (ฟังก์ชัน openBorrowPopup() ... จากครั้งก่อน)
function openBorrowPopup(equipmentId) {
    Swal.fire({ title: 'กำลังโหลดข้อมูล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    fetch(`get_borrow_form_data.php?id=${equipmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            let borrowerOptions = '<option value="">--- กรุณาเลือกผู้ยืม ---</option>';
            if (data.borrowers.length > 0) {
                data.borrowers.forEach(b => { borrowerOptions += `<option value="${b.id}">${b.full_name} (${b.contact_info || 'N/A'})</option>`; });
            } else {
                borrowerOptions = '<option value="" disabled>ยังไม่มีข้อมูลผู้ยืมในระบบ</option>';
            }
            const formHtml = `... (โค้ดฟอร์มยืมของคุณ) ...`; // (ย่อส่วนนี้เพื่อความกระชับ)
            
            Swal.fire({
                title: '📝 ฟอร์มยืมอุปกรณ์',
                html: `
                <div style="background: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                    <p><strong>อุปกรณ์:</strong> ${data.equipment.name}</p>
                    <p><strong>ซีเรียล:</strong> ${data.equipment.serial_number || 'N/A'}</p>
                </div>
                <form id="swalBorrowForm" style="text-align: left; margin-top: 20px;">
                    <input type="hidden" name="equipment_id" value="${data.equipment.id}">
                    <div style="margin-bottom: 15px;">
                        <label for="swal_borrower_id" style="font-weight: bold; display: block; margin-bottom: 5px;">ผู้ยืม:</label>
                        <select name="borrower_id" id="swal_borrower_id" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                            ${borrowerOptions}
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="swal_due_date" style="font-weight: bold; display: block; margin-bottom: 5px;">วันที่กำหนดคืน:</label>
                        <input type="date" name="due_date" id="swal_due_date" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                </form>`,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'ยืนยันการยืม',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#28a745',
                focusConfirm: false,
                preConfirm: () => {
                    const form = document.getElementById('swalBorrowForm');
                    if (!form.checkValidity()) {
                         Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                         return false;
                    }
                    return fetch('borrow_process.php', { method: 'POST', body: new FormData(form) })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status !== 'success') throw new Error(data.message);
                            return data;
                        })
                        .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('ยืมสำเร็จ!', 'บันทึกข้อมูลการยืมเรียบร้อย', 'success').then(() => location.reload());
                }
            });
        })
        .catch(error => {
            Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        });
}


// 2. ฟังก์ชันใหม่สำหรับ "แก้ไข" (Popup Form)
function openEditPopup(equipmentId) {
    // 1. แสดง Popup "กำลังโหลด..."
    Swal.fire({
        title: 'กำลังโหลดข้อมูล...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // 2. ดึงข้อมูล "เก่า" ของอุปกรณ์ (AJAX GET)
    fetch(`get_equipment_data.php?id=${equipmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                throw new Error(data.message); // เช่น "ไม่พบอุปกรณ์"
            }
            
            const equip = data.equipment;

            // 3. สร้าง HTML <option> สำหรับสถานะ (Status)
            // (ตรรกะที่ป้องกันการเปลี่ยนสถานะ 'borrowed')
            let statusOptions = '';
            if (equip.status === 'borrowed') {
                statusOptions = `<option value="borrowed" selected disabled>ถูกยืม (Borrowed) - (ต้องรับคืนก่อน)</option>`;
            } else {
                statusOptions = `
                    <option value="available" ${equip.status === 'available' ? 'selected' : ''}>ว่าง (Available)</option>
                    <option value="maintenance" ${equip.status === 'maintenance' ? 'selected' : ''}>ซ่อมบำรุง (Maintenance)</option>
                `;
            }

            // 4. สร้าง HTML สำหรับฟอร์มทั้งหมด
            const formHtml = `
                <form id="swalEditForm" style="text-align: left; margin-top: 20px;">
                    <input type="hidden" name="equipment_id" value="${equip.id}">
                    
                    <div style="margin-bottom: 15px;">
                        <label for="swal_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่ออุปกรณ์:</label>
                        <input type="text" name="name" id="swal_name" value="${equip.name}" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="swal_serial" style="font-weight: bold; display: block; margin-bottom: 5px;">เลขซีเรียล:</label>
                        <input type="text" name="serial_number" id="swal_serial" value="${equip.serial_number || ''}" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="swal_status" style="font-weight: bold; display: block; margin-bottom: 5px;">สถานะ:</label>
                        <select name="status" id="swal_status" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                            ${statusOptions}
                        </select>
                    </div>
                </form>`;

            // 5. เปิด SweetAlert Popup ที่มีฟอร์ม
            Swal.fire({
                title: '🔧 แก้ไขข้อมูลอุปกรณ์',
                html: formHtml,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'บันทึกการเปลี่ยนแปลง',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#007bff', // สีน้ำเงิน
                focusConfirm: false,
                preConfirm: () => {
                    // 6. ดึงข้อมูลจากฟอร์มใน Popup
                    const form = document.getElementById('swalEditForm');
                    const name = form.querySelector('#swal_name').value;
                    if (!name) {
                        Swal.showValidationMessage('กรุณากรอกชื่ออุปกรณ์');
                        return false; // หยุด
                    }

                    // 7. ส่งข้อมูลไปเบื้องหลัง (AJAX - POST)
                    return fetch('edit_process.php', {
                        method: 'POST',
                        body: new FormData(form) // ส่งข้อมูลฟอร์ม
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status !== 'success') {
                            throw new Error(data.message); // เช่น "Serial ซ้ำ"
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`);
                    });
                }
            }).then((result) => {
                // 8. เมื่อทุกอย่างสำเร็จ
                if (result.isConfirmed) {
                    Swal.fire('บันทึกสำเร็จ!', 'แก้ไขข้อมูลอุปกรณ์เรียบร้อย', 'success')
                    .then(() => location.reload()); // รีเฟรชหน้า
                }
            });
        })
        .catch(error => {
            // 9. กรณีดึงข้อมูล (ข้อ 2) ล้มเหลว
            Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        });
}
</script>

<?php
// 8. เรียกใช้ไฟล์ Footer
include('includes/footer.php'); 
?>