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
        select.parentNode.replaceChild(input, select);
        input.focus();
    }
}

// Validate quantity
function validateQuantity(input) {
    if (input.value < 1 || !Number.isInteger(Number(input.value))) {
        input.value = "";
    }
}

// Delete stock entry row
function deleteRow(button) {
    button.closest("tr").remove();
}

// Save stock with validation
function confirmSave() {
    if (validateInputs()) {
        if (confirm("Are you sure you want to add this?")) {
            saveStock();
        }
    }
}

// Validate inputs
function validateInputs() {
    let isValid = true;
    document.querySelectorAll("#stockTable tbody tr input, #stockTable tbody tr select").forEach(input => {
        if (!input.value.trim()) {
            input.classList.add("error");
            if (!input.nextElementSibling) {
                let errorText = document.createElement("div");
                errorText.classList.add("error-text");
                errorText.textContent = "This field is required";
                input.parentNode.appendChild(errorText);
            }
            isValid = false;
        } else {
            input.classList.remove("error");
            if (input.nextElementSibling) {
                input.nextElementSibling.remove();
            }
        }
    });
    return isValid;
}

// Save stock
function saveStock() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];

    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        let newStock = {
            medicine: row.querySelector(".medicine-name").value,
            brand: row.querySelector(".brand-name").value,
            category: row.querySelector(".category") ? row.querySelector(".category").value : row.querySelector(".category-input").value,
            quantity: parseInt(row.querySelector(".quantity").value),
            expirationDate: row.querySelector(".expiration-date").value,
            dateDelivered: row.querySelector(".delivery-date").value
        };

        inventory.push(newStock);
        row.remove();
    });

    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
}

// Load inventory
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

// Delete medicine
function deleteMedicine(index) {
    if (confirm("Are you sure you want to delete this medicine?")) {
        let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}
