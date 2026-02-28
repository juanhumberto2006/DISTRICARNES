<?php
require_once 'backend/php/conexion.php';

// Función para obtener ofertas activas
function getActiveOffers($db) {
    try {
        $sql = "SELECT * FROM ofertas WHERE estado = 'active' ORDER BY created_at DESC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

$ofertas = getActiveOffers($conexion);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>DISTRICARNES - Promociones</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="shortcut icon" href="./assets/icon/image-removebg-preview sin fondo (1).ico" type="image/x-icon">
    <link rel="stylesheet" href="./static/css/header_en_general.css" />
    <!-- <link rel="stylesheet" href="./static/css/promociones.css" />  Comentado para usar los nuevos estilos -->
    <link rel="stylesheet" href="./static/css/base.css" />
    <link rel="stylesheet" href="./static/css/chatbot.css" />
    <!-- <link rel="stylesheet" href="./css/ofertas.css"> Comentado para evitar conflictos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="./static/css/responsive.css" />


    <!-- Nuevos Estilos Integrados -->
    <style>
        /* Estilos específicos para la sección de promociones nueva */
        .promociones-page-content {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #ffffff;
            line-height: 1.6;
        }

        /* Hero Section */
        .hero-promo {
            background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-bottom: 3px solid #ff0000;
            margin-top: 0; 
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: #ff0000;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            font-weight: bold;
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        /* Promociones Section */
        .promociones-section {
            padding: 4rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title-promo {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            position: relative;
            color: #ffffff;
        }

        .section-title-promo::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: #ff0000;
        }

        .promo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }

        /* Nueva Tarjeta de Promoción */
        .promo-card {
            background: #111111;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #333;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .promo-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(255, 0, 0, 0.15);
            border-color: #ff0000;
        }

        .promo-img {
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .promo-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .promo-card:hover .promo-img img {
            transform: scale(1.1);
        }

        .promo-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff0000;
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-weight: 800;
            font-size: 1rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            z-index: 2;
        }

        .stock-badge {
            position: absolute;
            bottom: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.8);
            color: #4ade80;
            padding: 0.25rem 0.75rem;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #4ade80;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stock-badge::before {
            content: '●';
            font-size: 0.8rem;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #ffc107;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .rating-number {
            color: #888;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .promo-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .promo-title {
            font-size: 1.4rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .promo-desc {
            color: #aaaaaa;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #888;
        }

        .product-meta span {
            background: #1a1a1a;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid #333;
        }

        .promo-price {
            display: flex;
            align-items: baseline;
            gap: 1rem;
            margin-bottom: 1.5rem;
            margin-top: auto;
        }

        .old-price {
            color: #666;
            text-decoration: line-through;
            font-size: 1rem;
        }

        .new-price {
            color: #ff0000;
            font-size: 1.8rem;
            font-weight: 800;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a1a1a;
            border-radius: 8px;
            border: 1px solid #333;
            margin-bottom: 1rem;
            padding: 5px;
            max-width: 150px;
        }

        .qty-btn {
            background: none;
            border: none;
            color: #ffffff;
            font-size: 1.2rem;
            width: 30px;
            height: 30px;
            cursor: pointer;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            color: #ff0000;
        }

        .quantity-selector input {
            background: none;
            border: none;
            color: #ffffff;
            text-align: center;
            width: 40px;
            font-weight: bold;
            font-size: 1rem;
            -moz-appearance: textfield;
        }
        
        .quantity-selector input::-webkit-outer-spin-button,
        .quantity-selector input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .promo-btn {
            background: #ff0000;
            color: #ffffff;
            border: none;
            padding: 1rem;
            width: 100%;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .promo-btn:hover {
            background: #cc0000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 0, 0, 0.3);
        }
        
        .promo-btn:active {
            transform: translateY(0);
        }

        .view-details {
            display: block;
            text-align: center;
            color: #888;
            margin-top: 1rem;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .view-details:hover {
            color: #ff0000;
        }

        /* Ofertas Especiales */
        .ofertas-especiales {
            background: #111111;
            padding: 4rem 5%;
            border-top: 3px solid #ff0000;
            border-bottom: 3px solid #ff0000;
        }

        .oferta-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .oferta-item {
            text-align: center;
            padding: 2rem;
            background: #000000;
            border-radius: 10px;
            border: 1px solid #333;
            transition: border-color 0.3s;
        }

        .oferta-item:hover {
            border-color: #ff0000;
        }

        .oferta-icon {
            font-size: 3rem;
            color: #ff0000;
            margin-bottom: 1rem;
        }

        .oferta-item h3 {
            color: #ff0000;
            margin-bottom: 0.5rem;
            font-weight: bold;
            font-size: 1.3rem;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #000000 0%, #1a0000 100%);
            padding: 4rem 5%;
            text-align: center;
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            color: #ff0000;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .cta-content p {
            margin-bottom: 2rem;
        }

        .cta-btn {
            background: #ff0000;
            color: #ffffff;
            border: none;
            padding: 1rem 3rem;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .cta-btn:hover {
            background: #cc0000;
            transform: scale(1.05);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
             
            .section-title-promo {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body class="bg-black text-white">
    <!-- Header Original -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <a href="./index.html">
                    <img src="./assets/icon/LOGO-DISTRICARNES.png" alt="DISTRICARNES Logo">
                </a>
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
                <a href="./productos.php">Productos</a>
                <a href="./promociones.php" class="active">Ofertas</a>
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

    <!-- Contenido Principal - Promociones -->
    <div class="promociones-page-content">
        <section class="hero-promo">
            <div class="hero-content">
                <h1>¡PROMOCIONES IMPERDIBLES!</h1>
                <p>La mejor calidad al mejor precio, solo por tiempo limitado</p>
            </div>
        </section>

        <section class="promociones-section">
            <h2 class="section-title-promo">Promociones de la Semana</h2>

            <div class="promo-grid">
                <?php if (count($ofertas) > 0): ?>
                    <?php foreach ($ofertas as $oferta): ?>
                        <div class="promo-card">
                            <div class="promo-img">
                                <img src="<?php echo htmlspecialchars(!empty($oferta['imagen']) ? '.' . $oferta['imagen'] : 'https://images.unsplash.com/photo-1602470523298-6092c4d926d1?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80'); ?>" alt="<?php echo htmlspecialchars($oferta['nombre']); ?>">
                                <?php 
                                    $tag = '';
                                    $precio_actual = 0;
                                    $precio_antes_mostrar = '';
                                    $precio_actual_mostrar = '';
                                    
                                    // Lógica básica de precios
                                    if ($oferta['tipo'] === 'percentage') {
                                        $tag = '-' . intval($oferta['valor_descuento']) . '%';
                                        // Simular precio base si no hay producto real (para demo visual)
                                        $precio_base_ficticio = 25000; 
                                        $descuento = ($precio_base_ficticio * $oferta['valor_descuento']) / 100;
                                        $precio_actual = $precio_base_ficticio - $descuento;
                                        
                                        $precio_antes_mostrar = '$' . number_format($precio_base_ficticio, 0, ',', '.');
                                        $precio_actual_mostrar = '$' . number_format($precio_actual, 0, ',', '.');
                                        
                                    } elseif ($oferta['tipo'] === 'bogo') {
                                        $tag = '2x1';
                                        $precio_actual = $oferta['valor_descuento'] > 0 ? $oferta['valor_descuento'] : 15000; // Fallback
                                        $precio_antes_mostrar = ''; // No aplica "antes" en 2x1 usualmente, o es el precio de 2
                                        $precio_actual_mostrar = '$' . number_format($precio_actual, 0, ',', '.') . ' (Lleva 2)';
                                        
                                    } elseif ($oferta['tipo'] === 'fixed') {
                                        $tag = 'OFERTA';
                                        $precio_actual = $oferta['valor_descuento'];
                                        // Simular un precio "antes" mayor
                                        $precio_antes_mostrar = '$' . number_format($precio_actual * 1.2, 0, ',', '.');
                                        $precio_actual_mostrar = '$' . number_format($precio_actual, 0, ',', '.');
                                    }
                                ?>
                                <span class="promo-tag"><?php echo $tag; ?></span>
                                <span class="stock-badge">En stock</span>
                            </div>
                            
                            <div class="promo-info">
                                <h3 class="promo-title"><?php echo htmlspecialchars($oferta['nombre']); ?></h3>
                                <div class="product-rating">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    <span class="rating-number">(5.0)</span>
                                </div>
                                <p class="promo-desc"><?php echo htmlspecialchars($oferta['descripcion'] ?? 'Corte seleccionado de alta calidad.'); ?></p>
                                
                                <div class="product-meta">
                                    <span class="weight"><i class="fas fa-weight-hanging"></i> Peso aprox: 500g - 1kg</span>
                                    <span class="origin"><i class="fas fa-map-marker-alt"></i> Origen: Nacional</span>
                                </div>
                                
                                <div class="promo-price">
                                    <?php if($precio_antes_mostrar && $oferta['tipo'] !== 'bogo'): ?>
                                        <span class="old-price"><?php echo $precio_antes_mostrar; ?></span>
                                    <?php endif; ?>
                                    <span class="new-price"><?php echo $precio_actual_mostrar; ?></span>
                                </div>
                                
                                <div class="quantity-selector">
                                    <button class="qty-btn minus" onclick="updateQty(this, -1)">-</button>
                                    <input type="number" class="quantity-input" value="1" min="1" max="10" readonly>
                                    <button class="qty-btn plus" onclick="updateQty(this, 1)">+</button>
                                </div>
                                
                                <!-- Botón Añadir al Carrito -->
                                <!-- Usamos las clases que cart_utils.js reconoce automáticamente: add-to-cart-btn -->
                                <!-- Pasamos los datos necesarios vía data attributes -->
                                <button class="promo-btn add-to-cart-btn" 
                                        data-id="promo-<?php echo $oferta['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($oferta['nombre']); ?>"
                                        data-price="<?php echo $precio_actual; ?>"
                                        data-image="<?php echo htmlspecialchars(!empty($oferta['imagen']) ? '.' . $oferta['imagen'] : 'https://images.unsplash.com/photo-1602470523298-6092c4d926d1?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80'); ?>"
                                        data-qty="1">
                                    <i class="fas fa-shopping-cart"></i> Añadir al Carrito
                                </button>
                                
                                <a href="./productos.php" class="view-details">Ver detalles del producto →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center">
                        <p class="text-xl">No hay promociones activas en este momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Script para manejar la cantidad localmente antes de añadir al carrito -->
        <script>
            function updateQty(btn, change) {
                const container = btn.closest('.quantity-selector');
                const input = container.querySelector('.quantity-input');
                let val = parseInt(input.value);
                val += change;
                if (val < 1) val = 1;
                if (val > 10) val = 10;
                input.value = val;
                
                // Actualizar el data-qty del botón de añadir al carrito correspondiente
                const card = btn.closest('.promo-card');
                const addBtn = card.querySelector('.add-to-cart-btn');
                if(addBtn) {
                    addBtn.dataset.qty = val;
                }
            }
        </script>

        <section class="ofertas-especiales">
            <h2 class="section-title-promo">Ofertas Especiales</h2>

            <div class="oferta-container">
                <div class="oferta-item">
                    <div class="oferta-icon">🥩</div>
                    <h3>Combo Asado</h3>
                    <p>Vacío + Chorizo + Costillar</p>
                    <p style="color: #ff0000; font-size: 1.5rem; font-weight: bold;">$29.990</p>
                    <p style="color: #999; text-decoration: line-through;">$39.990</p>
                </div>

                <div class="oferta-item">
                    <div class="oferta-icon">🍖</div>
                    <h3>Pack Familiar</h3>
                    <p>Pollo Entero + Carne Molida</p>
                    <p style="color: #ff0000; font-size: 1.5rem; font-weight: bold;">$12.990</p>
                    <p style="color: #999; text-decoration: line-through;">$16.980</p>
                </div>

                <div class="oferta-item">
                    <div class="oferta-icon">🥓</div>
                    <h3>Promo Fin de Semana</h3>
                    <p>Lomo Fino + Entraña</p>
                    <p style="color: #ff0000; font-size: 1.5rem; font-weight: bold;">$24.990</p>
                    <p style="color: #999; text-decoration: line-through;">$32.980</p>
                </div>

                <div class="oferta-item">
                    <div class="oferta-icon">🔥</div>
                    <h3>Combo Parrillero</h3>
                    <p>Todos los cortes para 6 personas</p>
                    <p style="color: #ff0000; font-size: 1.5rem; font-weight: bold;">$49.990</p>
                    <p style="color: #999; text-decoration: line-through;">$65.990</p>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="cta-content">
                <h2>¡No te pierdas estas ofertas!</h2>
                <p>Suscríbete a nuestro newsletter y recibe promociones exclusivas directamente en tu correo</p>
                <button class="cta-btn">Suscribirme</button>
            </div>
        </section>
    </div>


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
                    <div class="menu-option">
                        <i class="fas fa-drumstick-bite"></i> Ver productos cárnicos
                    </div>
                    <div class="menu-option">
                        <i class="fas fa-cut"></i> Tipos de cortes
                    </div>
                    <div class="menu-option">
                        <i class="fas fa-clock"></i> Horarios y ubicación
                    </div>
                    <div class="menu-option">
                        <i class="fas fa-tags"></i> Precios y ofertas
                    </div>
                    <div class="menu-option">
                        <i class="fas fa-info-circle"></i> Sobre nosotros
                    </div>
                    <div class="menu-option">
                        <i class="fas fa-phone"></i> Contactar
                    </div>
                </div>
                <div class="message-timestamp">10:01 AM</div>
            </div>
        </div>
        <div class="chatbot-input">
            <div class="input-container">
                <input type="text" class="chat-input" id="userInput" placeholder="¿Qué deseas saber sobre nuestras carnes?" onkeypress="handleKeyPress(event)" autocomplete="off" />
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

    <!-- Script de autenticación global -->
    <script src="./static/js/header_actions.js"></script>
    <script src="./js/auth.js"></script>
    <script src="./static/js/cart_badge.js"></script>
    <script src="./static/js/history_favorites.js"></script>
    <script src="./static/js/index.js"></script>
    <script src="./static/js/chatbot.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.AuthSystem && typeof AuthSystem.checkUserSession === 'function') {
                AuthSystem.checkUserSession();
            }
        });
    </script>
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
    <script src="./static/js/cart_utils.js"></script>
    <script src="./static/js/loader.js" defer></script>
    <script src="./static/js/session_guard.js" defer></script>
    <script src="./static/js/network_guard.js" defer>
    </script>
    <!-- Global Auth Utilities -->
    <script src="./static/js/auth_utils.js" defer></script>

    <!-- NOTA: Ya no necesitamos promociones.js para cargar las ofertas porque las cargamos con PHP -->
    <!-- <script src="./static/js/promociones.js" defer></script> -->
    <!-- Java script para las animaciones del carrusel de las imagenes -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
