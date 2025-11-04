<?php
// 1. "จ้างยามมาเฝ้าประตู"
include('includes/check_session.php'); //
// 2. เรียกใช้ไฟล์เชื่อมต่อ DB
require_once('db_connect.php'); //

// 3. (ใหม่) ตรวจสอบ $_GET parameters
$message = '';
$message_type = '';
if (isset($_GET['add']) && $_GET['add'] == 'success') {
    $message = 'เพิ่มอุปกรณ์ใหม่สำเร็จ!'; $message_type = 'success';
} elseif (isset($_GET['edit']) && $_GET['edit'] == 'success') {
    $message = 'แก้ไขข้อมูลอุปกรณ์สำเร็จ!'; $message_type = 'success';
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'success') {
    $message = 'ลบข้อมูลอุปกรณ์สำเร็จ!'; $message_type = 'success';
} elseif (isset($_GET['error'])) {
    $message_type = 'error';
    if ($_GET['error'] == 'fk_constraint') {
        $message = 'ไม่สามารถลบอุปกรณ์ได้ เนื่องจากมีประวัติการยืม/คำขอ ค้างอยู่!';
    } elseif ($_GET['error'] == 'not_found') {
        $message = 'ไม่พบอุปกรณ์ที่ต้องการลบ!';
    } else {
        $message = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($_GET['error']);
    }
}

// 4. ตั้งค่าตัวแปรสำหรับหน้านี้
$page_title = "จัดการอุปกรณ์";
$current_page = "manage_equip";
// 5. เรียกใช้ไฟล์ Header
include('includes/header.php');

// 6. เตรียมดึงข้อมูลอุปกรณ์ (สำหรับตาราง)
try {
    $sql = "SELECT e.*, s.full_name as borrower_name, t.due_date 
            FROM med_equipment e
            LEFT JOIN med_transactions t ON e.id = t.equipment_id AND t.status = 'borrowed' AND t.approval_status IN ('approved', 'staff_added')
            LEFT JOIN med_students s ON t.borrower_student_id = s.id";

    $conditions = [];
    $params = [];

    // (รับค่าตัวกรอง)
    $search_query = $_GET['search'] ?? '';
    $status_query = $_GET['status'] ?? '';

    // (เงื่อนไขที่ 1: ค้นหา)
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $conditions[] = "(e.name LIKE ? OR e.serial_number LIKE ? OR e.description LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    // (เงื่อนไขที่ 2: กรองสถานะ)
    if (!empty($status_query)) {
        $conditions[] = "e.status = ?";
        $params[] = $status_query;
    }

    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY e.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    $equipments = [];
}
?>

<?php if ($message): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #fff; background-color: <?php echo ($message_type == 'success') ? 'var(--color-success)' : 'var(--color-danger)'; ?>;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="header-row">
        <h2><i class="fas fa-tools"></i>จัดการอุปกรณ์</h2>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <button class="add-btn" onclick="openAddEquipmentPopup()">
                <i class="fas fa-plus"></i> เพิ่มอุปกรณ์
            </button>
        <?php endif; ?>
    </div>

    <div class="filter-row">
        <form action="manage_equipment.php" method="GET" style="display: contents;">
            <label for="search_term">ค้นหา:</label>
            <input type="text" name="search" id="search_term" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="ชื่อ/ซีเรียล/รายละเอียด">
            
            <label for="filter_status">สถานะ:</label>
            <select name="status" id="filter_status">
                <option value="">-- ทั้งหมด --</option>
                <option value="available" <?php if ($status_query == 'available') echo 'selected'; ?>>ว่าง</option>
                <option value="borrowed" <?php if ($status_query == 'borrowed') echo 'selected'; ?>>ถูกยืม</option>
                <option value="maintenance" <?php if ($status_query == 'maintenance') echo 'selected'; ?>>ซ่อมบำรุง</option>
            </select>
            
            <button type="submit" class="btn btn-return"><i class="fas fa-filter"></i> กรอง</button>
            <a href="manage_equipment.php" class="btn btn-secondary"><i class="fas fa-times"></i> ล้างค่า</a>
        </form>
    </div>


    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 70px;">รูปภาพ</th> <th>ID</th>
                    <th>ชื่ออุปกรณ์</th>
                    <th>เลขซีเรียล</th>
                    <th>รายละเอียด</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipments)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">ไม่พบอุปกรณ์ตามเงื่อนไขที่กำหนด</td>
                <?php else: ?>
                    <?php foreach ($equipments as $row): ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                                         alt="รูป" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                    <div class="equipment-card-image-placeholder" style="display: none; width: 50px; height: 50px; font-size: 1.5rem;"><i class="fas fa-image"></i></div>
                                <?php else: ?>
                                    <div class="equipment-card-image-placeholder" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['id']; ?></td> <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['serial_number'] ?? '-'); ?></td>
                            <td style="white-space: pre-wrap; min-width: 200px;"><?php echo htmlspecialchars($row['description'] ?? '-'); ?></td>
                            <td>
                                <?php // (Status Badge ... โค้ดส่วนนี้ถูกต้องแล้ว)
                                if ($row['status'] == 'available'): ?>
                                    <span class="status-badge available">ว่าง</span>
                                <?php elseif ($row['status'] == 'borrowed'): ?>
                                    <span class="status-badge borrowed">ถูกยืม</span>
                                    <div style="font-size: 0.9em; margin-top: 5px; color: #555;">
                                        โดย: <strong><?php echo htmlspecialchars($row['borrower_name'] ?? 'N/A'); ?></strong><br>
                                        คืน: <?php echo $row['due_date'] ? date('d/m/Y', strtotime($row['due_date'])) : 'N/A'; ?>
                                    </div>
                                <?php else: // 'maintenance' ?>
                                    <span class="status-badge maintenance">ซ่อมบำรุง</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <?php // (Action Buttons ... โค้ดส่วนนี้ถูกต้องแล้ว)
                                if ($row['status'] == 'available'): ?>
                                    <button type="button" class="btn btn-borrow" onclick="openBorrowPopup(<?php echo $row['id']; ?>)">ยืม</button>
                                <?php elseif ($row['status'] == 'borrowed'): ?>
                                    <?php if (in_array($_SESSION['role'], ['admin', 'employee'])): ?>
                                        <button type="button" class="btn btn-return" onclick="openReturnPopup(<?php echo $row['id']; ?>)">รับคืน</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <button type="button" class="btn btn-manage" style="margin-left: 5px;" onclick="openEditPopup(<?php echo $row['id']; ?>)">แก้ไข</button>
                                    <a href="delete_equipment_process.php?id=<?php echo $row['id']; ?>"
                                       class="btn btn-danger" 
                                       style="margin-left: 5px;" 
                                       onclick="confirmDeleteEquipment(event, <?php echo $row['id']; ?>)">ลบ</a>
                                <?php endif; ?>
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

                <div style="margin-bottom: 15px;">
                    <label for="swal_eq_image_file" style="font-weight: bold; display: block; margin-bottom: 5px;">แนบรูปภาพ (ถ้ามี):</label>
                    <input type="file" name="image_file" id="swal_eq_image_file" accept="image/*" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                </form>`,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: 'บันทึกอุปกรณ์ใหม่',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: 'var(--color-success, #28a745)',
        focusConfirm: false,
        preConfirm: () => {
            const form = document.getElementById('swalAddEquipmentForm');
            const name = form.querySelector('#swal_eq_name').value;
            if (!name) {
                Swal.showValidationMessage('กรุณากรอกชื่ออุปกรณ์');
                return false;
            }
            
            // (สำคัญ) FormData สามารถส่งไฟล์ที่แนบไปกับ fetch ได้เลย
            return fetch('add_equipment_process.php', { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message);
                    return data;
                })
                .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('เพิ่มสำเร็จ!', 'เพิ่มอุปกรณ์ใหม่เรียบร้อย', 'success').then(() => location.href = 'manage_equipment.php?add=success');
        }
    });
}
function confirmDeleteEquipment(event, id) {
    event.preventDefault(); 
    const url = event.currentTarget.href;
    Swal.fire({
        title: "คุณแน่ใจหรือไม่?",
        text: "คุณกำลังจะลบอุปกรณ์นี้ออกจากระบบ! (จะลบได้ต่อเมื่อไม่มีประวัติการยืม)",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33", 
        cancelButtonColor: "#3085d6",
        confirmButtonText: "ใช่, ลบเลย",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>

<?php
// 7. เรียกใช้ไฟล์ Footer (ซึ่งมี JavaScript popups อื่นๆ อยู่)
include('includes/footer.php');
?>