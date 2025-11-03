<?php
// 1. "จ้างยามมาเฝ้าประตู"
include('includes/check_session.php'); //
require_once('db_connect.php');

// 2. ตั้งค่าตัวแปรสำหรับหน้านี้
$page_title = "จัดการประเภทอุปกรณ์";
$current_page = "manage_equip";

// 3. เรียกใช้ไฟล์ Header
include('includes/header.php');

// (ใหม่) 4. ดึงข้อมูลจากตาราง `med_equipment_types`
try {
    // (รับค่าตัวกรอง)
    $search_query = $_GET['search'] ?? '';

    $sql = "SELECT * FROM med_equipment_types";
    if (!empty($search_query)) {
        $sql .= " WHERE name LIKE :search OR description LIKE :search";
    }
    $sql .= " ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    if (!empty($search_query)) {
        $stmt->bindValue(':search', '%' . $search_query . '%');
    }
    $stmt->execute();
    $equipment_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $equipment_types = [];
    $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>

    <!-- (ใหม่) Notification Placeholder -->
    <div id="notification-area" style="display: none; padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #fff;"></div>
    <div class="header-row">
        <h2><i class="fas fa-boxes"></i>จัดการประเภทอุปกรณ์</h2>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <button class="add-btn" onclick="openAddEquipmentTypePopup()">
                <i class="fas fa-plus"></i> เพิ่มประเภทอุปกรณ์
            </button>
        <?php endif; ?>
    </div>

    <div class="filter-row">
        <form action="manage_equipment.php" method="GET" style="display: contents;">
            <label for="search_term">ค้นหา:</label>
            <input type="text" name="search" id="search_term" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="ชื่อประเภท, รายละเอียด">
            
            <button type="submit" class="btn btn-return"><i class="fas fa-filter"></i> กรอง</button>
            <a href="manage_equipment.php" class="btn btn-secondary"><i class="fas fa-times"></i> ล้างค่า</a>
        </form>
    </div>


    <!-- (Desktop View) Table - (แก้ไข) ย้ายคลาสมาที่ <table> -->
    <div class="table-container">
        <table class="desktop-only">
            <thead>
                <tr>
                    <th style="width: 70px;">รูปภาพ</th>
                    <th>ชื่อประเภทอุปกรณ์</th>
                    <th>รายละเอียด</th>
                    <th style="width: 150px;">จำนวน (ว่าง/ทั้งหมด)</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($error_message)): ?>
                    <tr><td colspan="5" style="color: red; text-align: center;"><?php echo $error_message; ?></td></tr>
                <?php elseif (empty($equipment_types)): ?>
                    <tr><td colspan="5" style="text-align: center;">ไม่พบข้อมูลประเภทอุปกรณ์</td></tr>
                <?php else: ?>
                    <?php foreach ($equipment_types as $type): ?>
                        <tr>
                            <td>
                                <?php if (!empty($type['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($type['image_url']); ?>" alt="รูป" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                <?php else: ?>
                                    <div class="equipment-card-image-placeholder" style="width: 50px; height: 50px; font-size: 1.5rem;"><i class="fas fa-camera"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($type['name']); ?></strong></td>
                            <td style="white-space: pre-wrap; min-width: 200px;"><?php echo htmlspecialchars($type['description'] ?? '-'); ?></td>
                            <td>
                                <span style="font-size: 1.2em; font-weight: bold; color: var(--color-success);"><?php echo $type['available_quantity']; ?></span>
                                / <?php echo $type['total_quantity']; ?>
                            </td>
                            <td class="action-buttons">
                                <button type="button" class="btn btn-borrow" onclick="openBorrowPopup(<?php echo $type['id']; ?>)" <?php echo ($type['available_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-hand-paper"></i> ยืม
                                </button>
                                <button type="button" class="btn btn-manage" onclick="openEditEquipmentTypePopup(<?php echo $type['id']; ?>)">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="location.href='manage_items.php?type_id=<?php echo $type['id']; ?>'">
                                    <i class="fas fa-list-ol"></i> จัดการรายชิ้น
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openAddEquipmentTypePopup() {
    Swal.fire({
        title: '➕ เพิ่มประเภทอุปกรณ์ใหม่',
        html: `
            <form id="swalAddForm" style="text-align: left; margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label for="swal_eq_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อประเภทอุปกรณ์:</label>
                    <input type="text" name="name" id="swal_eq_name" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
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
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: 'var(--color-success, #28a745)',
        focusConfirm: false,
        preConfirm: () => {
            const form = document.getElementById('swalAddForm');
            const name = form.querySelector('#swal_eq_name').value;
            if (!name) {
                Swal.showValidationMessage('กรุณากรอกชื่อประเภทอุปกรณ์');
                return false;
            }
            return fetch('add_equipment_type_process.php', { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message);
                    return data;
                })
                .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('เพิ่มสำเร็จ!', 'เพิ่มประเภทอุปกรณ์ใหม่เรียบร้อย', 'success').then(() => location.reload());
        }
    });
}
</script>

<?php
include('includes/footer.php');
?>