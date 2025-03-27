document.addEventListener("DOMContentLoaded", function () {
    highlightActivePage();
    loadInventory();
    loadRecentStocks();
});

// Highlight the active sidebar link
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
        <td><input type="text" class="form-control medicine-name" placeholder="Enter medicine"></td>
        <td><input type="text" class="form-control brand-name" placeholder="Enter brand name"></td>
        <td>
            <input type="number" class="form-control quantity" placeholder="Enter quantity">
            <div class="error-text"></div>
        </td>
        <td><input type="date" class="form-control expiration-date"></td>
        <td><input type="date" class="form-control delivery-date"></td>
        <td>
            <select class="form-control category">
                <option value="Pain Reliever">Pain Reliever</option>
                <option value="Antibiotic">Antibiotic</option>
                <option value="Antiseptic">Antiseptic</option>
                <option value="Vitamin">Vitamin</option>
                <option value="Other">Other</option>
            </select>
        </td>
        <td><button class="btn btn-danger btn-sm" onclick="deleteRow(this)">🗑</button></td>
    `;

    tableBody.appendChild(row);
}

// Delete a stock row
function deleteRow(button) {
    button.closest("tr").remove();
}

// Validate input fields, ensuring quantity is a positive whole number
function validateInputs() {
    let isValid = true;

    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        row.querySelectorAll("input, select").forEach(input => {
            let value = input.value.trim();
            let isQuantityField = input.classList.contains("quantity");
            let errorText = input.nextElementSibling; // Get existing error text

            // Create error text if not exists
            if (!errorText || !errorText.classList.contains("error-text")) {
                errorText = document.createElement("div");
                errorText.classList.add("error-text");
                input.parentNode.appendChild(errorText);
            }

            // Validate: Empty fields or invalid quantity
            if (value === "") {
                input.classList.add("error");
                errorText.textContent = "This field is required";
                errorText.style.display = "block";
                isValid = false;
            } else if (isQuantityField && (!/^\d+$/.test(value) || parseInt(value) <= 0)) {
                input.classList.add("error");
                errorText.textContent = "Quantity must be a whole number greater than 0";
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


// Save stock data
function confirmSave() {
    if (!validateInputs()) return;

    if (confirm("Are you sure you want to add this?")) {
        saveStock();
    }
}

// Save stock and add to recent stocks
function saveStock() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];

    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        let medicine = row.querySelector(".medicine-name").value;
        let brand = row.querySelector(".brand-name").value;
        let quantity = parseInt(row.querySelector(".quantity").value);
        let expirationDate = row.querySelector(".expiration-date").value;
        let dateDelivered = row.querySelector(".delivery-date").value;
        let category = row.querySelector(".category").value;

        let newStock = { medicine, brand, category, quantity, expirationDate, dateDelivered };
        inventory.push(newStock);
        recentStocks.unshift(newStock); // Add to the top

        row.remove();
    });

    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    localStorage.setItem("recentStocks", JSON.stringify(recentStocks));

    loadInventory();
    loadRecentStocks();
}

// Load and display inventory (sorted by expiration date)
function loadInventory() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    inventory.sort((a, b) => new Date(a.expirationDate) - new Date(b.expirationDate)); // Sort by expiry

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

// Load recently added stocks
function loadRecentStocks() {
    let recentStocks = JSON.parse(localStorage.getItem("recentStocks")) || [];
    let tableBody = document.querySelector("#recentStocksTable tbody");
    tableBody.innerHTML = "";

    recentStocks.forEach(med => {
        let row = `<tr>
            <td>${med.medicine}</td>
            <td>${med.brand}</td>
            <td>${med.category}</td>
            <td>${med.quantity}</td>
            <td>${med.expirationDate}</td>
            <td>${med.dateDelivered}</td>
        </tr>`;
        tableBody.innerHTML += row;
    });
}

// Dispense medicine (reduce quantity)
function dispenseMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    
    if (inventory[index].quantity > 0) {
        inventory[index].quantity--;

        if (inventory[index].quantity === 0) {
            if (confirm("Stock is empty. Remove from inventory?")) {
                inventory.splice(index, 1);
            }
        }

        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    } else {
        alert("Insufficient stock!");
    }
}

// Delete a medicine entry
function deleteMedicine(index) {
    if (confirm("Are you sure you want to delete this medicine?")) {
        let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}

// Filter inventory by category
function filterInventory() {
    let category = document.getElementById("filterCategory").value.toLowerCase();
    document.querySelectorAll("#inventoryTableBody tr").forEach(row => {
        let categoryText = row.children[2].textContent.toLowerCase();
        row.style.display = category === "" || categoryText === category ? "" : "none";
    });
}

// Search inventory
function searchInventory() {
    let searchTerm = document.getElementById("searchMedicine").value.toLowerCase();
    document.querySelectorAll("#inventoryTableBody tr").forEach(row => {
        let medicineName = row.children[0].textContent.toLowerCase();
        row.style.display = medicineName.includes(searchTerm) ? "" : "none";
    });
}

// Export inventory to CSV
function exportTableToCSV() {
    let table = document.getElementById("medicineInventoryTable");
    let rows = Array.from(table.querySelectorAll("tr")).map(row =>
        Array.from(row.cells).map(cell => cell.textContent).join(",")
    ).join("\n");

    let csvContent = "data:text/csv;charset=utf-8," + rows;
    let encodedUri = encodeURI(csvContent);
    let link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "medicine_inventory.csv");
    link.click();
}
