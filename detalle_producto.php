<?php
require_once __DIR__ . '/backend/php/conexion.php';

// Obtener ID del producto
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
$producto = null;

if ($id_producto > 0) {
    $stmt = $conexion->prepare("SELECT * FROM producto WHERE id_producto = ?");
    $stmt->execute([$id_producto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Redirigir si no existe
if (!$producto) {
    header("Location: productos.php");
    exit;
}

// === LÓGICA DE IMÁGENES (Idéntica a productos.php) ===
function base_prefix()
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
    $base = rtrim(dirname($script), '/');
    return ($base && $base !== '/') ? $base : '';
}
function normalize_web_path($fsPath, $rootDir)
{
    $p = str_replace('\\', '/', (string)$fsPath);
    $root = str_replace('\\', '/', (string)$rootDir);
    if (strpos($p, $root) === 0) {
        $p = substr($p, strlen($root));
    }
    if ($p !== '' && $p[0] !== '/') {
        $p = '/' . $p;
    }
    $prefix = base_prefix();
    if ($prefix && strpos($p, $prefix . '/') !== 0) {
        $p = $prefix . $p;
    }
    return $p;
}
function imageForCategory($cat)
{
    switch ($cat) {
        case 'cerdo':
            return base_prefix() . '/static/images/lomo_de_cerdo.jpeg';
        case 'res':
            return base_prefix() . '/static/images/lomo fresco.jpg';
        case 'pollo':
            return base_prefix() . '/static/images/imagenhero1.jpeg';
        case 'pescado':
            return base_prefix() . '/static/images/filete_de_robalo.jpg';
        default:
            return base_prefix() . '/static/images/image.png';
    }
}
function deriveCategoryFromName($name)
{
    $n = mb_strtolower(trim((string)$name), 'UTF-8');
    if (preg_match('/(res|vaca|ternera|carne\s*de\s*res)/i', $n))
        return 'res';
    if (preg_match('/(cerdo|puerco|chancho)/i', $n))
        return 'cerdo';
    if (preg_match('/(pollo|gallina|pechuga|muslo)/i', $n))
        return 'pollo';
    if (preg_match('/(pescado|robalo|bagre|mojarra|tilapia)/i', $n))
        return 'pescado';
    return 'otros';
}
function imageFromRow(array $row)
{
    $candidates = ['imagen', 'image', 'imagen_url', 'image_url', 'foto', 'imagen_producto', 'url_imagen'];
    $img = null;
    foreach ($candidates as $c) {
        if (isset($row[$c]) && trim((string)$row[$c]) !== '') {
            $img = (string)$row[$c];
            break;
        }
    }
    if ($img === null)
        return null;
    $img = str_replace('\\', '/', $img);
    if (preg_match('#^https?://#i', $img))
        return $img;

    $rootDir = __DIR__;
    $pos = strpos($img, 'static/images');
    if ($pos !== false) {
        return base_prefix() . '/' . substr($img, $pos);
    }
    return base_prefix() . '/' . ltrim($img, '/');
}

// Obtener imagen final
$cat = deriveCategoryFromName($producto['nombre']);
$imagen_producto = imageFromRow($producto);
if (!$imagen_producto) {
    $imagen_producto = imageForCategory($cat);
}

$modelsDirRel = 'assets/models/products/';
$glbRel = $modelsDirRel . $id_producto . '.glb';
$usdzRel = $modelsDirRel . $id_producto . '.usdz';
$glbAbs = __DIR__ . '/' . $glbRel;
$usdzAbs = __DIR__ . '/' . $usdzRel;
$model_glb_url = null;
$model_usdz_url = null;
if (file_exists($glbAbs)) {
    $model_glb_url = base_prefix() . '/' . $glbRel;
}
if (file_exists($usdzAbs)) {
    $model_usdz_url = base_prefix() . '/' . $usdzRel;
}

// === PRODUCTOS RELACIONADOS ===
$relatedProducts = [];
// Buscar productos de la misma categoría (basado en nombre o id_categoria si existiera)
// Usamos el nombre para derivar categoría
$relatedStmt = $conexion->prepare("SELECT * FROM producto WHERE id_producto != ? AND (LOWER(nombre) LIKE ? OR LOWER(nombre) LIKE ?) LIMIT 4");
$catTerm = '%' . $cat . '%';
// Fallback simple: buscar por categoría derivada
$relatedStmt->execute([$id_producto, $catTerm, $catTerm]);
$relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

// Si hay pocos, rellenar con cualquiera
if (count($relatedProducts) < 4) {
    $moreStmt = $conexion->prepare("SELECT * FROM producto WHERE id_producto != ? ORDER BY RANDOM() LIMIT " . (4 - count($relatedProducts)));
    $moreStmt->execute([$id_producto]);
    while ($r = $moreStmt->fetch(PDO::FETCH_ASSOC)) {
        // Evitar duplicados si ya estaba
        $exists = false;
        foreach ($relatedProducts as $rp)
            if ($rp['id_producto'] == $r['id_producto'])
                $exists = true;
        if (!$exists)
            $relatedProducts[] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> - Detalles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./static/css/header_en_general.css" />
    <link rel="stylesheet" href="./static/css/responsive.css" />
    <link rel="stylesheet" href="./static/css/base.css" />
    <link rel="stylesheet" href="./static/css/chatbot.css" />
    <link rel="shortcut icon" href="./assets/icon/image-removebg-preview sin fondo (1).ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
    <style>
        body {
            background-color: #000000;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .product-detail-container {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 4rem;
            align-items: start;
        }

        /* Columna Izquierda: Imagen */
        .detail-image-wrapper {
            background: #111;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid #333;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(20,20,20,0.8);
        }

        .viewer-tabs {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 8px;
            z-index: 20;
        }
        .viewer-tab {
            padding: 6px 12px;
            border-radius: 8px;
            background: #1a1a1a;
            border: 1px solid #333;
            color: #ccc;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .viewer-tab.active {
            background: #ff0000;
            color: #fff;
            border-color: #ff0000;
        }
        .viewer-tab.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .detail-image-wrapper img {
            width: 100%;
            height: auto;
            object-fit: contain;
            transition: transform 0.3s ease;
            max-height: 500px;
        }

        .detail-image-wrapper:hover img {
            transform: scale(1.05);
        }

        model-viewer.product-model {
            width: 100%;
            height: 500px;
            background: #111;
            border-radius: 20px;
            border: 1px solid #333;
            display: none;
        }

        /* Pseudo 3D (tilt) sobre imagen */
        .product-image-large.tilt-enabled {
            will-change: transform;
            transition: transform 80ms linear, box-shadow 200ms ease;
            cursor: grab;
            box-shadow: 0 30px 60px rgba(0,0,0,0.4);
        }
        .product-image-large.tilt-grabbing {
            cursor: grabbing;
        }

        .badges {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
            z-index: 10;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9rem;
            text-transform: uppercase;
            box-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }

        .badge-category {
            background: #ff0000;
            color: white;
        }
        
        .badge-stock {
            background: #28a745;
            color: white;
        }

        /* Columna Derecha: Info */
        .product-info-detail {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .breadcrumb-detail {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: -10px;
        }

        .breadcrumb-detail a {
            color: #ff0000;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .breadcrumb-detail a:hover {
            color: #fff;
        }

        .product-title-large {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.1;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .product-price-large {
            font-size: 2.8rem;
            color: #ff0000;
            font-weight: bold;
            display: flex;
            align-items: baseline;
            gap: 10px;
        }

        .iva-text {
            font-size: 1rem;
            color: #666;
            font-weight: normal;
        }

        .product-description-full {
            color: #ccc;
            line-height: 1.8;
            font-size: 1.1rem;
            border-bottom: 1px solid #333;
            padding-bottom: 1.5rem;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            background: #111;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #333;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #aaa;
        }

        .meta-item i {
            color: #ff0000;
            font-size: 1.2rem;
        }

        .meta-item strong {
            color: #fff;
        }

        /* Controles de Compra */
        .purchase-controls {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #0a0a0a;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #222;
        }

        .qty-selector-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .qty-label {
            font-weight: bold;
            color: #ccc;
        }

        .qty-selector-large {
            display: flex;
            align-items: center;
            background: #000;
            border: 1px solid #333;
            border-radius: 8px;
            width: fit-content;
        }

        .qty-btn-large {
            width: 40px;
            height: 40px;
            background: #1a1a1a;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .qty-btn-large:hover {
            background: #ff0000;
        }

        .qty-input-large {
            width: 50px;
            background: none;
            border: none;
            border-left: 1px solid #333;
            border-right: 1px solid #333;
            color: white;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .qty-input-large:focus {
            outline: none;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 15px;
        }

        .btn-large {
            padding: 1rem;
            border-radius: 8px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
        }

        .btn-add-cart-large {
            background: transparent;
            color: #fff;
            border: 2px solid #ff0000;
        }

        .btn-add-cart-large:hover {
            background: #ff0000;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 0, 0, 0.3);
        }

        .btn-buy-now {
            background: #ff0000;
            color: white;
            border: 2px solid #ff0000;
        }

        .btn-buy-now:hover {
            background: #cc0000;
            border-color: #cc0000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 0, 0, 0.4);
        }
        
        /* Productos Relacionados */
        .related-products {
            max-width: 1200px;
            margin: 0 auto 4rem auto;
            padding: 0 20px;
        }
        
        .related-title {
            font-size: 2rem;
            color: #fff;
            margin-bottom: 2rem;
            border-bottom: 2px solid #333;
            padding-bottom: 1rem;
            display: inline-block;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .related-card {
            background: #111;
            border: 1px solid #333;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .related-card:hover {
            transform: translateY(-5px);
            border-color: #ff0000;
        }
        
        .related-img {
            height: 180px;
            width: 100%;
            object-fit: cover;
        }
        
        .related-info {
            padding: 1rem;
        }
        
        .related-name {
            font-size: 1.1rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .related-price {
            color: #ff0000;
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* Responsivo */
        @media (max-width: 900px) {
            .product-detail-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .product-title-large {
                font-size: 2.5rem;
            }
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-black text-white">
    <!-- Header Original -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="./assets/icon/LOGO-DISTRICARNES.png" alt="DISTRICARNES Logo">
            </div>

            <!-- Buscador central estilo ML y pill promocional -->
            <div class="ml-search">
                <form action="productos.php" method="get">
                    <input type="search" name="q" id="site-search" placeholder="Buscar productos, marcas y más…" />
                    <button type="submit" aria-label="Buscar"><i class="fas fa-search"></i></button>
                </form>
            </div>


            <!-- Enlaces rápidos + botón de carrito (siempre visibles) -->
            <div id="quickLinks" class="ml-actions">
                <a id="cartButton" class="ml-icon-btn ml-icon-bounce" href="./carrito-de-compras/index.html" aria-label="Carrito">
                    <i class="bi bi-cart"></i>
                    <span class="ml-badge" id="cartCount">0</span>
                </a>
                <!-- Botones de acceso y registro -->
                <div id="authButtons" class="flex gap-3">
                    <a href="./login/login.html" class="block">
                        <button style="background-color: rgb(255, 0, 0); border-radius: 50px; color: white; border: 2px solid red;" onmouseover="this.style.borderColor='red'; this.style.backgroundColor='black'; this.style.color='white';" onmouseout="this.style.borderColor='red'; this.style.backgroundColor='red'; this.style.color='white';"
                            class="bg-red-700 hover:bg-red-800 transition text-white text-sm font-semibold px-4 py-2 rounded">
              <i class="bi bi-box-arrow-in-right" style="font-size: 1.5rem;"></i> INICAR SESIÓN
            </button>
                    </a>
                    <a href="./login/register.html" class="block">
                        <button style="background-color: rgb(255, 0, 0); border-radius: 50px; color: white; border: 2px solid red;" onmouseover="this.style.borderColor='red'; this.style.backgroundColor='black'; this.style.color='white';" onmouseout="this.style.borderColor='red'; this.style.backgroundColor='red'; this.style.color='white';"
                            class="bg-red-700 hover:bg-red-800 transition text-white text-sm font-semibold px-4 py-2 rounded">
              <i class="bi bi-person-plus-fill" style="font-size: 1.5rem;"></i>
              REGISTRARSE
            </button>
                    </a>
                </div>
            </div>


            <!-- Botones para usuario logueado (inicialmente ocultos) -->
            <div id="userLoggedButtons" style="display: none; box-shadow: 0 0 20px rgba(0,0,0,0.8);">
                <div class="user-profile-container">
                    <button class="menu-button" onclick="toggleUserDropdown()" aria-expanded="false" aria-haspopup="true">
            <span class="user-avatar" id="userAvatar"></span>
            <span class="user-name" id="userName"></span>
            <i class="fas fa-chevron-down dropdown-arrow"></i>
          </button>

                    <!-- User Dropdown Menu -->
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-info-dropdown">
                            <div class="user-avatar-large" id="userAvatarLarge"></div>
                            <div class="user-details">
                                <h4 id="userFullName"></h4>
                                <p id="userEmail"></p>
                                <span class="user-role" id="userRole"></span>
                            </div>
                        </div>


                        <div class="menu-divider"></div>

                        <div class="menu-items">
                            <a href="#" class="menu-item">
                                <i class="fas fa-user"></i>
                                <span>Mi Perfil</span>
                            </a>


                            <a href="./historial.html" class="menu-item">
                                <i class="fas fa-clock"></i> Historial de compra
                            </a>
                            <a href="./favoritos.html" class="menu-item">
                                <i class="fas fa-heart"></i> Mis favoritos
                            </a>
                            <a href="#" class="menu-item">
                                <i class="fas fa-edit"></i>
                                <span>Editar Perfil</span>
                            </a>
                            <a href="#" class="menu-item">
                                <i class="fas fa-key"></i>
                                <span>Cambiar Contraseña</span>
                            </a>

                            <a href="admin/admin_settings.html" class="menu-item">
                                <i class="fas fa-cog"></i>
                                <span>Configuración</span>
                            </a>


                            <div class="menu-divider"></div>

                            <a href="#" class="menu-item logout" onclick="logout()">
                                <i class="fas fa-sign-out-alt" style="color: red;"></i>
                                <span style="color: red;">Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <nav class="nav-menu" id="navMenu">
                <style>
                    /* Contenedor centrado en la página */
                    
                    .nav-menu {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: center;
                        justify-content: center;
                        gap: 6rem;
                        /* Increased spacing */
                        width: 100%;
                        max-width: 960px;
                        /* ancho máximo del nav centrado */
                        margin: 0 auto;
                        /* centra horizontalmente dentro del header */
                        box-sizing: border-box;
                        padding: 0.25rem 0.5rem;
                        text-align: center;
                    }
                    /* Asegura que los enlaces y botones queden centrados visualmente */
                    
                    .nav-menu>a,
                    .nav-menu>div {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .nav-menu a {
                        text-decoration: none;
                        color: inherit;
                        padding: 0.35rem 0.75rem;
                        font-family: 'Montserrat', sans-serif;
                        /* Applied Montserrat font */
                    }
                    /* Mantener los botones de auth alineados y centrados */
                    
                    #authButtons {
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        justify-content: center;
                    }
                    /* En móvil, permitir que los elementos se apilen centrados */
                    
                    @media (max-width: 768px) {
                        .nav-menu {
                            justify-content: center;
                            padding: 0.5rem;
                        }
                        /* Hacer los botones más compactos en móvil */
                        #authButtons {
                            width: 100%;
                            justify-content: center;
                            gap: 0.5rem;
                        }
                    }
                </style>

                <!-- Botones de navegación -->
                <a href="./index.html">Inicio</a>
                <a href="./productos.php" class="active">Productos</a>
                <a href="./promociones.php">Ofertas</a>
                <a href="./contacto.html">Contacto</a>
                <a href="./sobre_nosotros.html">Quienes Somos</a>
                <!-- Estilos y funcionalidad mejorados PARA EL MENU DEL USUARIO LOGUEADO -->
                <style>
                    /* Contenedor principal con fondo negro y separación de bordes */
                    
                    #userLoggedButtons {
                        background-color: #000000;
                        padding: 1rem;
                        border-radius: 10px;
                        margin: 0.5rem;
                    }
                    
                    .user-profile-container {
                        position: relative;
                        display: inline-block;
                    }
                    
                    .menu-button {
                        display: flex;
                        align-items: center;
                        gap: 0.75rem;
                        background: linear-gradient(135deg, #000000 0%, #000000 100%);
                        border: 2px solid #000000;
                        border-radius: 50px;
                        padding: 0.75rem 1.5rem;
                        color: #ffffff;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                    }
                    
                    .menu-button:hover {
                        border-color: #ff0000;
                        background: linear-gradient(135deg, #000000 0%, #000000 100%);
                        box-shadow: 0 6px 25px rgba(255, 0, 0, 0.25);
                        transform: translateY(-2px);
                    }
                    
                    .menu-button:active {
                        transform: translateY(0);
                    }
                    
                    .user-avatar {
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: bold;
                        font-size: 1.1rem;
                        color: white;
                        box-shadow: 0 2px 8px rgba(255, 0, 0, 0.3);
                    }
                    
                    .user-name {
                        font-size: 0.95rem;
                        color: #f0f0f0;
                        max-width: 120px;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }
                    
                    .dropdown-arrow {
                        margin-left: 0.5rem;
                        transition: transform 0.3s ease;
                        color: #ff0000;
                    }
                    
                    .menu-button[aria-expanded="true"] .dropdown-arrow {
                        transform: rotate(180deg);
                    }
                    
                    .user-dropdown {
                        position: absolute;
                        top: calc(100% + 12px);
                        right: 0;
                        background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
                        border: 2px solid #333;
                        border-radius: 16px;
                        min-width: 280px;
                        max-width: 320px;
                        opacity: 0;
                        visibility: hidden;
                        transform: translateY(-10px);
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        z-index: 1000;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                        backdrop-filter: blur(10px);
                    }
                    
                    .user-dropdown.active {
                        opacity: 1;
                        visibility: visible;
                        transform: translateY(0);
                    }
                    
                    .user-info-dropdown {
                        display: flex;
                        align-items: center;
                        gap: 1rem;
                        padding: 1.5rem;
                        border-bottom: 1px solid #333;
                    }
                    
                    .user-avatar-large {
                        width: 60px;
                        height: 60px;
                        border-radius: 50%;
                        background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: bold;
                        font-size: 1.5rem;
                        color: white;
                        box-shadow: 0 4px 12px rgba(255, 0, 0, 0.3);
                    }
                    
                    .user-details h4 {
                        color: #ffffff;
                        margin: 0 0 0.25rem 0;
                        font-size: 1.1rem;
                        font-weight: 600;
                    }
                    
                    .user-details p {
                        color: #cccccc;
                        margin: 0 0 0.5rem 0;
                        font-size: 0.85rem;
                    }
                    
                    .user-role {
                        background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%);
                        color: white;
                        padding: 0.25rem 0.75rem;
                        border-radius: 20px;
                        font-size: 0.75rem;
                        font-weight: 600;
                        display: inline-block;
                    }
                    
                    .menu-items {
                        padding: 0.5rem 0;
                    }
                    
                    .menu-item {
                        display: flex;
                        align-items: center;
                        gap: 0.75rem;
                        padding: 0.875rem 1.5rem;
                        color: #e0e0e0;
                        text-decoration: none;
                        transition: all 0.2s ease;
                        border-radius: 8px;
                        margin: 0 0.5rem;
                    }
                    
                    .menu-item:hover {
                        background: rgba(255, 0, 0, 0.1);
                        color: #ffffff;
                        transform: translateX(4px);
                    }
                    
                    .menu-item.logout {
                        color: #ff6b6b;
                    }
                    
                    .menu-item.logout:hover {
                        background: rgba(255, 0, 0, 0.15);
                        color: #ff4444;
                    }
                    
                    .menu-item i {
                        width: 20px;
                        text-align: center;
                        color: #ff0000;
                    }
                    
                    .menu-divider {
                        height: 1px;
                        background: linear-gradient(90deg, transparent 0%, #333 50%, transparent 100%);
                        margin: 0.5rem 1.5rem;
                    }
                    
                    @media (max-width: 768px) {
                        .user-dropdown {
                            right: -10px;
                            min-width: 260px;
                        }
                        .menu-button {
                            padding: 0.625rem 1.25rem;
                        }
                        .user-name {
                            max-width: 80px;
                        }
                    }
                </style>

                <script>
                    // Función mejorada para toggle del dropdown
                    function toggleUserDropdown() {
                        const dropdown = document.getElementById('userDropdown');
                        const button = document.querySelector('.menu-button');
                        const isOpen = dropdown.classList.contains('active');

                        // Cerrar todos los dropdowns primero
                        document.querySelectorAll('.user-dropdown').forEach(d => d.classList.remove('active'));
                        document.querySelectorAll('.menu-button').forEach(b => b.setAttribute('aria-expanded', 'false'));

                        if (!isOpen) {
                            dropdown.classList.add('active');
                            button.setAttribute('aria-expanded', 'true');
                        }
                    }

                    document.addEventListener('click', function(event) {
                        const container = document.querySelector('.user-profile-container');
                        if (!container.contains(event.target)) {
                            const dd = document.getElementById('userDropdown');
                            if (dd) dd.classList.remove('active');
                            const btn = document.querySelector('.menu-button');
                            if (btn) btn.setAttribute('aria-expanded', 'false');
                        }
                    });

                    document.addEventListener('keydown', function(event) {
                        if (event.key === 'Escape') {
                            const dd = document.getElementById('userDropdown');
                            if (dd) dd.classList.remove('active');
                            const btn = document.querySelector('.menu-button');
                            if (btn) btn.setAttribute('aria-expanded', 'false');
                        }
                    });

                    function updateUserProfile(userData) {
                        if (!userData) return;
                        const name = userData.name || userData.nombres_completos || 'Usuario';
                        const initials = (name.charAt(0) || 'U').toUpperCase();
                        const avatar = document.getElementById('userAvatar');
                        const userName = document.getElementById('userName');
                        const avatarLarge = document.getElementById('userAvatarLarge');
                        const fullName = document.getElementById('userFullName');
                        const email = document.getElementById('userEmail');
                        const role = document.getElementById('userRole');

                        if (avatar) avatar.textContent = initials;
                        if (userName) userName.textContent = name;
                        if (avatarLarge) avatarLarge.textContent = initials;
                        if (fullName) fullName.textContent = userData.fullName || name;
                        if (email) email.textContent = userData.email || userData.correo_electronico || '';
                        if (role) role.textContent = userData.role || userData.rol || 'Usuario';
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                        // placeholder para posibles inicializaciones
                    });

                    // Modal y sesión
                    function closeModal() {
                        const modal = document.getElementById('welcomeModal');
                        if (modal) modal.style.display = 'none';
                    }

                    window.addEventListener('load', function() {
                        var modalEl = document.getElementById('welcomeModal');
                        if (modalEl) modalEl.style.display = 'flex';
                        if (window.AuthSystem && typeof AuthSystem.checkUserSession === 'function') {
                            AuthSystem.checkUserSession();
                        } else if (typeof checkUserSession === 'function') {
                            checkUserSession();
                        }
                    });

                    function checkUserSession() {
                        const userData = localStorage.getItem('userData');
                        const sessionData = sessionStorage.getItem('currentSession');

                        if (userData || sessionData) {
                            const raw = JSON.parse(userData || sessionData);
                            if (raw && raw.isLoggedIn) {
                                const currentUser = raw.user ? raw.user : raw;
                                const authButtons = document.getElementById('authButtons');
                                const userLoggedButtons = document.getElementById('userLoggedButtons');
                                if (authButtons) authButtons.style.display = 'none';
                                if (userLoggedButtons) userLoggedButtons.style.display = 'block';

                                const displayName = currentUser.nombres_completos || currentUser.nombre || currentUser.correo_electronico || currentUser.email || 'Usuario';
                                const displayEmail = currentUser.correo_electronico || currentUser.email || '';
                                const displayRole = currentUser.rol || '';
                                const initials = (displayName.charAt(0) || 'U').toUpperCase();

                                const userAvatar = document.getElementById('userAvatar');
                                const userName = document.getElementById('userName');
                                const userAvatarLarge = document.getElementById('userAvatarLarge');
                                const userFullName = document.getElementById('userFullName');
                                const userEmail = document.getElementById('userEmail');
                                const userRole = document.getElementById('userRole');

                                if (userAvatar) userAvatar.textContent = initials;
                                if (userName) userName.textContent = displayName;
                                if (userAvatarLarge) userAvatarLarge.textContent = initials;
                                if (userFullName) userFullName.textContent = displayName;
                                if (userEmail) userEmail.textContent = displayEmail;
                                if (userRole) userRole.textContent = displayRole ? displayRole.charAt(0).toUpperCase() + displayRole.slice(1) : '';
                            }
                        }
                    }
                </script>
            </nav>

            <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
        <i class="fas fa-bars "></i>
      </button>
        </div>
    </header>

    <!-- Contenido Detalle -->
    <div class="product-detail-container product-detail"> <!-- clase product-detail añadida para cart_utils -->
        
        <!-- Columna Izquierda -->
        <div class="detail-image-wrapper">
            <div class="badges">
                <span class="badge badge-category"><?php echo htmlspecialchars(ucfirst($cat)); ?></span>
                <?php if ($producto['stock'] > 0): ?>
                    <span class="badge badge-stock">En Stock</span>
                <?php
else: ?>
                    <span class="badge" style="background: #666;">Agotado</span>
                <?php
endif; ?>
            </div>
            <div class="viewer-tabs">
                <button class="viewer-tab active" data-view="image">Imagen</button>
                <button class="viewer-tab" 
                        data-view="3d">
                    Vista 3D
                </button>
            </div>
            <img id="productImage" src="<?php echo htmlspecialchars($imagen_producto); ?>" class="product-image-large" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
            <?php if ($model_glb_url): ?>
            <model-viewer id="productModel"
                          class="product-model"
                          src="<?php echo htmlspecialchars($model_glb_url); ?>"
                          <?php if ($model_usdz_url) {
        echo 'ios-src="' . htmlspecialchars($model_usdz_url) . '"';
    }?>
                          shadow-intensity="1"
                          camera-controls
                          auto-rotate
                          ar
                          ar-modes="webxr scene-viewer quick-look"
                          exposure="1.0"
                          disable-zoom="false">
            </model-viewer>
            <?php
endif; ?>
        </div>

        <!-- Columna Derecha -->
        <div class="product-info-detail">
            <div class="breadcrumb-detail">
                <a href="index.html">Inicio</a> / <a href="productos.php">Productos</a> / <span><?php echo htmlspecialchars($producto['nombre']); ?></span>
            </div>

            <h1 class="product-title-large product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
            <!--estilos de las estrellas de calificacion -->
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <div style="color: #ffc107; font-size: 1.2rem;">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>
                <span style="color: #888; font-size: 0.9rem;">(4.8 de 5) • 120 opiniones</span>
            </div>

            <div class="product-price-large product-price-detail">
                $<?php echo number_format($producto['precio_venta'], 0, ',', '.'); ?>
                <span class="iva-text">IVA incluido</span>
            </div>

            <div class="product-description-full">
                <?php echo nl2br(htmlspecialchars($producto['descripcion'] ?? 'Sin descripción detallada.')); ?>
                <br><br>
                <p class="text-gray-400 text-sm">
                    * Las imágenes son referenciales. El peso final puede variar ligeramente.
                </p>
            </div>

            <div class="meta-grid">
                <div class="meta-item">
                    <i class="fas fa-box-open"></i>
                    <div>
                        <span>Disponibilidad:</span>
                        <strong style="color: #4ade80;"><?php echo $producto['stock']; ?> unidades</strong>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-barcode"></i>
                    <div>
                        <span>Código:</span>
                        <strong><?php echo str_pad($producto['id_producto'], 6, '0', STR_PAD_LEFT); ?></strong>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-weight-hanging"></i>
                    <div>
                        <span>Venta por:</span>
                        <strong>Unidad / Kg</strong>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-truck"></i>
                    <div>
                        <span>Entrega:</span>
                        <strong>Inmediata</strong>
                    </div>
                </div>
            </div>

            <div class="purchase-controls">
                <div class="qty-selector-wrapper">
                    <span class="qty-label">Cantidad:</span>
                    <div class="qty-selector-large">
                        <button class="qty-btn-large" onclick="updateQty(-1)">-</button>
                        <input type="number" class="qty-input-large quantity-input" value="1" min="1" max="<?php echo max(1, $producto['stock']); ?>" readonly>
                        <button class="qty-btn-large" onclick="updateQty(1)">+</button>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn-large btn-add-cart-large add-to-cart" 
                            data-id="<?php echo $producto['id_producto']; ?>"
                            data-title="<?php echo htmlspecialchars($producto['nombre']); ?>"
                            data-price="<?php echo $producto['precio_venta']; ?>"
                            data-image="<?php echo htmlspecialchars($imagen_producto); ?>"
                            data-qty="1">
                        <i class="fas fa-shopping-cart"></i> Agregar al Carrito
                    </button>
                    <!-- Botón comprar ahora: añade y redirige al carrito -->
                    <button class="btn-large btn-buy-now" onclick="buyNow()">
                        <i class="fas fa-bolt"></i> Comprar Ahora
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos Relacionados -->
    <?php if (count($relatedProducts) > 0): ?>
    <div class="related-products">
        <h2 class="related-title">También te podría gustar</h2>
        <div class="related-grid">
            <?php foreach ($relatedProducts as $rel):
        $relImg = imageFromRow($rel);
        if (!$relImg) {
            $relCat = deriveCategoryFromName($rel['nombre']);
            $relImg = imageForCategory($relCat);
        }
?>
            <a href="detalle_producto.php?id=<?php echo $rel['id_producto']; ?>" class="related-card" style="text-decoration: none;">
                <img src="<?php echo htmlspecialchars($relImg); ?>" alt="<?php echo htmlspecialchars($rel['nombre']); ?>" class="related-img">
                <div class="related-info">
                    <div class="related-name"><?php echo htmlspecialchars($rel['nombre']); ?></div>
                    <div class="related-price">$<?php echo number_format($rel['precio_venta'], 0, ',', '.'); ?></div>
                </div>
            </a>
            <?php
    endforeach; ?>
        </div>
    </div>
    <?php
endif; ?>

    <!--Footer Original-->
    <footer class="footer">
        <div class="footer-container">

            <!-- Columna 1: Información de Contacto -->
            <div class="footer-column">
                <h4>INFORMACIÓN DE CONTACTO</h4>
                <p><i class="fas fa-map-marker-alt"></i> Dirección: OLAYA HERRERA</p>
                <p><i class="fas fa-phone"></i> Teléfono: 301 5210177</p>
                <p><i class="fas fa-envelope"></i> Email: districarneshermanosnavarro@gmail.com</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Columna 2: Información -->
            <div class="footer-column">
                <h4>INFORMACIÓN</h4>
                <ul>
                    <li><i class="fas fa-info-circle"></i> Información Delivery</li>
                    <li><i class="fas fa-shield-alt"></i> Políticas de Privacidad</li>
                    <li><i class="fas fa-file-contract"></i> Términos y condiciones</li>
                    <li><i class="fas fa-headset"></i> Contáctanos</li>
                </ul>
            </div>

            <!-- Columna 3: Mi Cuenta -->
            <div class="footer-column">
                <h4>MI CUENTA</h4>
                <ul>
                    <li><i class="fas fa-user"></i> Mi cuenta</li>
                    <li><i class="fas fa-history"></i> Historial de ordenes</li>
                    <li><i class="fas fa-heart"></i> Lista de deseos</li>
                    <li><i class="fas fa-newspaper"></i> Boletín</li>
                    <li><i class="fas fa-undo"></i> Reembolsos</li>
                </ul>
            </div>

            <!-- Columna 4: Boletín Informativo -->
            <div class="footer-column">
                <h4>BOLETÍN INFORMATIVO</h4>
                <p>Suscríbete a nuestros boletines ahora y mantente al día con nuevas colecciones y ofertas exclusivas.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Ingresa el correo aquí..." required />
                    <button type="submit" style="background-color: #ff0000;">SUSCRÍBETE</button> </form>
            </div>

        </div>

        <!-- Pie inferior -->
        <center>
            <h4>&copy; 2026 DISTRICARNES HERMANOS NAVARRO. Todos los derechos reservados.</h4>
        </center>

    </footer>

    <!-- CHAT BOT -->
    <div class="chatbot-toggle" onclick="toggleChatbot()" title="Abrir chat DISTRICARNES" aria-label="Abrir chat DISTRICARNES">
        <i class="fas fa-robot"></i>
    </div>
    <div class="chatbot-container">
        <div class="chatbot-header">
            <div class="header-info">
                <div class="bot-avatar"><i class="fas fa-robot"></i></div>
                <h3>DISTRICARNES HERMANOS NAVARRO</h3>
                <p>Asistente Virtual</p>
                <p>Tu especialista en carnes premium</p>
            </div>
            <button class="close-btn" onclick="toggleChatbot()" aria-label="Cerrar chat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chatbot-messages" id="chatBox">
            <div class="message bot-message">
                ¡Hola! 🥩 Soy tu asistente de DISTRICARNES. ¿En qué puedo ayudarte hoy?
                <div class="menu-options">
                    <div class="menu-option"><i class="fas fa-drumstick-bite"></i> Ver productos cárnicos</div>
                    <div class="menu-option"><i class="fas fa-cut"></i> Tipos de cortes</div>
                    <div class="menu-option"><i class="fas fa-clock"></i> Horarios y ubicación</div>
                    <div class="menu-option"><i class="fas fa-tags"></i> Precios y ofertas</div>
                    <div class="menu-option"><i class="fas fa-info-circle"></i> Sobre nosotros</div>
                    <div class="menu-option"><i class="fas fa-phone"></i> Contactar</div>
                </div>
                <div class="message-timestamp">10:01 AM</div>
            </div>
        </div>
        <div class="chatbot-input">
            <div class="input-container">
                <input type="text" class="chat-input" id="userInput" placeholder="¿Qué deseas saber sobre nuestras carnes?"
                       onkeypress="handleKeyPress(event)" autocomplete="off" />
                <button class="voice-btn" title="Entrada de voz (No implementado)">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="send-btn" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="quick-actions">
                <button class="quick-action" onclick="handleQuickAction('productos')">
                    <i class="fas fa-drumstick-bite"></i> Ver Productos
                </button>
                <button class="quick-action" onclick="handleQuickAction('horarios')">
                    <i class="fas fa-clock"></i> Horarios
                </button>
                <button class="quick-action" onclick="handleQuickAction('contacto')">
                    <i class="fas fa-phone"></i> Contacto
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts Esenciales -->
    <script src="./static/js/header_actions.js"></script>
    <script src="./js/auth.js"></script>
    <script src="./static/js/cart_badge.js"></script>
    <script src="./static/js/auth_utils.js"></script>
    <script src="./static/js/cart_utils.js"></script>
    <script src="./static/js/product-3d-viewer.js"></script>
    <script src="./static/js/index.js"></script>
    <script src="./static/js/chatbot.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.querySelector('.chatbot-toggle');
            var container = document.querySelector('.chatbot-container');
            if (!toggle || !container) return;
            function openClose(e) {
                if (e) { e.preventDefault(); e.stopPropagation(); }
                container.classList.toggle('active');
                if (container.classList.contains('active')) {
                    setTimeout(function () {
                        var input = document.getElementById('userInput') || document.querySelector('.chat-input');
                        if (input) input.focus();
                    }, 200);
                }
            }
            toggle.addEventListener('click', openClose);
            toggle.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') openClose(e);
            });
        });
    </script>
    <style>
      /* Desactivar sticky del header solo en detalle de producto */
      .header { position: static !important; top: auto !important; }
    </style>
    
    <script>
        // Lógica para toggle de usuario (simple)
        function toggleUserDropdown() {
            const d = document.getElementById('userDropdown');
            if(d) d.style.display = (d.style.display === 'none') ? 'block' : 'none';
        }

        // Cantidad
        function updateQty(change) {
            const input = document.querySelector('.qty-input-large');
            let val = parseInt(input.value);
            const max = parseInt(input.getAttribute('max')) || 100;
            val += change;
            if (val < 1) val = 1;
            if (val > max) val = max;
            input.value = val;
            
            // Actualizar data-qty del botón
            const btn = document.querySelector('.add-to-cart');
            if(btn) btn.dataset.qty = val;
        }

        function buyNow() {
            // Simular clic en agregar al carrito y luego redirigir
            const addBtn = document.querySelector('.add-to-cart');
            if(addBtn) {
                // Verificar sesión antes de acción
                const userData = localStorage.getItem('userData');
                const sessionData = sessionStorage.getItem('currentSession');
                
                if(!userData && !sessionData) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Debes iniciar sesión',
                        text: 'Para comprar, primero ingresa a tu cuenta.',
                        confirmButtonText: 'Ir a Login',
                        showCancelButton: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = './login/login.html';
                        }
                    });
                    return;
                }
                
                // Si hay sesión, agregar y redirigir
                addBtn.click();
                setTimeout(() => {
                     window.location.href = './carrito-de-compras/index.html';
                }, 800);
            }
        }
        
        // Verificar sesión al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const userData = localStorage.getItem('userData');
            const sessionData = sessionStorage.getItem('currentSession');
            
            const authBtns = document.getElementById('authButtons');
            const userBtns = document.getElementById('userLoggedButtons');
            
            if(userData || sessionData) {
                if(authBtns) authBtns.style.display = 'none';
                if(userBtns) userBtns.style.display = 'block';
                
                // Cargar nombre
                try {
                    const u = JSON.parse(userData || sessionData);
                    const name = u.nombre || u.nombres_completos || 'Usuario';
                    const initials = name.charAt(0).toUpperCase();
                    document.getElementById('userName').textContent = name;
                    document.getElementById('userAvatar').textContent = initials;
                } catch(e) {}
            } else {
                if(authBtns) authBtns.style.display = 'flex';
                if(userBtns) userBtns.style.display = 'none';
            }
        });
        
        function logout() {
            localStorage.removeItem('userData');
            sessionStorage.removeItem('currentSession');
            window.location.reload();
        }
    </script>
</body>
</html>
