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
        register_setting( 'asp_settings_group', 'asp_results_page_id' );
        register_setting( 'asp_settings_group', 'asp_title_limit' );
        register_setting( 'asp_settings_group', 'asp_excerpt_limit' );
        
        // --- NUEVO: Configuración del Botón ---
        register_setting( 'asp_settings_group', 'asp_btn_text' );
        register_setting( 'asp_settings_group', 'asp_btn_color' );

        register_setting( 'asp_settings_group', 'asp_excluded_ids', array(
            'sanitize_callback' => array( $this, 'sanitize_excluded_ids' )
        ));
    }

    public function sanitize_excluded_ids( $input ) {
        if ( ! is_array( $input ) ) return array();
        return array_map( 'intval', $input );
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>Panel de Control: Buscador Profesional</h1>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                
                <div style="flex: 2; min-width: 300px;">
                    <div class="card" style="padding: 0 20px 20px; margin-top: 20px;">
                        <h2>⚙️ Configuración General</h2>
                        <form method="post" action="options.php">
                            <?php settings_fields( 'asp_settings_group' ); ?>
                            <?php do_settings_sections( 'asp_settings_group' ); ?>
                            
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row">Placeholder (Texto fondo)</th>
                                    <td>
                                        <input type="text" name="asp_placeholder_text" value="<?php echo esc_attr( get_option('asp_placeholder_text', 'Buscar servicio aquí...') ); ?>" class="regular-text" />
                                    </td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row">Texto del Botón</th>
                                    <td>
                                        <input type="text" name="asp_btn_text" value="<?php echo esc_attr( get_option('asp_btn_text', '') ); ?>" placeholder="Ej: Buscar (Déjalo vacío para usar ícono)" class="regular-text" />
                                        <p class="description">Si escribes texto, el ícono de lupa desaparecerá.</p>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">Color del Botón</th>
                                    <td>
                                        <input type="color" name="asp_btn_color" value="<?php echo esc_attr( get_option('asp_btn_color', '#0073aa') ); ?>" />
                                    </td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row">Página de Resultados</th>
                                    <td>
                                        <?php 
                                        wp_dropdown_pages( array(
                                            'name'              => 'asp_results_page_id',
                                            'show_option_none'  => '&mdash; Seleccionar página creada &mdash;',
                                            'option_none_value' => '0',
                                            'selected'          => get_option( 'asp_results_page_id', 0 )
                                        ));
                                        ?>
                                        <p class="description">Es obligatorio seleccionar la página donde pusiste el shortcode <code>[asp_resultados]</code>.</p>
                                    </td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row">Límite Título (caracteres)</th>
                                    <td>
                                        <input type="number" name="asp_title_limit" value="<?php echo esc_attr( get_option('asp_title_limit', 50) ); ?>" class="small-text" />
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">Límite Descripción (caracteres)</th>
                                    <td>
                                        <input type="number" name="asp_excerpt_limit" value="<?php echo esc_attr( get_option('asp_excerpt_limit', 100) ); ?>" class="small-text" />
                                    </td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row">Excluir Páginas</th>
                                    <td>
                                        <?php 
                                        $pages = get_pages( array( 'post_status' => 'publish' ) );
                                        $excluded = get_option( 'asp_excluded_ids', array() );
                                        ?>
                                        <select name="asp_excluded_ids[]" multiple="multiple" style="height: 150px; min-width: 300px;">
                                            <?php foreach ( $pages as $page ) : ?>
                                                <?php $selected = in_array( $page->ID, $excluded ) ? 'selected="selected"' : ''; ?>
                                                <option value="<?php echo $page->ID; ?>" <?php echo $selected; ?>>
                                                    <?php echo esc_html( $page->post_title ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button(); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
