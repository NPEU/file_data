<?php
/**
 * TopRecruiters
 *
 * Top recruiters data.
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class TopRecruiters extends DataServiceJSONDir
{

    public function __construct()
    {
        parent::__construct();
        $this->dir = $_SERVER['DOCUMENT_ROOT'] . '/datastore/top-recruiters';
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
        $data = [];
        $files = array_diff(scandir($this->dir), ['..', '.']);
        foreach ($files as $file) {
            $id = str_replace('.json', '', $file);
            $data[$id] = json_decode(file_get_contents($this->dir . '/' .$file));
        }
        $this->data = $data;
    }

    public function getId($value)
    {
        $ids = explode(',', trim($value, '()'));
        $data = [];
        foreach ($ids as $id) {
            $data[$id] = $this->data[$id];
        }
        return $data;
    }
}