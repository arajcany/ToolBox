# TextGrouper

Static Class that aims to group an array of string by various techniques.

## Summary

Consider the following array:

```php
[
    'a' => 'file_9_bar_02.png',
    'b' => 'file_0_a_002.png',
    'c' => 'file_9_bar_12.png',
    'd' => 'file_0_a_001.png',
    'e' => 'file_9_bar_04.png',
    'f' => 'unrelated_file_001.png',
    'g' => 'file_0_a_003.png',
    'h' => 'file_0_a_004.png',
    'i' => 'file_9_bar_05.png',
]
```
A human would sort them into the following groups:
```php
[
    [
        "a" => "file_9_bar_02.png",
        "c" => "file_9_bar_12.png",
        "e" => "file_9_bar_04.png",
        "i" => "file_9_bar_05.png"
    ],
    [
        "b" => "file_0_a_002.png",
        "d" => "file_0_a_001.png",
        "g" => "file_0_a_003.png",
        "h" => "file_0_a_004.png"
    ],
    [
        "f" => "unrelated_file_001.png"
    ]
]
```
