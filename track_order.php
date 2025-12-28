<?php
// track_order.php
session_start();

// Database configuration - Use your actual XAMPP credentials
$host = 'localhost';
$dbname = 'tailortrackdb';
$username = 'root';  // Default XAMPP username
$password = '';      // Default XAMPP password (empty)

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Log successful connection
    error_log("Database connected successfully to tailortrackdb");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

header('Content-Type: application/json');

if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo json_encode(['success' => false, 'error' => 'Order ID is required']);
    exit();
}

$order_id = trim($_GET['order_id']);

// Log the order ID being searched
error_log("Searching for order ID: " . $order_id);

try {
    // First, let's check if the orders table exists and get a sample
    $checkStmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($checkStmt->rowCount() == 0) {
        error_log("ERROR: 'orders' table not found!");
        echo json_encode(['success' => false, 'error' => 'Orders table not found in database']);
        exit();
    }
    
    // Get a list of all order IDs in the database for debugging
    $sampleStmt = $pdo->query("SELECT order_id FROM orders ORDER BY created_at DESC LIMIT 10");
    $allOrders = $sampleStmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("First 10 orders in database: " . implode(", ", $allOrders));
    
    // Now search for the specific order
    $query = "
        SELECT 
            o.*,
            u.full_name as tailor_name,
            c.full_name as cashier_name
        FROM orders o 
        LEFT JOIN users u ON o.assigned_tailor = u.user_id
        LEFT JOIN users c ON o.created_by = c.user_id
        WHERE o.order_id = :order_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_STR);
    $stmt->execute();
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        error_log("Order not found: $order_id");
        
        // Try case-insensitive search
        $query = "SELECT order_id FROM orders WHERE LOWER(order_id) = LOWER(:order_id)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            echo json_encode([
                'success' => false, 
                'error' => "Order '$order_id' not found. Try one of these: " . implode(", ", array_slice($allOrders, 0, 5))
            ]);
        } else {
            // Found with different case
            $correctOrder = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => false, 
                'error' => "Order found but with different case. Try: " . $correctOrder['order_id']
            ]);
        }
        exit();
    }
    
    error_log("Order found: " . $order['order_id'] . " with status: " . $order['status']);
    
    // Get upper body measurements if they exist
    $upper_measurements = [];
    try {
        $upper_stmt = $pdo->prepare("SELECT * FROM upper_body_measurements WHERE order_id = :order_id");
        $upper_stmt->bindParam(':order_id', $order_id, PDO::PARAM_STR);
        $upper_stmt->execute();
        $upper_measurements = $upper_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Upper measurements query error: " . $e->getMessage());
    }
    
    // Get lower body measurements if they exist
    $lower_measurements = [];
    try {
        $lower_stmt = $pdo->prepare("SELECT * FROM lower_body_measurements WHERE order_id = :order_id");
        $lower_stmt->bindParam(':order_id', $order_id, PDO::PARAM_STR);
        $lower_stmt->execute();
        $lower_measurements = $lower_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Lower measurements query error: " . $e->getMessage());
    }
    
    // Order type mapping
    $order_types = [
        'set_baju_melayu' => 'Set Baju Melayu',
        'set_baju_kurung' => 'Set Baju Kurung',
        'set_baju_kebaya' => 'Set Baju Kebaya',
        'baju_kurta' => 'Baju Kurta',
        'repair' => 'Repair Service'
    ];
    
    // Calculate progress based on status
    $progress_percentage = 0;
    $active_steps = 1;
    
    switch ($order['status']) {
        case 'assigned':
            $progress_percentage = 25;
            $active_steps = 2;
            break;
        case 'in_progress':
            $progress_percentage = 50;
            $active_steps = 3;
            break;
        case 'completed':
            $progress_percentage = 75;
            $active_steps = 4;
            break;
        case 'paid':
            $progress_percentage = 100;
            $active_steps = 5;
            break;
        case 'pending':
        default:
            $progress_percentage = 0;
            $active_steps = 1;
    }
    
    // Format dates
    $created_date = date('M j, Y', strtotime($order['created_at']));
    $updated_date = date('M j, Y', strtotime($order['updated_at']));
    
    // Prepare response
    $response = [
        'success' => true,
        'order' => [
            'order_id' => $order['order_id'],
            'customer_name' => htmlspecialchars($order['customer_name']),
            'customer_phone' => htmlspecialchars($order['customer_phone']),
            'customer_email' => htmlspecialchars($order['customer_email'] ?? ''),
            'order_type' => $order_types[$order['order_type']] ?? $order['order_type'],
            'order_type_raw' => $order['order_type'],
            'repair_type' => $order['repair_type'] ?? '',
            'status' => ucwords(str_replace('_', ' ', $order['status'])),
            'status_raw' => $order['status'],
            'payment_status' => ucfirst($order['payment_status']),
            'total_amount' => number_format($order['total_amount'], 2),
            'amount_paid' => number_format($order['amount_paid'] ?? 0, 2),
            'assigned_tailor' => $order['tailor_name'] ? htmlspecialchars($order['tailor_name']) : 'Not assigned yet',
            'created_by' => $order['cashier_name'] ? htmlspecialchars($order['cashier_name']) : 'System',
            'created_at' => $created_date,
            'updated_at' => $updated_date,
            'tailor_notes' => $order['tailor_notes'] ?? '',
            'upper_measurements' => $upper_measurements,
            'lower_measurements' => $lower_measurements,
            'progress_percentage' => $progress_percentage,
            'active_steps' => $active_steps
        ]
    ];
    
    error_log("Successfully returning order data for: " . $order['order_id']);
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in track_order.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in track_order.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>