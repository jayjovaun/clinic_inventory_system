<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';
require_once 'functions.php';
require_once 'models/Medicine.php';
require_once 'models/User.php';

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header('Location: login.php');
    exit();
}

// Initialize models
$medicineModel = new Medicine();
$userModel = new User();

$pageTitle = "Inventory";
$pageDescription = "View and manage medicine inventory by category";

// Get all medicines with their categories
$medicines = $medicineModel->getAllWithCategory();

// Get expired and expiring medicines
$expiredMedicines = $medicineModel->getExpired();
$expiringSoonMedicines = $medicineModel->getExpiringSoon();

// Filter medicines by category
$medicineCategoryItems = array_filter($medicines, function($medicine) {
    return strtolower($medicine['category_name']) === 'medicine';
});

$medicalSuppliesCategoryItems = array_filter($medicines, function($medicine) {
    return strtolower($medicine['category_name']) === 'medical supplies';
});

$dentalSuppliesCategoryItems = array_filter($medicines, function($medicine) {
    return strtolower($medicine['category_name']) === 'dental supplies';
});

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
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
    <link rel="stylesheet" href="/clinicz/assets/css/style.css">
    
    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/docx@8.3.0/build/index.umd.js"></script>
    <!-- Load export-utils.js before other scripts -->
    <script src="js/export-utils.js" defer></script>
    <script src="/clinicz/clinic.js" defer></script>
    
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
            background-color: var(--light-bg);
            min-height: 100vh;
            overflow-x: hidden;
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
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: var(--spacing-lg);
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid rgba(0,0,0,0.05);
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
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: var(--spacing-lg);
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: var(--spacing-md) var(--spacing-lg);
        }
        
        .card-body {
            padding: var(--spacing-lg);
        }
        
        .summary-card {
            display: flex;
            align-items: center;
            padding: var(--spacing-lg);
            border-radius: var(--border-radius);
            color: white;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }
        
        .summary-icon {
            font-size: 2.5rem;
            margin-right: var(--spacing-lg);
            background-color: rgba(255,255,255,0.2);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .summary-info {
            flex: 1;
        }
        
        .summary-info h3 {
            font-size: 2rem;
            margin-bottom: 5px;
            font-weight: 700;
        }
        
        .summary-info p {
            margin-bottom: 0;
            opacity: 0.9;
            color: white;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: var(--spacing-md);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .table td, .table th {
            padding: 0.75rem 1rem;
            vertical-align: middle;
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
        
        .account-dropdown .btn-link {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: none !important;
            padding: 0 !important;
            text-decoration: none !important;
        }
        
        .account-dropdown .btn-link:focus,
        .account-dropdown .btn-link:active {
            outline: none !important;
            box-shadow: none !important;
            border: none !important;
        }
        
        .search-container {
            margin-bottom: var(--spacing-md);
        }
        
        .search-input {
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
            font-size: 0.875rem;
        }
        
        .search-input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }
        
        .search-button {
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            background-color: var(--accent-color);
            color: white;
            border: none;
            font-size: 0.875rem;
        }
        
        .search-button:hover {
            background-color: #146c43;
        }
        
        /* Professional export button styling to match the image */
        .export-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 132px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .export-btn:hover {
            background-color: #146c43;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .export-btn i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
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
        
        @media (max-width: 992px) {
            .sidebar {
                width: 60px;
                padding: var(--spacing-md) 10px;
                overflow: hidden;
            }
            
            .sidebar h3, .sidebar a span {
                display: none;
            }
            
            .main-content {
                margin-left: 60px;
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
        <a href="reports.php">
            <i class="fas fa-file-medical"></i>
            <span>Medicine Dispense</span>
        </a>
        <a href="inventory.php" class="active">
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
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1><?php echo $pageTitle; ?></h1>
                <p class="text-muted"><?php echo $pageDescription; ?></p>
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
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid-container mb-4">
            <div class="summary-card" style="background-color: var(--accent-color);">
                <div class="summary-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="summary-info">
                    <h3><?php echo array_sum(array_column($medicines, 'quantity')); ?></h3>
                    <p class="text-muted mb-0">Total Items</p>
                </div>
            </div>
            <div class="summary-card" style="background-color: var(--expired-color);">
                <div class="summary-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="summary-info">
                    <h3><?php 
                    $expiredQuantity = 0;
                    foreach ($medicines as $medicine) {
                        $expirationDate = new DateTime($medicine['expiration_date']);
                        $currentDate = new DateTime();
                        if ($expirationDate < $currentDate) {
                            $expiredQuantity += $medicine['quantity'];
                        }
                    }
                    echo $expiredQuantity;
                    ?></h3>
                    <p class="text-muted mb-0">Expired Items</p>
                </div>
            </div>
            <div class="summary-card" style="background-color: var(--expiring-color);">
                <div class="summary-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="summary-info">
                    <h3><?php 
                    $expiringSoonQuantity = 0;
                    foreach ($medicines as $medicine) {
                        $expirationDate = new DateTime($medicine['expiration_date']);
                        $currentDate = new DateTime();
                        $interval = $currentDate->diff($expirationDate);
                        
                        if ($expirationDate >= $currentDate && $interval->days <= 30) {
                            $expiringSoonQuantity += $medicine['quantity'];
                        }
                    }
                    echo $expiringSoonQuantity;
                    ?></h3>
                    <p class="text-muted mb-0">Expiring Soon</p>
                </div>
            </div>
        </div>

        <!-- All Inventory Table -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Complete Inventory</h4>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showExportOptions('allInventoryTable')">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Search Bar -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="allInventorySearchInput" class="form-control search-input" placeholder="Search inventory..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn search-button" id="allInventorySearchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="allInventoryTable">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th class="text-center">Quantity</th>
                                <th>Expiration Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicines as $medicine): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['brand']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['category_name']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($medicine['quantity']); ?></td>
                                <td><?php echo formatDateDisplay($medicine['expiration_date']); ?></td>
                                <td class="text-center">
                                    <?php
                                    $expirationDate = new DateTime($medicine['expiration_date']);
                                    $currentDate = new DateTime();
                                    $interval = $currentDate->diff($expirationDate);
                                    
                                    if ($expirationDate < $currentDate) {
                                        echo '<span class="badge bg-danger">Expired</span>';
                                    } elseif ($interval->days <= 30) {
                                        echo '<span class="badge bg-warning">Expiring Soon</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Valid</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Medicine Category Table -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Medicine</h4>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showExportOptions('medicineTable')">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Search Bar -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="medicineSearchInput" class="form-control search-input" placeholder="Search medicines...">
                            <button class="btn search-button" id="medicineSearchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Medicine Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="medicineTable">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Brand</th>
                                <th class="text-center">Quantity</th>
                                <th>Expiration Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicineCategoryItems as $medicine): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['brand']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($medicine['quantity']); ?></td>
                                <td><?php echo formatDateDisplay($medicine['expiration_date']); ?></td>
                                <td class="text-center">
                                    <?php
                                    $expirationDate = new DateTime($medicine['expiration_date']);
                                    $currentDate = new DateTime();
                                    $interval = $currentDate->diff($expirationDate);
                                    
                                    if ($expirationDate < $currentDate) {
                                        echo '<span class="badge bg-danger">Expired</span>';
                                    } elseif ($interval->days <= 30) {
                                        echo '<span class="badge bg-warning">Expiring Soon</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Valid</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Medical Supplies Category Table -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Medical Supplies</h4>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showExportOptions('medicalSuppliesTable')">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Search Bar -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="medicalSuppliesSearchInput" class="form-control search-input" placeholder="Search medical supplies...">
                            <button class="btn search-button" id="medicalSuppliesSearchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Medical Supplies Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="medicalSuppliesTable">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Brand</th>
                                <th class="text-center">Quantity</th>
                                <th>Expiration Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicalSuppliesCategoryItems as $medicine): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['brand']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($medicine['quantity']); ?></td>
                                <td><?php echo formatDateDisplay($medicine['expiration_date']); ?></td>
                                <td class="text-center">
                                    <?php
                                    $expirationDate = new DateTime($medicine['expiration_date']);
                                    $currentDate = new DateTime();
                                    $interval = $currentDate->diff($expirationDate);
                                    
                                    if ($expirationDate < $currentDate) {
                                        echo '<span class="badge bg-danger">Expired</span>';
                                    } elseif ($interval->days <= 30) {
                                        echo '<span class="badge bg-warning">Expiring Soon</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Valid</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dental Supplies Category Table -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Dental Supplies</h4>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="showExportOptions('dentalSuppliesTable')">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Search Bar -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="dentalSuppliesSearchInput" class="form-control search-input" placeholder="Search dental supplies...">
                            <button class="btn search-button" id="dentalSuppliesSearchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Dental Supplies Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="dentalSuppliesTable">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Brand</th>
                                <th class="text-center">Quantity</th>
                                <th>Expiration Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dentalSuppliesCategoryItems as $medicine): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['brand']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($medicine['quantity']); ?></td>
                                <td><?php echo formatDateDisplay($medicine['expiration_date']); ?></td>
                                <td class="text-center">
                                    <?php
                                    $expirationDate = new DateTime($medicine['expiration_date']);
                                    $currentDate = new DateTime();
                                    $interval = $currentDate->diff($expirationDate);
                                    
                                    if ($expirationDate < $currentDate) {
                                        echo '<span class="badge bg-danger">Expired</span>';
                                    } elseif ($interval->days <= 30) {
                                        echo '<span class="badge bg-warning">Expiring Soon</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Valid</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="alertMessage"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this container for alerts -->
    <div id="alert-container"></div>

    <script>
        // Check if export utilities are loaded
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof exportToPDF === 'function' && 
                typeof exportToExcel === 'function' && 
                typeof exportToCSV === 'function') {
                console.log("Export utilities successfully loaded!");
            } else {
                console.error("Export utilities not found! Make sure export-utils.js is loaded correctly.");
            }
        });
        
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Pagination configuration
            const rowsPerPage = 10;
            const paginationData = {
                'allInventoryTable': { currentPage: 1, totalPages: 1 },
                'medicineTable': { currentPage: 1, totalPages: 1 },
                'medicalSuppliesTable': { currentPage: 1, totalPages: 1 },
                'dentalSuppliesTable': { currentPage: 1, totalPages: 1 }
            };
            
            // Initialize pagination for all tables
            initPagination('allInventoryTable');
            initPagination('medicineTable');
            initPagination('medicalSuppliesTable');
            initPagination('dentalSuppliesTable');
            
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
                ul.appendChild(nextLi);
                
                nav.appendChild(ul);
                paginationContainer.appendChild(nav);
            }
            
            // Function to show a specific page
            function showPage(tableId, pageNumber) {
                const table = document.getElementById(tableId);
                if (!table) return;
                
                const rows = table.querySelectorAll('tbody tr');
                const totalPages = Math.ceil(rows.length / rowsPerPage);
                
                // Validate page number
                pageNumber = Math.max(1, Math.min(pageNumber, totalPages));
                
                // Update current page
                paginationData[tableId].currentPage = pageNumber;
                
                // Calculate start and end index
                const startIndex = (pageNumber - 1) * rowsPerPage;
                const endIndex = Math.min(startIndex + rowsPerPage - 1, rows.length - 1);
                
                // Hide all rows
                rows.forEach((row, index) => {
                    if (index >= startIndex && index <= endIndex) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Update pagination controls
                updatePaginationControls(tableId);
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
                let tableTitle = 'Inventory';
                const tableElement = document.getElementById(tableId);
                if (tableElement) {
                    const cardHeader = tableElement.closest('.card').querySelector('.card-header');
                    if (cardHeader) {
                        const titleElement = cardHeader.querySelector('h4');
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
                
                // Get all rows (not just visible ones)
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                
                // Get row data
                const data = rows.map(row => {
                    return Array.from(row.querySelectorAll('td')).map(td => {
                        // Always extract only plain text, ignore all HTML elements
                        return td.textContent.trim();
                    });
                });
                
                // Combine headers and data
                return [headers, ...data];
            }
            
            // Function to export table to PDF
            function exportTableToPDF(title, tableId, data) {
                try {
                    // Get summary data
                    const summary = {
                        'Total Items': data.length - 1,
                        'Category': title,
                        'Date': new Date().toLocaleDateString()
                    };
                    
                    // Set options
                    const options = {
                        orientation: 'landscape',
                        summary: summary,
                        category: title
                    };
                    
                    // Generate filename
                    const filename = `${title.replace(/\s+/g, '_')}_${formatDateForFilename(new Date())}`;
                    
                    // Use enhanced export utility
                    if (typeof exportToPDF === 'function') {
                        exportToPDF(title, filename, data, options);
                    } else {
                        showAlert('Export utility not available. Please try again later.', 'error');
                    }
                } catch (error) {
                    showAlert('Failed to export to PDF: ' + error.message, 'error');
                }
            }
            
            // Function to export table to Excel
            function exportTableToExcel(title, tableId, data) {
                try {
                    // Get summary data
                    const summary = {
                        'Total Items': data.length - 1,
                        'Category': title,
                        'Date': new Date().toLocaleDateString()
                    };
                    
                    // Set options
                    const options = {
                        sheetName: title.substring(0, 31), // Excel sheet names limited to 31 chars
                        summary: summary,
                        category: title
                    };
                    
                    // Generate filename
                    const filename = `${title.replace(/\s+/g, '_')}_${formatDateForFilename(new Date())}`;
                    
                    // Use enhanced export utility
                    if (typeof exportToExcel === 'function') {
                        exportToExcel(title, filename, data, options);
                    } else {
                        showAlert('Export utility not available. Please try again later.', 'error');
                    }
                } catch (error) {
                    showAlert('Failed to export to Excel: ' + error.message, 'error');
                }
            }
            
            // Function to export table to CSV
            function exportTableToCSV(title, tableId, data) {
                try {
                    // Get summary data
                    const summary = {
                        'Total Items': data.length - 1,
                        'Category': title,
                        'Date': new Date().toLocaleDateString()
                    };
                    
                    // Set options
                    const options = {
                        summary: summary,
                        category: title
                    };
                    
                    // Generate filename
                    const filename = `${title.replace(/\s+/g, '_')}_${formatDateForFilename(new Date())}`;
                    
                    // Use enhanced export utility
                    if (typeof exportToCSV === 'function') {
                        exportToCSV(title, filename, data, options);
                    } else {
                        showAlert('Export utility not available. Please try again later.', 'error');
                    }
                } catch (error) {
                    showAlert('Failed to export to CSV: ' + error.message, 'error');
                }
            }
            
            // Helper function to format date for filename
            function formatDateForFilename(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
            
            // Table search functionality
            function setupTableSearch(searchInputId, searchBtnId, tableId) {
                const searchInput = document.getElementById(searchInputId);
                const searchBtn = document.getElementById(searchBtnId);
                const table = document.getElementById(tableId);
                
                if (!searchInput || !searchBtn || !table) return;
                
                const searchTable = function() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const rows = table.querySelectorAll('tbody tr');
                    
                    // Reset display for all rows
                    rows.forEach(row => {
                        row.style.display = '';
                        row.classList.remove('search-match');
                    });
                    
                    // If search term is empty, just show first page
                    if (searchTerm === '') {
                        showPage(tableId, 1);
                        return;
                    }
                    
                    // Filter rows based on search term
                    let matchCount = 0;
                    rows.forEach(row => {
                        // Get only the medicine/item name (first column)
                        const nameCell = row.querySelector('td:first-child');
                        
                        if (!nameCell) return;
                        
                        const medicineName = nameCell.textContent.toLowerCase();
                        
                        if (medicineName.includes(searchTerm)) {
                            row.classList.add('search-match');
                            matchCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Update pagination for search results
                    paginationData[tableId].totalPages = Math.ceil(matchCount / rowsPerPage);
                    paginationData[tableId].currentPage = 1;
                    
                    // Show first page of search results
                    const matchedRows = table.querySelectorAll('tbody tr.search-match');
                    matchedRows.forEach((row, index) => {
                        if (index < rowsPerPage) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Update pagination controls
                    updatePaginationControls(tableId);
                };
                
                searchBtn.addEventListener('click', searchTable);
                
                searchInput.addEventListener('keyup', function(event) {
                    if (event.key === 'Enter') {
                        searchTable();
                    }
                });
            }
            
            // Setup search for each table
            setupTableSearch('allInventorySearchInput', 'allInventorySearchBtn', 'allInventoryTable');
            setupTableSearch('medicineSearchInput', 'medicineSearchBtn', 'medicineTable');
            setupTableSearch('medicalSuppliesSearchInput', 'medicalSuppliesSearchBtn', 'medicalSuppliesTable');
            setupTableSearch('dentalSuppliesSearchInput', 'dentalSuppliesSearchBtn', 'dentalSuppliesTable');
        });
        
        // Show alert modal
        function showAlert(title, message) {
            document.getElementById('alertTitle').textContent = title;
            document.getElementById('alertMessage').textContent = message;
            
            const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
            alertModal.show();
        }
        
        // Logout function
        function logout() {
            window.location.href = 'logout.php';
        }
    </script>
    <script>
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
                        <p>Choose a format to export the data:</p>
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
        let tableTitle = 'Inventory';
        const tableElement = document.getElementById(tableId);
        if (tableElement) {
            const cardHeader = tableElement.closest('.card').querySelector('.card-header');
            if (cardHeader) {
                const titleElement = cardHeader.querySelector('h4');
                if (titleElement) {
                    tableTitle = titleElement.textContent.trim();
                }
            }
        }

        // Get table data
        const tableData = getTableData(tableId);

        // Set up event listeners
        document.getElementById('exportPDF').addEventListener('click', function() {
            exportToPDF(tableTitle, tableId, tableData);
            bootstrap.Modal.getInstance(document.getElementById('exportOptionsModal')).hide();
        });

        document.getElementById('exportExcel').addEventListener('click', function() {
            exportToExcel(tableTitle, tableId, tableData);
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

        // Get all rows (not just visible ones)
        const rows = Array.from(table.querySelectorAll('tbody tr'));

        // Get row data
        const data = rows.map(row => {
            return Array.from(row.querySelectorAll('td')).map(td => {
                // Always extract only plain text, ignore all HTML elements
                return td.textContent.trim();
            });
        });

        // Combine headers and data
        return [headers, ...data];
    }
</script>
</body>
</html>
