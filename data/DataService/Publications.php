<?php
/**
 * Publications
 *
 * https://www.npeu.ox.ac.uk/data/publications?authors=Plugge
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class Publications extends DataServiceDB
{



    public function __construct()
    {
        parent::__construct();

        if (DEV || TEST) {
            ini_set('display_errors', 1);
        }

        $jdatabase = 'jan';
        $domain = str_replace('.npeu.ox.ac.uk', '', $_SERVER['SERVER_NAME']);
        if ($domain != 'www') {
            $jdatabase .= '_' . $domain;
        }

        $jhostname = 'localhost';
        $jusername = NPEU_DATABASE_USR;
        $jpassword = NPEU_DATABASE_PWD;

        $this->dao     = new PDO("mysql:host=$jhostname;dbname=$jdatabase", $jusername, $jpassword, [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;'
        ]);
        $this->main_table  = '`jancore_publications`';

        $this->base_sql    = 'SELECT * FROM ' . $this->main_table;
        $this->base_wheres = [];
    }

    public function getAuthors($value)
    {
        return $this->parseValue($value, 'authors', 'LIKE CONCAT("%%", %s, "%%")');
    }

    public function getFullentry($value)
    {
        return $this->parseValue($value, 'full_entry', 'LIKE CONCAT("%%", %s, "%%")');
    }

    public function getCallNumber($value)
    {
        return $this->parseValue($value, 'call_number', '= %s');
    }

    public function getType($value)
    {
        return $this->parseValue($value, 'type', '= %s');
    }

    public function getYear($value)
    {
        return $this->parseValue($value, 'year', ' %s');
    }

    public function getCollectedByYear($data, $order = false)
    {
        $years = $this->getHelperYears($order);
        $data  = $this->collectData($data, $years, 'year', 'publications');
        return $data;
    }

    public function getCollectedByType($data, $order = false)
    {
        $types = $this->getHelperTypes($order);
        $data  = $this->collectData($data, $types, 'type', 'publications');
        return $data;
    }

    public function getHelperYears($order = false)
    {
        $data = [];
        if (!empty($order)) {
            $order = ' ORDER BY `year` ' . $order;
        }
        $sql = 'SELECT DISTINCT(`year`) FROM ' . $this->main_table . $order . ';';
        foreach ($this->dao->query($sql) as $row)
        {
            $data[] = $row['year'];
        }
        return $data;
    }

    public function getHelperTypes($order = false)
    {
        $data = [];
        if (!empty($order)) {
            $order = ' ORDER BY `type` ' . $order;
        }
        $sql = 'SELECT DISTINCT(`type`) FROM ' . $this->main_table . $order . ';';
        foreach ($this->dao->query($sql) as $row)
        {
            $data[] = $row['type'];
        }
        return $data;
    }
}