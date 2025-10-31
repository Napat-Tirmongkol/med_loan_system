<?php
// live_search_equipment.php
// API สำหรับการค้นหาแบบ Live Search (AJAX)

// (เราใช้ "ยาม" ฝั่งผู้ใช้ เพราะหน้านี้ผู้ใช้เป็นคนเรียก)
include('includes/check_student_session.php'); 
require_once('db_connect.php'); 

header('Content-Type: application/json');

// 1. รับคำค้นหา (term) จาก URL (ที่ JavaScript ส่งมา)
$search_term = $_GET['term'] ?? '';

$response = [
    'status' => 'error',
    'message' => 'No term provided',
    'results' => []
];

if (empty($search_term)) {
    echo json_encode($response);
    exit;
}

// 2. ค้นหาในฐานข้อมูล
try {
    $search_param = '%' . $search_term . '%';
    
    // (ค้นหาเฉพาะของที่ "available" เท่านั้น)
    $sql = "SELECT id, name, serial_number, image_url, description 
            FROM med_equipment 
            WHERE status = 'available' 
              AND (name LIKE ? OR serial_number LIKE ? OR description LIKE ?)
            ORDER BY name ASC
            LIMIT 10"; // (จำกัดผลลัพธ์แค่ 10 รายการ)
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$search_param, $search_param, $search_param]);
    $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['status'] = 'success';
    $response['results'] = $equipments;
    $response['message'] = 'Search successful';

} catch (PDOException $e) {
    $response['message'] = 'Database Error: ' . $e->getMessage();
}

// 3. ส่งผลลัพธ์ (JSON) กลับไป
echo json_encode($response);
exit;
?>