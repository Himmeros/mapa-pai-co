<?php
/**
 * Plugin Name: Mapa PAI Co.
 * Plugin URI:  https://github.com/Himmeros/mapa-pai-co/
 * Description: Sistema de directorio y gestión de clientes mediante atajos, creado para Páginas Amarillas en Internet.
 * Version:     1.1.1
 * Author:      Proyectos Himmeros
 * Text Domain: himmeros.xyz
 */


// Control de actualizaciones

// Incluye la librería
require 'lib/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Configura la conexión con tu repositorio de GitHub
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/Himmeros/mapa-pai-co', // La URL de tu repo
    __FILE__, // Ruta al archivo principal del plugin
    'mapa-pai-co' // El slug (nombre único) de tu plugin    
);

// Agrega esta línea: le dice a la librería dónde buscar la info
$myUpdateChecker->addReadme('readme.txt');

// Ajueste de búsqueda de actualizaciones

add_filter( 'plugins_api', 'mi_plugin_info_handler', 10, 3 );

function mi_plugin_info_handler( $result, $action, $args ) {
    // Solo actuamos si el plugin que buscan es el tuyo
    if ( 'plugin_information' !== $action || 'mapa-pai-co' !== $args->slug ) {
        return $result;
    }

    // Aquí defines la información que quieres mostrar
    $plugin_info = new stdClass();
    $plugin_info->name = 'Mapa PAI Co.';
    $plugin_info->version = '1.0.0'; // Deberías poner la versión actual aquí
    $plugin_info->author = 'Tu Nombre/Empresa';
    $plugin_info->sections = array(
        'description' => 'Este es el plugin Mapa PAI Co. gestionado desde GitHub.',
        'changelog'   => 'Aquí podrías cargar dinámicamente tu archivo CHANGELOG.md de GitHub.'
    );

    return $plugin_info;
}

// Esto ayuda a WordPress a relacionar la actualización con la carpeta actual
add_filter('upgrader_source_selection', function($source, $remote_source, $upgrader) {
    // 1. Limpiamos la ruta de cualquier barra final para no romper dirname()
    $source_clean = untrailingslashit($source);
    
    // 2. Solo aplicamos el cambio si la carpeta descargada pertenece a nuestro plugin
    if (stripos($source_clean, 'mapa-pai-co') === false) {
        return $source;
    }

    // 3. Construimos las rutas correctas
    $desired_folder_name = 'mapa-pai-co'; 
    $source_base = dirname($source_clean);
    $new_source = $source_base . '/' . $desired_folder_name;

    // 4. Renombramos si es necesario y devolvemos con la barra final que exige WordPress
    if ($source_clean !== $new_source) {
        rename($source_clean, $new_source);
        return trailingslashit($new_source);
    }

    return trailingslashit($source_clean);
}, 10, 3);


// Bloquear acceso directo

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes de rutas
define('MAPA_PAI_RUTA', plugin_dir_path(__FILE__));

// Cargar módulos
require_once MAPA_PAI_RUTA . 'includes/cpt-cliente.php';
require_once MAPA_PAI_RUTA . 'admin/menu-admin.php';
require_once MAPA_PAI_RUTA . 'includes/shortcodes.php';

// Cargar scripts y estilos de Leaflet
function mapa_pai_cargar_leaflet()
{
    // Solo cargar si estamos en el front-end
    if (!is_admin()) {
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'mapa_pai_cargar_leaflet');

function mapa_pai_cargar_estilos_publicos()
{
    // Registra y carga el archivo CSS que está en la carpeta 'css'
    wp_enqueue_style(
        'mapa-pai-estilos',
        plugin_dir_url(__FILE__) . 'css/mapa-estilos.css',
        array(),
        '1.0',
        'all'
    );
}
// El gancho 'wp_enqueue_scripts' es para cargar cosas en la parte pública (el frontend)
add_action('wp_enqueue_scripts', 'mapa_pai_cargar_estilos_publicos');