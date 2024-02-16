<?php

/**
 * DataServiceJSONDir
 *
 * {DESCRIPTION}
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class DataServiceJSONDir extends DataService
{
    public $dir;
    #public $base_sql;
    public $fields;
    #public $main_table;

    public function __construct()
    {
        parent::__construct();
    }

    public function hasField($name)
    {
        if (!$this->fields) {
            $this->listFields();
        }
        return in_array($name, $this->fields);
    }

    public function listFields()
    {

        #$query = $this->dao->prepare('DESCRIBE ' . $this->main_table);
        #$query->execute();
        #$this->fields = $query->fetchAll(PDO::FETCH_COLUMN);
    }

    public function processOrder($value)
    {
        /*$return = '';
        $parts = explode(' ', $value);
        if (!$this->hasField($parts[0])) {
            return false;
        }
        $return = $parts[0];
        if (!empty($parts[1])) {
            $dir = strtoupper($parts[1]);
            if (in_array($dir, array('ASC', 'DESC'))) {
                $return .= ' ' . $dir;
            }
        }
        return $return;*/
    }

    public function run($get = array())
    {
        foreach ($get as $key => $value) {
            $value = utf8_decode(urldecode($value));
            $key   = str_replace('!', '', $key, $count);
            if ($data = $this->getField($key, $value)) {
                $this->data = $data;
            }
        }
    }

    public function saveData($data, $id)
    {
        // Should add validation stuff here.
        $file = $this->dir . '/' . $id . '.json';

        if (is_null(json_decode($data))) {
            $msg  = '<p><b>Error:</b> invalid json.</p>';
            $msg .= '<pre>' . $data . '</pre>';
            return $msg;
        }

        file_put_contents($file, $data);

        $msg  = '<p>Data successfully saved to ' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $file) . '</p>';
        $msg .= '<p>New file details:</p>';
        $msg .= '<p><b>File modified time:</b> ' .  date('r', filemtime($file)) . '</p>';
        $msg .= '<p><b>File contents:</b></p>';
        $msg .= '<pre>' . file_get_contents($file) . '</pre>';

        return $msg;

        #isset($this->data[$id])
        #echo "<pre>\n";var_dump($id);echo "</pre>\n";
        #echo "<pre>\n";var_dump($data);echo "</pre>\n";exit;
    }

    protected function parseValue($value, $field, $sql_fragment)
    {
    /*    $i      = 0;
        $j      = 0;
        $sql    = $value;
        $values = array();
        #$sql_fragment = '`' . $field . '` ' . $sql_fragment;
        // general processing here

        if (preg_match_all('/\(?([!~><=])?([^!~><=)]+)\)?/', $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                #echo "<pre>\n";var_dump($match);echo "</pre>\n";
                $delim = false;
                $op    = false;
                if (strpos($match[2], ',') !== false) {
                    $delim = ',';
                    $op    = ' OR ';
                } elseif (strpos($match[2], '_') !== false) {
                    $delim = '_';
                    $op    = ' AND ';
                }
                $segment = $match[2];
                $segment = str_replace(',', $op, $segment);
                #echo "Seg1<pre>\n";var_dump($segment);echo "</pre>\n";
                if ($delim) {
                    $vals = explode($delim, $match[2]);
                } else {
                    $vals = array($segment);
                }
                foreach ($vals as $val) {
                    // Copy $val as they may diverge and we need the original too:
                    $v      = $val;
                    // Process any comparisons:
                    $comp   = '';
                    $number = str_replace(array('gte', 'gt', 'lte', 'lt'), '', $val);
                    if (is_numeric($number)) {
                        $comp   = str_replace($number, '', $val);
                        $v      = $number;
                        switch ($comp) {
                            case 'gte':
                                $comp = '>=';
                                break;
                            case 'gt':
                                $comp = '>';
                                break;
                            case 'lte':
                                $comp = '<=';
                                break;
                            case 'lt':
                                $comp = '<';
                                break;
                            default:
                                $comp = '=';
                        } // switch
                    }


                    // Copy $sql_fragment as they may diverge and we need the original too:
                    $fragment = $sql_fragment;
                    // Copy $val_key as they may diverge and we need the original too:
                    $val_key  = ':' . str_replace('.', '', $field) . $i;
                    $vk       = $val_key;
                    #echo "<pre>\n";var_dump($val_key);echo "</pre>\n";
                    // If there's a comp, add it to $val_key for later replacement:
                    if (!empty($comp)) {
                        $val_key = $comp . ' ' . $val_key;
                    }

                    // Add the actual key and value to return array:
                    $values[$vk] = $v;
                    // Complete fragment and replace in segment:
                    if ($match[1] == '!') {
                        $fragment = 'NOT ' . $fragment;
                    }
                    $fragment = $field . ' ' . $fragment;
                    // Dodgy temporary _ replacement:
                    #$segment = str_replace($val, sprintf($fragment, str_replace('_', '|', $val_key)), $segment);
                    $segment = str_replace($val, sprintf($fragment, $val_key), $segment);
                    #echo "Seg2<pre>\n";var_dump($segment);echo "</pre>\n";
                    $i++;
                }
                // If it's not the first set of matches, add AND operator:
                if ($j > 0) {
                    $segment = 'AND ' . $segment;
                }
                // Update $sql with processed fragment:
                $sql = str_replace($match[2], $segment, $sql);
                // Tidy any leftover operators:
                // Dodgy temporary _ replacement:
                $sql = str_replace(array('!', '~'), ' ', $sql);
                // Undo dodgy temporary _ replacement:
                #$sql = str_replace('|', '_', $sql);

                $j++;
            }
        }

        $return = array('sql'=>$sql, 'values'=>$values);
        #echo "<pre>\n"; var_dump($return); echo "</pre>\n"; exit;
        return $return;*/
    }
}