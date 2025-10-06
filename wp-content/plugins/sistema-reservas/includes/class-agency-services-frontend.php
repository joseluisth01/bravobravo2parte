<?php

/**
 * Clase para gestionar el frontend de servicios de agencias
 * Archivo: wp-content/plugins/sistema-reservas/includes/class-agency-services-frontend.php
 */
class ReservasAgencyServicesFrontend
{
    public function __construct()
    {
        // Registrar shortcodes
        add_shortcode('reservas_detalles_visita', array($this, 'render_detalles_visita'));
        add_shortcode('confirmacion_reserva_visita', array($this, 'render_confirmacion_visita'));

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // AJAX para procesar reserva de visita
        add_action('wp_ajax_process_visita_reservation', array($this, 'process_visita_reservation'));
        add_action('wp_ajax_nopriv_process_visita_reservation', array($this, 'process_visita_reservation'));
    }

    public function enqueue_assets()
    {
        global $post;

        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'reservas_detalles_visita') ||
            has_shortcode($post->post_content, 'confirmacion_reserva_visita')
        )) {
            wp_enqueue_style(
                'reservas-visita-style',
                RESERVAS_PLUGIN_URL . 'assets/css/visita-style.css',
                array(),
                '1.0.0'
            );

            wp_enqueue_script(
                'reservas-visita-script',
                RESERVAS_PLUGIN_URL . 'assets/js/visita-script.js',
                array('jquery'),
                '1.0.0',
                true
            );

            wp_localize_script('reservas-visita-script', 'reservasVisitaAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('reservas_nonce')
            ));
        }
    }

    public function render_detalles_visita()
    {
        ob_start();
?>
        <!-- Hero con imagen de portada y título -->
        <div id="service-hero" class="service-hero">
            <img id="hero-image" src="" alt="">
            <button type="button" class="back-btn container" onclick="goBackToServices()">
                <img style="width:10px !important" src="https://autobusmedinaazahara.com/wp-content/uploads/2025/07/Vector-15.svg" alt="">
                VOLVER A SERVICIOS
            </button>
            <div class="hero-overlay container">
                <h1 id="service-title" class="service-hero-title"></h1>
            </div>
        </div>

        <div class="visita-container container">
            <!-- Detalles de compra (precios dinámicos) -->
            <div class="visita-details-section">
                <h2>DETALLES DE COMPRA DE VISITA GUIADA</h2>
                <div style="padding:30px 60px;">

                    <div class="details-info-box">
                        <div style="background-color:#DB7461; display:flex; align-items:center; justify-content: space-around">
                            <div class="info-row adultos">
                                <span class="label">ADULTOS (MAYORES DE 12 AÑOS):</span>
                                <span class="price" id="precio-adulto-info">-€</span>
                            </div>
                            <div class="info-row ninos">
                                <span class="label">NIÑOS (DE 5 A 12 AÑOS):</span>
                                <span class="price" id="precio-nino-info">-€</span>
                            </div>
                            <div class="info-row menores">
                                <span class="label">NIÑOS (-5 AÑOS):</span>
                                <span class="price" id="precio-nino-menor-info">-€</span>
                            </div>
                        </div>

                        <div class="info-notes">
                            <img style="width: 30px;" src="https://dev.tictac-comunicacion.es/bravobravo2parte/wp-content/uploads/2025/10/Vector-20.svg" alt="">
                            <div>
                                <p>*Visita guiada de 3 horas y media aprox.</p>
                                <p>*Sistema de radioguías para grupos con más de 10 componentes</p>
                            </div>
                        </div>
                    </div>

                    <div class="details-grid-visita">
                        <!-- Columna izquierda: Fechas y Personas -->
                        <div class="details-column-left">
                            <div style="width:50%">
                                <div class="section-title">
                                    <h3>FECHAS Y HORAS</h3>
                                </div>

                                <div class="details-card">
                                    <div class="detail-row">
                                        <span class="label"><span>FECHA</span> INICIO VISITA GUIADA:</span>
                                        <span class="value" id="fecha-visita">-</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="label">HORA INICIO VISITA GUIADA:</span>
                                        <span class="value" id="hora-inicio">-</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="label">FECHA FIN VISITA GUIADA:</span>
                                        <span class="value" id="fecha-fin">-</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="label">HORA FIN VISITA GUIADA:</span>
                                        <span class="value" id="hora-fin">-</span>
                                    </div>
                                </div>
                            </div>
                            <div style="width:50%">
                                <div class="section-title">
                                    <h3>ENTRADAS, PERSONAS Y PRECIO</h3>
                                </div>

                                <div class="details-card">
                                    <div class="person-selector">
                                        <label>NÚMERO DE ADULTOS (>12 AÑOS):</label>
                                        <input type="number" id="adultos-visita" min="1" max="999" value="1" class="person-input">
                                    </div>

                                    <div class="person-selector">
                                        <label>NÚMERO DE NIÑOS (5/12 AÑOS):</label>
                                        <input type="number" id="ninos-visita" min="0" max="999" value="0" class="person-input">
                                    </div>

                                    <!-- ✅ NUEVO INPUT -->
                                    <div class="person-selector">
                                        <label>NÚMERO DE NIÑOS (-5 AÑOS):</label>
                                        <input type="number" id="ninos-menores-visita" min="0" max="999" value="0" class="person-input">
                                    </div>

                                    <div class="total-price-visita">
                                        <div class="total-row">
                                            <span class="label">TOTAL COMPRA:</span>
                                            <span class="value" id="total-visita">0,00€</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna derecha: Datos Personales -->
                        <div class="details-column-right">
                            <div class="section-title">
                                <h3>DATOS PERSONALES</h3>
                            </div>

                            <div class="details-card">
                                <form id="visita-personal-data-form">
                                    <div class="form-group">
                                        <input type="text" name="nombre" placeholder="NOMBRE" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" name="apellidos" placeholder="APELLIDOS" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="email" name="email" placeholder="EMAIL" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="tel" name="telefono" placeholder="MÓVIL O TELÉFONO" required>
                                    </div>

                                    <div class="privacy-policy-section">
                                        <label for="privacy-policy-visita">
                                            <input type="checkbox" id="privacy-policy-visita" name="privacy-policy" required>
                                            <span>Acepto haber leído y estar conforme con la <a href="https://autobusmedinaazahara.com/politica-de-privacidad/" target="_blank">política de privacidad</a></span>
                                        </label>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="final-buttons">
                        <button type="button" class="complete-btn" onclick="processVisitaReservation()">
                            COMPLETA COMPRA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar página de confirmación de reserva de visita
     */
    public function render_confirmacion_visita()
    {
        ob_start();
    ?>
        <style>
            .confirmacion-visita-container {
                max-width: 800px;
                margin: 50px auto;
                padding: 0;
            }

            .back-btn {
                color: black;
                border: none;
                font-size: 14px;
                cursor: pointer;
                text-transform: uppercase;
                display: flex;
                align-items: center;
                gap: 10px;
                background: none !important;
                margin-bottom: 20px;
                padding: 0;
                font-family: 'Duran-Regular';
                font-weight: 600;
            }

            .back-btn img {
                width: 10px;
            }

            .success-banner {
                background: #DB7461;
                color: white;
                text-align: center;
                padding: 20px;
                font-weight: bold;
                letter-spacing: 2px;
                border-top-left-radius: 15px;
                border-top-right-radius: 15px;
                font-family: 'Duran-Medium';
                text-transform: uppercase;
            }

            .content-section {
                background: #FFFFFF;
                padding: 50px 60px;
                text-align: center;
                border-bottom-left-radius: 15px;
                border-bottom-right-radius: 15px;
                box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            }

            .todo-listo {
                font-size: 18px;
                font-weight: bold;
                color: #333;
                margin-bottom: 25px;
                font-family: 'Duran-Regular';
            }

            .thank-you-message {
                margin-bottom: 30px;
            }

            .thank-you-message p {
                font-size: 16px;
                color: #2D2D2D;
                line-height: 1.8;
                margin: 0 0 15px 0;
                font-family: 'Duran-Regular';
            }

            .thank-you-message p strong {
                font-family: 'Duran-Medium';
            }

            .memorable-text {
                font-size: 16px;
                color: #2D2D2D;
                margin: 30px 0;
                font-family: 'Duran-Regular';
            }

            .action-buttons {
                display: flex;
                gap: 15px;
                align-items: center;
                margin-top: 30px;
                justify-content: space-between;
            }

            .complete-btn {
                background: #EFCF4B;
                border: none;
                padding: 15px 100px;
                font-size: 20px;
                font-weight: bold;
                color: #2E2D2C;
                cursor: pointer;
                transition: all 0.3s;
                min-width: 44%;
                font-family: 'Duran-Medium';
                text-transform: uppercase;
                border-radius: 10px;
                letter-spacing: 1px;
                margin: 0 auto;
                box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, .8);
            }

            .complete-btn:hover {
                transform: translateY(-2px);
                text-decoration: none;
                background-color: #efcf4b;
            }

            @media (max-width: 768px) {
                .confirmacion-visita-container {
                    margin: 20px;
                }

                .content-section {
                    padding: 40px 30px;
                }

                .action-buttons {
                    flex-direction: column;
                    gap: 15px;
                }

                .complete-btn {
                    width: 100%;
                    padding: 15px 30px;
                }
            }
        </style>

        <div class="confirmacion-visita-container container">
            <button type="button" class="back-btn" onclick="goBackInicio()">
                <img src="https://autobusmedinaazahara.com/wp-content/uploads/2025/07/Vector-15.svg" alt="">
                VOLVER A INICIO
            </button>

            <div class="success-banner">
                ¡GRACIAS POR TU COMPRA!
            </div>

            <div class="content-section">
                <div class="todo-listo">
                    ¡Todo listo!
                </div>

                <div class="thank-you-message">
                    <p><strong>Gracias por confiar en Autocares BRAVO y en nuestros guías colaboradores para vivir Medina Azahara al completo.</strong> Ahora solo te queda relajarte, dejarte llevar y disfrutar de cada historia que emergerá entre sus columnas califales.</p>
                </div>

                <div class="memorable-text">
                    ¡Que tu recorrido sea tan memorable como la ciudad que vas a descubrir!
                </div>

                <div class="action-buttons">
                    <button class="complete-btn" onclick="viewVisitaTicket()">
                        VER COMPROBANTE
                    </button>
                    <button class="complete-btn" onclick="downloadVisitaTicket()">
                        DESCARGAR COMPROBANTE
                    </button>
                </div>
            </div>
        </div>

        <script>
            // Cargar datos de confirmación al cargar la página
            window.addEventListener('DOMContentLoaded', function() {
                console.log('=== PÁGINA DE CONFIRMACIÓN DE VISITA CARGADA ===');

                // Obtener localizador de la URL
                const urlParams = new URLSearchParams(window.location.search);
                const localizador = urlParams.get('localizador');

                console.log('Localizador desde URL:', localizador);

                if (localizador) {
                    // Guardar localizador globalmente para los botones
                    window.visitaLocalizador = localizador;
                    console.log('✅ Localizador guardado para usar en botones:', localizador);
                }
            });

            function goBackInicio() {
                const currentPath = window.location.pathname;
                let targetUrl;

                if (currentPath.includes('/')) {
                    const pathParts = currentPath.split('/').filter(part => part !== '');
                    if (pathParts.length > 0 && pathParts[0] !== 'confirmacion-reserva-visita') {
                        targetUrl = window.location.origin + '/' + pathParts[0] + '/';
                    } else {
                        targetUrl = window.location.origin + '/';
                    }
                } else {
                    targetUrl = window.location.origin + '/';
                }

                console.log('Redirigiendo a inicio:', targetUrl);
                window.location.href = targetUrl;
            }

            // Funciones placeholder para los botones (se implementarán en el siguiente paso)
            function viewVisitaTicket() {
                console.log('Ver comprobante - Localizador:', window.visitaLocalizador);
                alert('Función "Ver Comprobante" - Se implementará en el siguiente paso');
            }

            function downloadVisitaTicket() {
                console.log('Descargar comprobante - Localizador:', window.visitaLocalizador);
                alert('Función "Descargar Comprobante" - Se implementará en el siguiente paso');
            }
        </script>
<?php
        return ob_get_clean();
    }

    /**
     * Procesar reserva de visita (SIN TPV por ahora)
     */
    public function process_visita_reservation()
    {
        header('Content-Type: application/json');

        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
                wp_send_json_error('Error de seguridad');
                return;
            }

            // Obtener datos
            $service_id = intval($_POST['service_id']);
            $fecha = sanitize_text_field($_POST['fecha']);
            $hora = sanitize_text_field($_POST['hora']);
            $adultos = intval($_POST['adultos']);
            $ninos = intval($_POST['ninos']);
            $ninos_menores = intval($_POST['ninos_menores']); // ✅ NUEVO
            $nombre = sanitize_text_field($_POST['nombre']);
            $apellidos = sanitize_text_field($_POST['apellidos']);
            $email = sanitize_email($_POST['email']);
            $telefono = sanitize_text_field($_POST['telefono']);

            // Validaciones básicas
            if ($adultos < 1) {
                wp_send_json_error('Debe haber al menos un adulto');
                return;
            }

            if (!is_email($email)) {
                wp_send_json_error('Email no válido');
                return;
            }

            // Obtener datos del servicio
            global $wpdb;
            $table_services = $wpdb->prefix . 'reservas_agency_services';

            $servicio = $wpdb->get_row($wpdb->prepare(
                "SELECT s.*, a.agency_name, a.email as agency_email
             FROM $table_services s
             INNER JOIN {$wpdb->prefix}reservas_agencies a ON s.agency_id = a.id
             WHERE s.id = %d AND s.servicio_activo = 1",
                $service_id
            ));

            if (!$servicio) {
                wp_send_json_error('Servicio no encontrado');
                return;
            }

            // Calcular precio total
            $total_personas = $adultos + $ninos + $ninos_menores; // ✅ MODIFICADO
            $precio_total = ($adultos * floatval($servicio->precio_adulto)) +
                ($ninos * floatval($servicio->precio_nino)) +
                ($ninos_menores * floatval($servicio->precio_nino_menor)); // ✅ NUEVO

            // Generar localizador
            if (!class_exists('ReservasReservas')) {
                require_once RESERVAS_PLUGIN_PATH . 'includes/class-reservas.php';
            }
            $localizador = ReservasReservas::generate_localizador();

            // Crear reserva de visita
            $table_visitas = $wpdb->prefix . 'reservas_visitas';

            $insert_data = array(
                'localizador' => $localizador,
                'service_id' => $service_id,
                'agency_id' => $servicio->agency_id,
                'fecha' => $fecha,
                'hora' => $hora,
                'nombre' => $nombre,
                'apellidos' => $apellidos,
                'email' => $email,
                'telefono' => $telefono,
                'adultos' => $adultos,
                'ninos' => $ninos,
                'ninos_menores' => $ninos_menores, // ✅ NUEVO
                'total_personas' => $total_personas,
                'precio_total' => $precio_total,
                'estado' => 'confirmada',
                'metodo_pago' => 'pendiente_tpv',
                'created_at' => current_time('mysql')
            );

            $result = $wpdb->insert($table_visitas, $insert_data);

            if ($result === false) {
                wp_send_json_error('Error guardando la reserva: ' . $wpdb->last_error);
                return;
            }

            $reserva_id = $wpdb->insert_id;

            // ✅ CONSTRUIR URL DE REDIRECCIÓN DINÁMICAMENTE
            $current_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : home_url();
            $parsed_url = parse_url($current_url);
            $path_parts = explode('/', trim($parsed_url['path'], '/'));

            // Si hay subdirectorio (primer segmento de la ruta)
            if (count($path_parts) > 0 && !empty($path_parts[0]) && $path_parts[0] !== 'confirmacion-reserva-visita') {
                $redirect_url = home_url('/' . $path_parts[0] . '/confirmacion-reserva-visita/?localizador=' . $localizador);
            } else {
                $redirect_url = home_url('/confirmacion-reserva-visita/?localizador=' . $localizador);
            }

            wp_send_json_success(array(
                'mensaje' => 'Reserva procesada correctamente',
                'redirect_url' => $redirect_url,
                'localizador' => $localizador,
                'reserva_id' => $reserva_id
            ));
        } catch (Exception $e) {
            error_log('ERROR procesando reserva visita: ' . $e->getMessage());
            wp_send_json_error('Error interno: ' . $e->getMessage());
        }
    }
}
