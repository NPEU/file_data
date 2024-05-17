<?php
/**
 * User
 *
 * Get User info for internal use:  * https://dev.npeu.ox.ac.uk/data/user?id=602
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class User extends DataServiceDB
{
    public $sort_key = false;


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
        $this->main_table  = '`jancore_users`';

        $this->base_sql    = 'SELECT usr.id, usr.username, usr.password, usr.block, usr.email FROM ' . $this->main_table . ' usr';
        $this->base_sql   .= ' JOIN `jancore_user_usergroup_map` ugmap ON usr.id = ugmap.user_id';
        $this->base_sql   .= ' JOIN `jancore_usergroups` ugp ON ugmap.group_id = ugp.id ';

        /*$this->base_wheres = [
            'usr.block = 0'
        ];*/

        // Users may not explicitly a Staff member so I can't really limit the query at this stage
        // (at least my SQL isn't good enough to write that query)

        /*$this->main_table  = '`jancore_users`';
        $this->base_sql    = 'SELECT usr.id, usr.username, usr.password FROM ' . $this->main_table . ' usr';
        $this->base_sql   .= ' JOIN `jancore_user_usergroup_map` ugmap ON usr.id = ugmap.user_id';
        $this->base_sql   .= ' JOIN `jancore_usergroups` ugp ON ugmap.group_id = ugp.id ';

        $this->base_wheres = [
            'ugp.title = "Staff"',
            'AND usr.block = 0'
        ];*/
    }

    public function init()
    {
        #echo $_SERVER['REMOTE_ADDR'];
        if (!in_array($_SERVER['REMOTE_ADDR'], NPEU_SAFE_IPS)) {
            #trigger_error('', E_USER_ERROR);
            return false;
        }

        return true;
    }

    public function run($get = [])
    {
        // Require a username parameter:
        if (!isset($get['username']) && !isset($get['id'])) {
            //trigger_error('Username required', E_USER_ERROR);
            echo 'Username or ID required.'; exit;
        }
        parent::run($get);
    }

    public function postQuery($data)
    {
        #echo "Data:<pre>"; var_dump( $data ); echo "</pre>"; exit;
        if (count($data) == 0) {
            return $data;
        }
        $data = $data[0];
        #echo "Data:<pre>"; var_dump( $data ); echo "</pre>"; exit;


        $sql  = 'SELECT profile_key, profile_value FROM `jancore_user_profiles`';
        //$sql .= ' WHERE user_id = ' . (int) $data['id'] . ' AND (profile_key LIKE "firstlastnames.%")';
        $sql .= ' WHERE user_id = ' . (int) $data['id'] . ' AND (profile_key LIKE "staffprofile.%" OR profile_key LIKE "firstlastnames.%")';
        $sql .= ' ORDER BY ordering';
        #echo "<pre>\n";var_dump($sql);echo "</pre>\n";
        $profile_data = [];
        foreach ($this->dao->query($sql) as $row) {
            $profile_key = str_replace(['staffprofile.', 'firstlastnames.'], '', $row['profile_key']);
            $profile_data[$profile_key] = $row['profile_value'];
        }
        //echo "Data:<pre>"; var_dump( $profile_data ); echo "</pre>"; exit;

        // Pick the data we want specifically:
        $data['firstname'] = $profile_data['firstname'];
        $data['lastname'] = $profile_data['lastname'];
        $data['lastname'] = $profile_data['lastname'];

        if (!empty($profile_data['avatar_img'])) {
            $data['profile_img_src'] = $profile_data['avatar_img'];
        } else {
            $data['profile_img_src'] = '/assets/images/avatars/_none.jpg';
        }


        //echo "Data:<pre>"; var_dump( $data ); echo "</pre>"; exit;



        $all_groups            = [];
        $explicit_users_groups = [];
        $all_users_groups      = [];

        $sql  = 'SELECT ugp.id AS id, ugp.parent_id AS parent_id, ugp.title AS title FROM `jancore_users` usr JOIN `jancore_user_usergroup_map` ugmap ON usr.id = ugmap.user_id JOIN `jancore_usergroups` ugp ON ugmap.group_id = ugp.id';
        $sql .= ' WHERE user_id = ' . (int) $data['id'];

        foreach ($this->dao->query($sql) as $row) {
            $explicit_users_groups[$row['id']] = [
                'title'     => $row['title'],
                'parent_id' => $row['parent_id']
            ];
        }

        $sql = 'SELECT id, parent_id, title FROM `jancore_usergroups` ORDER BY lft;';

        foreach ($this->dao->query($sql) as $row) {
            $all_groups[$row['id']] = [
                'title'     => $row['title'],
                'parent_id' => $row['parent_id']
            ];
        }

        #echo "<pre>"; var_dump($all_groups); echo "</pre>";
        //echo "<pre>"; var_dump($parents); echo "</pre>";


        $queue = $explicit_users_groups;
        while (!empty($queue)) {
            foreach ($queue as $id => $group) {
                if (!array_key_exists($id, $all_users_groups)) {
                    // add this item to the groups array:
                    $all_users_groups[$id] = $group['title'];
                    // Add the parent to the queue:
                    if ($group['parent_id'] > 0) {
                        $queue[$group['parent_id']] = $all_groups[$group['parent_id']];
                    }
                }
                unset($queue[$id]);
            }
        }

        #echo "<pre>"; var_dump($all_users_groups); echo "</pre>";

        $ordered_users_groups_flat = [];
        $ordered_users_groups_path = [];
        // Loop trough $all_groups (which is in the correct order) to correctly order the users'
        // groups:
        foreach ($all_groups as $id => $group) {
            if ($id > 1 && array_key_exists($id, $all_users_groups)) {
                $ordered_users_groups_flat[] = $group['title'];

                $path = $group['title'];
                if (array_key_exists($group['parent_id'], $ordered_users_groups_path)) {
                    $path = $ordered_users_groups_path[$group['parent_id']] . '.' . $path;
                }
                $ordered_users_groups_path[$id] = $path;
            }

        }

        $ordered_users_groups_nested = [];
        foreach($ordered_users_groups_path as $element){
            $this->assignArrayByPath($ordered_users_groups_nested, $element);
        }


        #echo "<pre>"; var_dump($ordered_users_groups_flat); echo "</pre>";
        #echo "<pre>"; var_dump($ordered_users_groups_path); echo "</pre>";
        #echo "<pre>"; var_dump($ordered_users_groups_nested); echo "</pre>";
        #echo "<pre>"; var_dump(json_encode($ordered_users_groups_nested)); echo "</pre>";
        #exit;


        $data['groups_flat']   = $ordered_users_groups_flat;
        $data['groups_path']   = array_values($ordered_users_groups_path);
        $data['groups_nested'] = $ordered_users_groups_nested;
        return $data;
    }

    // Taken from: http://stackoverflow.com/questions/15132659/convert-an-array-of-strings-each-string-has-dot-separated-values-to-a-multidim
    public function assignArrayByPath(&$arr, $path) {
        $keys = explode('.', $path);

        while ($key = array_shift($keys)) {
            $arr = &$arr[$key];
        }
    }

    public function getUsername($value)
    {
        return $this->parseValue($value, 'username', '= %s');
    }

    public function getId($value)
    {
        return $this->parseValue($value, 'usr.id', ' %s');
    }

}