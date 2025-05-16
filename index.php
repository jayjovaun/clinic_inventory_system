<?php
session_start();

// Set page title and subtitle
$pageTitle = 'Stock Entry';
$pageSubtitle = 'Add new stock to the inventory';

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
            background-color: rgba(0,0,0,0.02);
        }
        
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .form-control {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .btn {
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        
        .btn-success {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
        }
        
        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: var(--spacing-lg);
            border: 1px solid transparent;
        }
        
        .alert-success {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #badbcc;
        }
        
        .alert-danger {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
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
        
        .account-dropdown .btn-link:focus,
        .account-dropdown .btn-link:active {
            outline: none !important;
            box-shadow: none !important;
            border: none !important;
        }
        
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .is-invalid ~ .invalid-feedback {
            display: block;
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
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-header > div {
                margin-top: var(--spacing-sm);
                width: 100%;
            }
        }
        
        .category-cell {
            vertical-align: middle;
        }
        
        .category-container {
            position: relative;
        }
        
        .category-input {
            display: none;
            width: 100%;
        }
        
        /* Additional styles for better form appearance */
        .form-control, .form-select {
            padding: 0.375rem 0.75rem;
            height: calc(1.5em + 0.75rem + 2px);
        }
        
        /* Ensure table cells have proper spacing */
        #stockTable td {
            vertical-align: middle;
            padding: 0.5rem;
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
        <a href="index.php" class="active">
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
                <h1>Medicine Stock Entry</h1>
                <p class="text-muted">Add new stock to the inventory</p>
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

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Stock Entry Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Add New Stock</h5>
                <div>
                    <button type="button" class="btn btn-primary me-2" id="addRowBtn">
                        <i class="fas fa-plus me-1"></i> Add Row
                    </button>
                    <button type="button" class="btn btn-success" id="saveBtn">
                        <i class="fas fa-save me-1"></i> Save
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="stockTable">
                        <thead class="table-light">
                            <tr>
                                <th>Medicine Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th class="text-center">Quantity</th>
                                <th>Expiration Date</th>
                                <th>Delivery Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <input type="text" class="form-control medicine-name" required placeholder="Medicine name">
                                    <div class="invalid-feedback">Please enter a medicine name</div>
                                </td>
                                <td>
                                    <input type="text" class="form-control brand-name" required placeholder="Brand name">
                                    <div class="invalid-feedback">Please enter a brand name</div>
                                </td>
                                <td class="category-cell">
                                    <div class="category-container">
                                        <select class="form-select category-select" required>
                                            <option value="" selected disabled>Select category</option>
                                            <option value="Medicine">Medicine</option>
                                            <option value="Medical Supplies">Medical Supplies</option>
                                            <option value="Dental Supplies">Dental Supplies</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <input type="text" class="form-control category-input" placeholder="Enter category">
                                    </div>
                                    <div class="invalid-feedback">Please select or enter a category</div>
                                </td>
                                <td>
                                    <input type="number" class="form-control quantity text-center" min="1" required oninput="validateQuantity(this)" placeholder="Qty">
                                    <div class="invalid-feedback">Please enter a valid quantity</div>
                                </td>
                                <td>
                                    <input type="date" class="form-control expiration-date" required>
                                    <div class="invalid-feedback">Please select an expiration date</div>
                                </td>
                                <td>
                                    <input type="date" class="form-control delivery-date" required>
                                    <div class="invalid-feedback">Please select a delivery date</div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm delete-row-btn" title="Delete row">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recently Added Stocks Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold"><i class="fas fa-history me-2"></i>Recently Added Stocks</h5>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="exportBtn" onclick="showExportOptions('recentStocksTable')">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="recentStocksTable">
                        <thead class="table-light">
                            <tr>
                                <th>Medicine</th>
                                <th>Brand</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Expiration</th>
                                <th class="text-center">Delivered</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                    <!-- Pagination will be added dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Alert Modal -->
    <div class="modal fade" id="customAlertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="customAlertTitle">Notification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="customAlertBody">
                    <!-- Message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirm Modal -->
    <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="customConfirmTitle">Confirm Action</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="customConfirmBody">
                    <!-- Message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="customConfirmOK">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="/clinicz/js/export-utils.js"></script>
    <script>
        // Pagination configuration
        const rowsPerPage = 10;
        const paginationData = {
            'recentStocksTable': { currentPage: 1, totalPages: 1 }
        };
        
        let bootstrap;
        
        // Additional JavaScript to ensure validation works
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap
            bootstrap = window.bootstrap;
            
            // Set today's date as the default for delivery date
            document.querySelectorAll('.delivery-date').forEach(input => {
                input.valueAsDate = new Date();
            });
            
            // Set a default expiration date (1 year from today)
            document.querySelectorAll('.expiration-date').forEach(input => {
                const defaultExpiry = new Date();
                defaultExpiry.setFullYear(defaultExpiry.getFullYear() + 1);
                input.valueAsDate = defaultExpiry;
            });
            
            // Load recent stocks
            loadRecentStocks();

            // Initialize Bootstrap components
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Set up custom confirm modal
            const customConfirmOK = document.getElementById('customConfirmOK');
            if (customConfirmOK) {
                customConfirmOK.addEventListener('click', function() {
                    if (this.dataset.action === 'saveStock') {
                        saveStock();
                        const modal = bootstrap.Modal.getInstance(document.getElementById('customConfirmModal'));
                        if (modal) modal.hide();
                    }
                });
            }
            
            // Initialize category change handlers
            document.querySelectorAll('.category-select').forEach(select => {
                select.addEventListener('change', function() {
                    handleCategoryChange(this);
                });
            });
            
            // Add event listeners for buttons
            document.getElementById('addRowBtn').addEventListener('click', addStockRow);
            document.getElementById('saveBtn').addEventListener('click', confirmSave);
            
            // Add event listeners for delete buttons
            document.querySelectorAll('.delete-row-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    deleteRow(this);
                });
            });
        });

        // Validation functions
        function validateQuantity(input) {
            const value = parseInt(input.value);
            if (isNaN(value) || value < 1) {
                input.value = 1;
            }
        }
        
        function validateInputs() {
            let isValid = true;
            
            // Reset all validation states
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Validate medicine names
            document.querySelectorAll('.medicine-name').forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            // Validate brand names
            document.querySelectorAll('.brand-name').forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            // Validate categories
            document.querySelectorAll('tr').forEach(row => {
                const select = row.querySelector('.category-select');
                const input = row.querySelector('.category-input');
                
                if (select && select.style.display !== 'none') {
                    if (!select.value || select.value === "") {
                        select.classList.add('is-invalid');
                        isValid = false;
                    }
                } else if (input && input.style.display !== 'none') {
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        isValid = false;
                    }
                }
            });
            
            // Validate quantities
            document.querySelectorAll('.quantity').forEach(input => {
                const value = parseInt(input.value);
                if (isNaN(value) || value < 1) {
                    input.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            // Validate expiration dates
            document.querySelectorAll('.expiration-date').forEach(input => {
                if (!input.value) {
                    input.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            // Validate delivery dates
            document.querySelectorAll('.delivery-date').forEach(input => {
                if (!input.value) {
                    input.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            return isValid;
        }
        
        function confirmSave() {
            console.log("Confirm save called");
            if (!validateInputs()) {
                showAlert('Please fill in all required fields correctly', 'error');
                return;
            }
            
            const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
            document.getElementById('customConfirmTitle').textContent = 'Confirm Save';
            document.getElementById('customConfirmBody').textContent = 'Are you sure you want to save these items to inventory?';
            
            // Set data attribute for the action
            document.getElementById('customConfirmOK').dataset.action = 'saveStock';
            
            confirmModal.show();
        }
        
        function showAlert(message, type = 'info') {
            console.log("Show alert:", message, type);
            const alertModal = new bootstrap.Modal(document.getElementById('customAlertModal'));
            const title = type === 'error' ? 'Error' : 'Success';
            const titleClass = type === 'error' ? 'text-danger' : 'text-success';
            
            document.getElementById('customAlertTitle').textContent = title;
            document.getElementById('customAlertTitle').className = `modal-title ${titleClass}`;
            document.getElementById('customAlertBody').textContent = message;
            
            alertModal.show();
        }
        
        function addStockRow() {
            console.log("Add stock row called");
            const tbody = document.querySelector('#stockTable tbody');
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td>
                    <input type="text" class="form-control medicine-name" required placeholder="Medicine name">
                    <div class="invalid-feedback">Please enter a medicine name</div>
                </td>
                <td>
                    <input type="text" class="form-control brand-name" required placeholder="Brand name">
                    <div class="invalid-feedback">Please enter a brand name</div>
                </td>
                <td class="category-cell">
                    <div class="category-container">
                        <select class="form-select category-select" required>
                            <option value="" selected disabled>Select category</option>
                            <option value="Medicine">Medicine</option>
                            <option value="Medical Supplies">Medical Supplies</option>
                            <option value="Dental Supplies">Dental Supplies</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" class="form-control category-input" placeholder="Enter category">
                    </div>
                    <div class="invalid-feedback">Please select or enter a category</div>
                </td>
                <td>
                    <input type="number" class="form-control quantity text-center" min="1" required oninput="validateQuantity(this)" placeholder="Qty">
                    <div class="invalid-feedback">Please enter a valid quantity</div>
                </td>
                <td>
                    <input type="date" class="form-control expiration-date" required>
                    <div class="invalid-feedback">Please select an expiration date</div>
                </td>
                <td>
                    <input type="date" class="form-control delivery-date" required>
                    <div class="invalid-feedback">Please select a delivery date</div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm delete-row-btn" title="Delete row">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(newRow);
            
            // Set default dates for the new row
            const newDeliveryDate = newRow.querySelector('.delivery-date');
            const newExpirationDate = newRow.querySelector('.expiration-date');
            
            newDeliveryDate.valueAsDate = new Date();
            
            const defaultExpiry = new Date();
            defaultExpiry.setFullYear(defaultExpiry.getFullYear() + 1);
            newExpirationDate.valueAsDate = defaultExpiry;
            
            // Add event listener for category change
            const categorySelect = newRow.querySelector('.category-select');
            categorySelect.addEventListener('change', function() {
                handleCategoryChange(this);
            });
            
            // Add event listener for delete button
            const deleteBtn = newRow.querySelector('.delete-row-btn');
            deleteBtn.addEventListener('click', function() {
                deleteRow(this);
            });
        }
        
        function deleteRow(button) {
            console.log("Delete row called");
            const tbody = document.querySelector('#stockTable tbody');
            const row = button.closest('tr');
            
            // Don't delete if it's the only row
            if (tbody.rows.length > 1) {
                row.remove();
            } else {
                showAlert('Cannot delete the only row. At least one item is required.', 'error');
            }
        }
        
        function handleCategoryChange(select) {
            console.log("Handle category change called");
            const row = select.closest('tr');
            const categoryContainer = row.querySelector('.category-container');
            const categoryInput = row.querySelector('.category-input');
            
            if (select.value === 'Other') {
                // Hide select and show input
                select.style.display = 'none';
                categoryInput.style.display = 'block';
                categoryInput.focus();
                categoryInput.value = '';
                
                // Add event listener to go back to select if input is emptied
                categoryInput.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        select.style.display = 'block';
                        categoryInput.style.display = 'none';
                        select.value = '';
                    }
                });
            } else {
                select.style.display = 'block';
                categoryInput.style.display = 'none';
            }
        }
        
        function saveStock() {
            console.log("Save stock called");
            if (!validateInputs()) {
                return;
            }
            
            const rows = document.querySelectorAll('#stockTable tbody tr');
            const stockItems = [];
            
            rows.forEach(row => {
                const medicineName = row.querySelector('.medicine-name').value;
                const brandName = row.querySelector('.brand-name').value;
                
                // Get category from either select or input
                let category;
                const categorySelect = row.querySelector('.category-select');
                const categoryInput = row.querySelector('.category-input');
                
                if (categorySelect && categorySelect.style.display !== 'none') {
                    category = categorySelect.value;
                } else if (categoryInput && categoryInput.style.display !== 'none') {
                    category = categoryInput.value;
                }
                
                const quantity = parseInt(row.querySelector('.quantity').value);
                const expirationDate = row.querySelector('.expiration-date').value;
                const deliveryDate = row.querySelector('.delivery-date').value;
                
                stockItems.push({
                    name: medicineName,
                    brand: brandName,
                    category: category,
                    quantity: quantity,
                    expiration_date: expirationDate,
                    delivery_date: deliveryDate
                });
            });
            
            console.log("Stock items to save:", stockItems);
            
            // Send data to server via AJAX
            fetch('api/add_stock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(stockItems)
            })
            .then(response => response.json())
            .then(data => {
                console.log("API response:", data);
                if (data.success) {
                    showAlert('Stock added successfully!');
                    
                    // Clear the table except for the first row
                    const tbody = document.querySelector('#stockTable tbody');
                    while (tbody.rows.length > 1) {
                        tbody.deleteRow(1);
                    }
                    
                    // Reset the first row
                    const firstRow = tbody.rows[0];
                    firstRow.querySelector('.medicine-name').value = '';
                    firstRow.querySelector('.brand-name').value = '';
                    
                    // Reset category
                    const categorySelect = firstRow.querySelector('.category-select');
                    const categoryInput = firstRow.querySelector('.category-input');
                    categorySelect.style.display = 'block';
                    categoryInput.style.display = 'none';
                    categorySelect.value = '';
                    
                    firstRow.querySelector('.quantity').value = '';
                    
                    // Set default dates
                    const deliveryDate = firstRow.querySelector('.delivery-date');
                    const expirationDate = firstRow.querySelector('.expiration-date');
                    
                    deliveryDate.valueAsDate = new Date();
                    
                    const defaultExpiry = new Date();
                    defaultExpiry.setFullYear(defaultExpiry.getFullYear() + 1);
                    expirationDate.valueAsDate = defaultExpiry;
                    
                    // Reload recent stocks
                    loadRecentStocks();
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Network error occurred: ' + error.message, 'error');
            });
        }
        
        function loadRecentStocks() {
            console.log("Load recent stocks called");
            fetch('api/get_recent_stocks.php')
            .then(response => response.json())
            .then(data => {
                console.log("Recent stocks API response:", data);
                if (data.success) {
                    const tbody = document.querySelector('#recentStocksTable tbody');
                    tbody.innerHTML = '';
                    
                    if (data.data.length === 0) {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td colspan="6" class="text-center">No recent stock entries found</td>';
                        tbody.appendChild(row);
                    } else {
                        // Update pagination data
                        paginationData['recentStocksTable'].totalPages = Math.ceil(data.data.length / rowsPerPage);
                        
                        // Create pagination controls if they don't exist
                        createPaginationControls('recentStocksTable', data.data);
                        
                        // Show first page
                        showPage('recentStocksTable', 1, data.data);
                    }
                } else {
                    showAlert('Error loading recent stocks: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Network error occurred while loading recent stocks', 'error');
            });
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
                    showPage(tableId, currentPage - 1, data);
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
                    showPage(tableId, 1, data);
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
                    showPage(tableId, i, data);
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
                    showPage(tableId, totalPages, data);
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
                    showPage(tableId, currentPage + 1, data);
                }
            });
            
            nextLi.appendChild(nextLink);
            ul.appendChild(nextLi);
            
            nav.appendChild(ul);
            paginationContainer.appendChild(nav);
        }
        
        // Function to show a specific page
        function showPage(tableId, pageNumber, data) {
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
            
            // Calculate start and end index
            const startIndex = (pageNumber - 1) * rowsPerPage;
            const endIndex = Math.min(startIndex + rowsPerPage, data.length);
            
            // Add rows for the current page
            for (let i = startIndex; i < endIndex; i++) {
                const item = data[i];
                const row = document.createElement('tr');
                
                // Format dates
                const expiryDate = new Date(item.expiration_date);
                const deliveryDate = new Date(item.delivery_date);
                
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td>${item.brand}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-center">${expiryDate.toLocaleDateString()}</td>
                    <td class="text-center">${deliveryDate.toLocaleDateString()}</td>
                    <td>${item.category}</td>
                `;
                
                tbody.appendChild(row);
            }
            
            // Update pagination controls
            updatePaginationControls(tableId, data);
        }
        
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
            let tableTitle = 'Recently Added Stocks';
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
        
        // Function to get data from table
        function getTableData(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return [];
            
            // Get headers
            const headers = [];
            const headerRow = table.querySelector('thead tr');
            if (headerRow) {
                headerRow.querySelectorAll('th').forEach(th => {
                    headers.push(th.textContent.trim());
                });
            }
            
            // Get data from all rows, not just visible ones
            const data = [];
            table.querySelectorAll('tbody tr').forEach(tr => {
                const rowData = [];
                tr.querySelectorAll('td').forEach(td => {
                    // Always extract only plain text, ignore all HTML elements
                    rowData.push(td.textContent.trim());
                });
                if (rowData.length > 0) {
                    data.push(rowData);
                }
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
                    console.error('exportToPDF function not found');
                    showAlert('Export utility not available. Please try again later.', 'error');
                }
            } catch (error) {
                console.error('Error exporting to PDF:', error);
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
                    console.error('exportToExcel function not found');
                    showAlert('Export utility not available. Please try again later.', 'error');
                }
            } catch (error) {
                console.error('Error exporting to Excel:', error);
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
                    console.error('exportToCSV function not found');
                    showAlert('Export utility not available. Please try again later.', 'error');
                    
                    // Fallback to basic CSV export
                    fallbackExportToCSV(tableId);
                }
            } catch (error) {
                console.error('Error exporting to CSV:', error);
                showAlert('Failed to export to CSV: ' + error.message, 'error');
            }
        }
        
        // Fallback function for CSV export
        function fallbackExportToCSV(tableId) {
            console.log("Fallback export to CSV called");
            fetch('api/get_recent_stocks.php')
            .then(response => response.json())
            .then(data => {
                console.log("Export API response:", data);
                if (data.success) {
                    // Create CSV content
                    let csvContent = "data:text/csv;charset=utf-8,";
                    csvContent += "Medicine,Brand,Quantity,Expiration Date,Delivery Date,Category\n";
                    
                    data.data.forEach(item => {
                        csvContent += `${item.name},${item.brand},${item.quantity},${item.expiration_date},${item.delivery_date},${item.category}\n`;
                    });
                    
                    // Create download link
                    const encodedUri = encodeURI(csvContent);
                    const link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", "recent_stocks.csv");
                    document.body.appendChild(link);
                    
                    // Trigger download
                    link.click();
                    document.body.removeChild(link);
                } else {
                    showAlert('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Network error occurred', 'error');
            });
        }
        
        // Helper function to format date for filename
        function formatDateForFilename(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        function logout() {
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>