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

    /**
     * Renderizar página de detalles de reserva de visita
     */
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


            <!-- Detalles de la reserva -->
            <div class="visita-details-section">
                <h2>DETALLES DE LA RESERVA DE VISITA GUIADA</h2>

                <div class="details-grid">
                    <!-- Columna izquierda: Fechas y personas -->
                    <div class="details-column">
                        <div class="details-card">
                            <h3>FECHAS Y HORAS</h3>
                            <div class="detail-row">
                                <span class="label">FECHA VISITA:</span>
                                <span class="value" id="fecha-visita">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">HORA INICIO:</span>
                                <span class="value" id="hora-inicio">-</span>
                            </div>
                        </div>

                        <div class="details-card">
                            <h3>SELECCIONA LAS PERSONAS</h3>
                            <div class="person-selector">
                                <label>ADULTOS</label>
                                <input type="number" id="adultos-visita" min="1" max="999" value="1" class="person-input">
                                <span class="price-per-person" id="precio-adulto-display">0€/persona</span>
                            </div>

                            <div class="person-selector">
                                <label>NIÑOS (5/12 AÑOS)</label>
                                <input type="number" id="ninos-visita" min="0" max="999" value="0" class="person-input">
                                <span class="price-per-person" id="precio-nino-display">0€/persona</span>
                            </div>

                            <div class="total-price-visita">
                                <div class="total-row">
                                    <span class="label">TOTAL:</span>
                                    <span class="value" id="total-visita">0€</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha: Datos personales -->
                    <div class="details-column">
                        <div class="details-card">
                            <h3>DATOS PERSONALES</h3>
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
                        COMPLETAR RESERVA
                    </button>
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
        <div class="confirmacion-visita-container container">
            <button type="button" class="back-btn" onclick="goBackToInicio()">
                <img src="https://autobusmedinaazahara.com/wp-content/uploads/2025/07/Vector-15.svg" alt="">
                VOLVER AL INICIO
            </button>

            <div class="success-banner">
                <h1>¡RESERVA DE VISITA GUIADA CONFIRMADA!</h1>
            </div>

            <div class="content-section">
                <div class="thank-you-message">
                    <p>Gracias por reservar tu <strong>visita guiada a Medina Azahara</strong>.</p>
                    <p>Recibirás un email de confirmación con todos los detalles.</p>
                </div>

                <div class="confirmacion-details" id="confirmacion-details">
                    <h3>DETALLES DE TU RESERVA</h3>
                    <div class="detail-row">
                        <span class="label">LOCALIZADOR:</span>
                        <span class="value" id="conf-localizador">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">FECHA:</span>
                        <span class="value" id="conf-fecha">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">HORA:</span>
                        <span class="value" id="conf-hora">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">PERSONAS:</span>
                        <span class="value" id="conf-personas">-</span>
                    </div>
                    <div class="detail-row total">
                        <span class="label">TOTAL PAGADO:</span>
                        <span class="value" id="conf-total">-</span>
                    </div>
                </div>

                <div class="remember-text">
                    Te esperamos para disfrutar de esta increíble experiencia.
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
            $total_personas = $adultos + $ninos;
            $precio_total = ($adultos * floatval($servicio->precio_adulto)) +
                ($ninos * floatval($servicio->precio_nino));

            // Generar localizador
            if (!class_exists('ReservasReservas')) {
                require_once RESERVAS_PLUGIN_PATH . 'includes/class-reservas.php';
            }
            $localizador = ReservasReservas::generate_localizador();

            // Crear reserva de visita (tabla temporal o nueva)
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

            // TODO: Aquí irá la integración con TPV
            // Por ahora, simplemente confirmamos la reserva

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
