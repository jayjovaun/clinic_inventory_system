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

// Prevent negative and decimal values in quantity input fields
document.addEventListener("input", function (event) {
    if (event.target.classList.contains("quantity")) {
        event.target.value = event.target.value.replace(/[^0-9]/g, ""); // Allow only digits
    }
});

// Add a new row for stock entry
function addStockRow() {
    let tableBody = document.querySelector("#stockTable tbody");
    let row = document.createElement("tr");

    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name" placeholder="Enter medicine"></td>
        <td><input type="text" class="form-control brand-name" placeholder="Enter brand name"></td>
        <td><input type="number" class="form-control quantity" placeholder="Enter quantity"></td>
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
            let errorText = input.nextElementSibling;

            if (!errorText || !errorText.classList.contains("error-text")) {
                errorText = document.createElement("div");
                errorText.classList.add("error-text");
                input.parentNode.appendChild(errorText);
            }

            if (value === "") {
                input.classList.add("error");
                errorText.textContent = "This field is required";
                errorText.style.display = "block";
                isValid = false;
            } else if (isQuantityField) {
                let quantity = Number(value);
                if (!Number.isInteger(quantity) || quantity <= 0) {
                    input.classList.add("error");
                    errorText.textContent = "Quantity must be a positive whole number";
                    errorText.style.display = "block";
                    isValid = false;
                } else {
                    input.classList.remove("error");
                    errorText.style.display = "none";
                }
            } else {
                input.classList.remove("error");
                errorText.style.display = "none";
            }
        });
    });

    return isValid;
}

// Confirm save after validation
function confirmSave() {
    if (validateInputs()) {
        if (confirm("Are you sure you want to add this?")) {
            saveStock();
        }
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
        recentStocks.unshift(newStock);

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
    inventory.sort((a, b) => new Date(a.expirationDate) - new Date(b.expirationDate));

    let tableBody = document.querySelector("#inventoryTableBody");
    tableBody.innerHTML = "";

    inventory.forEach((med, index) => {
        let row = `<tr>
            <td contenteditable="false">${med.medicine}</td>
            <td contenteditable="false">${med.brand}</td>
            <td contenteditable="false">${med.category}</td>
            <td contenteditable="false">${med.quantity}</td>
            <td contenteditable="false">${med.expirationDate}</td>
            <td contenteditable="false">${med.dateDelivered}</td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="dispenseMedicine(${index})">➖ Dispense</button>
                <button class="btn btn-primary btn-sm" onclick="updateMedicine(this, ${index})">✏ Update</button>
                <button class="btn btn-danger btn-sm" onclick="deleteMedicine(${index})">🗑 Delete</button>
            </td>
        </tr>`;
        tableBody.innerHTML += row;
    });
}

// Update medicine details
function updateMedicine(button, index) {
    let row = button.closest("tr");
    let isEditing = row.getAttribute("data-editing") === "true";

    if (isEditing) {
        let updatedData = {
            medicine: row.children[0].textContent.trim(),
            brand: row.children[1].textContent.trim(),
            category: row.children[2].textContent.trim(),
            quantity: parseInt(row.children[3].textContent.trim()),
            expirationDate: row.children[4].textContent.trim(),
            dateDelivered: row.children[5].textContent.trim()
        };

        if (!updatedData.medicine || !updatedData.brand || !updatedData.category || !updatedData.expirationDate || !updatedData.dateDelivered || isNaN(updatedData.quantity) || updatedData.quantity <= 0) {
            alert("Please enter valid details.");
            return;
        }

        let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory[index] = updatedData;
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    } else {
        for (let i = 0; i < row.children.length - 1; i++) {
            row.children[i].contentEditable = true;
        }
        button.textContent = "✔ Save";
        row.setAttribute("data-editing", "true");
    }
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
