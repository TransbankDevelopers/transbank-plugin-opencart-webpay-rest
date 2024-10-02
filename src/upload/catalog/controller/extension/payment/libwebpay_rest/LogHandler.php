<?php
require __DIR__ . '/../../../../../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

define('Webpay_ROOT', dirname(__DIR__));

class LogHandler {

    //constants for log handler
    const LOG_DEBUG_ENABLED = false; //enable or disable debug logs
    const LOG_INFO_ENABLED = true; //enable or disable info logs
    const LOG_ERROR_ENABLED = true; //enable or disable error logs
    const DEFAULT_CONF_DAYS = 7;

    private $logFile;
    private $logDir;
    private $logURL;

    public function __construct($ecommerce = 'opencart', $days = 7, $weight = '2MB') {

        $this->logFile = null;
        $this->logDir = null;
        $this->logURL = null;
        $this->lockfile = Webpay_ROOT."/set_logs_activate.lock";
        $dia = date('Y-m-d');
        $this->confdays = $days;
        $this->confweight = $weight;

        $this->logDir = DIR_IMAGE."logs/Transbank_webpay";
        $this->logFile = "{$this->logDir}/log_transbank_{$ecommerce}_{$dia}.log";
        $this->logURL = str_replace($_SERVER['DOCUMENT_ROOT'], "", $this->logDir);

        $this->setMakeLogDir();

        $this->logger = new Logger('webpay_logger');
        $this->logger->pushHandler(new RotatingFileHandler($this->logFile, 10, Logger::DEBUG));
    }

    /**
     * Formats the size of a file in readable format.
     * @param string $path
     * @return string
     */
    private function formatBytes($path): string {
        $bytes = sprintf('%u', filesize($path));
        if ($bytes > 0) {
            $unit = intval(log($bytes, 1024));
            $units = array('B', 'KB', 'MB', 'GB');
            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }
        return $bytes;
    }



    /**
     * Create the log directory if it does not exist.
     */
    private function setMakeLogDir(): void {
        try {
            if (!file_exists($this->logDir)) {
                mkdir($this->logDir, 0777, true);
            }
        } catch(Exception $e) {
        }
    }

    /**
     * Configures the days and weight parameters for the logs.
     * @param int $days
     * @param string $weight
     */
    private function setparamsconf($days, $weight): void {
        if (file_exists($this->lockfile)) {
            $file = fopen($this->lockfile, "w") or die("No se puede truncar archivo");
            if (! is_numeric($days) or $days == null or $days == '' or $days === false) {
                $days = 7;
            }
            $txt = "{$days}\n";
            fwrite($file, $txt);
            $txt = "{$weight}\n";
            fwrite($file, $txt);
            fclose($file);
            chmod($this->lockfile, 0600);
        }
    }

    /**
     * Creates a lock file if it does not exist.
     * @return bool
     */
    private function setLockFile(): bool {
        if (! file_exists($this->lockfile)) {
            $file = fopen($this->lockfile, "w") or die("No se puede crear archivo de bloqueo");
            if (! is_numeric($this->confdays) or $this->confdays == null or $this->confdays == '' or $this->confdays === false) {
                $this->confdays = self::DEFAULT_CONF_DAYS;
            }
            $txt = "{$this->confdays}\n";
            fwrite($file, $txt);
            $txt = "{$this->confweight}\n";
            fwrite($file, $txt);
            fclose($file);
            chmod($this->lockfile, 0600);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validates the lock file and gets its configuration.
     * @return array
     */
    public function getValidateLockFile(): array {
        if (! file_exists($this->lockfile)) {
            $result = array(
                'status' => false,
                'lock_file' => basename($this->lockfile),
                'max_logs_days' => '7',
                'max_log_weight' => '2'
            );
        } else {
            $lines = file($this->lockfile);
            $this->confdays = trim(preg_replace('/\s\s+/', ' ', $lines[0]));
            $this->confweight = trim(preg_replace('/\s\s+/', ' ', $lines[1]));
            $result = array(
                'status' => true,
                'lock_file' => basename($this->lockfile),
                'max_logs_days' => $this->confdays,
                'max_log_weight' => $this->confweight
            );
        }
        return $result;
    }

    /**
     * Deletes the lock file if it exists.
     */
    private function delLockFile(): void {
        if (file_exists($this->lockfile)) {
            unlink($this->lockfile);
        }
    }

     /**
     * Generates a list of log files.
     * @return array|null
     */
    private function setLogList(): array {
        $arr = array_diff(scandir($this->logDir), array('.', '..'));
        foreach ($arr as $key => $value) {
            chmod($this->logDir."/".$value, 0660);
            $var[] = "<a href='{$this->logURL}/{$value}' download>{$value}</a>";
        }
        if (isset($var)) {
            $this->logList = $var;
        } else {
            $this->logList = null;
        }
        return $this->logList;
    }

    /**
     * Gets the information of the last log file.
     * @return array
     */
    private function getLastLog(): array {
        $files = glob($this->logDir."/*.log");
        if (!$files) {
            return array("No existen Logs disponibles");
        }
        $files = array_combine($files, array_map("filemtime", $files));
        arsort($files);
        $this->lastLog = key($files);
        if (isset($this->lastLog)) {
            $var = file_get_contents($this->lastLog);
        } else {
            $var = null;
        }
        $return = array(
            'log_file' => basename($this->lastLog),
            'log_weight' => $this->formatBytes($this->lastLog),
            'log_regs_lines' => count(file($this->lastLog)),
            'log_content' => $var
        );
        return $return;
    }




    /**
     * Returns the directory where the logs are stored.
     * @return string
     */
    private function getLogDir(): string {
        return $this->logDir;
    }

     /**
     * Count the number of logs in the directory.
     * @return array
     */
    private function setLogCount(): array {
        $logList = $this->setLogList();
        $count = isset($logList) ? count($logList) : 0;
        $result = array('log_count' => $count);
        return $result;
    }
    
    /**
     * Creates or deletes the lock file depending on the status.
     * @param bool $status
     */
    public function setLockStatus($status = true): void {
        if ($status === true) {
            $this->setLockFile();
        } else {
            $this->delLockFile();
        }
    }

     /**
     * Gets a summary of the current configuration and logs.
     * @return string
     */
    public function getResume(): bool|string {
        $result = array(
            'config' => $this->getValidateLockFile(),
            'log_dir' => $this->getLogDir(),
            'logs_count' => $this->setLogCount(),
            'logs_list' => $this->setLogList(),
            'last_log' => $this->getLastLog(),
        );
        return json_encode($result);
    }

    
}
