<?php 
/* *
 * Plugin Name: Simplificar menú de administración
 * Plugin URI: https://www.webycomunicacion.es/blog/plugins/simplificar-menu-administrador
 * Description: Simplifica la gestión de los usuarios no expertos ocultándoles todas las opciones que no necesitan usar.
 * Version: 1.0.2
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author: J&L | Web y Comunicación
 * Author URI: https://www.webycomunicacion.es
 * Text Domain: jlwebcom-simplificar-adminmenu
 * Domain Path: /languages/
 * License: GPLv2
 * */ 
 
add_action( 'admin_init', 'jlwebcom_simplifica_admin_menu_oculta_items',20 );
function jlwebcom_simplifica_admin_menu_oculta_items() {
    if( is_admin() ){
        $usuario = wp_get_current_user();
        $roles = $usuario->roles;
        if( !in_array('administrator',$roles) ){
            $items = jlwebcom_simplifica_admin_menu_get_items_admin_menu();
            foreach ($items as $key => $item) {
                if( get_option( 'jlwebcom_simplifica_admin_menu_' . jlwebcom_simplifica_admin_menu_adaptar($key) )['valor'] == 'on' ){
                    remove_menu_page( $items[$key] );
                }
            }
        }
    }
}

//Crea variables de opción con valor predeterminado
register_activation_hook( __FILE__, 'jlwebcom_simplifica_admin_menu_default_options');
function jlwebcom_simplifica_admin_menu_default_options(){
    $items = jlwebcom_simplifica_admin_menu_get_items_admin_menu();
    if( !is_array( $items ) ) return null;
    foreach ($items as $key => $item) {
        $aux = null;
        $aux['nombre'] = $key;
        $aux['url'] = $item;
        $aux['valor'] = '';
        switch ($item) {
            case 'wpcf7':
                $aux['valor'] = 'on';
                break;
            case 'plugins.php':
                $aux['valor'] = 'on';
                break;
            case 'users.php':
                $aux['valor'] = 'on';
                break;
            case 'tools.php':
                $aux['valor'] = 'on';
                break;
            case 'admin.php?page=itsec-dashboard':
                $aux['valor'] = 'on';
                break;
            case 'admin.php?page=maxmegamenu':
                $aux['valor'] = 'on';
                break;
                
            default:
                $aux['valor'] = '';
                break;
        }
        if( get_option( 'jlwebcom_simplifica_admin_menu_' . jlwebcom_simplifica_admin_menu_adaptar($key) ) === false ){
            add_option( 'jlwebcom_simplifica_admin_menu_' . jlwebcom_simplifica_admin_menu_adaptar($key), $aux );
        }
    }
    
}

//Añade página al menú de configuración
add_action( 'admin_menu', 'jlwebcom_simplifica_admin_menu_ajustes' );
function jlwebcom_simplifica_admin_menu_ajustes(){
    $pagina_opciones = add_options_page('Simplificar menú de administración',           //Título de la página
                                        'Simplificar menú',                             //Nombre en menú
                                        'manage_options',                               //Nivel de acceso (solo usuarios)
                                        'jlwebcom_simplifica_admin_menu_conf',          //Slug
                                        'jlwebcom_simplifica_admin_menu_genera_pagina'  //Función gestora de página
                                        );
}

//Genera el código de la página de ajustes
function jlwebcom_simplifica_admin_menu_genera_pagina(){
    $items = jlwebcom_simplifica_admin_menu_get_items_admin_menu_oculto();
    ?>
    <div class="wrap">
        <h2><?php printf( __( 'Ocultar opciones de menú', 'jlwebcom-simplificar-adminmenu' ) ); ?></h2>
        <i><?php printf( __( '(Marca las opciones que desees ocultar a los usuarios)', 'jlwebcom-simplificar-adminmenu' ) ); ?></i>
    </div>

    <form method="post" action="admin-post.php">
        <input type="hidden" name="action" value="jlwebcom_simplifica_admin_menu_guardar">
        <?php wp_nonce_field('jlwebcom_simplifica_admin_menu_token') ?>
        <br/>
        <?php
        foreach ($items as $key => $item) {
            ?>
            <input type='hidden' value='0' name='jlwebcom_simplifica_admin_menu_<?php printf( __('%s', 'jlwebcom-simplificar-adminmenu' ), $key ); ?>'>
            <input type="checkbox" id="jlwebcom_simplifica_admin_menu_<?php printf( __('%s', 'jlwebcom-simplificar-adminmenu' ), $key ); ?>" name="jlwebcom_simplifica_admin_menu_<?php printf( __('%s', 'jlwebcom-simplificar-adminmenu' ), $key ); ?>" <?php if($item['valor'] == on){ printf( 'checked' ); } ?> />
            <label for="jlwebcom_simplifica_admin_menu_<?php printf( __('%s', 'jlwebcom-simplificar-adminmenu' ), $key ); ?>"><b><?php printf( __('%s', 'jlwebcom-simplificar-adminmenu' ), $key ); ?></b></label>
            <br/><br/>
            <?php
        }
        submit_button( __( 'Guardar', 'jlwebcom-simplificar-adminmenu' ) )
        ?>
    </form>
    <?php     
}

//Guardar datos de formulario
add_action( 'admin_post_jlwebcom_simplifica_admin_menu_guardar', 'jlwebcom_simplifica_admin_menu_guardar' );
function jlwebcom_simplifica_admin_menu_guardar(){

    //Validar permisos de usuario
    if( !current_user_can( 'manage_options' ) ){
        wp_die( 'Not allowed' );
    }

    //Validar token
    check_admin_referer( 'jlwebcom_simplifica_admin_menu_token' );


    //Actualizar valores
    $campos = $_POST;
    foreach ($campos as $key => $campo) {
        $key    = sanitize_text_field( $key );
        $campo  = sanitize_text_field( $campo );
        if( get_option( $key ) !== false ){
            $item = get_option( $key );
            $item['valor'] = $campo;
            update_option( $key, $item );
        }
    }

    //Regreso a página de ajustes
    wp_redirect( add_query_arg( 'page',
                                'jlwebcom_simplifica_admin_menu_conf',
                                admin_url( 'options-general.php' )
                              ) 
                );
    exit;
}

function jlwebcom_simplifica_admin_menu_get_items_admin_menu() {
    global $menu;
    $items = null;
    foreach ($menu as $key => $item) {
        if( isset( $item[4] ) AND $item[0] != '' ){
            $items[strip_tags($item[0])] = $item[2];
        }
    }
    return $items;
}

function jlwebcom_simplifica_admin_menu_get_items_admin_menu_oculto() {
    $items = jlwebcom_simplifica_admin_menu_get_items_admin_menu();
    foreach ($items as $key => $item) {
       $items_res[jlwebcom_simplifica_admin_menu_adaptar($key)] = get_option( 'jlwebcom_simplifica_admin_menu_' . jlwebcom_simplifica_admin_menu_adaptar($key) );
    }
    return $items_res;
}

function jlwebcom_simplifica_admin_menu_adaptar( $key ){
    return strip_tags( str_replace(' ', '-', $key) );
}