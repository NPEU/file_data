<?php

/**
 * DataHelpers
 *
 * Helpers for the Data Service
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class DataHelpers
{
    public static function formatQuery($human_readable)
    {
        // Tidy spacing:
        $human_readable = str_replace(', ', ',', $human_readable);
        $rows    = explode("\n", trim($human_readable));
        $return  = [];
        $fields  = [];
        $order   = '';
        $collect = '';
        foreach ($rows as $row) {
            // Validate expected format:
            $pattern = "#^[a-zA-Z]+:\s?[[a-zA-Z0-9]\s()<>=,-_:]+$#";
            if (preg_match($pattern, $row) == false) {
                continue;
            }
            #echo "<pre>\n"; var_dump(preg_match($pattern, $row)); echo "</pre>\n";

            $items = explode(':', $row);


            if (strtolower(trim($items[0])) == 'order') {
                $order = trim($items[1]);
                if (strpos($order, 'as ') === 0) {
                    //order=+FIELD%28call_number,%272011-24%27,%272011-06%27,%27NPEU-89%27,%27NPEU-88%27,%27NPEU-87%27,%27NPEU-86%27,%27NPEU-85%27,%27NPEU-84%27,%27NPEU-83%27,%27NPEU-82%27,%27NPEU-81%27,%27NPEU-80%27,%27NPEU-79%27%29
                    // Allow order to be specified the same AS a field value order:
                    $order_field = str_replace('as ', '', $order);
                    if (!isset($fields[$order_field])) {
                        // Can't establish order so ignore.
                        continue;
                    }
                    $order = ' FIELD(' . $order_field . ",'" . str_replace(',', "','", $fields[$order_field]) . "')";
                }
                continue;
            }

            if (strtolower(trim($items[0])) == 'collect') {
                $collect = str_replace(' ', '_', trim($items[1]));
                continue;
            }

            $or = '';

            if (strtolower(trim($items[0])) == 'or') {
                $or = '!';
                unset($items[0]);
                $items = array_values($items);
            }

            $query = trim($items[1]);
            $query = str_ireplace(' not ', '_NOT_', $query);
            $query = str_replace('>=', 'gte', $query);
            $query = str_replace('>', 'gt', $query);
            $query = str_replace('<=', 'lte', $query);
            $query = str_replace('<', 'lt', $query);
            $query = str_ireplace(' and ', '_AND_', $query);
            $query = trim(str_ireplace(' or ', '_OR_', $query));

            $return[] = $or . trim($items[0]) . '=' . urlencode($query);
            // Add field info to array for later use, i.e. by ORDER stuff:
            $fields[$items[0]] = $query;
            #$return[] = $or . trim($items[0]) . '=' . urlencode(trim($items[1]));
        }
        if ($order != '') {
            $return[] = 'order=' . urlencode($order);
        }
        if ($collect != '') {
            $return[] = 'collect=' . urlencode($collect);
        }
        $return = implode('&', $return);
        return !empty($return) ?  $return : false;
    }

    /*public static function formatQuery($human_readable)
    {
        $rows   = explode("\n", trim($human_readable));
        $return = [];
        foreach ($rows as $row) {
            $or    = '';
            $not   = '';
            $items = explode(':', $row);
            #echo "<pre>\n";var_dump($items);echo "</pre>\n";
            if (strtolower(trim($items[0])) == 'or') {
                $or = '~';
                unset($items[0]);
                $items = array_values($items);
            }
            if (strtolower(trim($items[1])) == 'not') {
                $not = '!';
                unset($items[1]);
                $items = array_values($items);
            }
            // There should now be only 2 items.
            if (count($items) != 2) {
                // Should really raise error instead.
                trigger_error('formatQuery was passed an unsuitable string', E_USER_ERROR);
                return false;
            }
            $return[] = $or . trim($items[0]) . $not . '=' . trim($items[1]);
        }
        return implode('&', $return);
    }*/
}

?>