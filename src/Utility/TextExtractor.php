<?php

namespace arajcany\ToolBox\Utility;

class TextExtractor
{
    /**
     * Tries to extract an Order and Job ID from the given string
     *
     * A common situation might be a filename or path that contains the said Order and Job ID
     * IDs are usually integers
     *
     * Some common notations for jobs are $jobNotations = ['job', 'jb', 'j'] in upper and lower case
     * Some common notations for orders are $orderNotations = ['order', 'ord', 'o'] in upper and lower case
     *
     * Order and Job IDs can be mixed into a text string by path delimiters or other delimiters
     * such as ['~', '-', '|', '_', '^', '+']
     *
     * Sometimes an input string might only contain one or the other and not use a notation to
     * define if the number is an OrderID or JobID.
     * In such cases the $options['preference'] = 'order' or $options['preference'] = 'job'
     * is used to indicate if the found number is an Order or Job ID
     *
     * The return array is always structured as ['order_id' => 1111, 'job_id' => 222]
     * If Order and or Job ID cannot be extracted, the return values will be populated with null
     * e.g. ['order_id' => null, 'job_id' => null]
     *
     * Here are some example strings that may be fed into the function and the expected result
     * "/tmp/to_print/order-3428/job-435892/file.pdf"   ['order_id' => 3428, 'job_id' => 435892]
     * "/tmp/order-3428-job-435892.pdf"                 ['order_id' => 3428, 'job_id' => 435892]
     * "/tmp/O342-J435892.pdf"                          ['order_id' => 3428, 'job_id' => 435892]
     *
     * Some edge cases:
     * If the input string contains 2 or more numbers separated by a delimiter and a notation is not used,
     * the second last number will be returned as the order_id and the last number will
     * be returned as the job_id in the return array
     * /tmp/445345/9843987034/746568734/file.pdf                ['order_id' => 9843987034, 'job_id' => 746568734]
     * /tmp/445345-9843987034-746568734.pdf                     ['order_id' => 9843987034, 'job_id' => 746568734]
     * /tmp/order-445345/job-9843987034/746568734/file.pdf      ['order_id' => 445345, 'job_id' => 9843987034]
     *
     * @param string $text
     * @param array $options
     * @return array
     */
    public static function extractOrderAndJobId(string $text, array $options = []): array
    {
        $defaultOption = [
            'preference' => 'order'
        ];
        $options = array_merge($defaultOption, $options);

        $orderNotations = ['order', 'ord', 'o'];
        $jobNotations = ['job', 'jb', 'j'];
        $delimiters = ['~', '-', '|', '_', '^', '+', '/', '\\'];

        $orderID = null;
        $jobID = null;

        // First try to match annotated order and job IDs
        $pattern = '/(?:' . implode('|', array_merge($orderNotations, $jobNotations)) . ')[-_]?(\\d{3,})/i';
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $prefix = strtolower(preg_replace('/[^a-z]/i', '', $match[0]));
                $number = $match[1];
                if (in_array($prefix, $orderNotations) && !$orderID) {
                    $orderID = (int)$number;
                } elseif (in_array($prefix, $jobNotations) && !$jobID) {
                    $jobID = (int)$number;
                }
            }
        }

        // If one or both IDs are still null, fallback to raw number extraction
        if ($orderID === null || $jobID === null) {
            // Split by non-digit or non-letter characters
            $parts = preg_split('/[^a-zA-Z0-9]/', $text);
            $numbers = [];
            foreach ($parts as $part) {
                if (preg_match('/^\d{3,}$/', $part)) {
                    $numbers[] = (int)$part;
                }
            }

            $numCount = count($numbers);
            if ($numCount >= 2) {
                if ($orderID === null) {
                    $orderID = $numbers[$numCount - 2];
                }
                if ($jobID === null) {
                    $jobID = $numbers[$numCount - 1];
                }
            } elseif ($numCount === 1) {
                if ($options['preference'] === 'order') {
                    $orderID = $numbers[0];
                } else {
                    $jobID = $numbers[0];
                }
            }
        }

        return ['order_id' => $orderID, 'job_id' => $jobID];
    }


    /**
     * Tries to extract a quality from the given string
     *
     * Similar to the Order and Job ID extraction, attempts to extract the
     * number of copies or quantity from a given string.
     *
     * Qty often has the notation $qtyNotations = ['quantities', 'quantity', 'copies', 'copy', 'cps'];
     * The notation can either be on the left or the right of the number
     *
     * e.g.
     * "img_00000 (1 copy).jpg"                                         1
     * "/tmp/to_print/order-3428/job-435892/file A (copies 5).pdf"      5
     * "order970-job74688-cps42"                                        42
     *
     * @param string $text
     * @param array $options
     * @return integer
     */
    public static function extractQty(string $text, array $options = []): int
    {
        $defaultOption = [
            'default_qty' => 1
        ];

        $options = array_merge($defaultOption, $options);
        $qtyNotations = ['quantities', 'quantity', 'copies', 'copy', 'cps', 'count'];

        // 1. Get order and job IDs to exclude
        $ids = self::extractOrderAndJobId($text, ['preference' => 'order']);
        $exclude = array_filter([$ids['order_id'], $ids['job_id']], 'is_numeric');
        $text = str_replace($exclude, '', $text);


        // 2. Look for a qty with explicit notation (label before or after number)
        $pattern = '/(?:' .
            implode('|', $qtyNotations) .
            ')[\s:_-]*?(\d{1,6})|' .                  // pattern: label then number
            '(\d{1,6})[\s:_-]*?(?:' .
            implode('|', $qtyNotations) .
            ')/i';                                   // pattern: number then label

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (!empty($match[1])) {
                    return (int)$match[1];
                } elseif (!empty($match[2])) {
                    return (int)$match[2];
                }
            }
        }

        // 3. Fallback to default
        return (int)$options['default_qty'];
    }
}
