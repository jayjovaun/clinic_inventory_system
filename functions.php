<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';

// Redirect if not logged in
function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
        header('Location: login.php');
        exit();
    }
}

// Get user role
function getUserRole() {
    return $_SESSION['role'] ?? 'Guest';
}

// Display alert messages
function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo '<div class="alert alert-'.$alert['type'].' alert-dismissible fade show" role="alert">';
        echo $alert['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['alert']);
    }
}

// Set alert message
function setAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Get expired medicines
function getExpiredMedicines($pdo) {
    $stmt = $pdo->prepare("
        SELECT m.*, c.name AS category_name, 
               DATEDIFF(m.expiration_date, CURDATE()) AS days_until_expiry
        FROM medicines m
        JOIN categories c ON m.category = c.name
        WHERE m.expiration_date < CURDATE()
        AND m.status != 'Expired'
        ORDER BY m.expiration_date DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get expiring soon medicines
function getExpiringSoon($pdo) {
    $stmt = $pdo->prepare("
        SELECT m.*, c.name AS category_name, 
               DATEDIFF(m.expiration_date, CURDATE()) AS days_remaining
        FROM medicines m
        JOIN categories c ON m.category = c.name
        WHERE m.expiration_date >= CURDATE() 
        AND m.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND m.status != 'Expired'
        ORDER BY days_remaining ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Format date for display
function formatDateDisplay($dateString) {
    if (empty($dateString)) return '-';
    return date('m/d/Y', strtotime($dateString));
}

// Get all medicines
function getAllMedicines($pdo, $categoryFilter = null) {
    $sql = "
        SELECT m.*, c.name AS category_name 
        FROM medicines m
        JOIN categories c ON m.category = c.name
    ";
    
    if ($categoryFilter) {
        $sql .= " WHERE c.name = :category";
    }
    
    $sql .= " ORDER BY m.expiration_date ASC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($categoryFilter) {
        $stmt->bindParam(':category', $categoryFilter);
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get recent stock entries
function getRecentStocks($pdo, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT m.*, c.name AS category_name 
        FROM medicines m
        JOIN categories c ON m.category = c.name
        ORDER BY m.created_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get dispensed medicines
function getDispensedMedicines($pdo) {
    $stmt = $pdo->prepare("
        SELECT d.*, m.name AS medicine_name, m.brand, c.name AS category_name, 
               d.quantity_dispensed AS quantity, u.username AS dispensed_by_name
        FROM dispensed_medicines d
        JOIN medicines m ON d.medicine_id = m.id
        JOIN categories c ON m.category = c.name
        JOIN users u ON d.dispensed_by = u.id
        ORDER BY d.date_dispensed DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get medicine by ID
function getMedicineById($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT m.*, c.name AS category_name 
        FROM medicines m
        JOIN categories c ON m.category = c.name
        WHERE m.id = :id
    ");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch();
}

// Get all categories
function getAllCategories($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Clear notification
function clearNotification($pdo, $medicineId, $notificationType, $userId) {
    // Check if notification already exists
    $stmt = $pdo->prepare("
        SELECT id FROM notifications 
        WHERE medicine_id = :medicine_id 
        AND notification_type = :notification_type
    ");
    $stmt->execute([
        ':medicine_id' => $medicineId,
        ':notification_type' => $notificationType
    ]);
    
    $notification = $stmt->fetch();
    
    if ($notification) {
        // Update existing notification
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_cleared = TRUE, cleared_at = NOW(), cleared_by = :user_id
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $notification['id'],
            ':user_id' => $userId
        ]);
    } else {
        // Insert new cleared notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (medicine_id, notification_type, is_cleared, cleared_at, cleared_by)
            VALUES (:medicine_id, :notification_type, TRUE, NOW(), :user_id)
        ");
        $stmt->execute([
            ':medicine_id' => $medicineId,
            ':notification_type' => $notificationType,
            ':user_id' => $userId
        ]);
    }
}

// Get inventory summary
function getInventorySummary($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            m.name AS medicine_name,
            SUM(m.quantity) AS total_quantity,
            SUM(CASE WHEN m.expiration_date < CURDATE() THEN m.quantity ELSE 0 END) AS expired_quantity,
            SUM(CASE WHEN m.expiration_date >= CURDATE() AND m.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN m.quantity ELSE 0 END) AS expiring_soon_quantity
        FROM medicines m
        GROUP BY m.name
        ORDER BY m.name
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get monthly dispensing data
function getMonthlyDispensingData($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(date_dispensed, '%b %Y') AS month_year,
            SUM(quantity) AS total_quantity
        FROM dispensed_medicines
        GROUP BY month_year
        ORDER BY date_dispensed
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get category distribution
function getCategoryDistribution($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            c.name AS category_name,
            SUM(m.quantity) AS total_quantity
        FROM medicines m
        JOIN categories c ON m.category = c.name
        GROUP BY c.name
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get expiration status counts
function getExpirationStatusCounts($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN expiration_date < CURDATE() THEN quantity ELSE 0 END) AS expired,
            SUM(CASE WHEN expiration_date >= CURDATE() AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN quantity ELSE 0 END) AS expiring_soon,
            SUM(CASE WHEN expiration_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN quantity ELSE 0 END) AS valid
        FROM medicines
    ");
    $stmt->execute();
    return $stmt->fetch();
}

// Get category ID by name
function getCategoryIdByName($pdo, $categoryName) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name");
    $stmt->bindParam(':name', $categoryName);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

// Create a new category
function createCategory($pdo, $categoryName) {
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
    $stmt->bindParam(':name', $categoryName);
    $stmt->execute();
    return $pdo->lastInsertId();
}
?>