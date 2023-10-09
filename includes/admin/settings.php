<?php

$order_statuses = wc_get_order_statuses();

return apply_filters(
    'shipping_tcc_woo_stw_settings',
    [
        'enabled' => array(
            'title' => __('Activar/Desactivar'),
            'type' => 'checkbox',
            'label' => __('Activar TCC'),
            'default' => 'no'
        ),
        'title'        => array(
            'title'       => __( 'Título método de envío' ),
            'type'        => 'text',
            'description' => __( 'Esto controla el título que el usuario ve durante el pago' ),
            'default'     => __( 'TCC' ),
            'desc_tip'    => true
        ),
        'debug'        => array(
            'title'       => __( 'Depurador' ),
            'label'       => __( 'Habilitar el modo de desarrollador' ),
            'type'        => 'checkbox',
            'default'     => 'yes',
            'description' => __( 'Habilitar el modo de depuración para mostrar información de depuración.' ),
            'desc_tip' => true
        ),
        'environment' => array(
            'title' => __('Entorno'),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __('Entorno de pruebas o producción'),
            'desc_tip' => true,
            'default' => '1',
            'options'     => array(
                '0'    => __( 'Producción'),
                '1' => __( 'Pruebas')
            )
        ),
        'sender'  => array(
            'title' => __( 'Remitente' ),
            'type'  => 'title',
            'description' => __( 'Información requerida del remitente' )
        ),
        'city_sender' => array(
            'title' => __('Ciudad del remitente'),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __('Ciudad desde donde será enviada la mercancia'),
            'desc_tip' => true,
            'default' => true,
            'options'     => include dirname(__FILE__) . '/../cities.php'
        ),
        'sender_name' => array(
            'title'       => __( 'Nombre remitente' ),
            'type'        => 'text',
            'description' => __( 'Razón social del remitente, nombre completo' ),
            'default'     => get_bloginfo('name'),
            'desc_tip'    => true
        ),
        'phone_sender' => array(
            'title' => __( 'Teléfono del remitente' ),
            'type'  => 'text',
            'description' => __( 'Número telefónico del cliente remitente, si no se envia se tomará con base al número de cuenta' ),
            'desc_tip' => true
        ),
        'type_identification_sender' => array(
            'title' => __('Tipo identificación'),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __('Tipo identificación del remitente NIT o Cédula'),
            'desc_tip' => true,
            'default' => 'NIT',
            'options'     => array(
                'NIT'    => __('(NIT) Número de indentificación tributaria'),
                'CC' => __('Cédula de ciudadania')
            ),
        ),
        'identification_sender' => array(
            'title' => __('Número de identificación'),
            'type'        => 'number',
            'description' => __('Número de identificación del remitente'),
            'desc_tip' => true
        ),
        'params_title' => array(
            'title'       => __('Clave y números de cuentas TCC'),
            'type'        => 'title',
            'description' => __('Número de identificación del remitente'),
        ),
        'pass' => array(
            'title' => __('Clave'),
            'type'  => 'password',
            'description' => __('Clave o usuario asignado por TCC'),
            'desc_tip' => true
        ),
        'packing_account' => array(
            'title' => __('Cuenta paquetería'),
            'type'        => 'number',
            'description' => __('Número de cuenta paquetería'),
            'desc_tip' => true
        ),
        'courier_account' => array(
            'title' => __('Cuenta mensajería'),
            'type'        => 'number',
            'description' => __('Número de cuenta mensajería'),
            'desc_tip' => true
        ),
        'grabar_despacho_title' => array(
            'title'       => __('Grabación de despacho'),
            'type'        => 'title',
            'description' => __('Configuración para la solicitud de regogidas'),
        ),
        'guide_free_shipping' => array(
            'title'       => __( 'Grabar despachos cuando el envío es gratuito' ),
            'label'       => __( 'Habilitar la grabación de despachos para envíos gratuitos' ),
            'type'        => 'checkbox',
            'default'     => 'no',
            'description' => __( 'Permite la generación de guías cuando el envío es gratuito' ),
            'desc_tip' => true
        ),
        'grabar_despacho_status' => array(
            'title' => __( 'Estado de grabación de despacho' ),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __( 'Por defecto la grabación del despacho se genera cuando el estado del pedido cambia a procesando' ),
            'desc_tip' => false,
            'default' => 'wc-processing',
            'options' => $order_statuses
        )
    ]
);