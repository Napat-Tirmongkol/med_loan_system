</main> <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// 1. ฟังก์ชัน "ยืม"
function openBorrowPopup(equipmentId) {
    Swal.fire({ title: 'กำลังโหลดข้อมูล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    fetch(`get_borrow_form_data.php?id=${equipmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            
            // (เราใช้ชื่อ 'borrowers' ที่ถูกส่งมาจาก get_borrow_form_data.php)
            let borrowerOptions = '<option value="">--- กรุณาเลือกผู้ยืม ---</option>';
            if (data.borrowers.length > 0) {
                data.borrowers.forEach(b => { 
                    borrowerOptions += `<option value="${b.id}">${b.full_name} (${b.contact_info || 'N/A'})</option>`; 
                });
            } else {
                borrowerOptions = '<option value="" disabled>ยังไม่มีข้อมูลผู้ใช้งานในระบบ</option>';
            }
            
            Swal.fire({
                title: '📝 ฟอร์มยืมอุปกรณ์',
                html: `
                <div style="background: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                    <p style="margin: 0;"><strong>อุปกรณ์:</strong> ${data.equipment.name}</p>
                    <p style="margin: 5px 0 0 0;"><strong>ซีเรียล:</strong> ${data.equipment.serial_number || 'N/A'}</p>
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
                confirmButtonColor: 'var(--color-success, #28a745)',
                focusConfirm: false,
                preConfirm: () => {
                    const form = document.getElementById('swalBorrowForm');
                    const borrowerId = form.querySelector('#swal_borrower_id').value;
                    const dueDate = form.querySelector('#swal_due_date').value;
                    if (!borrowerId || !dueDate) {
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

// 2. ฟังก์ชัน "แก้ไข" (อัปเดตแล้ว)
function openEditPopup(equipmentId) {
    Swal.fire({ title: 'กำลังโหลดข้อมูล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    fetch(`get_equipment_data.php?id=${equipmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            const equip = data.equipment;
            let statusOptions = '';
            if (equip.status === 'borrowed') {
                statusOptions = `<option value="borrowed" selected disabled>ถูกยืม (Borrowed) - (ต้องรับคืนก่อน)</option>`;
            } else {
                statusOptions = `
                    <option value="available" ${equip.status === 'available' ? 'selected' : ''}>ว่าง (Available)</option>
                    <option value="maintenance" ${equip.status === 'maintenance' ? 'selected' : ''}>ซ่อมบำรุง (Maintenance)</option>
                `;
            }
            
            Swal.fire({
                title: '🔧 แก้ไขข้อมูลอุปกรณ์',
                html: `
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
                        <label for="swal_desc" style="font-weight: bold; display: block; margin-bottom: 5px;">รายละเอียด:</label>
                        <textarea name="description" id="swal_desc" rows="3" style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">${equip.description || ''}</textarea>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="swal_status" style="font-weight: bold; display: block; margin-bottom: 5px;">สถานะ:</label>
                        <select name="status" id="swal_status" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                            ${statusOptions}
                        </select>
                    </div>
                </form>`,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'บันทึกการเปลี่ยนแปลง',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: 'var(--color-primary, #0B6623)',
                focusConfirm: false,
                preConfirm: () => {
                    const form = document.getElementById('swalEditForm');
                    const name = form.querySelector('#swal_name').value;
                    if (!name) {
                        Swal.showValidationMessage('กรุณากรอกชื่ออุปกรณ์');
                        return false;
                    }
                    return fetch('edit_process.php', { method: 'POST', body: new FormData(form) })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status !== 'success') throw new Error(data.message);
                            return data;
                        })
                        .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('บันทึกสำเร็จ!', 'แก้ไขข้อมูลอุปกรณ์เรียบร้อย', 'success').then(() => location.reload());
                }
            });
        })
        .catch(error => {
            Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        });
}

// 3. ฟังก์ชัน "รับคืน"
function openReturnPopup(equipmentId) {
    Swal.fire({ title: 'กำลังโหลดข้อมูลการยืม...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    fetch(`get_return_form_data.php?id=${equipmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message);
            const trans = data.transaction;
            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                // (แก้ไข format วันที่ให้สั้นลง)
                return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' });
            };
            
            Swal.fire({
                title: '📦 ยืนยันการรับคืน?',
                html: `
                <div style="background: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                    <p style="margin: 0;"><strong>อุปกรณ์:</strong> ${trans.equipment_name} (${trans.equipment_serial || 'N/A'})</p>
                    <p style="margin: 5px 0 0 0;"><strong>ผู้ยืม:</strong> ${trans.borrower_name} (${trans.borrower_contact || 'N/A'})</p>
                    <p style="margin: 5px 0 0 0;"><strong>วันที่ยืม:</strong> ${formatDate(trans.borrow_date)}</p>
                    <p style="margin: 5px 0 0 0;"><strong>กำหนดคืน:</strong> ${formatDate(trans.due_date)}</p>
                </div>
                <p style="font-weight: bold; color: #dc3545;">กรุณาตรวจสอบอุปกรณ์ก่อนกดยืนยัน</p>
                <form id="swalReturnForm">
                    <input type="hidden" name="equipment_id" value="${equipmentId}">
                    <input type="hidden" name="transaction_id" value="${trans.transaction_id}">
                </form>`,
                icon: 'warning',
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'ใช่, ยืนยันการรับคืน',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: 'var(--color-primary, #0B6623)',
                cancelButtonColor: '#d33',
                preConfirm: () => {
                    const form = document.getElementById('swalReturnForm');
                    return fetch('return_process.php', { method: 'POST', body: new FormData(form) })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status !== 'success') throw new Error(data.message);
                            return data;
                        })
                        .catch(error => { Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${error.message}`); });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('รับคืนสำเร็จ!', 'อุปกรณ์กลับเข้าสู่สถานะ "ว่าง"', 'success')
                    .then(() => location.reload());
                }
            });
        })
        .catch(error => {
            Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
        });
}

// 4. ฟังก์ชัน "อนุมัติ" (Popup)
function openApprovePopup(transactionId) {
    Swal.fire({
        title: "ยืนยันการอนุมัติ?",
        text: "ระบบจะเปลี่ยนสถานะอุปกรณ์เป็น 'ถูกยืม'",
        icon: "info",
        showCancelButton: true,
        confirmButtonColor: "var(--color-success, #28a745)", // สีเขียว
        cancelButtonColor: "#d33",
        confirmButtonText: "ใช่, อนุมัติ",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('transaction_id', transactionId);

            fetch('approve_request_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('อนุมัติสำเร็จ!', data.message, 'success')
                    .then(() => location.reload());
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

// 5. ฟังก์ชัน "ปฏิเสธ" (Popup)
function openRejectPopup(transactionId) {
    Swal.fire({
        title: "คุณแน่ใจหรือไม่?",
        text: "คุณกำลังจะปฏิเสธคำขอนี้",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33", // สีแดง
        cancelButtonColor: "#3085d6",
        confirmButtonText: "ใช่, ปฏิเสธ",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('transaction_id', transactionId);

            fetch('reject_request_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('ปฏิเสธสำเร็จ', data.message, 'success')
                    .then(() => location.reload());
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

// ( ... โค้ดสำหรับ Hamburger ... )
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebar = document.querySelector('.sidebar');
const body = document.body; 

if (hamburgerBtn && sidebar) {
    hamburgerBtn.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-visible');
        body.classList.toggle('sidebar-open-overlay'); 
    });
    
    body.addEventListener('click', (event) => {
        if (body.classList.contains('sidebar-open-overlay') && 
            !sidebar.contains(event.target) && 
            !hamburgerBtn.contains(event.target)) {
            
            sidebar.classList.remove('sidebar-visible');
            body.classList.remove('sidebar-open-overlay');
        }
    });
}
</script>

</body>
</html>