<?php

use OpencartWebpayRest\TransbankSdkWebpay;

require_once(DIR_SYSTEM . 'library/OpencartWebpayRest/TransbankSdkWebpay.php');
require_once('LogHandler.php');

class HealthCheck {

    var $commerceCode;
    var $apiKey;
    var $environment;
    var $extensions;
    var $versioninfo;
    var $resume;
    var $fullResume;
    var $ecommerce;
    var $config;

    public function __construct($config) {
        $this->config = $config;
        $this->environment = $config['MODO'];
        $this->commerceCode = $config['COMMERCE_CODE'];
        $this->apiKey = $config['API_KEY'];
        $this->ecommerce = $config['ECOMMERCE'];
        // extensiones necesarias
        $this->extensions = array(
            'openssl',
            'SimpleXML',
            'soap',
            'dom'
        );
    }

    // valida version de php
    private function getValidatephp(){
        if (version_compare(phpversion(), '8.0', '<=') and version_compare(phpversion(), '7.0', '>=')) {
            $this->versioninfo = array(
                'status' => 'OK',
                'version' => phpversion()
            );
        } else {
            $this->versioninfo = array(
                'status' => 'Error!: Version no soportada',
                'version' => phpversion()
            );
        }
        return $this->versioninfo;
    }

    // verifica si existe la extension y cual es la version de esta
    private function getCheckExtension($extension){
        if (extension_loaded($extension)) {
            if ($extension == 'openssl') {
                $version = OPENSSL_VERSION_TEXT;
            } else {
                $version = phpversion($extension);
                if (empty($version) or $version == null or $version === false or $version == " " or $version == "") {
                    $version = "PHP Extension Compiled. ver:".phpversion();
                }
            }
            $status = 'OK';
            $result = array(
                'status' => $status,
                'version' => $version
            );
        } else {
            $result = array(
                'status' => 'Error!',
                'version' => 'No Disponible'
            );
        }
        return $result;
    }

    // obtiene ultimas versiones
    // obtiene versiones ultima publica en github (no compatible con virtuemart) lo ideal es que el :usuario/:repo sean entregados como string
    // permite un maximo de 60 consultas por hora
    private function getLastGitHubReleaseVersion($string){
        $baseurl = 'https://api.github.com/repos/'.$string.'/releases/latest';
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$baseurl);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        //curl_setopt($ch,CURLOPT_HEADER, false);
        $content=curl_exec($ch);
        curl_close($ch);
        $con = json_decode($content, true);
        $version = array_key_exists('tag_name',$con) ? $con['tag_name'] : '';
        return $version;
    }

    // funcion para obtener info de cada ecommerce, si el ecommerce es incorrecto o no esta seteado se escapa como respuesta "NO APLICA"
    private function getEcommerceInfo($ecommerce){
        $actualversion = $this->getLastGitHubReleaseVersion('opencart/opencart');
        $lastversionplugin = $this->getLastGitHubReleaseVersion('TransbankDevelopers/transbank-plugin-opencart-webpay-rest');
        $currentplugin = TransbankSdkWebpay::PLUGIN_VERSION;
        $result = array(
            'current_ecommerce_version' => $actualversion,
            'current_plugin_version' => $currentplugin,
            'last_version_plugin' => $lastversionplugin
        );
        return $result;
    }

    // creacion de retornos
    // arma array que entrega informacion del ecommerce: nombre, version instalada, ultima version disponible
    private function getPluginInfo($ecommerce){
        $data = $this->getEcommerceInfo($ecommerce);
        $result = array(
            'ecommerce' => $ecommerce,
            'ecommerce_version' => $data['current_ecommerce_version'],
            'current_plugin_version' => $data['current_plugin_version'],
            'last_plugin_version' => $data['last_version_plugin'] // ultimo declarado
        );
        return $result;
    }

    // arma array con informacion del ultimo plugin compatible con el ecommerce
    /*
    vers_product:
    1 => WebPay Soap
    2 => WebPay REST
    3 => PatPass
    4 => OnePay
    */


    // lista y valida extensiones/ modulos de php en servidor ademas mostrar version
    private function getExtensionsValidate(){
        foreach ($this->extensions as $value) {
            $this->resExtensions[$value] = $this->getCheckExtension($value);
        }
        return $this->resExtensions;
    }

    // crea resumen de informacion del servidor. NO incluye a PHP info
    private function getServerResume() {
        // arma array de despliegue
        $this->resume = array(
            'php_version' => $this->getValidatephp(),
            'server_version' => array('server_software' => $_SERVER['SERVER_SOFTWARE']),
            'plugin_info' => $this->getPluginInfo($this->ecommerce)
        );
        return $this->resume;
    }

    // crea array con la informacion de comercio para posteriormente exportarla via json
    private function getCommerceInfo() {
        $result = array(
            'environment' => $this->environment,
            'commerce_code' => $this->commerceCode,
            'api_key' => $this->apiKey,
        );
        return array('data' => $result);
    }

    // guarda en array informacion de funcion phpinfo
    private function getPhpInfo(){
        ob_start();
        phpinfo();
        $info = ob_get_contents();
        ob_end_clean();
        $newinfo = strstr($info, '<table>');
        $newinfo = strstr($newinfo, '<h1>PHP Credits</h1>',true);
        $return = array('string' => array('content' => str_replace('</div></body></html>','', $newinfo)));
        return $return;
    }

    public function setInitTransaction(){
        $transbankSdkWebpay = new TransbankSdkWebpay($this->config, new LogHandler());
        $amount = 990;
        $buyOrder = "_Healthcheck_";
        $sessionId = uniqid();
        $returnUrl = "https://webpay3gint.transbank.cl/filtroUnificado/initTransaction";
        $result = $transbankSdkWebpay->initTransaction($amount, $sessionId, $buyOrder, $returnUrl);
        if ($result) {
            if (!empty($result["error"]) && isset($result["error"])) {
                $status = 'Error';
            } else {
                $status = 'OK';
            }
        } else {
            if (array_key_exists('error', $result)) {
                $status =  "Error";
            }
        }
        $response = array(
            'status' => array('string' => $status),
            'response' => preg_replace('/<!--(.*)-->/Uis', '', $result)
        );
        return $response;
    }

    //compila en solo un metodo toda la informacion obtenida, lista para imprimir
    private function getFullResume() {
        $this->fullResume = array(
            //'validate_init_transaction' => $this->setInitTransaction(),
            'server_resume' => $this->getServerResume(),
            'php_extensions_status'  => $this->getExtensionsValidate(),
            'commerce_info' => $this->getCommerceInfo(),
            'php_info' => $this->getPhpInfo()
        );
        return $this->fullResume;
    }

    private function setpostinstall() {
        return false;
    }

    // imprime informacion de comercio y llaves
    public function printCommerceInfo() {
        return json_encode($this->getCommerceInfo());
    }

    public function printPhpInfo() {
        return json_encode($this->getPhpInfo());
    }


    // imprime en formato json la validacion de extensiones / modulos de php
    public function printExtensionStatus() {
        return json_encode($this->getExtensionsValidate());
    }

    // imprime en formato json informacion del servidor
    public function printServerResume() {
        return json_encode($this->getServerResume());
    }

    // imprime en formato json el resumen completo
    public function printFullResume() {
        return json_encode($this->getFullResume());
    }

    public function getInitTransaction() {
        return json_encode($this->setInitTransaction());
    }

    public function getpostinstallinfo() {
        return json_encode($this->setpostinstall());
    }
}
?>
