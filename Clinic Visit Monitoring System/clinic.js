document.addEventListener("DOMContentLoaded", function () {
    highlightActivePage();
    loadInventory();
});

// Highlight the active sidebar link
function highlightActivePage() {
    let path = window.location.pathname.split("/").pop();
    let pageMap = {
        "index.html": "stockEntry",
        "inventory.html": "manageInventory",
        "reports.html": "reports"
    };
    if (pageMap[path]) {
        document.getElementById(pageMap[path]).classList.add("active");
    }
}

// Add new stock row
function addStockRow() {
    let table = document.getElementById("stockTable").getElementsByTagName("tbody")[0];
    let row = table.insertRow();
    row.innerHTML = `
        <td><input type="text" class="form-control medicine"><span class="error-text">Enter medicine name.</span></td>
        <td><input type="text" class="form-control brand"><span class="error-text">Enter brand name.</span></td>
        <td>
            <input type="number" class="form-control quantity" min="1" step="1" oninput="validateQuantity(this)">
            <span class="error-text">Quantity must be at least 1.</span>
        </td>
        <td><input type="date" class="form-control expiration"><span class="error-text">Select an expiration date.</span></td>
        <td><input type="date" class="form-control delivered"><span class="error-text">Select a delivery date.</span></td>
        <td><input type="text" class="form-control category"><span class="error-text">Enter category.</span></td>
        <td><button class="btn btn-sm btn-danger" onclick="confirmDelete(this)">❌</button></td>
    `;
}

// Delete confirmation
function confirmDelete(button) {
    if (confirm("Are you sure you want to delete this entry?")) {
        button.closest("tr").remove();
    }
}

// Validate quantity input
function validateQuantity(input) {
    let value = input.value;
    if (value.includes(".") || value.includes(",")) {
        input.value = Math.floor(value);
    }
}

// Save stock with validation
function confirmSave() {
    if (validateStockInputs()) {
        if (confirm("Are you sure you want to add this?")) {
            saveStock();
        }
    } else {
        alert("Please correct the errors before saving.");
    }
}

// Validate stock input fields
function validateStockInputs() {
    let isValid = true;
    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        row.querySelectorAll("input").forEach(input => {
            let errorMsg = input.nextElementSibling;
            if (!input.value.trim() || (input.type === "number" && input.value <= 0)) {
                input.classList.add("error");
                errorMsg.style.display = "block";
                isValid = false;
            } else {
                input.classList.remove("error");
                errorMsg.style.display = "none";
            }
        });
    });
    return isValid;
}

// Save stock to recent stocks table
function saveStock() {
    let table = document.getElementById("stockTable").getElementsByTagName("tbody")[0];
    let recentTable = document.getElementById("recentStocksTable").getElementsByTagName("tbody")[0];
    
    table.querySelectorAll("tr").forEach(row => {
        let medicine = row.querySelector(".medicine").value.trim();
        let brand = row.querySelector(".brand").value.trim();
        let quantity = row.querySelector(".quantity").value.trim();
        let expiration = row.querySelector(".expiration").value;
        let delivered = row.querySelector(".delivered").value;
        let category = row.querySelector(".category").value.trim();

        let newRow = recentTable.insertRow();
        newRow.innerHTML = `<td>${medicine}</td><td>${brand}</td><td>${quantity}</td><td>${expiration}</td><td>${delivered}</td><td>${category}</td>`;
        
        row.remove();
    });

    alert("Stock added successfully!");
}
