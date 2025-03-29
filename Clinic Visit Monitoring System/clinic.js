/**
 * MEDICINE INVENTORY MANAGEMENT SYSTEM
 * Main controller for clinic inventory operations
 */

// ==================== INITIALIZATION ====================

// Runs when the DOM is fully loaded
document.addEventListener("DOMContentLoaded", function () {
    console.log("Initializing clinic inventory system...");
    
    // Initialize page components
    highlightActivePage();
    loadInventory();
    loadRecentStocks();
    loadDispensedMedicines();
    updateNotifications();
    
    // ===== SEARCH FUNCTIONALITY =====
    const searchInput = document.getElementById('searchMedicine');
    const searchIcon = document.getElementById('searchIcon');
    
    // Set up search input listener
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchInventory();
            // Apply other active filters after search
            filterInventory(); 
        });
    }
    
    // Set up search icon click listener
    if (searchIcon) {
        searchIcon.addEventListener('click', function() {
            searchInventory();
            filterInventory();
        });
    }
    
    // ===== FILTER FUNCTIONALITY =====
    // Category filter
    const filterCategoryBtn = document.getElementById('filterCategoryBtn');
    if (filterCategoryBtn) {
        filterCategoryBtn.addEventListener('click', function() {
            document.getElementById('categoryFilterModal').style.display = 'block';
        });
    }
    
    // Expiration filter
    const filterExpirationBtn = document.getElementById('filterExpirationBtn');
    if (filterExpirationBtn) {
        filterExpirationBtn.addEventListener('click', function() {
            document.getElementById('expirationFilterModal').style.display = 'block';
        });
    }
    
    // Delivery month filter
    const filterDeliveryBtn = document.getElementById('filterDeliveryBtn');
    if (filterDeliveryBtn) {
        filterDeliveryBtn.addEventListener('click', function() {
            document.getElementById('deliveryFilterModal').style.display = 'block';
        });
    }
    
    // ===== RESET FUNCTIONALITY =====
    const resetFilter = document.getElementById('resetFilter');
    if (resetFilter) {
        resetFilter.addEventListener('click', function() {
            // Clear all filter values
            document.getElementById('searchMedicine').value = '';
            document.getElementById('filterCategory').value = '';
            document.getElementById('filterExpiration').value = '';
            document.getElementById('filterDeliveryMonth').value = '';
            
            // Reload full inventory
            loadInventory();
            
            // Close any open filter modals
            document.querySelectorAll('.filter-modal').forEach(modal => {
                modal.style.display = 'none';
            });
            
            console.log("All filters reset");
        });
    }
});

// ==================== CORE FUNCTIONS ====================

/**
 * Highlights the active page in the sidebar
 */
function highlightActivePage() {
    const currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".sidebar a").forEach(link => {
        const isActive = link.getAttribute("href") === currentPage;
        link.classList.toggle("active", isActive);
        if (isActive) {
            console.log(`Active page: ${currentPage}`);
        }
    });
}

/**
 * Searches inventory by medicine name
 */
function searchInventory() {
    const searchTerm = document.getElementById('searchMedicine').value.toLowerCase();
    console.log(`Searching for: ${searchTerm}`);
    
    const rows = document.querySelectorAll("#inventoryTableBody tr");
    
    rows.forEach(row => {
        const medicineName = row.cells[0].textContent.toLowerCase();
        row.style.display = medicineName.includes(searchTerm) ? "" : "none";
    });
}

/**
 * Applies all active filters to inventory
 */
function filterInventory() {
    console.log("Applying filters...");
    
    const searchTerm = document.getElementById('searchMedicine').value.toLowerCase();
    const category = document.getElementById('filterCategory').value;
    const expirationStatus = document.getElementById('filterExpiration').value;
    const deliveryMonth = document.getElementById('filterDeliveryMonth').value;
    const now = new Date();
    const warningDays = 30; // Days considered as "expiring soon"
    
    const rows = document.querySelectorAll("#inventoryTableBody tr");
    let visibleCount = 0;
    
    rows.forEach(row => {
        const medicineName = row.cells[0].textContent.toLowerCase();
        const rowCategory = row.cells[2].textContent;
        const expirationDateText = row.cells[4].textContent;
        const deliveryDateText = row.cells[5].textContent;
        
        // Parse dates only if needed for filtering
        const expirationDate = expirationStatus ? new Date(expirationDateText) : null;
        const deliveryDate = deliveryMonth ? new Date(deliveryDateText) : null;
        
        let showRow = true;
        
        // Apply search filter
        if (searchTerm && !medicineName.includes(searchTerm)) {
            showRow = false;
        }
        
        // Apply category filter
        if (showRow && category && rowCategory !== category) {
            showRow = false;
        }
        
        // Apply expiration status filter
        if (showRow && expirationStatus && expirationDate) {
            const daysToExpire = Math.floor((expirationDate - now) / (1000 * 60 * 60 * 24));
            
            if (expirationStatus === 'Expired' && daysToExpire >= 0) {
                showRow = false;
            } 
            else if (expirationStatus === 'Expiring Soon' && (daysToExpire > warningDays || daysToExpire < 0)) {
                showRow = false;
            } 
            else if (expirationStatus === 'Valid' && daysToExpire <= warningDays) {
                showRow = false;
            }
        }
        
        // Apply delivery month filter
        if (showRow && deliveryMonth && deliveryDate) {
            const deliveryMonthValue = deliveryDate.getMonth() + 1;
            if (deliveryMonthValue !== parseInt(deliveryMonth)) {
                showRow = false;
            }
        }
        
        // Update row visibility
        row.style.display = showRow ? "" : "none";
        if (showRow) visibleCount++;
    });
    
    console.log(`Filter results: ${visibleCount} items visible`);
}

// ==================== FILTER MODAL FUNCTIONS ====================

/**
 * Applies category filter from modal
 */
function applyCategoryFilter() {
    const category = document.getElementById('filterCategory').value;
    console.log(`Applying category filter: ${category || 'All'}`);
    document.getElementById('categoryFilterModal').style.display = 'none';
    filterInventory();
}

/**
 * Applies expiration filter from modal
 */
function applyExpirationFilter() {
    const expiration = document.getElementById('filterExpiration').value;
    console.log(`Applying expiration filter: ${expiration || 'All'}`);
    document.getElementById('expirationFilterModal').style.display = 'none';
    filterInventory();
}

/**
 * Applies delivery month filter from modal
 */
function applyDeliveryFilter() {
    const deliveryMonth = document.getElementById('filterDeliveryMonth').value;
    console.log(`Applying delivery month filter: ${deliveryMonth || 'All'}`);
    document.getElementById('deliveryFilterModal').style.display = 'none';
    filterInventory();
}

// ==================== INVENTORY MANAGEMENT ====================

/**
 * Adds a new row to the stock entry table
 */
function addStockRow() {
    console.log("Adding new stock row...");
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

/**
 * Handles category selection change (for "Other" option)
 */
function handleCategoryChange(select) {
    if (select.value === "Other") {
        console.log("Custom category selected");
        const input = document.createElement("input");
        input.type = "text";
        input.className = "form-control category-input";
        input.placeholder = "Enter category";
        input.required = true;
        input.onblur = function() {
            if (!this.value.trim()) {
                showError(this, "Category cannot be empty");
            }
        };
        select.parentNode.replaceChild(input, select);
        input.focus();
    }
}

/**
 * Validates quantity input
 */
function validateQuantity(input) {
    const value = parseInt(input.value);
    if (isNaN(value) || value < 1) {
        showError(input, "Quantity must be ≥ 1");
        return false;
    }
    clearError(input);
    return true;
}

/**
 * Deletes a row from the stock entry table
 */
function deleteRow(button) {
    console.log("Deleting stock row...");
    button.closest("tr").remove();
}

/**
 * Validates all inputs in the stock entry table
 */
function validateInputs() {
    console.log("Validating inputs...");
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

// ==================== ERROR HANDLING ====================

/**
 * Shows an error message for an input field
 */
function showError(input, message) {
    clearError(input);
    input.classList.add("is-invalid");
    const errorDiv = document.createElement("div");
    errorDiv.className = "invalid-feedback";
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

/**
 * Clears error messages from an input field
 */
function clearError(input) {
    input.classList.remove("is-invalid");
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains("invalid-feedback")) {
        errorDiv.remove();
    }
}

// ==================== STOCK OPERATIONS ====================

/**
 * Confirms before saving stock entries
 */
function confirmSave() {
    console.log("Confirming stock save...");
    if (validateInputs()) {
        if (confirm("Are you sure you want to save these entries?")) {
            saveStock();
        }
    }
}

/**
 * Saves stock entries to localStorage
 */
function saveStock() {
    console.log("Saving stock...");
    if (!validateInputs()) {
        return;
    }

    // Get existing inventory
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];

    // Add new items from input table
    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        const medicine = row.querySelector(".medicine-name").value.trim();
        const brand = row.querySelector(".brand-name").value.trim();
        const categoryElement = row.querySelector(".category") || row.querySelector(".category-input");
        const category = categoryElement ? categoryElement.value.trim() : "";
        const quantity = parseInt(row.querySelector(".quantity").value);
        const expirationDate = row.querySelector(".expiration-date").value;
        const dateDelivered = row.querySelector(".delivery-date").value;

        if (medicine && brand && category && quantity && expirationDate && dateDelivered) {
            const newStock = { 
                medicine, 
                brand, 
                category, 
                quantity, 
                expirationDate, 
                dateDelivered 
            };
            inventory.push(newStock);
            recentStocks.unshift(newStock);
            console.log(`Added new stock: ${medicine}`);
        }
    });

    // Save updated data
    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    localStorage.setItem("recentStocks", JSON.stringify(recentStocks));
    
    // Reset input table
    document.querySelector("#stockTable tbody").innerHTML = `
        <tr>
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
        </tr>
    `;
    
    // Update displays
    loadInventory();
    loadRecentStocks();
    updateNotifications();
    
    alert("New stock items added successfully!");
    console.log("Stock saved successfully");
}

// ==================== DATA LOADING ====================

/**
 * Loads inventory data into the table
 */
function loadInventory() {
    console.log("Loading inventory...");
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    
    // Sort by expiration date (soonest first)
    inventory.sort((a, b) => new Date(a.expirationDate) - new Date(b.expirationDate));

    const tableBody = document.getElementById("inventoryTableBody");
    if (tableBody) {
        tableBody.innerHTML = inventory.map((med, index) => `
            <tr>
                <td>${med.medicine || '-'}</td>
                <td>${med.brand || '-'}</td>
                <td>${med.category || '-'}</td>
                <td class="text-center">${med.quantity || '0'}</td>
                <td class="text-center">${formatDate(med.expirationDate)}</td>
                <td class="text-center">${formatDate(med.dateDelivered)}</td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-warning btn-sm" onclick="dispenseMedicine(${index})">Dispense</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="editMedicine(${index})">Edit</button>
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteMedicine(${index})">Delete</button>
                    </div>
                </td>
            </tr>
        `).join("");
        
        console.log(`Loaded ${inventory.length} inventory items`);
    }
}

/**
 * Loads recent stock data
 */
function loadRecentStocks() {
    console.log("Loading recent stocks...");
    const recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];
    const tableBody = document.querySelector("#recentStocksTable tbody");
    if (tableBody) {
        tableBody.innerHTML = recentStocks.slice(0, 5).map(stock => `
            <tr>
                <td>${stock.medicine || '-'}</td>
                <td>${stock.brand || '-'}</td>
                <td>${stock.quantity || '0'}</td>
                <td>${formatDate(stock.expirationDate)}</td>
                <td>${formatDate(stock.dateDelivered)}</td>
                <td>${stock.category || '-'}</td>
            </tr>
        `).join("");
        
        console.log(`Loaded ${Math.min(recentStocks.length, 5)} recent stock entries`);
    }
}

/**
 * Loads dispensed medicines data
 */
function loadDispensedMedicines() {
    console.log("Loading dispensed medicines...");
    const dispensedMedicines = JSON.parse(localStorage.getItem("dispensedMedicines")) || [];
    const tableBody = document.getElementById("dispensedTableBody");
    if (tableBody) {
        tableBody.innerHTML = dispensedMedicines.map(med => `
            <tr>
                <td>${med.medicine || '-'}</td>
                <td>${med.brand || '-'}</td>
                <td>${med.category || '-'}</td>
                <td class="text-center">${med.quantity || '0'}</td>
                <td class="text-center">${formatDate(med.dateDispensed)}</td>
                <td class="text-center">${formatDate(med.expirationDate)}</td>
            </tr>
        `).join("");
        
        console.log(`Loaded ${dispensedMedicines.length} dispensed medicine records`);
    }
}

// ==================== UTILITY FUNCTIONS ====================

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

// ==================== MEDICINE OPERATIONS ====================

/**
 * Dispenses medicine from inventory
 */
function dispenseMedicine(index) {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const medicine = inventory[index];
    
    const quantity = prompt(`How many units of ${medicine.medicine} (${medicine.brand}) would you like to dispense? Current quantity: ${medicine.quantity}`);
    
    if (quantity === null) return;
    
    const quantityNum = parseInt(quantity);
    if (isNaN(quantityNum) || quantityNum <= 0) {
        alert("Please enter a valid positive number");
        return;
    }
    
    if (quantityNum > medicine.quantity) {
        alert(`Cannot dispense more than available quantity (${medicine.quantity})`);
        return;
    }
    
    if (confirm(`Dispense ${quantityNum} units of ${medicine.medicine}?`)) {
        console.log(`Dispensing ${quantityNum} units of ${medicine.medicine}`);
        
        if (quantityNum === medicine.quantity) {
            inventory.splice(index, 1);
        } else {
            inventory[index].quantity -= quantityNum;
        }
        
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        
        const dispensedMedicines = JSON.parse(localStorage.getItem("dispensedMedicines")) || [];
        dispensedMedicines.push({
            medicine: medicine.medicine,
            brand: medicine.brand,
            category: medicine.category,
            quantity: quantityNum,
            dateDispensed: new Date().toISOString().split('T')[0],
            expirationDate: medicine.expirationDate
        });
        localStorage.setItem("dispensedMedicines", JSON.stringify(dispensedMedicines));
        
        loadInventory();
        loadDispensedMedicines();
        updateNotifications();
        alert(`${quantityNum} units dispensed successfully!`);
        console.log("Dispensing complete");
    }
}

/**
 * Deletes medicine from inventory
 */
function deleteMedicine(index) {
    if (confirm("Are you sure you want to delete this medicine?")) {
        console.log("Deleting medicine...");
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        const deletedMedicine = inventory[index];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
        updateNotifications();
        console.log(`Deleted medicine: ${deletedMedicine.medicine}`);
    }
}

/**
 * Enables editing of a medicine entry
 */
function editMedicine(index) {
    console.log("Editing medicine...");
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const med = inventory[index];
    const row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    
    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name" value="${med.medicine}" required></td>
        <td><input type="text" class="form-control brand-name" value="${med.brand}" required></td>
        <td><input type="text" class="form-control category" value="${med.category}" required></td>
        <td class="text-center"><input type="number" class="form-control quantity" value="${med.quantity}" min="1" required></td>
        <td><input type="date" class="form-control expiration-date" value="${med.expirationDate}" required></td>
        <td><input type="date" class="form-control delivery-date" value="${med.dateDelivered}" required></td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-2">
                <button class="btn btn-success btn-sm" onclick="saveUpdatedMedicine(${index})">Save</button>
                <button class="btn btn-secondary btn-sm" onclick="loadInventory()">Cancel</button>
            </div>
        </td>
    `;
    
    console.log(`Editing medicine: ${med.medicine}`);
}

/**
 * Saves updated medicine information
 */
function saveUpdatedMedicine(index) {
    console.log("Saving updated medicine...");
    const row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    const inputs = {
        medicine: row.querySelector(".medicine-name"),
        brand: row.querySelector(".brand-name"),
        category: row.querySelector(".category"),
        quantity: row.querySelector(".quantity"),
        expirationDate: row.querySelector(".expiration-date"),
        dateDelivered: row.querySelector(".delivery-date")
    };

    let isValid = true;
    Object.entries(inputs).forEach(([field, input]) => {
        if (!input.value.trim()) {
            showError(input, "This field is required");
            isValid = false;
        } else if (field === "quantity" && (isNaN(input.value) || parseInt(input.value) < 1)) {
            showError(input, "Quantity must be ≥ 1");
            isValid = false;
        } else {
            clearError(input);
        }
    });

    if (!isValid) {
        console.log("Validation failed for medicine update");
        return;
    }

    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    inventory[index] = {
        medicine: inputs.medicine.value.trim(),
        brand: inputs.brand.value.trim(),
        category: inputs.category.value.trim(),
        quantity: parseInt(inputs.quantity.value),
        expirationDate: inputs.expirationDate.value,
        dateDelivered: inputs.dateDelivered.value
    };
    
    inventory.sort((a, b) => new Date(a.expirationDate) - new Date(b.expirationDate));
    
    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
    updateNotifications();
    
    console.log(`Medicine updated: ${inputs.medicine.value.trim()}`);
}

// ==================== EXPORT FUNCTIONS ====================

/**
 * Exports inventory to CSV
 */
function exportToCSV() {
    console.log("Exporting inventory to CSV...");
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    if (!inventory.length) return alert("No data to export!");

    const headers = ["Medicine", "Brand", "Category", "Quantity", "Expiration Date", "Delivery Date"];
    const csvContent = [
        headers.join(","),
        ...inventory.map(item => [
            `"${item.medicine}"`,
            `"${item.brand}"`,
            `"${item.category}"`,
            item.quantity,
            `"${formatDate(item.expirationDate)}"`,
            `"${formatDate(item.dateDelivered)}"`
        ].join(","))
    ].join("\n");

    downloadCSV(csvContent, `medicine_inventory_${new Date().toISOString().slice(0, 10)}.csv`);
    console.log("Inventory export complete");
}

/**
 * Exports dispensed medicines to CSV
 */
function exportDispensedToCSV() {
    console.log("Exporting dispensed medicines to CSV...");
    const dispensedMedicines = JSON.parse(localStorage.getItem("dispensedMedicines")) || [];
    if (!dispensedMedicines.length) return alert("No dispensed medicines to export!");

    const headers = ["Medicine", "Brand", "Category", "Quantity Dispensed", "Date Dispensed", "Expiration Date"];
    const csvContent = [
        headers.join(","),
        ...dispensedMedicines.map(item => [
            `"${item.medicine}"`,
            `"${item.brand}"`,
            `"${item.category}"`,
            item.quantity,
            `"${formatDate(item.dateDispensed)}"`,
            `"${formatDate(item.expirationDate)}"`
        ].join(","))
    ].join("\n");

    downloadCSV(csvContent, `dispensed_medicines_${new Date().toISOString().slice(0, 10)}.csv`);
    console.log("Dispensed medicines export complete");
}

/**
 * Downloads CSV file
 */
function downloadCSV(content, filename) {
    console.log(`Downloading CSV: ${filename}`);
    const blob = new Blob([content], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// ==================== NOTIFICATION FUNCTIONS ====================

/**
 * Checks for expiring medicines
 */
function checkMedicineExpirations() {
    console.log("Checking medicine expirations...");
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const now = new Date();
    const warningDays = 30;
    
    let expiredItems = [];
    let nearExpiryItems = [];

    inventory.forEach(med => {
        const expDate = new Date(med.expirationDate);
        const daysToExpire = Math.floor((expDate - now) / (1000 * 60 * 60 * 24));
        
        if (daysToExpire < 0) {
            expiredItems.push({
                medicine: med.medicine,
                brand: med.brand,
                quantity: med.quantity,
                expirationDate: med.expirationDate,
                days: Math.abs(daysToExpire)
            });
        } else if (daysToExpire <= warningDays) {
            nearExpiryItems.push({
                medicine: med.medicine,
                brand: med.brand,
                quantity: med.quantity,
                expirationDate: med.expirationDate,
                days: daysToExpire
            });
        }
    });

    console.log(`Found ${expiredItems.length} expired and ${nearExpiryItems.length} near-expiry items`);
    return { expiredItems, nearExpiryItems };
}

/**
 * Updates notification badges and content
 */
function updateNotifications() {
    console.log("Updating notifications...");
    const { expiredItems, nearExpiryItems } = checkMedicineExpirations();
    const totalAlerts = expiredItems.length + nearExpiryItems.length;
    const badge = document.getElementById('notificationBadge');
    const content = document.getElementById('notificationContent');

    badge.classList.toggle('d-none', totalAlerts === 0);

    if (totalAlerts === 0) {
        content.innerHTML = '<li class="px-3 py-2 text-muted">No expiration warnings</li>';
    } else {
        content.innerHTML = [
            ...expiredItems.map(item => `
                <li class="expired-notification">
                    <strong>${item.medicine} (${item.brand})</strong><br>
                    <small>Quantity: ${item.quantity} | Expired ${item.days} day${item.days === 1 ? '' : 's'} ago</small><br>
                    <small>Exp: ${formatDate(item.expirationDate)}</small>
                </li>
            `),
            ...nearExpiryItems.map(item => `
                <li class="near-expiry-notification">
                    <strong>${item.medicine} (${item.brand})</strong><br>
                    <small>Quantity: ${item.quantity} | Expires in ${item.days} day${item.days === 1 ? '' : 's'}</small><br>
                    <small>Exp: ${formatDate(item.expirationDate)}</small>
                </li>
            `)
        ].join('');
    }
    
    console.log("Notifications updated");
}

/**
 * Refreshes notifications
 */
function refreshNotifications() {
    console.log("Refreshing notifications...");
    updateNotifications();
}