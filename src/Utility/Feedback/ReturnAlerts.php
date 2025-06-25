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
    private string $classOwner = '';
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
            $ms = explode(".", $timestamp)[1];
            $compiled[$timestamp] = [
                'created' => date("Y-m-d H:i:s", intval($timestamp)) . "." . $ms,
                $levelFieldName => 'success',
                $messageFieldName => $message,
            ];
        }

        foreach ($this->dangerAlerts as $timestamp => $message) {
            $ms = explode(".", $timestamp)[1];
            $compiled[$timestamp] = [
                'created' => date("Y-m-d H:i:s", intval($timestamp)) . "." . $ms,
                $levelFieldName => 'danger',
                $messageFieldName => $message,
            ];
        }

        foreach ($this->warningAlerts as $timestamp => $message) {
            $ms = explode(".", $timestamp)[1];
            $compiled[$timestamp] = [
                'created' => date("Y-m-d H:i:s", intval($timestamp)) . "." . $ms,
                $levelFieldName => 'warning',
                $messageFieldName => $message,
            ];
        }

        foreach ($this->infoAlerts as $timestamp => $message) {
            $ms = explode(".", $timestamp)[1];
            $compiled[$timestamp] = [
                'created' => date("Y-m-d H:i:s", intval($timestamp)) . "." . $ms,
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

        foreach ($this->successAlerts as $timestamp => $message) {
            $ms = explode(".", $timestamp)[1];
            $compiled[$timestamp] = date("Y-m-d H:i:s", intval($timestamp)) . ".{$ms} SUCCESS: {$message}";
        }

        foreach ($this->dangerAlerts as $timestamp => $message) {
            $ms = explode(".", $timestamp)[1];
            $compiled[$timestamp] = date("Y-m-d H:i:s", intval($timestamp)) . ".{$ms} DANGER:  {$message}";
        }

        foreach ($this->warningAlerts as $timestamp => $message) {
            $ms = explode(".", $timestamp)[1];
            $compiled[$timestamp] = date("Y-m-d H:i:s", intval($timestamp)) . ".{$ms} WARNING: {$message}";
        }

        foreach ($this->infoAlerts as $timestamp => $message) {
            $ms = explode(".", $timestamp)[1];
            $compiled[$timestamp] = date("Y-m-d H:i:s", intval($timestamp)) . ".{$ms} INFO:    {$message}";
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
            $level = $this->extractLevel($item);

            if ($level === 'error') {
                $this->addDangerAlerts($item);
            } elseif ($level === 'warning') {
                $this->addWarningAlerts(__($item));
            } elseif ($level === 'danger') {
                $this->addDangerAlerts($item);
            } elseif ($level === 'success') {
                $this->addSuccessAlerts($item);
            } else {
                $this->addInfoAlerts(__($item));
            }
        }

        return $this->getAllAlerts();
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
            $microTime = $this->getMicrotime();
            $this->$type[$microTime] = $message;
        }

        return $this->$type;
    }

    /**
     * Extract the potential error level based on the contents of the string
     *
     * @param string $string
     * @return string
     */
    private function extractLevel(string $string): string
    {
        $string = strtolower($string);

        if (str_contains($string, 'warning')) {
            $level = 'warning';
        } else if (str_contains($string, 'success')) {
            $level = 'success';
        } else if (str_contains($string, 'danger')) {
            $level = 'danger';
        } else if (str_contains($string, 'info')) {
            $level = 'info';
        } else {
            $level = 'info';
        }

        return $level;
    }
}
