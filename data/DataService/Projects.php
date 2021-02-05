<?php
/**
 * Projects
 *
 * https://www.npeu.ox.ac.uk/data/projects?g=trials
 * https://www.npeu.ox.ac.uk/data/projects?g=sheer
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class Projects extends DataServiceDB
{
    #public $sort_key = false;
    public $order = 'title';

    public function __construct()
    {
        parent::__construct();

        $jhostname = 'localhost';

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

        $this->dao = new PDO("mysql:host=$jhostname;dbname=$jdatabase", $jusername, $jpassword, array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;'
        ));

        $this->main_table  = '`jancore_categories`';
        $this->base_sql    = 'SELECT c.id AS pr_catid, c.title AS name, c.alias, c.published AS status, fv1.value AS long_title, fv3.value AS project_group, fv4.value AS color, fv5.value AS logo_svg, fv6.value AS decoration_svg FROM ' . $this->main_table . ' c';
        $this->base_sql   .= ' JOIN `jancore_fields_values` fv1 ON fv1.field_id = 12 AND fv1.item_id = c.id';
        $this->base_sql   .= ' JOIN `jancore_fields_values` fv2 ON fv2.field_id = 6 AND fv2.item_id = c.id';
        $this->base_sql   .= ' JOIN `jancore_fields_values` fv3 ON fv3.field_id = 7 AND fv3.item_id = c.id';
        $this->base_sql   .= ' LEFT JOIN `jancore_fields_values` fv4 ON fv4.field_id = 9 AND fv4.item_id = c.id';
        $this->base_sql   .= ' LEFT JOIN `jancore_fields_values` fv5 ON fv5.field_id = 10 AND fv5.item_id = c.id';
        $this->base_sql   .= ' LEFT JOIN `jancore_fields_values` fv6 ON fv6.field_id = 11 AND fv6.item_id = c.id';

        $this->base_wheres = array(
            'fv2.value = "yes"',
            ' AND c.published > 0',
        );
    }

    public function getG($value)
    {
        return $this->parseValue($value, 'fv3.value', '= %s');
    }

    public function getId($value)
    {
        return $this->parseValue($value, 'c.id', ' %s');
    }

}