document.addEventListener("DOMContentLoaded", function () {
    highlightActivePage();
    loadInventory();
});

// Highlight active sidebar link
function highlightActivePage() {
    const currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".sidebar a").forEach(link => {
        link.classList.toggle("active", link.getAttribute("href") === currentPage);
    });
}

// Format date as dd/mm/yyyy
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Load inventory function with professional button styling
function loadInventory() {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    inventory.sort((a, b) => new Date(a.expirationDate) - new Date(b.expirationDate));
    
    const tableBody = document.getElementById("inventoryTableBody");
    tableBody.innerHTML = inventory.map((med, index) => `
        <tr>
            <td>${med.medicine || '-'}</td>
            <td>${med.brand || '-'}</td>
            <td>${med.category || '-'}</td>
            <td class="text-center">${med.quantity || '0'}</td>
            <td class="text-center">${formatDate(med.expirationDate)}</td>
            <td class="text-center">${formatDate(med.dateDelivered)}</td>
            <td class="text-center actions-cell">
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-outline-warning btn-sm" onclick="dispenseMedicine(${index})">Dispense</button>
                    <button class="btn btn-outline-primary btn-sm" onclick="editMedicine(${index})">Edit</button>
                    <button class="btn btn-outline-danger btn-sm" onclick="deleteMedicine(${index})">Delete</button>
                </div>
            </td>
        </tr>
    `).join("");
}

// Dispense medicine
function dispenseMedicine(index) {
    if (confirm("Are you sure you want to dispense this medicine?")) {
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}

// Delete medicine from inventory
function deleteMedicine(index) {
    if (confirm("Are you sure you want to delete this medicine?")) {
        const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
        inventory.splice(index, 1);
        localStorage.setItem("medicineInventory", JSON.stringify(inventory));
        loadInventory();
    }
}

// Edit medicine function
function editMedicine(index) {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    const med = inventory[index];
    const row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    
    row.innerHTML = `
        <td><input type="text" class="form-control medicine-name" value="${med.medicine}" required></td>
        <td><input type="text" class="form-control brand-name" value="${med.brand}" required></td>
        <td><input type="text" class="form-control category" value="${med.category}" required></td>
        <td class="text-center"><input type="number" class="form-control quantity-input" value="${med.quantity}" min="1" required></td>
        <td><input type="date" class="form-control expiration-date" value="${med.expirationDate}" required></td>
        <td><input type="date" class="form-control delivery-date" value="${med.dateDelivered}" required></td>
        <td class="text-center actions-cell">
            <div class="d-flex justify-content-center gap-2">
                <button class="btn btn-success btn-sm" onclick="saveUpdatedMedicine(${index})">Save</button>
                <button class="btn btn-secondary btn-sm" onclick="loadInventory()">Cancel</button>
            </div>
        </td>
    `;
}

// Save updated medicine
function saveUpdatedMedicine(index) {
    const row = document.querySelector(`#inventoryTableBody tr:nth-child(${index + 1})`);
    const inputs = {
        medicine: row.querySelector(".medicine-name"),
        brand: row.querySelector(".brand-name"),
        category: row.querySelector(".category"),
        quantity: row.querySelector(".quantity-input"),
        expirationDate: row.querySelector(".expiration-date"),
        dateDelivered: row.querySelector(".delivery-date")
    };

    // Validate inputs
    let isValid = true;
    Object.entries(inputs).forEach(([field, input]) => {
        if (!input.value.trim()) {
            input.classList.add("is-invalid");
            isValid = false;
        } else if (field === "quantity" && (isNaN(input.value) || parseInt(input.value) < 1)) {
            input.classList.add("is-invalid");
            isValid = false;
        } else {
            input.classList.remove("is-invalid");
        }
    });

    if (!isValid) {
        alert("Please fill all fields with valid values");
        return;
    }

    // Update inventory
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
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

// Export to CSV function
function exportToCSV() {
    const inventory = JSON.parse(localStorage.getItem("medicineInventory")) || [];
    if (!inventory.length) return alert("No data to export!");

    const headers = ["Medicine", "Brand", "Category", "Quantity", "Expiration Date", "Delivery Date"];
    const csvContent = [
        headers.join(","),
        ...inventory.map(item => headers.map(header => 
            `"${item[header.toLowerCase().replace(' ', '')]}"`
        ).join(","))
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `medicine_inventory_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}