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


    private function setCountLogByFile($filename) {
        $fp = file($this->logDir."/".$filename);
        $return  = array(
            'log_file' => $filename,
            'lines_regs' => count($fp)
        );
        return $return;
    }

    private function setLastLogCountLines() {
        $lastfile = $this->setLastLog();
        $fp = file($this->logDir."/".$lastfile['log_file']);
        $return  = array(
            'log_file' => basename($lastfile['log_file']),
            'lines_regs' => count($fp)
        );
        return $return;
    }

    private function setLogDir() {
        return $this->logDir;
    }

    private function setLogCount() {
        $logList = $this->setLogList();
        $count = isset($logList) ? count($logList) : 0;
        $result = array('log_count' => $count);
        return $result;
    }


    private function delAllLogs() {
        if (! file_exists($this->logDir)) {
            // echo "error!: no existe directorio de logs";
            exit;
        }
        $files = glob($this->logDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    // mantiene solo los ultimos n dias de logs
    private function digestLogs() {
        if (! file_exists($this->logDir)) {
            // echo "error!: no existe directorio de logs";
            $this->setMakeLogDir();
        }
        $files = glob($this->logDir.'/*', GLOB_ONLYDIR);
        $deletions = array_slice($files, 0, count($files) - $this->confdays);
        foreach ($deletions as $to_delete) {
            array_map('unlink', glob("$to_delete"));
            //$deleted = rmdir($to_delete);
        }
        return true;
    }

    // Obtiene archivo de bloqueo
    public function getLockFile(){
        return json_encode($this->getValidateLockFile());
    }

    // obtiene directorio de log
    public function getLogDir() {
        return json_encode($this->setLogDir());
    }

    // obtiene conteo de logs en logdir definido
    public function getLogCount() {
        return json_encode($this->setLogCount());
    }

    // obtiene listado de logs en logdir
    public function getLogList() {
        return json_encode($this->setLogList());
    }

    // obtiene ultimo log modificado (al crearse con timestamp es tambien el ultimo creado)
    public function getLastLog() {
        return json_encode($this->setLastLog());
    }

    // obtiene conteo de lineas de ultimo log creado
    public function getLastLogCountLines() {
        return json_encode($this->setLastLogCountLines());
    }

    // obtiene log en base a parametro
    public function getLogByFile($filename) {
        return json_encode($this->readLogByFile($filename));
    }

    // obtiene conteo de lineas de log en base a parametro
    public function getCountLogByFile($filename) {
        return json_encode($this->setCountLogByFile($filename));
    }

    public function delLogsFromDir() {
        $this->delAllLogs();
    }

    public function delKeepOnlyLastLogs() {
        $this->digestLogs();
    }

    public function setLockStatus($status = true) {
        if ($status === true) {
            $this->setLockFile();
        } else {
            $this->delLockFile();
        }
    }

    public function getResume() {
        $result = array(
            'config' => $this->getValidateLockFile(),
            'log_dir' => $this->setLogDir(),
            'logs_count' => $this->setLogCount(),
            'logs_list' => $this->setLogList(),
            'last_log' => $this->setLastLog(),
        );
        return json_encode($result);
    }

    /**
     * print ERROR log
     */
    public function logError($msg) {
        if (self::LOG_ERROR_ENABLED) {
            $this->logger->error('ERROR: ' . $msg);
        }
    }
}
