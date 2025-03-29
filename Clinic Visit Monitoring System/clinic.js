document.addEventListener("DOMContentLoaded", function () {
    highlightActivePage();
    loadInventory();
    loadRecentStocks();
    loadDispensedMedicines();
    updateNotifications()
    
    // Initialize search functionality for reports page
    if (document.getElementById('searchMedicine')) {
        document.getElementById('searchMedicine').addEventListener('input', searchInventory);
    }
});

// Highlight active sidebar link
function highlightActivePage() {
    const currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".sidebar a").forEach(link => {
        link.classList.toggle("active", link.getAttribute("href") === currentPage);
    });
}

// Add a new stock entry row
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

// Handle category "Other" selection
function handleCategoryChange(select) {
    if (select.value === "Other") {
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

function validateQuantity(input) {
    const value = parseInt(input.value);
    if (isNaN(value) || value < 1) {
        showError(input, "Quantity must be ≥ 1");
        return false;
    }
    clearError(input);
    return true;
}

// Delete stock entry row
function deleteRow(button) {
    button.closest("tr").remove();
}

// Validate all input fields before saving
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

// Show validation error
function showError(input, message) {
    clearError(input);
    input.classList.add("is-invalid");
    const errorDiv = document.createElement("div");
    errorDiv.className = "invalid-feedback";
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

// Clear validation error
function clearError(input) {
    input.classList.remove("is-invalid");
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains("invalid-feedback")) {
        errorDiv.remove();
    }
}

// Confirm save with validation
function confirmSave() {
    if (validateInputs()) {
        if (confirm("Are you sure you want to save these entries?")) {
            saveStock();
        }
    }
}

// Save stock to localStorage
function saveStock() {
    if (!validateInputs()) {
        return;
    }

    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];

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
        }
    });

    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    localStorage.setItem("recentStocks", JSON.stringify(recentStocks));
    
    // Clear the input table
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
    
    // Update both tables
    loadInventory();
    loadRecentStocks();
    
    alert("Inventory saved successfully!");
}
// Load inventory with sorting by expiration date
function loadInventory() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    
    // Sort by nearest expiration date first
    inventory.sort((a, b) => {
        const dateA = new Date(a.expirationDate);
        const dateB = new Date(b.expirationDate);
        return dateA - dateB;
    });

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
                <td class="text-center actions-cell">
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-warning btn-sm" onclick="dispenseMedicine(${index})">Dispense</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="editMedicine(${index})">Edit</button>
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteMedicine(${index})">Delete</button>
                    </div>
                </td>
            </tr>
        `).join("");
    }
}

// Load recent stocks
function loadRecentStocks() {
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
    }
}

// Load dispensed medicines
function loadDispensedMedicines() {
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
    }
}
// Format date to MM/DD/YYYY
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${month}/${day}/${year}`;
}

// Search functionality for reports page
function searchInventory() {
    const searchTerm = document.getElementById('searchMedicine').value.toLowerCase();
    const rows = document.querySelectorAll("#inventoryTableBody tr");
    
    rows.forEach(row => {
        const medicineName = row.cells[0].textContent.toLowerCase();
        row.style.display = medicineName.includes(searchTerm) ? "" : "none";
    });
}

// Filter by category
function filterInventory() {
    const category = document.getElementById('filterCategory').value;
    const rows = document.querySelectorAll("#inventoryTableBody tr");
    
    rows.forEach(row => {
        const rowCategory = row.cells[2].textContent;
        row.style.display = !category || rowCategory === category ? "" : "none";
    });
}

// Dispense medicine - updated to track dispensed items
function dispenseMedicine(index) {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const medicine = inventory[index];
    
    const quantity = prompt(`How many units of ${medicine.medicine} (${medicine.brand}) would you like to dispense? Current quantity: ${medicine.quantity}`);
    
    if (quantity === null) return; // User cancelled
    
    const quantityNum = parseInt(quantity);
    if (isNaN(quantityNum)) {
        alert("Please enter a valid number");
        return;
    }
    
    if (quantityNum <= 0) {
        alert("Quantity must be greater than 0");
        return;
    }
    
    if (quantityNum > medicine.quantity) {
        alert(`Cannot dispense more than available quantity (${medicine.quantity})`);
        return;
    }
    
    if (confirm(`Are you sure you want to dispense ${quantityNum} units of ${medicine.medicine} (${medicine.brand})?`)) {
        // Update inventory
        if (quantityNum === medicine.quantity) {
            inventory.splice(index, 1);
        } else {
            inventory[index].quantity -= quantityNum;
        }
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        
        // Add to dispensed medicines
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
        
        // Reload tables
        loadInventory();
        loadDispensedMedicines();
        
        alert(`${quantityNum} units of ${medicine.medicine} dispensed successfully!`);
    }
}

// Delete medicine from inventory
function deleteMedicine(index) {
    if (confirm("Are you sure you want to delete this medicine?")) {
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}

// Edit medicine function
function editMedicine(index) {
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
        <td class="text-center actions-cell">
            <div class="d-flex justify-content-center gap-2">
                <button class="btn btn-success btn-sm" onclick="saveUpdatedMedicine(${index})">Save</button>
                <button class="btn btn-secondary btn-sm" onclick="loadInventory()">Cancel</button>
            </div>
        </td>
    `;
}

// Save updated medicine
function saveUpdatedMedicine(index) {
    const row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    const inputs = {
        medicine: row.querySelector(".medicine-name"),
        brand: row.querySelector(".brand-name"),
        category: row.querySelector(".category"),
        quantity: row.querySelector(".quantity"),
        expirationDate: row.querySelector(".expiration-date"),
        dateDelivered: row.querySelector(".delivery-date")
    };

    // Validate inputs
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

    if (!isValid) return;

    // Update inventory
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    inventory[index] = {
        medicine: inputs.medicine.value.trim(),
        brand: inputs.brand.value.trim(),
        category: inputs.category.value.trim(),
        quantity: parseInt(inputs.quantity.value),
        expirationDate: inputs.expirationDate.value,
        dateDelivered: inputs.dateDelivered.value
    };
    
    // Re-sort inventory by expiration date
    inventory.sort((a, b) => new Date(a.expirationDate) - new Date(b.expirationDate));
    
    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
}

// Export to CSV function
function exportToCSV() {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    if (!inventory.length) return alert("No data to export!");

    const headers = ["Medicine", "Brand", "Category", "Quantity", "Expiration Date", "Delivery Date"];
    const csvContent = [
        headers.join(","),
        ...inventory.map(item => headers.map(header => 
            `"${item[header.toLowerCase().replace(' ', '')]}"`
        ).join(","))
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `medicine_inventory_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Export dispensed medicines to CSV
function exportDispensedToCSV() {
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

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `dispensed_medicines_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Check medicine expirations and update notifications
function checkMedicineExpirations() {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const now = new Date();
    const warningDays = 30; // Show warning 30 days before expiration
    
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

    return { expiredItems, nearExpiryItems };
}

// Update notification badge and content
function updateNotifications() {
    const { expiredItems, nearExpiryItems } = checkMedicineExpirations();
    const totalAlerts = expiredItems.length + nearExpiryItems.length;
    const badge = document.getElementById('notificationBadge');
    const content = document.getElementById('notificationContent');

    // Update badge
    if (totalAlerts > 0) {
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }

    // Update dropdown content
    if (totalAlerts === 0) {
        content.innerHTML = '<li class="px-3 py-2 text-muted">No expiration warnings</li>';
    } else {
        let html = '';
        
        // Add expired items first (red warnings)
        expiredItems.forEach(item => {
            html += `
                <li class="expired-notification">
                    <strong>${item.medicine} (${item.brand})</strong><br>
                    <small>Quantity: ${item.quantity} | Expired ${item.days} day${item.days === 1 ? '' : 's'} ago</small><br>
                    <small>Exp: ${formatDate(item.expirationDate)}</small>
                </li>
            `;
        });

        // Add near-expiry items (orange warnings)
        nearExpiryItems.forEach(item => {
            html += `
                <li class="near-expiry-notification">
                    <strong>${item.medicine} (${item.brand})</strong><br>
                    <small>Quantity: ${item.quantity} | Expires in ${item.days} day${item.days === 1 ? '' : 's'}</small><br>
                    <small>Exp: ${formatDate(item.expirationDate)}</small>
                </li>
            `;
        });

        content.innerHTML = html;
    }
}

// Call this whenever inventory changes (after save, delete, etc.)
function refreshNotifications() {
    updateNotifications();
}
// Add to saveStock() function after saving:
function saveStock() {
    // ... existing save code ...
    refreshNotifications();
}

// Add to deleteMedicine() function:
function deleteMedicine(index) {
    // ... existing delete code ...
    refreshNotifications();
}

// Add to dispenseMedicine() function:
function dispenseMedicine(index) {
    // ... existing dispense code ...
    refreshNotifications();
}