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

        $jhostname  = 'localhost';

        if (DEV) {
            $jdatabase = 'jan_dev';
            ini_set('display_errors', 1);
        } elseif (TEST) {
            $jdatabase = 'jan_test';
            ini_set('display_errors', 1);
        } else {
            $jdatabase = 'jan';
        }
    
        $jusername = NPEU_DATABASE_USR;
        $jpassword = NPEU_DATABASE_PWD;

		$this->dao     = new PDO("mysql:host=$jhostname;dbname=$jdatabase", $jusername, $jpassword, array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;'
		));

		$this->main_table  = '`jancore_publications`';
		$this->base_sql    = 'SELECT * FROM ' . $this->main_table;
		$this->base_wheres = array();
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
        #echo "<pre>\n";var_dump($years);echo "</pre>\n";exit;
		$data  = $this->collectData($data, $years, 'year', 'publications');
		#$this->collectData($data, array_keys($display_groups), 'displaygroup', 'people', $display_groups);
		#echo "getCollectedByYear<pre>\n";var_dump($data);echo "</pre>\n";exit;
		return $data;
	}

	public function getCollectedByType($data, $order = false)
	{
		$types = $this->getHelperTypes($order);
		$data  = $this->collectData($data, $types, 'type', 'publications');
		#$this->collectData($data, array_keys($display_groups), 'displaygroup', 'people', $display_groups);
		#echo "<pre>\n";var_dump($data);echo "</pre>\n";exit;
		return $data;
	}

	public function getHelperYears($order = false)
	{
		$data = array();
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
		$data = array();
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