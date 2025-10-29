<?php
// 1. "จ้างยาม" และ "เชื่อมต่อ DB"
include('includes/check_session.php');
require_once('db_connect.php');

// 2. ตรวจสอบสิทธิ์ Admin (เหมือนเดิม)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// 3. ดึงข้อมูลผู้ยืมทั้งหมด (เหมือนเดิม)
try {
    $stmt = $pdo->prepare("SELECT * FROM med_borrowers ORDER BY full_name ASC");
    $stmt->execute();
    $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    $borrowers = [];
}

// 4. ตั้งค่าตัวแปรสำหรับ Header (เหมือนเดิม)
$page_title = "จัดการผู้ยืม";
$current_page = "manage_user";
include('includes/header.php');
// ตรวจสอบ $_GET parameters สำหรับแสดงข้อความแจ้งเตือน
$message = '';
$message_type = ''; // 'success' หรือ 'error'

if (isset($_GET['add']) && $_GET['add'] == 'success') {
    $message = 'เพิ่มผู้ยืมใหม่สำเร็จ!';
    $message_type = 'success';
} elseif (isset($_GET['edit']) && $_GET['edit'] == 'success') {
    $message = 'แก้ไขข้อมูลผู้ยืมสำเร็จ!';
    $message_type = 'success';
} elseif (isset($_GET['delete']) && $_GET['delete'] == 'success') {
    $message = 'ลบข้อมูลผู้ยืมสำเร็จ!';
    $message_type = 'success';
} elseif (isset($_GET['error'])) {
    $message_type = 'error';
    if ($_GET['error'] == 'fk_constraint') {
        $message = 'ไม่สามารถลบผู้ยืมได้ เนื่องจากมีประวัติการยืมค้างอยู่!';
    } elseif ($_GET['error'] == 'not_found') {
        $message = 'ไม่พบข้อมูลผู้ยืมที่ต้องการลบ!';
    } elseif ($_GET['error'] == 'no_id') {
        $message = 'ไม่ได้ระบุ ID ผู้ยืม!';
    } else {
        $message = 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ';
    }
}
?>

<div class="container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>👥 จัดการรายชื่อผู้ยืม</h2>
        <button type="button" class="btn btn-borrow" style="font-size: 16px;" onclick="openAddBorrowerPopup()">
            <i class="fa-solid fa-plus"></i> เพิ่มผู้ยืม
        </button>
    </div>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่อ-สกุล</th>
                <th>ข้อมูลติดต่อ (เบอร์โทร, HN)</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($borrowers)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">ยังไม่มีข้อมูลผู้ยืมในระบบ</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; ?>
                <?php foreach ($borrowers as $borrower): ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><?php echo htmlspecialchars($borrower['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($borrower['contact_info']); ?></td>
                        <td>
                            <button type="button"
                                    class="btn btn-manage"
                                    onclick="openEditBorrowerPopup(<?php echo $borrower['id']; ?>)">แก้ไข</button>

                            <a href="delete_borrower_process.php?id=<?php echo $borrower['id']; ?>"
                               class="btn"
                               style="background-color: #dc3545;"
                               onclick="confirmDelete(event, <?php echo $borrower['id']; ?>)">ลบ</a>
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
// 1. ฟังก์ชันสำหรับ "ลบ" (อันเดิม)
function confirmDelete(event, id) {
    event.preventDefault();
    const url = event.currentTarget.href;
    Swal.fire({
        title: "คุณแน่ใจหรือไม่?",
        text: "คุณกำลังจะลบข้อมูลผู้ยืมนี้!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "ใช่, ลบเลย",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url; // ไปยังหน้า process การลบ
        }
    });
}

// 2. ฟังก์ชันสำหรับ "เพิ่ม" (Popup Form - อันเดิม)
function openAddBorrowerPopup() {
    Swal.fire({
        title: '➕ เพิ่มข้อมูลผู้ยืมใหม่',
        html: `... (โค้ด HTML ฟอร์มเพิ่มผู้ยืม) ...`, // (ย่อส่วนนี้เพื่อความกระชับ)
        html: `
            <form id="swalAddForm" style="text-align: left; margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label for="swal_full_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อ-สกุล ผู้ยืม:</label>
                    <input type="text" name="full_name" id="swal_full_name" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="swal_contact_info" style="font-weight: bold; display: block; margin-bottom: 5px;">ข้อมูลติดต่อ:</label>
                    <input type="text" name="contact_info" id="swal_contact_info" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                </div>
            </form>`,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#28a745',
        focusConfirm: false,
        preConfirm: () => {
            const form = document.getElementById('swalAddForm');
            const fullName = form.querySelector('#swal_full_name').value;
            if (!fullName) {
                Swal.showValidationMessage('กรุณากรอก ชื่อ-สกุล ผู้ยืม');
                return false;
            }
            return fetch('add_borrower_process.php', { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') throw new Error(data.message);
                    return data;
                })
                .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('บันทึกสำเร็จ!', 'เพิ่มผู้ยืมใหม่เรียบร้อยแล้ว', 'success').then(() => location.reload());
        }
    });
}

// 3. ฟังก์ชันใหม่สำหรับ "แก้ไข" (Popup Form)
function openEditBorrowerPopup(borrowerId) {
    // 1. แสดง Popup "กำลังโหลด..."
    Swal.fire({
        title: 'กำลังโหลดข้อมูลผู้ยืม...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    // 2. ดึงข้อมูล "เก่า" ของผู้ยืม (AJAX GET)
    fetch(`get_borrower_data.php?id=${borrowerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                throw new Error(data.message); // เช่น "ไม่พบข้อมูล"
            }
            const borrower = data.borrower;

            // 3. สร้าง HTML สำหรับฟอร์มแก้ไข
            const formHtml = `
                <form id="swalEditBorrowerForm" style="text-align: left; margin-top: 20px;">
                    <input type="hidden" name="borrower_id" value="${borrower.id}">
                    <div style="margin-bottom: 15px;">
                        <label for="swal_edit_full_name" style="font-weight: bold; display: block; margin-bottom: 5px;">ชื่อ-สกุล ผู้ยืม:</label>
                        <input type="text" name="full_name" id="swal_edit_full_name" value="${borrower.full_name}" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="swal_edit_contact_info" style="font-weight: bold; display: block; margin-bottom: 5px;">ข้อมูลติดต่อ:</label>
                        <input type="text" name="contact_info" id="swal_edit_contact_info" value="${borrower.contact_info || ''}" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                    </div>
                </form>`;

            // 4. เปิด SweetAlert Popup ที่มีฟอร์ม
            Swal.fire({
                title: '🔧 แก้ไขข้อมูลผู้ยืม',
                html: formHtml,
                showCancelButton: true,
                confirmButtonText: 'บันทึกการเปลี่ยนแปลง',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#007bff', // สีน้ำเงิน
                focusConfirm: false,
                preConfirm: () => {
                    // 5. ดึงข้อมูลจากฟอร์มใน Popup
                    const form = document.getElementById('swalEditBorrowerForm');
                    const fullName = form.querySelector('#swal_edit_full_name').value;
                    if (!fullName) {
                        Swal.showValidationMessage('กรุณากรอก ชื่อ-สกุล ผู้ยืม');
                        return false; // หยุด
                    }

                    // 6. ส่งข้อมูลไปเบื้องหลัง (AJAX - POST)
                    return fetch('edit_borrower_process.php', {
                        method: 'POST',
                        body: new FormData(form) // ส่งข้อมูลฟอร์ม
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
                // 7. เมื่อทุกอย่างสำเร็จ
                if (result.isConfirmed) {
                    Swal.fire('บันทึกสำเร็จ!', 'แก้ไขข้อมูลผู้ยืมเรียบร้อย', 'success')
                    .then(() => location.reload()); // รีเฟรชหน้า
                }
            });
        })
        .catch(error => {
            // 8. กรณีดึงข้อมูล (ข้อ 2) ล้มเหลว
            Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        });
}
</script>


<?php
// 7. เรียกใช้ Footer
include('includes/footer.php');
?>