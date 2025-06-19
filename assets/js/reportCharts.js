const REPORT_COLORS = {
    income: {
        background: 'rgba(40, 167, 69, 0.5)',
        border: 'rgb(40, 167, 69)'
    },
    expense: {
        background: 'rgba(220, 53, 69, 0.5)',
        border: 'rgb(220, 53, 69)'
    },
    categories: {
        'Housing': '#4e73df',
        'Food': '#1cc88a', 
        'Transportation': '#36b9cc',
        'Healthcare': '#f6c23e',
        'Entertainment': '#e74a3b',
        'Shopping': '#858796',
        'Education': '#5a5c69',
        'Debt/Loan': '#2c9faf',
        'Savings': '#7E57C2',
        'Other': '#4A5568'
    }
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Loading report charts...'); // Debug log
    loadReportData();
});

async function loadReportData() {
    try {
        console.log('Loading monthly comparison data...');
        const response = await fetch('api/chart-data.php?type=monthly_comparison');
        const data = await response.json();
        
        console.log('Data received:', data);

        if (data.success && data.monthly_comparison) {
            const chartData = data.monthly_comparison;
            const ctx = document.getElementById('monthlyComparisonChart');
            
            if (!ctx) {
                console.error('Canvas element not found');
                return;
            }

            // Create chart
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.months, // Now shows Jan-Dec
                    datasets: [{
                        label: 'Income',
                        data: chartData.income,
                        backgroundColor: REPORT_COLORS.income.background,
                        borderColor: REPORT_COLORS.income.border,
                        borderWidth: 1
                    }, {
                        label: 'Expenses',
                        data: chartData.expense,
                        backgroundColor: REPORT_COLORS.expense.background,
                        borderColor: REPORT_COLORS.expense.border,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => formatCurrency(value)
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${formatCurrency(context.raw)}`;
                                }
                            }
                        }
                    }
                }
            });

            // Update table
            const tbody = document.getElementById('monthlyStatsBody');
            if (tbody) {
                let totalIncome = 0;
                let totalExpense = 0;

                tbody.innerHTML = chartData.months.map((month, i) => {
                    const income = chartData.income[i];
                    const expense = chartData.expense[i];
                    const net = chartData.net[i];

                    totalIncome += income;
                    totalExpense += expense;

                    return `
                        <tr>
                            <td>${month}</td>
                            <td class="text-end">${formatCurrency(income)}</td>
                            <td class="text-end">${formatCurrency(expense)}</td>
                            <td class="text-end ${net >= 0 ? 'text-success' : 'text-danger'}">
                                ${formatCurrency(net)}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-${net >= 0 ? 'success' : 'danger'}">
                                    ${net >= 0 ? 'Surplus' : 'Deficit'}
                                </span>
                            </td>
                        </tr>
                    `;
                }).join('');

                // Add totals row
                const totalNet = totalIncome - totalExpense;
                tbody.innerHTML += `
                    <tr class="table-light fw-bold">
                        <td>TOTAL</td>
                        <td class="text-end">${formatCurrency(totalIncome)}</td>
                        <td class="text-end">${formatCurrency(totalExpense)}</td>
                        <td class="text-end ${totalNet >= 0 ? 'text-success' : 'text-danger'}">
                            ${formatCurrency(totalNet)}
                        </td>
                        <td></td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Helper function untuk format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function updateMetrics(metrics) {
    if (!metrics) return;

    const elements = {
        netCashflow: document.getElementById('netCashflow'),
        lastNetUpdate: document.getElementById('lastNetUpdate'),
        savingsRate: document.getElementById('savingsRate'),
        expenseRatio: document.getElementById('expenseRatio'),
        debtRatio: document.getElementById('debtRatio'),
        savingsProgress: document.getElementById('savingsProgress')
    };

    // Update values with formatting
    if (elements.netCashflow) {
        elements.netCashflow.textContent = formatCurrency(metrics.net_cashflow);
        // Set timestamp data
        if (elements.lastNetUpdate) {
            elements.lastNetUpdate.dataset.time = metrics.last_update;
            updateTimestamp();  // Reuse existing function from main.js
        }
    }
    if (elements.savingsRate) elements.savingsRate.textContent = `${metrics.savings_rate.toFixed(1)}%`;
    if (elements.expenseRatio) elements.expenseRatio.textContent = `${metrics.expense_ratio.toFixed(1)}%`;
    if (elements.debtRatio) elements.debtRatio.textContent = `${metrics.debt_ratio.toFixed(1)}%`;

    // Update savings progress bar with animation
    if (elements.savingsProgress) {
        const percentage = Math.min(metrics.savings_rate, 100);
        elements.savingsProgress.style.transition = 'width 0.5s ease-in-out';
        elements.savingsProgress.style.width = `${percentage}%`;
        elements.savingsProgress.setAttribute('aria-valuenow', percentage);
    }

    // Update status indicators
    updateStatusIndicators(metrics);
}

function updateStatusIndicators(metrics) {
    const expenseStatus = document.getElementById('expenseStatus');
    if (expenseStatus) {
        expenseStatus.innerHTML = metrics.expense_ratio <= 70 ? 
            '<i class="bx bx-check"></i> Healthy' : 
            '<i class="bx bx-x"></i> High';
    }

    const debtStatus = document.getElementById('debtStatus');
    if (debtStatus) {
        debtStatus.innerHTML = metrics.debt_ratio <= 30 ? 
            '<i class="bx bx-check"></i> Good' : 
            '<i class="bx bx-x"></i> High';
    }
}

function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        document.body.style.cursor = 'wait';
        overlay.style.display = 'flex';
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        document.body.style.cursor = 'default';
        overlay.style.display = 'none';
    }
}

function updateExportPeriod(period) {
    const exportDate = document.getElementById('exportDate');
    const datePickerContainer = document.getElementById('datePickerContainer');
    
    if (!exportDate || !datePickerContainer) return;

    const today = new Date();
    
    switch(period) {
        case 'day':
            datePickerContainer.style.display = 'block';
            exportDate.value = today.toISOString().split('T')[0];
            break;
        case 'week':
            const monday = new Date(today);
            monday.setDate(today.getDate() - today.getDay() + 1);
            exportDate.value = monday.toISOString().split('T')[0];
            datePickerContainer.style.display = 'none';
            break;
        case 'month':
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            exportDate.value = firstDay.toISOString().split('T')[0];
            datePickerContainer.style.display = 'none';
            break;
        case 'year':
            const firstDayOfYear = new Date(today.getFullYear(), 0, 1);
            exportDate.value = firstDayOfYear.toISOString().split('T')[0];
            datePickerContainer.style.display = 'none';
            break;
    }
}