<?php

namespace Saulmoralespa\Tcc;

use Exception;
use SoapClient;

class WebService
{

    const BASE_URL_SERVICES = 'http://clientes.tcc.com.co/servicios/';

    const SANDBOX_BASE_URL_SERVICES = 'http://clientes.tcc.com.co/preservicios/';

    const SANDBOX_URL_SHIPMENTS = 'http://preclientes.tcc.com.co/api/clientes/remesasws?wsdl';

    const URL_SHIPMENTS = 'http://tccremesas.saulmoralespa.com/?wsdl';
    private static bool $sandbox = false;
    private string $pass;

    public function __construct(
        $pass
    )
    {
        $this->pass = $pass;
    }

    /**
     * @param bool $mode
     * @return $this
     */
    public function sandbox_mode(bool $mode = false): WebService
    {
        if ($mode){
            self::$sandbox = true;
        }

        return $this;
    }

    /**
     * @return string
     */
    private function getUrlLiquidation(): string
    {
        if (self::$sandbox){
            $url = self::SANDBOX_BASE_URL_SERVICES;
        }else{
            $url = self::BASE_URL_SERVICES;
        }

        $url .= "liquidacionacuerdos.asmx?wsdl";

        return $url;
    }

    /**
     * @return string
     */
    private function getUrlInfoShipment(): string
    {
        if (self::$sandbox){
            $url = self::SANDBOX_BASE_URL_SERVICES;
        }else{
            $url = self::BASE_URL_SERVICES;
        }

        $url .= "informacionremesas.asmx?wsdl";

        return $url;
    }

    /**
     * @return string
     */
    private function getUrlShipment(): string
    {
        if (self::$sandbox) return self::SANDBOX_URL_SHIPMENTS;
        return self::URL_SHIPMENTS;
    }

    /**
     * @param array $params
     * @return array|Exception
     * @throws Exception
     */
    public function consultarLiquidacion2(array $params)
    {
        $params = array_merge($params, [
            'Clave' => $this->pass
        ]);
        $operation = strtolower(__FUNCTION__);
        return $this->callSoap($this->getUrlLiquidation(),$operation, $params, true);
    }

    /**
     * @param array $params
     * @return array|Exception
     * @throws Exception
     */
    public function grabarDespacho7(array $params)
    {
        $params['despacho']['clave'] = isset($params['despacho']) ? $this->pass : '';
        //$params['despacho']['cuentaremitente'] = isset($params['despacho']) ? $this->account : '';
        $operation = strtolower(__FUNCTION__);
        return $this->callSoap($this->getUrlShipment(),$operation, $params);
    }

    /**
     * @param array $params
     * @return array|Exception
     * @throws Exception
     */
    public function ConsultarInformacionRemesasEstadosUEN(array $params)
    {
        $params = array_merge($params, [
            'Clave' => $this->pass
        ]);
        return $this->callSoap($this->getUrlInfoShipment(),__FUNCTION__, $params);
    }

    /**
     * @return array
     */
    private function optionsSoap(): array
    {
        return [
            "trace" => true,
            'exceptions' => false,
            "soap_version"  => SOAP_1_1,
            "connection_timeout"=> 60,
            "encoding"=> "utf-8",
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'ciphers'=>'AES256-SHA'
                ]
            ]),
            'cache_wsdl' => WSDL_CACHE_NONE
        ];
    }

    /**
     * @param $endpoint
     * @param $operation
     * @param $params
     * @param bool $liquidation
     * @return Exception|array
     * @throws Exception
     */
    private function callSoap($endpoint, $operation, $params, bool $liquidation = false)
    {
        try{
            $client = new SoapClient($endpoint, $this->optionsSoap());
            $nameFunctionResult = $operation . "Result";
            $res = $client->$operation($params);
            $json = json_encode($res);
            $res = json_decode($json, true);

            if ($liquidation) $res = $res[$nameFunctionResult];

            if (isset($res['respuesta']['codigo']) &&
                $res['respuesta']['codigo'] !== "0"){
                $message = $res['respuesta']['codigo']['mensaje'] ?? "Código interno: {$res['respuesta']['codigo']}";
                throw new Exception($message);
            }

            return $res;
        }catch (Exception $ex){
            throw new  Exception($ex->getMessage());
        }
    }
}