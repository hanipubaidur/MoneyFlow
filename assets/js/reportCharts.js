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
    let selectedDate = null;

    if (periodSelector) {
        periodSelector.addEventListener('click', function(e) {
            const button = e.target.closest('[data-period]');
            if (button) {
                if (button.classList.contains('active')) return;
                this.querySelectorAll('[data-period]').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                selectedDate = null;
                loadReportPageData(button.dataset.period);
            }
            // Handle Select Date button
            if (e.target.id === 'selectDateBtn') {
                showCustomDateModal();
            }
        });
    }

    // Modal Custom Date Picker
    function showCustomDateModal() {
        const modal = new bootstrap.Modal(document.getElementById('customDateModal'));
        populateCustomDatePicker();
        modal.show();
    }

    function populateCustomDatePicker() {
        const daySel = document.getElementById('customDateDay');
        const monthSel = document.getElementById('customDateMonth');
        const yearSel = document.getElementById('customDateYear');
        const now = new Date();
        // Tahun: 5 tahun ke belakang sampai tahun ini
        yearSel.innerHTML = '';
        for (let y = now.getFullYear(); y >= now.getFullYear() - 5; y--) {
            yearSel.innerHTML += `<option value="${y}">${y}</option>`;
        }
        // Bulan
        monthSel.innerHTML = '';
        for (let m = 1; m <= 12; m++) {
            monthSel.innerHTML += `<option value="${m}">${m.toString().padStart(2, '0')}</option>`;
        }
        // Hari (default 31, akan diubah saat bulan/tahun berubah)
        updateCustomDateDays();

        yearSel.onchange = monthSel.onchange = updateCustomDateDays;
        function updateCustomDateDays() {
            const year = parseInt(yearSel.value);
            const month = parseInt(monthSel.value);
            const daysInMonth = new Date(year, month, 0).getDate();
            daySel.innerHTML = '';
            for (let d = 1; d <= daysInMonth; d++) {
                daySel.innerHTML += `<option value="${d}">${d.toString().padStart(2, '0')}</option>`;
            }
        }
        // Set default ke hari ini
        yearSel.value = now.getFullYear();
        monthSel.value = now.getMonth() + 1;
        daySel.value = now.getDate();
    }

    document.getElementById('showDateBtn').addEventListener('click', function() {
        const day = document.getElementById('customDateDay').value.padStart(2, '0');
        const month = document.getElementById('customDateMonth').value.padStart(2, '0');
        const year = document.getElementById('customDateYear').value;
        selectedDate = `${year}-${month}-${day}`; // tetap kirim ke backend yyyy-mm-dd
        // Remove active from all period buttons
        document.querySelectorAll('#reportPeriodSelector [data-period]').forEach(btn => btn.classList.remove('active'));
        // Hide modal
        bootstrap.Modal.getInstance(document.getElementById('customDateModal')).hide();
        loadReportPageData('date', selectedDate);
    });
});

// Ubah loadReportPageData agar bisa menerima tanggal
async function loadReportPageData(period, date = null) {
    try {
        let url = `reports.php?ajax=1&period=${period}`;
        if (period === 'date' && date) url += `&date=${date}`;
        const response = await fetch(url);
        const data = await response.json();

        updatePeriodLabel(data.periodLabel);
        updateMetrics(data.metrics);
        updateBreakdownTables(data.breakdowns);

        // Tampilkan pesan jika tidak ada data
        showNoDataIfNeeded(data);

    } catch (error) {
        console.error('Failed to load report data:', error);
    }
}

// Tampilkan pesan jika tidak ada data
function showNoDataIfNeeded(data) {
    // Cek semua metrik dan breakdown kosong
    const noIncome = !data.breakdowns.income.length;
    const noExpense = !data.breakdowns.expense.length;
    const noCashflow = !data.metrics || (data.metrics.net_cashflow === 0 && data.metrics.savings_rate === 0 && data.metrics.expense_ratio === 0 && data.metrics.debt_ratio === 0);
    if (noIncome && noExpense && noCashflow) {
        document.getElementById('incomeBreakdownBody').innerHTML = '<tr><td colspan="3" class="text-center text-muted">No data found.</td></tr>';
        document.getElementById('incomeBreakdownFoot').innerHTML = '';
        document.getElementById('expenseBreakdownBody').innerHTML = '<tr><td colspan="3" class="text-center text-muted">No data found.</td></tr>';
        document.getElementById('expenseBreakdownFoot').innerHTML = '';
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