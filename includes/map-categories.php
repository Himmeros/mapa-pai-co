<?php
// Archivo: includes/map-categories.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Devuelve el bloque JavaScript para renderizar la caja de categorías dinámica
 */
function mapa_pai_obtener_js_categorias( $color_fondo ) {
    return '
        var controlCategorias = L.control({ position: "topright" });
        controlCategorias.onAdd = function (map) {
            var div = L.DomUtil.create("div", "mapa-pai-leyenda-categorias");
            div.style.backgroundColor = "' . esc_js( $color_fondo ) . '";
            div.style.color = "#fff";
            div.style.padding = "15px";
            div.style.borderRadius = "5px";
            div.style.marginTop = "10px"; 
            div.innerHTML = "<h4 style=\'margin-top:0; color:#fff; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 5px; margin-bottom: 10px;\'>Categorías</h4><div class=\"contenedor-filtros-scroll\">" + 
                Array.from(categoriasUnicas).sort().map(cat => `<label style=\'display:block; margin-bottom:5px; cursor:pointer;\'><input type="checkbox" class="filtro-cat" value="${cat}" checked> ${cat}</label>`).join("") + 
                "</div>";
            L.DomEvent.disableClickPropagation(div);
            return div;
        };
        controlCategorias.addTo(map);
    ';
}