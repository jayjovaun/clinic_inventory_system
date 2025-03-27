// Initialization
document.addEventListener("DOMContentLoaded", () => {
    highlightActivePage();
    loadInventory();
});

// Helper functions
function highlightActivePage() {
    document.querySelectorAll(".sidebar a").forEach(link => {
        if (link.getAttribute("href") === window.location.pathname.split("/").pop()) {
            link.classList.add("active");
        }
    });
}

function showError(input, message) {
    clearError(input);
    input.classList.add("is-invalid");
    const errorText = document.createElement("div");
    errorText.className = "invalid-feedback";
    errorText.textContent = message;
    input.parentNode.appendChild(errorText);
}

function clearError(input) {
    input.classList.remove("is-invalid");
    const errorText = input.nextElementSibling;
    if (errorText && errorText.classList.contains("invalid-feedback")) {
        errorText.remove();
    }
}

// Inventory functions
function loadInventory() {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const tableBody = document.getElementById("inventoryTableBody");
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

function dispenseMedicine(index) {
    if (confirm("Dispense this medicine?")) {
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}

function deleteMedicine(index) {
    if (confirm("Delete this medicine?")) {
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}

function editMedicine(index) {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const med = inventory[index];
    const row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    
    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name" value="${med.medicine}"></td>
        <td><input type="text" class="form-control brand-name" value="${med.brand}"></td>
        <td><input type="text" class="form-control category" value="${med.category}"></td>
        <td><input type="number" class="form-control quantity" min="1" value="${med.quantity}"></td>
        <td><input type="date" class="form-control expiration-date" value="${med.expirationDate}"></td>
        <td><input type="date" class="form-control delivery-date" value="${med.dateDelivered}"></td>
        <td>
            <button class="btn btn-success btn-sm" onclick="saveUpdatedMedicine(${index})">💾 Save</button>
            <button class="btn btn-secondary btn-sm" onclick="loadInventory()">❌ Cancel</button>
        </td>
    `;
}

function saveUpdatedMedicine(index) {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    
    // Clear errors
    row.querySelectorAll("input").forEach(input => clearError(input));
    
    // Get values
    const inputs = {
        medicine: row.querySelector(".medicine-name"),
        brand: row.querySelector(".brand-name"),
        category: row.querySelector(".category"),
        quantity: row.querySelector(".quantity"),
        expirationDate: row.querySelector(".expiration-date"),
        dateDelivered: row.querySelector(".delivery-date")
    };
    
    // Validate
    let isValid = true;
    Object.entries(inputs).forEach(([key, input]) => {
        if (!input.value.trim()) {
            showError(input, `${key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())} is required`);
            isValid = false;
        } else if (key === "quantity" && (isNaN(input.value) || parseInt(input.value) < 1)) {
            showError(input, "Quantity must be ≥ 1");
            isValid = false;
        }
    });
    
    if (!isValid) return;
    
    // Update and save
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

// CSV Export
function exportToCSV() {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    if (!inventory.length) return alert("No data to export!");
    
    const headers = ["Medicine", "Brand", "Category", "Quantity", "Expiration Date", "Delivery Date"];
    const csvRows = [
        headers.join(","),
        ...inventory.map(item => 
            headers.map(header => {
                const value = item[header.toLowerCase().replace(" ", "")];
                return `"${value}"`;
            }).join(",")
        )
    ];
    
    const csvContent = csvRows.join("\n");
    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "medicine_inventory.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Search and filter
function searchInventory() {
    const term = document.getElementById("searchMedicine").value.toLowerCase();
    document.querySelectorAll("#inventoryTableBody tr").forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? "" : "none";
    });
}

function filterInventory() {
    const category = document.getElementById("filterCategory").value;
    document.querySelectorAll("#inventoryTableBody tr").forEach(row => {
        const rowCategory = row.cells[2].textContent;
        row.style.display = !category || rowCategory === category ? "" : "none";
    });
}