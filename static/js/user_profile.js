(function(){
  function el(id){ return document.getElementById(id); }
  function showTab(name){
    var tabs = ['overview','edit','password','settings'];
    for(var i=0;i<tabs.length;i++){
      var c = el('tab-'+tabs[i]);
      if(c) c.className = (tabs[i]===name?'content active':'content');
    }
    var btns = document.querySelectorAll('.tabs button');
    for(var j=0;j<btns.length;j++){
      var b = btns[j];
      var t = b.getAttribute('data-tab');
      b.className = (t===name?'active':'');
    }
  }
  function getInitialTab(){
    var allowed = ['overview','edit','password','settings'];
    var tab = 'overview';
    try{
      var qs = new URLSearchParams(window.location.search);
      var t1 = qs.get('tab');
      if(t1 && allowed.indexOf(t1)>=0){ tab = t1; }
      else if(window.location.hash){
        var t2 = window.location.hash.replace('#','');
        if(allowed.indexOf(t2)>=0){ tab = t2; }
      }
    }catch(e){}
    return tab;
  }
  function getSessionUser(){
    try{
      var raw = localStorage.getItem('userData') || sessionStorage.getItem('currentSession');
      if(!raw) return null;
      var data = JSON.parse(raw);
      if(data && data.isLoggedIn){ return data.user ? data.user : data; }
      return null;
    }catch(e){ return null; }
  }
  function loadProfile(){
    var u = getSessionUser();
    if(!u){ try{ location.assign('./login/login.html'); }catch(e){} return; }
    el('ovName').textContent = u.nombres_completos || u.nombre || 'Usuario';
    el('ovEmail').textContent = u.correo_electronico || u.email || '';
    el('ovRole').textContent = u.rol || '';
    var initials = String(el('ovName').textContent||'U').charAt(0).toUpperCase();
    var av = document.getElementById('profileInitial'); if(av) av.textContent = initials;
    el('fullName').value = u.nombres_completos || u.nombre || '';
    el('email').value = u.correo_electronico || u.email || '';
  }
  function reloadServerProfile(){
    fetch('./backend/php/user_profile_manage.php', {
      method:'POST',
      headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action:'get_profile' })
    }).then(function(r){ return r.json(); })
    .then(function(d){
      if(d && d.success && d.user){
        el('ovName').textContent = d.user.nombres_completos || 'Usuario';
        el('ovEmail').textContent = d.user.correo_electronico || '';
        el('ovRole').textContent = d.user.rol || '';
        el('fullName').value = d.user.nombres_completos || '';
        el('email').value = d.user.correo_electronico || '';
        var av = document.getElementById('profileInitial'); if(av){ av.textContent = String(el('ovName').textContent||'U').charAt(0).toUpperCase(); }
      }
    }).catch(function(){});
  }
  function bindTabs(){
    var btns = document.querySelectorAll('.tabs button');
    for(var i=0;i<btns.length;i++){
      btns[i].addEventListener('click', function(){
        var t = this.getAttribute('data-tab'); showTab(t);
      });
    }
  }
  function bindEdit(){
    var saveBtn = el('saveProfile');
    var resetBtn = el('resetProfile');
    var alertBox = el('editAlert');
    if(saveBtn){
      saveBtn.addEventListener('click', function(){
        alertBox.textContent = '';
        var fullName = el('fullName').value.trim();
        var email = el('email').value.trim();
        if(fullName==='' || email===''){ alertBox.textContent = 'Completa nombre y correo.'; return; }
        fetch('./backend/php/user_profile_manage.php', {
          method:'POST',
          headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action:'update_profile', fullName: fullName, email: email })
        }).then(function(r){ return r.json(); })
        .then(function(d){
          alertBox.textContent = d.message || (d.success?'Guardado':'Error');
          if(d && d.success){
            try{
              var raw = localStorage.getItem('userData') || sessionStorage.getItem('currentSession');
              if(raw){
                var data = JSON.parse(raw);
                if(data.user){ data.user.nombres_completos = fullName; data.user.correo_electronico = email; }
                else { data.nombres_completos = fullName; data.correo_electronico = email; }
                localStorage.setItem('userData', JSON.stringify(data));
                sessionStorage.setItem('currentSession', JSON.stringify(data));
              }
            }catch(e){}
            reloadServerProfile();
          }
        }).catch(function(){ alertBox.textContent = 'Error de red.'; });
      });
    }
    if(resetBtn){
      resetBtn.addEventListener('click', function(){ loadProfile(); alertBox.textContent=''; });
    }
  }
  function bindPassword(){
    var btn = el('changePassword');
    var alertBox = el('passAlert');
    if(btn){
      btn.addEventListener('click', function(){
        alertBox.textContent='';
        var currentPassword = el('currentPassword').value;
        var newPassword = el('newPassword').value;
        var confirmPassword = el('confirmPassword').value;
        if((newPassword||'').length<8){ alertBox.textContent='La nueva contraseña debe tener al menos 8 caracteres.'; return; }
        if(newPassword!==confirmPassword){ alertBox.textContent='Las contraseñas nuevas no coinciden.'; return; }
        fetch('./backend/php/user_profile_manage.php', {
          method:'POST',
          headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action:'change_password',
            currentPassword: currentPassword,
            newPassword: newPassword,
            confirmPassword: confirmPassword
          })
        }).then(function(r){ return r.json(); })
        .then(function(d){ alertBox.textContent = d.message || (d.success?'Contraseña actualizada':'Error'); })
        .catch(function(){ alertBox.textContent='Error de red.'; });
      });
    }
  }
  function bindSettings(){
    var u = getSessionUser(); if(!u) return;
    var key = 'userSettings_'+String(u.id || u.id_usuario || u.email || 'anon');
    var emailNotifs = el('stEmailNotifs');
    var rememberFavs = el('stRememberFavs');
    var showIVA = el('stShowIVA');
    var alertBox = el('settingsAlert');
    try{
      var raw = localStorage.getItem(key);
      var cfg = raw ? JSON.parse(raw) : {};
      emailNotifs.checked = !!cfg.emailNotifs;
      rememberFavs.checked = !!cfg.rememberFavs;
      showIVA.checked = !!cfg.showIVA;
    }catch(e){}
    var saveBtn = el('saveSettings');
    if(saveBtn){
      saveBtn.addEventListener('click', function(){
        var cfg = {
          emailNotifs: !!emailNotifs.checked,
          rememberFavs: !!rememberFavs.checked,
          showIVA: !!showIVA.checked
        };
        try{ localStorage.setItem(key, JSON.stringify(cfg)); alertBox.textContent='Configuración guardada.'; }catch(e){ alertBox.textContent='No se pudo guardar.'; }
      });
    }
  }
  function init(){
    bindTabs();
    showTab(getInitialTab());
    loadProfile();
    reloadServerProfile();
    bindEdit();
    bindPassword();
    bindSettings();
  }
  if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', init); }
  else { init(); }
})();
