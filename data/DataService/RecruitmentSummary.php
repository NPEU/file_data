<?php
/**
 * RecruitmentSummary
 *
 * {DESCRIPTION}
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class RecruitmentSummary extends DataServiceJSONDir
{

    public function __construct()
    {
        parent::__construct();
        $this->dir = $_SERVER['DOCUMENT_ROOT'] . '/datastore/recruitment-summary';
        /*if (LOCALHOST) {
            if (DEV) {
                $jdatabase = 'jan_dev';
            } else {
                $jdatabase = 'jan';
            }

        } else {
            if (DEV) {
                $jdatabase = 'jan_dev';
            } else {
                $jdatabase = 'jan';
            }
            $jhostname = 'localhost';
            $jusername = 'scriptusermed';
            $jpassword = 'squ1l00kal';
        }*/
        $data = array();
        $files = array_diff(scandir($this->dir), array('..', '.'));
        foreach ($files as $file) {
            $id = str_replace('.json', '', $file);
            $data[$id] = json_decode(file_get_contents($this->dir . '/' .$file));
        }
        $this->data = $data;
    }

    public function getId($value)
    {
        $ids = explode(',', trim($value, '()'));
        $data = array();
        foreach ($ids as $id) {
            $data[$id] = $this->data[$id];
        }
        return $data;
    }
}