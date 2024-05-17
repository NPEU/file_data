<?php
/**
 * Staff
 *
 * https://www.npeu.ox.ac.uk/data/staff?id=602
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class Staff extends DataServiceDB
{
    public $sort_key = false;
    public $basic_data_only = false;

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

        $this->base_sql    = 'SELECT usr.id, usr.name, usr.email, usr.registerDate AS register_date FROM ' . $this->main_table . ' usr';
        $this->base_sql   .= ' JOIN `jancore_user_usergroup_map` ugmap ON usr.id = ugmap.user_id';
        $this->base_sql   .= ' JOIN `jancore_usergroups` ugp ON ugmap.group_id = ugp.id ';

        #echo $this->base_sql; exit;
        if (isset($_GET['external']) && $_GET['external'] === '1') {
            $this->base_wheres = [
                'ugp.title = "Staff" OR ugp.title = "External Staff"',
                'AND usr.block = 0'
            ];
        } else {
            $this->base_wheres = [
                'ugp.title = "Staff"',
                'AND usr.block = 0'
            ];
        }

        if (isset($_GET['basic']) && $_GET['basic'] === '1' ) {
            $this->basic_data_only = true;
            #var_dump($this->basic_data_only); exit;
        }
        #var_dump($this->basic_data_only); exit;
    }

    public function init()
    {
        #file_put_contents(__DIR__ . '/log.txt', $_SERVER['REMOTE_ADDR']);

        // This service relies on the staff profile Jooomla plugin:
        $profile_file = $_SERVER['DOCUMENT_ROOT'] . '/plugins/user/staffprofile/forms/profile.xml';
        if (!file_exists($profile_file)) {
            #trigger_error('Staff service relies on the staff profile jooomla plugin', E_USER_WARNING);
            return false;
        }
        return true;
    }

    public function postQuery($data)
    {
        #echo $_SERVER['REMOTE_ADDR']; exit;
        #echo "<pre>\n";var_dump($data);echo "</pre>\n";exit;
        $new_data = [];

        /*$not_basic_data = [
            'biography',
            'custom',
            'custom_title',
            'projects',
            'publications_manual',
            'publications_query',
            'team',
            'pa',
            'pa_details_only'
        ];*/
        $basic_data = [
            'id',
            'name',
            'email',
            'firstname',
            'lastname',
            'alias',
            'displaygroup',
            'role',
            'title',
            'qualifications',
            'avatar_img',
            'profile_img_src'
        ];

        foreach ($data as $key => $item) {

            $sql  = 'SELECT profile_key, profile_value FROM `jancore_user_profiles`';
            $sql .= ' WHERE user_id = ' . (int) $item['id'] . ' AND (profile_key LIKE "staffprofile.%" OR profile_key LIKE "firstlastnames.%")';
            $sql .= ' ORDER BY ordering';
            #echo "<pre>\n";var_dump($sql);echo "</pre>\n";
            foreach ($this->dao->query($sql) as $row) {
                $profile_key = str_replace(['staffprofile.', 'firstlastnames.'], '', $row['profile_key']);
                if ($this->basic_data_only) {
                    if(in_array($profile_key, $basic_data)) {
                        $item[$profile_key] = $row['profile_value'];
                    }
                } else {
                    $item[$profile_key] = $row['profile_value'];
                }
            }

            // Add any publications:
            if (!$this->basic_data_only) {
                $item['publications_uri']  = '';
                $item['publications_data'] = '';
            }
            if (!empty($item['publications_query'])) {
                #echo "<pre>\n";var_dump($item['publications']);echo "</pre>\n";
                $query = str_replace(["\r", "\n\n"], "\n", $item['publications_query']);
                #echo "<pre>\n";var_dump($query);echo "</pre>\n";exit;
                $f_query = DataHelpers::formatQuery($query);
                #echo "<pre>\n";var_dump($query);echo "</pre>\n";exit;
                #echo "<pre>\n";var_dump(preg_match_all('/(\d{1,3}|\d{4}-\d{2}|NPEU-\d+)\n/', $query, $matches));echo "</pre>\n";exit;
                if ($f_query) {
                    // Valid query formatted:
                    $data_uri = $_SERVER['DOMAIN'] . '/data/publications?' . $f_query;
                    $item['publications_uri']  = $data_uri;
                    $item['publications_data'] = json_decode(file_get_contents($data_uri), true);
                } elseif (preg_match_all('/(\d{1,3}|\d{4}-\d{2}|NPEU-\d+)\n/', $query, $matches)) {
                    // Entry may be a list of call numbers, so try that:
                    $pq  =  preg_replace('#(\r|\n)+#', ',', $item['publications_query']);
                    #echo "<pre>\n";var_dump($pq);echo "</pre>\n";exit;
                    $pq  = 'call_number=' . $pq . "&order=+FIELD(call_number,'" . str_replace(',', "','", $pq) . "')&collect=type";
                    $uri = $_SERVER['DOMAIN'] . '/data/publications?' . $pq;
                    $item['publications_uri']  = $uri;
                    $item['publications_data'] = json_decode(file_get_contents($uri ), true);
                }
            }
            #echo "<pre>\n";var_dump($item['publications']);echo "</pre>\n";
            #echo "<pre>\n";var_dump($item['publications_data']);echo "</pre>\n";
            #echo "<pre>\n";var_dump($item['publications_uri']);echo "</pre>\n";exit;
            #echo "<pre>\n";var_dump(isset($item['publications_data'][0]));echo "</pre>\n";exit;
            // Set profile img src:
            if (!empty($item['avatar_img'])) {
                $item['profile_img_src'] = $item['avatar_img'];
            } else {
                $item['profile_img_src'] = '/assets/images/avatars/_none.jpg';
            }

            #$item['profile_img_src_type'] = 'avatar';
            /* Leaving out Gravatar for now:
            #$item['profile_img_src_type'] = 'gravatar';
            if (!isset($item['use_gravatar'])) {
                $item['use_gravatar'] = '0';
            }
            // By default use avatar if there is one
            if (!empty($item['avatar_img'])) {
                $item['profile_img_src']      = $item['avatar_img'];
                #$item['profile_img_src_type'] = 'avatar';
            // If not, and use Gravatar is not 'No'...
            } elseif ($item['use_gravatar'] == '1') {
                // ...check if there's a Gravatar email and use that...
                if (!empty($item['gravatar_email'])) {
                    $item['profile_img_src'] = '//www.gravatar.com/avatar/' . md5($item['gravatar_email']);
                // ...otherwise use the default email for Gravatar
                } else {
                    $item['profile_img_src'] = '//www.gravatar.com/avatar/' . md5($item['email']);
                }
            // No avatar supplied and 'No' to use Gravatar, so use blank Gravatar
            } else {
                $item['profile_img_src'] = '//www.gravatar.com/avatar?d=mm';
            }
            /*--------------*/


            /*if (!empty($item['avatar_img'])) {
                $item['profile_img_src']      = $item['avatar_img'];
                $item['profile_img_src_type'] = 'avatar'];
            } elseif(!empty($item['gravatar_email'])) {
                $item['profile_img_src'] = '//www.gravatar.com/avatar/' . md5($item['gravatar_email']);
            } else {
                $item['profile_img_src'] = '//www.gravatar.com/avatar/' . md5($item['email']);
            }*/
            // Tidy biography:
            if (!empty($item['biography'])) {
                $item['biography'] = trim(preg_replace('/^<br\s?\/?>$/', '', $item['biography']));
            }

            // Get Team member details:
            if (!empty($item['team'])) {
                $members = [];
                $member_ids = json_decode($item['team']);
                $data_uri = $_SERVER['DOMAIN'] . '/data/staff?id=';

                foreach($member_ids as $member_id) {
                    $member = json_decode(file_get_contents($data_uri . $member_id . '&basic=1'), true);
                    // Team members may have left (account deactivated), so skip any empty results:
                    if (!isset($member[0])) {
                        continue;
                    }
                    $members[$member_id] = $member[0];
                }
                $item['team'] = $members;
            }

            // Get Project details:
            if (!empty($item['projects'])) {
                $projects = [];
                $project_ids = json_decode($item['projects']);

                /*$sql  = 'SELECT c.id, c.title, c.alias, fv.value AS long_title ';
                $sql .= 'FROM `jancore_categories` c ';
                $sql .= 'JOIN `jancore_fields_values` fv ON c.id = fv.item_id AND fv.field_id = 12 '; // Hard-coded value not robust/transferable.
                $sql .= 'WHERE id IN(' . implode(',', $project_ids) . ') ';
                $sql .= 'ORDER BY c.title;';*/
                $sql  = 'SELECT id ';
                $sql .= 'FROM `jancore_brands` ';
                $sql .= 'WHERE id IN(' . implode(',', $project_ids) . ') ';
                $sql .= 'ORDER BY name;';
                #echo "<pre>\n";var_dump($sql);echo "</pre>\n";exit;
                $profile_data = [];
                foreach ($this->dao->query($sql, PDO::FETCH_ASSOC) as $row) {
                    $projects[] = $row['id'];
                }

                $item['projects'] = $projects;
            }

            /*if (!empty($item['biography_id'])) {
                // Hard to get this through Joomla model as not in CSM here, but
                // probably not necessary as it's only in the content table
                // because profile table can't hold it, thus it would never be
                // run through plugins etc anyway.
                $sql    = 'SELECT introtext FROM `jancore_content` WHERE id = ' . $item['biography_id'] . ';';
                $stmt   = $this->dao->query($sql);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $item['biography'] = trim(preg_replace('/^<br \/>$/', '', $result['introtext']));
            }*/
            // Add custom tab:
            /*if (!empty($item['custom_id'])) {
                // Hard to get this through Joomla model as not in CSM here, but
                // probably not necessary as it's only in the content table
                // because profile table can't hold it, thus it would never be
                // run through plugins etc anyway.
                $sql    = 'SELECT introtext FROM `jancore_content` WHERE id = ' . $item['custom_id'] . ';';
                $stmt   = $this->dao->query($sql);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $item['custom_content'] = trim(preg_replace('/^<br \/>$/', '', $result['introtext']));
            }*/
            // Add manual publications:
            if (!empty($item['publications'])) {
                $item['publications'] = trim(preg_replace('/^<br\s?\/?>$/', '', $item['publications']));
            }

            /*if (!empty($item['publications_id'])) {
                // Hard to get this through Joomla model as not in CSM here, but
                // probably not necessary as it's only in the content table
                // because profile table can't hold it, thus it would never be
                // run through plugins etc anyway.
                $sql    = 'SELECT introtext FROM `jancore_content` WHERE id = ' . $item['publications_id'] . ';';
                $stmt   = $this->dao->query($sql);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $item['publications_manual'] = trim(preg_replace('/^<br \/>$/', '', $result['introtext']));
            }*/
            // Maybe temporary?
            if (!isset($item['alias'])) {
                $item['alias'] = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $item['firstname'] . ' ' . $item['lastname']))) . '-' . $item['id'];
            }


            // PA
            if (!empty($item['pa'])) {
                $pa_data = json_decode(file_get_contents($_SERVER['DOMAIN'] . '/data/staff?id=' . $item['pa'] . '&basic=1'), true);
                $item['pa_details'] = $pa_data[0];
            }

            // Remove private data if not NPEU IP address:
            #$item['ip'] = $_SERVER['REMOTE_ADDR'];
            #$item['t'] = strpos($_SERVER['REMOTE_ADDR'], '129.67');
            unset(
                $item['biography_id'],
                $item['publications_id'],
                $item['custom_id']
            );

            // 192.168.242.18
            // 10.192.18.10
            if ($this->basic_data_only || (strpos($_SERVER['REMOTE_ADDR'], '129.67') !== 0 && $_SERVER['REMOTE_ADDR'] != '10.192.18.10' && $_SERVER['REMOTE_ADDR'] != '192.168.242.23')) {
                unset(
                    $item['tel'],
                    $item['room'],
                    $item['register_date']
                );
            }

            $new_key = $key;
            if ($this->sort_key && isset($item[$this->sort_key])) {
                if ($this->sort_key == 'lastname') {
                    $new_key = $item['lastname'] . '-' . $item['firstname'] . '-' . $item['id'];
                } else {
                    $new_key = $item[$this->sort_key];
                }
            }
            #echo "<pre>\n";var_dump($new_key);echo "</pre>\n";
            $new_data[$new_key] = $item;
            #echo "<pre>\n";var_dump($new_data);echo "</pre>\n";
        }
        ksort($new_data);
        #echo "<pre>"; var_dump( $new_data ); echo "</pre>"; exit;
        return $new_data;
    }

    public function processOrder($value)
    {
        #echo "<pre>"; var_dump( $value ); echo "</pre>"; exit;
        $result = parent::processOrder($value);
        if ($result === false) {
            $this->sort_key = $value;
        }
        return $result;
        #echo "<pre>"; var_dump( $this->sort_key ); echo "</pre>"; exit;
    }

    public function getAlias($value)
    {
        return $this->parseValue($value, 'alias', '= %s');
    }

    public function getDisplaygroup($value)
    {
        return $this->parseValue($value, 'alias', '= %s');
    }

    public function getCollectedByDisplaygroup($data, $order = false)
    {
        $display_groups = $this->getHelperDisplaygroup($order);
        $data           = $this->collectData($data, array_keys($display_groups), 'displaygroup', 'people', $display_groups);
        #echo "<pre>"; var_dump( $data ); echo "</pre>"; exit;
        return $data;
    }

    public function getHelperDisplaygroup($order = false)
    {
        $form_string = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/plugins/user/staffprofile/forms/profile.xml');
        $form_xml    = new SimpleXMLElement($form_string);
        $option_objs = $form_xml->xpath('//field[@name="displaygroup"]/option[@value!=""]');
        $data        = [];
        foreach ($option_objs as $node) {
            $name   = (string) $node;
            $meta   = ['alias' => preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', strtolower($name)))];
            $data[$name] = $meta;

        }
        #echo "<pre>\n";var_dump($data);echo "</pre>\n";exit;
        return $data;
    }

    public function getId($value)
    {
        return $this->parseValue($value, 'usr.id', ' %s');
    }

    public function getName($value)
    {
        return $this->parseValue($value, 'name', '= %s');
    }

    /*public function getAuthors($value)
    {
        return $this->parseValue($value, 'authors', 'LIKE CONCAT("%%", %s, "%%")');
    }

    public function getFullentry($value)
    {
        return $this->parseValue($value, 'full_entry', 'LIKE CONCAT("%%", %s, "%%")');
    }

    public function getYear($value)
    {
        return $this->parseValue($value, 'year', ' %s');
    }

    */
}