<?php

use Saulmoralespa\Tcc\WebService;

class Shipping_Tcc_WC
{
    protected static ?WebService $tcc = null;

    private static object|null $settings = null;

    public static function get_instance(): ?WebService
    {
        if (isset(self::$settings) && isset(self::$tcc)) return self::$tcc;

        self::$settings = (object)get_option('woocommerce_shipping_tcc_wc_settings');

        if(self::$settings->pass && self::$settings->packing_account){
            self::$tcc = new WebService(self::$settings->pass, self::$settings->packing_account);
            $is_test = (bool)(integer)self::$settings->environment;
            self::$tcc->sandbox_mode($is_test);
        }

        return self::$tcc;
    }


    public static function get_liquitation($package = []): array|null
    {
        if (!self::get_instance()) return null;

        $state_destination = $package['destination']['state'];
        $city_destination  = $package['destination']['city'];
        $items = $package['contents'];
        $city_destination  = self::get_city($city_destination);
        $total_valorization = 0;

        if (!isset($package['contents']) ) return null;

        $res = null;

        try {

            $data = self::data_products($items);
            $destine = self::get_code_city($state_destination, $city_destination);

            $params = [
                'Liquidacion' => [
                    'tipoenvio' => '1', //Siempre debe ir 1
                    'idciudadorigen' => self::$settings->city_sender, //Ciudad origen con código Dane
                    'idciudaddestino' => $destine, //Ciudad destino con código Dane
                    'valormercancia' => $total_valorization,
                    'boomerang' => '0', //Debe ir siempre 0
                    'cuenta' => $data['account'],
                    'fecharemesa' => wp_date('Y-m-d'),
                    'idunidadestrategicanegocio' => $data['idunidadestrategicanegocio'],
                    'unidades' => $data['units']
                ]
            ];
            $res = self::get_instance()->consultarLiquidacion2($params);
        }catch (\Exception $ex){
            shipping_tcc_woo_stw()->log($ex->getMessage());
        }

        return $res;
    }

    public static function generate_guide($order_id, $previous_status, $next_status): void
    {
        $sub_orders = get_children( array( 'post_parent' => $order_id, 'post_type' => 'shop_order' ),  ARRAY_A);
        $orders =  $sub_orders ?: [['ID' => $order_id]];

        foreach ($orders as $sub) {
            $order_id = $sub['ID'];
            self::exec_guide($order_id, $next_status);
        }
    }

    public static function exec_guide($order_id, string $new_status)
    {
        $order = wc_get_order($order_id);

        if (!$order->has_shipping_method(SHIPPING_TCC_WOO_STW_ID)) return;

        $next_status = wc_get_order_status_name($new_status);
        $numero_remesa = get_post_meta($order_id, '_shipping_tcc_numero_remesa', true);

        if(!self::get_instance() ||
            self::$settings->enabled === 'no' ||
            $next_status !== wc_get_order_status_name(self::$settings->grabar_despacho_status) ||
            !empty($numero_remesa) ||
            empty(self::$settings->license_key) ||
            (self::$settings->guide_free_shipping === 'yes' && $order->get_shipping_total() > 0)
        ) return;

        $order_id_origin = $order->get_parent_id() ? $order->get_parent_id() : $order->get_id();
        $order = new WC_Order($order_id_origin);
        $razonsocialdestinatario = $order->get_shipping_first_name() ? $order->get_shipping_first_name() .
            " " . $order->get_shipping_last_name() : $order->get_billing_first_name() .
            " " . $order->get_billing_last_name();
        $direcciondestinatario = $order->get_shipping_address_1() ? $order->get_shipping_address_1() .
            " " . $order->get_shipping_address_2() : $order->get_billing_address_1() .
            " " . $order->get_billing_address_2();
        $state_destination = $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state();
        $city_destination  = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
        $city_destination  = self::get_city($city_destination);
        $ciudaddestinatario = self::get_code_city($state_destination, $city_destination);
        $items = $order->get_items();
        $data = self::data_products($items, true);

        try {
            $params = [
                'despacho' => array(
                    'numerorelacion' => '',
                    'fechahorarelacion' => '',
                    'solicitudrecogida' => array(
                        'numero' => '',
                        'fecha' => wp_date('Y-m-d'),
                        'ventanainicio' => wp_date('Y-m-d\TH:i:s'),
                        'ventanafin' => wp_date('Y-m-d\TH:i:s')
                    ),
                    'unidadnegocio' => 1, // 1 paqueteria, 2 paqueteria
                    'numeroremesa' => '',
                    'fechadespacho' => wp_date('Y-m-d'),
                    'tipoidentificacionremitente' => self::$settings->type_identification_sender ?? '',
                    'identificacionremitente' => substr(self::$settings->identification_sender, 0, 10) ?? '',
                    'sederemitente' => '',
                    'primernombreremitente' => '',
                    'segundonombreremitente' => '',
                    'primerapellidoremitente' => '',
                    'segundoapellidoremitente' => '',
                    'razonsocialremitente' => substr(self::$settings->sender_name, 0, 60),
                    'naturalezaremitente' => self::$settings->type_identification_sender === 'NIT' ? 'J' : 'N', // J Jurídicon N Natural
                    'direccionremitente' => self::$settings->address_sender,
                    'contactoremitente' => '',
                    'emailremitente' => '',
                    'telefonoremitente' => self::$settings->phone_sender ?? '',
                    'ciudadorigen' => self::$settings->city_sender . '000',
                    'tipoidentificaciondestinatario' => '',
                    'identificaciondestinatario' => '',
                    'sededestinatario' => '',
                    'primernombredestinatario' => '',
                    'segundonombredestinatario' => '',
                    'primerapellidodestinatario' => '',
                    'segundoapellidodestinatario' => '',
                    'razonsocialdestinatario' => $razonsocialdestinatario,
                    'naturalezadestinatario' => '',
                    'direcciondestinatario' => $direcciondestinatario,
                    'contactodestinatario' => '',
                    'emaildestinatario' => $order->get_billing_email(),
                    'telefonodestinatario' => $order->get_billing_phone(),
                    'ciudaddestinatario' => $ciudaddestinatario . '000',
                    'barriodestinatario' => '',
                    'totalpeso' => '',
                    'totalpesovolumen' => '',
                    'totalvalormercancia' => '',
                    'formapago' => '',
                    'observaciones' => '',
                    'llevabodega' => '',
                    'recogebodega' => '',
                    'centrocostos' => '',
                    'totalvalorproducto' => '',
                    'tiposervicio' => '',
                    'unidad' => $data['units'],
                    'documentoreferencia' => array(
                        'tipodocumento' => '',
                        'numerodocumento' => 'FA',
                        'fechadocumento' => wp_date('Y-m-d')
                    ),
                    'numeroreferenciacliente' => $order_id_origin

                )
            ];
            $res = self::$tcc->grabarDespacho7($params);

            $numero_remesa = $res['remesa'] ?? '';

            if(!$numero_remesa) return;

            $note = sprintf( __( 'Número de remesa: %s' ), $numero_remesa );
            $order->add_order_note($note);
            update_post_meta($order_id, '_shipping_tcc_numero_remesa', $numero_remesa);
        }catch (\Exception $ex){
            shipping_tcc_woo_stw()->log($ex->getMessage());
        }
    }


    public static function data_products(array $items, $guide = false) : array
    {

        $units = [];
        $total_weight = 0;

        foreach ($items as $item) {
            $product_id = $guide ? $item['product_id'] : $item['data']->get_id();
            $product = wc_get_product($product_id);
            $quantity = $item['quantity'];

            if ($item['variation_id'] > 0 && in_array($item['variation_id'], $product->get_children()))
                $product = wc_get_product($item['variation_id']);

            if (!$product->get_weight() || !$product->get_length()
                || !$product->get_width() || !$product->get_height())
                break;

            $custom_price_product = get_post_meta($product->get_id(), '_shipping_custom_price_product_smp', true);
            $price = $custom_price_product ?:   $product->get_price();
            $total_valorization = $price * $quantity;
            $weight = floatval( $product->get_weight() ) * floatval( $quantity );
            $height = $product->get_height() * $quantity;
            $weight_volume = ($product->get_length() * $product->get_width() * $height) / 5000;
            $total_weight += $weight;

            $units[] = [
                'tipounidad' => 'TIPO_UND_PAQ',
                'claseempaque' => '', // CLEM_CAJA, CLEM_SOBRE, CLEM_LIO
                'kilosreales' => $weight,
                'valormercancia' => $total_valorization,
                'numerounidades' => 1,
                'pesoreal' => $weight,
                'pesovolumen' => $weight_volume,
                'alto' => $height,
                'largo' => $product->get_length(),
                'ancho' => $product->get_width(),
                'tipoempaque' => ''
            ];
        }

        if (self::$settings->packing_account &&
            self::$settings->courier_account){
            $account = ($total_weight > 5) ? self::$settings->packing_account : self::$settings->courier_account;
            $idunidadestrategicanegocio = ($total_weight > 5) ? 1 : 2;
        }elseif (self::$settings->packing_account){
            $account = self::$settings->packing_account;
            $idunidadestrategicanegocio = 1;
        }else{
            $account = self::$settings->courier_account;
            $idunidadestrategicanegocio = 2;
        }

        $data = [
            'account' => $account,
            'idunidadestrategicanegocio' => $idunidadestrategicanegocio,
            'units' => $units
        ];

        return apply_filters('shipping_tcc_data_products', $data, $items, $guide);
    }

    public static function get_city(string $city_destination): string
    {
        $city_destination = self::clean_string($city_destination);
        return self::clean_city($city_destination);
    }

    /**
     * @param $state
     * @param $city
     * @param string $country
     * @return false|int|string
     */
    public static function get_code_city($state, $city, string $country = 'CO')
    {
        $name_state = self::name_destination($country, $state);

        $address = "$city - $name_state";

        if (self::$settings->debug === 'yes'){
            $city = self::$settings->city_sender;
            shipping_tcc_woo_stw()->log("origin: $city: $address");
        }

        $cities = include dirname(__FILE__) . '/cities.php';

        $destine = array_search($address, $cities);
        if (!$destine)
            $destine = array_search($address, self::clean_cities($cities));

        return $destine;
    }

    public static function name_destination($country, $state_destination): string
    {
        $countries_obj = new WC_Countries();
        $country_states_array = $countries_obj->get_states();

        $name_state_destination = '';

        if (!isset($country_states_array[$country][$state_destination]))
            return $name_state_destination;

        $name_state_destination = $country_states_array[$country][$state_destination];
        return self::clean_string($name_state_destination);
    }

    public static function clean_cities(array $cities): array
    {
        foreach ($cities as &$city) {
            $city = self::clean_string($city);
        }

        return $cities;
    }

    public static function clean_string($string): string
    {
        $not_permitted = array("á", "é", "í", "ó", "ú", "Á", "É", "Í",
            "Ó", "Ú", "ñ");
        $permitted = array("a", "e", "i", "o", "u", "A", "E", "I", "O",
            "U", "n");
        return str_replace($not_permitted, $permitted, $string);
    }

    public static function clean_city($city): string
    {
        return $city === 'Bogota D.C' ? 'Bogota' : $city;
    }

}