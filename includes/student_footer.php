<?php
// includes/student_footer.php
// ปิดแท็ก main และเพิ่ม Footer Navigation

// ตรวจสอบว่ามีตัวแปร $active_page หรือไม่ ถ้าไม่มให้เป็นค่าว่าง
$active_page = $active_page ?? ''; 
?>

</main> <nav class="footer-nav">
    <a href="student_dashboard.php" class="<?php echo ($active_page == 'home') ? 'active' : ''; ?>">
        <i class="fas fa-hand-holding-medical"></i>
        ที่ยืมอยู่
    </a>
    <a href="borrow_list.php" class="<?php echo ($active_page == 'borrow') ? 'active' : ''; ?>">
        <i class="fas fa-boxes-stacked"></i>
        ยืมอุปกรณ์
    </a>
    <a href="request_history.php" class="<?php echo ($active_page == 'history') ? 'active' : ''; ?>">
        <i class="fas fa-history"></i>
        ประวัติ
    </a>
    <a href="edit_profile.php" class="<?php echo ($active_page == 'settings') ? 'active' : ''; ?>">
        <i class="fas fa-user-cog"></i>
        ตั้งค่า
    </a>
</nav>

</body>
</html>