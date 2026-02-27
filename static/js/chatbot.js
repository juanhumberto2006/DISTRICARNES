// ===== CHATBOT DISTRICARNES HERMANOS NAVARRO =====

function toggleChatbot() {
    const chatbot = document.querySelector('.chatbot-container');
    chatbot.classList.toggle('active');
    if (chatbot.classList.contains('active')) {
        setTimeout(() => {
            const input = document.getElementById('userInput') || document.querySelector('.chat-input');
            if (input) input.focus();
        }, 200);
    }
}

function sendMessageUI() {
    const input = document.querySelector('.chat-input');
    const messageText = input.value.trim();
    if (!messageText) return;

    // Dejado como wrapper por compatibilidad; usa la versión con IA
    const inputEl = document.getElementById('userInput');
    if (inputEl) inputEl.value = messageText;
    sendMessage();
}

function handleKeyPress(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function showTypingIndicator() {
    const messages = document.querySelector('.chatbot-messages');
    let typingIndicator = document.querySelector('.typing-indicator');
    if (!typingIndicator) {
        typingIndicator = document.createElement('div');
        typingIndicator.className = 'typing-indicator';
        typingIndicator.innerHTML = '<div class="dot"></div><div class="dot"></div><div class="dot"></div>';
        messages.appendChild(typingIndicator);
    }
    typingIndicator.style.display = 'flex';
    messages.scrollTop = messages.scrollHeight;
}

function hideTypingIndicator() {
    const typingIndicator = document.querySelector('.typing-indicator');
    if (typingIndicator) {
        typingIndicator.style.display = 'none';
    }
}

// ===== RESPUESTAS INTELIGENTES DEL BOT =====
function getBotResponse(message) {
    message = message.toLowerCase();
    
    // Productos cárnicos
    if (message.includes('productos') || message.includes('carnes') || message.includes('carne')) {
        return getProductsResponse(message);
    }
    
    // Tipos de cortes
    else if (message.includes('cortes') || message.includes('corte') || message.includes('filete') || message.includes('chuleta')) {
        return getCutsResponse(message);
    }
    
    // Precios y ofertas
    else if (message.includes('precio') || message.includes('precios') || message.includes('costo') || message.includes('ofertas') || message.includes('descuento')) {
        return getPricesResponse();
    }
    
    // Horarios y ubicación
    else if (message.includes('horario') || message.includes('horarios') || message.includes('abierto') || message.includes('ubicación') || message.includes('dirección')) {
        return getScheduleLocationResponse();
    }
    
    // Información sobre la empresa
    else if (message.includes('sobre') || message.includes('empresa') || message.includes('historia') || message.includes('navarro') || message.includes('hermanos')) {
        return getAboutResponse();
    }
    
    // Contacto
    else if (message.includes('contacto') || message.includes('teléfono') || message.includes('whatsapp') || message.includes('llamar')) {
        return getContactResponse();
    }
    
    // Calidad y frescura
    else if (message.includes('fresco') || message.includes('frescura') || message.includes('calidad') || message.includes('premium')) {
        return getQualityResponse();
    }
    
    // Preparación y consejos
    else if (message.includes('cocinar') || message.includes('preparar') || message.includes('receta') || message.includes('consejos')) {
        return getCookingTipsResponse(message);
    }
    
    // Disponibilidad
    else if (message.includes('disponible') || message.includes('stock') || message.includes('hay') || message.includes('tienen')) {
        return getAvailabilityResponse();
    }
    
    // Saludos
    else if (message.includes('hola') || message.includes('buenos') || message.includes('buenas') || message.includes('saludos')) {
        return '¡Hola! 👋 Bienvenido a DISTRICARNES Hermanos Navarro. Somos especialistas en carnes premium con más de 28 años de tradición. ¿En qué puedo ayudarte hoy?';
    }
    
    // Despedidas
    else if (message.includes('gracias') || message.includes('adiós') || message.includes('chao') || message.includes('bye')) {
        return '¡De nada! 😊 Gracias por elegir DISTRICARNES Hermanos Navarro. ¡Esperamos verte pronto en nuestra carnicería! 🥩';
    }
    
    // Respuesta por defecto
    else {
        return getDefaultResponse();
    }
}

// ===== RESPUESTAS ESPECÍFICAS =====
function getProductsResponse(message) {
    const responses = [
        '🥩 <strong>Nuestros productos estrella:</strong><br>• Carne de res premium (lomo, filete, chuleta)<br>• Carne de cerdo fresca (lomo, chuleta, costillas)<br>• Pollo fresco y orgánico<br>• Pescados y mariscos del día<br>• Embutidos artesanales<br><br>¿Te interesa algún producto en particular?',
        '🍖 <strong>Carnes Premium disponibles:</strong><br>• Filete de res (corte especial)<br>• Lomo de cerdo fresco<br>• Chuletas de cerdo<br>• Robalo fresco<br>• Pollo de granja<br><br>Todas nuestras carnes son seleccionadas cuidadosamente para garantizar la máxima calidad.',
        '🥓 <strong>Especialidades de la casa:</strong><br>• Carne BBQ marinada<br>• Cortes premium para asados<br>• Pescados frescos del día<br>• Embutidos caseros<br>• Carnes orgánicas<br><br>¡Pregúntame por disponibilidad y precios!'
    ];
    return responses[Math.floor(Math.random() * responses.length)];
}

function getCutsResponse(message) {
    if (message.includes('res') || message.includes('beef')) {
        return '🥩 <strong>Cortes de res disponibles:</strong><br>• Filete mignon<br>• Lomo alto y bajo<br>• Chuleta de res<br>• Costillas<br>• Carne para guisar<br>• Carne molida premium<br><br>Todos nuestros cortes son frescos y de la mejor calidad.';
    } else if (message.includes('cerdo') || message.includes('pork')) {
        return '🐷 <strong>Cortes de cerdo frescos:</strong><br>• Lomo de cerdo<br>• Chuletas de cerdo<br>• Costillas BBQ<br>• Tocino fresco<br>• Pernil<br>• Carne molida de cerdo<br><br>Perfectos para cualquier ocasión especial.';
    } else {
        return '🔪 <strong>Nuestros cortes especializados:</strong><br>• Cortes de res premium<br>• Cortes de cerdo frescos<br>• Filetes de pescado<br>• Cortes para BBQ<br>• Cortes para guisos<br><br>¿Qué tipo de corte necesitas? ¡Puedo darte más detalles!';
    }
}

function getPricesResponse() {
    return '💰 <strong>Información de precios:</strong><br>• Manejamos precios competitivos y justos<br>• Ofertas especiales los fines de semana<br>• Descuentos por compras al mayor<br>• Promociones en productos de temporada<br><br>📞 Para precios específicos, contáctanos directamente. ¡Los precios pueden variar según disponibilidad!';
}

function getScheduleLocationResponse() {
    return '🕒 <strong>Horarios de atención:</strong><br>• Lunes a Sábado: 7:00 AM - 7:00 PM<br>• Domingos: 8:00 AM - 2:00 PM<br><br>📍 <strong>Ubicación:</strong><br>Estamos ubicados en el corazón de la ciudad, fácil acceso y estacionamiento disponible.<br><br>🚗 ¡Ven a visitarnos y conoce nuestras instalaciones!';
}

function getAboutResponse() {
    return '🏪 <strong>DISTRICARNES Hermanos Navarro</strong><br><br>Con más de <strong>28 años de tradición</strong>, somos una empresa familiar dedicada a ofrecer las mejores carnes premium. Hemos atendido a más de <strong>8,500 familias</strong> con productos 100% frescos.<br><br>🏆 <strong>Nuestros valores:</strong><br>• Calidad garantizada<br>• Frescura diaria<br>• Servicio personalizado<br>• Tradición familiar';
}

function getContactResponse() {
    return '📞 <strong>Contáctanos:</strong><br>• Teléfono: [Número de teléfono]<br>• WhatsApp: [Número de WhatsApp]<br>• Email: info@districarnes.com<br><br>🏪 <strong>Visítanos:</strong><br>• Dirección: [Dirección completa]<br>• Horarios: Lun-Sáb 7AM-7PM, Dom 8AM-2PM<br><br>¡Estamos aquí para atenderte!';
}

function getQualityResponse() {
    return '⭐ <strong>Nuestra garantía de calidad:</strong><br>• 100% carnes frescas diariamente<br>• Productos premium seleccionados<br>• Cadena de frío garantizada<br>• Más de 28 años de experiencia<br>• Certificaciones de calidad<br><br>🥩 ¡La frescura que tu familia merece!';
}

function getCookingTipsResponse(message) {
    if (message.includes('res') || message.includes('filete')) {
        return '👨‍🍳 <strong>Consejos para carne de res:</strong><br>• Saca la carne del refrigerador 30 min antes<br>• Sazona con sal y pimienta<br>• Sella a fuego alto por ambos lados<br>• Cocina al término deseado<br>• Deja reposar 5 minutos antes de servir<br><br>¡El secreto está en no sobrecocinar!';
    } else if (message.includes('cerdo')) {
        return '🐷 <strong>Consejos para carne de cerdo:</strong><br>• Cocina completamente (75°C interno)<br>• Marina previamente para más sabor<br>• Cocina a fuego medio-bajo<br>• Usa termómetro para verificar cocción<br>• Deja reposar antes de cortar<br><br>¡Perfecta para asados familiares!';
    } else {
        return '🍳 <strong>Consejos generales de cocción:</strong><br>• Usa las temperaturas adecuadas<br>• No voltees la carne constantemente<br>• Deja reposar después de cocinar<br>• Sazona al gusto<br>• Acompaña con vegetales frescos<br><br>¿Necesitas consejos para algún corte específico?';
    }
}

function getAvailabilityResponse() {
    return '✅ <strong>Disponibilidad actual:</strong><br>• Productos frescos diariamente<br>• Stock renovado cada mañana<br>• Reservas disponibles por teléfono<br>• Productos de temporada según disponibilidad<br><br>📞 ¡Llámanos para confirmar disponibilidad de productos específicos!';
}

function getDefaultResponse() {
    const responses = [
        '🤔 No estoy seguro de entender tu pregunta. Puedo ayudarte con:<br>• Productos cárnicos<br>• Tipos de cortes<br>• Precios y ofertas<br>• Horarios y ubicación<br>• Información sobre nosotros<br>• Contacto',
        '❓ ¿Podrías ser más específico? Estoy aquí para ayudarte con:<br>• Carnes y productos<br>• Consejos de cocina<br>• Horarios de atención<br>• Información de contacto<br>• Preguntas sobre calidad',
        '💭 No entendí completamente. ¿Te interesa saber sobre:<br>• Nuestros productos frescos<br>• Cortes especiales<br>• Horarios de la carnicería<br>• Cómo contactarnos<br>• Nuestra historia familiar'
    ];
    return responses[Math.floor(Math.random() * responses.length)];
}

// ===== MANEJO DE ACCIONES RÁPIDAS =====
function handleQuickAction(action) {
    let actionText = '';
    switch(action) {
        case 'productos':
            actionText = 'Ver productos cárnicos';
            break;
        case 'horarios':
            actionText = 'Horarios y ubicación';
            break;
        case 'contacto':
            actionText = 'Contactar';
            break;
        default:
            actionText = action;
    }
    sendToAI(actionText);
}

// ===== INICIALIZACIÓN DEL CHATBOT =====
document.addEventListener('DOMContentLoaded', function() {
    // Manejo de opciones del menú
    document.querySelectorAll('.menu-option').forEach(option => {
        option.addEventListener('click', () => {
            sendToAI(option.textContent.trim());
        });
    });
    
    // Forzar binding del botón Enviar y del input, por si falla el inline onclick/onkeypress
    const sendBtn = document.querySelector('.send-btn');
    if (sendBtn) {
        sendBtn.addEventListener('click', function (e) {
            e.preventDefault();
            sendMessage();
        });
    }
    const inputEl = document.getElementById('userInput') || document.querySelector('.chat-input');
    if (inputEl) {
        inputEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    // Auto-scroll al final cuando se abre el chatbot
    const chatbotToggle = document.querySelector('.chatbot-toggle');
    if (chatbotToggle) {
        chatbotToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.preventDefault();
            e.stopPropagation();
            try { toggleChatbot(); } catch (_) {}
            setTimeout(() => {
                const messages = document.querySelector('.chatbot-messages');
                if (messages) {
                    messages.scrollTop = messages.scrollHeight;
                }
            }, 300);
        });
    }
});

// ===== FUNCIONES ADICIONALES =====
function clearChat() {
    const messages = document.querySelector('.chatbot-messages');
    messages.innerHTML = `
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
            <div class="message-timestamp">${getCurrentTime()}</div> 
        </div>
    `;
    // Re-inicializar event listeners
    document.querySelectorAll('.menu-option').forEach(option => {
        option.addEventListener('click', () => {
            sendToAI(option.textContent.trim());
        });
    });
}

async function sendToAI(message) {
    if (!message) return;

    const chatBox = document.getElementById('chatBox');
    chatBox.innerHTML += `<div class="message user-message">${message}</div>`;
    chatBox.scrollTop = chatBox.scrollHeight;

    showTypingIndicator();
    try {
        const endpoint = "/DISTRICARNES/backend/api/chat.php";
        const res = await fetch(endpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message })
        });
        const resClone = res.clone();
        let data;
        try {
            data = await res.json();
        } catch (parseErr) {
            const txt = await resClone.text();
            throw new Error(txt && txt.length ? txt : 'Respuesta no válida del servidor');
        }
        if (!res.ok) {
            const msg = data && (data.error || data.message) ? (data.error || data.message) : `HTTP ${res.status}`;
            throw new Error(msg);
        }
        const botReply = (data && data.error)
            ? `Error: ${data.error}`
            : (data && data.reply)
              ? data.reply
              : (data && data.choices && data.choices[0] && data.choices[0].message && data.choices[0].message.content)
                ? data.choices[0].message.content
                : "Lo siento, no pude procesar tu mensaje en este momento.";
        chatBox.innerHTML += `<div class="message bot-message">${botReply}</div>`;
    } catch (e) {
        console.error('Chat error:', e);
        chatBox.innerHTML += `<div class="message bot-message">No pude conectar con el asistente. ${e && e.message ? '('+e.message+')' : ''}</div>`;
    } finally {
        hideTypingIndicator();
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

async function sendMessage() {
    const input = document.getElementById("userInput") || document.querySelector(".chat-input");
    const message = input ? input.value.trim() : "";
    if (!message) return;

    const chatBox = document.getElementById("chatBox");
    chatBox.innerHTML += `<div class="message user-message">${message}</div>`;
    if (input) input.value = "";
    chatBox.scrollTop = chatBox.scrollHeight;

    showTypingIndicator();
    try {
        const endpoint = "/DISTRICARNES/backend/api/chat.php";
        const response = await fetch(endpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message })
        });
        const respClone = response.clone();
        let data;
        try {
            data = await response.json();
        } catch (parseErr) {
            const txt = await respClone.text();
            throw new Error(txt && txt.length ? txt : 'Respuesta no válida del servidor');
        }
        if (!response.ok) {
            const msg = data && (data.error || data.message) ? (data.error || data.message) : `HTTP ${response.status}`;
            throw new Error(msg);
        }
        const botReply = (data && data.error)
            ? `Error: ${data.error}`
            : (data && data.reply)
              ? data.reply
              : (data && data.choices && data.choices[0] && data.choices[0].message && data.choices[0].message.content)
                ? data.choices[0].message.content
                : "Lo siento, no pude procesar tu mensaje en este momento.";
        chatBox.innerHTML += `<div class="message bot-message">${botReply}</div>`;
    } catch (e) {
        console.error('Chat error:', e);
        chatBox.innerHTML += `<div class="message bot-message">No pude conectar con el asistente. ${e && e.message ? '('+e.message+')' : ''}</div>`;
    } finally {
        hideTypingIndicator();
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}
