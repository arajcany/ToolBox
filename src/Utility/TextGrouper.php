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


    /**
     * Faster method to group items by similarity.
     * Is faster because it pre-groups the items so that the exponential comparisons is avoided.
     *
     * @param array $listOfItems
     * @param int $lowestMatchLimit
     * @return array
     */
    public static function bySimilarityFast(array $listOfItems, int $lowestMatchLimit = 95): array
    {
        $postSorted = [];
        $preGrouped = self::preGroupItems($listOfItems);

        foreach ($preGrouped as $group) {
            $sorted = self::quickGroup($group, $lowestMatchLimit);
            $postSorted = array_merge($postSorted, $sorted);
        }

        //re-sort back into some semblance of their original order
        $finalSorted = [];
        $originalKeyPositions = array_keys($listOfItems);
        foreach ($postSorted as $groupKey => $groupValues) {
            $firstKey = array_key_first($groupValues);
            $matchedKey = array_search($firstKey, $originalKeyPositions);
            $finalSorted[$matchedKey] = $groupValues;
        }
        ksort($finalSorted);
        $finalSorted = array_values($finalSorted);

        return $finalSorted;
    }

    private static function preGroupItems($listOfItems): array
    {
        $lengths = [];
        foreach ($listOfItems as $item) {
            $lengths[] = strlen($item);
        }
        $averageLength = intval((array_sum($lengths)) / count($lengths));

        //create a string based on the average length
        $baseString = str_pad("", $averageLength, implode(range('a', 'z')));

        $map = [];
        foreach ($listOfItems as $itemKey => $itemValue) {
            similar_text($baseString, $itemValue, $percent);
            $preGrouper = floor($percent * 1000);
            $map[$preGrouper][$itemKey] = $itemValue;
        }

        return $map;
    }

    private static function quickGroup($listOfItems, $lowestMatchLimit = 95): array
    {
        $map = self::createMap($listOfItems, $lowestMatchLimit);

        $grouping = [];
        $cnt = 0;
        $matchedKeys = [];
        foreach ($map as $keyMaster => $matchValues) {
            $grouping[$cnt][$keyMaster] = $listOfItems[$keyMaster];
            $matchedKeys[] = $keyMaster;
            foreach ($matchValues as $keySlave => $percentage) {
                if ($percentage >= $lowestMatchLimit) {
                    $grouping[$cnt][$keySlave] = $listOfItems[$keySlave];
                    $matchedKeys[] = $keySlave;
                }
            }
            $cnt++;
        }
        $unmatchedKeys = $listOfItems;
        foreach ($matchedKeys as $matchedKey) {
            unset($unmatchedKeys[$matchedKey]);
        }
        foreach ($unmatchedKeys as $k => $unmatchedKey) {
            $grouping[][$k] = $unmatchedKey;
        }

        return $grouping;
    }

    private static function createMap($listOfItems, $lowestMatchLimit = 95): array
    {
        $map = [];

        while (count($listOfItems) > 0) {
            $keysToUnset = [];
            $masterItemKey = array_key_first($listOfItems);
            $masterItemValue = $listOfItems[$masterItemKey];
            unset($listOfItems[$masterItemKey]);
            foreach ($listOfItems as $itemKey => $itemValue) {
                similar_text($masterItemValue, $itemValue, $percent);
                $map[$masterItemKey][$itemKey] = $percent;

                if ($percent >= $lowestMatchLimit) {
                    $keysToUnset[] = $itemKey;
                }
            }

            foreach ($keysToUnset as $keyToUnset) {
                unset($listOfItems[$keyToUnset]);
            }

        }

        return $map;
    }

}