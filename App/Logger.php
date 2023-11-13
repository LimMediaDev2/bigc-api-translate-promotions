<?php

namespace App;

use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface {
    private $logFilePath;

    public function __construct($logDirectory = '/logs')
    {
        $logDirectory = __DIR__ . '/../' . $logDirectory;

        if (!file_exists($logDirectory)) {
            mkdir($logDirectory, 0775, true);
        }

        $this->logFilePath = $logDirectory . '/log-' . date('Y-m-d') . '.log';

        if (!file_exists($this->logFilePath)) {
            touch($this->logFilePath);
        }
    }

    private function checkDailyLog(): void
    {
        $currentDate = date('Y-m-d');
        $logDate = date('Y-m-d', filemtime($this->logFilePath));

        if ($currentDate !== $logDate) {
            $this->logFilePath = str_replace($logDate, $currentDate, $this->logFilePath);
        }
    }

    private function formatLogMessage(string $level, string $message, array $context = []): void
    {
        $formattedMessage = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), strtoupper($level), $message);

        if (!empty($context)) {
            $formattedMessage .= json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }

        $this->checkDailyLog();

        file_put_contents($this->logFilePath, $formattedMessage, FILE_APPEND | LOCK_EX);
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->formatLogMessage('EMERGENCY', $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->formatLogMessage('ALERT', $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->formatLogMessage('CRITICAL', $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->formatLogMessage('ERROR', $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->formatLogMessage('WARNING', $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->formatLogMessage('NOTICE', $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->formatLogMessage('INFO', $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->formatLogMessage('DEBUG', $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->formatLogMessage(mb_strtoupper($level, 'UTF-8'), $message, $context);
    }
}
