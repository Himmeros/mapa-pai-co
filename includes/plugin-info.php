<?php
// Archivo: includes/plugin-info.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'plugins_api', 'mapa_pai_info_plugin', 9999, 3 );

function mapa_pai_info_plugin( $result, $action, $args ) {
    if ( 'plugin_information' !== $action || strpos( $args->slug, 'mapa-pai-co' ) === false ) {
        return $result;
    }

    $plugin_info = is_object( $result ) ? $result : new stdClass();
    
    if ( empty( $plugin_info->name ) ) $plugin_info->name = 'Mapa PAI Co. 🇨🇴🇻🇪';
    if ( empty( $plugin_info->version ) ) $plugin_info->version = '1.1.4';
    if ( empty( $plugin_info->author ) ) $plugin_info->author = 'Proyectos Himmeros';

    if ( ! isset( $plugin_info->sections ) || ! is_array( $plugin_info->sections ) ) {
        $plugin_info->sections = array();
    }

    $plugin_info->sections['description'] = '
        
        <h3>Mapa PAI Co.</h3>
        <p>Directorio interactivo de ubicaciones y clientes diseñado a la medida, gestionado desde GitHub.</p>
        <hr>
        <h3>Características ::</h3>
        <ul>
            <li>Se utiliza la conexión con Leaflet, por lo tanto no se tiene que instalar nada en el servidor.</li>
            <li>También se validan las direcciones, tanto visualmente en el mapa como con coordenadas reales.</li>
        </ul>
        <br>

        <!-- Bloque de Información Destacada -->
        <div style="background: #f0f0f1; padding: 15px; border-left: 4px solid #2271b1; margin-bottom: 20px;">
            <p style="margin: 0 0 5px 0;"><strong>PAI Co. 🇨🇴</strong><br>
            <a href="https://paginasamarillasinternet.com" target="_blank">paginasamarillasinternet.com</a></p>
            
            <p style="margin: 10px 0 0 0;"><strong>PAI Ve. 🇻🇪</strong><br>
            <a href="https://paginasamarillasinternet.net" target="_blank">paginasamarillasinternet.net</a></p>
        </div>
    ';
    
    $plugin_info->sections['changelog']   = '<h4>v1.1.5</h4><ul><li>Ajustes de conexión con la API de actualizaciones.</li></ul>';

    return $plugin_info;
}