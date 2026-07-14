<?php
// Archivo: includes/map-layout.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve el bloque HTML y CSS de la estructura principal (Sidebar + Mapa)
 */
function mapa_pai_obtener_layout_directorio( $dimensiones, $color_sidebar ) {
    $html = '
    <style>
        .mapa-pai-wrapper { display: flex; gap: 15px; width: ' . esc_attr($dimensiones['width']) . '; max-width: 100%; height: ' . esc_attr($dimensiones['height']) . '; }
        .mapa-pai-sidebar { width: 280px; background-color: ' . esc_attr($color_sidebar) . '; color: #fff; border: 2px solid #333; border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; }
        .mapa-pai-sidebar-header { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); background-color: rgba(0,0,0,0.2); }
        .mapa-pai-sidebar-header h3 { margin: 0 0 10px 0; color: #fff; font-size: 16px; }
        .mapa-pai-buscador-input { width: 100%; padding: 8px 12px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.2); background-color: rgba(0,0,0,0.3); color: #fff; font-size: 13px; box-sizing: border-box; transition: border-color 0.2s; }
        .mapa-pai-buscador-input:focus { outline: none; border-color: #2196F3; }
        .mapa-pai-buscador-input::placeholder { color: #ccc; }
        .mapa-pai-sidebar-lista { flex: 1; overflow-y: auto; list-style: none !important; list-style-type: none !important; padding: 0 !important; margin: 0 !important; }
        .mapa-pai-item-cliente { padding: 12px 15px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; transition: background 0.2s; list-style: none !important; list-style-type: none !important; margin: 0 !important; }
        .mapa-pai-item-cliente:hover { background-color: rgba(255,255,255,0.1); }
        
        .mapa-pai-item-seleccionado { background-color: rgba(255, 255, 255, 0.15) !important; border-left: 4px solid #fff !important; }
        
        .mapa-pai-item-nombre { font-weight: bold; font-size: 14px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        .mapa-pai-item-color { width: 12px; height: 12px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
        .mapa-pai-item-direccion { font-size: 12px; color: #ccc; line-height: 1.3; }
        .mapa-pai-mapa-container { flex: 1; border: 2px solid #333; border-radius: 8px; height: 100%; z-index: 1; }
        .custom-gps-icon, .custom-pin-icon { background: transparent; border: none; }
        .burbuja-gps-contenedor { position: relative; width: 20px; height: 20px; }
        .burbuja-gps-centro { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 14px; height: 14px; background-color: #2196F3; border: 2px solid white; border-radius: 50%; box-shadow: 0 1px 4px rgba(0,0,0,0.5); z-index: 2; }
        .burbuja-gps-onda { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; background-color: rgba(33, 150, 243, 0.4); border-radius: 50%; z-index: 1; animation: pulsar-gps 1.5s infinite ease-out; }
        @keyframes pulsar-gps { 0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; } 100% { transform: translate(-50%, -50%) scale(2.5); opacity: 0; } }
        .contenedor-filtros-scroll { max-height: 250px; overflow-y: auto; padding-right: 10px; }
        
        .custom-pin-icon svg { transform-origin: bottom center; transition: transform 0.2s ease; filter: drop-shadow(2px 4px 6px rgba(0,0,0,0.3)); }
        .custom-pin-icon:hover svg { transform: scale(1.1) translateY(-2px); }

        @keyframes pinBrincoEfecto {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-14px) scale(1.05); }
        }
        .pin-brincando svg {
            animation: pinBrincoEfecto 0.5s ease infinite;
        }

        @media (max-width: 768px) {
            .mapa-pai-wrapper { flex-direction: column; height: auto !important; display: flex; }
            .mapa-pai-sidebar { width: 100%; height: 280px; }
            .mapa-pai-mapa-container { width: 100%; height: 60vh; min-height: 400px; display: block; }
        }
    </style>

    <div class="mapa-pai-wrapper">
        <div class="mapa-pai-sidebar">
            <div class="mapa-pai-sidebar-header">
                <h3>Directorio de Clientes</h3>
                <input type="text" id="mapa-pai-buscador" class="mapa-pai-buscador-input" placeholder="Buscar nombre o dirección...">
            </div>
            <ul id="mapa-pai-sidebar-lista" class="mapa-pai-sidebar-lista"></ul>
        </div>
        <div id="mapa-directorio-pai" class="mapa-pai-mapa-container"></div>
    </div>';

    return $html;
}