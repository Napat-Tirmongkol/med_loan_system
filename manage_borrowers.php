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
?>

<div class="container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>👥 จัดการรายชื่อผู้ยืม</h2>
        
        <button type="button" class="btn btn-borrow" style="font-size: 16px;" onclick="openAddBorrowerPopup()">
            ➕ เพิ่มผู้ยืมใหม่
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
                            <a href="edit_borrower_form.php?id=<?php echo $borrower['id']; ?>" class="btn btn-manage">แก้ไข</a>
                            
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
            window.location.href = url;
        }
    });
}

// 2. ฟังก์ชันใหม่สำหรับ "เพิ่ม" (Popup Form)
function openAddBorrowerPopup() {
    Swal.fire({
        title: '➕ เพิ่มข้อมูลผู้ยืมใหม่',
        // นี่คือ HTML ของฟอร์ม (จากไฟล์ add_borrower_form.php)
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
        
        // ส่วนนี้คือ "AJAX"
        preConfirm: () => {
            // 1. ดึงข้อมูลจากฟอร์มใน Popup
            const form = document.getElementById('swalAddForm');
            const fullName = form.querySelector('#swal_full_name').value;

            if (!fullName) {
                Swal.showValidationMessage('กรุณากรอก ชื่อ-สกุล ผู้ยืม'); // แสดง Error ถ้าไม่กรอก
                return false;
            }

            // 2. ส่งข้อมูลไปเบื้องหลัง (AJAX)
            return fetch('add_borrower_process.php', {
                method: 'POST',
                body: new FormData(form) // ส่งข้อมูลฟอร์มทั้งหมด
            })
            .then(response => response.json()) // รอคำตอบแบบ JSON
            .then(data => {
                if (data.status !== 'success') {
                    throw new Error(data.message); // ถ้า PHP ตอบ error
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`);
            });
        }
    }).then((result) => {
        // 3. เมื่อทุกอย่างสำเร็จ (AJAX สำเร็จ และกดยืนยัน)
        if (result.isConfirmed) {
            Swal.fire(
                'บันทึกสำเร็จ!',
                'เพิ่มผู้ยืมใหม่เรียบร้อยแล้ว',
                'success'
            ).then(() => {
                // 4. รีเฟรชหน้าเพื่อแสดงข้อมูลใหม่
                location.reload(); 
            });
        }
    });
}
</script>

<?php
// 7. เรียกใช้ Footer
include('includes/footer.php'); 
?>