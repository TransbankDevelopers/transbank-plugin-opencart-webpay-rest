<?php

namespace Transbank\Opencart\Webpay\Utils;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class LogHandler
{

    private $logDir;
    private $logger;

    public function __construct($ecommerce = 'opencart')
    {
        $this->logDir = DIR_STORAGE . "logs/Transbank_webpay";
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
        $bytes = is_readable($path) ? filesize($path) : 0;
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
     * Returns the directory where the logs are stored.
     * @return string
     */
    private function getLogDir(): string
    {
        return $this->logDir;
    }

    /**
     * Get the list of log file names in the log directory.
     * @return array
     */
    private function getLogList(): array
    {
        if (!is_dir($this->logDir) || ($entries = scandir($this->logDir)) === false) {
            return [];
        }

        return array_values(array_diff($entries, array('.', '..')));
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
        $logContent = null;

        if (is_readable($this->lastLog)) {
            $logContent = file_get_contents($this->lastLog) ?: null;
        }

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
        $logList = $this->getLogList();
        $count = isset($logList) ? count($logList) : 0;
        return ['log_count' => $count];
    }

    /**
     * Strips markup and normalizes whitespace in log messages, preventing markup
     * injection and forged log lines (via injected newlines) regardless of how
     * the caller obtained the value.
     * @param string $msg
     * @return string
     */
    private function sanitizeMessage($msg): string
    {
        $msg = strip_tags((string) $msg);
        $msg = preg_replace('/[\r\n]+/', ' ', $msg);

        return trim($msg);
    }

    /**
     * Gets a summary of the current configuration and logs.
     * @return string
     */
    public function getResume()
    {
        $result = array(
            'log_dir' => $this->getLogDir(),
            'logs_count' => $this->setLogCount(),
            'logs_list' => $this->getLogList(),
            'last_log' => $this->getLastLog(),
        );
        return json_encode($result);
    }

    /**
     * Logs a message at the DEBUG level.
     * @param string $msg The message to be logged.
     * @return void
     */
    public function logDebug($msg)
    {
        $this->logger->debug($this->sanitizeMessage($msg));
    }

    /**
     * Logs a message at the INFO level.
     * @param string $msg The message to be logged.
     * @return void
     */
    public function logInfo($msg)
    {
        $this->logger->info($this->sanitizeMessage($msg));
    }

    /**
     * Logs a message at the ERROR level.
     * @param string $msg The message to be logged.
     * @return void
     */
    public function logError($msg)
    {
        $this->logger->error($this->sanitizeMessage($msg));
    }
}
