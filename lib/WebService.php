<?php

namespace Saulmoralespa\Tcc;

use SoapClient;
use Exception;

class WebService
{

    const BASE_URL_SERVICES = 'http://clientes.tcc.com.co/servicios/';

    const SANDBOX_BASE_URL_SERVICES = 'http://clientes.tcc.com.co/preservicios/';

    const SANDBOX_URL_SHIPMENTS = 'http://preclientes.tcc.com.co/api/clientes/remesasws?wsdl';

    const URL_SHIPMENTS = 'http://tccremesas.saulmoralespa.com/api/clientes/remesasws?wsdl';

    private static bool $sandbox = false;

    public function __construct(
        private $pass,
        private $account
    )
    {

    }

    /**
     * @param bool $mode
     * @return $this
     */
    public function sandbox_mode(bool $mode = false): static
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

    private function getUrlShipment(): string
    {
        if (self::$sandbox) return self::SANDBOX_URL_SHIPMENTS;
        return self::URL_SHIPMENTS;
    }

    /**
     * @param array $params
     * @return Exception|array
     * @throws Exception
     */
    public function consultarLiquidacion2(array $params): Exception|array
    {
        $params = array_merge($params, [
            'Clave' => $this->pass
        ]);
        $operation = strtolower(__FUNCTION__);
        return $this->callSoap($this->getUrlLiquidation(),$operation, $params, true);
    }

    /**
     * @param array $params
     * @return Exception|array|null
     * @throws Exception
     */
    public function grabarDespacho7(array $params):Exception|array
    {
        $params['despacho']['clave'] = isset($params['despacho']) ? $this->pass : '';
        $params['despacho']['cuentaremitente'] = isset($params['despacho']) ? $this->account : '';
        $operation = strtolower(__FUNCTION__);
        return $this->callSoap($this->getUrlShipment(),$operation, $params);
    }

    /**
     * @param array $params
     * @return Exception|array
     * @throws Exception
     */
    public function anularDespacho(array $params):Exception|array
    {
        $params['clave'] = $this->pass;
        $params['cuentaremitente'] = $this->account;
        $operation = strtolower(__FUNCTION__);
        return $this->callSoap($this->getUrlShipment(),$operation, $params);
    }

    /**
     * @param array $params
     * @return array|Exception
     * @throws Exception
     */
    public function ConsultarInformacionRemesasEstadosUEN(array $params): Exception|array
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
            'exceptions' => true,
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
     * @return array|Exception
     * @throws Exception
     */
    private function callSoap($endpoint, $operation, $params, bool $liquidation = false): Exception|array
    {
        try{
            $client = new SoapClient($endpoint, $this->optionsSoap());
            $nameFunctionResult = $operation . "Result";
            $res = $client->$operation($params);
            $json = json_encode($res);
            $res = json_decode($json, true);

            if ($liquidation){
                $res = $res[$nameFunctionResult];
            }

            self::checkErros($res);

            return $res;
        }catch (\SoapFault $soapFault) {
            throw new \Exception($soapFault->getMessage());
        }catch (Exception $ex){
            throw new  Exception($ex->getMessage());
        }
    }

    /**
     * @param $res
     * @return void
     * @throws Exception
     */
    private static function checkErros($res): void
    {
        if(isset($res['remesa']) && empty($res['remesa'])){
            throw new Exception($res['mensaje']);
        }

        if (isset($res['respuesta']['codigo']) &&
            $res['respuesta']['codigo'] !== "0"){
            $message = $res['respuesta']['codigo']['mensaje'] ?? '';
            $message = $res['respuesta']['mensajeinterno'] ?? $message;
            throw new Exception($message);
        }
    }
}