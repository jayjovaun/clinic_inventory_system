<?php
session_start();

// Set page title
$pageTitle = 'Medicine Inventory Report';
$pageSubtitle = 'View and manage inventory records';

require_once 'database.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header('Location: login.php');
    exit();
}

// Get medicines data
$medicines = getAllMedicines($pdo);
$dispensedMedicines = getDispensedMedicines($pdo);
$categories = getAllCategories($pdo);

// Handle search and filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$expiration = $_GET['expiration'] ?? '';
$deliveryMonth = $_GET['delivery_month'] ?? '';

// Filter medicines based on search and filters
if (!empty($search) || !empty($category) || !empty($expiration) || !empty($deliveryMonth)) {
    $filteredMedicines = [];
    
    foreach ($medicines as $medicine) {
        $includeItem = true;
        
        // Filter by search term (medicine name)
        if (!empty($search) && stripos($medicine['name'], $search) === false) {
            $includeItem = false;
        }
        
        // Filter by category
        if (!empty($category) && $medicine['category_name'] !== $category) {
            $includeItem = false;
        }
        
        // Filter by expiration status
        if (!empty($expiration)) {
            $expirationStatus = getExpirationStatus($medicine['expiration_date']);
            if ($expirationStatus['status'] !== $expiration) {
                $includeItem = false;
            }
        }
        
        // Filter by delivery month
        if (!empty($deliveryMonth)) {
            $medicineDeliveryMonth = date('Y-m', strtotime($medicine['date_delivered']));
            if ($medicineDeliveryMonth !== $deliveryMonth) {
                $includeItem = false;
            }
        }
        
        if ($includeItem) {
            $filteredMedicines[] = $medicine;
        }
    }
    
    $medicines = $filteredMedicines;
}

// Sort medicines by expiration date (expiring soon first)
usort($medicines, function($a, $b) {
    $aDate = new DateTime($a['expiration_date']);
    $bDate = new DateTime($b['expiration_date']);
    $today = new DateTime();
    
    // If both are expired, sort by most recently expired
    if ($aDate < $today && $bDate < $today) {
        return $aDate < $bDate ? 1 : -1;
    }
    
    // If only one is expired, it comes first
    if ($aDate < $today) return -1;
    if ($bDate < $today) return 1;
    
    // If both are not expired, sort by closest to expiration
    return $aDate > $bDate ? 1 : -1;
});

// Helper function to get expiration status
function getExpirationStatus($expirationDate) {
    $today = new DateTime();
    $expiry = new DateTime($expirationDate);
    $diff = $today->diff($expiry);
    $daysRemaining = $diff->days * ($diff->invert ? -1 : 1);
    
    if ($daysRemaining < 0) {
        return ['status' => 'Expired', 'class' => 'danger', 'days' => abs($daysRemaining)];
    } elseif ($daysRemaining <= 30) {
        return ['status' => 'Expiring Soon', 'class' => 'warning', 'days' => $daysRemaining];
    } else {
        return ['status' => 'Valid', 'class' => 'success', 'days' => $daysRemaining];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Clinic Inventory System</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/docx@8.3.0/build/index.umd.js"></script>
    <script src="/clinicz/js/export-utils.js" defer></script>
    <script src="/clinicz/js/pagination-utils.js" defer></script>
    
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #343a40;
            --secondary-color: #495057;
            --accent-color: #198754;
            --expired-color: #e74a3b;
            --expiring-color: #f6c23e;
            --valid-color: #1cc88a;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.05);
            --hover-shadow: 0 6px 15px rgba(0,0,0,0.1);
            --border-radius: 10px;
            --spacing-sm: 10px;
            --spacing-md: 15px;
            --spacing-lg: 25px;
        }
        
        body {
            display: flex;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--primary-color);
            color: white;
            padding: var(--spacing-lg);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-logo {
            display: block;
            margin: 0 auto var(--spacing-lg);
            max-width: 100%;
            height: auto;
        }
        
        .sidebar h3 {
            text-align: center;
            margin-bottom: var(--spacing-lg);
            font-weight: 600;
            font-size: 1.4rem;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: var(--spacing-sm) var(--spacing-md);
            margin: var(--spacing-sm) 0;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .sidebar a i {
            margin-right: var(--spacing-sm);
            width: 20px;
            text-align: center;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background-color: var(--secondary-color);
            transform: translateX(3px);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: var(--spacing-lg);
            width: calc(100% - var(--sidebar-width));
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .dashboard-title h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .dashboard-title p {
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .dashboard-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: none;
            margin-bottom: var(--spacing-lg);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: var(--spacing-lg);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--light-bg);
            font-weight: 600;
            border-top: none;
            vertical-align: middle;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
            border-radius: 30px;
        }
        
        .badge-expired {
            background-color: var(--expired-color);
            color: white;
        }
        
        .badge-expiring {
            background-color: var(--expiring-color);
            color: #333;
        }
        
        .badge-valid {
            background-color: var(--valid-color);
            color: white;
        }
        
        .account-dropdown {
            margin-left: var(--spacing-md);
        }
        
        .account-dropdown .btn-link {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: none !important;
            padding: 0 !important;
            text-decoration: none !important;
        }
        
        .account-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
            border: none !important;
            outline: none !important;
            background: none !important;
            text-decoration: none !important;
        }
        
        .account-icon:hover { 
            color: var(--accent-color);
            transform: scale(1.1);
        }
        
        .account-dropdown .btn-link:focus,
        .account-dropdown .btn-link:active {
            outline: none !important;
            box-shadow: none !important;
            border: none !important;
        }
        
        .search-bar {
            max-width: 500px;
            margin-bottom: var(--spacing-md);
        }
        
        .filter-actions {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
            align-items: center;
        }
        
        .filter-btn {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 5px;
            transition: all 0.2s;
        }
        
        .filter-btn:hover {
            color: var(--accent-color);
            transform: scale(1.1);
        }
        
        .filter-btn.active {
            color: var(--accent-color);
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .action-buttons {
            white-space: nowrap;
        }
        
        .editing-row {
            background-color: rgba(25, 135, 84, 0.08) !important;
        }
        
        /* Export Button Style - Matching the image */
        .export-csv-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #ffffff;
            color: #0d6efd;
            border: 1px solid #0d6efd;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: normal;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            width: auto;
        }
        
        .export-csv-btn:hover {
            background-color: #f8f9fa;
        }
        
        .export-csv-btn i {
            color: #0d6efd;
            font-size: 16px;
            margin-right: 4px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .dashboard-actions {
                margin-top: var(--spacing-md);
                width: 100%;
                justify-content: flex-end;
            }
        }
        
        /* Fix for user icon blue lines */
        .account-link {
            text-decoration: none !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: none !important;
            padding: 0 !important;
            display: inline-block;
        }
        
        .account-link:focus,
        .account-link:active,
        .account-link:hover {
            text-decoration: none !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="images/logo_new.png" alt="Health Logo" height="80" class="sidebar-logo">
        <h3>Clinic Inventory</h3>
        <a href="dashboard.php">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="index.php">
            <i class="fas fa-plus-circle"></i>
            <span>Stock Entry</span>
        </a>
        <a href="reports.php" class="active">
            <i class="fas fa-file-medical"></i>
            <span>Medicine Dispense</span>
        </a>
        <a href="inventory.php">
            <i class="fas fa-boxes"></i>
            <span>Inventory</span>
        </a>
        <div style="flex-grow: 1; min-height: 30px;"></div>
        <a href="logout.php" class="text-danger">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1><?php echo $pageTitle; ?></h1>
                <p class="text-muted"><?php echo $pageSubtitle; ?></p>
            </div>
            <div class="dashboard-actions">
                <!-- Account Dropdown -->
                <div class="dropdown account-dropdown">
                    <a href="#" class="account-link" id="accountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle account-icon"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header" id="accountUsername"><?php echo htmlspecialchars($_SESSION['username']); ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="search-bar">
            <div class="input-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search medicine by name..." value="<?php echo htmlspecialchars($search); ?>">
                <button id="searchBtn" class="btn btn-primary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <!-- Medicine Inventory Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="fw-bold"><i class="fas fa-pills me-2"></i>Medicine Inventory</h5>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showExportOptions('inventoryTable')">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Actions -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="filter-actions">
                        <button class="filter-btn" id="filterCategoryBtn" title="Filter by Category" data-bs-toggle="modal" data-bs-target="#categoryFilterModal">
                            <i class="fas fa-tags"></i>
                        </button>
                        <button class="filter-btn" id="filterExpirationBtn" title="Filter by Expiration" data-bs-toggle="modal" data-bs-target="#expirationFilterModal">
                            <i class="fas fa-calendar-times"></i>
                        </button>
                        <button class="filter-btn" id="filterDeliveryBtn" title="Filter by Delivery Month" data-bs-toggle="modal" data-bs-target="#deliveryFilterModal">
                            <i class="fas fa-truck"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="resetFilterBtn" title="Reset filters">
                            <i class="fas fa-sync-alt me-1"></i> Reset
                        </button>
                    </div>
                </div>
                
                <!-- Inventory Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Expiration Date</th>
                                <th class="text-center">Delivery Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicines as $medicine): 
                                $expirationStatus = getExpirationStatus($medicine['expiration_date']);
                            ?>
                            <tr data-id="<?php echo $medicine['id']; ?>" data-name="<?php echo htmlspecialchars($medicine['name']); ?>" data-brand="<?php echo htmlspecialchars($medicine['brand']); ?>" data-category="<?php echo htmlspecialchars($medicine['category_name']); ?>" data-quantity="<?php echo $medicine['quantity']; ?>" data-expiration="<?php echo $medicine['expiration_date']; ?>" data-delivery="<?php echo $medicine['date_delivered']; ?>">
                                <td class="medicine-name"><?php echo htmlspecialchars($medicine['name']); ?></td>
                                <td class="brand-name"><?php echo htmlspecialchars($medicine['brand']); ?></td>
                                <td class="category-name"><?php echo htmlspecialchars($medicine['category_name']); ?></td>
                                <td class="quantity text-center"><?php echo $medicine['quantity']; ?></td>
                                <td class="expiration-date text-center"><?php echo formatDateDisplay($medicine['expiration_date']); ?></td>
                                <td class="delivery-date text-center"><?php echo formatDateDisplay($medicine['date_delivered']); ?></td>
                                <td class="status text-center">
                                    <?php if ($expirationStatus['status'] === 'Expired'): ?>
                                        <span class="badge bg-danger">Expired (<?php echo $expirationStatus['days']; ?> days ago)</span>
                                    <?php elseif ($expirationStatus['status'] === 'Expiring Soon'): ?>
                                        <span class="badge bg-warning text-dark">Expiring (<?php echo $expirationStatus['days']; ?> days left)</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Valid (<?php echo $expirationStatus['days']; ?> days left)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center action-buttons">
                                    <button class="btn btn-success btn-sm btn-action dispense-btn" title="Dispense">
                                        <i class="fas fa-prescription-bottle-alt"></i>
                                    </button>
                                    <button class="btn btn-primary btn-sm btn-action edit-btn" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-action delete-btn" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Medicine Dispensed Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="fw-bold"><i class="fas fa-prescription-bottle-alt me-2"></i>Medicine Dispensed</h5>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showExportOptions('dispensedTable')">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="dispensedTable">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Date Dispensed</th>
                                <th>Dispensed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dispensedMedicines as $dispensed): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dispensed['medicine_name']); ?></td>
                                <td><?php echo htmlspecialchars($dispensed['brand']); ?></td>
                                <td><?php echo htmlspecialchars($dispensed['category_name']); ?></td>
                                <td class="text-center"><?php echo $dispensed['quantity']; ?></td>
                                <td class="text-center"><?php echo formatDateDisplay($dispensed['date_dispensed']); ?></td>
                                <td><?php echo htmlspecialchars($dispensed['dispensed_by_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modals -->
    <div class="modal fade" id="categoryFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter by Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <select id="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        <option value="Medicine" <?php echo ($category === 'Medicine') ? 'selected' : ''; ?>>Medicine</option>
                        <option value="Medical Supplies" <?php echo ($category === 'Medical Supplies') ? 'selected' : ''; ?>>Medical Supplies</option>
                        <option value="Dental Supplies" <?php echo ($category === 'Dental Supplies') ? 'selected' : ''; ?>>Dental Supplies</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="applyCategoryFilterBtn">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="expirationFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter by Expiration Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <select id="expirationFilter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Expired" <?php echo ($expiration === 'Expired') ? 'selected' : ''; ?>>Expired</option>
                        <option value="Expiring Soon" <?php echo ($expiration === 'Expiring Soon') ? 'selected' : ''; ?>>Expiring Soon (≤30 days)</option>
                        <option value="Valid" <?php echo ($expiration === 'Valid') ? 'selected' : ''; ?>>Valid (>30 days)</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="applyExpirationFilterBtn">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deliveryFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter by Delivery Month</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <select id="deliveryMonthFilter" class="form-select">
                        <option value="">All Months</option>
                        <?php
                        $months = [];
                        foreach ($medicines as $medicine) {
                            $month = date('F Y', strtotime($medicine['date_delivered']));
                            $monthValue = date('Y-m', strtotime($medicine['date_delivered']));
                            if (!in_array($month, $months)) {
                                $months[] = $month;
                                echo '<option value="' . htmlspecialchars($monthValue) . '" ' . (($deliveryMonth === $monthValue) ? 'selected' : '') . '>' . htmlspecialchars($month) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="applyDeliveryFilterBtn">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dispense Medicine Modal -->
    <div class="modal fade" id="dispenseMedicineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dispense Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="dispenseMedicineForm">
                        <input type="hidden" id="dispenseMedicineId">
                        <input type="hidden" id="dispenseMedicineName">
                        <input type="hidden" id="dispenseBrandName">
                        <input type="hidden" id="dispenseCategory">
                        <input type="hidden" id="dispenseDate" value="<?php echo date('Y-m-d'); ?>">
                        <div class="mb-3">
                            <label for="dispenseQuantity" class="form-label">Quantity to Dispense</label>
                            <input type="number" class="form-control" id="dispenseQuantity" min="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmDispenseBtn">Dispense</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Medicine Modal -->
    <div class="modal fade" id="editMedicineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editMedicineForm">
                        <input type="hidden" id="editMedicineId">
                        <div class="mb-3">
                            <label for="editMedicineName" class="form-label">Medicine Name</label>
                            <input type="text" class="form-control" id="editMedicineName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editBrandName" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="editBrandName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCategory" class="form-label">Category</label>
                            <select class="form-select" id="editCategory" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" class="form-control mt-2" id="editCategoryOther" placeholder="Enter category" style="display: none;">
                        </div>
                        <div class="mb-3">
                            <label for="editQuantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="editQuantity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="editExpirationDate" class="form-label">Expiration Date</label>
                            <input type="date" class="form-control" id="editExpirationDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDeliveryDate" class="form-label">Delivery Date</label>
                            <input type="date" class="form-control" id="editDeliveryDate" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveMedicineBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this medicine from inventory?</p>
                    <p class="fw-bold" id="deleteItemName"></p>
                    <input type="hidden" id="deleteItemId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertTitle">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="alertMessage"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Reports Page -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Pagination configuration
            const rowsPerPage = 10;
            const paginationData = {
                'inventoryTable': { currentPage: 1, totalPages: 1 },
                'dispensedTable': { currentPage: 1, totalPages: 1 }
            };
            
            // Initialize pagination for tables
            initPagination('inventoryTable');
            
            // Initialize search functionality
            document.getElementById('searchBtn').addEventListener('click', function() {
                const searchTerm = document.getElementById('searchInput').value.trim();
                if (searchTerm) {
                    window.location.href = `reports.php?search=${encodeURIComponent(searchTerm)}`;
                }
            });
            
            // Allow pressing Enter to search
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('searchBtn').click();
                }
            });
            
            // Reset filter button
            document.getElementById('resetFilterBtn').addEventListener('click', function() {
                window.location.href = 'reports.php';
            });
            
            // Function to initialize pagination
            function initPagination(tableId) {
                const table = document.getElementById(tableId);
                if (!table) return;
                
                const rows = table.querySelectorAll('tbody tr');
                
                // Calculate total pages
                paginationData[tableId].totalPages = Math.ceil(rows.length / rowsPerPage);
                
                // Create pagination controls
                createPaginationControls(tableId);
                
                // Show first page
                showPage(tableId, 1);
            }
            
            // Function to create pagination controls
            function createPaginationControls(tableId) {
                const table = document.getElementById(tableId);
                if (!table) return;
                
                // Create container for pagination
                const paginationContainer = document.createElement('div');
                paginationContainer.className = 'pagination-container mt-3 d-flex justify-content-center';
                paginationContainer.id = tableId + 'Pagination';
                
                // Add pagination controls to container
                updatePaginationControls(tableId);
                
                // Insert pagination container after table
                table.parentNode.insertBefore(paginationContainer, table.nextSibling);
            }
            
            // Function to update pagination controls
            function updatePaginationControls(tableId) {
                const paginationContainer = document.getElementById(tableId + 'Pagination');
                if (!paginationContainer) return;
                
                const currentPage = paginationData[tableId].currentPage;
                const totalPages = paginationData[tableId].totalPages;
                
                // Clear existing controls
                paginationContainer.innerHTML = '';
                
                if (totalPages <= 1) return; // No pagination needed
                
                // Create pagination nav
                const nav = document.createElement('nav');
                nav.setAttribute('aria-label', 'Page navigation');
                
                const ul = document.createElement('ul');
                ul.className = 'pagination';
                
                // Previous button
                const prevLi = document.createElement('li');
                prevLi.className = 'page-item' + (currentPage === 1 ? ' disabled' : '');
                
                const prevLink = document.createElement('a');
                prevLink.className = 'page-link';
                prevLink.href = '#';
                prevLink.textContent = 'Previous';
                prevLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentPage > 1) {
                        showPage(tableId, currentPage - 1);
                    }
                });
                
                prevLi.appendChild(prevLink);
                ul.appendChild(prevLi);
                
                // Page numbers
                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                
                if (endPage - startPage + 1 < maxVisiblePages) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }
                
                // First page button if not visible
                if (startPage > 1) {
                    const firstLi = document.createElement('li');
                    firstLi.className = 'page-item';
                    
                    const firstLink = document.createElement('a');
                    firstLink.className = 'page-link';
                    firstLink.href = '#';
                    firstLink.textContent = '1';
                    firstLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        showPage(tableId, 1);
                    });
                    
                    firstLi.appendChild(firstLink);
                    ul.appendChild(firstLi);
                    
                    if (startPage > 2) {
                        const ellipsisLi = document.createElement('li');
                        ellipsisLi.className = 'page-item disabled';
                        
                        const ellipsisSpan = document.createElement('span');
                        ellipsisSpan.className = 'page-link';
                        ellipsisSpan.textContent = '...';
                        
                        ellipsisLi.appendChild(ellipsisSpan);
                        ul.appendChild(ellipsisLi);
                    }
                }
                
                // Page numbers
                for (let i = startPage; i <= endPage; i++) {
                    const pageLi = document.createElement('li');
                    pageLi.className = 'page-item' + (i === currentPage ? ' active' : '');
                    
                    const pageLink = document.createElement('a');
                    pageLink.className = 'page-link';
                    pageLink.href = '#';
                    pageLink.textContent = i;
                    pageLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        showPage(tableId, i);
                    });
                    
                    pageLi.appendChild(pageLink);
                    ul.appendChild(pageLi);
                }
                
                // Last page button if not visible
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        const ellipsisLi = document.createElement('li');
                        ellipsisLi.className = 'page-item disabled';
                        
                        const ellipsisSpan = document.createElement('span');
                        ellipsisSpan.className = 'page-link';
                        ellipsisSpan.textContent = '...';
                        
                        ellipsisLi.appendChild(ellipsisSpan);
                        ul.appendChild(ellipsisLi);
                    }
                    
                    const lastLi = document.createElement('li');
                    lastLi.className = 'page-item';
                    
                    const lastLink = document.createElement('a');
                    lastLink.className = 'page-link';
                    lastLink.href = '#';
                    lastLink.textContent = totalPages;
                    lastLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        showPage(tableId, totalPages);
                    });
                    
                    lastLi.appendChild(lastLink);
                    ul.appendChild(lastLi);
                }
                
                // Next button
                const nextLi = document.createElement('li');
                nextLi.className = 'page-item' + (currentPage === totalPages ? ' disabled' : '');
                
                const nextLink = document.createElement('a');
                nextLink.className = 'page-link';
                nextLink.href = '#';
                nextLink.textContent = 'Next';
                nextLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentPage < totalPages) {
                        showPage(tableId, currentPage + 1);
                    }
                });
                
                nextLi.appendChild(nextLink);
                ul.appendChild(nextLink);
                
                nav.appendChild(ul);
                paginationContainer.appendChild(nav);
            }
            
            // Function to show a specific page
            function showPage(tableId, pageNumber) {
                const table = document.getElementById(tableId);
                if (!table) return;
                
                const tbody = table.querySelector('tbody');
                if (!tbody) return;
                
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                // Validate page number
                const totalPages = paginationData[tableId].totalPages;
                pageNumber = Math.max(1, Math.min(pageNumber, totalPages));
                
                // Update current page
                paginationData[tableId].currentPage = pageNumber;
                
                // Calculate start and end index
                const startIndex = (pageNumber - 1) * rowsPerPage;
                const endIndex = Math.min(startIndex + rowsPerPage, rows.length);
                
                // Hide all rows
                rows.forEach(row => row.style.display = 'none');
                
                // Show rows for current page
                for (let i = startIndex; i < endIndex; i++) {
                    rows[i].style.display = '';
                }
                
                // Update pagination controls
                updatePaginationControls(tableId);
            }
            
            // Initialize dispense buttons
            document.querySelectorAll('.dispense-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const id = row.getAttribute('data-id');
                    const name = row.getAttribute('data-name');
                    const brand = row.getAttribute('data-brand');
                    const category = row.getAttribute('data-category');
                    const quantity = row.getAttribute('data-quantity');
                    
                    // Populate the dispense form
                    document.getElementById('dispenseMedicineId').value = id;
                    document.getElementById('dispenseMedicineName').value = name;
                    document.getElementById('dispenseBrandName').value = brand;
                    document.getElementById('dispenseCategory').value = category;
                    
                    // Show the modal
                    const dispenseModal = new bootstrap.Modal(document.getElementById('dispenseMedicineModal'));
                    dispenseModal.show();
                });
            });
            
            // Initialize edit buttons
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const id = row.getAttribute('data-id');
                    const name = row.getAttribute('data-name');
                    const brand = row.getAttribute('data-brand');
                    const category = row.getAttribute('data-category');
                    const quantity = row.getAttribute('data-quantity');
                    const expirationDate = row.getAttribute('data-expiration');
                    const deliveryDate = row.getAttribute('data-delivery');
                    
                    // Populate the edit form
                    document.getElementById('editMedicineId').value = id;
                    document.getElementById('editMedicineName').value = name;
                    document.getElementById('editBrandName').value = brand;
                    
                    const categorySelect = document.getElementById('editCategory');
                    const categoryOtherContainer = document.getElementById('editCategoryOther').parentElement;
                    const categoryOther = document.getElementById('editCategoryOther');
                    
                    // Check if category exists in the dropdown
                    let categoryExists = false;
                    for (let i = 0; i < categorySelect.options.length; i++) {
                        if (categorySelect.options[i].value === category) {
                            categorySelect.selectedIndex = i;
                            categoryExists = true;
                            break;
                        }
                    }
                    
                    // If category doesn't exist, select "Other" and show the input field
                    if (!categoryExists) {
                        for (let i = 0; i < categorySelect.options.length; i++) {
                            if (categorySelect.options[i].value === 'Other') {
                                categorySelect.selectedIndex = i;
                                break;
                            }
                        }
                        categoryOtherContainer.style.display = 'block';
                        categoryOther.value = category;
                    } else {
                        categoryOtherContainer.style.display = 'none';
                        categoryOther.value = '';
                    }
                    
                    document.getElementById('editQuantity').value = quantity;
                    document.getElementById('editExpirationDate').value = expirationDate;
                    document.getElementById('editDeliveryDate').value = deliveryDate;
                    
                    // Show the modal
                    const editModal = new bootstrap.Modal(document.getElementById('editMedicineModal'));
                    editModal.show();
                });
            });
            
            // Initialize delete buttons
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const id = row.getAttribute('data-id');
                    const name = row.getAttribute('data-name');
                    
                    // Set the medicine ID for deletion
                    document.getElementById('deleteItemId').value = id;
                    document.getElementById('deleteItemName').textContent = name;
                    
                    // Show the modal
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                    deleteModal.show();
                });
            });
            
            // Confirm dispense button
            document.getElementById('confirmDispenseBtn').addEventListener('click', function() {
                if (validateDispenseForm()) {
                    dispenseMedicine();
                }
            });
            
            // Confirm edit button
            document.getElementById('saveMedicineBtn').addEventListener('click', function() {
                if (validateEditForm()) {
                    saveMedicineChanges();
                }
            });
            
            // Confirm delete button
            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                const medicineId = document.getElementById('deleteItemId').value;
                deleteMedicine(medicineId);
            });
            
            // Apply filter buttons
            document.getElementById('applyCategoryFilterBtn').addEventListener('click', function() {
                applyFilters();
            });
            
            document.getElementById('applyExpirationFilterBtn').addEventListener('click', function() {
                applyFilters();
            });
            
            document.getElementById('applyDeliveryFilterBtn').addEventListener('click', function() {
                applyFilters();
            });
        });
        
        // Validate dispense form
        function validateDispenseForm() {
            const form = document.getElementById('dispenseMedicineForm');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
            
            return true;
        }
        
        // Validate edit form
        function validateEditForm() {
            const form = document.getElementById('editMedicineForm');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }
            
            // Additional validation for "Other" category
            const categorySelect = document.getElementById('editCategory');
            const categoryOther = document.getElementById('editCategoryOther');
            
            if (categorySelect.value === 'Other' && !categoryOther.value.trim()) {
                categoryOther.setCustomValidity('Please enter a category name');
                categoryOther.reportValidity();
                return false;
            }
            
            return true;
        }
        
        // Save medicine changes
        function saveMedicineChanges() {
            const id = document.getElementById('editMedicineId').value;
            const name = document.getElementById('editMedicineName').value;
            const brand = document.getElementById('editBrandName').value;
            let category = document.getElementById('editCategory').value;
            
            // If category is "Other", use the custom value
            if (category === 'Other') {
                category = document.getElementById('editCategoryOther').value;
            }
            
            const quantity = document.getElementById('editQuantity').value;
            const expirationDate = document.getElementById('editExpirationDate').value;
            const deliveryDate = document.getElementById('editDeliveryDate').value;
            
            // Create form data for submission
            const formData = new FormData();
            formData.append('id', id);
            formData.append('name', name);
            formData.append('brand', brand);
            formData.append('category', category);
            formData.append('quantity', quantity);
            formData.append('expiration_date', expirationDate);
            formData.append('delivery_date', deliveryDate);
            
            // Send AJAX request to update medicine
            fetch('api/update_medicine.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editMedicineModal'));
                    modal.hide();
                    
                    // Show success message
                    showAlert('Success', 'Medicine updated successfully');
                    
                    // Reload the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('Error', data.message || 'Failed to update medicine');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error', 'An error occurred while updating the medicine');
            });
        }
        
        // Dispense medicine
        function dispenseMedicine() {
            const id = document.getElementById('dispenseMedicineId').value;
            const quantity = document.getElementById('dispenseQuantity').value;
            const dateDispensed = document.getElementById('dispenseDate').value;
            
            // Create form data for submission
            const formData = new FormData();
            formData.append('id', id);
            formData.append('quantity', quantity);
            formData.append('date_dispensed', dateDispensed);
            
            // Send AJAX request to dispense medicine
            fetch('api/dispense_medicine.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('dispenseMedicineModal'));
                modal.hide();
                
                if (data.success) {
                    // Show success message
                    showAlert('Success', 'Medicine dispensed successfully');
                    
                    // Reload the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('Error', data.message || 'Failed to dispense medicine');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error', 'An error occurred while dispensing the medicine');
            });
        }
        
        // Delete medicine
        function deleteMedicine(id) {
            // Create form data for submission
            const formData = new FormData();
            formData.append('id', id);
            
            // Send AJAX request to delete medicine
            fetch('api/delete_medicine.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
                modal.hide();
                
                if (data.success) {
                    // Show success message
                    showAlert('Success', 'Medicine deleted successfully');
                    
                    // Reload the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('Error', data.message || 'Failed to delete medicine');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error', 'An error occurred while deleting the medicine');
            });
        }
        
        // Apply filters and update URL
        function applyFilters() {
            const searchValue = document.getElementById('searchInput').value;
            const categoryValue = document.getElementById('categoryFilter').value;
            const expirationValue = document.getElementById('expirationFilter').value;
            const deliveryMonthValue = document.getElementById('deliveryMonthFilter').value;
            
            // Build query string
            let queryParams = [];
            if (searchValue) queryParams.push('search=' + encodeURIComponent(searchValue));
            if (categoryValue) queryParams.push('category=' + encodeURIComponent(categoryValue));
            if (expirationValue) queryParams.push('expiration=' + encodeURIComponent(expirationValue));
            if (deliveryMonthValue) queryParams.push('delivery_month=' + encodeURIComponent(deliveryMonthValue));
            
            // Update URL and reload page
            const queryString = queryParams.length > 0 ? '?' + queryParams.join('&') : '';
            window.location.href = 'reports.php' + queryString;
        }
        
        // Show alert modal
        function showAlert(title, message) {
            document.getElementById('alertTitle').textContent = title;
            document.getElementById('alertMessage').textContent = message;
            
            const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
            alertModal.show();
        }
        
        // Function to show export options
        function showExportOptions(tableId) {
            // Create modal HTML
            const modalHTML = `
            <div class="modal fade" id="exportOptionsModal" tabindex="-1" aria-labelledby="exportOptionsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exportOptionsModalLabel">Export Options</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Choose a format to export the inventory data:</p>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" id="exportPDF">
                                    <i class="fas fa-file-pdf me-2"></i>Export as PDF
                                </button>
                                <button class="btn btn-outline-primary" id="exportExcel">
                                    <i class="fas fa-file-excel me-2"></i>Export as Excel
                                </button>
                                <button class="btn btn-outline-primary" id="exportWord">
                                    <i class="fas fa-file-word me-2"></i>Export as Word
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('exportOptionsModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Get table title
            let tableTitle = 'Medicine Inventory';
            const tableElement = document.getElementById(tableId);
            if (tableElement) {
                const cardHeader = tableElement.closest('.card').querySelector('.card-header');
                if (cardHeader) {
                    const titleElement = cardHeader.querySelector('h5');
                    if (titleElement) {
                        tableTitle = titleElement.textContent.trim();
                    }
                }
            }
            
            // Get table data
            const tableData = getTableData(tableId);
            
            // Set up event listeners
            document.getElementById('exportPDF').addEventListener('click', function() {
                exportTableToPDF(tableTitle, tableId, tableData);
                bootstrap.Modal.getInstance(document.getElementById('exportOptionsModal')).hide();
            });
            
            document.getElementById('exportExcel').addEventListener('click', function() {
                exportTableToExcel(tableTitle, tableId, tableData);
                bootstrap.Modal.getInstance(document.getElementById('exportOptionsModal')).hide();
            });
            
            document.getElementById('exportWord').addEventListener('click', function() {
                exportToWord(tableTitle, tableId, tableData);
                bootstrap.Modal.getInstance(document.getElementById('exportOptionsModal')).hide();
            });
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('exportOptionsModal'));
            modal.show();
        }
        
        // Function to get table data
        function getTableData(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return [];
            
            // Get headers
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
            
            // Remove "Actions" header for exports
            const actionColumnIndex = headers.findIndex(header => header === 'Actions');

            // Get all rows (not just visible ones)
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            
            // Get row data
            const data = rows.map(row => {
                return Array.from(row.querySelectorAll('td')).map((td, index) => {
                    // Skip the Actions column for exports (if found)
                    if (actionColumnIndex !== -1 && index === actionColumnIndex) {
                        return null;
                    }
                    // Always extract only plain text, ignore all HTML elements
                    return td.textContent.trim();
                }).filter(cell => cell !== null); // Remove null values (Actions column)
            });
            
            // Remove Actions header from headers array
            if (actionColumnIndex !== -1) {
                headers.splice(actionColumnIndex, 1);
            }
        
            // Combine headers and data
            return [headers, ...data];
        }
        
        // Function to export table to PDF
        function exportTableToPDF(title, tableId, data) {
            // Get summary data
            const summary = {
                'Total Items': data.length - 1,
                'Category': 'Medicine Inventory',
                'Date': new Date().toLocaleDateString()
            };
            
            // Set options
            const options = {
                orientation: 'landscape',
                summary: summary,
                category: 'Medicine Inventory'
            };
            
            // Generate filename
            const filename = `${title.replace(/\s+/g, '_')}_${formatDateForFilename(new Date())}`;
            
            // Use enhanced export utility
            exportToPDF(title, filename, data, options);
        }
        
        // Function to export table to Excel
        function exportTableToExcel(title, tableId, data) {
            // Get summary data
            const summary = {
                'Total Items': data.length - 1,
                'Category': 'Medicine Inventory',
                'Date': new Date().toLocaleDateString()
            };
            
            // Set options
            const options = {
                sheetName: 'Medicine Inventory',
                summary: summary,
                includeCharts: false,
                category: 'Medicine Inventory'
            };
            
            // Generate filename
            const filename = `${title.replace(/\s+/g, '_')}_${formatDateForFilename(new Date())}`;
            
            // Use enhanced export utility
            exportToExcel(title, filename, data, options);
        }
        
        // Function to export table to CSV
        function exportTableToCSV(title, tableId, data) {
            // Get summary data
            const summary = {
                'Total Items': data.length - 1,
                'Category': 'Medicine Inventory',
                'Date': new Date().toLocaleDateString()
            };
            
            // Set options
            const options = {
                summary: summary,
                category: 'Medicine Inventory'
            };
            
            // Generate filename
            const filename = `${title.replace(/\s+/g, '_')}_${formatDateForFilename(new Date())}`;
            
            // Use enhanced export utility
            exportToCSV(title, filename, data, options);
        }
        
        // Format date for filename
        function formatDateForFilename(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Define the logout function
        function logout() {
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>


