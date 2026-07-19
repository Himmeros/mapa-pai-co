<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. REGISTRO DEL MENÚ
function mapa_pai_menu_administrador() {
    $icono_paico = plugins_url( '../img/logo_paico_20x20.png', __FILE__ );

    add_menu_page(
        'Mapa PAI Co. Instrucciones', 
        'Mapa PAI Co.',                
        'manage_options',              
        'mapa-pai-config',            
        'mapa_pai_vista_admin',        
        $icono_paico,                  
        25                            
    );
    
    // Submenú Detalles
    add_submenu_page(
        'mapa-pai-config', 
        'Detalles del Sistema', 
        '01 Detalles', 
        'manage_options', 
        'mapa-pai-detalles', 
        'mapa_pai_vista_detalles' 
    );

    // Submenú Configuración
    add_submenu_page(
        'mapa-pai-config',             
        'Configuración de CPTs',       
        '02 Configuración',                
        'manage_options',              
        'mapa-pai-ajustes',            
        'mapa_pai_vista_configuracion' 
    );
}
add_action( 'admin_menu', 'mapa_pai_menu_administrador' );

// 2. VISTA PRINCIPAL: INSTRUCCIONES
function mapa_pai_vista_admin() {
    ?>
    <div class="wrap">
        <h1>Configuración de Mapa PAI Co.</h1>
        <p>Bienvenido al panel de control desarrollado por <strong>Proyectos Himmeros</strong>.</p>
        <hr class="wp-header-end">
        <div style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 320px; background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
                <h2 style="margin-top: 0;">1. Cortocircuito (Shortcode) Cliente</h2>
                <p>Uso: <code>[mapa_pai_cliente id="ID"]</code></p>
                <p>Se puede dejar sin el ID para que tome la referencia del post automáticamente.</p>
            </div>
            <div style="flex: 1; min-width: 320px; background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px;">
                <h2 style="margin-top: 0;">2. Cortocircuito (Shortcode) Directorio</h2>
                <p>Uso: <code>[mapa_pai_directorio]</code></p>
            </div>
        </div>
    </div>
    <?php
}

// 3. VISTA DE CONFIGURACIÓN (SELECTOR DE CPTS, DIMENSIONES, CAPAS Y COLOR)
function mapa_pai_vista_configuracion() {
    if ( isset($_POST['mapa_pai_guardar_settings']) && check_admin_referer('mapa_pai_nonce_action', 'mapa_pai_nonce_field') ) {
        
        // Guardar CPTs
        $cpts_seleccionados = isset($_POST['mapa_pai_cpts']) ? array_map('sanitize_text_field', $_POST['mapa_pai_cpts']) : array();
        update_option('mapa_pai_cpts_habilitados', $cpts_seleccionados);
        
        // Guardar Dimensiones
        $dimensiones = array(
            'width'  => sanitize_text_field($_POST['mapa_width']),
            'height' => sanitize_text_field($_POST['mapa_height'])
        );
        update_option('mapa_pai_dimensiones', $dimensiones);

        // Guardar la Capa del Mapa
        $capa_seleccionada = sanitize_text_field($_POST['mapa_capa']);
        update_option('mapa_pai_capa_estilo', $capa_seleccionada);
        
        // Guardar el Color del Directorio
        $color_directorio = sanitize_text_field($_POST['mapa_color_directorio']);
        update_option('mapa_pai_color_directorio', $color_directorio);

        // NUEVO: Guardar el Color del Texto
        $color_texto = sanitize_text_field($_POST['mapa_color_texto']);
        update_option('mapa_pai_color_texto', $color_texto);
        
        echo '<div class="updated notice is-dismissible"><p><strong>Configuración guardada correctamente.</strong></p></div>';
    }

    // Cargar opciones actuales
    $opciones_activas = get_option('mapa_pai_cpts_habilitados', array());
    $dimensiones      = get_option('mapa_pai_dimensiones', array('width' => '100%', 'height' => '500px'));
    $capa_actual      = get_option('mapa_pai_capa_estilo', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
    
    // Cargar color actual de fondo
    $color_actual = get_option('mapa_pai_color_directorio', '#1e1e1e');

    // NUEVO: Cargar color actual de texto (por defecto blanco #ffffff)
    $color_texto_actual = get_option('mapa_pai_color_texto', '#ffffff');

    $cpts_disponibles = array(
        'page'        => 'Páginas',
        'post'        => 'Entradas',
        'job_listing' => 'Listados de WP Job Manager',
        'product'     => 'WooCommerce (Productos)'
    );

    $capas_disponibles = array(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png' => 'OpenStreetMap (Estándar)',
        'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png' => 'CartoDB (Claro / Minimalista)',
        'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png' => 'CartoDB (Oscuro)',
        'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png' => 'OpenTopoMap (Topográfico)'
    );
    ?>
    <div class="wrap">
        <h1>Configuración de Campos, Tamaños y Estilos</h1>
        <hr class="wp-header-end">

        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 4px; margin-top: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <form method="post" action="">
                <?php wp_nonce_field('mapa_pai_nonce_action', 'mapa_pai_nonce_field'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row" style="width: 200px;">Mostrar campos en:</th>
                        <td>
                            <?php foreach ( $cpts_disponibles as $slug => $nombre ) : ?>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="mapa_pai_cpts[]" value="<?php echo esc_attr($slug); ?>" <?php echo in_array($slug, $opciones_activas) ? 'checked' : ''; ?>>
                                    <?php echo esc_html($nombre); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Estilo del Mapa (Capa)</th>
                        <td>
                            <select name="mapa_capa" style="width: 100%; max-width: 300px;">
                                <?php foreach ( $capas_disponibles as $url => $nombre ) : ?>
                                    <option value="<?php echo esc_attr($url); ?>" <?php selected($capa_actual, $url); ?>>
                                        <?php echo esc_html($nombre); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Elige la apariencia visual de los mapas que verán los usuarios.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Color de Fondo del Directorio</th>
                        <td>
                            <input type="color" name="mapa_color_directorio" value="<?php echo esc_attr($color_actual); ?>">
                            <p class="description">Selecciona el color de fondo para la columna "Directorio de Clientes".</p>
                        </td>
                    </tr>

                    <!-- NUEVO: Fila para seleccionar el color del texto -->
                    <tr>
                        <th scope="row">Color del Texto del Directorio</th>
                        <td>
                            <input type="color" name="mapa_color_texto" value="<?php echo esc_attr($color_texto_actual); ?>">
                            <p class="description">Selecciona el color del texto y los iconos dentro del directorio (recomendado: un color que contraste con el fondo).</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Ancho del mapa</th>
                        <td><input type="text" name="mapa_width" value="<?php echo esc_attr($dimensiones['width']); ?>" style="width: 100%; max-width: 300px;"></td>
                    </tr>
                    <tr>
                        <th scope="row">Alto del mapa</th>
                        <td><input type="text" name="mapa_height" value="<?php echo esc_attr($dimensiones['height']); ?>" style="width: 100%; max-width: 300px;"></td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="mapa_pai_guardar_settings" class="button button-primary button-large" value="Guardar Cambios">
                </p>
            </form>
        </div>
    </div>
    <?php
}
// ==========================================
// 4. VISTA DE LA PÁGINA "DETALLES"
// ==========================================
function mapa_pai_vista_detalles() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // Ruta de la bandera apuntando a la carpeta img
    $ruta_bandera = plugins_url( '../img/colovene.png', __FILE__ );
    $ruta_himmeros = plugins_url( '../img/himmeros.png', __FILE__ );
    ?>
    <div class="wrap">
        <!-- PRIMER BLOQUE: Título y Bandera -->
        
        <div style="display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px;">
            
            <h2 style="margin: 0; font-size: 24px;">
                <span style="color: #F5C527;">Páginas</span> 
                <span style="color: #F5C527;">Ama</span><span style="color: #131AA1;">rillas</span> 
                <span style="color: #131AA1;">en</span> 
                <span style="color: #7D150B;">Internet</span>
            </h2>
            
            <img src="<?php echo esc_url( $ruta_bandera ); ?>" alt="Bandera Colovene" style="height: 60px; width: auto;">
            
        </div>
        
        <!-- SEGUNDO BLOQUE: Información del Desarrollador y Logo -->
        <div style="display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-top: 20px;">
            
            <!-- Columna Izquierda: Textos -->
            <div style="flex: 1; padding-right: 20px;">
                <p style="font-size: 16px; margin-top: 0; color: #3c434a;">
                    Desarrollado por <strong>Proyectos Himmeros</strong>, Caracas - Venezuela, para uso exclusivo de PAI Co., en constante evolución, sistemas que funcionan .
                </p>
                <p style="font-size: 15px; line-height: 1.8; margin-bottom: 0; color: #50575e;">
                    <strong>Correo ::</strong> <a href="mailto:admhimmeros@gmail.com" style="text-decoration: none;">admhimmeros@gmail.com</a><br>
                    <strong>WhatsApp ::</strong> <a href="https://wa.me/584129998075" target="_blank" style="text-decoration: none;">+584129998075</a><br>
                    <strong>Web ::</strong> <a href="https://himmeros.xyz" target="_blank" style="text-decoration: none;">himmeros.xyz</a>
                </p>
            </div>

            <!-- Columna Derecha: Imagen Himmeros -->
            <div>
                <img src="<?php echo esc_url( $ruta_himmeros ); ?>" alt="Proyectos Himmeros" style="max-height: 90px; width: auto;">
            </div>
            
        </div>
        
    </div>
    <?php
}