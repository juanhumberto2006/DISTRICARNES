// Variables de estado
let products = [];
let cart = [];
let currentCategory = 'all';

document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    loadProducts();
    
    // Búsqueda en tiempo real
    document.getElementById('productSearch').addEventListener('input', (e) => {
        renderProducts(e.target.value);
    });
});

// Cargar categorías
function loadCategories() {
    fetch('../backend/php/get_categories.php')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('categoriesContainer');
            // Mantener el botón "Todos"
            let html = '<span class="cat-chip active" onclick="filterCategory(\'all\', this)">Todos</span>';
            if (data.categories) {
                data.categories.forEach(cat => {
                    // Si id es null, usar name como ID
                    const id = cat.id || cat.name; 
                    html += `<span class="cat-chip" onclick="filterCategory('${id}', this)">${cat.display}</span>`;
                });
            }
            container.innerHTML = html;
        })
        .catch(err => console.error('Error loading categories:', err));
}

function filterCategory(catId, element) {
    currentCategory = catId;
    
    // Update UI active state
    document.querySelectorAll('.cat-chip').forEach(el => el.classList.remove('active'));
    if(element) element.classList.add('active');
    
    renderProducts(document.getElementById('productSearch').value);
}

// Cargar productos
function loadProducts() {
    const grid = document.getElementById('productsGrid');
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #666; padding: 20px;">Cargando productos...</div>';

    fetch('../backend/php/get_products.php')
        .then(res => {
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            return res.json();
        })
        .then(data => {
            if (data.ok && data.products) {
                products = data.products.map(p => {
                    // Helper para buscar propiedades case-insensitive
                    const getVal = (keys) => {
                        for (let k of keys) {
                            if (p[k] !== undefined) return p[k];
                        }
                        // Try case-insensitive
                        const pKeys = Object.keys(p);
                        for (let k of keys) {
                            const found = pKeys.find(pk => pk.toLowerCase() === k.toLowerCase());
                            if (found) return p[found];
                        }
                        return null;
                    };

                    return {
                        id: getVal(['id_producto', 'id', 'producto_id', 'code', 'codigo']) || 'N/A',
                        name: getVal(['nombre', 'name', 'producto', 'title']) || 'Sin Nombre',
                        price: parseFloat(getVal(['precio', 'price', 'valor', 'costo']) || 0),
                        stock: parseInt(getVal(['stock', 'cantidad', 'qty', 'existencias']) || 0),
                        image: getVal(['imagen', 'image', 'img', 'foto', 'url_imagen']) || '../assets/img/default-product.png',
                        category_id: getVal(['categoria_id', 'id_categoria', 'category_id']),
                        category_name: getVal(['categoria', 'category', 'cat_nombre']) || '',
                        // Mapear código de barras o lote
                        code: getVal(['codigo_barra', 'barcode', 'codigo', 'code', 'lote']) || ''
                    };
                });
                renderProducts();
            } else {
                throw new Error(data.error || 'Formato de datos incorrecto');
            }
        })
        .catch(err => {
            console.error('Error loading products:', err);
            grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #e61515; padding: 20px;">
                <i class="fas fa-exclamation-triangle"></i> Error cargando productos.<br>
                <small>${err.message}</small><br>
                <button class="btn-secondary" onclick="loadProducts()" style="margin-top:10px">Reintentar</button>
            </div>`;
        });
}

function renderProducts(searchTerm = '') {
    const grid = document.getElementById('productsGrid');
    grid.innerHTML = '';
    
    const term = searchTerm.toLowerCase();
    
    const filtered = products.filter(p => {
        const matchesSearch = p.name.toLowerCase().includes(term);
        const matchesCat = currentCategory === 'all' || 
                           p.category_id == currentCategory || 
                           p.category_name.toLowerCase() === currentCategory.toLowerCase();
        return matchesSearch && matchesCat;
    });
    
    if (filtered.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #666; padding: 20px;">No se encontraron productos</div>';
        return;
    }
    
    filtered.forEach(p => {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.onclick = () => addToCart(p);
        
        // Manejo de ruta de imagen (asumiendo que vienen relativas desde root)
        let imgPath = p.image;
        if (imgPath && !imgPath.startsWith('http') && !imgPath.startsWith('../')) {
            imgPath = '../' + imgPath;
        }
        
        const fmtPrice = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(p.price);
        
        card.innerHTML = `
            <img src="${imgPath}" class="product-img" onerror="this.src='../assets/img/logo-placeholder.png'">
            <div class="product-info">
                <div class="product-name">${p.name}</div>
                <div class="product-stock">Stock: ${p.stock}</div>
                <div class="product-price">${fmtPrice}</div>
            </div>
        `;
        grid.appendChild(card);
    });
}

// Lógica del Carrito
function addToCart(product) {
    if (product.stock <= 0) {
        Swal.fire({ icon: 'error', title: 'Sin Stock', text: 'Este producto no tiene existencias disponibles', timer: 1500, showConfirmButton: false });
        return;
    }
    
    const existing = cart.find(item => item.id === product.id);
    
    if (existing) {
        if (existing.qty < product.stock) {
            existing.qty++;
        } else {
            Swal.fire({ icon: 'warning', title: 'Stock Máximo', text: 'No puedes agregar más unidades de las disponibles', timer: 1500 });
        }
    } else {
        cart.push({ ...product, qty: 1 });
    }
    updateCartUI();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

function changeQty(index, delta) {
    const item = cart[index];
    const newQty = item.qty + delta;
    
    if (newQty > 0 && newQty <= item.stock) {
        item.qty = newQty;
    } else if (newQty <= 0) {
        removeFromCart(index);
        return;
    }
    updateCartUI();
}

function clearCart() {
    if(cart.length > 0) {
        cart = [];
        updateCartUI();
    }
}

function updateCartUI() {
    const container = document.getElementById('cartItems');
    container.innerHTML = '';
    
    let subtotal = 0;
    
    if (cart.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: #666; padding: 20px;">Carrito vacío</div>';
        document.getElementById('payButton').disabled = true;
    } else {
        document.getElementById('payButton').disabled = false;
        
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.qty;
            subtotal += itemTotal;
            
            const div = document.createElement('div');
            div.className = 'cart-item';
            div.innerHTML = `
                <div class="cart-item-info">
                    <div class="cart-item-title">${item.name}</div>
                    <div class="cart-item-price">$${item.price.toLocaleString('es-CO')} x ${item.qty}</div>
                </div>
                <div class="cart-item-controls">
                    <button class="qty-btn" onclick="changeQty(${index}, -1)">-</button>
                    <span style="min-width:20px; text-align:center">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty(${index}, 1)">+</button>
                    <button class="remove-btn" onclick="removeFromCart(${index})"><i class="fas fa-times"></i></button>
                </div>
            `;
            container.appendChild(div);
        });
    }
    
    // Calcular descuento y total
    const discountInput = document.getElementById('discountInput');
    const discount = discountInput ? parseFloat(discountInput.value || 0) : 0;
    const total = Math.max(0, subtotal - discount);

    const fmtSubtotal = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(subtotal);
    const fmtTotal = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(total);
    
    document.getElementById('subtotalDisplay').textContent = fmtSubtotal;
    document.getElementById('totalDisplay').textContent = fmtTotal;
    
    // Actualizar modal si está abierto
    const modalTotalEl = document.getElementById('modalTotal');
    if (modalTotalEl) modalTotalEl.textContent = fmtTotal;
    
    // Recalcular cambio si ya hay monto recibido
    calculateChange();
}

function calculateChange() {
    const totalText = document.getElementById('totalDisplay').textContent;
    // Limpiar formato moneda para obtener número
    const total = parseFloat(totalText.replace(/[^0-9]/g, '')) || 0;
    
    const receivedInput = document.getElementById('amountReceived');
    const received = receivedInput ? parseFloat(receivedInput.value || 0) : 0;
    
    const changeDisplay = document.getElementById('changeDisplay');
    if (changeDisplay) {
        if (received >= total) {
            const change = received - total;
            changeDisplay.value = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(change);
            changeDisplay.style.color = '#4caf50';
        } else {
            changeDisplay.value = '$0';
            changeDisplay.style.color = '#aaa';
        }
    }
}

// Checkout
function openCheckoutModal() {
    if (cart.length === 0) return;
    document.getElementById('checkoutModal').style.display = 'block';
    // Reset client search logic on open if empty
    if (!document.getElementById('clientSearch').value) {
        resetClient();
    }
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').style.display = 'none';
}

// Client Search Logic
let searchTimeout = null;
const clientSearchInput = document.getElementById('clientSearch');
const clientResults = document.getElementById('clientResults');

clientSearchInput.addEventListener('input', (e) => {
    const q = e.target.value.trim();
    if (q.length < 2) {
        clientResults.style.display = 'none';
        return;
    }
    
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetch(`../backend/php/search_clients.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (data.ok && data.clients && data.clients.length > 0) {
                    let html = '';
                    data.clients.forEach(c => {
                        html += `<div style="padding:10px; cursor:pointer; border-bottom:1px solid #333;" onclick="selectClient('${c.name}', '${c.email}')">
                            <div style="font-weight:bold">${c.name}</div>
                            <div style="font-size:0.8rem; color:#aaa">${c.email}</div>
                        </div>`;
                    });
                    clientResults.innerHTML = html;
                    clientResults.style.display = 'block';
                } else {
                    clientResults.innerHTML = '<div style="padding:10px; color:#aaa">No encontrado</div>';
                    clientResults.style.display = 'block';
                }
            });
    }, 300);
});

function selectClient(name, email) {
    document.getElementById('clientSearch').value = name;
    document.getElementById('clientEmail').value = email; // hidden
    document.getElementById('clientEmailDisplay').value = email; // display
    clientResults.style.display = 'none';
}

function resetClient() {
    document.getElementById('clientSearch').value = '';
    document.getElementById('clientEmail').value = '';
    document.getElementById('clientEmailDisplay').value = '';
    clientResults.style.display = 'none';
}

function processSale() {
    // Si el usuario escribió un nombre manual pero no seleccionó de la lista, lo usamos
    const clientName = document.getElementById('clientSearch').value || 'Público General';
    const clientEmail = document.getElementById('clientEmailDisplay').value || '';
    
    const paymentMethod = document.getElementById('paymentMethod').value;
    const discountInput = document.getElementById('discountInput');
    const discount = discountInput ? parseFloat(discountInput.value || 0) : 0;
    
    // Calcular total real basado en UI actual
    const subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
    const total = Math.max(0, subtotal - discount);

    const payload = {
        client_name: clientName,
        client_email: clientEmail,
        payment_method: paymentMethod,
        items: cart.map(i => ({ id: i.id, qty: i.qty, price: i.price, title: i.name })),
        subtotal: subtotal,
        discount: discount,
        total: total
    };
    
    Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading() });
    
    fetch('../backend/php/pos_checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Venta Exitosa',
                text: `Venta #${data.order_id} registrada correctamente`,
                showCancelButton: true,
                confirmButtonText: 'Imprimir Ticket',
                cancelButtonText: 'Cerrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Abrir ticket en nueva ventana
                    window.open(`../backend/php/print_receipt.php?id=${data.order_id}`, '_blank', 'width=400,height=600');
                }
                closeCheckoutModal();
                clearCart();
                loadProducts(); // Recargar stock
            });
        } else {
            Swal.fire('Error', data.message || 'Error al procesar venta', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}
