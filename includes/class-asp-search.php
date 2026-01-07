<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ASP_Search {

    public function __construct() {
        // Shortcode para la CAJA de búsqueda
        add_shortcode( 'buscar_paginas', array( $this, 'render_search_box' ) );
        // Shortcode para la LISTA de resultados
        add_shortcode( 'asp_resultados', array( $this, 'render_results_list' ) );
        
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_asp_fetch_results', array( $this, 'ajax_fetch_results' ) );
        add_action( 'wp_ajax_nopriv_asp_fetch_results', array( $this, 'ajax_fetch_results' ) );
        add_action( 'wp_head', array( $this, 'print_sticky_styles' ) ); 
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'asp-style', ASP_URL . 'assets/css/style.css' );
        wp_enqueue_script( 'asp-script', ASP_URL . 'assets/js/search.js', array('jquery'), '1.0', true );
        wp_localize_script( 'asp-script', 'aspData', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'asp_search_nonce' )
        ));
    }

    /**
     * 1. SHORTCODE: CAJA DE BÚSQUEDA [buscar_paginas]
     */
    public function render_search_box() {
        $placeholder = get_option( 'asp_placeholder_text', 'Buscar servicio aquí...' );
        
        // Determinar a dónde enviar el formulario
        $results_page_id = get_option( 'asp_results_page_id', 0 );
        $action_url = $results_page_id ? get_permalink( $results_page_id ) : home_url( '/' );

        ob_start();
        ?>
        <div class="asp-search-wrapper">
            <form role="search" method="get" class="asp-search-form" action="<?php echo esc_url( $action_url ); ?>" autocomplete="off">
                <div class="asp-input-group">
                    <input type="search" id="asp-search-input" class="search-field"
                        placeholder="<?php echo esc_attr( $placeholder ); ?>"
                        value="<?php echo get_search_query(); ?>" name="s" /> <button type="submit" class="search-submit"><span class="dashicons dashicons-search"></span></button>
                </div>
                
                <input type="hidden" name="post_type" value="page" />
                <div id="asp-results-container" class="asp-results-dropdown"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 2. SHORTCODE: PÁGINA DE RESULTADOS [asp_resultados]
     */
    public function render_results_list() {
        // Verificar si hay una búsqueda en curso
        if ( ! isset( $_GET['s'] ) || empty( $_GET['s'] ) ) {
            return '<p class="asp-message">Introduce un término para buscar.</p>';
        }

        $term = sanitize_text_field( $_GET['s'] );
        $excluded_ids = get_option( 'asp_excluded_ids', array() );
        
        // Obtener límites de caracteres
        $limit_title = (int) get_option( 'asp_title_limit', 50 );
        $limit_desc  = (int) get_option( 'asp_excerpt_limit', 100 );

        // Configurar Query
        $args = array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            's'              => $term,
            'posts_per_page' => 10,
            'post__not_in'   => $excluded_ids,
            'paged'          => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1
        );

        $query = new WP_Query( $args );
        
        ob_start();

        if ( $query->have_posts() ) {
            echo '<div class="asp-results-grid">';
            while ( $query->have_posts() ) {
                $query->the_post();
                
                // Procesar imagen
                $thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium' ); // Imagen más grande para resultados
                $img_html = $thumb ? '<div class="asp-res-img" style="background-image: url('.$thumb.');"></div>' : '<div class="asp-res-img asp-no-img"></div>';

                // Procesar textos
                $title = get_the_title();
                if ( $limit_title > 0 && mb_strlen($title) > $limit_title ) {
                    $title = mb_substr($title, 0, $limit_title) . '...';
                }

                $excerpt = get_the_excerpt();
                if ( ! $excerpt ) { $excerpt = wp_trim_words( get_the_content(), 20 ); }
                if ( $limit_desc > 0 && mb_strlen($excerpt) > $limit_desc ) {
                    $excerpt = mb_substr($excerpt, 0, $limit_desc) . '...';
                }

                ?>
                <div class="asp-result-item">
                    <a href="<?php the_permalink(); ?>" class="asp-result-link">
                        <?php echo $img_html; ?>
                        <div class="asp-res-content">
                            <h3><?php echo esc_html( $title ); ?></h3>
                            <p><?php echo esc_html( $excerpt ); ?></p>
                            <span class="asp-read-more">Ver más &rarr;</span>
                        </div>
                    </a>
                </div>
                <?php
            }
            echo '</div>'; // Fin grid

            // Paginación simple
            echo '<div class="asp-pagination">';
            echo paginate_links( array( 'total' => $query->max_num_pages ) );
            echo '</div>';

            wp_reset_postdata();
        } else {
            echo '<p class="asp-message">No se encontraron resultados para: <strong>' . esc_html( $term ) . '</strong></p>';
        }

        return ob_get_clean();
    }

    // --- AJAX y LOGS se mantienen igual que antes, solo asegúrate de copiar esas funciones aquí abajo ---
    public function ajax_fetch_results() {
        // ... (Copia el código de ajax_fetch_results de la respuesta anterior aquí) ...
        // Importante: Asegúrate de incluir esta función para que el autocompletado siga funcionando
        check_ajax_referer( 'asp_search_nonce', 'security' );
        $term = sanitize_text_field( $_POST['term'] );
        $excluded_ids = get_option( 'asp_excluded_ids', array() );
        $this->log_search_stats( $term ); // Log stats
        
        $args = array( 'post_type' => 'page', 'post_status' => 'publish', 's' => $term, 'posts_per_page' => 5, 'post__not_in' => $excluded_ids );
        $query = new WP_Query( $args );
        $results = array();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $thumb = get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' );
                $default_img = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>');
                $results[] = array( 'title' => get_the_title(), 'link'  => get_the_permalink(), 'image' => $thumb ? $thumb : $default_img );
            }
            wp_reset_postdata();
        }
        wp_send_json_success( $results );
    }

    private function log_search_stats( $term ) {
        // ... (Copia el código de log_search_stats de la respuesta anterior aquí) ...
        global $wpdb;
        $table_name = $wpdb->prefix . 'asp_search_stats';
        $term = strtolower( trim( $term ) );
        if ( strlen( $term ) < 3 ) return; 
        $exists = $wpdb->get_row( $wpdb->prepare( "SELECT id, hits FROM $table_name WHERE term = %s", $term ) );
        if ( $exists ) {
            $wpdb->update( $table_name, array( 'hits' => $exists->hits + 1, 'last_search' => current_time( 'mysql' ) ), array( 'id' => $exists->id ) );
        } else {
            $wpdb->insert( $table_name, array( 'term' => $term, 'hits' => 1, 'last_search' => current_time( 'mysql' ) ) );
        }
    }

    public function print_sticky_styles() {
        ?>
        <style>
            .bloque-flotante { position: -webkit-sticky; position: sticky; top: 20px; z-index: 999; align-self: start; }
            body.admin-bar .bloque-flotante { top: 52px; }
        </style>
        <?php
    }
}
