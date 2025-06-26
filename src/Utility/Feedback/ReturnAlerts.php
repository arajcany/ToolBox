<?php

namespace arajcany\ToolBox\Utility\Feedback;

/**
 * This handy trait can be used for logging error inside an Object.
 * Often a Method needs to return a single value such as true/false.
 * However, you need to know why true/false was returned.
 * You can use this trait to log a message before you return the value,
 * then you can call getAllAlerts() to get an array of all the alerts
 * that were raised.
 *
 * Alerts match the Bootstrap CSS framework levels so that you can
 * appropriately style the alert in the GUI
 *
 */
trait ReturnAlerts
{
    private array $successAlerts = [];
    private array $dangerAlerts = [];
    private array $warningAlerts = [];
    private array $infoAlerts = [];

    //often when running in CLI, a single return value and message are needed.
    private int $returnValue = 0;
    private string $returnMessage = '';

    /**
     * Ultra-fine micro time
     *
     * @param string $separator
     * @return string
     */
    private function getMicrotime(string $separator = ''): string
    {
        $mt = microtime();
        $mt = explode(" ", $mt);
        $unixTS = $mt[1];
        $microParts = explode(".", $mt[0]);

        return "{$unixTS}{$separator}{$microParts[1]}";
    }


    /**
     * @param int $returnValue
     */
    public function setReturnValue(int $returnValue): void
    {
        $this->returnValue = $returnValue;
    }

    /**
     * @return int
     */
    public function getReturnValue(): int
    {
        return $this->returnValue;
    }

    /**
     * @param string $returnMessage
     */
    public function setReturnMessage(string $returnMessage): void
    {
        $this->returnMessage = $returnMessage;
    }

    /**
     * @return string
     */
    public function getReturnMessage(): string
    {
        return $this->returnMessage;
    }

    /**
     * Return to default state
     *
     * @return void
     */
    public function clearAllReturnAlerts(): void
    {
        $this->returnValue = 0;
        $this->returnMessage = '';
        $this->successAlerts = [];
        $this->dangerAlerts = [];
        $this->warningAlerts = [];
        $this->infoAlerts = [];
    }

    /**
     * Return Alerts in their base array format.
     *
     * NOTE: this delivers the alerts out of sequence - they are grouped by level.
     *
     * @return array
     */
    public function getAllAlerts(): array
    {
        return [
            'success' => array_values($this->successAlerts),
            'danger' => array_values($this->dangerAlerts),
            'warning' => array_values($this->warningAlerts),
            'info' => array_values($this->infoAlerts),
        ];
    }

    /**
     * @return string
     */
    public function getHighestAlertLevel(): string
    {
        if (!empty($this->dangerAlerts)) {
            $status = 'danger';
        } elseif (!empty($this->warningAlerts)) {
            $status = 'warning';
        } elseif (!empty($this->infoAlerts)) {
            $status = 'info';
        } else {
            $status = 'success';
        }

        return $status;
    }

    /**
     * Return Alerts ready for a mass into a log style table.
     *
     * @param string $levelFieldName
     * @param string $messageFieldName
     * @return array
     */
    public function getAllAlertsForMassInsert(string $levelFieldName = 'level', string $messageFieldName = 'message'): array
    {
        $compiled = [];

        foreach ($this->successAlerts as $timestamp => $message) {
            $parts = explode(".", $timestamp, 2);
            $ts = isset($parts[0]) ? intval($parts[0]) : 0;
            $ms = $parts[1] ?? '000000';
            $compiled[$timestamp] = [
                'created' => date("Y-m-d H:i:s", $ts) . "." . $ms,
                $levelFieldName => 'success',
                $messageFieldName => $message,
            ];
        }

        foreach ($this->dangerAlerts as $timestamp => $message) {
            $parts = explode(".", $timestamp, 2);
            $ts = isset($parts[0]) ? intval($parts[0]) : 0;
            $ms = $parts[1] ?? '000000';
            $compiled[$timestamp] = [
                'created' => date("Y-m-d H:i:s", $ts) . "." . $ms,
                $levelFieldName => 'danger',
                $messageFieldName => $message,
            ];
        }

        foreach ($this->warningAlerts as $timestamp => $message) {
            $parts = explode(".", $timestamp, 2);
            $ts = isset($parts[0]) ? intval($parts[0]) : 0;
            $ms = $parts[1] ?? '000000';
            $compiled[$timestamp] = [
                'created' => date("Y-m-d H:i:s", $ts) . "." . $ms,
                $levelFieldName => 'warning',
                $messageFieldName => $message,
            ];
        }

        foreach ($this->infoAlerts as $timestamp => $message) {
            $parts = explode(".", $timestamp, 2);
            $ts = isset($parts[0]) ? intval($parts[0]) : 0;
            $ms = $parts[1] ?? '000000';
            $compiled[$timestamp] = [
                'created' => date("Y-m-d H:i:s", $ts) . "." . $ms,
                $levelFieldName => 'info',
                $messageFieldName => $message,
            ];
        }

        ksort($compiled);
        return $compiled;
    }

    /**
     * Return alerts in more like a standard log file format.
     * Still an array where every entry needs to be written as a line to file.
     *
     * @return array
     */
    public function getAllAlertsLogSequence(): array
    {
        $compiled = [];

        $formatLogLine = function ($timestamp, $message, $level) {
            $parts = explode(".", $timestamp, 2);
            $ts = isset($parts[0]) ? intval($parts[0]) : 0;
            $ms = $parts[1] ?? '000000';

            return [$timestamp, date("Y-m-d H:i:s", $ts) . ".{$ms} " . strtoupper($level) . ": " . $message];
        };

        foreach ($this->successAlerts as $timestamp => $message) {
            [$ts, $line] = $formatLogLine($timestamp, $message, 'success');
            $compiled[$ts] = $line;
        }

        foreach ($this->dangerAlerts as $timestamp => $message) {
            [$ts, $line] = $formatLogLine($timestamp, $message, 'danger');
            $compiled[$ts] = $line;
        }

        foreach ($this->warningAlerts as $timestamp => $message) {
            [$ts, $line] = $formatLogLine($timestamp, $message, 'warning');
            $compiled[$ts] = $line;
        }

        foreach ($this->infoAlerts as $timestamp => $message) {
            [$ts, $line] = $formatLogLine($timestamp, $message, 'info');
            $compiled[$ts] = $line;
        }

        ksort($compiled);
        return array_values($compiled);
    }

    /**
     * Get the Return Alerts for use in the $this->mergeAlerts()
     *
     * @return array
     */
    public function getAllAlertsForMerge(): array
    {
        return [
            'success' => $this->successAlerts,
            'danger' => $this->dangerAlerts,
            'warning' => $this->warningAlerts,
            'info' => $this->infoAlerts,
        ];
    }

    /**
     * Use this to merge alerts from two classes that have used Return Alerts.
     *
     * $this->mergeAlerts($OtherObject->getAllAlertForMerge());
     *
     * @param array $alerts
     * @return void
     */
    public function mergeAlerts(array $alerts): void
    {
        if ($alerts['success']) {
            $this->successAlerts = array_merge($this->successAlerts, $alerts['success']);
        }

        if ($alerts['danger']) {
            $this->dangerAlerts = array_merge($this->dangerAlerts, $alerts['danger']);
        }

        if ($alerts['warning']) {
            $this->warningAlerts = array_merge($this->warningAlerts, $alerts['warning']);
        }

        if ($alerts['info']) {
            $this->infoAlerts = array_merge($this->infoAlerts, $alerts['info']);
        }
    }

    /**
     * Merge in the Return Alerts from another object.
     * Saves a step as this method check if the other object uses Return Alerts.
     *
     * @param object $otherObject
     * @return void
     */
    public function mergeAlertsFromObject(object $otherObject): void
    {
        if (!method_exists($otherObject, 'getAllAlertsForMerge')) {
            return;
        }
        $alerts = $otherObject->getAllAlertsForMerge();
        $this->mergeAlerts($alerts);
    }

    /**
     * @return array
     */
    public function getSuccessAlerts(): array
    {
        return $this->successAlerts;
    }

    /**
     * @return array
     */
    public function getDangerAlerts(): array
    {
        return $this->dangerAlerts;
    }

    /**
     * @return array
     */
    public function getWarningAlerts(): array
    {
        return $this->warningAlerts;
    }

    /**
     * @return array
     */
    public function getInfoAlerts(): array
    {
        return $this->infoAlerts;
    }

    /**
     * @param array|string $message
     * @return array
     */
    public function addSuccessAlerts(array|string $message): array
    {
        return $this->_addAlert($message, 'successAlerts');
    }

    /**
     * @param array|string $message
     * @return array
     */
    public function addDangerAlerts(array|string $message): array
    {
        return $this->_addAlert($message, 'dangerAlerts');
    }

    /**
     * @param array|string $message
     * @return array
     */
    public function addWarningAlerts(array|string $message): array
    {
        return $this->_addAlert($message, 'warningAlerts');
    }

    /**
     * @param array|string $message
     * @return array
     */
    public function addInfoAlerts(array|string $message): array
    {
        return $this->_addAlert($message, 'infoAlerts');
    }

    /**
     * Try to add the right alert type based on the error string
     *
     * @param array|string $message
     * @return array
     */
    public function addSmartAlerts(array|string $message): array
    {
        if (is_string($message)) {
            $message = [$message];
        }

        foreach ($message as $item) {
            $level = $this->mapToBootstrapLevel($this->extractLevel($item));

            switch ($level) {
                case 'danger':
                    $this->addDangerAlerts($item);
                    break;
                case 'warning':
                    $this->addWarningAlerts($item);
                    break;
                case 'success':
                    $this->addSuccessAlerts($item);
                    break;
                case 'info':
                default:
                    $this->addInfoAlerts($item);
                    break;
            }
        }

        return $this->getAllAlerts();
    }

    /**
     * Map PSR-3 or descriptive level to Bootstrap alert level
     *
     * @param string $level
     * @return string
     */
    private function mapToBootstrapLevel(string $level): string
    {
        return match ($level) {
            'emergency', 'alert', 'critical', 'error', 'danger' => 'danger',
            'warning' => 'warning',
            'success' => 'success',
            default => 'info' //'notice', 'info', 'debug'
        };
    }

    /**
     * Set an alert with micro-timestamp as the key.
     *
     * @param array|string $messages
     * @param string $type
     * @return array
     */
    private function _addAlert(array|string $messages, string $type): array
    {
        /** @var array $this ->$type */
        if (is_string($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $message) {
            do {
                $microTime = $this->getMicrotime('.');
            } while (isset($this->$type[$microTime])); // retry until unique key found

            $this->$type[$microTime] = $message;
        }

        return $this->$type;
    }

    /**
     * Extract the potential PSR-3 log level based on the contents of the string
     *
     * @param string $string
     * @return string One of: emergency, alert, critical, error, warning, notice, info, debug, success
     */
    private function extractLevel(string $string): string
    {
        $string = strtolower($string);

        if (str_contains($string, 'emergency')) {
            return 'emergency';
        } elseif (str_contains($string, 'alert')) {
            return 'alert';
        } elseif (str_contains($string, 'critical')) {
            return 'critical';
        } elseif (str_contains($string, 'error')) {
            return 'error';
        } elseif (str_contains($string, 'warning')) {
            return 'warning';
        } elseif (str_contains($string, 'notice')) {
            return 'notice';
        } elseif (str_contains($string, 'debug')) {
            return 'debug';
        } elseif (str_contains($string, 'info')) {
            return 'info';
        }elseif (str_contains($string, 'success')) {
            return 'success'; //not a PSR-3 log level but is used by Bootstrap
        }

        return 'info';
    }
}
