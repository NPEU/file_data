<?php
/**
 * Research
 *
 * https://www.npeu.ox.ac.uk/data/research?id=7
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class Research extends DataServiceDB
{
    public $jan_dao;
    public $database;
    public $jdatabase;

    public function __construct($params)
    {
        parent::__construct($params);
        $hostname = 'localhost';

        if (DEV || TEST) {
            $database = 'intranet_petal_dev';
        } else {
            $database = 'intranet_petal';
        }

        $username = NPEU_DATABASE_USR;
        $password = NPEU_DATABASE_PWD;

        $this->database = $database;

        $this->dao = new PDO("mysql:host=$hostname;dbname=$database", $username, $password, array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;'
        ));

        $jhostname = 'localhost';
        if (DEV || TEST) {
            $jdatabase = 'jan_dev';
        } else {
            $jdatabase = 'jan';
        }
        $jusername = NPEU_DATABASE_USR;
        $jpassword = NPEU_DATABASE_PWD;

        $jusername = NPEU_DATABASE_USR;
        $jpassword = NPEU_DATABASE_PWD;


        $this->jdatabase = $jdatabase;

        $this->jan_dao     = new PDO("mysql:host=$jhostname;dbname=$jdatabase", $jusername, $jpassword, array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8;'
        ));

        $this->main_table  = '`' . $database . '`.`pow_projects`';

        $select = 'SELECT
            p.project_id AS id,
            p.title,
            p.short_title,
            p.alias,
            p.brand_project,
            p.pru,
            p.duration_start,
            p.duration_end,
            u.id AS contact_id,
            u.name AS contact_name,
            u.email AS contact_email,
            sp.profile_value AS contact_alias,
            p.external_ci,
            p.status_id,
            st.name AS status,
            p.page_id,
            p.joomla_menu_id,
            p.publications_query,
            p.included AS published,
            p.keywords,
            p.themes,
            pt.name AS pru_theme,
            p.featured,
            p.date_created,
            p.date_modified,
            t.html AS body,
            m.path AS related_page_uri,
            b.name AS brand_name';

        if ($this->is_staff || (($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) && (isset($_GET['amount']) && $_GET['amount'] === '1'))) {
            $select .= ',
            p.amount';
        }

        $select .= '
        FROM ' . $this->main_table . ' p';

        $this->base_sql    = $select;
        $this->base_sql   .= ' JOIN `' . $database . '`.`cms_component_text` t ON p.text_id = t.component_id';
        $this->base_sql   .= ' JOIN `' . $database . '`.`pow_contacts` c ON p.contact_id = c.contact_id';
        #$this->base_sql   .= ' JOIN auth_users u ON c.user_id = u.user_id';
        $this->base_sql   .= ' JOIN `' . $jdatabase . '`.`jancore_users` u ON c.user_id = u.id';
        $this->base_sql   .= ' JOIN `' . $jdatabase . '`.`jancore_user_profiles` sp ON (c.user_id = sp.user_id AND sp.profile_key = "staffprofile.alias")';
        $this->base_sql   .= ' LEFT JOIN `' . $jdatabase . '`.`jancore_menu` m ON p.joomla_menu_id = m.id';
        $this->base_sql   .= ' LEFT JOIN `' . $database . '`.`pow_status` st ON p.status_id = st.status_id';
        $this->base_sql   .= ' LEFT JOIN `' . $database . '`.`pow_pru_themes` pt ON p.pru_theme = pt.id';
        $this->base_sql   .= ' LEFT JOIN `' . $jdatabase . '`.`jancore_projects` b ON p.brand_project = b.alias';
        #$this->base_sql   .= ' JOIN pow_roles r ON p.project_id = r.project_id';

        #echo $this->base_sql; exit;

        $this->base_wheres = array(
            'p.status_id > 0'
        );
    }


    public function postQuery($data)
    {
        $themes = $this->getHelperThemes();
        foreach ($data as &$item) {
            #echo "<pre>\n"; var_dump($item); echo "</pre>\n"; exit;
            // Unencode title:
            $item['title'] = htmlspecialchars_decode($item['title'], ENT_QUOTES);

            // Add theme titles:
            $theme_keys = explode(', ', $item['themes']);
            $theme_titles = array();
            #echo "<pre>\n"; var_dump($themes); echo "</pre>\n"; #exit;
            foreach ($themes as $theme_title => $theme_info) {
                if (in_array($theme_info['alias'], $theme_keys)) {
                    $theme_titles[$theme_info['alias']] = $theme_title;
                }

            }
            $item['theme_titles'] = $theme_titles;
            #echo "<pre>\n"; var_dump($theme_titles); echo "</pre>\n"; exit;

            // Add publications:
            if (!empty($item['publications_query'])) {
                $pqs =  preg_replace('#(\r|\n)+#', ',', $item['publications_query']);
                $pq = 'call_number=' . $pqs . "&order=+FIELD(call_number,'" . str_replace(',', "','", $pqs) . "')&collect=type";
                $item['publications_query'] = $pq;

                /*
                This looped API request causes MASSIVE delays in overall request time.
                Without this time can be reduced from something liek 44 seconds to 1.3 seconds!
                $item['publications_data']  = json_decode(file_get_contents($_SERVER['DOMAIN'] . '/data/publications?' . $item['publications_query']), true);

                So, attempting to make the DB query directy:
                */
                $sql  = 'SELECT * FROM `' . $this->jdatabase . '`.`jancore_publications`';
                $sql .= " WHERE call_number = '" . str_replace(',', "' OR call_number = '", $pqs) . "'";
                $sql .= " ORDER BY FIELD(call_number,'" . str_replace(',', "','", $pqs) . "')";
                #$sql .= ';';

                #echo "<pre>\n"; var_dump($sql); echo "</pre>\n"; #exit;
                $publications = array();
                foreach ($this->dao->query($sql, PDO::FETCH_ASSOC) as $row)
                {
                    $publications[] = $row;
                }

                require_once (__DIR__ . '/Publications.php');
                $pub_service = new Publications();

                $collection_publications = $pub_service->getCollectedByType($publications);

                if (!empty($collection_publications)) {
                    $item['publications_data'] = $collection_publications;
                }

            }

            // Fix related_page_uri:
            $item['related_page_uri'] = trim($item['related_page_uri'], '/');

            // Add Investigators:
            // This section should be altered to work more liek the people helper, but not sure what
            // impact that may have so for the moment I'm adding the new fields (marled !!, but
            // keeping the old ones until I'm sure I can get rid of them.
            $sql  = 'SELECT
                r.role,
                fn.profile_value AS firstname,
                ln.profile_value AS lastname,
                u.email,
                u.block,
                n.first_name AS fn,
                n.last_name AS ln,
                n.email AS ne,
                i.name AS inst_name,
                i.city AS inst_city,
                i.country AS inst_country,
                al.profile_value AS alias,
                r.user_id,
                r.nonstaff_id,
                i.institution_id
                FROM `' . $this->database . '`.`pow_roles` r';
            $sql .= ' LEFT JOIN `' . $this->jdatabase . '`.`jancore_users` u ON r.user_id = u.id' . "\n";
            $sql .= ' LEFT JOIN `' . $this->database . '`.`anc_non_staff` n ON r.nonstaff_id = n.nonstaff_id' . "\n";
            $sql .= ' LEFT JOIN `' . $this->database . '`.`anc_institutions` i ON n.institution_id = i.institution_id' . "\n";
            $sql .= ' LEFT JOIN `' . $this->jdatabase . '`.`jancore_user_profiles` fn ON (r.user_id = fn.user_id AND fn.profile_key = "firstlastnames.firstname")' . "\n";
            $sql .= ' LEFT JOIN `' . $this->jdatabase . '`.`jancore_user_profiles` ln ON (r.user_id = ln.user_id AND ln.profile_key = "firstlastnames.lastname")' . "\n";
            $sql .= ' LEFT JOIN `' . $this->jdatabase . '`.`jancore_user_profiles` al ON (r.user_id = al.user_id AND al.profile_key = "staffprofile.alias")' . "\n";
            $sql .= ' WHERE project_id = ' . (int) $item['id'];

            // @TEMP - this may change when custom ordering is enabled.
            $sql .= ' ORDER BY lastname';

            #echo "<pre>\n"; var_dump($sql); echo "</pre>\n"; exit;

            foreach ($this->dao->query($sql, PDO::FETCH_ASSOC) as $row)
            {
                // !! Single id:
                $s_id = isset($row['user_id'])
                      ? $row['user_id']
                      : 'ns' . $row['nonstaff_id'];
                $row = array_merge(array('id' => $s_id), $row);


                // Tidy up non-staff first and last names:
                if (isset($row['fn'])) {
                    $row['firstname'] = $row['fn'];
                    $row['lastname']  = $row['ln'];
                }
                unset($row['fn'], $row['ln']);

                // Remove ex-staff emails:
                if ($row['block'] === '1') {
                    $row['email'] = NULL;
                }

                // Tidy up non-staff emails:
                if (isset($row['ne'])) {
                    $row['email'] = $row['ne'];
                }
                unset($row['ne']);

                // !! Force institution id
                if (empty($row['institution_id'])) {
                    $row['institution_id'] = $row['block'] === '0'
                                           ? '0'
                                           : '36';
                }

                // Set some defaults:
                $row['staff']   = false;
                if (!$row['user_id']) {
                    $row['user_id'] = false;
                }

                // If we have an active staff member:
                if ($row['user_id'] && $row['block'] === '0') {
                    $row['staff'] = true;
                    unset($row['inst_name'], $row['inst_city'], $row['inst_country']);
                } else {
                    // !! unset($row['alias']);
                    $row['alias'] = '';
                    $inst_full = $row['inst_name'];
                    if (!empty($row['inst_city'])) {
                        $inst_full .= ', ' . $row['inst_city'];
                    }
                    if (!empty($row['inst_country'])) {
                        $inst_full .= ', ' . $row['inst_country'];
                    }
                    $row['inst_full'] = $inst_full;
                }

                // !! Force insitution name:
                if (empty($row['inst_full'])) {
                    $row['institution'] = $row['block'] === '0'
                                        ? 'NPEU'
                                        : 'NPEU (Former member)';
                } else {
                    $row['institution'] = $row['inst_full'];
                }

                #echo "<pre>\n"; var_dump($row); echo "</pre>\n";
                unset($row['block']);

                switch ($row['role']) {
                    case 1:
                        $role = 'chief_investigators';
                        break;
                    case 2:
                        $role = 'other_investigators';
                        break;
                    case 3:
                        $role = 'other_staff';
                        break;
                }
                unset($row['role']);
                $item[$role][] = $row;
                #echo "<pre>\n"; var_dump($row); echo "</pre>\n";
            }

            // Add Funding bodies:
            $sql  = 'SELECT
                f.full_name AS funder_name,
                f.acronym AS funder_abbr
                FROM `' . $this->database . '`.`anc_funding_bodies` f';
            $sql .= ' LEFT JOIN `' . $this->database . '`.`pow_funding_body_2_project` f2p ON f.funding_body_id = f2p.funding_body_id' . "\n";
            $sql .= ' WHERE project_id = ' . (int) $item['id'];

            #echo "<pre>\n"; var_dump($sql); echo "</pre>\n"; exit;
            $funders = array();
            foreach ($this->dao->query($sql, PDO::FETCH_ASSOC) as $row)
            {
                $funders[] = $row;
            }

            if (!empty($funders)) {
                $item['funders'] = $funders;
            }
        }
        #echo "<pre>\n"; print_r($data); echo "</pre>\n"; exit;
        #exit;
        return $data;
    }

    public function getId($value)
    {
        return $this->parseValue($value, 'p.project_id', ' %s');
    }

    public function getDurationStart($value)
    {
        return $this->parseValue($value, 'duration_start', ' %s');
    }

    public function getDurationEnd($value)
    {
        return $this->parseValue($value, 'duration_end', ' %s');
    }

    public function getIncluded($value)
    {
        return $this->parseValue($value, 'p.included', ' %s');
    }


    public function getCollectedByTheme($data, $order = false)
    {
        $themes = $this->getHelperThemes($order);
        // Note: Can't use collectData here as project could belong to more that
        // one theme. Perhaps abstract this out as collectDataMany?
        $aliases = array();
        $container = array();

        foreach ($themes as $name => $meta) {
            $container[$name] = array();
            $aliases[$meta['alias']] = $name;
            if (!empty($meta)) {
                $container[$name] += $meta;
            }
        }

        foreach ($data as $item) {
            if (!empty($item['themes'])) {
                $item_themes = explode(',', str_replace(', ', ',', $item['themes']));
                foreach ($item_themes as $item_theme) {
                    $container[$aliases[$item_theme]]['projects'][] = $item;
                }

            }
        }

        foreach ($container as $key => $value) {
            if (isset($value['projects'])) {
                $length = count($value['projects']);
            } else {
                $length = 0;
            }
            $container[$key]['length'] = $length;
        }
        return $container;
    }

    public function getCollectedByPRUTheme($data, $order = false)
    {
        #echo "<pre>\n"; print_r($data); echo "</pre>\n"; exit;
        $pru_themes = $this->getHelperPRUThemes($order);
        #echo "<pre>\n"; var_dump($pru_themes); echo "</pre>\n"; exit;
        $data  = $this->collectData($data, array_keys($pru_themes), 'pru_theme', 'projects', $pru_themes);
        #$this->collectData($data, array_keys($display_groups), 'displaygroup', 'people', $display_groups);
        #echo "<pre>\n";var_dump($data);echo "</pre>\n";exit;
        return $data;
    }

    public function getHelperThemes($order = false)
    {
        $data  = array();

        if (!empty($order)) {
            $order = ' ORDER BY `' . $order . '`';
        } else {
            $order = ' ORDER BY `name`';
        }

        $sql = 'SELECT name, alias FROM `pow_themes`' . $order . ';';

        foreach ($this->dao->query($sql) as $row)
        {
            $name        = $row['name'];
            $data[$name] = array('alias' => $row['alias']);
        }
        return $data;
    }

    public function getHelperPRUThemes($order = false)
    {
        $data  = array();

        if (!empty($order)) {
            $order = ' ORDER BY `' . $order . '`';
        } else {
            $order = ' ORDER BY `name`';
        }

        $sql = 'SELECT name, alias FROM `pow_pru_themes`' . $order . ';';

        foreach ($this->dao->query($sql) as $row)
        {
            $name        = $row['name'];
            $data[$name] = array('alias' => $row['alias']);
        }

        #echo "<pre>\n"; var_dump($data); echo "</pre>\n"; exit;
        return $data;
    }

    public function getHelperPeople($order = false)
    {
        $data  = array();

        if (empty($order)) {
            $order = 'lastname,firstname';
        }

        $sql = [];
        $sql[] = '(SELECT';
        $sql[] = '    u.id AS id,';
        $sql[] = '    fn.profile_value AS firstname,';
        $sql[] = '    ln.profile_value AS lastname,';
        $sql[] = '    u.email AS email,';
        $sql[] = '    IF(u.block = 0, "0", "36") AS institution_id,';
        $sql[] = '    IF(u.block = 0, "NPEU", "NPEU (Former member)") AS institution,';
        $sql[] = '    IF(u.block = 0, al.profile_value, "") AS alias';
        $sql[] = 'FROM `' . $this->jdatabase . '`.`jancore_users` u';
        $sql[] = 'LEFT JOIN `' . $this->jdatabase . '`.`jancore_user_profiles` fn ON (u.id = fn.user_id AND fn.profile_key = "firstlastnames.firstname")';
        $sql[] = 'LEFT JOIN `' . $this->jdatabase . '`.`jancore_user_profiles` ln ON (u.id = ln.user_id AND ln.profile_key = "firstlastnames.lastname")';
        $sql[] = 'LEFT JOIN `' . $this->jdatabase . '`.`jancore_user_profiles` al ON (u.id = al.user_id AND al.profile_key = "staffprofile.alias")';
        $sql[] = 'JOIN `' . $this->jdatabase . '`.`jancore_user_usergroup_map` ug ON ug.`user_id` = u.`id` AND ug.`group_id` = 10';
        $sql[] = 'WHERE u.id != 601)';
        $sql[] = '';
        $sql[] = 'UNION';
        $sql[] = '';
        $sql[] = '(SELECT';
        $sql[] = '    CONCAT("ns", n.nonstaff_id) AS id,';
        $sql[] = '    n.first_name COLLATE utf8_unicode_ci AS firstname,';
        $sql[] = '    n.last_name COLLATE utf8_unicode_ci AS lastname,';
        $sql[] = '    n.email COLLATE utf8_unicode_ci AS email,';
        $sql[] = '    i.institution_id,';
        $sql[] = '    CONCAT_WS(", ", i.name, i.city, i.country) AS institution,';
        $sql[] = '    "" AS alias';
        $sql[] = 'FROM `' . $this->database . '`.`anc_non_staff` n';
        $sql[] = 'LEFT JOIN `' . $this->database . '`.`anc_institutions` i ON n.institution_id = i.institution_id';
        $sql[] = 'WHERE n.`active_state` = 1)';
        $sql[] = '';
        $sql[] = 'ORDER BY ' .  $order . ';';


        $sql = implode("\n", $sql);

        #echo "<pre>\n"; var_dump($sql); echo "</pre>\n"; exit;

        foreach ($this->dao->query($sql, PDO::FETCH_ASSOC) as $row)
        {
            $row['firstname']   = html_entity_decode($row['firstname'], ENT_QUOTES);
            $row['lastname']    = html_entity_decode($row['lastname'], ENT_QUOTES);
            $row['institution'] = html_entity_decode($row['institution'], ENT_QUOTES);
            $data[] = $row;
        }
        return $data;
    }

    public function getHelperContacts($order = false)
    {
        $data  = array();

        if (empty($order)) {
            $order = 'lastname,firstname';
        }

        $sql = [];
        $sql[] = 'SELECT';
        $sql[] = '    c.user_id AS id,';
        $sql[] = '    fn.profile_value AS firstname,';
        $sql[] = '    ln.profile_value AS lastname,';
        $sql[] = '    c.initials AS initials,';
        $sql[] = '    u.email AS email,';
        $sql[] = '    al.profile_value AS alias';
        $sql[] = 'FROM `' . $this->database . '`.`pow_contacts` c';
        $sql[] = 'LEFT JOIN `' . $this->jdatabase . '`.`jancore_users` u ON u.id = c.user_id';
        $sql[] = 'LEFT JOIN `' . $this->jdatabase . '`.`jancore_user_profiles` fn ON (c.user_id = fn.user_id AND fn.profile_key = "firstlastnames.firstname")';
        $sql[] = 'LEFT JOIN `' . $this->jdatabase . '`.`jancore_user_profiles` ln ON (c.user_id = ln.user_id AND ln.profile_key = "firstlastnames.lastname")';
        $sql[] = 'LEFT JOIN `' . $this->jdatabase . '`.`jancore_user_profiles` al ON (c.user_id = al.user_id AND al.profile_key = "staffprofile.alias")';
        $sql[] = 'JOIN `' . $this->jdatabase . '`.`jancore_user_usergroup_map` ug ON ug.`user_id` = c.`user_id` AND ug.`group_id` = 10';
        #$sql[] = 'WHERE u.id != 601)';
        $sql[] = 'ORDER BY ' .  $order . ';';


        $sql = implode("\n", $sql);

        #echo "<pre>\n"; var_dump($sql); echo "</pre>\n"; exit;

        foreach ($this->dao->query($sql, PDO::FETCH_ASSOC) as $row)
        {
            $row['firstname']   = html_entity_decode($row['firstname'], ENT_QUOTES);
            $row['lastname']    = html_entity_decode($row['lastname'], ENT_QUOTES);
            $data[] = $row;
        }
        return $data;
    }

    public function getHelperInstitutions($order = false)
    {
        $data  = array();

        if (empty($order)) {
            $order = 'name';
        }

        $sql = [];
        $sql[] = 'SELECT';
        $sql[] = '    i.institution_id AS id,';
        $sql[] = '    CONCAT_WS(", ", i.name, i.city, i.country) AS name';
        $sql[] = 'FROM `' . $this->database . '`.`anc_institutions` i';
        $sql[] = 'ORDER BY ' .  $order . ';';


        $sql = implode("\n", $sql);

        #echo "<pre>\n"; var_dump($sql); echo "</pre>\n"; exit;

        foreach ($this->dao->query($sql, PDO::FETCH_ASSOC) as $row)
        {
            $data[$row['id']] = html_entity_decode($row['name'], ENT_QUOTES);
        }
        return $data;
    }

    public function getHelperFunders($order = false)
    {
        $data  = array();

        if (empty($order)) {
            $order = 'name';
        }

        $sql = [];
        $sql[] = 'SELECT';
        $sql[] = '    f.funding_body_id AS id,';
        $sql[] = '    f.full_name AS name,';
        $sql[] = '    f.acronym AS acronym';
        $sql[] = 'FROM `' . $this->database . '`.`anc_funding_bodies` f';
        $sql[] = 'ORDER BY ' .  $order . ';';


        $sql = implode("\n", $sql);

        #echo "<pre>\n"; var_dump($sql); echo "</pre>\n"; exit;

        foreach ($this->dao->query($sql, PDO::FETCH_ASSOC) as $row)
        {
            $data[$row['id']] = array(
                'name'    => html_entity_decode($row['name'], ENT_QUOTES),
                'acronym' => html_entity_decode($row['acronym'], ENT_QUOTES)
            );
        }
        return $data;
    }

    public function getHelperBrandProjects($order = false)
    {
        $data  = array();

        if (empty($order)) {
            $order = 'name';
        }

        $sql = [];
        $sql[] = 'SELECT';
        $sql[] = '    id,';
        $sql[] = '    name,';
        $sql[] = '    alias';
        $sql[] = 'FROM `' . $this->jdatabase . '`.`jancore_projects`';
        $sql[] = 'ORDER BY ' .  $order . ';';


        $sql = implode("\n", $sql);

        #echo "<pre>\n"; var_dump($sql); echo "</pre>\n"; exit;

        foreach ($this->dao->query($sql, PDO::FETCH_ASSOC) as $row)
        {
            #$data[] = $row;
            $data[$row['id']]['name']  = $row['name'];
            $data[$row['id']]['alias'] = $row['alias'];
        }
        return $data;
    }
}