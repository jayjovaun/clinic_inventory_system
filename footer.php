        <!-- Custom Alert Modal -->
        <div class="modal fade" id="customAlertModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="customAlertTitle">Notification</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="customAlertBody">
                        <!-- Message will be inserted here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Confirm Modal -->
        <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="customConfirmTitle">Confirm Action</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="customConfirmBody">
                        <!-- Message will be inserted here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="customConfirmOK">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Global chart references
            const charts = {};
            
            // Function to clear single notification
            function clearSingleNotification(medicineId, notificationType) {
                fetch('clear_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `medicine_id=${medicineId}&notification_type=${notificationType}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the notification item from UI
                        const notificationItem = document.querySelector(`.notification-clear[onclick="clearSingleNotification(${medicineId}, '${notificationType}')`).closest('.notification-item');
                        notificationItem.remove();
                        
                        // Update notification badge
                        const badge = document.getElementById('notificationBadge');
                        if (badge) {
                            const currentCount = parseInt(badge.textContent) || 0;
                            const newCount = currentCount - 1;
                            if (newCount <= 0) {
                                badge.classList.add('d-none');
                                document.getElementById('notificationContent').innerHTML = '<li class="px-3 py-2 text-muted">No inventory alerts</li>';
                            } else {
                                badge.textContent = newCount > 9 ? '9+' : newCount;
                            }
                        }
                    }
                });
            }
            
            // Function to clear all notifications
            function clearAllNotifications() {
                fetch('clear_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'clear_all=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        document.getElementById('notificationContent').innerHTML = '<li class="px-3 py-2 text-muted">No inventory alerts</li>';
                        const badge = document.getElementById('notificationBadge');
                        if (badge) {
                            badge.classList.add('d-none');
                        }
                        
                        // Close the dropdown
                        const dropdown = bootstrap.Dropdown.getInstance(document.querySelector('#notificationDropdown'));
                        if (dropdown) {
                            dropdown.hide();
                        }
                    }
                });
            }
            
            // Export chart as PNG
            function exportChart(chartId, format) {
                const canvas = document.getElementById(chartId);
                const link = document.createElement('a');
                link.download = `chart-${chartId}-${new Date().toISOString().slice(0,10)}.${format}`;
                link.href = canvas.toDataURL(`image/${format}`);
                link.click();
            }

            // Export chart as PDF
            function exportChartAsPDF(chartId) {
                const { jsPDF } = window.jspdf;
                const canvas = document.getElementById(chartId);
                const chartTitle = document.querySelector(`#${chartId}`).closest('.card').querySelector('.chart-title').textContent;
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('landscape');
                
                pdf.setFontSize(16);
                pdf.text(chartTitle, 15, 15);
                pdf.addImage(imgData, 'PNG', 15, 25, 260, 120);
                pdf.save(`chart-${chartId}-${new Date().toISOString().slice(0,10)}.pdf`);
            }

            // Export chart as Excel
            function exportChartAsExcel(chartId) {
                const chart = charts[chartId];
                if (!chart) return;
                
                let data = [];
                const chartTitle = document.querySelector(`#${chartId}`).closest('.card').querySelector('.chart-title').textContent;
                
                if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
                    data = chart.data.labels.map((label, index) => ({
                        Category: label,
                        Value: chart.data.datasets[0].data[index]
                    }));
                } else if (chart.config.type === 'bar' || chart.config.type === 'line') {
                    data = chart.data.labels.map((label, index) => ({
                        Label: label,
                        Value: chart.data.datasets[0].data[index]
                    }));
                }
                
                const worksheet = XLSX.utils.json_to_sheet(data);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, "Chart Data");
                XLSX.writeFile(workbook, `chart-${chartId}-${new Date().toISOString().slice(0,10)}.xlsx`);
            }

            // Download all charts
            function downloadAllCharts() {
                const chartIds = ['categoryChart', 'inventoryChart', 'expirationChart', 'monthlyChart'];
                
                // Create a zip file containing all charts in different formats
                chartIds.forEach(id => {
                    exportChartAsPDF(id);
                    // Add slight delay between downloads to avoid browser blocking
                    setTimeout(() => exportChart(id, 'png'), 500);
                    setTimeout(() => exportChartAsExcel(id), 1000);
                });
            }
            
            // Render Chart.js chart with professional options
            function renderChart(canvasId, config) {
                const ctx = document.getElementById(canvasId).getContext('2d');
                return new Chart(ctx, {
                    ...config,
                    options: {
                        ...config.options,
                        maintainAspectRatio: false,
                        responsive: true,
                        plugins: {
                            ...config.options?.plugins,
                            tooltip: {
                                ...config.options?.plugins?.tooltip,
                                backgroundColor: '#2e3a4d',
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                padding: 12,
                                displayColors: true,
                                usePointStyle: true,
                                callbacks: {
                                    ...config.options?.plugins?.tooltip?.callbacks,
                                    labelColor: function(context) {
                                        return {
                                            borderColor: 'transparent',
                                            backgroundColor: context.dataset.backgroundColor[context.dataIndex],
                                            borderRadius: 2
                                        };
                                    }
                                }
                            }
                        }
                    }
                });
            }
        </script>
    </body>
</html>