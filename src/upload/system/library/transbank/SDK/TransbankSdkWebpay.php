<?php
namespace Transbank\SDK;
use Transbank\Exceptions\TransactionCreationException;
use Transbank\Exceptions\TransactionCommitException;
require_once DIR_SYSTEM . '/library/Transbank/vendor/autoload.php';

use Transbank\Webpay\Configuration;
use Transbank\Webpay\Webpay;
use Transbank\Webpay\WebpayPlus;
use Transbank\Webpay\WebpayPlus\Transaction;

class TransbankSdkWebpay
{

    const PLUGIN_VERSION = '1.0.0'; //version of plugin payment

    public function __construct($config = null, $log = null)
    {
        $this->log = $log;
        if (isset($config)) {
            $environment = isset($config["MODO"]) ? $config["MODO"] : 'TEST';
            if ($environment != "TEST") {
                WebpayPlus::configureForProduction($config['COMMERCE_CODE'], $config['API_KEY']);
            }
        }
    }

    public function initTransaction($amount, $sessionId, $buyOrder, $returnUrl)
    {
        $result = [];
        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('initTransaction - amount: ' . $amount . ', sessionId: ' . $sessionId . ', buyOrder: ' . $buyOrder . ', txDate: ' . $txDate . ', txTime: ' . $txTime);

            $response = (new Transaction)->create($buyOrder, $sessionId, $amount, $returnUrl);
            $this->log->logInfo('initTransaction - initResult: ' . json_encode($response));
            if (isset($response) && isset($response->url) && isset($response->token)) {
                $result = [
                    "url" => $response->url,
                    "token_ws" => $response->token
                ];
            } else {
                throw new TransactionCreationException();
            }
        } catch (TransactionCreationException $e) {
            $result = [
                "error" => 'Error al crear la transacción',
                "detail" => $e->getMessage()
            ];
            $this->log->logError(json_encode($result));
        }

        return $result;
    }

    public function commitTransaction($tokenWs)
    {
        $result = [];
        try {
            $this->log->logInfo('getTransactionResult - tokenWs: ' . $tokenWs);
            if ($tokenWs == null) {
                throw new TransactionCommitException();
            }

            return (new Transaction)->commit($tokenWs);
        } catch (TransactionCommitException $e) {
            $result = [
                "error" => 'Error al confirmar la transacción',
                "detail" => $e->getMessage()
            ];
            $this->log->logError(json_encode($result));
        }

        return $result;
    }
}
