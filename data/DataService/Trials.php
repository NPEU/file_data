<?php
/**
 * Trials
 *
 * Trials data
 * https://www.npeu.ox.ac.uk/data/trials?id=40
 * https://www.npeu.ox.ac.uk/data/trials?collect=status
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class Trials extends DataServiceDB
{
    public $jan_dao;
    public $database;
    public $jdatabase;
    public $order = 'title';

    public function __construct()
    {
        parent::__construct();

        $hostname  = 'localhost';
        $jhostname = 'localhost';

        if (DEV) {
            $database  = 'trials_dev';
            $jdatabase = 'jan_dev';
            ini_set('display_errors', 1);
        } elseif (TEST) {
            $database  = 'trials_test';
            $jdatabase = 'jan_test';
            ini_set('display_errors', 1);
        } else {
            $database  = 'trials';
            $jdatabase = 'jan';
        }

        $username  = NPEU_DATABASE_USR;
        $password  = NPEU_DATABASE_PWD;
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

        $this->main_table  = '`' . $database . '`.`trials_data`';

        $this->base_sql    = 'SELECT d.*, b.primary_colour, b.secondary_colour, b.tertiary_colour, b.logo_svg, b.logo_svg_with_fallback, b.logo_svg_path, b.logo_png_path, b.params';
        $this->base_sql   .= "\n" . ' FROM ' . $this->main_table . ' d';
        $this->base_sql   .= "\n" . ' LEFT JOIN `' . $jdatabase . '`.`jancore_brands` b ON d.alias = b.alias';
        $this->base_wheres = array(
            'web_include = "Y"',
        );
    }

    public function postQuery($data)
    {
        $public_root_path = realpath($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR;
        foreach ($data as &$item) {

            $image_path       = $public_root_path . trim($item['logo_png_path'], '/');
            if (file_exists($image_path) && is_file($image_path)) {
                $image_info       = getimagesize($image_path);
                $image_real_ratio = $image_info[0] / $image_info[1];

                $height = 120;
                if ($image_info[0] > $image_info[1]) {
                    $width = round($height * $image_real_ratio);
                } else {
                    $width = round($height / $image_real_ratio);
                }
                $item['fallback_logo_height'] = $height;
                $item['fallback_logo_width']  = $width;
            }
        }
        return $data;
    }

    public function getId($value)
    {
        return $this->parseValue($value, 'd.id', ' %s');
    }

    public function getLanding($value)
    {
        return $this->parseValue($value, 'web_landing_include', '= %s');
    }

    public function getStatus($value)
    {
        return $this->parseValue($value, 'status', '= %s');
    }

    public function getSupportedtrial($value)
    {
        return $this->parseValue($value, 'supported_trial', '= %s');
    }

    public function getAlias($value)
    {
        return $this->parseValue($value, 'd.alias', '= %s');
    }


    public function getCollectedByStatus($data, $order = false)
    {
        $statuses = $this->getHelperStatuses($order);
        $data  = $this->collectData($data, $statuses, 'status', 'trials');
        #$this->collectData($data, array_keys($display_groups), 'displaygroup', 'people', $display_groups);
        #echo "<pre>\n";var_dump($data);echo "</pre>\n";exit;
        return $data;
    }

    public function getHelperStatuses($order = '')
    {
        $data = array();
        if (!empty($order)) {
            $order = ' ORDER BY `status` ' . $order;
        }
        $sql = 'SELECT DISTINCT(`status`) FROM ' . $this->main_table . $order . ';';
        foreach ($this->dao->query($sql) as $row)
        {
            $data[] = $row['status'];
        }
        return $data;
    }

}