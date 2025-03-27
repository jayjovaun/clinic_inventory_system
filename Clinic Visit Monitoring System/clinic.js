document.addEventListener("DOMContentLoaded", function () {
    loadInventory();
});

// Function to load inventory data
function loadInventory() {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let tableBody = document.getElementById("inventoryTableBody");
    tableBody.innerHTML = "";

    inventory.forEach((item, index) => {
        let row = document.createElement("tr");

        row.innerHTML = `
            <td>${item.medicine}</td>
            <td>${item.brand}</td>
            <td>${item.category}</td>
            <td>${item.quantity}</td>
            <td>${item.expiration}</td>
            <td>${item.dateDelivered}</td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="updateRow(${index})">Update</button>
                <button class="btn btn-success btn-sm" onclick="dispenseMedicine(${index})">Dispense</button>
                <button class="btn btn-danger btn-sm" onclick="deleteMedicine(${index})">Delete</button>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

// Function to delete a medicine
function deleteMedicine(index) {
    if (confirm("Are you sure you want to delete this medicine?")) {
        let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}

// Function to dispense medicine
function dispenseMedicine(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let medicine = inventory[index];

    let quantity = parseInt(prompt(`Enter quantity to dispense (Available: ${medicine.quantity}):`), 10);
    
    if (!isNaN(quantity) && quantity > 0 && quantity <= medicine.quantity) {
        medicine.quantity -= quantity;
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    } else {
        alert("Invalid quantity. Please enter a valid number.");
    }
}

// Function to update a row
function updateRow(index) {
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let table = document.getElementById("medicineInventoryTable");
    let row = table.rows[index + 1];

    // Convert table row to editable input fields
    row.cells[0].innerHTML = `<input type="text" id="editMedicine" class="form-control" value="${inventory[index].medicine}">`;
    row.cells[1].innerHTML = `<input type="text" id="editBrand" class="form-control" value="${inventory[index].brand}">`;
    row.cells[2].innerHTML = `<input type="text" id="editCategory" class="form-control" value="${inventory[index].category}">`;
    row.cells[3].innerHTML = `<input type="number" id="editQuantity" class="form-control" value="${inventory[index].quantity}" min="1">`;
    row.cells[4].innerHTML = `<input type="date" id="editExpiration" class="form-control" value="${inventory[index].expiration}">`;
    row.cells[5].innerHTML = `<input type="date" id="editDateDelivered" class="form-control" value="${inventory[index].dateDelivered}">`;

    // Change action buttons
    row.cells[6].innerHTML = `
        <button class="btn btn-primary btn-sm" onclick="saveUpdate(${index})">Save</button>
        <button class="btn btn-secondary btn-sm" onclick="cancelUpdate(${index})">Cancel</button>
    `;
}

// Function to save the updated data
function saveUpdate(index) {
    let medicine = document.getElementById("editMedicine").value.trim();
    let brand = document.getElementById("editBrand").value.trim();
    let category = document.getElementById("editCategory").value.trim();
    let quantity = document.getElementById("editQuantity").value.trim();
    let expiration = document.getElementById("editExpiration").value;
    let dateDelivered = document.getElementById("editDateDelivered").value;

    if (medicine === "" || brand === "" || category === "" || quantity === "" || expiration === "" || dateDelivered === "") {
        alert("All fields must be filled!");
        return;
    }

    if (!/^\d+$/.test(quantity) || parseInt(quantity) < 1) {
        alert("Quantity must be a whole number greater than zero.");
        return;
    }

    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    inventory[index] = { medicine, brand, category, quantity: parseInt(quantity), expiration, dateDelivered };
    localStorage.setItem("medicineInventory", JSON.stringify(inventory));
    loadInventory();
}

// Function to cancel update and restore previous values
function cancelUpdate(index) {
    loadInventory();
}

// Function to search in inventory
function searchInventory() {
    let searchValue = document.getElementById("searchMedicine").value.toLowerCase();
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let tableBody = document.getElementById("inventoryTableBody");
    tableBody.innerHTML = "";

    inventory.forEach((item, index) => {
        if (item.medicine.toLowerCase().includes(searchValue) || item.brand.toLowerCase().includes(searchValue)) {
            let row = document.createElement("tr");

            row.innerHTML = `
                <td>${item.medicine}</td>
                <td>${item.brand}</td>
                <td>${item.category}</td>
                <td>${item.quantity}</td>
                <td>${item.expiration}</td>
                <td>${item.dateDelivered}</td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="updateRow(${index})">Update</button>
                    <button class="btn btn-success btn-sm" onclick="dispenseMedicine(${index})">Dispense</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMedicine(${index})">Delete</button>
                </td>
            `;

            tableBody.appendChild(row);
        }
    });
}

// Function to filter inventory by category
function filterInventory() {
    let filterValue = document.getElementById("filterCategory").value;
    let inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    let tableBody = document.getElementById("inventoryTableBody");
    tableBody.innerHTML = "";

    inventory.forEach((item, index) => {
        if (filterValue === "" || item.category === filterValue) {
            let row = document.createElement("tr");

            row.innerHTML = `
                <td>${item.medicine}</td>
                <td>${item.brand}</td>
                <td>${item.category}</td>
                <td>${item.quantity}</td>
                <td>${item.expiration}</td>
                <td>${item.dateDelivered}</td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="updateRow(${index})">Update</button>
                    <button class="btn btn-success btn-sm" onclick="dispenseMedicine(${index})">Dispense</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMedicine(${index})">Delete</button>
                </td>
            `;

            tableBody.appendChild(row);
        }
    });
}

// Function to export table to CSV
function exportTableToCSV(tableID) {
    let table = document.getElementById(tableID);
    let csv = Papa.unparse(table);
    let csvBlob = new Blob([csv], { type: "text/csv" });
    let link = document.createElement("a");

    link.href = URL.createObjectURL(csvBlob);
    link.download = "MedicineInventory.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
