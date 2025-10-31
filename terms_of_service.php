<?php
// terms_of_service.php
// (หน้านี้ไม่จำเป็นต้อง check session เพราะทุกคนควรอ่านได้)
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อตกลงการใช้งาน</title>
    <link rel="stylesheet" href="CSS/style.css"> 
</head>
<body style="background-color: var(--color-page-bg);">

    <div class="profile-container" style="max-width: 800px; margin-top: 30px; margin-bottom: 30px;">
        <h2>ข้อตกลงและเงื่อนไขการใช้งาน</h2>
        <p class="text-muted">โปรดอ่านข้อตกลงด้านล่างนี้อย่างละเอียดก่อนใช้งานระบบ</p>

        <div class="form-container" style="text-align: left; line-height: 1.6;">
            
            <h3>1. คำนิยาม</h3>
            <p>
                "ระบบ" หมายถึง ระบบยืมคืนอุปกรณ์การแพทย์ MedLoan นี้
                <br>
                "ผู้ใช้" หมายถึง นักศึกษา, อาจารย์, หรือบุคลากรที่ลงทะเบียนผ่าน LINE เพื่อใช้งานระบบ
            </p>

            <h3>2. การใช้งานระบบ</h3>
            <p>
                ผู้ใช้ตกลงที่จะใช้ระบบนี้เพื่อวัตถุประสงค์ในการยืม-คืนอุปกรณ์การแพทย์เพื่องานที่เกี่ยวข้องกับการศึกษาหรือการปฏิบัติงานเท่านั้น
            </p>

            <h3>3. ความรับผิดชอบของผู้ยืม</h3>
            <p>
                ผู้ใช้ต้องรับผิดชอบต่ออุปกรณ์ที่ยืมไป และต้องส่งคืนในสภาพสมบูรณ์ภายในวันที่กำหนด หากเกิดการชำรุดหรือสูญหาย ...
            </p>

            <br>
            <button onclick="window.close();" class="btn-loan" style="background-color: var(--color-secondary);">
                ปิดหน้าต่างนี้
            </button>
            
        </div>
    </div>

</body>
</html>