<?php
// manage_items.php
// (ไฟล์ใหม่)

// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// 3. รับ type_id จาก URL
$type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;
if ($type_id == 0) {
    die("ไม่ได้ระบุประเภทอุปกรณ์ <a href='manage_equipment.php'>กลับไปหน้าจัดการประเภท</a>");
}

// 4. ดึงข้อมูล "ประเภท" อุปกรณ์
try {
    $stmt_type = $pdo->prepare("SELECT * FROM med_equipment_types WHERE id = ?");
    $stmt_type->execute([$type_id]);
    $equipment_type = $stmt_type->fetch(PDO::FETCH_ASSOC);

    if (!$equipment_type) {
        die("ไม่พบประเภทอุปกรณ์นี้ <a href='manage_equipment.php'>กลับไปหน้าจัดการประเภท</a>");
    }

    // 5. ดึงข้อมูล "ชิ้น" อุปกรณ์ทั้งหมดในประเภทนี้
    $stmt_items = $pdo->prepare("SELECT * FROM med_equipment_items WHERE type_id = ? ORDER BY name ASC, serial_number ASC");
    $stmt_items->execute([$type_id]);
    $equipment_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage());
}

// 6. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "จัดการอุปกรณ์รายชิ้น - " . htmlspecialchars($equipment_type['name']);
$current_page = "manage_equip";
include('includes/header.php');
?>

<div class="header-row">
    <div>
        <h2 style="display: flex; align-items: center; gap: 1rem;">
            <a href="manage_equipment.php" class="btn btn-secondary" style="font-size: 1rem;">
                <i class="fas fa-chevron-left"></i>
            </a>
            <i class="fas fa-list-ol"></i>
            จัดการอุปกรณ์รายชิ้น: <strong><?php echo htmlspecialchars($equipment_type['name']); ?></strong>
        </h2>
        <p class="text-muted" style="margin-left: 55px;">
            เพิ่ม/ลบ/แก้ไข อุปกรณ์แต่ละชิ้นที่อยู่ในประเภทนี้
        </p>
    </div>
    <button class="add-btn" onclick="openAddItemPopup(<?php echo $type_id; ?>, '<?php echo htmlspecialchars(addslashes($equipment_type['name'])); ?>')">
        <i class="fas fa-plus"></i> เพิ่มอุปกรณ์ชิ้นใหม่
    </button>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">ID ชิ้น</th>
                <th>ชื่อเฉพาะ (ถ้ามี)</th>
                <th>เลขซีเรียล</th>
                <th>รายละเอียด/หมายเหตุ</th>
                <th style="width: 150px;">สถานะ</th>
                <th style="width: 180px;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($equipment_items)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">ยังไม่มีอุปกรณ์รายชิ้นในประเภทนี้</td>
                </tr>
            <?php else: ?>
                <?php foreach ($equipment_items as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['serial_number'] ?? '-'); ?></td>
                        <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($item['description'] ?? '-'); ?></td>
                        <td>
                            <?php
                                $status = $item['status'];
                                $badge_class = 'available';
                                if ($status == 'borrowed') {
                                    $badge_class = 'borrowed';
                                } elseif ($status == 'maintenance') {
                                    $badge_class = 'maintenance';
                                }
                                echo "<span class='status-badge {$badge_class}'>" . ucfirst($status) . "</span>";
                            ?>
                        </td>
                        <td class="action-buttons">
                            <?php if ($item['status'] != 'borrowed'): // (ถ้าไม่ถูกยืมอยู่) ?>
                                <button type="button" class="btn btn-manage" onclick="openEditItemPopup(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </button>
                                <button type="button" class="btn btn-danger" onclick="confirmDeleteItem(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i> ลบ
                                </button>
                            <?php else: // (ถ้าถูกยืมอยู่) ?>
                                <span class="text-muted">ถูกยืมอยู่</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
include('includes/footer.php');
?>

```

#### 2. สร้างไฟล์ Backend สำหรับเพิ่มอุปกรณ์รายชิ้น (`add_item_process.php`)

```diff
--- /dev/null
+++ b/c:/xampp/htdocs/medloan_system/add_item_process.php
@@ -0,0 +1,71 @@
+<?php
+// add_item_process.php
+// (ไฟล์ใหม่)
+
+include('includes/check_session_ajax.php');
+require_once('db_connect.php');
+require_once('includes/log_function.php');
+
+if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
+    header('Content-Type: application/json');
+    echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ']);
+    exit;
+}
+header('Content-Type: application/json');
+
+$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];
+
+if ($_SERVER["REQUEST_METHOD"] == "POST") {
+    $type_id       = isset($_POST['type_id']) ? (int)$_POST['type_id'] : 0;
+    $name          = isset($_POST['name']) ? trim($_POST['name']) : '';
+    $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
+    $description   = isset($_POST['description']) ? trim($_POST['description']) : null;
+
+    if ($type_id == 0 || empty($name)) {
+        $response['message'] = 'ข้อมูลไม่ครบถ้วน (Type ID หรือ Name)';
+        echo json_encode($response);
+        exit;
+    }
+
+    try {
+        $pdo->beginTransaction();
+
+        // 1. เช็ค Serial Number ซ้ำ (ถ้ามีการกรอก)
+        if (!empty($serial_number)) {
+            $stmt_check = $pdo->prepare("SELECT id FROM med_equipment_items WHERE serial_number = ?");
+            $stmt_check->execute([$serial_number]);
+            if ($stmt_check->fetch()) {
+                throw new Exception("เลขซีเรียล '$serial_number' นี้มีในระบบแล้ว");
+            }
+        }
+
+        // 2. INSERT ข้อมูลลง med_equipment_items
+        $sql_item = "INSERT INTO med_equipment_items (type_id, name, serial_number, description, status) VALUES (?, ?, ?, ?, 'available')";
+        $stmt_item = $pdo->prepare($sql_item);
+        $stmt_item->execute([$type_id, $name, $serial_number, $description]);
+        $new_item_id = $pdo->lastInsertId();
+
+        // 3. อัปเดตจำนวนใน med_equipment_types
+        $sql_type = "UPDATE med_equipment_types SET total_quantity = total_quantity + 1, available_quantity = available_quantity + 1 WHERE id = ?";
+        $stmt_type = $pdo->prepare($sql_type);
+        $stmt_type->execute([$type_id]);
+
+        // 4. บันทึก Log
+        $admin_user_id = $_SESSION['user_id'] ?? null;
+        $admin_user_name = $_SESSION['full_name'] ?? 'System';
+        $log_desc = "Admin '{$admin_user_name}' ได้เพิ่มอุปกรณ์ชิ้นใหม่ (ID: {$new_item_id}) ชื่อ '{$name}' (SN: {$serial_number}) เข้าไปในประเภท ID: {$type_id}";
+        log_action($pdo, $admin_user_id, 'create_equipment_item', $log_desc);
+
+        $pdo->commit();
+
+        $response['status'] = 'success';
+        $response['message'] = 'เพิ่มอุปกรณ์ชิ้นใหม่สำเร็จ';
+
+    } catch (Exception $e) {
+        $pdo->rollBack();
+        $response['message'] = $e->getMessage();
+    }
+}
+
+echo json_encode($response);
+exit;
+?>
```

#### 3. เพิ่มฟังก์ชัน JavaScript ใน `includes/footer.php`

```diff
--- a/c:/xampp/htdocs/medloan_system/includes/footer.php
+++ b/c:/xampp/htdocs/medloan_system/includes/footer.php
@@ -275,6 +275,50 @@
         });
 }
 
+// (ใหม่) ฟังก์ชันสำหรับหน้า จัดการรายชิ้น
+function openAddItemPopup(typeId, typeName) {
+    Swal.fire({
+        title: `➕ เพิ่มชิ้นอุปกรณ์ใหม่`,
+        html: `
+            <p style="text-align: left;">กำลังเพิ่มอุปกรณ์เข้าไปในประเภท: <strong>${typeName}</strong></p>
+            <form id="swalAddItemForm" style="text-align: left; margin-top: 20px;">
+                <input type="hidden" name="type_id" value="${typeId}">
+                <div style="margin-bottom: 15px;">
+                    <label for="swal_item_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อเฉพาะ (ถ้ามี):</label>
+                    <input type="text" name="name" id="swal_item_name" value="${typeName}" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
+                    <small>ปกติจะใช้ชื่อเดียวกับประเภท แต่สามารถตั้งชื่อเฉพาะได้ เช่น 'รถเข็น A-01'</small>
+                </div>
+                <div style="margin-bottom: 15px;">
+                    <label for="swal_item_serial" style="font-weight: bold; display: block; margin-bottom: 5px;">เลขซีเรียล (Serial Number):</label>
+                    <input type="text" name="serial_number" id="swal_item_serial" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
+                </div>
+                <div style="margin-bottom: 15px;">
+                    <label for="swal_item_desc" style="font-weight: bold; display: block; margin-bottom: 5px;">รายละเอียด/หมายเหตุ:</label>
+                    <textarea name="description" id="swal_item_desc" rows="2" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;"></textarea>
+                </div>
+            </form>`,
+        showCancelButton: true,
+        confirmButtonText: 'บันทึก',
+        preConfirm: () => {
+            const form = document.getElementById('swalAddItemForm');
+            if (!form.checkValidity()) {
+                Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
+                return false;
+            }
+            return fetch('add_item_process.php', { method: 'POST', body: new FormData(form) })
+                .then(response => response.json())
+                .then(data => {
+                    if (data.status !== 'success') throw new Error(data.message);
+                    return data;
+                })
+                .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
+        }
+    }).then((result) => {
+        if (result.isConfirmed) {
+            Swal.fire('เพิ่มสำเร็จ!', 'เพิ่มอุปกรณ์ชิ้นใหม่เรียบร้อย', 'success').then(() => location.reload());
+        }
+    });
+}
 // ( ... โค้ดสำหรับ Hamburger ... )
 const hamburgerBtn = document.getElementById('hamburgerBtn');
 const sidebar = document.querySelector('.sidebar');

```

ตอนนี้คุณสามารถคลิกปุ่ม "จัดการรายชิ้น" จากหน้า `manage_equipment.php` เพื่อเข้าไปยังหน้าสำหรับจัดการอุปกรณ์แต่ละชิ้นได้แล้วครับ หากต้องการให้ผมสร้างฟังก์ชัน "แก้ไข" และ "ลบ" สำหรับอุปกรณ์รายชิ้นต่อ สามารถแจ้งได้เลยนะครับ

<!--
[PROMPT_SUGGESTION]เพิ่มฟังก์ชัน "แก้ไข" และ "ลบ" สำหรับอุปกรณ์รายชิ้นในหน้า manage_items.php[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]ปรับปรุงหน้า Dashboard ให้แสดงกราฟสรุปตามประเภทอุปกรณ์[/PROMPT_SUGGESTION]
-->