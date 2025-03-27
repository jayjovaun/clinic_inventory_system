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

// Add a new row for stock entry
function addStockRow() {
    let tableBody = document.querySelector("#stockTable tbody");
    let row = document.createElement("tr");

    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name"></td>
        <td><input type="text" class="form-control brand-name"></td>
        <td>
            <select class="form-control category" style="width: 180px;" onchange="handleOtherCategory(this)">
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

// Prevent negative or decimal values in quantity
function validateQuantity(input) {
    if (input.value < 1 || !Number.isInteger(Number(input.value))) {
        input.value = "";
    }
}

// Delete a stock row
function deleteRow(button) {
    button.closest("tr").remove();
}

// Validate inputs
function validateInputs() {
    let isValid = true;
    document.querySelectorAll("#stockTable tbody tr, #inventoryTableBody tr").forEach(row => {
        row.querySelectorAll("input, select").forEach(input => {
            let value = input.value.trim();
            let isQuantityField = input.classList.contains("quantity");
            let errorText = input.nextElementSibling;

            if (!errorText || !errorText.classList.contains("error-text")) {
                errorText = document.createElement("div");
                errorText.classList.add("error-text");
                input.parentNode.appendChild(errorText);
            }

            if (value === "" || (isQuantityField && (!/^[1-9]\d*$/.test(value)))) {
                input.classList.add("error");
                errorText.textContent = value === "" ? "This field is required" : "Quantity must be a whole number greater than 0";
                errorText.style.display = "block";
                isValid = false;
            } else {
                input.classList.remove("error");
                errorText.style.display = "none";
            }
        });
    });

    return isValid;
}

// Confirm before saving
function confirmSave() {
    if (validateInputs()) {
        if (confirm("Are you sure you want to add this?")) {
            saveStock();
        }
    }
}

// Save stock
function saveStock() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];

    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        let medicine = row.querySelector(".medicine-name").value;
        let brand = row.querySelector(".brand-name").value;
        let category = row.querySelector(".category").value;
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
                <button class="btn btn-primary btn-sm" onclick="editMedicine(${index})">✏ Update</button>
                <button class="btn btn-danger btn-sm" onclick="deleteMedicine(${index})">🗑 Delete</button>
            </td>
        </tr>`;
        tableBody.innerHTML += row;
    });
}

// Dispense medicine (removes from inventory)
function dispenseMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    inventory.splice(index, 1);
    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
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

// Edit medicine
function editMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let med = inventory[index];

    let row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name" value="${med.medicine}"></td>
        <td><input type="text" class="form-control brand-name" value="${med.brand}"></td>
        <td><input type="number" class="form-control quantity" style="width: 80px;" value="${med.quantity}" oninput="validateQuantity(this)"></td>
        <td><input type="date" class="form-control expiration-date" value="${med.expirationDate}"></td>
        <td><input type="date" class="form-control delivery-date" value="${med.dateDelivered}"></td>
        <td>
            <select class="form-control category" style="width: 180px;">
                <option value="Pain Reliever" ${med.category === "Pain Reliever" ? "selected" : ""}>Pain Reliever</option>
                <option value="Antibiotic" ${med.category === "Antibiotic" ? "selected" : ""}>Antibiotic</option>
                <option value="Antiseptic" ${med.category === "Antiseptic" ? "selected" : ""}>Antiseptic</option>
                <option value="Vitamin" ${med.category === "Vitamin" ? "selected" : ""}>Vitamin</option>
            </select>
        </td>
        <td>
            <button class="btn btn-success btn-sm" onclick="saveUpdatedMedicine(${index})">💾 Save</button>
            <button class="btn btn-secondary btn-sm" onclick="loadInventory()">❌ Cancel</button>
        </td>
    `;
}

// Save updated medicine
function saveUpdatedMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);

    inventory[index] = {
        medicine: row.querySelector(".medicine-name").value,
        brand: row.querySelector(".brand-name").value,
        category: row.querySelector(".category").value,
        quantity: parseInt(row.querySelector(".quantity").value),
        expirationDate: row.querySelector(".expiration-date").value,
        dateDelivered: row.querySelector(".delivery-date").value
    };

    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
}
