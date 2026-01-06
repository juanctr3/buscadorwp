<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ASP_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Configuración Buscador', 
            'Buscador Pro', 
            'manage_options', 
            'asp_settings', 
            array( $this, 'settings_page_html' ), 
            'dashicons-search', 
            80
        );
    }

    public function register_settings() {
        register_setting( 'asp_settings_group', 'asp_placeholder_text' );
        register_setting( 'asp_settings_group', 'asp_excluded_ids' );
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>Configuración del Buscador Profesional</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'asp_settings_group' ); ?>
                <?php do_settings_sections( 'asp_settings_group' ); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Texto del Placeholder</th>
                        <td>
                            <input type="text" name="asp_placeholder_text" value="<?php echo esc_attr( get_option('asp_placeholder_text', 'Buscar servicio aquí...') ); ?>" class="regular-text" />
                            <p class="description">Ej: Buscar servicio aquí...</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Excluir IDs de Páginas</th>
                        <td>
                            <input type="text" name="asp_excluded_ids" value="<?php echo esc_attr( get_option('asp_excluded_ids') ); ?>" class="regular-text" />
                            <p class="description">Introduce los IDs separados por coma (ej: 12, 45, 90).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr>
            <h2>Estadísticas de Búsqueda (Top 10)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Término Buscado</th>
                        <th>Cantidad de Búsquedas</th>
                        <th>Última vez</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'asp_search_stats';
                    $results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY hits DESC LIMIT 10" );
                    
                    if ( $results ) {
                        foreach ( $results as $row ) {
                            echo "<tr><td>{$row->term}</td><td>{$row->hits}</td><td>{$row->last_search}</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No hay datos aún.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
