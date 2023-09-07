<?php

use Saulmoralespa\Tcc\WebService;
class Shipping_Tcc_WC extends WC_Shipping_Method_Shipping_Tcc_WC
{
    protected WebService $tcc;
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);
        $this->tcc = new WebService($this->pass);
    }

    /**
     * @throws Exception
     */
    public static function get_liquitation($package)
    {
        $instance = new self();
        $state_destination = $package['destination']['state'];
        $city_destination  = $package['destination']['city'];
        $city_destination  = self::get_city($city_destination);
        $total_valorization = 0;
        $total_weight = 0;
        $units = [];

        if (isset($package['contents']) ){
            foreach ( $package['contents'] as $item ) {
                /**
                 * @var  $product WC_Product
                 */
                $product = $item['data'];
                $quantity = $item['quantity'];


                if ($item['variation_id'] > 0 && in_array($item['variation_id'], $product->get_children()))
                    $product = wc_get_product($item['variation_id']);

                if (!is_numeric($product->get_weight()) || !is_numeric($product->get_length())
                    || !is_numeric($product->get_width()) || !is_numeric($product->get_height()))
                    break;

                $custom_price_product = get_post_meta($product->get_id(), '_shipping_custom_price_product_smp', true);
                $price = $custom_price_product ?: $product->get_price();
                $total_valorization += $price * $quantity;

                $weight = floatval( $product->get_weight() ) * floatval( $quantity );
                $height = $product->get_height() * $quantity;
                $weight_volume = ($product->get_length() * $product->get_width() * $height) / 5000;
                $total_weight += $weight;

                $units['unidad'][] = [
                    'numerounidades' => '1',
                    'pesoreal' => $weight,
                    'pesovolumen' => $weight_volume,
                    'alto' => $height,
                    'largo' => $product->get_length(),
                    'ancho' => $product->get_width(),
                    'tipoempaque' => ''
                ];

            }
        }

        if ($total_weight > 5){
            $account = $instance->packing_account;
            $idunidadestrategicanegocio = 1;
        }else{
            $account = $instance->courier_account;
            $idunidadestrategicanegocio = 2;
        }

        $destine = self::get_code_city($state_destination, $city_destination);

        $res = [];

        try {
            $params = [
                'Liquidacion' => [
                    'tipoenvio' => '1',
                    'idciudadorigen' => $instance->city_sender,
                    'idciudaddestino' => $destine,
                    'valormercancia' => $total_valorization,
                    'boomerang' => '0',
                    'cuenta' => $account,
                    'fecharemesa' => wp_date('Y-m-d'),
                    'idunidadestrategicanegocio' => $idunidadestrategicanegocio,
                    'unidades' => $units
                ]
            ];
            shipping_tcc_woo_stw()->log($params);
            $res = $instance->tcc->sandbox_mode($instance->is_test)->consultarLiquidacion2($params);
        }catch (\Exception $ex){
            shipping_tcc_woo_stw()->log($ex->getMessage());
        }

        return $res;
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
    public static function get_code_city($state, $city, string $country = 'CO'): bool|int|string
    {
        $instance = new self();
        $name_state = $instance::name_destination($country, $state);

        $address = "$city - $name_state";

        if ($instance->debug === 'yes')
            shipping_tcc_woo_stw()->log("origin: $instance->city_sender: $address");

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

    public static function clean_cities($cities)
    {
        foreach ($cities as $key => $value) {
            $cities[$key] = self::clean_string($value);
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