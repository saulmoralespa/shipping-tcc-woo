<?php

class WC_Shipping_Method_Shipping_Tcc_WC extends WC_Shipping_Method
{
    protected string $debug;

    protected string $is_test;

    protected string $sender_name;

    protected string $city_sender;

    protected string $phone_sender;

    protected string $type_identification_sender;

    protected string $identification_sender;

    protected string $pass;

    protected string $packing_account;

    protected string $courier_account;

    protected string $guide_free_shipping;

    protected string $grabar_despacho_status;

    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id = 'shipping_tcc_wc';
        $this->instance_id = absint( $instance_id );
        $this->method_title = __( 'TCC' );
        $this->method_description = __( 'TCC empresa transportadora de Colombia' );
        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');

        $this->debug = $this->get_option( 'debug' );
        $this->is_test = (bool)$this->get_option( 'environment' );
        $this->type_identification_sender = $this->get_option('type_identification_sender');
        $this->identification_sender = $this->get_option('identification_sender');
        $this->pass = $this->get_option('pass');
        $this->packing_account = $this->get_option('packing_account');
        $this->courier_account = $this->get_option('courier_account');

        $this->sender_name = $this->get_option('sender_name');
        $this->city_sender = $this->get_option('city_sender');
        $this->phone_sender = $this->get_option('phone_sender');
        $this->guide_free_shipping = $this->get_option('guide_free_shipping');
        $this->grabar_despacho_status = $this->get_option('grabar_despacho_status');

        $this->supports = array(
            'settings',
            'shipping-zones'
        );

        $this->init_form_fields();
        $this->init_settings();
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function is_available($package)
    {
        return $this->enabled === 'yes' &&
            !empty($this->pass) &&
            (!empty($this->packing_account) ||
            !empty($this->courier_account));
    }

    public function init_form_fields()
    {
        $this->form_fields = include(dirname(__FILE__) . '/admin/settings.php');
    }

    public function admin_options()
    {
        ?>
        <h3><?php echo esc_html($this->title); ?></h3>
        <p><?php echo wp_kses_post(wpautop($this->method_description)); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    public function validate_text_field($key, $value)
    {
        if ($key === 'packing_account' && $value && mb_substr($value, 0, 1) !== "1"){
            WC_Admin_Settings::add_error("El número de cuenta de paquetería es inválido");
            $value = '';
        }

        if ($key === 'courier_account' && $value && mb_substr($value, 0, 1) !== "5"){
            WC_Admin_Settings::add_error("El número de cuenta de mensajería es inválido");
            $value = '';
        }

        return $value;
    }

    /**
     * @param $package
     * @return void
     * @throws Exception
     */
    public function calculate_shipping($package = array())
    {
        $country = $package['destination']['country'];

        if($country !== 'CO') return;

        $res = Shipping_Tcc_WC::get_liquitation($package);

        if (isset($res["total"]["totaldespacho"])){
            $rate = [
                'id'      => $this->id,
                'label'   => $this->title,
                'cost'    => $res['total']["totaldespacho"],
                'package' => $package
            ];

            $this->add_rate( $rate );
        }

        /*$tcc = new WebService('CLIENTETCC608W3A61CJ', '1485100');
        $params = array(
            'despacho' => array(
                'numerorelacion' => '',
                'fechahorarelacion' => '',
                'solicitudrecogida' => array(
                    'numero' => '',
                    'fecha' => '2023-09-18',
                    'ventanainicio' => '2023-09-18T14:00:00',
                    'ventanafin' => '2023-09-18T16:00:00'
                ),
                'unidadnegocio' => '1',
                'numeroremesa' => '',
                'fechadespacho' => '2023-09-18',
                'tipoidentificacionremitente' => 'CC',
                'identificacionremitente' => '901094896',
                'sederemitente' => '',
                'primernombreremitente' => '',
                'segundonombreremitente' => '',
                'primerapellidoremitente' => '',
                'segundoapellidoremitente' => '',
                'razonsocialremitente' => 'PRUEBAS PRODALIA TCC',
                'naturalezaremitente' => 'J',
                'direccionremitente' => 'CALLE 67 # 45-25',
                'contactoremitente' => '',
                'emailremitente' => '',
                'telefonoremitente' => '3506336074',
                'ciudadorigen' => '11001000',
                'tipoidentificaciondestinatario' => 'CC',
                'identificaciondestinatario' => '37878908',
                'sededestinatario' => '',
                'primernombredestinatario' => '',
                'segundonombredestinatario' => '',
                'primerapellidodestinatario' => '',
                'segundoapellidodestinatario' => '',
                'razonsocialdestinatario' => 'ANA RODRIGUEZ',
                'naturalezadestinatario' => 'N',
                'direcciondestinatario' => 'Carrera 43 # 31- 106',
                'contactodestinatario' => '',
                'emaildestinatario' => '',
                'telefonodestinatario' => '3003445557',
                'ciudaddestinatario' => '11001000',
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
                'unidad' => array(
                    array(
                        'tipounidad' => 'TIPO_UND_PAQ',
                        'tipoempaque' => '',
                        'claseempaque' => 'CLEM_CAJA',
                        'dicecontener' => '',
                        'kilosreales' => '15',
                        'largo' => '50',
                        'alto' => '50',
                        'ancho' => '50',
                        'pesovolumen' => '',
                        'valormercancia' => '100000',
                        'codigobarras' => '',
                        'numerobolsa' => '',
                        'referencias' => '',
                        'unidadesinternas' => ''
                    )
                ),
                'documentoreferencia' => array(
                    'tipodocumento' => '',
                    'numerodocumento' => 'FA',
                    'fechadocumento' => '2021-10-26'
                ),
                'numeroreferenciacliente' => '81699881400'
            )
        );

        $res = $tcc->sandbox_mode(true)->grabardespacho7($params);
        var_dump($res);*/
    }
}