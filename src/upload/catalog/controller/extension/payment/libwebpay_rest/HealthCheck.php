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

    /**
    * Constructor for initializing the configuration.
    *
    * @param array $config Array containing configuration values.
    */
    public function __construct($config) {
        $this->config = $config;
        $this->configFile = dirname(DIR_SYSTEM) . '/admin/index.php';
        $this->environment = $config['MODO'];
        $this->commerceCode = $config['COMMERCE_CODE'];
        $this->apiKey = $config['API_KEY'];
        $this->ecommerce = $config['ECOMMERCE'];
        $this->extensions  = ['openssl', 'SimpleXML', 'soap', 'dom'];
    }

   /**
    * Validates the current PHP version.
    *
    * @return array The status and the current PHP version.
    */
    private function getValidatephp(): array{
        $phpVersion = phpversion();
        $status = (version_compare($phpVersion, '8.0', '<=') && version_compare($phpVersion, '7.0', '>=')) ? 'OK' : 'Error!: Versión no soportad';
        return [
            'status' => $status,
            'version' => $phpVersion
        ];
    }

   /**
    * Checks if an extension is loaded and retrieves its version.
    *
    * @param string $extension The name of the extension to check.
    *
    * @return array The status and the extension version.
    */
    private function getCheckExtension($extension): array{
        if (!extension_loaded($extension)) {
            return [
                'status' => 'Error!',
                'version' => 'No disponible'
            ];
        }
        $extensionIsSsl = $extension === 'openssl';
        $extensionVersion = $extensionIsSsl ? OPENSSL_VERSION_TEXT : phpversion($extension);
        $version = $extensionVersion ?: 'Extensión PHP compilada. ver:' . phpversion();

        return [
            'status' => 'OK',
            'version' => $version
        ];

    }

   /**
    * Gets the currently installed OpenCart version.
    *
    * @return string The installed OpenCart version
    */
    private function getOpenCartVersion() {
        if (file_exists($this->configFile)) {
            $fileContent = file_get_contents($this->configFile);
            if (preg_match("/define\('VERSION', '([^']+)'\);/", $fileContent, $matches)) {
                return $matches[1];
            }
        }
            return 'Versión no encontrada';
    }

   /**
    * Gets the latest public release version from a specified GitHub repository.
    *
    * @param string $repository In the format 'user/repo'.
    *
    * @return string The latest release version.
    */
    private function getLastGitHubReleaseVersion($string): string{
        $baseurl = 'https://api.github.com/repos/'.$string.'/releases/latest';
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$baseurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        $content = curl_exec($ch);
        curl_close($ch);
        $con = json_decode($content, true);
        return $con['tag_name'] ?? '';
    }

   /**
    * Retrieves information about the current ecommerce setup.
    *
    * @return array Array containing the current OpenCart version,
    *               the current Transbank plugin version, and the latest Transbank plugin version.
    */
    private function getEcommerceInfo(): array{
        return [
            'current_opencart_version' => $this->getOpenCartVersion(),
            'current_transbank_plugin_version' => TransbankSdkWebpay::PLUGIN_VERSION,
            'latest_transbank_plugin_version' => $this->getLastGitHubReleaseVersion('TransbankDevelopers/transbank-plugin-opencart-webpay-rest')
        ];
    }

   /**
    * Constructs an array providing information about the eCommerce platform and the plugin.
    *
    * @param string $ecommerce
    *
    * @return array Array containing the eCommerce name,
    *               the installed version, the current plugin version, and the latest plugin version available.
    */
    private function getPluginInfo($ecommerce): array {
        $data = $this->getEcommerceInfo();
        return [
            'ecommerce' => $ecommerce,
            'ecommerce_version' => $data['current_opencart_version'],
            'current_plugin_version' => $data['current_transbank_plugin_version'],
            'last_plugin_version' => $data['latest_transbank_plugin_version']
        ];
    }

   /**
    * Lists and validates PHP extensions/modules
    *
    * @return array The values are arrays containing the status and version of each extension.
    */
    private function getExtensionsValidate(): array{
        $extensions = [];
        foreach ($this->extensions as $value) {
            $extensions[$value] = $this->getCheckExtension($value);
        }
        return $extensions;
    }

   /**
    * Gets server information. Does not include PHP info.
    *
    * @return array Array containing the PHP version, server version, and plugin information
    */
    private function getServerResume(): array {
        return [
            'php_version' => $this->getValidatephp(),
            'server_version' => ['server_software' => $_SERVER['SERVER_SOFTWARE']],
            'plugin_info' => $this->getPluginInfo($this->ecommerce)
        ];
    }

   /**
    * Creates an array with commerce information
    *
    * @return array  Array containing the environment, commerce code, and API key.
    */
    private function getCommerceInfo(): array {
        return [
            'data' => [
                'environment' => $this->environment,
                'commerce_code' => $this->commerceCode,
                'api_key' => $this->apiKey,
            ]
        ];
    }

   /**
    * Creates an array with PHP information.
    *
    * @return array Array containing the PHP information
    */
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

    /**
     * Initializes a transaction.
     *
     * @return array Array containing the status and the response.
     */
    public function setInitTransaction(): array {
        $transbankSdkWebpay = new TransbankSdkWebpay($this->config, new LogHandler());
        $amount = 990;
        $buyOrder = "_Healthcheck_";
        $sessionId = uniqid();
        $returnUrl = "https://webpay3gint.transbank.cl/filtroUnificado/initTransaction";
        $result = $transbankSdkWebpay->initTransaction($amount, $sessionId, $buyOrder, $returnUrl);

        $status = (isset($result["error"])) ? 'Error' : 'OK';
        return [
            'status' => ['string' => $status],
            'response' => preg_replace('/<!--(.*)-->/Uis', '', $result)
        ];
    }

    /**
    * Gets all information into a single method.
    *
    * @return array Array containing server resume, PHP extensions status,
    *               commerce information, and PHP info.
    */
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

   /**
    * Return commerce information and keys.
    *
    * @return string A JSON  containing the commerce information.
    */
    public function printCommerceInfo() {
        return json_encode($this->getCommerceInfo());
    }

   /**
    * Return Php information.
    *
    * @return string A JSON containing the Php information.
    */
    public function printPhpInfo() {
        return json_encode($this->getPhpInfo());
    }

   /**
    * Return the validation status of PHP extensions/modules in JSON format.
    *
    * @return string A JSON containing the validation status of PHP extensions/modules.
    */
    public function printExtensionStatus() {
        return json_encode($this->getExtensionsValidate());
    }

   /**
    * Return server information in JSON format.
    *
    * @return string A JSON containing the server information.
    */
    public function printServerResume() {
        return json_encode($this->getServerResume());
    }

   /**
    * Return the full resume information in JSON format.
    *
    * @return string A JSON containing the full resume information.
    */
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
