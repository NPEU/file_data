<?php
/**
 * SHEER
 *
 * https://www.npeu.ox.ac.uk/data/sheer?state=1
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class Sheer extends DataServiceDB
{
	public function __construct()
	{
		parent::__construct();

        $hostname  = 'localhost';
        $jhostname = 'localhost';

        if (DEV) {
            $database  = 'sheer_dev';
            $jdatabase = 'jan_dev';
            ini_set('display_errors', 1);
        } elseif (TEST) {
            $database  = 'sheer_test';
            $jdatabase = 'jan_test';

        } else {
            $database  = 'sheer';
            $jdatabase = 'jan';
            ini_set('display_errors', 1);
        }

        $username = NPEU_DATABASE_USR;
        $password = NPEU_DATABASE_PWD;
        $jusername = NPEU_DATABASE_USR;
        $jpassword = NPEU_DATABASE_PWD;


        $this->database  = $database;
        $this->jdatabase = $jdatabase;

        $this->dao = new PDO("mysql:host=$hostname;dbname=$database", $username, $password, array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;'
        ));

        $this->jan_dao = new PDO("mysql:host=$jhostname;dbname=$jdatabase", $jusername, $jpassword, array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;'
        ));

        $this->main_table  = '`' . $database . '`.`sheer_data`';


        $this->base_sql    = 'SELECT d.*, b.primary_colour, b.secondary_colour, b.tertiary_colour, b.logo_svg, b.logo_svg_with_fallback, b.logo_svg_path, b.logo_png_path, b.params';
		$this->base_sql   .= "\n" . ' FROM ' . $this->main_table . ' d';
		$this->base_sql   .= "\n" . ' JOIN `' . $jdatabase . '`.`jancore_brands` b ON d.alias = b.alias';
	}

    public function postQuery($data)
    {
        $public_root_path  = realpath($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR;
        foreach ($data as &$item) {
            $item['image_info'] = getimagesize($public_root_path . $item['logo_png_path']);

            $w = $item['image_info'][0];
            $h = $item['image_info'][1];
            $image_ratio = ($w < $h) ? ($w / $h) : ($h / $w);

            $item['image_info']['ratio'] = $image_ratio;
        }

        return $data;
    }


	public function getState($value)
	{
		return $this->parseValue($value, 'd.state', '%s');
	}


}