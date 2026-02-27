document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.viewer-tab');
  const imageEl = document.getElementById('productImage');
  const modelEl = document.getElementById('productModel');

  if (!tabs.length || !imageEl) return;

  // --- Pseudo 3D (tilt) sobre la imagen como fallback ---
  let tiltActive = false;
  let rect = null;
  let reqId = 0;
  const maxRotate = 10; // grados
  const maxScale = 1.04;

  const enableTilt = () => {
    if (tiltActive) return;
    tiltActive = true;
    imageEl.classList.add('tilt-enabled');
    rect = imageEl.getBoundingClientRect();
    imageEl.style.transform = `scale(${maxScale})`;
    imageEl.addEventListener('mousemove', onMove);
    imageEl.addEventListener('mouseleave', onLeave);
    imageEl.addEventListener('mousedown', onDown);
    imageEl.addEventListener('mouseup', onUp);
    imageEl.addEventListener('touchmove', onTouchMove, {passive:false});
    imageEl.addEventListener('touchend', onLeave);
    window.addEventListener('resize', () => { rect = imageEl.getBoundingClientRect(); });
  };

  const disableTilt = () => {
    if (!tiltActive) return;
    tiltActive = false;
    imageEl.classList.remove('tilt-enabled','tilt-grabbing');
    imageEl.style.transform = '';
    imageEl.removeEventListener('mousemove', onMove);
    imageEl.removeEventListener('mouseleave', onLeave);
    imageEl.removeEventListener('mousedown', onDown);
    imageEl.removeEventListener('mouseup', onUp);
    imageEl.removeEventListener('touchmove', onTouchMove);
    imageEl.removeEventListener('touchend', onLeave);
    if (reqId) cancelAnimationFrame(reqId);
  };

  const onDown = () => imageEl.classList.add('tilt-grabbing');
  const onUp = () => imageEl.classList.remove('tilt-grabbing');

  function onMove(e){
    if (!rect) rect = imageEl.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    updateTilt(x, y);
  }
  function onTouchMove(e){
    if (!rect) rect = imageEl.getBoundingClientRect();
    const t = e.touches[0];
    const x = t.clientX - rect.left;
    const y = t.clientY - rect.top;
    updateTilt(x, y);
  }
  function updateTilt(x, y){
    const nx = (x / rect.width) - 0.5;   // -0.5 a 0.5
    const ny = (y / rect.height) - 0.5;
    const rx = (-ny * 2) * maxRotate;    // arriba/abajo
    const ry = (nx * 2) * maxRotate;     // izquierda/derecha
    if (reqId) cancelAnimationFrame(reqId);
    reqId = requestAnimationFrame(()=>{
      imageEl.style.transform = `perspective(800px) rotateX(${rx}deg) rotateY(${ry}deg) scale(${maxScale})`;
    });
  }
  function onLeave(){
    if (reqId) cancelAnimationFrame(reqId);
    reqId = requestAnimationFrame(()=>{
      imageEl.style.transform = '';
    });
  }

  const showImage = () => {
    imageEl.style.display = 'block';
    if (modelEl) modelEl.style.display = 'none';
    disableTilt();
    tabs.forEach(t => t.classList.toggle('active', t.dataset.view === 'image'));
  };

  const show3D = () => {
    if (modelEl) {
      imageEl.style.display = 'none';
      modelEl.style.display = 'block';
      tabs.forEach(t => t.classList.toggle('active', t.dataset.view === '3d'));
    } else {
      // Fallback: activar efecto 3D sobre la imagen existente
      imageEl.style.display = 'block';
      enableTilt();
      tabs.forEach(t => t.classList.toggle('active', t.dataset.view === '3d'));
    }
  };

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const view = tab.dataset.view;
      if (view === '3d') show3D();
      else showImage();
    });
  });

  showImage();
});
