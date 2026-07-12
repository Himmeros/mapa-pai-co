<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cargar Leaflet solo cuando estamos editando un CPT habilitado
function mapa_pai_enqueue_admin_scripts($hook) {
    global $post;
    
    // Obtener tipos habilitados
    $cpts_habilitados = get_option('mapa_pai_cpts_habilitados', array());
    $todos_los_cpts = array_unique(array_merge(array('mapa_pai_cliente'), $cpts_habilitados));

    // Determinar el post type actual (funciona en editar y nuevo post)
    $current_post_type = '';
    if ($hook == 'post.php') {
        $current_post_type = get_post_type($post);
    } elseif ($hook == 'post-new.php') {
        $current_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';
    }

    if ( in_array($current_post_type, $todos_los_cpts) ) {
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), null, true);
    }
}
add_action('admin_enqueue_scripts', 'mapa_pai_enqueue_admin_scripts');

// 2. Registrar el Custom Post Type "Cliente"
function mapa_pai_registrar_cpt_cliente() {
    $args = array(
        'public'       => true,
        'label'        => 'Clientes PAI',
        'menu_icon'    => 'dashicons-location',
        'supports'     => array( 'title', 'editor', 'revisions' ),
        'has_archive'  => true,
        'show_in_rest' => true,
    );
    register_post_type( 'mapa_pai_cliente', $args );
}
add_action( 'init', 'mapa_pai_registrar_cpt_cliente' );

// 3. Inyectar la caja de coordenadas en TODOS los CPTs habilitados
function mapa_pai_agregar_metabox_coordenadas() {
    $cpts_habilitados = get_option('mapa_pai_cpts_habilitados', array());
    $todos_los_cpts = array_unique(array_merge(array('mapa_pai_cliente'), $cpts_habilitados));

    foreach ( $todos_los_cpts as $cpt ) {
        add_meta_box(
            'mapa_pai_coordenadas_box',
            'Ubicación Geográfica (Mapa PAI Co.)',
            'mapa_pai_coordenadas_callback',
            $cpt,
            'normal', 
            'high'    
        );
    }
}
add_action( 'add_meta_boxes', 'mapa_pai_agregar_metabox_coordenadas' );

// 4. Interfaz con Mapa Leaflet (Actualizada con Botón de Validación)
function mapa_pai_coordenadas_callback( $post ) {
    wp_nonce_field( 'guardar_mapa_pai_coordenadas', 'mapa_pai_coordenadas_nonce' );
    
    $lat = get_post_meta( $post->ID, '_mapa_pai_latitud', true ) ?: '10.4806';
    $lon = get_post_meta( $post->ID, '_mapa_pai_longitud', true ) ?: '-66.9036';
    $dir = get_post_meta( $post->ID, '_mapa_pai_direccion', true );
    ?>
    
    <div id="mapa-pai-admin" style="height: 300px; width: 100%; margin-bottom: 15px; border: 1px solid #ccc;"></div>

    <!-- Fila de campos (sin el botón) -->
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <div style="flex: 2;"><label>Dirección:</label><input type="text" id="mapa_pai_direccion" name="mapa_pai_direccion" value="<?php echo esc_attr($dir); ?>" style="width: 100%;" /></div>
        <div style="flex: 1;"><label>Latitud:</label><input type="text" id="mapa_pai_latitud" name="mapa_pai_latitud" value="<?php echo esc_attr($lat); ?>" style="width: 100%;" /></div>
        <div style="flex: 1;"><label>Longitud:</label><input type="text" id="mapa_pai_longitud" name="mapa_pai_longitud" value="<?php echo esc_attr($lon); ?>" style="width: 100%;" /></div>
    </div>

    <!-- Fila independiente para el botón -->
    <div style="margin-top: 10px;">
        <button type="button" id="btn-validar-dir" class="button button-secondary">Validar Dirección</button>
        <span id="msg-validar" style="margin-left: 10px; font-size: 12px; color: #666;"></span>
    </div>

    <script>
        
    document.addEventListener('DOMContentLoaded', function() {
        var latInput = document.getElementById('mapa_pai_latitud');
        var lonInput = document.getElementById('mapa_pai_longitud');
        var dirInput = document.getElementById('mapa_pai_direccion');
        var msgSpan = document.getElementById('msg-validar');
        var btnValidar = document.getElementById('btn-validar-dir');
        
        var map = L.map('mapa-pai-admin').setView([<?php echo $lat; ?>, <?php echo $lon; ?>], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marker = L.marker([<?php echo $lat; ?>, <?php echo $lon; ?>], {draggable: true}).addTo(map);

        // Lógica de validación
        btnValidar.addEventListener('click', function() {
            var direccion = dirInput.value;
            if (!direccion) { alert('Escribe una dirección primero'); return; }
            
            msgSpan.innerText = 'Buscando...';
            
            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(direccion))
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        var lat = parseFloat(data[0].lat);
                        var lon = parseFloat(data[0].lon);
                        
                        latInput.value = lat.toFixed(6);
                        lonInput.value = lon.toFixed(6);
                        
                        var newLatLng = [lat, lon];
                        marker.setLatLng(newLatLng);
                        map.setView(newLatLng, 16);
                        
                        msgSpan.innerText = '✅ Ubicación encontrada';
                    } else {
                        msgSpan.innerText = '❌ No encontrada';
                    }
                })
                .catch(err => {
                    msgSpan.innerText = 'Error de conexión';
                    console.error(err);
                });
        });

        // Eventos existentes
        marker.on('dragend', function(e) {
            var pos = marker.getLatLng();
            latInput.value = pos.lat.toFixed(6);
            lonInput.value = pos.lng.toFixed(6);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            latInput.value = e.latlng.lat.toFixed(6);
            lonInput.value = e.latlng.lng.toFixed(6);
        });
    });
    </script>
    <?php
}

// 5. Guardar datos
function mapa_pai_guardar_coordenadas( $post_id ) {
    if ( ! isset( $_POST['mapa_pai_coordenadas_nonce'] ) || ! wp_verify_nonce( $_POST['mapa_pai_coordenadas_nonce'], 'guardar_mapa_pai_coordenadas' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['mapa_pai_direccion'] ) ) update_post_meta( $post_id, '_mapa_pai_direccion', sanitize_text_field( $_POST['mapa_pai_direccion'] ) );
    if ( isset( $_POST['mapa_pai_latitud'] ) ) update_post_meta( $post_id, '_mapa_pai_latitud', sanitize_text_field( $_POST['mapa_pai_latitud'] ) );
    if ( isset( $_POST['mapa_pai_longitud'] ) ) update_post_meta( $post_id, '_mapa_pai_longitud', sanitize_text_field( $_POST['mapa_pai_longitud'] ) );
}
add_action( 'save_post', 'mapa_pai_guardar_coordenadas' );

// ==========================================
// 6. SELECTOR DE COLOR PARA CATEGORÍAS
// ==========================================
function mapa_pai_add_color_field() {
    ?>
    <div class="form-field">
        <label for="mapa_pai_color">Color del Pin en el Mapa</label>
        <input type="color" name="mapa_pai_color" id="mapa_pai_color" value="#2196F3" style="max-width: 80px; height: 35px; cursor: pointer;">
        <p>Elige el color que tendrá esta categoría en el mapa.</p>
    </div>
    <?php
}
add_action('job_listing_category_add_form_fields', 'mapa_pai_add_color_field');
add_action('category_add_form_fields', 'mapa_pai_add_color_field');

function mapa_pai_edit_color_field($term) {
    $color = get_term_meta($term->term_id, 'mapa_pai_color', true) ?: '#2196F3';
    ?>
    <tr class="form-field">
        <th scope="row"><label for="mapa_pai_color">Color del Pin en el Mapa</label></th>
        <td>
            <input type="color" name="mapa_pai_color" id="mapa_pai_color" value="<?php echo esc_attr($color); ?>" style="max-width: 80px; height: 35px; cursor: pointer;">
            <p class="description">Elige el color que tendrá esta categoría en el mapa.</p>
        </td>
    </tr>
    <?php
}
add_action('job_listing_category_edit_form_fields', 'mapa_pai_edit_color_field');
add_action('category_edit_form_fields', 'mapa_pai_edit_color_field');

function mapa_pai_save_color_field($term_id) {
    if (isset($_POST['mapa_pai_color'])) {
        update_term_meta($term_id, 'mapa_pai_color', sanitize_text_field($_POST['mapa_pai_color']));
    }
}
add_action('created_job_listing_category', 'mapa_pai_save_color_field');
add_action('edited_job_listing_category', 'mapa_pai_save_color_field');
add_action('created_category', 'mapa_pai_save_color_field');
add_action('edited_category', 'mapa_pai_save_color_field');