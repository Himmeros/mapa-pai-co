<?php
// Archivo: includes/plugin-info.php

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --- INFORMACIÓN DEL PLUGIN (Modal de detalles) ---
add_filter( 'plugins_api', 'mapa_pai_info_plugin', 9999, 3 );

function mapa_pai_info_plugin( $result, $action, $args ) {
    if ( 'plugin_information' !== $action || strpos( $args->slug, 'mapa-pai-co' ) === false ) {
        return $result;
    }

    $plugin_info = is_object( $result ) ? $result : new stdClass();
    
    if ( ! isset( $plugin_info->sections ) || ! is_array( $plugin_info->sections ) ) {
        $plugin_info->sections = array();
    }

    $plugin_info->sections['description'] = '<h3>Mapa PAI Co.</h3><p>Directorio interactivo de ubicaciones y clientes diseñado a la medida, gestionado desde GitHub.</p>';
    $plugin_info->sections['changelog']   = '<h4>v1.1.4</h4><ul><li>Ajustes de conexión con la API de actualizaciones.</li></ul>';

    return $plugin_info;
}