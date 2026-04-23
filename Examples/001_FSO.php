<?php

use arajcany\ToolBox\Flysystem\FsoTasks;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';


$FsoTasks = new FsoTasks();


$messages = [];
foreach (range(1, 2) as $number) {
    $messages[] = "Message Number {$number}";
}

$FsoTasks->addDangerAlerts($messages);
$FsoTasks->addSuccessAlerts($messages);
$FsoTasks->addInfoAlerts($messages);
$FsoTasks->addWarningAlerts($messages);

dd($FsoTasks->getSuccessAlerts());