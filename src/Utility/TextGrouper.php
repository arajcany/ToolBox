<?php

namespace arajcany\ToolBox\Utility;


class TextGrouper
{
    /**
     * @param array $listOfItems an array of items to be grouped
     * @param bool $ignorePureMatches if true range is from 99% match to $lowerMatchLimit. if false range is from 100% match to  $lowerMatchLimit.
     * @param int $lowerMatchLimit lowest % of similarity you are willing to accept e.g. if below 80% are they really a match?
     * @param bool $groupsCountMustOutweighSinglesCount if true, keep looping till most of the list of items have been put into a group
     * @return array
     */
    public static function bySimilarity(array $listOfItems, bool $ignorePureMatches = true, int $lowerMatchLimit = 95, bool $groupsCountMustOutweighSinglesCount = true): array
    {
        //to be considered a group you need at least N entries...
        $groupEntriesTriggerThreshold = 2;

        //are they really that similar if the match is below N%...?
        $lowerMatchLimit = intval($lowerMatchLimit);

        if ($ignorePureMatches) {
            $decrementingRange = range(99, $lowerMatchLimit);
        } else {
            $decrementingRange = range(100, $lowerMatchLimit);
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
            $valuesCompared = [];
            $groups = [];
            $cnt = 0;

            $items = $listOfItems;
            $groupEntriesTrigger = 0;

            while (count($items) > 0) {
                $itemsFirstKey = array_key_first($items);
                $groups[$cnt][$itemsFirstKey] = $items[$itemsFirstKey];
                unset($items[$itemsFirstKey]);

                $beforeCount = 0;
                $afterCount = 1;
                while ($beforeCount !== $afterCount) {

                    $beforeCount = count($items);
                    foreach ($groups[$cnt] as $keyItemRef => $itemRef) {
                        foreach ($items as $keyItemCompare => $itemCompare) {
                            if (!isset($valuesCompared[$keyItemRef][$keyItemCompare])) {
                                $calc = similar_text($itemRef, $itemCompare, $percent);
                                if ($percent >= $thresholdPercent) {
                                    $groups[$cnt][$keyItemCompare] = $itemCompare;
                                    $groupEntriesTrigger = max($groupEntriesTrigger, count($groups[$cnt]));
                                    unset($items[$keyItemCompare]);
                                }
                                $valuesCompared[$keyItemRef][$keyItemCompare] = $percent;
                            }
                        }
                    }
                    $afterCount = count($items);

                }

                $cnt++;
            }

            if ($groupEntriesTrigger >= $groupEntriesTriggerThreshold) {
                if ($groupsCountMustOutweighSinglesCount) {
                    $countSingles = 0;
                    $countMulti = 0;
                    foreach ($groups as $group) {
                        if (count($group) == 1) {
                            $countSingles++;
                        } elseif (count($group) > 1) {
                            $countMulti++;
                        }
                    }
                    if ($countMulti >= $countSingles) {
                        return $groups;
                    }
                } else {
                    return $groups;
                }
            }
        }

        //something went wrong!
        if (isset($groups) && (count($groups, COUNT_RECURSIVE) > count($groups, COUNT_NORMAL))) {
            return $groups;
        } else {
            return [$listOfItems];
        }
    }


}