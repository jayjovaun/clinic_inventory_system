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

// Load inventory from local storage
function loadInventory() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let tableBody = document.querySelector("#inventoryTableBody");
    tableBody.innerHTML = "";

    inventory.forEach((item, index) => {
        let row = document.createElement("tr");
        row.innerHTML = `
            <td>${item.medicine}</td>
            <td>${item.brand}</td>
            <td>${item.category}</td>
            <td>${item.quantity}</td>
            <td>${item.expirationDate}</td>
            <td>${item.dateDelivered}</td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="updateStock(${index})">✏ Update</button>
                <button class="btn btn-danger btn-sm" onclick="deleteStock(${index})">🗑 Delete</button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Prevent negative or decimal values in quantity
function validateQuantity(input) {
    if (input.value < 1 || !Number.isInteger(Number(input.value))) {
        input.value = "";
    }
}

// Update stock - correctly mapped inputs
function updateStock(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let item = inventory[index];

    let row = document.querySelectorAll("#inventoryTableBody tr")[index];
    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name" value="${item.medicine}"></td>
        <td><input type="text" class="form-control brand-name" value="${item.brand}"></td>
        <td>
            <select class="form-control category">
                <option value="Pain Reliever" ${item.category === "Pain Reliever" ? "selected" : ""}>Pain Reliever</option>
                <option value="Antibiotic" ${item.category === "Antibiotic" ? "selected" : ""}>Antibiotic</option>
                <option value="Antiseptic" ${item.category === "Antiseptic" ? "selected" : ""}>Antiseptic</option>
                <option value="Vitamin" ${item.category === "Vitamin" ? "selected" : ""}>Vitamin</option>
            </select>
        </td>
        <td><input type="number" class="form-control quantity" min="1" value="${item.quantity}" oninput="validateQuantity(this)"></td>
        <td><input type="date" class="form-control expiration-date" value="${item.expirationDate}"></td>
        <td><input type="date" class="form-control delivery-date" value="${item.dateDelivered}"></td>
        <td>
            <button class="btn btn-success btn-sm" onclick="saveUpdatedStock(${index})">✔ Save</button>
            <button class="btn btn-secondary btn-sm" onclick="cancelUpdate(${index})">✖ Cancel</button>
        </td>
    `;
}

// Save updated stock after editing
function saveUpdatedStock(index) {
    let row = document.querySelectorAll("#inventoryTableBody tr")[index];
    let medicine = row.querySelector(".medicine-name").value;
    let brand = row.querySelector(".brand-name").value;
    let category = row.querySelector(".category").value;
    let quantity = parseInt(row.querySelector(".quantity").value);
    let expirationDate = row.querySelector(".expiration-date").value;
    let dateDelivered = row.querySelector(".delivery-date").value;

    if (!medicine || !brand || !category || !expirationDate || !dateDelivered || isNaN(quantity) || quantity < 1) {
        alert("Please fill in all fields correctly.");
        return;
    }

    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    inventory[index] = { medicine, brand, category, quantity, expirationDate, dateDelivered };
    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
}

// Cancel update and revert to original row
function cancelUpdate(index) {
    loadInventory();
}

// Delete stock with confirmation
function deleteStock(index) {
    if (confirm("Are you sure you want to delete this item?")) {
        let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}
