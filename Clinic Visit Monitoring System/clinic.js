document.addEventListener("DOMContentLoaded", function () {
    highlightActivePage();
    loadInventory();
    loadRecentStocks();
});

// Highlight active sidebar link
function highlightActivePage() {
    let currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".sidebar a").forEach(link => {
        if (link.getAttribute("href") === currentPage) {
            link.classList.add("active");
        }
    });
}

// Add a new stock entry row
function addStockRow() {
    let tableBody = document.querySelector("#stockTable tbody");
    let row = document.createElement("tr");

    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name"></td>
        <td><input type="text" class="form-control brand-name"></td>
        <td>
            <select class="form-control category" onchange="handleCategoryChange(this)">
                <option value="Pain Reliever">Pain Reliever</option>
                <option value="Antibiotic">Antibiotic</option>
                <option value="Antiseptic">Antiseptic</option>
                <option value="Vitamin">Vitamin</option>
                <option value="Other">Other</option>
            </select>
        </td>
        <td><input type="number" class="form-control quantity" style="width: 80px;" min="1" oninput="validateQuantity(this)"></td>
        <td><input type="date" class="form-control expiration-date"></td>
        <td><input type="date" class="form-control delivery-date"></td>
        <td><button class="btn btn-danger btn-sm" onclick="deleteRow(this)">🗑</button></td>
    `;

    tableBody.appendChild(row);
}

// Handle category "Other" selection
function handleCategoryChange(select) {
    if (select.value === "Other") {
        let input = document.createElement("input");
        input.type = "text";
        input.classList.add("form-control", "category-input");
        input.placeholder = "Enter category";
        input.setAttribute("onblur", "validateCategoryInput(this)");

        select.parentNode.replaceChild(input, select);
        input.focus();
    }
}

// Validate category input if "Other" is selected
function validateCategoryInput(input) {
    if (input.value.trim() === "") {
        showError(input, "Category cannot be empty");
    } else {
        clearError(input);
    }
}

// Validate quantity (no negatives or decimals)
function validateQuantity(input) {
    if (input.value < 1 || !Number.isInteger(Number(input.value))) {
        input.value = "";
        showError(input, "Quantity must be a whole number greater than 0");
    } else {
        clearError(input);
    }
}

// Delete stock entry row
function deleteRow(button) {
    button.closest("tr").remove();
}

// Confirm save function with validation
function confirmSave() {
    if (validateInputs()) {
        if (confirm("Are you sure you want to add this?")) {
            saveStock();
        }
    }
}

// Validate all input fields
function validateInputs() {
    let isValid = true;
    document.querySelectorAll("#stockTable tbody tr, #inventoryTableBody tr").forEach(row => {
        row.querySelectorAll("input, select").forEach(input => {
            let value = input.value.trim();
            let isQuantityField = input.classList.contains("quantity");

            if (!value || (isQuantityField && (!/^[1-9]\d*$/.test(value)))) {
                showError(input, value === "" ? "This field is required" : "Quantity must be greater than 0");
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
    // Remove any existing error first
    clearError(input);
    
    // Add error class to input
    input.classList.add("is-invalid");
    
    // Create error message element
    let errorText = document.createElement("div");
    errorText.classList.add("invalid-feedback");
    errorText.textContent = message;
    
    // Insert after the input
    input.parentNode.appendChild(errorText);
}

// Clear validation error
function clearError(input) {
    input.classList.remove("is-invalid");
    let errorText = input.nextElementSibling;
    if (errorText && errorText.classList.contains("invalid-feedback")) {
        errorText.remove();
    }
}

// Save stock function
function saveStock() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];

    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        let medicine = row.querySelector(".medicine-name").value;
        let brand = row.querySelector(".brand-name").value;
        let category = row.querySelector(".category") ? row.querySelector(".category").value : row.querySelector(".category-input").value;
        let quantity = parseInt(row.querySelector(".quantity").value);
        let expirationDate = row.querySelector(".expiration-date").value;
        let dateDelivered = row.querySelector(".delivery-date").value;

        let newStock = { medicine, brand, category, quantity, expirationDate, dateDelivered };
        inventory.push(newStock);
        recentStocks.unshift(newStock);

        row.remove();
    });

    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    localStorage.setItem("recentStocks", JSON.stringify(recentStocks));

    loadInventory();
    loadRecentStocks();
}

// Load inventory function
function loadInventory() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    inventory.sort((a, b) => new Date(a.expirationDate) - new Date(b.expirationDate));

    let tableBody = document.querySelector("#inventoryTableBody");
    tableBody.innerHTML = "";

    inventory.forEach((med, index) => {
        let row = `<tr>
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
        </tr>`;
        tableBody.innerHTML += row;
    });
}

// Dispense medicine
function dispenseMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    if (confirm("Are you sure you want to dispense this medicine?")) {
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}

// Delete medicine from inventory
function deleteMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    if (confirm("Are you sure you want to delete this medicine?")) {
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}

// Edit medicine function
function editMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let med = inventory[index];

    let row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name" value="${med.medicine}"></td>
        <td><input type="text" class="form-control brand-name" value="${med.brand}"></td>
        <td><input type="text" class="form-control category" value="${med.category}"></td>
        <td><input type="number" class="form-control quantity" style="width: 80px;" min="1" value="${med.quantity}" oninput="validateQuantity(this)"></td>
        <td><input type="date" class="form-control expiration-date" value="${med.expirationDate}"></td>
        <td><input type="date" class="form-control delivery-date" value="${med.dateDelivered}"></td>
        <td>
            <button class="btn btn-success btn-sm" onclick="saveUpdatedMedicine(${index})">💾 Save</button>
            <button class="btn btn-secondary btn-sm" onclick="loadInventory()">❌ Cancel</button>
        </td>
    `;
}

// Save updated medicine function with validation
function saveUpdatedMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    
    // Clear any existing errors first
    row.querySelectorAll("input").forEach(input => clearError(input));
    
    // Get all values
    let inputs = {
        medicine: row.querySelector(".medicine-name"),
        brand: row.querySelector(".brand-name"),
        category: row.querySelector(".category"),
        quantity: row.querySelector(".quantity"),
        expirationDate: row.querySelector(".expiration-date"),
        dateDelivered: row.querySelector(".delivery-date")
    };
    
    // Validate each field
    let isValid = true;
    
    // Validate medicine name
    if (!inputs.medicine.value.trim()) {
        showError(inputs.medicine, "Medicine name is required");
        isValid = false;
    }
    
    // Validate brand name
    if (!inputs.brand.value.trim()) {
        showError(inputs.brand, "Brand name is required");
        isValid = false;
    }
    
    // Validate category
    if (!inputs.category.value.trim()) {
        showError(inputs.category, "Category is required");
        isValid = false;
    }
    
    // Validate quantity
    if (!inputs.quantity.value || isNaN(inputs.quantity.value) || parseInt(inputs.quantity.value) < 1) {
        showError(inputs.quantity, "Valid quantity is required (minimum 1)");
        isValid = false;
    }
    
    // Validate expiration date
    if (!inputs.expirationDate.value) {
        showError(inputs.expirationDate, "Expiration date is required");
        isValid = false;
    }
    
    // Validate delivery date
    if (!inputs.dateDelivered.value) {
        showError(inputs.dateDelivered, "Delivery date is required");
        isValid = false;
    }
    
    // If validation failed, stop here
    if (!isValid) {
        return;
    }
    
    // If all valid, update the inventory
    let updatedMedicine = {
        medicine: inputs.medicine.value.trim(),
        brand: inputs.brand.value.trim(),
        category: inputs.category.value.trim(),
        quantity: parseInt(inputs.quantity.value),
        expirationDate: inputs.expirationDate.value,
        dateDelivered: inputs.dateDelivered.value
    };

    inventory[index] = updatedMedicine;
    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
}

// Load recent stocks
function loadRecentStocks() {
    let recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];
    let tableBody = document.querySelector("#recentStocksTable tbody");
    tableBody.innerHTML = "";

    recentStocks.slice(0, 5).forEach(stock => {
        let row = `<tr>
            <td>${stock.medicine}</td>
            <td>${stock.brand}</td>
            <td>${stock.category}</td>
            <td>${stock.quantity}</td>
            <td>${stock.expirationDate}</td>
            <td>${stock.dateDelivered}</td>
        </tr>`;
        tableBody.innerHTML += row;
    });
}

// Export to CSV function
function exportToCSV() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    
    if (inventory.length === 0) {
        alert("No data to export!");
        return;
    }

    // CSV header
    let csv = "Medicine,Brand,Category,Quantity,Expiration Date,Delivery Date\n";
    
    // Add each row
    inventory.forEach(item => {
        csv += `"${item.medicine}","${item.brand}","${item.category}",${item.quantity},"${item.expirationDate}","${item.dateDelivered}"\n`;
    });

    // Create download link
    let blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    let url = URL.createObjectURL(blob);
    let link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", "medicine_inventory.csv");
    link.style.visibility = "hidden";
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}