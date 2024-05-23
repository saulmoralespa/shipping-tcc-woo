<?php
/**
 * Plugin Name: Shipping TCC Woo
 * Description: Shipping TCC Woocommerce is available for Colombia
 * Version: 2.0.0
 * Author: Saul Morales Pacheco
 * Author URI: https://saulmoralespa.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC tested up to: 8.9.1
 * WC requires at least: 4.0
 * Requires Plugins: woocommerce,departamentos-y-ciudades-de-colombia-para-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(!defined('SHIPPING_TCC_WOO_STW_VERSION')){
    define('SHIPPING_TCC_WOO_STW_VERSION', '2.0.0');
}

if(!defined('SHIPPING_TCC_WOO_STW_ID')){
    define('SHIPPING_TCC_WOO_STW_ID', 'shipping_tcc_wc');
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
        <p><?php echo wp_kses_post(wpautop( $notice )); ?></p>
    </div>
    <?php
}

function shipping_tcc_woo_stw_requirements(){

    if ( ! function_exists( 'is_plugin_active' ) ) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

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

    $woo_countries  = new WC_Countries();
    $default_country = $woo_countries->get_base_country();

    if ($default_country !== 'CO') {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    $country = 'El plugin Shipping TCC Woocommerce requiere que la tienda esté ubicada en Colombia. '  .
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
add_action( 'woocommerce_product_after_variable_attributes', array('Shipping_Tcc_WC_Plugin', 'variation_settings_fields'), 10, 3 );
add_action( 'woocommerce_product_options_shipping', array('Shipping_Tcc_WC_Plugin', 'add_custom_shipping_option_to_products'), 10);