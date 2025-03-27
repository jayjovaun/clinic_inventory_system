document.addEventListener("DOMContentLoaded", loadInventory);

function addStockRow() {
    let table = document.getElementById("stockTable").getElementsByTagName("tbody")[0];
    let row = table.insertRow();
    row.innerHTML = `
        <td><input type="text" class="form-control medicine"><span class="error-text">Enter medicine name.</span></td>
        <td><input type="text" class="form-control brand-name"><span class="error-text">Enter brand name.</span></td>
        <td>
            <input type="number" class="form-control quantity" min="1" step="1" oninput="validateQuantity(this)">
            <span class="error-text">Quantity must be a whole number (at least 1).</span>
        </td>
        <td><input type="date" class="form-control expiration"><span class="error-text">Select an expiration date.</span></td>
        <td>
            <select class="form-control category" onchange="toggleInput(this, 'category')">
                <option value="">Select Category</option>
                <option value="Antibiotic">Antibiotic</option>
                <option value="Analgesic">Analgesic</option>
                <option value="Other">Other</option>
            </select>
        </td>
        <td><button class="btn btn-sm btn-danger" onclick="confirmDelete(this)">❌</button></td>
    `;
}

function toggleInput(select, type) {
    if (select.value === "Other") {
        let input = document.createElement("input");
        input.type = "text";
        input.className = `form-control ${type}-custom`;
        input.placeholder = `Enter custom ${type}`;
        select.parentNode.replaceChild(input, select);
    }
}

function confirmDelete(button) {
    if (confirm("Are you sure you want to delete this entry?")) {
        button.closest("tr").remove();
    }
}

function validateQuantity(input) {
    let value = input.value;
    if (value.includes(".") || value.includes(",")) {
        input.value = Math.floor(value);
    }
}

function confirmSave() {
    if (validateStockInputs()) {
        if (confirm("Are you sure you want to add this?")) {
            saveStock();
        }
    } else {
        alert("Please correct the errors before saving.");
    }
}

function validateStockInputs() {
    let isValid = true;
    document.querySelectorAll("#stockTable tbody tr").forEach(row => {
        row.querySelectorAll("input, select").forEach(input => {
            let errorMsg = input.nextElementSibling;
            if (!input.value.trim() || (input.type === "number" && input.value <= 0)) {
                input.classList.add("error");
                if (errorMsg) errorMsg.style.display = "block";
                isValid = false;
            } else {
                input.classList.remove("error");
                if (errorMsg) errorMsg.style.display = "none";
            }
        });
    });
    return isValid;
}

function saveStock() {
    let table = document.getElementById("stockTable").getElementsByTagName("tbody")[0];
    let recentTable = document.getElementById("recentStocksTable").getElementsByTagName("tbody")[0];
    table.querySelectorAll("tr").forEach(row => {
        let medicine = row.querySelector(".medicine").value.trim();
        let brandName = row.querySelector(".brand-name").value.trim();
        let quantity = row.querySelector(".quantity").value.trim();
        let expiration = row.querySelector(".expiration").value;
        let categoryInput = row.querySelector(".category") || row.querySelector(".category-custom");
        let category = categoryInput ? categoryInput.value.trim() : "";

        let newRow = recentTable.insertRow();
        newRow.innerHTML = `<td>${medicine}</td><td>${brandName}</td><td>${quantity}</td><td>${expiration}</td><td>${category}</td>`;
        row.remove();
    });
    alert("Stock added successfully!");
}
