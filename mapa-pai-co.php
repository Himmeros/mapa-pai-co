<?php
/**
 * Plugin Name: Mapa PAI Co.
 * Plugin URI:  https://github.com/Himmeros/mapa-pai-co/
 * Description: Sistema de directorio y gestión de clientes mediante atajos, creado para Páginas Amarillas en Internet.
 * Version:     1.1.7
 * Author:      Proyectos Himmeros
 * Text Domain: himmeros.xyz
 */


// Control de actualizaciones

// Incluye la librería
require 'lib/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// 1. Configura la conexión con tu repositorio de GitHub
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/Himmeros/mapa-pai-co', // La URL de tu repo
    __FILE__, // Ruta al archivo principal del plugin
    'mapa-pai-co' // El slug (nombre único) de tu plugin    
);

// 2. Cargar el módulo de la ventana de detalles
require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-info.php';

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