<?php

namespace arajcany\ToolBox\Utility;


class TextGrouper
{
    /**
     * @param array $listOfItems
     * @param bool $ignorePureMatches
     * @return array
     */
    public static function bySimilarity(array $listOfItems, bool $ignorePureMatches = true): array
    {
        if ($ignorePureMatches) {
            $decrementingRange = range(99, 0);
        } else {
            $decrementingRange = range(100, 0);
        }

        //polyfill
        if (!function_exists('array_key_last')) {
            function array_key_last(array $array): bool|int|string|null
            {
                if (!empty($array)) {
                    return key(array_slice($array, -1, 1, true));
                }
                return false;
            }
        }

        //polyfill
        if (!function_exists('array_key_first')) {
            function array_key_first(array $arr): bool|int|string
            {
                foreach ($arr as $key => $unused) {
                    return $key;
                }
                return false;
            }
        }

        foreach ($decrementingRange as $thresholdPercent) {
            $groups = [];
            $cnt = 0;

            $items = $listOfItems;
            $groupEntriesTrigger = 0;

            while (count($items) > 0) {
                $itemsFirstKey = array_key_first($items);
                $currentGroup = [];
                $currentGroup[$itemsFirstKey] = $items[$itemsFirstKey];
                unset($items[$itemsFirstKey]);

                $beforeCount = 0;
                $afterCount = 1;
                while ($beforeCount !== $afterCount) {
                    $beforeCount = count($items);
                    foreach ($currentGroup as $keyItemRef => $itemRef) {
                        foreach ($items as $keyItemCompare => $itemCompare) {
                            $calc = similar_text($itemRef, $itemCompare, $percent);
                            if ($percent >= $thresholdPercent) {
                                $currentGroup[$keyItemCompare] = $itemCompare;
                                $groupEntriesTrigger = max($groupEntriesTrigger, count($currentGroup));
                                unset($items[$keyItemCompare]);
                            }
                        }
                    }
                    $afterCount = count($items);
                }

                $groups[$cnt] = $currentGroup;
                $cnt++;
            }

            if ($thresholdPercent === 0 || $groupEntriesTrigger >= 2) {
                return $groups;
            }
        }

        return [$listOfItems];
    }

}