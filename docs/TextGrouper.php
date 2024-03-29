<?php

use arajcany\ToolBox\Utility\TextGrouper;

require __DIR__ . '/../vendor/autoload.php';


//$s = microtime(true);
//$desc = unrelatedList();
//$groups = TextGrouper::bySimilarity($desc, true, 95, true);
//file_put_contents(__DIR__ . "/../tmp/unrelated.json", json_encode($groups, JSON_PRETTY_PRINT));
//$e = microtime(true);
//dump($e - $s);
//dump($groups);
//
//$s = microtime(true);
//$names = getFileNames();
//$groups = TextGrouper::bySimilarity($names, true, 80, true);
//file_put_contents(__DIR__ . "/../tmp/filenamesA.json", json_encode($groups, JSON_PRETTY_PRINT));
//$e = microtime(true);
//dump($e - $s);
//dump($groups);
//
//$s = microtime(true);
//$names = getFileNames();
//$groups = TextGrouper::bySimilarity($names, true, 80, false);
//file_put_contents(__DIR__ . "/../tmp/filenamesB.json", json_encode($groups, JSON_PRETTY_PRINT));
//$e = microtime(true);
//dump($e - $s);
//dump($groups);
//
//$s = microtime(true);
//$names = getFileNames();
//$groups = TextGrouper::createMap($names);
//$e = microtime(true);
//dump($groups);
//dump($e - $s);
//
//$s = microtime(true);
//$names = getLargeList();
//$groups = TextGrouper::bySimilarity($names, true, 90, false);
//file_put_contents(__DIR__ . "/../tmp/largeList.json", json_encode($groups, JSON_PRETTY_PRINT));
//$e = microtime(true);
//dump($e - $s);
//dump($groups);
//
$s = microtime(true);
$names = getLargeList();
$groups = TextGrouper::bySimilarityFast($names, 90);
file_put_contents(__DIR__ . "/../tmp/largeList.json", json_encode($groups, JSON_PRETTY_PRINT));
$e = microtime(true);
dump($e - $s);
dump($groups);

function unrelatedList()
{
    return [
        'a' => 'there',
        'b' => 'is',
        'c' => 'no',
        'd' => 'logic',
        'e' => 'to',
        'f' => 'match',
        'g' => 'these',
        'h' => 'list',
        'i' => 'items',
    ];
}


function getFileNames()
{
    return [
        'a' => 'file_9_bar_02.png',
        'b' => 'file_0_a_002.png',
        'c' => 'file_9_bar_12.png',
        'd' => 'file_0_a_001.png',
        'e' => 'file_9_bar_04.png',
        'f' => 'unrelated_file_001.png',
        'g' => 'file_0_a_003.png',
        'h' => 'file_0_a_004.png',
        'i' => 'file_9_bar_05.png',
        'j' => 'no_match_here.png',
        'k' => 'file_12_bar_8.png',
        'l' => 'file_12_bar_9.png',
        'm' => 'file_12_bar_10.png',
        'n' => 'file_12_bar_11.png',
        'o' => 'file_12_bar_12.png',
        'p' => 'file_12_bar_13.png',
    ];
}


function getDescriptionList()
{
    return [

    ];
}


function getLargeList()
{
    $data = file_get_contents(__DIR__ . "/../tmp/largeList.txt");
    return explode("\r\n", $data);
}