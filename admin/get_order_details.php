<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['order_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Order ID is required']);
    exit();
}

$order_id = $_GET['order_id'];

try {
    // Get order details with cashier and tailor information
    $stmt = $pdo->prepare("
        SELECT o.*, 
               t.full_name as tailor_name,
               c.full_name as cashier_name,
               ub.shoulder, ub.chest, ub.waist as upper_waist, ub.sleeve_length, ub.armhole, ub.wrist, ub.neck, ub.top_length,
               lb.waist as lower_waist, lb.hip, lb.bottom_length, lb.inseam, lb.outseam
        FROM orders o 
        LEFT JOIN users t ON o.assigned_tailor = t.user_id 
        LEFT JOIN users c ON o.created_by = c.user_id
        LEFT JOIN upper_body_measurements ub ON o.order_id = ub.order_id
        LEFT JOIN lower_body_measurements lb ON o.order_id = lb.order_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Order not found']);
        exit();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'order' => $order]);
    
} catch (PDOException $e) {
    error_log("Get order details error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Unable to fetch order details']);
}
?>