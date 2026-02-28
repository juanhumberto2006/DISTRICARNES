// Guardián de sesión: evita volver a páginas protegidas tras cerrar sesión
// y redirige siempre al login apropiado. También refuerza acceso en /admin/.
(function(){
  const LOGOUT_FLAG_KEY = 'logoutFlag';

  function isLoginPage(){
    const p = location.pathname.toLowerCase();
    return p.includes('/login/') || p.endsWith('login.html') || p.endsWith('/login') || p.includes('admin_login.html');
  }

  function isAdminArea(){
    return location.pathname.toLowerCase().includes('/admin/');
  }

  function getSession(){
    let sessionData = null;
    try {
      const storedData = localStorage.getItem('userData') || sessionStorage.getItem('currentSession');
      if (storedData) {
        sessionData = JSON.parse(storedData);
      }
    } catch (e) {
      console.error('Error parsing session data:', e);
      return { logged: false, user: null };
    }

    const logged = !!(sessionData && sessionData.isLoggedIn);
    const user = sessionData && sessionData.user ? sessionData.user : null;

    return { logged, user };
  }

  function isAdminUser(user){
    const role = user && (user.rol || user.role || user.tipo || user.userType);
    const r = String(role || '').toLowerCase();
    return ['admin','administrador','superuser','staff'].includes(r) || r.includes('admin');
  }

  function redirectToLogin(){
    try {
      const host = (location && location.hostname) ? location.hostname.toLowerCase() : '';
      const isRender = /onrender\.com$/.test(host);
      const target = isRender
        ? (location.origin + '/')
        : (location.origin + '/DISTRICARNES/index.html');
      location.replace(target);
    } catch(e) {}
  }

  function markLoggedOut(){
    try {
      sessionStorage.setItem(LOGOUT_FLAG_KEY, '1');
      // Limpiar estado accesible por JS
      localStorage.removeItem('userData');
      sessionStorage.removeItem('currentSession');
    } catch(e) {}
    try {
      // Empujar y reemplazar estado para neutralizar historial inmediato
      history.pushState(null, '', location.href);
      history.replaceState(null, '', location.href);
    } catch(e) {}
  }

  function protect(){
    const { logged, user } = getSession();

    // Refuerzo de área admin: exige sesión válida y rol admin
    if (isAdminArea()){
      // Permitir libre acceso a la página de login del admin
      if (!isLoginPage()){
        if (!logged || !isAdminUser(user)){
          redirectToLogin();
          return;
        }
      }
    }

    // Si se marcó logout, cualquier intento de retroceso debe ir al login
    function shouldRedirect(){ return sessionStorage.getItem(LOGOUT_FLAG_KEY) === '1'; }

    window.addEventListener('popstate', function(){ if (shouldRedirect()) redirectToLogin(); });
    window.addEventListener('pageshow', function(e){
      if (shouldRedirect()) redirectToLogin();
    });

    // Si no hay sesión y no estamos en la página de login, y existe marca de logout
    // if (!logged && !isLoginPage() && shouldRedirect()){
    //   redirectToLogin();
    // }
  }

  function hook(){
    // Integrarse al flujo global de logout
    window.addEventListener('auth:loggedOut', markLoggedOut);

    // Mostrar confirmación universal antes de cualquier logout
    function confirmAndLogout(next){
      const proceed = () => {
        try { markLoggedOut(); } catch(e) {}
        try { window.dispatchEvent(new CustomEvent('auth:loggedOut')); } catch(e) {}
        // No llamamos al next() para evitar redirecciones propias de páginas antiguas.
        // La limpieza ya se realizó en markLoggedOut(); centralizamos la navegación aquí.
      };

      if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function'){
        Swal.fire({
          title: '¿Cerrar Sesión?',
          text: '¿Estás seguro de que deseas cerrar sesión?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sí, cerrar sesión',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            proceed();
            try {
              Swal.fire({
                title: 'Sesión Cerrada',
                text: 'Has cerrado sesión exitosamente',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
              }).then(() => {
                redirectToLogin();
              });
            } catch(e) {}
          }
        });
      } else {
        // Fallback modal simple
        const modal = document.createElement('div');
        modal.innerHTML = `
<div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
  <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; max-width: 400px; width: 90%;">
    <h3 style="margin-bottom: 15px; color: #333;">¿Estás seguro que deseas cerrar sesión?</h3>
    <div style="display: flex; justify-content: center; gap: 10px;">
      <button id="logoutConfirmBtn" style="background: #dc2626; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">Sí, cerrar sesión</button>
      <button id="logoutCancelBtn" style="background: #e5e7eb; color: #333; padding: 8px 16px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">Cancelar</button>
    </div>
  </div>
</div>`;
        document.body.appendChild(modal);
        const confirmBtn = modal.querySelector('#logoutConfirmBtn');
        const cancelBtn = modal.querySelector('#logoutCancelBtn');
        confirmBtn.addEventListener('click', () => { try { modal.remove(); } catch(e) {}; proceed(); });
        cancelBtn.addEventListener('click', () => { try { modal.remove(); } catch(e) {}; });
      }
    }

    const originalLogout = window.logout;
    window.logout = function(){
      const boundOriginal = (typeof originalLogout === 'function') ? originalLogout.bind(this) : null;
      confirmAndLogout(boundOriginal);
    };

    // Interceptar clics en enlaces de logout para mostrar confirmación
    document.addEventListener('click', function(e){
      const el = e.target;
      const anchor = el && (el.closest ? el.closest('a') : null);
      if (!anchor) return;
      const href = (anchor.getAttribute('href') || '').toLowerCase();
      const isLogoutAnchor = anchor.classList.contains('logout') || href.includes('logout');
      if (isLogoutAnchor){
        e.preventDefault();
        confirmAndLogout();
      }
    }, true);
  }

  function init(){
    // Si estamos en login y hay sesión, limpiar marca de logout para permitir acceso normal
    const { logged } = getSession();
    if (isLoginPage() && logged){ try { sessionStorage.removeItem(LOGOUT_FLAG_KEY); } catch(e){} }
    protect();
    hook();
  }

  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
  else { init(); }
})();
