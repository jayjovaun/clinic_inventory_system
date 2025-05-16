/**
 * MEDICINE INVENTORY MANAGEMENT SYSTEM
 * Main controller for clinic inventory operations
 * PHP Backend Version
 */

// ==================== INITIALIZATION ====================

// Initialize localStorage data if not exists
if (!localStorage.getItem('medicineInventory')) {
    localStorage.setItem('medicineInventory', JSON.stringify([]));
}
if (!localStorage.getItem('dispensedMedicines')) {
    localStorage.setItem('dispensedMedicines', JSON.stringify([]));
}
if (!localStorage.getItem('recentStocks')) {
    localStorage.setItem('recentStocks', JSON.stringify([]));
}

// Single DOMContentLoaded event listener
document.addEventListener("DOMContentLoaded", function() {
    highlightActivePage();
    setupNavigation();

    if (isInventoryPage()) {
        loadInventory();
        loadRecentStocks();
        initPagination('allInventoryTable');
        initPagination('medicineTable');
        initPagination('medicalSuppliesTable');
        initPagination('dentalSuppliesTable');
    }
    if (isReportsPage()) {
        loadDispensedMedicines();
    }

    updateNotifications();
    setupSearchFunctionality();
    setupFilterFunctionality();
    setupResetFunctionality();
    updateAccountDropdown();
});

// Setup navigation handlers
function setupNavigation() {
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && !href.startsWith('javascript:')) {
                // Let the default navigation happen
                return true;
            }
        });
    });
}

// Page type checks
function isInventoryPage() {
    return window.location.pathname.includes('inventory.php');
}

function isReportsPage() {
    return window.location.pathname.includes('reports.php');
}

// ==================== CORE FUNCTIONS ====================

/**
 * Highlights the active page in the sidebar
 */
function highlightActivePage() {
    const currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".sidebar a").forEach(link => {
        const linkPage = link.getAttribute('href').split('/').pop();
        const isActive = linkPage === currentPage;
        if (isActive) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

function setupSearchFunctionality() {
    const searchInput = document.getElementById('searchMedicine');
    const searchIcon = document.getElementById('searchIcon');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchInventory();
            filterInventory();
        });
    }
    
    if (searchIcon) {
        searchIcon.addEventListener('click', function() {
            searchInventory();
            filterInventory();
        });
    }
}

function setupFilterFunctionality() {
    const filterCategoryBtn = document.getElementById('filterCategoryBtn');
    const filterExpirationBtn = document.getElementById('filterExpirationBtn');
    const filterDeliveryBtn = document.getElementById('filterDeliveryBtn');
    
    if (filterCategoryBtn) {
        filterCategoryBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('categoryFilterModal'));
            modal.show();
        });
    }
    
    if (filterExpirationBtn) {
        filterExpirationBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('expirationFilterModal'));
            modal.show();
        });
    }
    
    if (filterDeliveryBtn) {
        filterDeliveryBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('deliveryFilterModal'));
            modal.show();
        });
    }

    // Set up apply buttons with auto-close functionality
    setupModalApplyButton('categoryFilterModal');
    setupModalApplyButton('expirationFilterModal');
    setupModalApplyButton('deliveryFilterModal');
}

// Helper function to handle modal apply buttons
function setupModalApplyButton(modalId) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) return;

    const applyButton = modalElement.querySelector('.btn-primary');
    if (!applyButton) return;

    // Remove previous event listeners to prevent stacking
    const newApplyButton = applyButton.cloneNode(true);
    applyButton.parentNode.replaceChild(newApplyButton, applyButton);

    newApplyButton.addEventListener('click', function() {
        // Get the modal instance and hide it
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
        
        // Apply the filters
        filterInventory();
        updateActiveFilterIcons();
    });
}

function updateActiveFilterIcons() {
    const categoryBtn = document.getElementById('filterCategoryBtn');
    const expirationBtn = document.getElementById('filterExpirationBtn');
    const deliveryBtn = document.getElementById('filterDeliveryBtn');
    
    if (categoryBtn) {
        categoryBtn.classList.toggle('active', document.getElementById('filterCategory').value !== '');
    }
    if (expirationBtn) {
        expirationBtn.classList.toggle('active', document.getElementById('filterExpiration').value !== '');
    }
    if (deliveryBtn) {
        deliveryBtn.classList.toggle('active', document.getElementById('filterDeliveryMonth').value !== '');
    }
}

function setupResetFunctionality() {
    const resetFilter = document.getElementById('resetFilter');
    if (resetFilter) {
        resetFilter.addEventListener('click', function() {
            document.getElementById('searchMedicine').value = '';
            document.getElementById('filterCategory').value = '';
            document.getElementById('filterExpiration').value = '';
            document.getElementById('filterDeliveryMonth').value = '';
            
            loadInventory();
            
            // Hide any open filter modals
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
            
            // Update filter icons
            updateActiveFilterIcons();
        });
    }

    // Initialize modal cleanup handlers (should only be done once, not inside click handler)
    const modals = [
        'categoryFilterModal',
        'expirationFilterModal',
        'deliveryFilterModal'
    ];
    
    modals.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function() {
                // Clean up any lingering backdrop
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = ''; // Restore scrolling
                document.body.style.paddingRight = ''; // Remove padding added by Bootstrap
            });
        }
    });
}

function searchInventory() {
    const searchTerm = document.getElementById('searchMedicine').value.toLowerCase();
    
    const rows = document.querySelectorAll("#inventoryTableBody tr");
    rows.forEach(row => {
        const medicineName = row.cells[0].textContent.toLowerCase();
        row.style.display = medicineName.includes(searchTerm) ? "" : "none";
    });
}

function filterInventory() {
    const searchTerm = document.getElementById('searchMedicine').value.toLowerCase();
    const category = document.getElementById('filterCategory').value;
    const expirationStatus = document.getElementById('filterExpiration').value;
    const deliveryMonth = document.getElementById('filterDeliveryMonth').value;
    const now = new Date();
    const warningDays = 30;
    
    const rows = document.querySelectorAll("#inventoryTableBody tr");
    let visibleCount = 0;
    
    rows.forEach(row => {
        const medicineName = row.cells[0].textContent.toLowerCase();
        const rowCategory = row.cells[2].textContent;
        const expirationDateText = row.cells[4].textContent;
        const deliveryDateText = row.cells[5].textContent;
        
        const expirationDate = expirationStatus ? new Date(expirationDateText) : null;
        const deliveryDate = deliveryMonth ? new Date(deliveryDateText) : null;
        
        let showRow = true;
        
        if (searchTerm && !medicineName.includes(searchTerm)) showRow = false;
        if (showRow && category && rowCategory !== category) showRow = false;
        
        if (showRow && expirationStatus && expirationDate) {
            const daysToExpire = Math.floor((expirationDate - now) / (1000 * 60 * 60 * 24));
            
            if (expirationStatus === 'Expired' && daysToExpire >= 0) showRow = false;
            else if (expirationStatus === 'Expiring Soon' && (daysToExpire > warningDays || daysToExpire < 0)) showRow = false;
            else if (expirationStatus === 'Valid' && daysToExpire <= warningDays) showRow = false;
        }
        
        if (showRow && deliveryMonth && deliveryDate) {
            const deliveryMonthValue = deliveryDate.getMonth() + 1;
            if (deliveryMonthValue !== parseInt(deliveryMonth)) showRow = false;
        }
        
        row.style.display = showRow ? "" : "none";
        if (showRow) visibleCount++;
    });
}

// ==================== INVENTORY MANAGEMENT ====================

function addStockRow() {
    const tableBody = document.querySelector("#stockTable tbody");
    const row = document.createElement("tr");
    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name" required></td>
        <td><input type="text" class="form-control brand-name" required></td>
        <td>
            <select class="form-control category" onchange="handleCategoryChange(this)" required>
                <option value="">Select Category</option>
                <option value="Pain Reliever">Pain Reliever</option>
                <option value="Antibiotic">Antibiotic</option>
                <option value="Antiseptic">Antiseptic</option>
                <option value="Vitamin">Vitamin</option>
                <option value="Other">Other</option>
            </select>
        </td>
        <td><input type="number" class="form-control quantity" min="1" required oninput="validateQuantity(this)"></td>
        <td><input type="date" class="form-control expiration-date" required></td>
        <td><input type="date" class="form-control delivery-date" required></td>
        <td><button class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button></td>
    `;
    tableBody.appendChild(row);
}

function handleCategoryChange(select) {
    if (select.value === "Other") {
        const input = document.createElement("input");
        input.type = "text";
        input.className = "form-control category-input";
        input.placeholder = "Enter category";
        input.required = true;
        input.onblur = function() {
            if (!this.value.trim()) showError(this, "Category cannot be empty");
        };
        select.parentNode.replaceChild(input, select);
        input.focus();
    }
}

function validateQuantity(input) {
    const value = parseInt(input.value);
    if (isNaN(value) || value < 1) {
        showError(input, "Quantity must be ≥ 1");
        return false;
    }
    clearError(input);
    return true;
}

function deleteRow(button) {
    button.closest("tr").remove();
}

function validateInputs() {
    let isValid = true;
    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        const inputs = [
            row.querySelector(".medicine-name"),
            row.querySelector(".brand-name"),
            row.querySelector(".category, .category-input"),
            row.querySelector(".quantity"),
            row.querySelector(".expiration-date"),
            row.querySelector(".delivery-date")
        ];

        inputs.forEach(input => {
            if (!input) {
                isValid = false;
                return;
            }

            if (!input.value.trim()) {
                showError(input, "This field is required");
                isValid = false;
            } else if (input.classList.contains("quantity") && (isNaN(input.value) || parseInt(input.value) < 1)) {
                showError(input, "Quantity must be ≥ 1");
                isValid = false;
            } else {
                clearError(input);
            }
        });
    });
    return isValid;
}

function showError(input, message) {
    clearError(input);
    input.classList.add("is-invalid");
    const errorDiv = document.createElement("div");
    errorDiv.className = "invalid-feedback";
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

function clearError(input) {
    input.classList.remove("is-invalid");
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains("invalid-feedback")) {
        errorDiv.remove();
    }
}

async function confirmSave() {
    if (validateInputs()) {
        const confirmed = await showConfirm("Are you sure you want to save these entries?");
        if (confirmed) {
            saveStock();
        }
    }
}

async function saveStock() {
    if (!validateInputs()) return;

    const formData = new FormData();
    const rows = document.querySelectorAll("#stockTable tbody tr");
    const stocks = [];

    rows.forEach((row, index) => {
        const medicine = row.querySelector(".medicine-name").value.trim();
        const brand = row.querySelector(".brand-name").value.trim();
        const categoryElement = row.querySelector(".category") || row.querySelector(".category-input");
        const category = categoryElement ? categoryElement.value.trim() : "";
        const quantity = parseInt(row.querySelector(".quantity").value);
        const expirationDate = row.querySelector(".expiration-date").value;
        const dateDelivered = row.querySelector(".delivery-date").value;

        if (medicine && brand && category && quantity && expirationDate && dateDelivered) {
            stocks.push({
                medicine,
                brand,
                category,
                quantity,
                expirationDate,
                dateDelivered
            });
        }
    });

    // Send data to PHP backend
    stocks.forEach(async (stock) => {
        let endpoint = 'api/add_item.php';
        if (stock.category.toLowerCase() === 'medical supplies') {
            endpoint = 'api/add_medical_supply.php';
        } else if (stock.category.toLowerCase() === 'dental supplies') {
            endpoint = 'api/add_dental_supply.php';
        }

        await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(stock),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Stock added successfully to ${stock.category}`);
            } else {
                console.error(`Failed to add stock to ${stock.category}:`, data.message);
            }
        })
        .catch(error => {
            showAlert("Error adding stock", "Error");
        });
    });
}

// ==================== DATA LOADING ====================

function loadInventory() {
    fetch('api/get_inventory.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const tableBody = document.getElementById("inventoryTableBody");
                if (tableBody) {
                    tableBody.innerHTML = data.inventory.map(med => `
                        <tr data-id="${med.id}">
                            <td>${med.medicine || '-'}</td>
                            <td>${med.brand || '-'}</td>
                            <td>${med.category || '-'}</td>
                            <td class="text-center">${med.quantity || '0'}</td>
                            <td class="text-center">${formatDate(med.expiration_date)}</td>
                            <td class="text-center">${formatDate(med.date_delivered)}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-outline-warning btn-sm" onclick="dispenseMedicine('${med.id}')">Dispense</button>
                                    <button class="btn btn-outline-primary btn-sm" onclick="editMedicine('${med.id}')">Edit</button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteMedicine('${med.id}')">Delete</button>
                                </div>
                            </td>
                        </tr>
                    `).join("");
                }
            }
        });
}

function loadRecentStocks() {
    fetch('api/get_recent_stocks.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tableBody = document.querySelector("#recentStocksTable tbody");
                if (tableBody) {
                    tableBody.innerHTML = data.stocks.map(stock => `
                        <tr>
                            <td>${stock.medicine || '-'}</td>
                            <td>${stock.brand || '-'}</td>
                            <td>${stock.quantity || '0'}</td>
                            <td>${formatDate(stock.expiration_date)}</td>
                            <td>${formatDate(stock.date_delivered)}</td>
                            <td>${stock.category || '-'}</td>
                        </tr>
                    `).join("");
                }
            }
        })
        .catch(error => {
            showAlert("Error loading recent stocks", "Error");
        });
}

function loadDispensedMedicines() {
    fetch('api/get_dispensed.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tableBody = document.getElementById("dispensedTableBody");
                if (tableBody) {
                    tableBody.innerHTML = data.medicines.map(med => `
                        <tr>
                            <td>${med.medicine || '-'}</td>
                            <td>${med.brand || '-'}</td>
                            <td>${med.category || '-'}</td>
                            <td class="text-center">${med.quantity || '0'}</td>
                            <td class="text-center">${formatDate(med.date_dispensed)}</td>
                            <td class="text-center">${formatDate(med.expiration_date)}</td>
                        </tr>
                    `).join("");
                }
            }
        })
        .catch(error => {
            showAlert("Error loading dispensed medicines", "Error");
        });
}

/**
 * Formats a date string as MM/DD/YYYY
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '-';
    
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${month}/${day}/${year}`;
}

/**
 * Finds medicine index by ID in the inventory
 */
function findMedicineIndexById(id) {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    return inventory.findIndex(item => item.id === id);
}

// ==================== MEDICINE OPERATIONS ====================

async function dispenseMedicine(id) {
    const medicine = await getMedicineById(id);
    if (!medicine) {
        showAlert("Medicine not found in inventory", "Error");
        return;
    }
    
    const modalContent = `
        <div class="mb-3">
            <label class="form-label">How many units of ${medicine.medicine} (${medicine.brand}) would you like to dispense?</label>
            <p class="small text-muted">Current quantity: ${medicine.quantity}</p>
            <input type="number" id="dispenseQuantity" class="form-control" min="1" max="${medicine.quantity}" required>
        </div>
    `;
    
    const confirmed = await showConfirm(modalContent, "Dispense Medicine");
    if (!confirmed) return;
    
    const quantityInput = document.getElementById('dispenseQuantity');
    const quantityNum = parseInt(quantityInput.value);
    
    if (isNaN(quantityNum) || quantityNum <= 0) {
        showAlert("Please enter a valid quantity", "Invalid Input");
        return;
    }
    
    if (quantityNum > medicine.quantity) {
        showAlert(`Cannot dispense more than available quantity (${medicine.quantity})`, "Invalid Quantity");
        return;
    }
    
    // Send dispense request to PHP backend
    fetch('api/dispense_medicine.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            medicine_id: id,
            quantity: quantityNum
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`${quantityNum} units dispensed successfully!`, "Success");
            loadInventory();
            loadDispensedMedicines();
            updateNotifications();
        } else {
            showAlert(data.message || "Error dispensing medicine", "Error");
        }
    })
    .catch(error => {
        showAlert("Error dispensing medicine", "Error");
    });
}

async function deleteMedicine(id) {
    const confirmed = await showConfirm("Are you sure you want to delete this medicine?");
    if (!confirmed) return;

    fetch('api/delete_medicine.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ medicine_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert("Medicine deleted successfully");
            loadInventory();
            updateNotifications();
        } else {
            showAlert(data.message || "Error deleting medicine", "Error");
        }
    })
    .catch(error => {
        showAlert("Error deleting medicine", "Error");
    });
}

function editMedicine(id) {
    getMedicineById(id)
        .then(med => {
            if (!med) {
                showAlert("Medicine not found in inventory", "Error");
                return;
            }
            
            const row = document.querySelector(`#inventoryTableBody tr[data-id="${id}"]`);
            if (!row) return;
            
            row.innerHTML = `
                <td><input type="text" class="form-control medicine-name" value="${escapeHtml(med.medicine)}" required></td>
                <td><input type="text" class="form-control brand-name" value="${escapeHtml(med.brand)}" required></td>
                <td><input type="text" class="form-control category" value="${escapeHtml(med.category)}" required></td>
                <td class="text-center">
                    <div class="quantity-addition-container">
                        <span class="original-quantity">${med.quantity || 0}</span>
                        <span class="add-sign">+</span>
                        <input type="number" class="form-control quantity-to-add" value="0" min="0" style="width: 70px;">
                    </div>
                </td>
                <td><input type="date" class="form-control expiration-date" value="${med.expiration_date}" required></td>
                <td><input type="date" class="form-control delivery-date" value="${med.date_delivered}" required></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-success btn-sm" onclick="saveUpdatedMedicine('${med.id}')">Save</button>
                        <button class="btn btn-secondary btn-sm" onclick="loadInventory()">Cancel</button>
                    </div>
                </td>
            `;
            row.classList.add("editing-row");
        })
        .catch(error => {
            showAlert("Error loading medicine details", "Error");
        });
}

function escapeHtml(unsafe) {
    return unsafe
         ? unsafe.toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;")
         : '';
}

async function saveUpdatedMedicine(id) {
    const row = document.querySelector(`#inventoryTableBody tr[data-id="${id}"]`);
    
    if (!row) {
        console.error("No edit row found for ID:", id);
        return;
    }
    
    const inputs = {
        medicine: row.querySelector(".medicine-name"),
        brand: row.querySelector(".brand-name"),
        category: row.querySelector(".category"),
        originalQuantity: parseInt(row.querySelector(".original-quantity").textContent),
        quantityToAdd: row.querySelector(".quantity-to-add"),
        expirationDate: row.querySelector(".expiration-date"),
        dateDelivered: row.querySelector(".delivery-date")
    };

    // Validate all fields
    let isValid = true;
    Object.entries(inputs).forEach(([field, input]) => {
        if (typeof input === 'object' && !input.value.trim() && field !== 'quantityToAdd') {
            showError(input, "This field is required");
            isValid = false;
        } else if (field === "quantityToAdd" && (isNaN(input.value) || parseInt(input.value) < 0)) {
            showError(input, "Quantity must be ≥ 0");
            isValid = false;
        } else if (typeof input === 'object') {
            clearError(input);
        }
    });

    if (!isValid) {
        showAlert("Please fix the errors before saving.", "Validation Error");
        return;
    }

    const quantityToAdd = parseInt(inputs.quantityToAdd.value) || 0;
    const newQuantity = inputs.originalQuantity + quantityToAdd;

    const confirmed = await showConfirm(
        `Are you sure you want to update this medicine?<br><br>
        <strong>New quantity will be: ${newQuantity}</strong>`,
        "Confirm Update"
    );
    if (!confirmed) return;

    // Send update request to PHP backend
    fetch('api/update_medicine.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            medicine_id: id,
            medicine: inputs.medicine.value.trim(),
            brand: inputs.brand.value.trim(),
            category: inputs.category.value.trim(),
            quantity: newQuantity,
            expiration_date: inputs.expirationDate.value,
            date_delivered: inputs.dateDelivered.value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert("Medicine updated successfully!", "Success");
            loadInventory();
        } else {
            showAlert(data.message || "Error updating medicine", "Error");
        }
    })
    .catch(error => {
        showAlert("Error updating medicine", "Error");
    });
}

/**
 * Updates notifications based on medicine inventory
 */
function updateNotifications() {
    fetch('api/get_notifications.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('notificationBadge');
                const content = document.getElementById('notificationContent');
                
                if (badge) {
                    badge.classList.toggle('d-none', data.totalAlerts === 0);
                    badge.textContent = data.totalAlerts > 9 ? '9+' : data.totalAlerts;
                }
                
                if (content) {
                    if (data.totalAlerts === 0) {
                        content.innerHTML = '<li class="px-3 py-2 text-muted">No inventory alerts</li>';
                    } else {
                        content.innerHTML = [
                            ...data.expiredItems.map(item => `
                                <li class="notification-item expired-notification">
                                    <button class="notification-clear" onclick="clearSingleNotification(event, '${item.id}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <strong>${item.medicine || 'Unknown'} (${item.brand || '-'})</strong>
                                    <div class="d-flex justify-content-between small">
                                        <span>Expired on ${formatDate(item.expiration_date)}</span>
                                        <span>Qty: ${item.quantity || '0'}</span>
                                    </div>
                                </li>
                            `),
                            ...data.nearExpiryItems.map(item => `
                                <li class="notification-item near-expiry-notification">
                                    <button class="notification-clear" onclick="clearSingleNotification(event, '${item.id}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <strong>${item.medicine || 'Unknown'} (${item.brand || '-'})</strong>
                                    <div class="d-flex justify-content-between small">
                                        <span>Expires in ${item.days_remaining} days</span>
                                        <span>Qty: ${item.quantity || '0'}</span>
                                    </div>
                                </li>
                            `)
                        ].join('') || '<li class="px-3 py-2 text-muted">No alerts</li>';
                    }
                }
            }
        });
}

function clearSingleNotification(event, itemId) {
    event.stopPropagation();
    
    fetch('api/clear_notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ item_id: itemId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationItem = event.target.closest('.notification-item');
            if (notificationItem) {
                notificationItem.remove();
            }
            
            const notificationCount = document.querySelectorAll('#notificationContent .notification-item').length;
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = notificationCount > 9 ? '9+' : notificationCount;
                badge.classList.toggle('d-none', notificationCount === 0);
            }
            
            if (notificationCount === 0) {
                const content = document.getElementById('notificationContent');
                if (content) {
                    content.innerHTML = '<li class="px-3 py-2 text-muted">No inventory alerts</li>';
                }
            }
        }
    })
    .catch(error => {
        showAlert("Error clearing notification", "Error");
    });
}

function clearAllNotifications(event) {
    event.stopPropagation();
    
    fetch('api/clear_all_notifications.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotifications();
            const dropdown = bootstrap.Dropdown.getInstance(document.querySelector('#notificationDropdown'));
            if (dropdown) {
                dropdown.hide();
            }
        }
    })
    .catch(error => {
        showAlert("Error clearing all notifications", "Error");
    });
}

/**
 * Refreshes notifications
 */
function refreshNotifications() {
    updateNotifications();
}

/**
 * Handles user logout
 */
function logout() {
    // Clear client-side data
    localStorage.removeItem('clinicUsername');
    localStorage.removeItem('clinicPassword');
    localStorage.removeItem('medicineInventory');
    localStorage.removeItem('dispensedMedicines');
    localStorage.removeItem('recentStocks');
    
    // Redirect to logout.php which will handle server-side session cleanup
    window.location.href = 'logout.php';
    return false;
}

function updateAccountDropdown() {
    fetch('api/get_user_info.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const accountEmail = document.getElementById('accountEmail');
                if (accountEmail) {
                    accountEmail.textContent = `${data.email || 'User'} (${data.role || 'Role'})`;
                }

                const accountDropdown = document.getElementById('accountDropdown');
                if (accountDropdown && data.role === 'Admin') {
                    accountDropdown.innerHTML = '<i class="fas fa-user-shield fs-4"></i>';
                }
            }
        })
        .catch(error => {
            showAlert("Error updating account dropdown", "Error");
        });
}

/**
 * Checks if current user is admin
 */
function isAdmin() {
    return document.getElementById('accountEmail')?.textContent.includes('Admin');
}

// Update the publicPages array and checkAuth function
const publicPages = ['login.php'];

function checkAuth() {
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop() || 'index.php';
    
    // If trying to access any page other than login, let the server handle the redirect
    if (!publicPages.includes(currentPage)) {
        return;
    }
    
    // If trying to access login page, let the server handle the redirect
    if (publicPages.includes(currentPage)) {
        return;
    }
}

/**
 * Exports inventory to CSV
 */
function exportToCSV() {
    fetch('api/export_inventory.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                downloadCSV(data.csvContent, `medicine_inventory_${new Date().toISOString().slice(0, 10)}.csv`);
            } else {
                showAlert(data.message || "Error exporting inventory", "Export Error");
            }
        })
        .catch(error => {
            showAlert("Error exporting inventory", "Export Error");
        });
}

function exportDispensedToCSV() {
    fetch('api/export_dispensed.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                downloadCSV(data.csvContent, `dispensed_medicines_${new Date().toISOString().slice(0, 10)}.csv`);
            } else {
                showAlert(data.message || "Error exporting dispensed medicines", "Export Error");
            }
        })
        .catch(error => {
            showAlert("Error exporting dispensed medicines", "Export Error");
        });
}

/**
 * Downloads CSV file
 */
function downloadCSV(content, filename) {
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

/**
 * Export table to Excel
 */
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        showAlert('Table not found', 'error');
        return;
    }

    // Convert table to worksheet
    const ws = XLSX.utils.table_to_sheet(table);
    
    // Create workbook and add worksheet
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Data');
    
    // Generate Excel file and trigger download
    XLSX.writeFile(wb, `${filename}_${new Date().toISOString().split('T')[0]}.xlsx`);
    
    showAlert('Table exported successfully');
}

/**
 * Shows a custom alert dialog
 */
function showAlert(message, title = 'Notification') {
    const alertModal = new bootstrap.Modal(document.getElementById('customAlertModal'));
    document.getElementById('customAlertTitle').textContent = title;
    document.getElementById('customAlertBody').innerHTML = message;
    alertModal.show();
}

function showConfirm(message, title = 'Confirm Action') {
    return new Promise((resolve) => {
        const confirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
        document.getElementById('customConfirmTitle').textContent = title;
        document.getElementById('customConfirmBody').innerHTML = message;
        
        const confirmButton = document.getElementById('customConfirmOK');
        
        // Remove previous event listeners to avoid stacking
        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
        
        newConfirmButton.addEventListener('click', () => {
            confirmModal.hide();
            resolve(true);
        });
        
        document.getElementById('customConfirmModal').addEventListener('hidden.bs.modal', () => {
            resolve(false);
        });
        
        confirmModal.show();
    });
}

async function getMedicineById(id) {
    try {
        const response = await fetch(`api/get_medicine.php?id=${id}`);
        const data = await response.json();
        return data.success ? data.medicine : null;
    } catch (error) {
        return null;
    }
}

// API Configuration
const API_BASE_URL = '/api';

// Utility Functions
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.getElementById('alert-container').appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}

function handleApiError(error) {
    showAlert(error.message || 'An error occurred', 'danger');
}

// Authentication Functions
async function login(username, password) {
    try {
        const response = await fetch(`${API_BASE_URL}/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'dashboard.php';
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
    }
}

async function logout() {
    try {
        const response = await fetch(`${API_BASE_URL}/logout.php`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'login.php';
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
    }
}

// Inventory Management Functions
async function getInventory() {
    try {
        const response = await fetch(`${API_BASE_URL}/get_inventory.php`);
        const data = await response.json();
        
        if (data.success) {
            return data.inventory;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
        return [];
    }
}

async function addItem(itemData) {
    try {
        const response = await fetch(`${API_BASE_URL}/add_item.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(itemData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Item added successfully');
            return true;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
        return false;
    }
}

async function updateStock(itemId, quantity) {
    try {
        const response = await fetch(`${API_BASE_URL}/update_stock.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: itemId, quantity })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Stock updated successfully');
            return true;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
        return false;
    }
}

async function deleteItem(itemId) {
    try {
        const response = await fetch(`${API_BASE_URL}/delete_item.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: itemId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Item deleted successfully');
            return true;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
        return false;
    }
}

// Reporting Functions
async function getExpiringItems(days = 30) {
    try {
        const response = await fetch(`${API_BASE_URL}/get_expiring_items.php?days=${days}`);
        const data = await response.json();
        
        if (data.success) {
            return data.items;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
        return [];
    }
}

async function getLowStock(threshold = 10) {
    try {
        const response = await fetch(`${API_BASE_URL}/get_low_stock.php?threshold=${threshold}`);
        const data = await response.json();
        
        if (data.success) {
            return data.items;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
        return [];
    }
}

// System Functions
async function getCategories() {
    try {
        const response = await fetch(`${API_BASE_URL}/get_categories.php`);
        const data = await response.json();
        
        if (data.success) {
            return data.categories;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
        return [];
    }
}

async function exportInventory() {
    try {
        const response = await fetch(`${API_BASE_URL}/export_inventory.php`);
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `inventory_export_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
    } catch (error) {
        handleApiError(error);
    }
}

// Notification Functions
async function getNotifications(page = 1, limit = 20) {
    try {
        const response = await fetch(`${API_BASE_URL}/get_notifications.php?page=${page}&limit=${limit}`);
        const data = await response.json();
        
        if (data.success) {
            return data;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
        return { notifications: [], pagination: { total: 0, pages: 0 } };
    }
}

async function markNotificationsAsRead() {
    try {
        const response = await fetch(`${API_BASE_URL}/get_notifications.php?mark_read=true`);
        const data = await response.json();
        
        if (data.success) {
            return true;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        handleApiError(error);
        return false;
    }
}

// ==================== CHART EXPORT FUNCTIONS ====================

/**
 * Export chart as PNG
 */
function exportChart(chartId, format) {
    const chart = charts[chartId];
    if (!chart) {
        showAlert('Chart not found', 'error');
        return;
    }

    const link = document.createElement('a');
    link.download = `${chartId}_${new Date().toISOString().split('T')[0]}.${format}`;
    link.href = chart.toBase64Image();
    link.click();
}

/**
 * Export chart as PDF
 */
function exportChartAsPDF(chartId) {
    const chart = charts[chartId];
    if (!chart) {
        showAlert('Chart not found', 'error');
        return;
    }

    const canvas = chart.canvas;
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF('l', 'mm', 'a4');

    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const imageWidth = canvas.width;
    const imageHeight = canvas.height;
    const ratio = Math.min(pageWidth / imageWidth, pageHeight / imageHeight);

    // Add chart directly to the PDF
    const chartY = 10; // Top margin
    pdf.addImage(imgData, 'PNG', 0, chartY, imageWidth * ratio, imageHeight * ratio);

    pdf.save(`${chartId}_${new Date().toISOString().split('T')[0]}.pdf`);
}

/**
 * Export chart data as Excel
 */
function exportChartAsExcel(chartId) {
    const chart = charts[chartId];
    if (!chart) {
        showAlert('Chart not found', 'error');
        return;
    }

    const data = chart.data.datasets[0].data;
    const labels = chart.data.labels;
    const rows = labels.map((label, index) => [label, data[index]]);

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([['Category', 'Value'], ...rows]);
    XLSX.utils.book_append_sheet(wb, ws, 'Chart Data');
    XLSX.writeFile(wb, `${chartId}_${new Date().toISOString().split('T')[0]}.xlsx`);
}

/**
 * Download all charts
 */
function downloadAllCharts() {
    Object.keys(charts).forEach(chartId => {
        exportChart(chartId, 'png');
        exportChartAsPDF(chartId);
        exportChartAsExcel(chartId);
    });
    showAlert('All charts downloaded successfully');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Login Form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            login(username, password);
        });
    }

    // Logout Button
    const logoutButton = document.getElementById('logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', logout);
    }

    // Inventory Form
    const inventoryForm = document.getElementById('inventory-form');
    if (inventoryForm) {
        inventoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(inventoryForm);
            const itemData = {
                name: formData.get('name'),
                category: formData.get('category'),
                quantity: parseInt(formData.get('quantity')),
                expiry_date: formData.get('expiry_date')
            };
            addItem(itemData);
        });
    }

    // Export Button
    const exportButton = document.getElementById('export-button');
    if (exportButton) {
        exportButton.addEventListener('click', exportInventory);
    }

    // Initialize DataTables
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        if ($.fn.DataTable.isDataTable(table)) {
            $(table).DataTable();
        }
    });

    // Add this to ensure auth check runs on every page load
    checkAuth();
});

// Pagination configuration
// Remove or rename this to avoid duplicate declaration with dashboard.php
// const rowsPerPage = 10;
const paginationData = {
    'allInventoryTable': { currentPage: 1, totalPages: 1 },
    'medicineTable': { currentPage: 1, totalPages: 1 },
    'medicalSuppliesTable': { currentPage: 1, totalPages: 1 },
    'dentalSuppliesTable': { currentPage: 1, totalPages: 1 }
};

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