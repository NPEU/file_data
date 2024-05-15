<?php

/**
 * {NAME}
 *
 * {DESCRIPTION}
 *
 * @package NSys
 * @author akirk
 * @copyright Copyright (c) 2011 NPEU
 * @version 0.1

 **/
class dataHelpers
{
    public static function formatQuery($human_readable)
    {
        $rows   = explode("\n", trim(str_replace(', ', ',', $human_readable)));
        $return = array();
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
    }
}

?>