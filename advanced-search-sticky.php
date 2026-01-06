<?php
/**
 * Plugin Name: Buscador Avanzado y Sticky
 * Description: Buscador AJAX profesional con miniaturas, exclusiones, estadísticas y bloques flotantes.
 * Version: 2.0
 * Author: Tu Asistente IA
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ASP_PATH', plugin_dir_path( __FILE__ ) );
define( 'ASP_URL', plugin_dir_url( __FILE__ ) );

// Incluir clases
require_once ASP_PATH . 'includes/class-asp-admin.php';
require_once ASP_PATH . 'includes/class-asp-search.php';

// Inicializar clases
function asp_init_plugin() {
    new ASP_Admin();
    new ASP_Search();
}
add_action( 'plugins_loaded', 'asp_init_plugin' );

// Crear tabla de estadísticas al activar
register_activation_hook( __FILE__, 'asp_create_stats_table' );

function asp_create_stats_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'asp_search_stats';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        term varchar(255) NOT NULL,
        hits mediumint(9) NOT NULL DEFAULT 1,
        last_search datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
