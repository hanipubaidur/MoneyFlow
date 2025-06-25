// =================================================================================
// ||                                                                             ||
// ||         VERSI FINAL - ACCOUNT BALANCES KEMBALI STATIS (SEPERTI AWAL)        ||
// ||                                                                             ||
// =================================================================================

window.activeReportAnimations = window.activeReportAnimations || {};

const reportCurrencyFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0,
});

function formatReportCurrency(amount) {
    return reportCurrencyFormatter.format(Math.floor(amount));
}

function animateReportCurrency(elementId, endValue) {
    const el = document.getElementById(elementId);
    if (!el) return;
    if (window.activeReportAnimations[elementId]) {
        cancelAnimationFrame(window.activeReportAnimations[elementId]);
    }
    const startValue = parseInt(el.textContent.replace(/[^\d-]/g, ''), 10) || 0;
    const finalValue = Math.floor(endValue);
    const duration = 1200;
    let startTime = null;

    if (startValue === finalValue) return; // Pengecekan dikembalikan untuk efisiensi
    
    function animationStep(timestamp) {
        if (!startTime) startTime = timestamp;
        const progress = Math.min((timestamp - startTime) / duration, 1);
        const easedProgress = 1 - Math.pow(1 - progress, 4);
        const currentValue = startValue + (finalValue - startValue) * easedProgress;
        el.textContent = formatReportCurrency(currentValue);
        if (progress < 1) {
            window.activeReportAnimations[elementId] = requestAnimationFrame(animationStep);
        } else {
            el.textContent = formatReportCurrency(finalValue);
            delete window.activeReportAnimations[elementId];
        }
    }
    window.activeReportAnimations[elementId] = requestAnimationFrame(animationStep);
}

function animateReportPercentage(elementId, endValue) {
    const el = document.getElementById(elementId);
    if (!el) return;
    if (window.activeReportAnimations[elementId]) {
        cancelAnimationFrame(window.activeReportAnimations[elementId]);
    }
    const startValue = parseFloat(el.textContent.replace(/[^\d,.-]/g, '').replace(',', '.')) || 0;
    const finalValue = endValue;
    const duration = 1200;
    let startTime = null;
    
    if (Math.abs(startValue - finalValue) < 0.1) return; // Pengecekan dikembalikan untuk efisiensi

    function animationStep(timestamp) {
        if (!startTime) startTime = timestamp;
        const progress = Math.min((timestamp - startTime) / duration, 1);
        const easedProgress = 1 - Math.pow(1 - progress, 4);
        const currentValue = startValue + (finalValue - startValue) * easedProgress;
        el.textContent = currentValue.toFixed(1) + '%';
        if (progress < 1) {
            window.activeReportAnimations[elementId] = requestAnimationFrame(animationStep);
        } else {
            el.textContent = finalValue.toFixed(1) + '%';
            delete window.activeReportAnimations[elementId];
        }
    }
    window.activeReportAnimations[elementId] = requestAnimationFrame(animationStep);
}


document.addEventListener('DOMContentLoaded', function() {
    // LOGIKA ANIMASI AKUN DIKEMBALIKAN KE SINI
    const accountBalances = document.querySelectorAll('.account-balance-anim');
    accountBalances.forEach(el => {
        const finalBalance = parseFloat(el.dataset.balance);
        animateReportCurrency(el.id, finalBalance);
    });

    // Panggil load data untuk pertama kali
    loadReportPageData('month');

    const periodSelector = document.getElementById('reportPeriodSelector');
    if (periodSelector) {
        periodSelector.addEventListener('click', function(e) {
            const button = e.target.closest('[data-period]');
            if (!button || button.classList.contains('active')) return;
            this.querySelectorAll('[data-period]').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            loadReportPageData(button.dataset.period);
        });
    }
});

async function loadReportPageData(period) {
    try {
        const response = await fetch(`reports.php?ajax=1&period=${period}`);
        const data = await response.json();
        
        updatePeriodLabel(data.periodLabel);
        updateMetrics(data.metrics);
        // Panggilan ke updateAccountBalances DIHAPUS
        updateBreakdownTables(data.breakdowns);

    } catch (error) {
        console.error('Failed to load report data:', error);
    }
}

function updatePeriodLabel(label) {
    const el = document.getElementById('periodLabel');
    if (el && label) el.textContent = label;
}

function updateMetrics(metrics) {
    if (!metrics) return;
    animateReportCurrency('netCashflow', metrics.net_cashflow);
    animateReportPercentage('savingsRate', metrics.savings_rate);
    animateReportPercentage('expenseRatio', metrics.expense_ratio);
    animateReportPercentage('debtRatio', metrics.debt_ratio);
}

// FUNGSI updateAccountBalances DIHAPUS KARENA TIDAK DIPERLUKAN LAGI

function updateBreakdownTables(breakdowns) {
    const incomeBody = document.getElementById('incomeBreakdownBody');
    const incomeFoot = document.getElementById('incomeBreakdownFoot');
    const expenseBody = document.getElementById('expenseBreakdownBody');
    const expenseFoot = document.getElementById('expenseBreakdownFoot');
    
    if(incomeBody && incomeFoot) {
        if (breakdowns.income.length > 0) {
            incomeBody.innerHTML = breakdowns.income.map(item => `<tr><td>${item.source_name}</td><td class="text-end">${formatReportCurrency(item.total)}</td><td class="text-end">${breakdowns.totalIncome > 0 ? (item.total / breakdowns.totalIncome * 100).toFixed(1) : 0}%</td></tr>`).join('');
            incomeFoot.innerHTML = `<tr><td>Total</td><td class="text-end">${formatReportCurrency(breakdowns.totalIncome)}</td><td></td></tr>`;
        } else {
            incomeBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No income data.</td></tr>';
            incomeFoot.innerHTML = `<tr><td>Total</td><td class="text-end">Rp0</td><td></td></tr>`;
        }
    }
    
    if(expenseBody && expenseFoot) {
        if (breakdowns.expense.length > 0) {
            expenseBody.innerHTML = breakdowns.expense.map(item => `<tr><td>${item.category_name}</td><td class="text-end">${formatReportCurrency(item.total)}</td><td class="text-end">${breakdowns.totalExpense > 0 ? (item.total / breakdowns.totalExpense * 100).toFixed(1) : 0}%</td></tr>`).join('');
            expenseFoot.innerHTML = `<tr><td>Total</td><td class="text-end">${formatReportCurrency(breakdowns.totalExpense)}</td><td></td></tr>`;
        } else {
            expenseBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No expense data.</td></tr>';
            expenseFoot.innerHTML = `<tr><td>Total</td><td class="text-end">Rp0</td><td></td></tr>`;
        }
    }
}