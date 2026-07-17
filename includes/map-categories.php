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
            
            // Construimos el HTML con el título y un botón de colapsar alineados
            div.innerHTML = "<div style=\'display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 5px; margin-bottom: 10px;\'>" +
                                "<h4 style=\'margin:0; color:#fff;\'>Categorías</h4>" +
                                "<span id=\'mapa-pai-toggle-btn\' style=\'cursor:pointer; font-weight:bold; font-size:18px; line-height:1; user-select:none; padding-left: 15px;\'>−</span>" +
                            "</div>" +
                            "<div id=\'mapa-pai-contenido-filtros\' class=\"contenedor-filtros-scroll\">" + 
                                Array.from(categoriasUnicas).sort().map(cat => `<label style=\'display:block; margin-bottom:5px; cursor:pointer;\'><input type="checkbox" class="filtro-cat" value="${cat}" checked> ${cat}</label>`).join("") + 
                            "</div>";
            
            L.DomEvent.disableClickPropagation(div);
            
            // Lógica para detectar el clic en el botón y mostrar/ocultar el contenido
            var btnToggle = div.querySelector("#mapa-pai-toggle-btn");
            var contenido = div.querySelector("#mapa-pai-contenido-filtros");
            
            L.DomEvent.on(btnToggle, "click", function(e) {
                L.DomEvent.stopPropagation(e); // Evita que el clic pase al mapa
                
                if (contenido.style.display === "none") {
                    contenido.style.display = "block";
                    btnToggle.innerHTML = "−"; // Cambia al símbolo de minimizar
                } else {
                    contenido.style.display = "none";
                    btnToggle.innerHTML = "+"; // Cambia al símbolo de maximizar
                }
            });

            return div;
        };
        controlCategorias.addTo(map);
    ';
}