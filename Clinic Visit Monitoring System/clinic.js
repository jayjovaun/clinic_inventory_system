document.addEventListener("DOMContentLoaded", function () {
    highlightActivePage();
    loadInventory();
    loadRecentStocks();
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
        <td><input type="number" class="form-control quantity" min="1" required></td>
        <td><input type="date" class="form-control expiration-date" required></td>
        <td><input type="date" class="form-control delivery-date" required></td>
        <td><button class="btn btn-danger btn-sm" onclick="deleteRow(this)">🗑</button></td>
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
        input.onblur = () => validateCategoryInput(input);
        select.replaceWith(input);
        input.focus();
    }
}

// Validate category input if "Other" is selected
function validateCategoryInput(input) {
    if (!input.value.trim()) {
        showError(input, "Category cannot be empty");
        return false;
    }
    clearError(input);
    return true;
}

// Validate quantity (no negatives or decimals)
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

// Confirm save function with validation
function confirmSave() {
    if (validateAllInputs()) {
        if (confirm("Are you sure you want to add this?")) {
            saveStock();
        }
    }
}

// Validate all input fields
function validateAllInputs() {
    let isValid = true;
    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        row.querySelectorAll("input, select").forEach(input => {
            if (input.classList.contains("quantity")) {
                isValid = validateQuantity(input) && isValid;
            } else if (input.classList.contains("category-input")) {
                isValid = validateCategoryInput(input) && isValid;
            } else if (!input.value.trim()) {
                showError(input, "This field is required");
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
    const errorText = document.createElement("div");
    errorText.className = "invalid-feedback";
    errorText.textContent = message;
    input.parentNode.appendChild(errorText);
}

// Clear validation error
function clearError(input) {
    input.classList.remove("is-invalid");
    const errorText = input.nextElementSibling;
    if (errorText?.classList.contains("invalid-feedback")) {
        errorText.remove();
    }
}

// Save stock function
function saveStock() {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];

    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        const newStock = {
            medicine: row.querySelector(".medicine-name").value.trim(),
            brand: row.querySelector(".brand-name").value.trim(),
            category: row.querySelector(".category, .category-input").value.trim(),
            quantity: parseInt(row.querySelector(".quantity").value),
            expirationDate: row.querySelector(".expiration-date").value,
            dateDelivered: row.querySelector(".delivery-date").value
        };
        inventory.push(newStock);
        recentStocks.unshift(newStock);
    });

    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    localStorage.setItem("recentStocks", JSON.stringify(recentStocks));
    document.querySelector("#stockTable tbody").innerHTML = "";
    loadInventory();
    loadRecentStocks();
}

// Load inventory function
function loadInventory() {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    inventory.sort((a, b) => new Date(a.expirationDate) - new Date(b.expirationDate));
    
    const tableBody = document.querySelector("#inventoryTableBody");
    tableBody.innerHTML = inventory.map((med, index) => `
        <tr>
            <td>${med.medicine}</td>
            <td>${med.brand}</td>
            <td>${med.category}</td>
            <td>${med.quantity}</td>
            <td>${med.expirationDate}</td>
            <td>${med.dateDelivered}</td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="dispenseMedicine(${index})">➖ Dispense</button>
                <button class="btn btn-primary btn-sm" onclick="editMedicine(${index})">✏ Update</button>
                <button class="btn btn-danger btn-sm" onclick="deleteMedicine(${index})">🗑 Delete</button>
            </td>
        </tr>
    `).join("");
}

// Dispense medicine
function dispenseMedicine(index) {
    if (confirm("Are you sure you want to dispense this medicine?")) {
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
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
        <td><input type="number" class="form-control quantity" min="1" value="${med.quantity}" required></td>
        <td><input type="date" class="form-control expiration-date" value="${med.expirationDate}" required></td>
        <td><input type="date" class="form-control delivery-date" value="${med.dateDelivered}" required></td>
        <td>
            <button class="btn btn-success btn-sm" onclick="saveUpdatedMedicine(${index})">💾 Save</button>
            <button class="btn btn-secondary btn-sm" onclick="loadInventory()">❌ Cancel</button>
        </td>
    `;
}

// Save updated medicine function with validation
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

    // Clear errors and validate
    let isValid = true;
    Object.entries(inputs).forEach(([field, input]) => {
        clearError(input);
        if (!input.value.trim()) {
            showError(input, `${field.replace(/^\w/, c => c.toUpperCase())} is required`);
            isValid = false;
        } else if (field === "quantity" && (isNaN(input.value) || parseInt(input.value) < 1) {
            showError(input, "Quantity must be ≥ 1");
            isValid = false;
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
    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
}

// Load recent stocks
function loadRecentStocks() {
    const recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];
    const tableBody = document.querySelector("#recentStocksTable tbody");
    tableBody.innerHTML = recentStocks.slice(0, 5).map(stock => `
        <tr>
            <td>${stock.medicine}</td>
            <td>${stock.brand}</td>
            <td>${stock.category}</td>
            <td>${stock.quantity}</td>
            <td>${stock.expirationDate}</td>
            <td>${stock.dateDelivered}</td>
        </tr>
    `).join("");
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
    link.download = "medicine_inventory_" + new Date().toISOString().slice(0, 10) + ".csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}