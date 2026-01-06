<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ASP_Search {

    public function __construct() {
        add_shortcode( 'buscar_paginas', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_asp_fetch_results', array( $this, 'ajax_fetch_results' ) );
        add_action( 'wp_ajax_nopriv_asp_fetch_results', array( $this, 'ajax_fetch_results' ) );
        add_action( 'wp_head', array( $this, 'print_sticky_styles' ) ); // Mantiene funcionalidad sticky
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'asp-style', ASP_URL . 'assets/css/style.css' );
        wp_enqueue_script( 'asp-script', ASP_URL . 'assets/js/search.js', array('jquery'), '1.0', true );
        wp_localize_script( 'asp-script', 'aspData', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'asp_search_nonce' )
        ));
    }

    public function render_shortcode() {
        $placeholder = get_option( 'asp_placeholder_text', 'Buscar servicio aquí...' );
        ob_start();
        ?>
        <div class="asp-search-wrapper">
            <form role="search" method="get" class="asp-search-form" action="<?php echo home_url( '/' ); ?>" autocomplete="off">
                <div class="asp-input-group">
                    <input type="search" id="asp-search-input" class="search-field"
                        placeholder="<?php echo esc_attr( $placeholder ); ?>"
                        value="<?php echo get_search_query(); ?>" name="s" />
                    <button type="submit" class="search-submit"><span class="dashicons dashicons-search"></span></button>
                </div>
                <input type="hidden" name="post_type" value="page" />
                <div id="asp-results-container" class="asp-results-dropdown"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_fetch_results() {
        check_ajax_referer( 'asp_search_nonce', 'security' );

        $term = sanitize_text_field( $_POST['term'] );
        $excluded_ids = array_map( 'intval', explode( ',', get_option( 'asp_excluded_ids', '' ) ) );

        // 1. Log Stats
        $this->log_search_stats( $term );

        // 2. Query
        $args = array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            's'              => $term,
            'posts_per_page' => 5,
            'post__not_in'   => $excluded_ids
        );

        $query = new WP_Query( $args );
        $results = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $thumb = get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' );
                $results[] = array(
                    'title' => get_the_title(),
                    'link'  => get_the_permalink(),
                    'image' => $thumb ? $thumb : ASP_URL . 'assets/css/default-icon.png' // Fallback image logic
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success( $results );
    }

    private function log_search_stats( $term ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'asp_search_stats';
        $term = strtolower( trim( $term ) );
        
        if ( strlen( $term ) < 3 ) return; // No guardar busquedas muy cortas

        // Revisar si existe
        $exists = $wpdb->get_row( $wpdb->prepare( "SELECT id, hits FROM $table_name WHERE term = %s", $term ) );

        if ( $exists ) {
            $wpdb->update( 
                $table_name, 
                array( 'hits' => $exists->hits + 1, 'last_search' => current_time( 'mysql' ) ), 
                array( 'id' => $exists->id ) 
            );
        } else {
            $wpdb->insert( 
                $table_name, 
                array( 'term' => $term, 'hits' => 1, 'last_search' => current_time( 'mysql' ) ) 
            );
        }
    }

    public function print_sticky_styles() {
        ?>
        <style>
            .bloque-flotante {
                position: -webkit-sticky; position: sticky; top: 20px; z-index: 999; align-self: start;
            }
            body.admin-bar .bloque-flotante { top: 52px; }
        </style>
        <?php
    }
}
