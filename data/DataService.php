<?php

/**
 * DataService
 *
 * {DESCRIPTION}
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
abstract class DataService
{
    public $base_url;
    public $base_wheres;

    public $group         = false;
    public $helpers       = false;
    public $helpers_order = false;
    public $limit         = false;
    public $order         = false;


    public $is_staff      = false;

    protected $data;

    public function __construct($params = array())
    {
        if (!empty($params['is_staff'])) {
            $this->is_staff = $params['is_staff'];
        }
        if (!$this->init()) {
            echo 'Could not continue.'; exit;
        }
    }

    public function init()
    {
        return true;
    }

    public function getBaseWheres()
    {
        return $this->base_wheres;
    }

    public function getField($name, $values)
    {
        $method = 'get' . ucfirst(strtolower(str_replace('_', '', $name)));
        if (method_exists($this, $method)) {
            return $this->$method($values);
        }
        return false;
    }

    public function getData()
    {
        return $this->data;
    }

    public function hasField($name)
    {
    }

    public function listFields()
    {
    }

    public function postQuery($data)
    {
        return $data;
    }

    public function run($get = array())
    {
        if (isset($get['group'])) {
            #$this->callback = $get['group']; <- why was this 'callback'? Changed to 'group'.
            $this->group = $get['group'];
            unset($get['group']);
        }

        /*if (isset($get['helpers'])) {
            $this->helpers = $get['helpers'];
            unset($get['helpers']);
        }

        if (isset($get['helpers_order'])) {
            $this->helpers_order = $get['helpers_order'];
            unset($get['helpers_order']);
        }*/

        if (isset($get['limit'])) {
            $this->limit = $get['limit'];
            unset($get['limit']);
        }

        if (isset($get['order'])) {
            $this->order = $get['order'];
            unset($get['order']);
        }
        return $get;
    }

    public function saveData($data, $id)
    {
        return false;
    }

    protected function collectData($data = array(), $keys = array(), $field = '', $collect_name = '', $meta = array())
    {
        $container = array();
        if (empty($keys)) {
            $keys = array('*');
            $meta = array('*'=>array('alias'=>'*'));
        }
        foreach ($keys as $key) {
            $container[$key] = array();
            if (!empty($meta)) {
                $container[$key] += $meta[$key];
            }
        }
        foreach ($data as $item) {
            if (!empty($item[$field])) {
                $container[$item[$field]][$collect_name][] = $item;
            } else {
                $container['*'][$collect_name][] = $item;
            }
        }
        foreach ($container as $key => $value) {
            if (isset($value[$collect_name])) {
                $length = count($value[$collect_name]);
            } else {
                $length = 0;
            }
            $container[$key]['length'] = $length;
        }
        return $container;
    }
}