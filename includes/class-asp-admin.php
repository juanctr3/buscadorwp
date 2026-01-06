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
        // Texto del placeholder
        register_setting( 'asp_settings_group', 'asp_placeholder_text' );
        
        // Exclusiones: Usamos un callback especial para guardar el array correctamente
        register_setting( 'asp_settings_group', 'asp_excluded_ids', array(
            'sanitize_callback' => array( $this, 'sanitize_excluded_ids' )
        ));
    }

    /**
     * Limpia los datos del selector múltiple antes de guardar en DB
     */
    public function sanitize_excluded_ids( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }
        return array_map( 'intval', $input );
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>Panel de Control: Buscador Profesional</h1>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                
                <div style="flex: 2; min-width: 300px;">
                    <div class="card" style="padding: 0 20px 20px; margin-top: 20px;">
                        <h2>Configuración General</h2>
                        <form method="post" action="options.php">
                            <?php settings_fields( 'asp_settings_group' ); ?>
                            <?php do_settings_sections( 'asp_settings_group' ); ?>
                            
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row">Texto del Buscador</th>
                                    <td>
                                        <input type="text" name="asp_placeholder_text" value="<?php echo esc_attr( get_option('asp_placeholder_text', 'Buscar servicio aquí...') ); ?>" class="regular-text" />
                                        <p class="description">Este es el texto que aparece dentro de la caja de búsqueda antes de escribir.</p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">Excluir Páginas de los Resultados</th>
                                    <td>
                                        <?php 
                                        // 1. Obtener todas las páginas publicadas
                                        $pages = get_pages( array( 'post_status' => 'publish' ) );
                                        // 2. Obtener las ya excluidas (ahora es un array)
                                        $excluded = get_option( 'asp_excluded_ids', array() );
                                        ?>
                                        
                                        <select name="asp_excluded_ids[]" multiple="multiple" style="height: 200px; min-width: 300px; padding: 5px;">
                                            <?php foreach ( $pages as $page ) : ?>
                                                <?php $selected = in_array( $page->ID, $excluded ) ? 'selected="selected"' : ''; ?>
                                                <option value="<?php echo $page->ID; ?>" <?php echo $selected; ?>>
                                                    <?php echo esc_html( $page->post_title ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description" style="color: #d63638;">
                                            <strong>Nota:</strong> Mantén presionada la tecla <code>Ctrl</code> (Windows) o <code>Cmd</code> (Mac) para seleccionar múltiples páginas.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button(); ?>
                        </form>
                    </div>

                    <div class="card" style="padding: 0 20px 20px; margin-top: 20px;">
                        <h2>📊 Estadísticas de Búsqueda (Top 10)</h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Término Buscado</th>
                                    <th>Veces Buscado</th>
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
                                        echo "<tr><td>" . esc_html($row->term) . "</td><td>" . esc_html($row->hits) . "</td><td>" . esc_html($row->last_search) . "</td></tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3'>No hay datos registrados aún.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="flex: 1; min-width: 250px;">
                    <div class="card" style="background: #f0f6fc; border-left: 4px solid #72aee6; margin-top: 20px; padding: 10px 20px;">
                        <h3>📝 Instrucciones de Uso</h3>
                        
                        <h4>1. Insertar el Buscador</h4>
                        <p>Copia y pega este código corto en cualquier página, entrada o widget:</p>
                        <code style="background: #fff; padding: 5px; display: block; margin-bottom: 10px;">[buscar_paginas]</code>
                        
                        <h4>2. Fijar Elementos (Sticky)</h4>
                        <p>Para que un elemento se quede fijo al hacer scroll, ve a las opciones avanzadas del bloque (en Gutenberg) y añade esta clase en "Clase CSS adicional":</p>
                        <code style="background: #fff; padding: 5px; display: block;">bloque-flotante</code>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}
