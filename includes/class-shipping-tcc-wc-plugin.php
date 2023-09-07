<?php

class Shipping_Tcc_WC_Plugin
{
    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;
    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public $plugin_url;
    /**
     * assets plugin.
     *
     * @var string
     */
    public $assets;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public $includes_path;
    /**
     * Absolute path to plugin lib dir
     *
     * @var string
     */
    public $lib_path;
    /**
     * @var bool
     */
    private $_bootstrapped = false;

    public function __construct($file, $version)
    {
        $this->file = $file;
        $this->version = $version;

        $this->plugin_path   = trailingslashit( plugin_dir_path( $this->file ) );
        $this->plugin_url    = trailingslashit( plugin_dir_url( $this->file ) );
        $this->assets = $this->plugin_url . trailingslashit('assets');
        $this->includes_path = $this->plugin_path . trailingslashit( 'includes' );
        $this->lib_path = $this->plugin_path . trailingslashit( 'lib' );
    }

    public function run_tcc_stw(): void
    {
        try{
            if ($this->_bootstrapped){
                throw new Exception( 'Shipping TCC Woo can only be called once');
            }
            $this->_run();
            $this->_bootstrapped = true;
        }catch (Exception $e){
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                add_action('admin_notices', function() use($e) {
                    shipping_tcc_woo_stw_notices($e->getMessage());
                });
            }
        }
    }

    protected function _run(): void
    {
        if (!class_exists('\Saulmoralespa\Tcc\WebService'))
            require_once ($this->lib_path . 'vendor/autoload.php');
        require_once ($this->includes_path . 'class-method-shipping-tcc-wc.php');
        require_once ($this->includes_path . 'class-shipping-tcc-wc.php');

        add_filter( 'plugin_action_links_' . plugin_basename($this->file), array($this, 'plugin_action_links'));
        add_filter( 'woocommerce_shipping_methods', array( $this, 'shipping_tcc_wc_add_method') );
    }

    public function plugin_action_links($links)
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=shipping_tcc_wc') . '">' . 'Configuraciones' . '</a>';
        $plugin_links[] = '<a target="_blank" href="https://shop.saulmoralespa.com/shipping-tcc-woocommerce/">' . 'Documentación' . '</a>';
        return array_merge( $plugin_links, $links );
    }

    public function shipping_tcc_wc_add_method($methods)
    {
        $methods['shipping_tcc_wc'] = 'WC_Shipping_Method_Shipping_Tcc_WC';
        return $methods;
    }

    public function log($message): void
    {
        if (is_array($message) || is_object($message))
            $message = print_r($message, true);
        $logger = new WC_Logger();
        $logger->add('shipping-tcc-wc', $message);
    }
}