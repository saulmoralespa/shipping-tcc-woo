<?php
/**
 * Plugin Name: Shipping TCC Woo
 * Description: Shipping TCC Woocommerce is available for Colombia
 * Version: 1.0.0
 * Author: Saul Morales Pacheco
 * Author URI: https://saulmoralespa.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC tested up to: 6.3.1
 * WC requires at least: 4.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(!defined('SHIPPING_TCC_WOO_STW_VERSION')){
    define('SHIPPING_TCC_WOO_STW_VERSION', '1.0.0');
}

add_action( 'plugins_loaded', 'shipping_tcc_woo_stw_init');

function shipping_tcc_woo_stw_init(){
    if ( !shipping_tcc_woo_stw_requirements() )
        return;

    shipping_tcc_woo_stw()->run_tcc_stw();
}

function shipping_tcc_woo_stw_notices( $notice ) {
    ?>
    <div class="error notice">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function shipping_tcc_woo_stw_requirements(){

    if ( ! extension_loaded( 'soap' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_tcc_woo_stw_notices( 'El plugin Shipping TCC Woocommerce requiere que la extensión SOAP esté instalada' );
                }
            );
        }
        return false;
    }

    if ( ! is_plugin_active(
        'woocommerce/woocommerce.php'
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    shipping_tcc_woo_stw_notices( 'El plugin Shipping TCC Woocommerce requiere que esté instalado y activo el plugin: Woocommerce' );
                }
            );
        }
        return false;
    }

    $woo_countries  = new WC_Countries();
    $default_country = $woo_countries->get_base_country();

    if ( ! in_array( $default_country, array( 'CO' ), true ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    $country = 'El plugin Shipping TCC Woocommerce requiere que el país donde se encuentra ubicada la tienda sea Colombia '  .
                        sprintf(
                            '%s',
                            '<a href="' . admin_url() .
                            'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">' .
                            'Click para establecer</a>' );
                    shipping_tcc_woo_stw_notices( $country );
                }
            );
        }
        return false;
    }

    $plugin_path_departamentos_ciudades_colombia_woo = 'departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php';

    if ( !is_plugin_active(
        $plugin_path_departamentos_ciudades_colombia_woo
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    $action = 'install-plugin';
                    $slug = 'departamentos-y-ciudades-de-colombia-para-woocommerce';
                    $plugin_install_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => $action,
                                'plugin' => $slug
                            ),
                            admin_url( 'update.php' )
                        ),
                        $action.'_'.$slug
                    );
                    $plugin = 'El plugin Shipping TCC Woocommerce requiere que esté instalado y activo el plugin: '  .
                        sprintf(
                            '%s',
                            "<a class='button button-primary' href='$plugin_install_url'>Departamentos y ciudades de Colombia para Woocommerce</a>" );

                    shipping_tcc_woo_stw_notices( $plugin );
                }
            );
        }
        return false;
    }
    return true;
}

function shipping_tcc_woo_stw(){
    static $plugin;
    if (!isset($plugin)){
        require_once('includes/class-shipping-tcc-wc-plugin.php');
        $plugin = new Shipping_Tcc_WC_Plugin(__FILE__, SHIPPING_TCC_WOO_STW_VERSION);
    }
    return $plugin;
}