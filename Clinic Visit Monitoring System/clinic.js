/**
 * Clinic Inventory Management System
 * Comprehensive JavaScript for all inventory operations
 */

document.addEventListener("DOMContentLoaded", function() {
    initApplication();
});

// Initialize application components
function initApplication() {
    highlightActiveNav();
    if (document.getElementById("inventoryTableBody")) {
        loadInventory();
    }
    setupEventListeners();
}

// Highlight active navigation link
function highlightActiveNav() {
    const currentPage = window.location.pathname.split("/").pop() || "index.html";
    document.querySelectorAll(".sidebar a").forEach(link => {
        link.classList.toggle("active", link.getAttribute("href") === currentPage);
    });
}

// Format date for display (dd/mm/yyyy)
function formatDisplayDate(dateString) {
    if (!dateString) return '-';
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    } catch (e) {
        console.error("Date formatting error:", e);
        return '-';
    }
}

// Format date for input fields (yyyy-mm-dd)
function formatInputDate(dateString) {
    if (!dateString) return '';
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        
        return date.toISOString().split('T')[0];
    } catch (e) {
        console.error("Date formatting error:", e);
        return '';
    }
}

// Load and display inventory data
function loadInventory() {
    try {
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        
        // Sort by expiration date (nearest first)
        inventory.sort((a, b) => {
            const dateA = new Date(a.expirationDate || '9999-12-31');
            const dateB = new Date(b.expirationDate || '9999-12-31');
            return dateA - dateB;
        });

        const tableBody = document.getElementById("inventoryTableBody");
        if (!tableBody) return;

        tableBody.innerHTML = inventory.map((medicine, index) => `
            <tr data-medicine-id="${index}">
                <td>${medicine.medicine || '-'}</td>
                <td>${medicine.brand || '-'}</td>
                <td>${medicine.category || '-'}</td>
                <td class="text-center ${getQuantityClass(medicine.quantity)}">
                    ${medicine.quantity || '0'}
                </td>
                <td class="text-center ${getExpirationClass(medicine.expirationDate)}">
                    ${formatDisplayDate(medicine.expirationDate)}
                </td>
                <td class="text-center">${formatDisplayDate(medicine.dateDelivered)}</td>
                <td class="text-center actions-cell">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="dispenseMedicine(${index})" 
                            title="Dispense Medicine" ${medicine.quantity <= 0 ? 'disabled' : ''}>
                            <i class="fas fa-pills"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="editMedicine(${index})" 
                            title="Edit Record">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteMedicine(${index})" 
                            title="Delete Record">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join("");
    } catch (error) {
        console.error("Error loading inventory:", error);
        showAlert("Failed to load inventory data", "danger");
    }
}

// Get CSS class for quantity display
function getQuantityClass(quantity) {
    quantity = parseInt(quantity) || 0;
    if (quantity <= 0) return 'text-danger fw-bold';
    if (quantity <= 10) return 'text-warning';
    return '';
}

// Get CSS class for expiration date
function getExpirationClass(expirationDate) {
    if (!expirationDate) return '';
    
    try {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const expDate = new Date(expirationDate);
        expDate.setHours(0, 0, 0, 0);
        
        const diffDays = Math.floor((expDate - today) / (1000 * 60 * 60 * 24));
        
        if (diffDays < 0) return 'text-danger fw-bold'; // Expired
        if (diffDays <= 30) return 'text-warning'; // Expiring soon
        return '';
    } catch (e) {
        return '';
    }
}

// Dispense medicine from inventory
function dispenseMedicine(index) {
    try {
        if (!confirm("Are you sure you want to dispense this medicine?")) return;
        
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        if (index < 0 || index >= inventory.length) {
            throw new Error("Invalid medicine index");
        }
        
        // Decrement quantity
        if (inventory[index].quantity > 1) {
            inventory[index].quantity -= 1;
            localStorage.setItem("medicineInventory", JSON.stringify(inventory));
            showAlert("Medicine dispensed successfully", "success");
        } else {
            // Remove if last item
            inventory.splice(index, 1);
            localStorage.setItem("medicineInventory", JSON.stringify(inventory));
            showAlert("Last item dispensed - removed from inventory", "info");
        }
        
        loadInventory();
    } catch (error) {
        console.error("Error dispensing medicine:", error);
        showAlert("Failed to dispense medicine", "danger");
    }
}

// Delete medicine from inventory
function deleteMedicine(index) {
    try {
        if (!confirm("Are you sure you want to permanently delete this record?")) return;
        
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        if (index < 0 || index >= inventory.length) {
            throw new Error("Invalid medicine index");
        }
        
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        
        showAlert("Record deleted successfully", "success");
        loadInventory();
    } catch (error) {
        console.error("Error deleting medicine:", error);
        showAlert("Failed to delete record", "danger");
    }
}

// Switch to edit mode for a medicine record
function editMedicine(index) {
    try {
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        if (index < 0 || index >= inventory.length) {
            throw new Error("Invalid medicine index");
        }
        
        const medicine = inventory[index];
        const row = document.querySelector(`#inventoryTableBody tr[data-medicine-id="${index}"]`);
        
        if (!row) return;
        
        row.innerHTML = `
            <td>
                <input type="text" class="form-control form-control-sm" 
                    value="${medicine.medicine || ''}" required>
                <div class="invalid-feedback">Medicine name required</div>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" 
                    value="${medicine.brand || ''}">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" 
                    value="${medicine.category || ''}">
            </td>
            <td class="text-center">
                <input type="number" class="form-control form-control-sm quantity-input" 
                    value="${medicine.quantity || 0}" min="0" required>
                <div class="invalid-feedback">Valid quantity required</div>
            </td>
            <td>
                <input type="date" class="form-control form-control-sm" 
                    value="${formatInputDate(medicine.expirationDate)}">
            </td>
            <td>
                <input type="date" class="form-control form-control-sm" 
                    value="${formatInputDate(medicine.dateDelivered)}">
            </td>
            <td class="text-center actions-cell">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-success" onclick="saveUpdatedMedicine(${index})">
                        <i class="fas fa-check"></i> Save
                    </button>
                    <button class="btn btn-secondary" onclick="loadInventory()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </td>
        `;
        
        // Focus first field
        row.querySelector('input').focus();
    } catch (error) {
        console.error("Error editing medicine:", error);
        showAlert("Failed to edit record", "danger");
    }
}

// Save updated medicine information
function saveUpdatedMedicine(index) {
    try {
        const row = document.querySelector(`#inventoryTableBody tr[data-medicine-id="${index}"]`);
        if (!row) return;
        
        const inputs = row.querySelectorAll('input');
        const [nameInput, brandInput, categoryInput, quantityInput, expInput, deliveryInput] = inputs;
        
        // Validate required fields
        let isValid = true;
        
        if (!nameInput.value.trim()) {
            nameInput.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!quantityInput.value || isNaN(quantityInput.value) || quantityInput.value < 0) {
            quantityInput.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            showAlert("Please fill all required fields with valid values", "warning");
            return;
        }
        
        // Update inventory
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        
        inventory[index] = {
            medicine: nameInput.value.trim(),
            brand: brandInput.value.trim(),
            category: categoryInput.value.trim(),
            quantity: parseInt(quantityInput.value),
            expirationDate: expInput.value || null,
            dateDelivered: deliveryInput.value || null
        };
        
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        showAlert("Record updated successfully", "success");
        loadInventory();
    } catch (error) {
        console.error("Error saving medicine:", error);
        showAlert("Failed to update record", "danger");
    }
}

// Export inventory data to CSV
function exportToCSV() {
    try {
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        if (inventory.length === 0) {
            showAlert("No data available to export", "warning");
            return;
        }
        
        // Prepare CSV headers
        const headers = [
            "Medicine Name",
            "Brand",
            "Category",
            "Quantity",
            "Expiration Date",
            "Delivery Date"
        ];
        
        // Prepare CSV rows
        const rows = inventory.map(item => [
            `"${escapeCsv(item.medicine || '')}"`,
            `"${escapeCsv(item.brand || '')}"`,
            `"${escapeCsv(item.category || '')}"`,
            item.quantity || 0,
            `"${formatDisplayDate(item.expirationDate)}"`,
            `"${formatDisplayDate(item.dateDelivered)}"`
        ]);
        
        // Combine into CSV content
        const csvContent = [
            headers.join(","),
            ...rows.map(row => row.join(","))
        ].join("\n");
        
        // Create download link
        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        const timestamp = new Date().toISOString().replace(/[:.]/g, "-");
        
        link.href = url;
        link.download = `medicine-inventory-${timestamp}.csv`;
        link.style.display = "none";
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        showAlert("Export completed successfully", "success");
    } catch (error) {
        console.error("Error exporting CSV:", error);
        showAlert("Failed to export data", "danger");
    }
}

// Escape special characters for CSV
function escapeCsv(text) {
    return text.toString()
        .replace(/"/g, '""')
        .replace(/\n/g, ' ')
        .replace(/\r/g, ' ');
}

// Display alert message
function showAlert(message, type = "info") {
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = "alert";
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector(".main-content") || document.body;
    container.prepend(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
        alert.close();
    }, 5000);
}

// Initialize event listeners
function setupEventListeners() {
    // Add any global event listeners here
}