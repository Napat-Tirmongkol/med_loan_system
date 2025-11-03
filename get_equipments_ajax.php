<?php
// get_equipments_ajax.php
// (ไฟล์ใหม่) Endpoint สำหรับดึงข้อมูลอุปกรณ์ด้วย AJAX

header('Content-Type: application/json');
require_once('db_connect.php');

$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาด', 'data' => []];

try {
    // 1. เตรียม SQL Query พื้นฐาน (เหมือนใน manage_equipment.php)
    $sql = "SELECT e.*, s.full_name as borrower_name, t.due_date 
            FROM med_equipment e
            LEFT JOIN med_transactions t ON e.id = t.equipment_id AND t.status = 'borrowed' AND t.approval_status IN ('approved', 'staff_added')
            LEFT JOIN med_students s ON t.borrower_student_id = s.id";

    $conditions = [];
    $params = [];

    // 2. รับค่าตัวกรองจาก Request (GET หรือ POST ก็ได้)
    $search_query = $_REQUEST['search'] ?? '';
    $status_query = $_REQUEST['status'] ?? '';

    // 3. สร้างเงื่อนไขแบบไดนามิก
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $conditions[] = "(e.name LIKE ? OR e.serial_number LIKE ? OR e.description LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if (!empty($status_query)) {
        $conditions[] = "e.status = ?";
        $params[] = $status_query;
    }

    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY e.name ASC";

    // 4. ดึงข้อมูลและส่งกลับเป็น JSON
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['status'] = 'success';
    $response['data'] = $equipments;

} catch (PDOException $e) {
    $response['message'] = "เกิดข้อผิดพลาด DB: " . $e->getMessage();
}

echo json_encode($response);
?>