<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php'); //

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); //
    exit;
}

// 3. (Query ที่ 1) ดึงข้อมูลผู้ใช้งาน (med_students)
try {
    $sql_students = "SELECT 
                s.*, 
                u.id as linked_user_id 
            FROM med_students s
            LEFT JOIN med_users u ON s.line_user_id = u.linked_line_user_id
            ORDER BY s.full_name ASC";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $student_error = "เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้งาน: " . $e->getMessage();
    $students = [];
}

// 4. (Query ที่ 2) ดึงข้อมูลพนักงาน (med_users)
try {
    $sql_staff = "SELECT 
                    u.id, u.username, u.full_name, u.role, u.linked_line_user_id,
                    s.full_name as linked_student_name
                  FROM med_users u
                  LEFT JOIN med_students s ON u.linked_line_user_id = s.line_user_id
                  ORDER BY u.role ASC, u.username ASC";
    $stmt_staff = $pdo->prepare($sql_staff);
    $stmt_staff->execute();
    $staff_accounts = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $staff_error = "เกิดข้อผิดพลาดในการดึงข้อมูลพนักงาน: " . $e->getMessage();
    $staff_accounts = [];
}


// 5. (โค้ดเช็ค $_GET)
$message = '';
$message_type = '';
if (isset($_GET['add']) && $_GET['add'] == 'success') {
    $message = 'เพิ่มผู้ใช้งานใหม่สำเร็จ!'; $message_type = 'success';
} elseif (isset($_GET['edit']) && $_GET['edit'] == 'success') {
    $message = 'แก้ไขข้อมูลผู้ใช้งานสำเร็จ!'; $message_type = 'success';
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'success') {
    $message = 'ลบข้อมูลผู้ใช้งานสำเร็จ!'; $message_type = 'success';
} elseif (isset($_GET['promote']) && $_GET['promote'] == 'success') {
    $message = 'เลื่อนขั้นผู้ใช้งานเป็นพนักงานสำเร็จ!'; $message_type = 'success';
} elseif (isset($_GET['staff_op']) && $_GET['staff_op'] == 'success') {
    $message = 'ดำเนินการกับบัญชีพนักงานสำเร็จ!'; $message_type = 'success';
} elseif (isset($_GET['error'])) {
    $message_type = 'error';
    if ($_GET['error'] == 'fk_constraint') {
        $message = 'ไม่สามารถลบผู้ใช้งานได้ เนื่องจากมีประวัติการทำรายการค้างอยู่!';
    } else {
        $message = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($_GET['error']);
    }
}


// 6. ตั้งค่าตัวแปรสำหรับ Header
$page_title = "จัดการผู้ใช้งาน";
$current_page = "manage_user";
include('includes/header.php');
?>

<?php if ($message): ?>
    <div style="padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #fff; background-color: <?php echo ($message_type == 'success') ? 'var(--color-success)' : 'var(--color-danger)'; ?>;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="header-row">
    <h2><i class="fas fa-users"></i> 👥 จัดการผู้ใช้งาน (User)</h2>
    <button class="add-btn" onclick="openAddStudentPopup()" style="background-color: var(--color-info);">
        <i class="fas fa-plus"></i> เพิ่มผู้ใช้งาน (โดย Admin)
    </button>
</div>

<div class="table-container" style="margin-bottom: 2rem;">
    <?php if (isset($student_error)) echo "<p style='color: red; padding: 15px;'>$student_error</p>"; ?>
    <table>
        <thead>
            <tr>
                <th>ชื่อ-สกุล</th>
                <th>รหัสผู้ใช้งาน/บุคลากร</th> <th>สถานะภาพ</th>
                <th>เบอร์โทร</th>
                <th>ลงทะเบียนโดย</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">ยังไม่มีข้อมูลผู้ใช้งานในระบบ</td> </tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['student_personnel_id'] ?? '-'); ?></td> <td>
                            <?php 
                                echo htmlspecialchars($student['status']); 
                                if($student['status'] == 'other') { echo ' (' . htmlspecialchars($student['status_other']) . ')'; }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($student['phone_number'] ?? '-'); ?></td>

                        <td>
                            <?php if ($student['line_user_id']): ?>
                                <span style="color: #00B900; font-weight: bold;">LINE</span>
                            <?php else: ?>
                                <span style="color: #6c757d;">Admin</span>
                            <?php endif; ?>
                        </td>
                        <td class="action-buttons">
                            <button type="button"
                                    class="btn btn-manage"
                                    onclick="openEditStudentPopup(<?php echo $student['id']; ?>)">แก้ไข</button>
                            
                            <?php if ($student['linked_user_id']): ?>
                                <button type="button" 
                                        class="btn btn-danger" 
                                        onclick="confirmDemote(<?php echo $student['linked_user_id']; ?>, '<?php echo htmlspecialchars(addslashes($student['full_name'])); ?>')">
                                    <i class="fas fa-user-minus"></i> ลดสิทธิ์
                                </button>
                            <?php else: ?>
                                <?php if (empty($student['line_user_id'])): ?>
                                    <a href="delete_student_process.php?id=<?php echo $student['id']; ?>"
                                       class="btn btn-danger"
                                       onclick="confirmDeleteStudent(event, <?php echo $student['id']; ?>)">ลบ</a>
                                <?php else: ?>
                                    <button type="button" 
                                            class="btn" 
                                            style="background-color: #ffc107; color: #333;" 
                                            onclick="openPromotePopup(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars(addslashes($student['full_name'])); ?>', '<?php echo htmlspecialchars(addslashes($student['line_user_id'])); ?>')">
                                        <i class="fas fa-user-shield"></i> เลื่อนขั้น
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<div class="header-row">
    <h2><i class="fas fa-user-shield"></i> 🛡️ จัดการบัญชีพนักงาน (Admin/Employee)</h2>
    <button class="add-btn" onclick="openAddStaffPopup()">
        <i class="fas fa-plus"></i> เพิ่มบัญชีพนักงาน
    </button>
</div>

<div class="table-container">
    <?php if (isset($staff_error)) echo "<p style='color: red; padding: 15px;'>$staff_error</p>"; ?>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>ชื่อ-สกุล</th>
                <th>สิทธิ์ (Role)</th>
                <th>บัญชีที่เชื่อมโยง (LINE)</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($staff_accounts)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">ไม่มีข้อมูลบัญชีพนักงาน</td>
                </tr>
            <?php else: ?>
                <?php foreach ($staff_accounts as $staff): ?>
                    <tr style="<?php if ($staff['id'] == $_SESSION['user_id']) echo 'background-color: #e6f7ff;'; ?>">
                        <td>
                            <?php echo htmlspecialchars($staff['username']); ?>
                            <?php if ($staff['id'] == $_SESSION['user_id']) echo ' <strong>(คุณ)</strong>'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                        <td>
                            <?php if ($staff['role'] == 'admin'): ?>
                                <span style="color: var(--color-danger); font-weight: bold;">Admin <i class="fa-solid fa-crown"></i></span>
                            <?php else: ?>
                                <span style="color: var(--color-primary);">Employee</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($staff['linked_line_user_id']): ?>
                                <span style="color: #00B900;" title="ผูกกับ LINE ID: <?php echo htmlspecialchars($staff['linked_line_user_id']); ?>">
                                    <i class="fas fa-link"></i> <?php echo htmlspecialchars($staff['linked_student_name'] ?? 'N/A'); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #6c757d;">(ไม่มี)</span>
                            <?php endif; ?>
                        </td>
                        <td class="action-buttons">
                            <button type="button"
                                    class="btn btn-manage"
                                    onclick="openEditStaffPopup(<?php echo $staff['id']; ?>)">แก้ไข</button>
                            
                            <?php if ($staff['id'] != $_SESSION['user_id']): ?>
                                <?php if ($staff['linked_line_user_id']): ?>
                                    <button type="button" 
                                            class="btn btn-danger" 
                                            onclick="confirmDemote(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars(addslashes($staff['full_name'])); ?>')">
                                        <i class="fas fa-user-minus"></i> ลดสิทธิ์
                                    </button>
                                <?php else: ?>
                                    <button type="button" 
                                            class="btn btn-danger" 
                                            onclick="confirmDeleteStaff(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars(addslashes($staff['full_name'])); ?>')">
                                        <i class="fas fa-trash"></i> ลบบัญชี
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// (JS: "student" -> "user")
function confirmDeleteStudent(event, id) {
    event.preventDefault();
    const url = event.currentTarget.href;
    Swal.fire({
        title: "คุณแน่ใจหรือไม่?",
        text: "คุณกำลังจะลบผู้ใช้งานนี้ (เฉพาะที่ Admin เพิ่มเอง)",
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
function openAddStudentPopup() {
    Swal.fire({
        title: '➕ เพิ่มผู้ใช้งาน (โดย Admin)',
        html: `
            <form id="swalAddForm" style="text-align: left; margin-top: 20px;">
                <p>ผู้ใช้งานที่เพิ่มโดย Admin จะไม่มี LINE ID เชื่อมโยง</p>
                <div style="margin-bottom: 15px;">
                    <label for="swal_full_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อ-สกุล: <span style="color:red;">*</span></label>
                    <input type="text" name="full_name" id="swal_full_name" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="swal_phone_number" style="font-weight: bold; display: block; margin-bottom: 5px;">เบอร์โทร:</label>
                    <input type="text" name="phone_number" id="swal_phone_number" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                </form>`,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: 'var(--color-success, #28a745)',
        focusConfirm: false,
        preConfirm: () => {
            const form = document.getElementById('swalAddForm');
            const fullName = form.querySelector('#swal_full_name').value;
            if (!fullName) {
                Swal.showValidationMessage('กรุณากรอก ชื่อ-สกุล ผู้ใช้งาน');
                return false;
            }
            return fetch('add_student_process.php', { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message);
                    return data;
                })
                .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('บันทึกสำเร็จ!', 'เพิ่มผู้ใช้งานใหม่เรียบร้อยแล้ว', 'success').then(() => location.href = 'manage_students.php?add=success');
        }
    });
}
function openEditStudentPopup(studentId) {
    Swal.fire({ title: 'กำลังโหลดข้อมูล...', didOpen: () => { Swal.showLoading(); } });
    fetch(`get_student_data.php?id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            const student = data.student;
            const formHtml = `
                <form id="swalEditStudentForm" style="text-align: left; margin-top: 20px;">
                    <input type="hidden" name="student_id" value="${student.id}">
                    <div style="margin-bottom: 15px;">
                        <label for="swal_edit_full_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อ-สกุล: <span style="color:red;">*</span></label>
                        <input type="text" name="full_name" id="swal_edit_full_name" value="${student.full_name}" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    
                                        <div style="margin-bottom: 15px;">
                        <label for="swal_edit_student_id" style="font-weight: bold; display: block; margin-bottom: 5px;">รหัสผู้ใช้งาน/บุคลากร:</label>
                        <input type="text" name="student_personnel_id" id="swal_edit_student_id" value="${student.student_personnel_id || ''}" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                                        <div style="margin-bottom: 15px;">
                        <label for="swal_edit_phone_number" style="font-weight: bold; display: block; margin-bottom: 5px;">เบอร์โทร:</label>
                        <input type="text" name="phone_number" id="swal_edit_phone_number" value="${student.phone_number || ''}" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    </form>`;
            Swal.fire({
                title: '🔧 แก้ไขข้อมูลผู้ใช้งาน',
                html: formHtml,
                showCancelButton: true,
                confirmButtonText: 'บันทึกการเปลี่ยนแปลง',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: 'var(--color-primary, #0B6623)',
                focusConfirm: false,
                preConfirm: () => {
                    const form = document.getElementById('swalEditStudentForm');
                    const fullName = form.querySelector('#swal_edit_full_name').value;
                    if (!fullName) {
                        Swal.showValidationMessage('กรุณากรอก ชื่อ-สกุล');
                        return false;
                    }
                    return fetch('edit_student_process.php', { method: 'POST', body: new FormData(form) })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status !== 'success') throw new Error(data.message);
                            return data;
                        })
                        .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('บันทึกสำเร็จ!', 'แก้ไขข้อมูลผู้ใช้งานเรียบร้อย', 'success').then(() => location.href = 'manage_students.php?edit=success');
                }
            });
        })
        .catch(error => {
            Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        });
}
function openPromotePopup(studentId, studentName, lineId) {
    Swal.fire({
        title: 'เลื่อนขั้นผู้ใช้งาน',
        html: `
            <p style="text-align: left;">คุณกำลังจะเลื่อนขั้น <strong>${studentName}</strong> (ที่มี LINE ID) ให้เป็น "พนักงาน"</p>
            <p style="text-align: left;">กรุณาสร้างบัญชีสำหรับ Login (เผื่อกรณีที่ไม่ได้เข้าผ่าน LINE):</p>
            
            <form id="swalPromoteForm" style="text-align: left; margin-top: 20px;">
                <input type="hidden" name="student_id_to_promote" value="${studentId}">
                <input type="hidden" name="line_user_id_to_link" value="${lineId}">
                
                <div style="margin-bottom: 15px;">
                    <label for="swal_username" style="font-weight: bold; display: block; margin-bottom: 5px;">1. Username (สำหรับ Login): <span style="color:red;">*</span></label>
                    <input type="text" name="new_username" id="swal_username" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="swal_password" style="font-weight: bold; display: block; margin-bottom: 5px;">2. Password (ชั่วคราว): <span style="color:red;">*</span></label>
                    <input type="text" name="new_password" id="swal_password" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="swal_role" style="font-weight: bold; display: block; margin-bottom: 5px;">3. สิทธิ์ (Role): <span style="color:red;">*</span></label>
                    <select name="new_role" id="swal_role" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="employee">พนักงาน (Employee)</option>
                        <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                    </select>
                </div>
            </form>`,
        showCancelButton: true,
        confirmButtonText: 'ยืนยันการเลื่อนขั้น',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: 'var(--color-warning, #ffc107)',
        focusConfirm: false,
        preConfirm: () => {
            const form = document.getElementById('swalPromoteForm');
            const username = form.querySelector('#swal_username').value;
            const password = form.querySelector('#swal_password').value;
            if (!username || !password) {
                Swal.showValidationMessage('กรุณากรอก Username และ Password');
                return false;
            }
            return fetch('promote_student_process.php', { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message);
                    return data;
                })
                .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('เลื่อนขั้นสำเร็จ!', 'ผู้ใช้งานนี้กลายเป็นพนักงานแล้ว', 'success').then(() => location.href = 'manage_students.php?promote=success');
        }
    });
}
function confirmDemote(userId, staffName) {
    Swal.fire({
        title: `คุณแน่ใจหรือไม่?`,
        text: `คุณกำลังจะลดสิทธิ์ ${staffName} กลับไปเป็น "ผู้ใช้งาน" บัญชีพนักงานจะถูกลบ (แต่ยัง Login LINE ได้)`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "ใช่, ลดสิทธิ์เลย",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('user_id_to_demote', userId); 
            fetch('demote_staff_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('ลดสิทธิ์สำเร็จ!', data.message, 'success')
                    .then(() => location.href = 'manage_students.php?staff_op=success');
                } else {
                    Swal.fire('เกิดข้อผิดพลาด!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('เกิดข้อผิดพลาด AJAX', error.message, 'error');
            });
        }
    });
}
function confirmDeleteStaff(userId, staffName) {
    Swal.fire({
        title: `คุณแน่ใจหรือไม่?`,
        text: `คุณกำลังจะลบบัญชีพนักงาน [${staffName}] ออกจากระบบอย่างถาวร (จะลบได้ต่อเมื่อไม่มีประวัติการอนุมัติค้างอยู่)`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "ใช่, ลบบัญชี",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('user_id_to_demote', userId); 
            fetch('demote_staff_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('ลบสำเร็จ!', data.message, 'success')
                    .then(() => location.href = 'manage_students.php?staff_op=success');
                } else {
                    Swal.fire('เกิดข้อผิดพลาด!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('เกิดข้อผิดพลาด AJAX', error.message, 'error');
            });
        }
    });
}
function openAddStaffPopup() {
    Swal.fire({
        title: '➕ เพิ่มบัญชีพนักงานใหม่',
        html: `
            <p style="text-align: left;">บัญชีนี้จะใช้สำหรับ Login ในหน้า Admin/Employee (จะไม่ถูกผูกกับ LINE)</p>
            <form id="swalAddStaffForm" style="text-align: left; margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label for="swal_s_username" style="font-weight: bold; display: block; margin-bottom: 5px;">Username: <span style="color:red;">*</span></label>
                    <input type="text" name="username" id="swal_s_username" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="swal_s_password" style="font-weight: bold; display: block; margin-bottom: 5px;">Password: <span style="color:red;">*</span></label>
                    <input type="text" name="password" id="swal_s_password" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="swal_s_fullname" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อ-สกุล: <span style="color:red;">*</span></label>
                    <input type="text" name="full_name" id="swal_s_fullname" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="swal_s_role" style="font-weight: bold; display: block; margin-bottom: 5px;">สิทธิ์ (Role): <span style="color:red;">*</span></label>
                    <select name="role" id="swal_s_role" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="employee">พนักงาน (Employee)</option>
                        <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                    </select>
                </div>
            </form>`,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: 'var(--color-success, #28a745)',
        focusConfirm: false,
        preConfirm: () => {
            const form = document.getElementById('swalAddStaffForm');
            if (!form.checkValidity()) {
                Swal.showValidationMessage('กรุณากรอกข้อมูล * ให้ครบถ้วน');
                return false;
            }
            return fetch('add_staff_process.php', { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message);
                    return data;
                })
                .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('บันทึกสำเร็จ!', 'เพิ่มบัญชีพนักงานใหม่เรียบร้อย', 'success').then(() => location.href = 'manage_students.php?staff_op=success');
        }
    });
}
function openEditStaffPopup(userId) {
    Swal.fire({ title: 'กำลังโหลดข้อมูล...', didOpen: () => { Swal.showLoading(); } });
    
    fetch(`get_staff_data.php?id=${userId}`) 
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            const staff = data.staff;
            
            const is_linked = staff.linked_line_user_id ? true : false;
            const disabled_attr = is_linked ? 'disabled' : '';
            const linked_warning = is_linked ? '<p style="color: #00B900; text-align: left;">(บัญชีนี้ผูกกับ LINE จึงไม่สามารถแก้ไขชื่อและสิทธิ์ได้จากหน้านี้)</p>' : '';

            const formHtml = `
                <form id="swalEditStaffForm" style="text-align: left; margin-top: 20px;">
                    <input type="hidden" name="user_id" value="${staff.id}">
                    ${linked_warning}
                    <div style="margin-bottom: 15px;">
                        <label for="swal_e_username" style="font-weight: bold; display: block; margin-bottom: 5px;">Username: <span style="color:red;">*</span></label>
                        <input type="text" name="username" id="swal_e_username" value="${staff.username}" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="swal_e_fullname" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อ-สกุล: <span style="color:red;">*</span></label>
                        <input type="text" name="full_name" id="swal_e_fullname" value="${staff.full_name}" required ${disabled_attr} style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; background-color: ${is_linked ? '#f4f4f4' : '#fff'};">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="swal_e_role" style="font-weight: bold; display: block; margin-bottom: 5px;">สิทธิ์ (Role): <span style="color:red;">*</span></label>
                        <select name="role" id="swal_e_role" required ${disabled_attr} style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd; background-color: ${is_linked ? '#f4f4f4' : '#fff'};">
                            <option value="employee" ${staff.role == 'employee' ? 'selected' : ''}>พนักงาน (Employee)</option>
                            <option value="admin" ${staff.role == 'admin' ? 'selected' : ''}>ผู้ดูแลระบบ (Admin)</option>
                        </select>
                    </div>
                    <hr style="margin: 20px 0;">
                    <div style="margin-bottom: 15px;">
                        <label for="swal_e_password" style="font-weight: bold; display: block; margin-bottom: 5px;">Reset รหัสผ่าน (กรอกเฉพาะเมื่อต้องการเปลี่ยน):</label>
                        <input type="text" name="new_password" id="swal_e_password" placeholder="กรอกรหัสผ่านใหม่" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                </form>`;
                
            Swal.fire({
                title: '🔧 แก้ไขบัญชีพนักงาน',
                html: formHtml,
                showCancelButton: true,
                confirmButtonText: 'บันทึกการเปลี่ยนแปลง',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: 'var(--color-primary, #0B6623)',
                focusConfirm: false,
                preConfirm: () => {
                    const form = document.getElementById('swalEditStaffForm');
                    if (!form.checkValidity()) {
                        Swal.showValidationMessage('กรุณากรอกข้อมูล * ให้ครบถ้วน');
                        return false;
                    }
                    return fetch('edit_staff_process.php', { method: 'POST', body: new FormData(form) })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status !== 'success') throw new Error(data.message);
                            return data;
                        })
                        .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('บันทึกสำเร็จ!', 'แก้ไขข้อมูลบัญชีเรียบร้อย', 'success').then(() => location.href = 'manage_students.php?staff_op=success');
                }
            });
        })
        .catch(error => {
            Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        });
}
</script>

<?php
// 7. เรียกใช้ Footer
include('includes/footer.php');
?>