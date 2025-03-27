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
        <td><input type="number" class="form-control quantity" min="1" oninput="validateQuantity(this)"></td>
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
        showValidationError(input, "Category cannot be empty");
    } else {
        clearValidationError(input);
    }
}

// Validate quantity (no negatives or decimals)
function validateQuantity(input) {
    if (input.value < 1 || !Number.isInteger(Number(input.value))) {
        input.value = "";
    }
}

// Show validation error
function showValidationError(input, message) {
    input.classList.add("error");

    let errorText = input.nextElementSibling;
    if (!errorText || !errorText.classList.contains("error-text")) {
        errorText = document.createElement("div");
        errorText.classList.add("error-text");
        errorText.textContent = message;
        input.parentNode.appendChild(errorText);
    }
}

// Clear validation error
function clearValidationError(input) {
    input.classList.remove("error");
    if (input.nextElementSibling && input.nextElementSibling.classList.contains("error-text")) {
        input.nextElementSibling.remove();
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
                showValidationError(input, value === "" ? "This field is required" : "Quantity must be greater than 0");
                isValid = false;
            } else {
                clearValidationError(input);
            }
        });
    });

    return isValid;
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

// Save updates to medicine
function saveUpdatedMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);

    let updatedMedicine = {
        medicine: row.querySelector(".medicine-name").value,
        brand: row.querySelector(".brand-name").value,
        category: row.querySelector(".category").value,
        quantity: row.querySelector(".quantity").value,
        expirationDate: row.querySelector(".expiration-date").value,
        dateDelivered: row.querySelector(".delivery-date").value,
    };

    inventory[index] = updatedMedicine;
    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
}
