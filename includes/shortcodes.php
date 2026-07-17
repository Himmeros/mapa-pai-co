<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// 1. SHORTCODE CLIENTE INDIVIDUAL
// ==========================================
function mapa_pai_shortcode_cliente( $atts ) {
    $atts = shortcode_atts( array( 'id' => '' ), $atts );
    $post_id = !empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

    $lat = get_post_meta( $post_id, '_mapa_pai_latitud', true );
    $lon = get_post_meta( $post_id, '_mapa_pai_longitud', true );
    $nombre = get_the_title( $post_id );

    $capa_mapa = get_option('mapa_pai_capa_estilo', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');

    if ( empty( $lat ) || empty( $lon ) ) {
        return '<p>No hay coordenadas definidas para esta ubicación.</p>';
    }

    $map_id = 'mapa-single-' . $post_id;
    
    // CORRECCIÓN RESPONSIVE: Estilos estructurados en lugar de atributos inline rígidos
    $html = '
    <style>
        #' . $map_id . ' { height: 400px; width: 100%; border: 1px solid #ddd; border-radius: 8px; z-index: 1; }
        @media (max-width: 768px) { 
            #' . $map_id . ' { height: 50vh; min-height: 350px; } 
        }
    </style>';
    
    $html .= '<div id="' . $map_id . '"></div>';
    
    $html .= '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { attribution: "© OpenStreetMap" });
        var cartoLight = L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png", { attribution: "© CartoDB" });
        var cartoDark = L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png", { attribution: "© CartoDB" });
        var topo = L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", { attribution: "© OpenTopoMap" });

        var mapasBase = {
            "Estándar (OSM)": osm,
            "Claro (Minimalista)": cartoLight,
            "Oscuro": cartoDark,
            "Topográfico": topo
        };

        var capaSeleccionadaUrl = "' . esc_js($capa_mapa) . '";
        var capaInicial = osm;
        
        if (capaSeleccionadaUrl.indexOf("light_all") !== -1) capaInicial = cartoLight;
        if (capaSeleccionadaUrl.indexOf("dark_all") !== -1) capaInicial = cartoDark;
        if (capaSeleccionadaUrl.indexOf("opentopomap") !== -1) capaInicial = topo;

        var map = L.map("' . $map_id . '", {
            center: [' . $lat . ', ' . $lon . '],
            zoom: 16,
            layers: [capaInicial]
        });
        
        L.control.layers(mapasBase, null, { position: "topright" }).addTo(map);
        
        L.marker([' . $lat . ', ' . $lon . ']).addTo(map).bindPopup("<b>' . esc_js($nombre) . '</b>").openPopup();
        
        // CORRECCIÓN: Fuerza a Leaflet a recalcular sus dimensiones en móviles durante el renderizado inicial
        setTimeout(function(){ map.invalidateSize(); }, 400);
    });
    </script>';
    
    return $html;
}
add_shortcode( 'mapa_pai_cliente', 'mapa_pai_shortcode_cliente' );


// ==========================================
// 2. SHORTCODE DIRECTORIO GENERAL
// ==========================================
function mapa_pai_shortcode_directorio() {
    // --- SE APLICÓ EL ORDENAMIENTO EN LA CONSULTA PHP ---
    $args = array(
        'post_type'      => array('post', 'page', 'mapa_pai_cliente', 'job_listing'),
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC'
    );

    $clientes = new WP_Query( $args );
    $lista_clientes = array();

    if ( $clientes->have_posts() ) {
        while ( $clientes->have_posts() ) {
            $clientes->the_post();
            $post_id = get_the_ID();
            $lat     = get_post_meta( $post_id, '_mapa_pai_latitud', true );
            $lon     = get_post_meta( $post_id, '_mapa_pai_longitud', true );
            
            if ( !empty($lat) && !empty($lon) ) {
                $categorias_post = array();
                $terms_category = get_the_terms( $post_id, 'category' );
                if ( !empty($terms_category) && !is_wp_error($terms_category) ) {
                    foreach ( $terms_category as $term ) { $categorias_post[] = $term->name; }
                }
                $terms_job = get_the_terms( $post_id, 'job_listing_category' );
                if ( !empty($terms_job) && !is_wp_error($terms_job) ) {
                    foreach ( $terms_job as $term ) { $categorias_post[] = $term->name; }
                }
                if ( empty($categorias_post) ) { $categorias_post[] = 'Sin categoría'; }

                $lista_clientes[] = array(
                    'nombre'     => get_the_title(),
                    'direccion'  => get_post_meta( $post_id, '_mapa_pai_direccion', true ) ?: 'Ubicación registrada',
                    'lat'        => $lat,
                    'lon'        => $lon,
                    'categorias' => $categorias_post,
                    'permalink'  => get_permalink( $post_id )
                );
            }
        }
        wp_reset_postdata();
    }

    $colores_dinamicos = array();
    $todos_los_terms = get_terms( array(
        'taxonomy' => array('category', 'job_listing_category'), 
        'hide_empty' => false
    ));
    if ( ! is_wp_error( $todos_los_terms ) ) {
        foreach ( $todos_los_terms as $t ) {
            $c = get_term_meta( $t->term_id, 'mapa_pai_color', true );
            if ( $c ) { $colores_dinamicos[$t->name] = $c; }
        }
    }
    
    $json_colores = json_encode((object)$colores_dinamicos);
    $json_clientes = json_encode($lista_clientes);
    $dimensiones = get_option('mapa_pai_dimensiones', array('width' => '100%', 'height' => '500px'));
    $capa_mapa = get_option('mapa_pai_capa_estilo', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
    
    $color_sidebar_opcion = get_option('mapa_pai_color_directorio', '#111');
    $color_sidebar = !empty($color_sidebar_opcion) ? $color_sidebar_opcion : '#111';
    
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
    </style>';

    $html .= '
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

    $html .= '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { attribution: "© OpenStreetMap contributors" });
        var cartoLight = L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png", { attribution: "© CartoDB" });
        var cartoDark = L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png", { attribution: "© CartoDB" });
        var topo = L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", { attribution: "© OpenTopoMap" });

        var mapasBase = {
            "Estándar (OSM)": osm,
            "Claro (Minimalista)": cartoLight,
            "Oscuro": cartoDark,
            "Topográfico": topo
        };

        var capaSeleccionadaUrl = "' . esc_js($capa_mapa) . '";
        var capaInicial = osm; 
        
        if (capaSeleccionadaUrl.indexOf("light_all") !== -1) capaInicial = cartoLight;
        if (capaSeleccionadaUrl.indexOf("dark_all") !== -1) capaInicial = cartoDark;
        if (capaSeleccionadaUrl.indexOf("opentopomap") !== -1) capaInicial = topo;

        var map = L.map("mapa-directorio-pai", {
            layers: [capaInicial]
        });

        L.control.layers(mapasBase, null, { position: "topright" }).addTo(map);

        var clientes = ' . $json_clientes . ';
        
        // --- SE APLICÓ EL ORDENAMIENTO EN JAVASCRIPT ---
        clientes.sort(function(a, b) {
            var nombreA = a.nombre.trim();
            var nombreB = b.nombre.trim();
            return nombreA.localeCompare(nombreB, "es", { sensitivity: "base" });
        });
        // --- FIN DEL ORDENAMIENTO JAVASCRIPT ---

        var limitesMapa = L.latLngBounds();
        var hayMarcadores = false;
        var todosLosMarcadores = [];
        var categoriasUnicas = new Set();
        var listaSidebar = document.getElementById("mapa-pai-sidebar-lista");
        var inputBuscador = document.getElementById("mapa-pai-buscador");
        var coloresCategorias = ' . $json_colores . ';
        
        var marcadorSeleccionado = null; 

        function obtenerColor(categoriasArray) {
            if (!categoriasArray || categoriasArray.length === 0) return "#333333"; 
            var categoriaPrincipal = categoriasArray[0]; 
            return coloresCategorias[categoriaPrincipal] || "#673AB7"; 
        }

        function crearPinSVG(color) {
            var svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 36" width="28px" height="42px">
                <path fill="${color}" d="M12 0C7.58 0 4 3.58 4 8c0 5.25 7 13 8 28 1-15 8-22.75 8-28 0-4.42-3.58-8-8-8z"/>
                <circle fill="#ffffff" cx="12" cy="8" r="4"/>
            </svg>`;
            return L.divIcon({ className: "custom-pin-icon", html: svg, iconSize: [28, 42], iconAnchor: [14, 42], popupAnchor: [0, -32] });
        }

        clientes.forEach(function(cliente) {
            var latNum = parseFloat(cliente.lat);
            var lonNum = parseFloat(cliente.lon);
            if (!isNaN(latNum) && !isNaN(lonNum)) {
                
                var colorAsignado = obtenerColor(cliente.categorias);
                var iconoPersonalizado = crearPinSVG(colorAsignado);

                var contenidoPopup = "<b>" + cliente.nombre + "</b><br>" + 
                                     cliente.direccion + "<br>" +
                                     "<a href=\'" + cliente.permalink + "\' style=\'color: #2196F3; text-decoration: none; display: inline-block; margin-top: 6px; font-weight: bold; font-size: 12px;\'>Ver detalles →</a>";

                // Le agregué autoClose: false para que no cierre tu globo de "Estoy aquí"
                var nuevoMarcador = L.marker([latNum, lonNum], { icon: iconoPersonalizado })
                    .bindPopup(contenidoPopup, { autoClose: false });
                
                nuevoMarcador.categorias = cliente.categorias;
                nuevoMarcador.textoBusqueda = (cliente.nombre + " " + cliente.direccion).toLowerCase();
                
                cliente.categorias.forEach(function(cat) { categoriasUnicas.add(cat); });
                
                var li = document.createElement("li");
                li.className = "mapa-pai-item-cliente";
                li.innerHTML = `
                    <div class="mapa-pai-item-nombre">
                        <span class="mapa-pai-item-color" style="background-color: ${colorAsignado};"></span>
                        ${cliente.nombre}
                    </div>
                    <div class="mapa-pai-item-direccion">${cliente.direccion}</div>
                `;
                
                li.addEventListener("click", function() {
                    document.querySelectorAll(".mapa-pai-item-cliente").forEach(function(el) {
                        el.classList.remove("mapa-pai-item-seleccionado");
                    });
                    
                    li.classList.add("mapa-pai-item-seleccionado");

                    marcadorSeleccionado = nuevoMarcador; 
                    map.flyTo([latNum, lonNum], 16, { animate: true, duration: 1.2 });
                    map.once("moveend", function() { nuevoMarcador.openPopup(); });
                });

                li.addEventListener("mouseenter", function() {
                    if (nuevoMarcador._icon) {
                        nuevoMarcador._icon.classList.add("pin-brincando");
                    }
                });
                
                li.addEventListener("mouseleave", function() {
                    if (nuevoMarcador._icon) {
                        nuevoMarcador._icon.classList.remove("pin-brincando");
                    }
                });

                listaSidebar.appendChild(li);
                nuevoMarcador.elementoSidebar = li; 

                nuevoMarcador.addTo(map);
                todosLosMarcadores.push(nuevoMarcador);
                limitesMapa.extend([latNum, lonNum]);
                hayMarcadores = true;
            }
        });

        setTimeout(function(){ map.invalidateSize(); }, 400);

        if (hayMarcadores) { map.fitBounds(limitesMapa, { padding: [40, 40], maxZoom: 15 }); } else { map.setView([10.4806, -66.9036], 13); }

        var BotonUbicacion = L.Control.extend({
            options: { position: "topleft" },
            onAdd: function (map) {
                var c = L.DomUtil.create("div", "leaflet-bar leaflet-control");
                var b = L.DomUtil.create("a", "", c);
                b.href = "#"; b.innerHTML = "📍"; b.style.backgroundColor = "#fff"; b.style.textAlign = "center";
                b.onclick = function(e) { e.preventDefault(); e.stopPropagation(); map.locate(); };
                return c;
            }
        });
        
        map.addControl(new BotonUbicacion());
        
       var controlCategorias = L.control({ position: "topright" });
        controlCategorias.onAdd = function (map) {
            var div = L.DomUtil.create("div", "mapa-pai-leyenda-categorias");
            div.style.backgroundColor = "' . esc_js($color_sidebar) . '";
            div.style.color = "#fff";
            div.style.padding = "15px";
            div.style.borderRadius = "5px";
            div.style.marginTop = "10px"; 
            
            div.innerHTML = "<div style=\'display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 5px; margin-bottom: 10px;\'>" +
                                "<h4 style=\'margin:0; color:#fff;\'>Categorías</h4>" +
                                "<span id=\'mapa-pai-toggle-btn\' style=\'cursor:pointer; font-weight:bold; font-size:18px; line-height:1; user-select:none; padding-left: 15px;\'>−</span>" +
                            "</div>" +
                            "<div id=\'mapa-pai-contenido-filtros\' class=\"contenedor-filtros-scroll\">" + 
                                Array.from(categoriasUnicas).sort().map(cat => `<label style=\'display:block; margin-bottom:5px; cursor:pointer;\'><input type="checkbox" class="filtro-cat" value="${cat}" checked> ${cat}</label>`).join("") + 
                            "</div>";
            
            L.DomEvent.disableClickPropagation(div);
            
            // Script para hacer que el botón funcione
            setTimeout(function() {
                var btnToggle = div.querySelector("#mapa-pai-toggle-btn");
                var contenido = div.querySelector("#mapa-pai-contenido-filtros");
                
                if(btnToggle && contenido) {
                    btnToggle.addEventListener("click", function(e) {
                        e.stopPropagation();
                        if (contenido.style.display === "none") {
                            contenido.style.display = "block";
                            btnToggle.innerHTML = "−";
                        } else {
                            contenido.style.display = "none";
                            btnToggle.innerHTML = "+";
                        }
                    });
                }
            }, 100);

            return div;
        };
        controlCategorias.addTo(map);

        inputBuscador.addEventListener("input", procesarFiltros);
        document.addEventListener("change", function(e) {
            if (e.target.classList.contains("filtro-cat")) {
                procesarFiltros();
            }
        });

        var marcadorUsuario;
        var iconoGpsPulsante = L.divIcon({
            className: "custom-gps-icon",
            html: \'<div class="burbuja-gps-contenedor"><div class="burbuja-gps-centro"></div><div class="burbuja-gps-onda"></div></div>\',
            iconSize: [20, 20], iconAnchor: [10, 10]
        });

        map.on("locationfound", function(e) {
            if (marcadorUsuario) map.removeLayer(marcadorUsuario);
            
            // Texto devuelto a "Estoy aquí" + autoClose: false 
            marcadorUsuario = L.marker(e.latlng, { icon: iconoGpsPulsante })
                .addTo(map)
                .bindPopup("<b style=\'color:#000;\'>Estoy aquí</b>", { autoClose: false });
            
            if (marcadorSeleccionado) {
                var limites = L.latLngBounds([
                    e.latlng,
                    marcadorSeleccionado.getLatLng()
                ]);
                map.fitBounds(limites, { padding: [60, 60], maxZoom: 16 });
                marcadorUsuario.openPopup();
                marcadorSeleccionado.openPopup();
            } else {
                map.setView(e.latlng, 15);
                marcadorUsuario.openPopup();
            }
        });

        window.addEventListener("resize", function() {
            map.invalidateSize();
        });
    });
    </script>';

    return $html;
}
add_shortcode( 'mapa_pai_directorio', 'mapa_pai_shortcode_directorio' );
?>