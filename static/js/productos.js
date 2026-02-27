// Carga de productos desde la BD (tabla producto)
let products = [];

function normalizeRow(row){
  return {
    id: row.id_producto || row.id || row.ID || row.codigo,
    name: row.nombre || row.name || 'Producto',
    price: Number(row.precio_venta || row.precio || row.precio_base || row.price || 0),
    image: row.imagen || row.image || '',
    categoria: (row.categoria || row.category || '').toLowerCase(),
    subcategoria: (row.subcategoria || row.subcategory || '').toLowerCase(),
    stock: Number(row.stock || row.existencias || 1),
    descripcion: row.descripcion || row.description || '',
    codigo: row.codigo || row.code || '',
    marca: row.marca || row.brand || '',
    origen: row.origen || row.origin || '',
    peso: row.peso || row.peso_neto || row.weight || '',
    unidad: row.unidad || row.unit || '',
    composicion: row.composicion || row.ingredientes || row.composition || '',
    presentacion: row.presentacion || row.presentation || '',
    etiquetas: row.etiquetas || row.tags || ''
  };
}

function getParam(name){
  const url = new URL(window.location.href);
  return url.searchParams.get(name);
}

async function fetchProducts(){
  try{
    const params = new URLSearchParams();
    const q = getParam('q');
    const categoria = getParam('categoria');
    const subcategoria = getParam('subcategoria');
    if(q) params.set('q', q);
    if(categoria) params.set('categoria', categoria);
    if(subcategoria) params.set('subcategoria', subcategoria);

    const res = await fetch('backend/php/get_products.php' + (params.toString()?`?${params.toString()}`:''));
    const data = await res.json();
    products = (data && data.products ? data.products : []).map(normalizeRow);
  }catch(e){
    console.error('Error al cargar productos', e);
    products = [];
  }
}

// FUNCIONES PRINCIPALES
let currentFilters = {
    search: '',
    minPrice: 2,
    maxPrice: 50,
    categories: [],
    tags: []
};

function renderProducts() {
    const container = document.getElementById('products-container');
    // Si el contenedor fue renderizado por PHP, no duplicar tarjetas
    if (container && container.dataset && container.dataset.serverRender === '1') {
        // NO INTERCEPTAR CLICKS EN BTN-INFO
        return;
    }
    container.innerHTML = '';

    const filteredProducts = products.filter(product => {
        // Filtrar por búsqueda
        if (currentFilters.search && !product.name.toLowerCase().includes(currentFilters.search.toLowerCase())) {
            return false;
        }

        // Filtrar por precio
        if (product.price < currentFilters.minPrice || product.price > currentFilters.maxPrice) {
            return false;
        }

        // Filtrar por categorías (comparación por texto de categoria)
        if (currentFilters.categories.length > 0) {
            const catText = (product.categoria || '').toLowerCase();
            const hasCategory = currentFilters.categories.some(cat => catText.includes(cat.toLowerCase()));
            if (!hasCategory) return false;
        }

        return true;
    });

    // Mostrar conteo
    const countEl = document.querySelector('.products-count');
    if(countEl) countEl.textContent = `Mostrando 1-${filteredProducts.length} de ${filteredProducts.length} resultados`;

    // Renderizar productos
    filteredProducts.forEach(product => {
        const rating = (Math.random() * (5.0 - 4.0) + 4.0).toFixed(1);
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
          <div class="product-image">
            <a href="detalle_producto.php?id=${product.id}">
                <img src="${product.image}" alt="${product.name}">
            </a>
            <button class="favorite-btn" aria-label="Añadir a favoritos" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}" data-image="${product.image}"><i class="far fa-heart"></i></button>
          </div>
          <div class="product-info">
            <div class="product-rating">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                ${rating >= 4.8 ? '<i class="fas fa-star"></i>' : '<i class="fas fa-star-half-alt"></i>'}
                <span class="rating-number">(${rating})</span>
            </div>
            <a href="detalle_producto.php?id=${product.id}" style="text-decoration:none;">
                <h3 class="product-name">${product.name}</h3>
            </a>
            <div class="product-price">$${product.price.toFixed(2)}</div>
            <div class="product-actions">
              <button class="btn btn-add add-to-cart-btn" data-id="${product.id}" data-title="${product.name}" data-price="${product.price}" data-image="${product.image}" data-qty="1">
                <i class="fas fa-cart-plus"></i> Agregar
              </button>
              <a href="detalle_producto.php?id=${product.id}" class="btn btn-info">Ver Detalles</a>
            </div>
          </div>
        `;
        container.appendChild(card);
    });
}

function showToast(message) {
    const toast = document.getElementById('toast');
    if(!toast) return;
    toast.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// EVENT LISTENERS
// El input de búsqueda puede ser el del header o el del sidebar
const searchInputEl = document.getElementById('search-input')
    || document.getElementById('site-search')
    || document.getElementById('site-search-sidebar');
if (searchInputEl) {
    searchInputEl.addEventListener('input', function() {
        currentFilters.search = this.value || '';
        renderProducts();
    });
}

const searchBtnEl = document.getElementById('search-btn');
if (searchBtnEl && searchInputEl) {
    searchBtnEl.addEventListener('click', function() {
        currentFilters.search = searchInputEl.value || '';
        renderProducts();
    });
}

// Sliders de precio
const minSlider = document.getElementById('price-min');
const maxSlider = document.getElementById('price-max');
const minDisplay = document.getElementById('min-display');
const maxDisplay = document.getElementById('max-display');
const priceValue = document.getElementById('price-value');

function updatePriceRange() {
    let min = parseInt(minSlider.value);
    let max = parseInt(maxSlider.value);

    if (min >= max) {
        minSlider.value = max - 1;
        min = max - 1;
    }

    if (max <= min) {
        maxSlider.value = min + 1;
        max = min + 1;
    }

    minDisplay.textContent = `${min} €`;
    maxDisplay.textContent = `${max} €`;
    priceValue.textContent = `${min} € — ${max} €`;

    currentFilters.minPrice = min;
    currentFilters.maxPrice = max;
    renderProducts();
}

if (minSlider && maxSlider) {
    minSlider.addEventListener('input', updatePriceRange);
    maxSlider.addEventListener('input', updatePriceRange);
}

// Filtros por categoría
document.querySelectorAll('input[data-category]').forEach(input => {
    input.addEventListener('change', function() {
        const category = this.dataset.category;
        if (this.checked) {
            currentFilters.categories.push(category);
        } else {
            const index = currentFilters.categories.indexOf(category);
            if (index > -1) {
                currentFilters.categories.splice(index, 1);
            }
        }
        renderProducts();
    });
});

// Filtros por etiqueta
document.querySelectorAll('input[data-tag]').forEach(input => {
    input.addEventListener('change', function() {
        const tag = this.dataset.tag;
        if (this.checked) {
            currentFilters.tags.push(tag);
        } else {
            const index = currentFilters.tags.indexOf(tag);
            if (index > -1) {
                currentFilters.tags.splice(index, 1);
            }
        }
        renderProducts();
    });
});

// Botón de filtrar
const filterBtnEl = document.querySelector('.filter-btn');
if (filterBtnEl) {
    filterBtnEl.addEventListener('click', function() {
        renderProducts();
    });
}

// Inicializar
// ====== Secciones por categorías dinámicas desde BD ======
let categories = [];

function buildCategoriesFromProducts(){
  const set = new Map();
  products.forEach(p=>{
    const name = (p.categoria||'').trim().toLowerCase();
    if(!name) return;
    if(!set.has(name)){
      set.set(name, { name, display: name.toUpperCase() });
    }
  });
  categories = Array.from(set.values()).sort((a,b)=> a.name.localeCompare(b.name));
}

function renderCards(container, list){
  list.forEach(product=>{
    const card = document.createElement('div');
    card.className = 'product-card';
    card.innerHTML = `
      <div class="product-image">
        <a href="detalle_producto.php?id=${product.id}">
            <img src="${product.image}" alt="${product.name}">
        </a>
        <button class="favorite-btn" aria-label="Añadir a favoritos" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}" data-image="${product.image}"><i class="far fa-heart"></i></button>
      </div>
      <div class="product-info">
        <a href="detalle_producto.php?id=${product.id}" style="text-decoration:none;">
            <h3 class="product-name">${product.name}</h3>
        </a>
        <div class="product-price">$${(Number(product.price)||0).toFixed(2)}</div>
        <div class="product-actions">
          <button class="btn btn-add add-to-cart-btn" data-id="${product.id}" data-title="${product.name}" data-price="${product.price}" data-image="${product.image}" data-qty="1">
            <i class="fas fa-cart-plus"></i> Agregar
          </button>
          <a href="detalle_producto.php?id=${product.id}" class="btn btn-info">Ver Detalles</a>
        </div>
      </div>
    `;
    container.appendChild(card);
  });
}

function renderCategorySections(){
  const root = document.getElementById('category-sections');
  if(!root) return;
  root.innerHTML = '';

  categories.forEach(cat=>{
    const section = document.createElement('section');
    section.className = 'category-block';
    section.innerHTML = `<h2 class="category-title">${cat.display}</h2><div class="category-grid"></div>`;
    root.appendChild(section);

    const grid = section.querySelector('.category-grid');
    const items = products.filter(p=>{
      const catText = (p.categoria||'').toLowerCase();
      return catText.includes((cat.name||'').toLowerCase());
    });
    renderCards(grid, items);
  });
}

// Delegación global por si algún render no vincula eventos
document.addEventListener('click', function(e){
  const infoBtn = e.target.closest && e.target.closest('.btn-info');
  if(infoBtn){
    // Permitir navegación normal (no preventDefault) para que el enlace <a> funcione
    return;
  }
});
// Inicializar con carga desde API
(async function init(){
  await fetchProducts();
  buildCategoriesFromProducts();
  renderProducts();
  renderCategorySections();
})();
