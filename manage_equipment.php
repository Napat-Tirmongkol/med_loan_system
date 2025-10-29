<?php
// 1. "จ้างยามมาเฝ้าประตู"
include('includes/check_session.php');
// 2. เรียกใช้ไฟล์เชื่อมต่อ DB
require_once('db_connect.php');
// 3. ตั้งค่าตัวแปรสำหรับหน้านี้
$page_title = "จัดการอุปกรณ์";
$current_page = "manage_equip";
// 4. เรียกใช้ไฟล์ Header
include('includes/header.php');
// 5. เตรียมดึงข้อมูลอุปกรณ์ (สำหรับตาราง)
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
        <h2>⚙️ จัดการอุปกรณ์ทั้งหมด</h2>

        <?php // ปุ่ม "เพิ่มอุปกรณ์ใหม่" (Admin Only) ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <button type="button" class="btn btn-borrow" style="font-size: 16px;" onclick="openAddEquipmentPopup()" title="เพิ่มอุปกรณ์ใหม่">
                <i class="fa-solid fa-plus"></i> เพิ่มอุปกรณ์
            </button>
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
                                <button type="button" class="btn btn-borrow" onclick="openBorrowPopup(<?php echo $row['id']; ?>)">ยืม</button>
                            <?php elseif ($row['status'] == 'borrowed'): ?>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <button type="button" class="btn btn-return" onclick="openReturnPopup(<?php echo $row['id']; ?>)">รับคืน</button>
                                <?php else: ?>
                                    <span style="color: #6c757d;">(ถูกยืมอยู่)</span>
                                <?php endif; ?>
                            <?php else: // 'maintenance' ?>
                                <span style="color: #6c757d;">(ซ่อมบำรุง)</span>
                            <?php endif; ?>

                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <button type="button" class="btn btn-manage" style="margin-left: 5px;" onclick="openEditPopup(<?php echo $row['id']; ?>)">แก้ไข</button>
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
// (ฟังก์ชัน openBorrowPopup, openEditPopup, openReturnPopup ... จาก footer.php)
// เราต้องคัดลอกมาไว้ที่นี่ด้วย เพราะหน้านี้ไม่ได้เรียก footer.php โดยตรง
// (หรือวิธีที่ดีกว่าคือ ย้าย Script ทั้งหมดไปไว้ใน footer.php แล้วเรียก footer.php ที่นี่)

// *** สมมติว่าฟังก์ชัน openBorrowPopup, openEditPopup, openReturnPopup อยู่ใน footer.php ***

// 4. ฟังก์ชันใหม่สำหรับ "เพิ่มอุปกรณ์" (Popup Form)
function openAddEquipmentPopup() {
    Swal.fire({
        title: '➕ เพิ่มอุปกรณ์ใหม่',
        html: `
            <form id="swalAddEquipmentForm" style="text-align: left; margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label for="swal_eq_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่ออุปกรณ์:</label>
                    <input type="text" name="name" id="swal_eq_name" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="swal_eq_serial" style="font-weight: bold; display: block; margin-bottom: 5px;">เลขซีเรียล (ถ้ามี):</label>
                    <input type="text" name="serial_number" id="swal_eq_serial" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="swal_eq_desc" style="font-weight: bold; display: block; margin-bottom: 5px;">รายละเอียด:</label>
                    <textarea name="description" id="swal_eq_desc" rows="3" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;"></textarea>
                </div>
            </form>`,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: 'บันทึกอุปกรณ์ใหม่',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#28a745', // สีเขียว
        focusConfirm: false,
        preConfirm: () => {
            const form = document.getElementById('swalAddEquipmentForm');
            const name = form.querySelector('#swal_eq_name').value;
            if (!name) {
                Swal.showValidationMessage('กรุณากรอกชื่ออุปกรณ์');
                return false;
            }

            return fetch('add_equipment_process.php', {
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
        if (result.isConfirmed) {
            Swal.fire('เพิ่มสำเร็จ!', 'เพิ่มอุปกรณ์ใหม่เรียบร้อย', 'success')
            .then(() => location.reload()); // รีเฟรชหน้า manage_equipment.php
        }
    });
}
</script>


<?php
// 7. เรียกใช้ไฟล์ Footer (ซึ่งมี JavaScript popups อื่นๆ อยู่)
include('includes/footer.php');
?>