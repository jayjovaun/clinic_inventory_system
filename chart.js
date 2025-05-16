document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById("visitChart").getContext("2d");

    if (!ctx) {
        console.error("Canvas element with ID 'visitChart' not found.");
        return;
    }

    new Chart(ctx, {
        type: "line",
        data: {
            labels: ["Feb 25", "Feb 26", "Feb 27", "Feb 28", "Mar 1", "Mar 2", "Mar 3"],
            datasets: [
                {
                    label: "Students",
                    data: [50, 60, 70, 80, 90, 100, 120],
                    borderColor: "red",
                    borderWidth: 2,
                    fill: false
                },
                {
                    label: "Faculty",
                    data: [20, 25, 30, 35, 40, 45, 50],
                    borderColor: "green",
                    borderWidth: 2,
                    fill: false
                },
                {
                    label: "Guests",
                    data: [10, 15, 20, 25, 20, 25, 30],
                    borderColor: "gray",
                    borderWidth: 2,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
