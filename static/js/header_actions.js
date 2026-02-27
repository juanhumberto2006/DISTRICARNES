document.addEventListener('DOMContentLoaded', () => {
  // Toggle user dropdown safely
  const menuButton = document.querySelector('.menu-button');
  const userDropdown = document.getElementById('userDropdown');
  if (menuButton && userDropdown) {
    menuButton.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = userDropdown.style.display === 'block';
      userDropdown.style.display = isOpen ? 'none' : 'block';
      menuButton.setAttribute('aria-expanded', (!isOpen).toString());
    });

    document.addEventListener('click', (e) => {
      if (userDropdown.style.display === 'block') {
        const within = userDropdown.contains(e.target) || menuButton.contains(e.target);
        if (!within) {
          userDropdown.style.display = 'none';
          menuButton.setAttribute('aria-expanded', 'false');
        }
      }
    });
  }

  // Basic search redirect safety if using header form
  const searchForm = document.querySelector('.ml-search form');
  const searchInput = document.querySelector('.ml-search input[type="search"]');
  if (searchForm && searchInput) {
    searchForm.addEventListener('submit', (e) => {
      const q = (searchInput.value || '').trim();
      // Let native submit happen; optionally can route to productos.html?q=...
      if (!q.length) {
        // Prevent empty submissions from reloading
        e.preventDefault();
      }
    });
  }

  // ====== Lógica de autenticación y visibilidad global (carrito / botones login) ======
  const AuthSystem = {
    getSession(){
      const userData = localStorage.getItem('userData');
      const sessionData = sessionStorage.getItem('currentSession');
      let raw = null;
      try { raw = userData ? JSON.parse(userData) : (sessionData ? JSON.parse(sessionData) : null); } catch(e){ raw = null; }
      return raw && raw.user ? raw.user : raw;
    },
    isLoggedIn(user){
      if(!user) return false;
      // campos posibles: isLoggedIn, estado, bloqueado
      const blocked = String((user.estado||'').toLowerCase()) === 'bloqueado' || Boolean(user.bloqueado);
      return (Boolean(user.isLoggedIn) || 'correo_electronico' in user || 'email' in user) && !blocked;
    },
    isBlocked(user){
      if(!user) return false;
      return String((user.estado||'').toLowerCase()) === 'bloqueado' || Boolean(user.bloqueado);
    },
    checkUserSession(){
      const quickLinks = document.getElementById('quickLinks'); // contiene carrito y enlaces rápidos
      const authButtons = document.getElementById('authButtons');
      const userLoggedButtons = document.getElementById('userLoggedButtons');

      const user = this.getSession();
      const logged = this.isLoggedIn(user);
      const blocked = this.isBlocked(user);

      // Carrito: siempre visible; si se desea ocultar cuando bloqueado, descomentar:
      // if (quickLinks) quickLinks.style.display = blocked ? 'none' : 'flex';
      if (quickLinks) quickLinks.style.display = 'flex';

      if (logged) {
        if (authButtons) authButtons.style.display = 'none';
        if (userLoggedButtons) userLoggedButtons.style.display = 'block';
      } else {
        if (authButtons) authButtons.style.display = 'block';
        if (userLoggedButtons) userLoggedButtons.style.display = 'none';
      }
    }
  };

  // Exponer para que otras páginas puedan invocarlo
  window.AuthSystem = AuthSystem;
  // Ejecutar al cargar
  try { AuthSystem.checkUserSession(); } catch(e) { /* no-op */ }

  // Navegación de menú de usuario
  document.addEventListener('click', function(e){
    var link = e.target && e.target.closest ? e.target.closest('#userDropdown .menu-item') : null;
    if(!link) return;
    var text = (link.textContent || '').toLowerCase();
    var url = null;
    var tab = null;
    if(text.indexOf('mi perfil')>=0){ url = './perfil.html'; tab = 'overview'; }
    else if(text.indexOf('editar perfil')>=0){ url = './perfil.html'; tab = 'edit'; }
    else if(text.indexOf('cambiar contraseña')>=0){ url = './perfil.html'; tab = 'password'; }
    else if(text.indexOf('configuración')>=0){ url = './perfil.html'; tab = 'settings'; }
    else if(text.indexOf('historial')>=0){ url = './historial.html'; }
    else if(text.indexOf('favoritos')>=0){ url = './favoritos.html'; }
    if(url){
      e.preventDefault();
      var dd = document.getElementById('userDropdown');
      if(dd){ dd.style.display = 'none'; dd.classList.remove('active'); }
      var sep = url.indexOf('?')===-1 ? '?' : '&';
      if(tab){ window.location.href = url + sep + 'tab=' + encodeURIComponent(tab); }
      else { window.location.href = url; }
    }
  });
});
