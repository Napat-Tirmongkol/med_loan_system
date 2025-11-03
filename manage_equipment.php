<?php
// 1. "จ้างยามมาเฝ้าประตู"
include('includes/check_session.php'); //
require_once('db_connect.php');

// 2. ตั้งค่าตัวแปรสำหรับหน้านี้
$page_title = "จัดการประเภทอุปกรณ์"; // (แก้ชื่อ)
$current_page = "manage_equip";

// 3. เรียกใช้ไฟล์ Header
include('includes/header.php');

// (ใหม่) 4. ดึงข้อมูลจากตาราง `med_equipment_types` แทน
try {
    // (รับค่าตัวกรอง)
    $search_query = $_GET['search'] ?? '';

    $sql = "SELECT * FROM med_equipment_types";
    if (!empty($search_query)) {
        $sql .= " WHERE name LIKE :search OR description LIKE :search"; // (แก้เงื่อนไข)
    }
    $sql .= " ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    if (!empty($search_query)) {
        $stmt->bindValue(':search', '%' . $search_query . '%');
    }
    $stmt->execute();
    $equipment_types = $stmt->fetchAll(PDO::FETCH_ASSOC); // (เปลี่ยนชื่อตัวแปร)
} catch (PDOException $e) {
    $equipment_types = []; // (เปลี่ยนชื่อตัวแปร)
    $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>

    <!-- (ใหม่) Notification Placeholder -->
    <div id="notification-area" style="display: none; padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #fff;"></div>
    <div class="header-row">
        <h2><i class="fas fa-boxes"></i>จัดการประเภทอุปกรณ์</h2> <!-- (แก้ Icon และ Title) -->
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <button class="add-btn" onclick="openAddEquipmentTypePopup()"> <!-- (แก้ชื่อฟังก์ชัน) -->
                <i class="fas fa-plus"></i> เพิ่มประเภทอุปกรณ์
            </button>
        <?php endif; ?>
    </div>

    <div class="filter-row">
        <form action="manage_equipment.php" method="GET" style="display: contents;"> <!-- (แก้ Action) -->
            <label for="search_term">ค้นหา:</label>
            <input type="text" name="search" id="search_term" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="ชื่อประเภท, รายละเอียด"> <!-- (แก้ Placeholder) -->
            
            <button type="submit" class="btn btn-return"><i class="fas fa-filter"></i> กรอง</button>
            <a href="manage_equipment.php" class="btn btn-secondary"><i class="fas fa-times"></i> ล้างค่า</a>
        </form>
    </div>


    <!-- (Desktop View) Table - (แก้โครงสร้างตาราง) -->
    <div class="table-container">
        <table class="desktop-only">
            <thead>
                <tr>
                    <th style="width: 70px;">รูปภาพ</th>
                    <th>ชื่อประเภทอุปกรณ์</th>
                    <th>รายละเอียด</th>
                    <th style="width: 150px;">จำนวน (ว่าง/ทั้งหมด)</th> <!-- (แก้หัวตาราง) -->
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($error_message)): ?>
                    <tr><td colspan="5" style="color: red; text-align: center;"><?php echo $error_message; ?></td></tr>
                <?php elseif (empty($equipment_types)): ?>
                    <tr><td colspan="5" style="text-align: center;">ไม่พบข้อมูลประเภทอุปกรณ์</td></tr> <!-- (แก้ข้อความ) -->
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
                                <button type="button" class="btn btn-borrow" onclick="openBorrowPopup(<?php echo $type['id']; ?>)" <?php echo ($type['available_quantity'] <= 0) ? 'disabled' : ''; ?>> <!-- (แก้ Popup) -->
                                    <i class="fas fa-hand-paper"></i> ยืม
                                </button>
                                <button type="button" class="btn btn-manage" onclick="openEditEquipmentTypePopup(<?php echo $type['id']; ?>)"> <!-- (แก้ Popup) -->
                                    <i class="fas fa-edit"></i> แก้ไข
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="openManageItemsPopup(<?php echo $type['id']; ?>)"> <!-- (เปลี่ยนเป็น Popup) -->
                                    <i class="fas fa-list-ol"></i> จัดการรายชิ้น
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- (Mobile View) Card List -->
    <div class="mobile-only" style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (isset($error_message)): ?>
            <div class="history-card" style="color: red; justify-content: center;"><?php echo $error_message; ?></div>
        <?php elseif (empty($equipment_types)): ?>
            <div class="history-card" style="justify-content: center;">ไม่พบข้อมูลประเภทอุปกรณ์</div>
        <?php else: ?>
            <?php foreach ($equipment_types as $type): ?>
                <div class="history-card">
                    <div class="history-card-icon">
                        <?php if (!empty($type['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($type['image_url']); ?>" alt="รูป" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                            <div class="equipment-card-image-placeholder" style="width: 50px; height: 50px; font-size: 1.5rem; border-radius: 8px;"><i class="fas fa-camera"></i></div>
                        <?php endif; ?>
                    </div>

                    <div class="history-card-info">
                        <h4><?php echo htmlspecialchars($type['name']); ?></h4>
                        <p>
                            จำนวน: 
                            <span style="font-weight: bold; color: var(--color-success);"><?php echo $type['available_quantity']; ?></span>
                            / <?php echo $type['total_quantity']; ?>
                        </p>
                    </div>

                    <div class="pending-card-actions">
                        <button type="button" class="btn btn-borrow" onclick="openBorrowPopup(<?php echo $type['id']; ?>)" <?php echo ($type['available_quantity'] <= 0) ? 'disabled' : ''; ?>>
                            <i class="fas fa-hand-paper"></i> ยืม
                        </button>
                        <button type="button" class="btn btn-manage" onclick="openEditEquipmentTypePopup(<?php echo $type['id']; ?>)">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="openManageItemsPopup(<?php echo $type['id']; ?>)">
                            <i class="fas fa-list-ol"></i> จัดการ
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
include('includes/footer.php');
?>