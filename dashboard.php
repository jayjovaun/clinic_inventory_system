<?php
session_start();
require_once 'config/config.php';
require_once 'includes/helpers.php';
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

// Get all medicines with their categories
$medicines = $medicineModel->getAllWithCategory();

// Get dispensed medicines
$dispensedMedicines = $medicineModel->getDispensed();

// Calculate summary statistics
$totalMedicines = array_sum(array_column($medicines, 'quantity'));
$totalDispensed = array_sum(array_column($dispensedMedicines, 'quantity_dispensed'));

// Calculate expiring soon medicines
$now = new DateTime();
$soon = new DateTime();
$soon->modify('+30 days');
$expiringSoon = 0;

foreach ($medicines as $medicine) {
    $expDate = new DateTime($medicine['expiration_date']);
    if ($expDate > $now && $expDate <= $soon) {
        $expiringSoon += $medicine['quantity'];
    }
}

// Get categories for chart
$categories = [];
foreach ($medicines as $medicine) {
    $category = $medicine['category_name'];
    if (!isset($categories[$category])) {
        $categories[$category] = 0;
    }
    $categories[$category] += $medicine['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Clinic Inventory System</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/clinicz/assets/css/style.css">
    
    <!-- JavaScript Dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bcrypt.js/2.4.0/bcrypt.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="/clinicz/js/export-utils.js"></script>
    <script src="/clinicz/clinic.js" defer></script>
    <script src="https://unpkg.com/jszip@3.7.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/docx@8.3.0/build/index.umd.js"></script>
    
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
            min-height: 100vh;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: none;
            margin-bottom: var(--spacing-lg);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: white;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: var(--spacing-md);
            border-top-left-radius: var(--border-radius) !important;
            border-top-right-radius: var(--border-radius) !important;
            font-weight: 600;
        }
        
        .card-body {
            padding: var(--spacing-lg);
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            margin: var(--spacing-md) 0;
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
        }
        
        .account-dropdown {
            margin-left: var (--spacing-md);
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
        
        .notification-item {
            padding: 10px 15px;
            border-left: 4px solid transparent;
            margin-bottom: 5px;
            transition: all 0.2s;
            position: relative;
            padding-right: 30px;
            cursor: pointer;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .expired-notification {
            border-left-color: var(--expired-color);
            background-color: rgba(231, 74, 59, 0.1);
        }
        
        .near-expiry-notification {
            border-left-color: var(--expiring-color);
            background-color: rgba(246, 194, 62, 0.1);
        }
        
        .low-stock-notification {
            border-left-color: #3498db;
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .notification-content {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .notification-footer {
            font-size: 0.85rem;
            color: #666;
        }
        
        .notification-clear {
            position: absolute;
            top: 8px;
            right: 8px;
            background: transparent;
            border: none;
            color: #adb5bd;
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .notification-clear:hover {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            margin-bottom: 8px;
        }
        
        .clear-all-btn {
            background: transparent;
            border: none;
            color: #dc3545;
            font-size: 0.85rem;
            cursor: pointer;
            padding: 2px 5px;
        }
        
        .clear-all-btn:hover {
            text-decoration: underline;
        }
        
        .notification-empty {
            padding: 15px;
            text-align: center;
            color: #999;
            font-style: italic;
        }
        
        .export-btn-group {
            display: flex;
            gap: 3px;
            height: 32px;
            margin-left: 10px;
        }
        
        .export-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.3rem 0.6rem;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: none;
            height: 32px;
            min-width: 50px;
            line-height: 1;
            border-radius: 4px;
            border: none;
        }
        
        .export-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .export-btn i {
            margin-right: 0.25rem;
            font-size: 0.8rem;
        }
        
        .export-btn[onclick*="png"] {
            background-color: #0078D7;
            color: white;
        }
        
        .export-btn[onclick*="pdf"] {
            background-color: #D92B1C;
            color: white;
        }
        
        .export-btn[onclick*="excel"] {
            background-color: #ffffff;
            color: #333333;
            border: 1px solid #dddddd;
        }
        
        .download-all-btn {
            margin-bottom: var(--spacing-lg);
        }
        
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            vertical-align: middle;
            border-color: #dee2e6;
        }
        
        .table th {
            font-weight: 600;
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 0.25rem;
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
            
            .sidebar a {
                justify-content: center;
                padding: var(--spacing-md) 0;
            }
            
            .sidebar a i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
            
            .sidebar-logo {
                height: 40px;
                margin-bottom: var(--spacing-md);
            }
        }
        
        @media (max-width: 768px) {
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
        
        /* Notification Styles */
        #notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(25%, -25%);
            font-size: 0.7rem;
        }
        
        .notification-dropdown {
            width: 320px !important;
            max-height: 400px !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
        }
        
        #notification-container {
            max-height: 400px;
            overflow-y: auto;
            padding: 0;
            margin: 0;
            width: 100%;
        }
        
        .notification-title-header {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            text-align: center;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            position: relative;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-title {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .notification-footer {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .notification-clear {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #adb5bd;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .notification-clear:hover {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .notification-empty {
            padding: 20px;
            text-align: center;
            color: #6c757d;
        }
        
        .expired-notification {
            border-left: 3px solid var(--expired-color);
        }
        
        .near-expiry-notification {
            border-left: 3px solid var(--expiring-color);
        }
        
        .low-stock-notification {
            border-left: 3px solid var(--accent-color);
        }
        
        .clear-all-btn {
            background: none;
            border: none;
            color: var(--accent-color);
            font-size: 0.85rem;
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 3px;
        }
        
        .clear-all-btn:hover {
            background-color: rgba(25, 135, 84, 0.1);
        }
        
        /* Export Button Style */
        .btn-outline-primary.btn-sm {
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 4px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: normal;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: #0d6efd;
            border-color: #0d6efd;
            background-color: transparent;
            transition: all 0.2s ease-in-out;
        }
        
        .btn-outline-primary.btn-sm:hover {
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .btn-outline-primary.btn-sm i {
            font-size: 14px;
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
        <a href="dashboard.php" class="active">
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
        <a href="inventory.php">
            <i class="fas fa-boxes"></i>
            <span>Inventory</span>
        </a>
        <a href="logout.php" class="text-danger">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <div class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1>Inventory Analytics Dashboard</h1>
                <p class="text-muted">Comprehensive overview of medicine inventory</p>
            </div>
            <div class="dashboard-actions">
                <!-- Notification Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0" aria-labelledby="notificationDropdown">
                        <div id="notification-container">
                            <!-- Notifications will be inserted here by JavaScript -->
                        </div>
                    </div>
                </div>
                
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
        <div class="row mb-4 g-4">
            <div class="col-lg-4 col-md-6">
                <div class="summary-card bg-primary">
                    <div class="summary-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div class="summary-info">
                        <h3 id="totalMedicines"><?php echo $totalMedicines; ?></h3>
                        <p class="mb-0">Total Medicines in Stock</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="summary-card bg-success">
                    <div class="summary-icon">
                        <i class="fas fa-hand-holding-medical"></i>
                    </div>
                    <div class="summary-info">
                        <h3 id="totalDispensed"><?php echo $totalDispensed; ?></h3>
                        <p class="mb-0">Medicines Dispensed</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="summary-card bg-warning">
                    <div class="summary-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="summary-info">
                        <h3 id="expiringSoon"><?php echo $expiringSoon; ?></h3>
                        <p class="mb-0">Expiring Soon (≤30 days)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-4">
            <!-- Medicine by Category Pie Chart -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold">Medicine Distribution by Category</h6>
                        <div class="export-btn-group">
                            <button class="export-btn" onclick="exportChart('categoryPieChart', 'png')" title="Download as PNG">
                                <i class="fas fa-file-image"></i> PNG
                            </button>
                            <button class="export-btn" onclick="exportChart('categoryPieChart', 'pdf')" title="Download as PDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button class="export-btn" onclick="exportChart('categoryPieChart', 'excel')" title="Download as Excel">
                                Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medicine by Category Bar Chart -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold">Medicine Quantity by Category</h6>
                        <div class="export-btn-group">
                            <button class="export-btn" onclick="exportChart('categoryChart', 'png')" title="Download as PNG">
                                <i class="fas fa-file-image"></i> PNG
                            </button>
                            <button class="export-btn" onclick="exportChart('categoryChart', 'pdf')" title="Download as PDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button class="export-btn" onclick="exportChart('categoryChart', 'excel')" title="Download as Excel">
                                Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Stock Status -->
            <div class="col-lg-12 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold">Medicine Stock Status</h6>
                        <div class="export-btn-group">
                            <button class="export-btn" onclick="exportChart('stockChart', 'png')" title="Download as PNG">
                                <i class="fas fa-file-image"></i> PNG
                            </button>
                            <button class="export-btn" onclick="exportChart('stockChart', 'pdf')" title="Download as PDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button class="export-btn" onclick="exportChart('stockChart', 'excel')" title="Download as Excel">
                                Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="stockChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Medicine Dispensing Trends -->
        <div class="row mb-4">
            <div class="col-lg-12 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold">Medicine Dispensing Trends</h6>
                        <div class="export-btn-group">
                            <button class="export-btn" onclick="exportChart('dispensingTrendsChart', 'png')" title="Download as PNG">
                                <i class="fas fa-file-image"></i> PNG
                            </button>
                            <button class="export-btn" onclick="exportChart('dispensingTrendsChart', 'pdf')" title="Download as PDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button class="export-btn" onclick="exportChart('dispensingTrendsChart', 'excel')" title="Download as Excel">
                                Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dispensingTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Download All Button -->
        <div class="d-flex justify-content-end mb-4">
            <div class="dropdown">
                <button class="btn btn-success dropdown-toggle" type="button" id="downloadAllBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-download me-2"></i>Download All Charts
                </button>
                <ul class="dropdown-menu" aria-labelledby="downloadAllBtn">
                    <li><a class="dropdown-item" href="#" onclick="downloadAllCharts('png')"><i class="fas fa-file-image me-2"></i>PNG Format</a></li>
                    <li><a class="dropdown-item" href="#" onclick="downloadAllCharts('jpg')"><i class="fas fa-file-image me-2"></i>JPG Format</a></li>
                    <li><a class="dropdown-item" href="#" onclick="downloadAllCharts('pdf')"><i class="fas fa-file-pdf me-2"></i>PDF Format</a></li>
                    <li><a class="dropdown-item" href="#" onclick="downloadAllCharts('excel')"><i class="fas fa-file-excel me-2"></i>Excel Format</a></li>
                    <li><a class="dropdown-item" href="#" onclick="downloadAllCharts('csv')"><i class="fas fa-file-csv me-2"></i>CSV Format</a></li>
                </ul>
            </div>
        </div>

        <!-- Expiring Medicines Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold">
                            <i class="fas fa-clock me-2"></i>Expiring Soon (Next 30 Days)
                        </h6>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="exportListBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download me-2"></i>Export List
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportListBtn">
                                <li><a class="dropdown-item" href="#" onclick="exportExpiringList('pdf')"><i class="fas fa-file-pdf me-2"></i>PDF Format</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportExpiringList('excel')"><i class="fas fa-file-excel me-2"></i>Excel Format</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportExpiringList('csv')"><i class="fas fa-file-csv me-2"></i>CSV Format</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="expiringTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Brand</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th>Expiration Date</th>
                                        <th>Days Remaining</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pagination configuration
        const rowsPerPage = 10;
        const paginationData = {
            'expiringTable': { currentPage: 1, totalPages: 1 }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize notifications when the page loads
            updateNotifications();
            
            // Set up interval to check for new notifications every 5 minutes
            setInterval(updateNotifications, 5 * 60 * 1000);
            
            // Initialize charts
            initializeCharts();
            
            // Fetch medicine data for the expiring medicines table
            fetchMedicineData();
        });
        
        // Initialize charts function
        function initializeCharts() {
            const categoryPieConfig = {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($categories)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($categories)); ?>,
                        backgroundColor: [
                            'rgba(78, 115, 223, 0.8)',
                            'rgba(28, 200, 138, 0.8)',
                            'rgba(54, 185, 204, 0.8)',
                            'rgba(246, 194, 62, 0.8)',
                            'rgba(231, 74, 59, 0.8)',
                            'rgba(133, 135, 150, 0.8)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            };
            charts.categoryPieChart = renderChart('categoryPieChart', categoryPieConfig);
            
            const categoryChartConfig = {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($categories)); ?>,
                    datasets: [{
                        label: 'Quantity',
                        data: <?php echo json_encode(array_values($categories)); ?>,
                        backgroundColor: 'rgba(78, 115, 223, 0.8)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
            charts.categoryChart = renderChart('categoryChart', categoryChartConfig);
            
            const stockData = {
                labels: ['Total Stock', 'Dispensed', 'Expiring Soon'],
                datasets: [{
                    label: 'Quantity',
                    data: [<?php echo $totalMedicines; ?>, <?php echo $totalDispensed; ?>, <?php echo $expiringSoon; ?>],
                    backgroundColor: [
                        'rgba(78, 115, 223, 0.8)',
                        'rgba(28, 200, 138, 0.8)',
                        'rgba(246, 194, 62, 0.8)'
                    ],
                    borderColor: [
                        'rgba(78, 115, 223, 1)',
                        'rgba(28, 200, 138, 1)',
                        'rgba(246, 194, 62, 1)'
                    ],
                    borderWidth: 1
                }]
            };
            const stockChartConfig = {
                type: 'bar',
                data: stockData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
            charts.stockChart = renderChart('stockChart', stockChartConfig);
            
            // Initialize dispensing trends chart with loading state
            const dispensingTrendsInitialConfig = {
                type: 'line',
                data: {
                    labels: ['Loading...'],
                    datasets: [{
                        label: 'Dispensed Medicines',
                        data: [0],
                        backgroundColor: 'rgba(28, 200, 138, 0.2)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity Dispensed'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Dispensed: ${context.parsed.y} units`;
                                }
                            }
                        }
                    }
                }
            };
            charts.dispensingTrendsChart = renderChart('dispensingTrendsChart', dispensingTrendsInitialConfig);
            
            // Fetch dispensing trends data
            fetchDispensingTrends();
        }
        
        const charts = {};
        
        // Add debugging logs to fetchMedicineData function
        function fetchMedicineData() {
            // Show loading state in the table
            const tbody = document.getElementById('expiringTable').getElementsByTagName('tbody')[0];
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

            // Fetch inventory data from the server
            fetch('api/direct_inventory_check.php')
                .then(response => {
                    console.log('API Response Status:', response.status); // Debugging log
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('API Response Data:', data); // Debugging log
                    // Process the data to identify expiring medicines
                    if (data && data.success && Array.isArray(data.medicines)) {
                        loadExpiringMedicinesTable(data.medicines);
                    } else {
                        throw new Error('Invalid data format received from server');
                    }
                })
                .catch(error => {
                    console.error('Error fetching medicine data:', error);
                    // Show error message in the table
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading data. Please try refreshing the page.</td></tr>';
                });
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`;
        }
        
        function renderChart(canvasId, config) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, config);
        }
        
        function loadExpiringMedicinesTable(inventory) {
            const now = new Date();
            const soon = new Date();
            soon.setDate(soon.getDate() + 30);

            const expiringSoon = inventory.filter(item => {
                const expDate = new Date(item.expiration_date);
                return expDate >= now && expDate <= soon;
            }).sort((a, b) => new Date(a.expiration_date) - new Date(b.expiration_date));

            // Update pagination data
            paginationData['expiringTable'].totalPages = Math.ceil(expiringSoon.length / rowsPerPage);
            
            // Create pagination controls if they don't exist
            createPaginationControls('expiringTable', expiringSoon);
            
            // Show first page
            showPage('expiringTable', 1, expiringSoon, now);
        }
        
        // Function to create pagination controls
        function createPaginationControls(tableId, data) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            // Remove existing pagination if any
            const existingPagination = document.getElementById(tableId + 'Pagination');
            if (existingPagination) {
                existingPagination.remove();
            }
            
            // Create container for pagination
            const paginationContainer = document.createElement('div');
            paginationContainer.className = 'pagination-container mt-3 d-flex justify-content-center';
            paginationContainer.id = tableId + 'Pagination';
            
            // Add pagination controls to container
            updatePaginationControls(tableId, data);
            
            // Insert pagination container after table
            table.parentNode.insertBefore(paginationContainer, table.nextSibling);
        }
        
        // Function to update pagination controls
        function updatePaginationControls(tableId, data) {
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
                    const now = new Date();
                    showPage(tableId, currentPage - 1, data, now);
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
                    const now = new Date();
                    showPage(tableId, 1, data, now);
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
                    const now = new Date();
                    showPage(tableId, i, data, now);
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
                    const now = new Date();
                    showPage(tableId, totalPages, data, now);
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
                    const now = new Date();
                    showPage(tableId, currentPage + 1, data, now);
                }
            });
            
            nextLi.appendChild(nextLink);
            ul.appendChild(nextLi);
            
            nav.appendChild(ul);
            paginationContainer.appendChild(nav);
        }
        
        // Function to show a specific page
        function showPage(tableId, pageNumber, data, now) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            
            // Clear the table body
            tbody.innerHTML = '';
            
            // Validate page number
            const totalPages = paginationData[tableId].totalPages;
            pageNumber = Math.max(1, Math.min(pageNumber, totalPages));
            
            // Update current page
            paginationData[tableId].currentPage = pageNumber;
            
            // If no data, show empty message
            if (data.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="6" class="text-center">No medicines expiring soon</td>';
                tbody.appendChild(row);
                return;
            }
            
            // Calculate start and end index
            const startIndex = (pageNumber - 1) * rowsPerPage;
            const endIndex = Math.min(startIndex + rowsPerPage, data.length);
            
            // Add rows for the current page
            for (let i = startIndex; i < endIndex; i++) {
                const item = data[i];
                const expDate = new Date(item.expiration_date);
                const daysUntil = Math.ceil((expDate - now) / (1000 * 60 * 60 * 24));

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td>${item.brand}</td>
                    <td>${item.category_name || 'N/A'}</td>
                    <td>${item.quantity}</td>
                    <td>${formatDate(item.expiration_date)}</td>
                    <td>
                        <span class="badge ${daysUntil <= 7 ? 'bg-danger' : 'bg-warning'}">
                            ${daysUntil} day${daysUntil === 1 || daysUntil === -1 ? '' : 's'}
                        </span>
                    </td>
                `;
                tbody.appendChild(row);
            }
            
            // Update pagination controls
            updatePaginationControls(tableId, data);
        }
        
        function downloadAllCharts(format) {
            // Show loading indicator
            showAlert('Exporting Charts', `Preparing ${format.toUpperCase()} export. Please wait...`);
            
            // Instead of processing in the browser, we'll use a simple approach
            // that triggers individual downloads for each chart
            const chartIds = ['categoryPieChart', 'categoryChart', 'stockChart', 'dispensingTrendsChart'];
            
            // Queue downloads with a delay between each to prevent browser freezing
            queueDownloads(chartIds, format, 0);
        }
        
        function queueDownloads(chartIds, format, index) {
            if (index >= chartIds.length) {
                // All downloads queued
                setTimeout(() => {
                    showAlert('Export Complete', `All charts have been exported in ${format.toUpperCase()} format.`);
                }, 500);
                return;
            }
            
            // Download current chart
            const chartId = chartIds[index];
            exportSingleChart(chartId, format);
            
            // Queue next download after a delay
            setTimeout(() => {
                queueDownloads(chartIds, format, index + 1);
            }, 1000); // 1 second delay between downloads
        }
        
        function exportSingleChart(chartId, format) {
            const chart = charts[chartId];
            if (!chart) {
                console.error(`Chart with ID ${chartId} not found`);
                return;
            }
            
            const title = getChartTitle(chartId);
            const filename = `${title.replace(/\s+/g, '_')}_${formatDateForFilename(new Date())}`;
            
            try {
                switch (format) {
                    case 'png':
                        downloadAsImage(chart, 'image/png', `${filename}.png`);
                        break;
                        
                    case 'jpg':
                        downloadAsImage(chart, 'image/jpeg', `${filename}.jpg`, 0.9);
                        break;
                        
                    case 'pdf':
                        downloadAsPDF(chart, title, `${filename}.pdf`);
                        break;
                        
                    case 'excel':
                        downloadAsExcel(chart, title, `${filename}.xlsx`);
                        break;
                        
                    default:
                        console.error(`Unsupported format: ${format}`);
                        showAlert('Export Error', `Unsupported format: ${format}`);
                }
            } catch (error) {
                console.error(`Error exporting chart ${chartId}:`, error);
                showAlert('Export Error', `Failed to export chart "${title}": ${error.message}`);
            }
        }
        
        // Function to export the expiring medicines table
        function exportExpiringList(format) {
            try {
                const table = document.getElementById('expiringTable');
                if (!table) {
                    throw new Error('Expiring medicines table not found');
                }
                
                // Get table data
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                
                // Skip if no data
                if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td').colSpan === 6)) {
                    showAlert('Export Error', 'No data to export');
                    return;
                }
                
                const title = 'Expiring Medicines List';
                const filename = `Expiring_Medicines_${formatDateForFilename(new Date())}`;
                
                // Get table headers
                const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
                
                // Get table data
                const data = rows.map(row => {
                    return Array.from(row.querySelectorAll('td')).map(td => {
                        // If the cell contains a badge, get the text content
                        const badge = td.querySelector('.badge');
                        return badge ? badge.textContent.trim() : td.textContent.trim();
                    });
                });
                
                // Combine headers and data
                const tableData = [headers, ...data];
                
                switch (format) {
                    case 'pdf':
                        exportExpiringListAsPDF(title, filename, tableData);
                        break;
                        
                    case 'excel':
                        exportExpiringListAsExcel(title, filename, tableData);
                        break;
                        
                    case 'csv':
                        exportExpiringListAsCSV(title, filename, tableData);
                        break;
                        
                    default:
                        console.error(`Unsupported format: ${format}`);
                        showAlert('Export Error', `Unsupported format: ${format}`);
                }
            } catch (error) {
                console.error('Error exporting expiring list:', error);
                showAlert('Export Error', `Failed to export expiring medicines list: ${error.message}`);
            }
        }
        
        function exportExpiringListAsPDF(title, filename, data) {
            try {
                // Get summary data
                const summary = {
                    'Total Items': data.length - 1,
                    'Report Type': 'Expiring Medicines',
                    'Date Range': 'Next 30 days'
                };
                
                // Set options
                const options = {
                    orientation: 'portrait',
                    summary: summary,
                    category: 'Expiring Medicines'
                };
                
                // Use enhanced export utility
                exportToPDF(title, filename, data, options);
            } catch (error) {
                console.error('Error generating PDF:', error);
                throw error;
            }
        }
        
        function exportExpiringListAsExcel(title, filename, data) {
            try {
                // Get summary data
                const summary = {
                    'Total Items': data.length - 1,
                    'Report Type': 'Expiring Medicines',
                    'Date Range': 'Next 30 days'
                };
                
                // Set options
                const options = {
                    sheetName: 'Expiring Medicines',
                    summary: summary,
                    includeCharts: true,
                    category: 'Expiring Medicines'
                };
                
                // Use enhanced export utility
                exportToExcel(title, filename, data, options);
            } catch (error) {
                console.error('Error generating Excel:', error);
                throw error;
            }
        }
        
        function exportExpiringListAsCSV(title, filename, data) {
            try {
                // Get summary data
                const summary = {
                    'Total Items': data.length - 1,
                    'Report Type': 'Expiring Medicines',
                    'Date Range': 'Next 30 days'
                };
                
                // Set options
                const options = {
                    summary: summary,
                    category: 'Expiring Medicines'
                };
                
                // Use enhanced export utility
                exportToCSV(title, filename, data, options);
            } catch (error) {
                console.error('Error generating CSV:', error);
                throw error;
            }
        }
        
        function downloadAsPDF(chart, title, filename) {
            try {
                // Get chart as image data
                const imgData = chart.canvas.toDataURL('image/png');
                
                // Create PDF using jsPDF
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF();
                
                // Set up document properties
                pdf.setProperties({
                    title: title,
                    subject: 'Chart Export',
                    author: 'Clinic Inventory System',
                    keywords: 'chart, inventory, clinic',
                    creator: 'Clinic Inventory System'
                });
                
                // Add clinic info
                pdf.setFontSize(16);
                pdf.setTextColor(41, 128, 185); // Blue
                pdf.text('Clinic Inventory System', 14, 15);
                
                // Add title
                pdf.setFontSize(14);
                pdf.setTextColor(52, 73, 94); // Dark blue
                pdf.text(title, 14, 25);
                
                // Add date
                pdf.setFontSize(10);
                pdf.setTextColor(100, 100, 100);
                pdf.text(`Generated: ${new Date().toLocaleString()}`, 14, 32);
                
                // Calculate dimensions to maintain aspect ratio
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const chartWidth = pageWidth - 28; // Margins
                const chartHeight = (chart.canvas.height * chartWidth) / chart.canvas.width;
                
                // Add chart image
                pdf.addImage(imgData, 'PNG', 14, 40, chartWidth, chartHeight);
                
                // Add footer
                pdf.setFontSize(8);
                pdf.setTextColor(100, 100, 100);
                pdf.text('Clinic Inventory System - Chart Export', 14, pageHeight - 10);
                pdf.text(`Page 1 of 1`, pageWidth / 2, pageHeight - 10, { align: 'center' });
                pdf.text('CONFIDENTIAL', pageWidth - 14, pageHeight - 10, { align: 'right' });
                
                // Save PDF
                pdf.save(filename);
            } catch (error) {
                console.error('Error generating PDF:', error);
                throw error;
            }
        }
        
        function downloadAsExcel(chart, title, filename) {
            try {
                // Get chart data
                const chartData = getChartData(chart);
                
                // Create workbook
                const wb = XLSX.utils.book_new();
                
                // Set workbook properties
                wb.Props = {
                    Title: title,
                    Subject: 'Chart Export',
                    Author: 'Clinic Inventory System',
                    CreatedDate: new Date()
                };
                
                // Create worksheet data with title and date
                const wsData = [
                    ['Clinic Inventory System'],
                    [title],
                    [`Generated: ${new Date().toLocaleString()}`],
                    [''],
                    ['Category', 'Value']
                ];
                
                // Add chart data
                chartData.labels.forEach((label, index) => {
                    wsData.push([label, chartData.values[index]]);
                });
                
                // Create worksheet
                const ws = XLSX.utils.aoa_to_sheet(wsData);
                
                // Set column widths
                ws['!cols'] = [
                    { wch: 30 }, // Category column
                    { wch: 15 }  // Value column
                ];
                
                // Add worksheet to workbook
                XLSX.utils.book_append_sheet(wb, ws, 'Chart Data');
                
                // Generate Excel file and trigger download
                XLSX.writeFile(wb, filename);
            } catch (error) {
                console.error('Error generating Excel:', error);
                throw error;
            }
        }
        
        function downloadAsCSV(chart, title, filename) {
            try {
                // Get chart data
                const chartData = getChartData(chart);
                
                // Create CSV content
                let csvContent = 'Clinic Inventory System\n';
                csvContent += `${title}\n`;
                csvContent += `Generated: ${new Date().toLocaleString()}\n\n`;
                csvContent += 'Category,Value\n';
                
                // Add chart data
                chartData.labels.forEach((label, index) => {
                    csvContent += `"${label}","${chartData.values[index]}"\n`;
                });
                
                // Create blob and download link
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                
                // Create temporary link and trigger download
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                
                // Clean up
                setTimeout(() => {
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                }, 100);
            } catch (error) {
                console.error('Error generating CSV:', error);
                throw error;
            }
        }
        
        function getChartData(chart) {
            // Extract labels and data from chart
            const labels = chart.data.labels;
            const values = [];
            
            // Get values from datasets
            chart.data.datasets.forEach(dataset => {
                // If multiple datasets, we'll use the first one for simplicity
                if (values.length === 0) {
                    values.push(...dataset.data);
                }
            });
            
            return { labels, values };
        }
        
        function exportToPDF(title, filename, data, options) {
            // Create PDF using jsPDF
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF();
            
            // Set up document properties
            pdf.setProperties({
                title: title,
                subject: 'Report Export',
                author: 'Clinic Inventory System',
                keywords: 'report, inventory, clinic',
                creator: 'Clinic Inventory System'
            });
            
            // Add clinic info
            pdf.setFontSize(16);
            pdf.setTextColor(41, 128, 185); // Blue
            pdf.text('Clinic Inventory System', 14, 15);
            
            // Add title
            pdf.setFontSize(14);
            pdf.setTextColor(52, 73, 94); // Dark blue
            pdf.text(title, 14, 25);
            
            // Add date
            pdf.setFontSize(10);
            pdf.setTextColor(100, 100, 100);
            pdf.text(`Generated: ${new Date().toLocaleString()}`, 14, 32);
            
            // Add summary
            if (options.summary) {
                pdf.setFontSize(10);
                pdf.setTextColor(100, 100, 100);
                Object.keys(options.summary).forEach(key => {
                    pdf.text(`${key}: ${options.summary[key]}`, 14, 40 + (10 * Object.keys(options.summary).indexOf(key)));
                });
            }
            
            // Add table
            pdf.autoTable({
                head: [data[0]],
                body: data.slice(1),
                startY: 60,
                styles: { fontSize: 8 },
                headStyles: { fillColor: [41, 128, 185], textColor: 255 },
                alternateRowStyles: { fillColor: [240, 240, 240] }
            });
            
            // Add footer
            pdf.setFontSize(8);
            pdf.setTextColor(100, 100, 100);
            pdf.text('Clinic Inventory System - Report Export', 14, pdf.internal.pageSize.getHeight() - 10);
            pdf.text(`Page 1 of 1`, pdf.internal.pageSize.getWidth() / 2, pdf.internal.pageSize.getHeight() - 10, { align: 'center' });
            pdf.text('CONFIDENTIAL', pdf.internal.pageSize.getWidth() - 14, pdf.internal.pageSize.getHeight() - 10, { align: 'right' });
            
            // Save PDF
            pdf.save(filename);
        }
        
        function exportToExcel(title, filename, data, options) {
            // Create workbook
            const wb = XLSX.utils.book_new();
            
            // Set workbook properties
            wb.Props = {
                Title: title,
                Subject: 'Report Export',
                Author: 'Clinic Inventory System',
                CreatedDate: new Date()
            };
            
            // Create worksheet data with title and date
            const wsData = [
                ['Clinic Inventory System'],
                [title],
                [`Generated: ${new Date().toLocaleString()}`],
                [''],
                ['Category', 'Value']
            ];
            
            // Add summary
            if (options.summary) {
                Object.keys(options.summary).forEach(key => {
                    wsData.push([key, options.summary[key]]);
                });
            }
            
            // Add table data
            data.forEach(row => {
                wsData.push(row);
            });
            
            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            
            // Set column widths
            ws['!cols'] = [
                { wch: 30 }, // Category column
                { wch: 15 }  // Value column
            ];
            
            // Add worksheet to workbook
            XLSX.utils.book_append_sheet(wb, ws, 'Report Data');
            
            // Generate Excel file and trigger download
            XLSX.writeFile(wb, filename);
        }
        
        function exportToCSV(title, filename, data, options) {
            // Create CSV content
            let csvContent = 'Clinic Inventory System\n';
            csvContent += `${title}\n`;
            csvContent += `Generated: ${new Date().toLocaleString()}\n\n`;
            
            // Add summary
            if (options.summary) {
                Object.keys(options.summary).forEach(key => {
                    csvContent += `${key},${options.summary[key]}\n`;
                });
            }
            
            // Add table data
            data.forEach(row => {
                csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
            });
            
            // Create blob and download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            // Create temporary link and trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            
            // Clean up
            setTimeout(() => {
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }, 100);
        }
        
        function formatDateForFilename(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        function getChartTitle(chartId) {
            switch (chartId) {
                case 'categoryPieChart':
                    return 'Medicine Distribution by Category';
                case 'categoryChart':
                    return 'Medicine Quantity by Category';
                case 'stockChart':
                    return 'Medicine Stock Status';
                case 'dispensingTrendsChart':
                    return 'Medicine Dispensing Trends';
                default:
                    return 'Chart Export';
            }
        }
        
        function exportChart(chartId, format) {
            const chart = charts[chartId];
            if (!chart) {
                console.error(`Chart with ID ${chartId} not found`);
                return;
            }
            
            const title = getChartTitle(chartId);
            const filename = `${title.replace(/\s+/g, '_')}_${formatDateForFilename(new Date())}`;
            
            try {
                switch (format) {
                    case 'png':
                        downloadAsImage(chart, 'image/png', `${filename}.png`);
                        break;
                        
                    case 'pdf':
                        downloadAsPDF(chart, title, `${filename}.pdf`);
                        break;
                        
                    case 'excel':
                        downloadAsExcel(chart, title, `${filename}.xlsx`);
                        break;
                        
                    default:
                        console.error(`Unsupported format: ${format}`);
                        showAlert('Export Error', `Unsupported format: ${format}`);
                }
            } catch (error) {
                console.error(`Error exporting chart ${chartId}:`, error);
                showAlert('Export Error', `Failed to export chart "${title}": ${error.message}`);
            }
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
                            <p>Choose a format to export the data:</p>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" id="exportPDF">
                                    <i class="fas fa-file-pdf me-2"></i>Export as PDF
                                </button>
                                <button class="btn btn-outline-primary" id="exportExcel">
                                    <i class="fas fa-file-excel me-2"></i>Export as Excel
                                </button>
                                <button class="btn btn-outline-primary" id="exportCSV">
                                    <i class="fas fa-file-csv me-2"></i>Export as CSV
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
            let tableTitle = 'Dashboard Data';
            const tableElement = document.getElementById(tableId);
            if (tableElement) {
                const cardHeader = tableElement.closest('.card').querySelector('.card-header');
                if (cardHeader) {
                    const titleElement = cardHeader.querySelector('h6');
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
            
            document.getElementById('exportCSV').addEventListener('click', function() {
                exportTableToCSV(tableTitle, tableId, tableData);
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
            exportToPDF(title, filename, data, options);
        }
        
        // Function to export table to Excel
        function exportTableToExcel(title, tableId, data) {
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
                includeCharts: false,
                category: title
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
            exportToCSV(title, filename, data, options);
        }
        
        function fetchDispensingTrends() {
            // Show loading state in the chart
            const chart = charts.dispensingTrendsChart;
            chart.data.labels = ['Loading...'];
            chart.data.datasets[0].data = [0];
            chart.update();
            
            // Fetch dispensing trends data from the server
            fetch('api/get_dispensing_trends.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dispensing trends data:', data);
                    
                    // Process the data
                    if (data && data.success && data.monthly) {
                        updateDispensingTrendsChart(data.monthly);
                    } else {
                        throw new Error('Invalid data format received from server');
                    }
                })
                .catch(error => {
                    console.error('Error fetching dispensing trends:', error);
                    
                    // Show error in chart
                    chart.data.labels = ['Error loading data'];
                    chart.data.datasets[0].data = [0];
                    chart.update();
                });
        }
        
        function updateDispensingTrendsChart(monthlyData) {
            // Update chart data
            const chart = charts.dispensingTrendsChart;
            chart.data.labels = monthlyData.labels;
            chart.data.datasets[0].data = monthlyData.data;
            
            // Update chart
            chart.update();
        }
        
        function showAlert(title, message) {
            const alertModal = new bootstrap.Modal(document.getElementById('customAlertModal'));
            document.getElementById('alertTitle').textContent = title;
            document.getElementById('alertMessage').textContent = message;
            alertModal.show();
        }

        function updateNotifications() {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                badge.textContent = "...";
                badge.style.display = 'inline-block';
            }
            fetch('api/direct_inventory_check.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const now = new Date();
                        const expired = [];
                        const expiringSoon = [];
                        const lowStock = [];
                        // Get dismissed IDs from localStorage
                        const dismissed = JSON.parse(localStorage.getItem('dismissedNotifications') || '[]');
                        data.medicines.forEach(item => {
                            if (dismissed.includes(item.id)) return;
                            let expDate;
                            if (item.expiration_date.includes('/')) {
                                const parts = item.expiration_date.split('/');
                                expDate = new Date(parts[2], parts[0] - 1, parts[1]);
                            } else {
                                expDate = new Date(item.expiration_date);
                            }
                            if (isNaN(expDate.getTime())) return;
                            const daysUntil = Math.ceil((expDate - now) / (1000 * 3600 * 24));
                            item.days_remaining = daysUntil;
                            if (expDate < now) expired.push(item);
                            if (expDate >= now && daysUntil <= 30) expiringSoon.push(item);
                            if (item.quantity <= 10) lowStock.push(item);
                        });
                        updateNotificationUI(expired, expiringSoon, lowStock);
                    } else {
                        if (badge) {
                            badge.textContent = '!';
                            badge.style.display = 'inline-block';
                        }
                    }
                })
                .catch(() => {
                    if (badge) {
                        badge.textContent = '!';
                        badge.style.display = 'inline-block';
                    }
                });
        }
        function updateNotificationUI(expired, expiringSoon, lowStock) {
            const badge = document.getElementById('notification-badge');
            const container = document.getElementById('notification-container');
            // Use a Set to avoid double-counting in badge
            const uniqueIds = new Set([
                ...expired.map(i => i.id),
                ...expiringSoon.map(i => i.id),
                ...lowStock.map(i => i.id)
            ]);
            const totalCount = uniqueIds.size;
            if (badge) {
                badge.textContent = totalCount;
                badge.style.display = totalCount > 0 ? 'inline-block' : 'none';
            }
            if (!container) return;
            container.innerHTML = '';
            if (totalCount === 0) {
                container.innerHTML = `<div class="notification-empty">No notifications</div>`;
                return;
            }
            // Modern header
            container.innerHTML += `
                <div class="notification-title-header">
                    <i class="fas fa-bell me-2"></i>Inventory Alerts
                </div>
                <div class="notification-header">
                    <span><strong>${totalCount}</strong> Notification${totalCount !== 1 ? 's' : ''}</span>
                    <button class="clear-all-btn" onclick="clearAllNotifications(event)">Clear All</button>
                </div>
            `;
            let rendered = 0;
            // Expired
            expired.forEach(item => {
                rendered++;
                container.innerHTML += `
                    <div class="notification-item expired-notification" data-id="${item.id}">
                        <button class="notification-clear" onclick="clearNotification(event, ${item.id})">×</button>
                        <div class="notification-title">
                            <i class="fas fa-skull-crossbones text-danger me-1"></i>
                            <strong>${item.name}</strong> <span class="text-muted">(${item.brand || 'Generic'})</span>
                        </div>
                        <div class="notification-content">
                            <span class="badge bg-danger me-2">EXPIRED</span>
                            <span>Expired on <b>${item.expiration_date}</b></span>
                        </div>
                        <div class="notification-footer">
                            <i class="fas fa-box"></i> Qty: <b>${item.quantity}</b>
                        </div>
                    </div>
                `;
            });
            // Expiring soon
            expiringSoon.forEach(item => {
                rendered++;
                container.innerHTML += `
                    <div class="notification-item near-expiry-notification" data-id="${item.id}">
                        <button class="notification-clear" onclick="clearNotification(event, ${item.id})">×</button>
                        <div class="notification-title">
                            <i class="fas fa-hourglass-half text-warning me-1"></i>
                            <strong>${item.name}</strong> <span class="text-muted">(${item.brand || 'Generic'})</span>
                        </div>
                        <div class="notification-content">
                            <span class="badge bg-warning text-dark me-2">EXPIRING SOON</span>
                            <span>In <b>${item.days_remaining}</b> day${item.days_remaining === 1 || item.days_remaining === -1 ? '' : 's'} (${item.expiration_date})</span>
                        </div>
                        <div class="notification-footer">
                            <i class="fas fa-box"></i> Qty: <b>${item.quantity}</b>
                        </div>
                    </div>
                `;
            });
            // Low stock
            lowStock.forEach(item => {
                rendered++;
                container.innerHTML += `
                    <div class="notification-item low-stock-notification" data-id="${item.id}">
                        <button class="notification-clear" onclick="clearNotification(event, ${item.id})">×</button>
                        <div class="notification-title">
                            <i class="fas fa-triangle-exclamation text-info me-1"></i>
                            <strong>${item.name}</strong> <span class="text-muted">(${item.brand || 'Generic'})</span>
                        </div>
                        <div class="notification-content">
                            <span class="badge bg-info me-2">LOW STOCK</span>
                            <span>Current quantity: <b>${item.quantity}</b></span>
                        </div>
                        <div class="notification-footer">
                            <i class="fas fa-layer-group"></i> Category: <b>${item.category_name || 'N/A'}</b>
                        </div>
                    </div>
                `;
            });
            // Debug: log how many notifications were rendered
            console.log('Rendered notifications:', rendered, 'Unique badge count:', totalCount);
        }

        function clearNotification(event, itemId) {
            event.stopPropagation();
            // Add to dismissed list in localStorage
            let dismissed = JSON.parse(localStorage.getItem('dismissedNotifications') || '[]');
            if (!dismissed.includes(itemId)) {
                dismissed.push(itemId);
                localStorage.setItem('dismissedNotifications', JSON.stringify(dismissed));
            }
            // Remove from UI
            const notification = document.querySelector(`.notification-item[data-id="${itemId}"]`);
            if (notification) {
                notification.remove();
            }
            updateNotificationBadge();
            const container = document.getElementById('notification-container');
            if (container && container.querySelectorAll('.notification-item').length === 0) {
                const header = container.querySelector('.notification-header');
                if (header) header.remove();
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'notification-empty';
                emptyMessage.textContent = 'No notifications';
                container.appendChild(emptyMessage);
            }
        }
        
        function clearAllNotifications(event) {
            event.stopPropagation();
            // Dismiss all currently visible notifications
            const allIds = Array.from(document.querySelectorAll('.notification-item')).map(el => parseInt(el.getAttribute('data-id')));
            let dismissed = JSON.parse(localStorage.getItem('dismissedNotifications') || '[]');
            allIds.forEach(id => { if (!dismissed.includes(id)) dismissed.push(id); });
            localStorage.setItem('dismissedNotifications', JSON.stringify(dismissed));
            // ...existing code for UI clearing...
            const container = document.getElementById('notification-container');
            if (container) {
                container.innerHTML = '';
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'notification-empty';
                emptyMessage.textContent = 'No notifications';
                container.appendChild(emptyMessage);
            }
            const badge = document.getElementById('notification-badge');
            if (badge) {
                badge.textContent = '0';
                badge.style.display = 'inline-block';
            }
        }
        // When inventory changes, clear dismissed notifications for items that no longer match any alert
        function resetDismissedNotificationsIfNeeded(medicines) {
            let dismissed = JSON.parse(localStorage.getItem('dismissedNotifications') || '[]');
            const ids = medicines.map(m => m.id);
            // Remove dismissed IDs that are no longer in the inventory
            dismissed = dismissed.filter(id => ids.includes(id));
            localStorage.setItem('dismissedNotifications', JSON.stringify(dismissed));
        }
        // Call this after fetching medicines
        // Example: after data.medicines is loaded in updateNotifications
        // resetDismissedNotificationsIfNeeded(data.medicines);
    </script>

    <!-- <script src="/clinicz/js/notifications.js"></script> -->

    <!-- Custom Alert Modal -->
    <div class="modal fade" id="customAlertModal" tabindex="-1" aria-hidden="true">
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
</body>
</html>
