<?php

use OpencartWebpayRest\TransbankSdkWebpay;

require_once(DIR_SYSTEM . 'library/OpencartWebpayRest/TransbankSdkWebpay.php');
require_once('LogHandler.php');

class HealthCheck {

    private $commerceCode;
    private $apiKey;
    private $environment;
    private $extensions;
    private $ecommerce;
    private $config;

    public function __construct($config) {
        $this->config = $config;
        $this->environment = $config['MODO'];
        $this->commerceCode = $config['COMMERCE_CODE'];
        $this->apiKey = $config['API_KEY'];
        $this->ecommerce = $config['ECOMMERCE'];
        // extensiones necesarias
        $this->extensions  = ['openssl', 'SimpleXML', 'soap', 'dom'];
    }

    // valida version de php
    private function getValidatephp(): array{
        $phpVersion = phpversion();
        $status = (version_compare($phpVersion, '8.0', '<=') && version_compare($phpVersion, '7.0', '>=')) ? 'OK' : 'Error!: Unsupported version';
        return [
            'status' => $status,
            'version' => $phpVersion
        ];
    }

    // verifica si existe la extension y cual es la version de esta
    private function getCheckExtension($extension): array{
        if (!extension_loaded($extension)) {
            return [
                'status' => 'Error!',
                'version' => 'Unavailable'
            ];
        }
        $extensionIsSsl = $extension === 'openssl';
        $extensionVersion = $extensionIsSsl ? OPENSSL_VERSION_TEXT : phpversion($extension);
        $version = $extensionVersion ?: 'PHP Extension Compiled. ver:' . phpversion();

        return [
            'status' => 'OK',
            'version' => $version
        ];

    }

    // obtiene ultimas versiones
    // obtiene versiones ultima publica en github (no compatible con virtuemart) lo ideal es que el :usuario/:repo sean entregados como string
    // permite un maximo de 60 consultas por hora
    private function getLastGitHubReleaseVersion($string): string{
        $baseurl = 'https://api.github.com/repos/'.$string.'/releases/latest';
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$baseurl);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        $content=curl_exec($ch);
        curl_close($ch);
        $con = json_decode($content, true);
        return $con['tag_name'] ?? '';
    }

    // funcion para obtener info de cada ecommerce, si el ecommerce es incorrecto o no esta seteado se escapa como respuesta "NO APLICA"
    private function getEcommerceInfo(): array{
        return [
            'current_ecommerce_version' => TransbankSdkWebpay::PLUGIN_VERSION,
            'last_ecommerce_version' => $this->getLastGitHubReleaseVersion('opencart/opencart'),
            'current_plugin_version' => TransbankSdkWebpay::PLUGIN_VERSION
        ];
    }

    // creacion de retornos
    // arma array que entrega informacion del ecommerce: nombre, version instalada, ultima version disponible
    private function getPluginInfo($ecommerce): array {
        $data = $this->getEcommerceInfo();
        return [
            'ecommerce' => $ecommerce,
            'ecommerce_version' => $data['current_ecommerce_version'],
            'current_plugin_version' => $data['current_plugin_version'],
            'last_plugin_version' => $this->getPluginLastVersion($ecommerce, $data['current_ecommerce_version'])
        ];
    }

    // arma array con informacion del ultimo plugin compatible con el ecommerce
    /*
    vers_product:
    1 => WebPay Soap
    2 => WebPay REST
    3 => PatPass
    4 => OnePay
    */
    private function getPluginLastVersion($ecommerce, $currentversion){
        return 'Indefinido';
    }

    // lista y valida extensiones/ modulos de php en servidor ademas mostrar version
    private function getExtensionsValidate(): array{
        $extensions = [];
        foreach ($this->extensions as $value) {
            $extensions[$value] = $this->getCheckExtension($value);
        }
        return $extensions;
    }

    // crea resumen de informacion del servidor. NO incluye a PHP info
    private function getServerResume(): array {
        return [
            'php_version' => $this->getValidatephp(),
            'server_version' => ['server_software' => $_SERVER['SERVER_SOFTWARE']],
            'plugin_info' => $this->getPluginInfo($this->ecommerce)
        ];
    }

    // crea array con la informacion de comercio para posteriormente exportarla via json
    private function getCommerceInfo(): array {
        return [
            'data' => [
                'environment' => $this->environment,
                'commerce_code' => $this->commerceCode,
                'api_key' => $this->apiKey,
            ]
        ];
    }

    // guarda en array informacion de funcion phpinfo
    private function getPhpInfo(): array {
        ob_start();
        phpinfo();
        $info = ob_get_clean();
        $newinfo = strstr($info, '<table>');
        $newinfo = strstr($newinfo, '<h1>PHP Credits</h1>', true);
        return [
            'string' => ['content' => str_replace('</div></body></html>', '', $newinfo)]
        ];
    }

    public function setInitTransaction(): array {
        $transbankSdkWebpay = new TransbankSdkWebpay($this->config, new LogHandler());
        $amount = 990;
        $buyOrder = "_Healthcheck_";
        $sessionId = uniqid();
        $returnUrl = "https://webpay3gint.transbank.cl/filtroUnificado/initTransaction";
        $result = $transbankSdkWebpay->initTransaction($amount, $sessionId, $buyOrder, $returnUrl);

        $status = (!empty($result["error"]) && isset($result["error"])) ? 'Error' : 'OK';
        if (!$result && array_key_exists('error', $result)) {
            $status = 'Error';
        }

        return [
            'status' => ['string' => $status],
            'response' => preg_replace('/<!--(.*)-->/Uis', '', $result)
        ];

    }

    //compila en solo un metodo toda la informacion obtenida, lista para imprimir
    private function getFullResume(): array {
        return [
            'server_resume' => $this->getServerResume(),
            'php_extensions_status' => $this->getExtensionsValidate(),
            'commerce_info' => $this->getCommerceInfo(),
            'php_info' => $this->getPhpInfo()
        ];
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
