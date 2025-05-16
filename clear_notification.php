<?php
require_once 'database.php';
require_once 'functions.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    if (isset($_POST['clear_all']) && $_POST['clear_all'] == '1') {
        // Get all expired and expiring medicines
        $expired = getExpiredMedicines($pdo);
        $expiring = getExpiringSoon($pdo);
        
        // Clear all notifications
        foreach ($expired as $medicine) {
            clearNotification($pdo, $medicine['id'], 'Expired', $_SESSION['user_id']);
        }
        
        foreach ($expiring as $medicine) {
            clearNotification($pdo, $medicine['id'], 'Expiring Soon', $_SESSION['user_id']);
        }
        
        echo json_encode(['success' => true]);
    } elseif (isset($_POST['medicine_id']) && isset($_POST['notification_type'])) {
        // Clear single notification
        clearNotification($pdo, $_POST['medicine_id'], $_POST['notification_type'], $_SESSION['user_id']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>