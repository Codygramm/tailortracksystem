<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Validation
    if (empty($username) || empty($role) || empty($full_name)) {
        $message = "Please fill in all required fields!";
    } else {
        try {
            // Check if username already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $message = "Username already exists!";
            } else {
                // Update staff
                if (!empty($password)) {
                    // If password is provided, update it
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ?, full_name = ?, email = ?, phone = ? WHERE user_id = ?");
                    $stmt->execute([$username, $hashedPassword, $role, $full_name, $email, $phone, $user_id]);
                } else {
                    // If password is not provided, don't update it
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, full_name = ?, email = ?, phone = ? WHERE user_id = ?");
                    $stmt->execute([$username, $role, $full_name, $email, $phone, $user_id]);
                }
                
                $success = true;
                $message = "Staff member updated successfully!";
            }
        } catch (PDOException $e) {
            error_log("Update staff error: " . $e->getMessage());
            $message = "Unable to update staff. Please try again.";
        }
    }
}

// Return JSON response for AJAX requests
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $message
]);
?>