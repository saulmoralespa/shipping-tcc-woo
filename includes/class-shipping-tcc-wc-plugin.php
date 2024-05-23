<?php

class Shipping_Tcc_WC_Plugin
{
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public string $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public string $plugin_url;
    /**
     * assets plugin.
     *
     * @var string
     */
    public string $assets;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public string $includes_path;
    /**
     * Absolute path to plugin lib dir
     *
     * @var string
     */
    public string $lib_path;
    /**
     * @var bool
     */
    private bool $_bootstrapped = false;

    public function __construct(
        protected $file,
        protected  $version
    )
    {
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
            require_once ($this->lib_path . 'WebService.php');
        require_once ($this->lib_path . 'plugin-update-checker/plugin-update-checker.php');
        require_once ($this->includes_path . 'class-method-shipping-tcc-wc.php');
        require_once ($this->includes_path . 'class-shipping-tcc-wc.php');

        $myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/saulmoralespa/shipping-tcc-woo',
            $this->file, //Full path to the main plugin file or functions.php.
            'shipping-tcc-woo'
        );

        $myUpdateChecker->setBranch('main');
        $myUpdateChecker->getVcsApi()->enableReleaseAssets();

        add_action( 'woocommerce_process_product_meta', array($this, 'save_custom_shipping_option_to_products'), 10 );
        add_action( 'woocommerce_save_product_variation', array($this, 'save_variation_settings_fields'), 10, 2 );
        add_filter( 'plugin_action_links_' . plugin_basename($this->file), array($this, 'plugin_action_links'));
        add_filter( 'woocommerce_shipping_methods', array( $this, 'shipping_tcc_wc_add_method') );
        add_action( 'woocommerce_order_status_changed', array('Shipping_Tcc_WC', 'generate_guide'), 10, 3 );
    }

    public function plugin_action_links($links): array
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=shipping_tcc_wc') . '">' . 'Configuraciones' . '</a>';
        $plugin_links[] = '<a target="_blank" href="https://shop.saulmoralespa.com/shipping-tcc-woocommerce/">' . 'Documentación' . '</a>';
        return array_merge( $plugin_links, $links );
    }

    public function shipping_tcc_wc_add_method($methods): array
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

    public static function add_custom_shipping_option_to_products(): void
    {
        global $post;
        global $shipping_custom_price_product_smp_loaded;

        if (!isset($shipping_custom_price_product_smp_loaded)) {
            $shipping_custom_price_product_smp_loaded = false;
        }

        if($shipping_custom_price_product_smp_loaded) return;

        woocommerce_wp_text_input( [
            'id'          => '_shipping_custom_price_product_smp[' . $post->ID . ']',
            'label'       => __( 'Valor declarado del producto'),
            'placeholder' => 'Valor declarado del envío',
            'desc_tip'    => true,
            'description' => __( 'El valor que desea declarar para el envío'),
            'value'       => get_post_meta( $post->ID, '_shipping_custom_price_product_smp', true ),
        ] );

        $shipping_custom_price_product_smp_loaded = true;
    }

    public static function variation_settings_fields($loop, $variation_data, $variation): void
    {
        global ${"shipping_custom_price_product_smp_$variation->ID"};

        if (!isset(${"shipping_custom_price_product_smp_$variation->ID"})) {
            ${"shipping_custom_price_product_smp_$variation->ID"} = false;
        }

        if(${"shipping_custom_price_product_smp_$variation->ID"}) return;

        woocommerce_wp_text_input(
            array(
                'id'          => '_shipping_custom_price_product_smp[' . $variation->ID . ']',
                'label'       => __( 'Valor declarado del producto'),
                'placeholder' => 'Valor declarado del envío',
                'desc_tip'    => true,
                'description' => __( 'El valor que desea declarar para el envío'),
                'value'       => get_post_meta( $variation->ID, '_shipping_custom_price_product_smp', true )
            )
        );

        ${"shipping_custom_price_product_smp_$variation->ID"} = true;
    }

    public function save_custom_shipping_option_to_products($post_id): void
    {
        $custom_price_product = esc_attr($_POST['_shipping_custom_price_product_smp'][ $post_id ]);
        if( isset( $custom_price_product ) )
            update_post_meta( $post_id, '_shipping_custom_price_product_smp', esc_attr( $custom_price_product ) );
    }

    public function save_variation_settings_fields($post_id): void
    {
        $custom_variation_price_product = esc_attr($_POST['_shipping_custom_price_product_smp'][ $post_id ]);
        if( ! empty( $custom_variation_price_product ) ) {
            update_post_meta( $post_id, '_shipping_custom_price_product_smp', $custom_variation_price_product );
        }
    }
}