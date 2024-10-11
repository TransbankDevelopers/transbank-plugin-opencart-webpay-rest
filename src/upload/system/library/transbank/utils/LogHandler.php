<?php
namespace Transbank\Utils;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

define('Webpay_ROOT', dirname(__DIR__));

class LogHandler
{
    //constants for log handler
    const LOG_DEBUG_ENABLED = false; //enable or disable debug logs
    const LOG_INFO_ENABLED = true; //enable or disable info logs
    const LOG_ERROR_ENABLED = true; //enable or disable error logs

    private $logDir;
    private $logURL;
    private $logger;

    public function __construct($ecommerce = 'opencart')
    {
        $this->logDir = DIR_SYSTEM . "logs/Transbank_webpay";
        $this->logURL = str_replace($_SERVER['DOCUMENT_ROOT'], "", $this->logDir);
        $this->setMakeLogDir();
        $this->logger = new Logger('webpay_logger');
        $this->logger->pushHandler(new RotatingFileHandler("{$this->logDir}/log_transbank_{$ecommerce}.log", 10, Logger::DEBUG));
    }

    /**
     * Formats the size of a file in readable format.
     * @param string $path
     * @return string
     */
    private function formatBytes($path): string
    {
        $bytes = @filesize($path);
        if ($bytes > 0) {
            $unit = intval(log($bytes, 1024));
            $units = array('B', 'KB', 'MB', 'GB');
            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }
        return '0 B';
    }

    /**
     * Create the log directory if it does not exist.
     */
    private function setMakeLogDir(): void
    {
        try {
            if (!file_exists($this->logDir)) {
                mkdir($this->logDir, 0777, true);
            }
        } catch (\Exception $e) {
            error_log('Error al crear la carpeta de logs ' . $e->getMessage());
        }
    }

     /**
     * Returns the directory where the logs are stored.
     * @return string
     */
    private function getLogDir(): string
    {
        return $this->logDir;
    }

    /**
     * Generates a list of log files.
     * @return array|null
     */
    private function setLogList(): array
    {
        $arr = array_diff(scandir($this->logDir), array('.', '..'));
        foreach ($arr as $value) {
            chmod($this->logDir . "/" . $value, 0777);
            $logList[] = "<a href='{$this->logURL}/{$value}' download>{$value}</a>";
        }
        return $logList ?? null;
    }

    /**
     * Gets the information of the last log file.
     * @return array
     */
    private function getLastLog(): array
    {
        $files = glob($this->logDir . "/*.log");
        if (!$files) {
            return ["No existen Logs disponibles"];
        }
        $files = array_combine($files, array_map("filemtime", $files));
        arsort($files);
        $this->lastLog = key($files);
        $logContent = @file_get_contents($this->lastLog) ?: null;
        return [
            'log_file' => basename($this->lastLog),
            'log_weight' => $this->formatBytes($this->lastLog),
            'log_regs_lines' => count(file($this->lastLog)),
            'log_content' => $logContent
        ];
    }

    /**
     * Count the number of logs in the directory.
     * @return array
     */
    private function setLogCount(): array
    {
        $logList = $this->setLogList();
        $count = isset($logList) ? count($logList) : 0;
        return ['log_count' => $count];
    }

    /**
     * Gets a summary of the current configuration and logs.
     * @return string
     */
    public function getResume(): bool|string
    {
        $result = array(
            'log_dir' => $this->getLogDir(),
            'logs_count' => $this->setLogCount(),
            'logs_list' => $this->setLogList(),
            'last_log' => $this->getLastLog(),
        );
        return json_encode($result);
    }

    /**
     * Print DEBUG log
     */
    public function logDebug($msg)
    {
        if (self::LOG_DEBUG_ENABLED) {
            $this->logger->debug($msg);
        }
    }

     /**
     * Print INFO log
     */
    public function logInfo($msg)
    {
        if (self::LOG_INFO_ENABLED) {
            $this->logger->info($msg);
        }
    }

    /**
     * Print ERROR log
     */
    public function logError($msg)
    {
        if (self::LOG_ERROR_ENABLED) {
            $this->logger->error($msg);
        }
    }
}
