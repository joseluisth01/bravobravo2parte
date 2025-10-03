/**
 * JavaScript para gestión de reservas de visitas guiadas
 * Archivo: wp-content/plugins/sistema-reservas/assets/js/visita-script.js
 */

let serviceData = null;

jQuery(document).ready(function($) {
    console.log('=== VISITA SCRIPT INICIALIZADO ===');
    
    // Verificar si estamos en la página de detalles
    if ($('#service-hero').length > 0) {
        loadServiceData();
    }
    
    // Verificar si estamos en la página de confirmación
    if ($('.confirmacion-visita-container').length > 0) {
        loadConfirmationData();
    }
    
    // Event listeners para cálculo de precio
    $('#adultos-visita, #ninos-visita').on('input change', function() {
        calculateTotalPrice();
    });
});

/**
 * Cargar datos del servicio desde sessionStorage
 */
function loadServiceData() {
    console.log('=== CARGANDO DATOS DEL SERVICIO ===');
    
    try {
        const dataString = sessionStorage.getItem('selectedServiceData');
        
        if (!dataString) {
            console.error('No hay datos del servicio en sessionStorage');
            alert('Error: No se encontraron datos del servicio. Por favor, vuelve a seleccionar el servicio.');
            window.history.back();
            return;
        }
        
        serviceData = JSON.parse(dataString);
        console.log('Datos del servicio cargados:', serviceData);
        
        // Rellenar la página con los datos
        populateServicePage();
        
        // Calcular precio inicial
        calculateTotalPrice();
        
    } catch (error) {
        console.error('Error cargando datos del servicio:', error);
        alert('Error cargando los datos del servicio');
        window.history.back();
    }
}

/**
 * Rellenar la página con los datos del servicio
 */
function populateServicePage() {
    console.log('=== RELLENANDO PÁGINA ===');
    
    // Imagen de portada y título
    if (serviceData.portada_url) {
        jQuery('#hero-image').attr('src', serviceData.portada_url);
    } else {
        // Imagen por defecto si no hay
        jQuery('#hero-image').attr('src', 'https://via.placeholder.com/1200x400?text=Visita+Guiada');
    }
    
    jQuery('#service-title').text(serviceData.titulo || serviceData.agency_name || 'VISITA GUIADA');
    
    // Fecha y hora de la reserva del autobús
    const fechaObj = new Date(serviceData.fecha + 'T00:00:00');
    const fechaFormateada = fechaObj.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    jQuery('#fecha-visita').text(fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1));
    jQuery('#hora-inicio').text(serviceData.hora || '-');
    
    // Precios por persona
    const precioAdulto = parseFloat(serviceData.precio_adulto) || 0;
    const precioNino = parseFloat(serviceData.precio_nino) || 0;
    
    jQuery('#precio-adulto-display').text(precioAdulto.toFixed(2) + '€/persona');
    jQuery('#precio-nino-display').text(precioNino.toFixed(2) + '€/persona');
    
    console.log('✅ Página rellenada correctamente');
}

/**
 * Calcular precio total de la visita
 */
function calculateTotalPrice() {
    if (!serviceData) {
        console.log('No hay datos del servicio para calcular precio');
        return;
    }
    
    const adultos = parseInt(jQuery('#adultos-visita').val()) || 0;
    const ninos = parseInt(jQuery('#ninos-visita').val()) || 0;
    
    const precioAdulto = parseFloat(serviceData.precio_adulto) || 0;
    const precioNino = parseFloat(serviceData.precio_nino) || 0;
    
    const total = (adultos * precioAdulto) + (ninos * precioNino);
    
    jQuery('#total-visita').text(total.toFixed(2) + '€');
    
    console.log('Precio calculado:', {
        adultos: adultos,
        ninos: ninos,
        precioAdulto: precioAdulto,
        precioNino: precioNino,
        total: total
    });
}

/**
 * Procesar reserva de visita
 */
function processVisitaReservation() {
    console.log('=== PROCESANDO RESERVA DE VISITA ===');
    
    // Validar política de privacidad
    const privacyCheckbox = document.getElementById('privacy-policy-visita');
    if (!privacyCheckbox || !privacyCheckbox.checked) {
        alert('Debes aceptar la política de privacidad para continuar.');
        if (privacyCheckbox) privacyCheckbox.focus();
        return;
    }
    
    // Validar datos personales
    const nombre = jQuery('[name="nombre"]').val().trim();
    const apellidos = jQuery('[name="apellidos"]').val().trim();
    const email = jQuery('[name="email"]').val().trim();
    const telefono = jQuery('[name="telefono"]').val().trim();
    
    if (!nombre || nombre.length < 2) {
        alert('Por favor, introduce un nombre válido (mínimo 2 caracteres).');
        jQuery('[name="nombre"]').focus();
        return;
    }
    
    if (!apellidos || apellidos.length < 2) {
        alert('Por favor, introduce apellidos válidos (mínimo 2 caracteres).');
        jQuery('[name="apellidos"]').focus();
        return;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email || !emailRegex.test(email)) {
        alert('Por favor, introduce un email válido.');
        jQuery('[name="email"]').focus();
        return;
    }
    
    if (!telefono || telefono.length < 9) {
        alert('Por favor, introduce un teléfono válido (mínimo 9 dígitos).');
        jQuery('[name="telefono"]').focus();
        return;
    }
    
    // Validar personas
    const adultos = parseInt(jQuery('#adultos-visita').val()) || 0;
    const ninos = parseInt(jQuery('#ninos-visita').val()) || 0;
    
    if (adultos < 1) {
        alert('Debe haber al menos un adulto en la reserva.');
        jQuery('#adultos-visita').focus();
        return;
    }
    
    // Obtener total
    const totalText = jQuery('#total-visita').text();
    const total = parseFloat(totalText.replace('€', '').trim());
    
    // Preparar datos para enviar
    const reservationData = {
        action: 'process_visita_reservation',
        nonce: reservasVisitaAjax.nonce,
        service_id: serviceData.id,
        agency_id: serviceData.agency_id,
        fecha: serviceData.fecha,
        hora: serviceData.hora,
        adultos: adultos,
        ninos: ninos,
        total: total,
        nombre: nombre,
        apellidos: apellidos,
        email: email,
        telefono: telefono
    };
    
    console.log('Datos a enviar:', reservationData);
    
    // Deshabilitar botón
    const processBtn = jQuery('.complete-btn');
    const originalText = processBtn.text();
    processBtn.prop('disabled', true).text('Procesando...');
    
    // Enviar solicitud AJAX
    jQuery.ajax({
        url: reservasVisitaAjax.ajax_url,
        type: 'POST',
        data: reservationData,
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            
            if (response.success) {
                console.log('✅ Reserva procesada correctamente');
                
                // Guardar datos para la página de confirmación
                sessionStorage.setItem('visitaConfirmationData', JSON.stringify({
                    localizador: response.data.localizador,
                    fecha: serviceData.fecha,
                    hora: serviceData.hora,
                    adultos: adultos,
                    ninos: ninos,
                    total: total,
                    nombre: nombre,
                    apellidos: apellidos,
                    email: email
                }));
                
                // Redirigir a página de confirmación
                window.location.href = response.data.redirect_url;
            } else {
                console.error('❌ Error en la respuesta:', response.data);
                alert('Error: ' + (response.data || 'Error desconocido al procesar la reserva'));
                processBtn.prop('disabled', false).text(originalText);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error AJAX:', error);
            console.error('Response:', xhr.responseText);
            alert('Error de conexión al procesar la reserva. Por favor, inténtalo de nuevo.');
            processBtn.prop('disabled', false).text(originalText);
        }
    });
}

/**
 * Cargar datos de confirmación
 */
function loadConfirmationData() {
    console.log('=== CARGANDO DATOS DE CONFIRMACIÓN ===');
    
    try {
        const dataString = sessionStorage.getItem('visitaConfirmationData');
        
        if (!dataString) {
            console.error('No hay datos de confirmación en sessionStorage');
            return;
        }
        
        const data = JSON.parse(dataString);
        console.log('Datos de confirmación cargados:', data);
        
        // Rellenar datos
        jQuery('#conf-localizador').text(data.localizador || '-');
        
        // Formatear fecha
        const fechaObj = new Date(data.fecha + 'T00:00:00');
        const fechaFormateada = fechaObj.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        jQuery('#conf-fecha').text(fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1));
        
        jQuery('#conf-hora').text(data.hora || '-');
        
        const totalPersonas = (data.adultos || 0) + (data.ninos || 0);
        jQuery('#conf-personas').text(totalPersonas + ' persona' + (totalPersonas !== 1 ? 's' : ''));
        
        jQuery('#conf-total').text((data.total || 0).toFixed(2) + '€');
        
        // Limpiar sessionStorage
        sessionStorage.removeItem('visitaConfirmationData');
        sessionStorage.removeItem('selectedServiceData');
        
        console.log('✅ Datos de confirmación cargados correctamente');
        
    } catch (error) {
        console.error('Error cargando datos de confirmación:', error);
    }
}

/**
 * Volver a servicios
 */
function goBackToServices() {
    sessionStorage.removeItem('selectedServiceData');
    
    // ✅ CONSTRUIR URL RELATIVA CORRECTAMENTE
    const currentPath = window.location.pathname;
    let targetUrl;
    
    // Si estamos en un subdirectorio
    if (currentPath.includes('/')) {
        const pathParts = currentPath.split('/').filter(part => part !== '');
        
        // Si hay al menos una parte en la ruta (subdirectorio)
        if (pathParts.length > 0 && pathParts[0] !== 'confirmacion-reserva') {
            // Usar el primer segmento como base
            targetUrl = window.location.origin + '/' + pathParts[0] + '/confirmacion-reserva/';
        } else {
            // Estamos en la raíz
            targetUrl = window.location.origin + '/confirmacion-reserva/';
        }
    } else {
        // Estamos en la raíz
        targetUrl = window.location.origin + '/confirmacion-reserva/';
    }
    
    console.log('Volviendo a:', targetUrl);
    window.location.href = targetUrl;
}

/**
 * Volver al inicio
 */
function goBackToInicio() {
    // ✅ CONSTRUIR URL DE INICIO CORRECTAMENTE
    const currentPath = window.location.pathname;
    let targetUrl;
    
    // Si estamos en un subdirectorio
    if (currentPath.includes('/')) {
        const pathParts = currentPath.split('/').filter(part => part !== '');
        
        // Si hay al menos una parte en la ruta (subdirectorio)
        if (pathParts.length > 0) {
            // Usar el primer segmento como base
            targetUrl = window.location.origin + '/' + pathParts[0] + '/';
        } else {
            // Estamos en la raíz
            targetUrl = window.location.origin + '/';
        }
    } else {
        // Estamos en la raíz
        targetUrl = window.location.origin + '/';
    }
    
    console.log('Volviendo al inicio:', targetUrl);
    window.location.href = targetUrl;
}

/**
 * Ver comprobante de visita (PENDIENTE - para próxima fase)
 */
function viewVisitaTicket() {
    alert('La función de visualización de comprobantes estará disponible próximamente.');
}

/**
 * Descargar comprobante de visita (PENDIENTE - para próxima fase)
 */
function downloadVisitaTicket() {
    alert('La función de descarga de comprobantes estará disponible próximamente.');
}