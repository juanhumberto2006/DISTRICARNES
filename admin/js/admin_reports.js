document.addEventListener('DOMContentLoaded', () => {
    // Initial Load
    updateReports();
});

let charts = {}; // Store chart instances

async function updateReports() {
    const range = document.getElementById('dateRange').value;
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    
    // Construct query params
    let params = new URLSearchParams({ range });
    if (range === 'custom') {
        if (!start || !end) {
            alert('Por favor selecciona fecha inicio y fin');
            return;
        }
        params.append('start', start);
        params.append('end', end);
    }
    
    try {
        const res = await fetch(`../backend/php/get_reports.php?${params.toString()}`);
        const data = await res.json();
        
        if (data.ok) {
            updateSales(data.sales);
            updateProducts(data.products);
            updateCustomers(data.customers);
            updateInventory(data.inventory);
            updateTopTable(data.topTable);
        } else {
            console.error('Error fetching reports:', data);
        }
    } catch (err) {
        console.error('Network error:', err);
    }
}

function updateSales(data) {
    if (data.error) return;
    
    // Update Stats
    document.getElementById('statTotalSales').textContent = formatCurrency(data.total);
    document.getElementById('statOrdersCount').textContent = data.orders;
    document.getElementById('statAvgOrder').textContent = formatCurrency(data.avg);
    
    // Update Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    if (charts.sales) charts.sales.destroy();
    
    charts.sales = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Ventas Diarias',
                data: data.data,
                borderColor: '#48bb78',
                backgroundColor: 'rgba(72, 187, 120, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                y: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { color: 'rgba(255,255,255,0.1)' } }
            }
        }
    });
    
    // Update Trend Chart (Using same data for simplicity or extended logic)
    // For now, we reuse the daily data for the detailed trend
    const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
    if (charts.trend) charts.trend.destroy();
    
    charts.trend = new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Ventas (Selección)',
                data: data.data,
                backgroundColor: '#4299e1',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { color: '#fff' }, grid: { display: false } },
                y: { ticks: { color: '#fff' }, grid: { color: 'rgba(255,255,255,0.1)' } }
            }
        }
    });
}

function updateProducts(data) {
    if (data.error) return;
    
    document.getElementById('statTopProductSales').textContent = data.topProduct;
    document.getElementById('statCategoriesCount').textContent = data.totalCats;
    document.getElementById('statLowStock').textContent = data.lowStock;
    
    const ctx = document.getElementById('productsChart').getContext('2d');
    if (charts.products) charts.products.destroy();
    
    charts.products = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.catLabels,
            datasets: [{
                data: data.catData,
                backgroundColor: ['#c53030', '#4299e1', '#9f7aea', '#ed8936', '#48bb78', '#ecc94b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { color: '#fff', boxWidth: 10 } }
            }
        }
    });
}

function updateCustomers(data) {
    if (data.error) return;
    
    document.getElementById('statNewCustomers').textContent = data.new;
    document.getElementById('statReturningCustomers').textContent = data.recurring;
    document.getElementById('statCustomerRetention').textContent = data.retention + '%';
    
    const ctx = document.getElementById('customersChart').getContext('2d');
    if (charts.customers) charts.customers.destroy();
    
    charts.customers = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Nuevos', 'Recurrentes'],
            datasets: [{
                label: 'Clientes',
                data: [data.new, data.recurring],
                backgroundColor: ['#9f7aea', '#4299e1']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#fff' }, grid: { display: false } },
                y: { ticks: { color: '#fff' }, grid: { color: 'rgba(255,255,255,0.1)' } }
            }
        }
    });
}

function updateInventory(data) {
    if (data.error) return;
    
    document.getElementById('statInventoryValue').textContent = formatCurrency(data.value);
    document.getElementById('statTotalItems').textContent = data.items;
    
    const ctx = document.getElementById('inventoryChart').getContext('2d');
    if (charts.inventory) charts.inventory.destroy();
    
    charts.inventory = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Alto (>20)', 'Medio (10-20)', 'Bajo (5-10)', 'Crítico (<5)', 'Reabastecido'],
            datasets: [{
                label: 'Estado Stock',
                data: data.status,
                borderColor: '#ed8936',
                backgroundColor: 'rgba(237, 137, 54, 0.2)',
                pointBackgroundColor: '#ed8936'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    ticks: { display: false },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    angleLines: { color: 'rgba(255,255,255,0.1)' },
                    pointLabels: { color: '#fff' }
                }
            },
            plugins: { legend: { display: false } }
        }
    });
}

function updateTopTable(rows) {
    const tbody = document.querySelector('#topProductsTable tbody');
    tbody.innerHTML = '';
    
    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No hay datos en este período</td></tr>';
        return;
    }
    
    rows.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${r.nombre}</td>
            <td>${r.categoria || 'Sin Cat'}</td>
            <td>${r.unidades}</td>
            <td>${formatCurrency(r.ingresos)}</td>
            <td><span class="trend-indicator trend-neutral">--</span></td>
            <td>${r.stock}</td>
        `;
        tbody.appendChild(tr);
    });
}

function formatCurrency(val) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(val || 0);
}
