<?php
#ini_set('display_errors', 'On');
// Include the DB-IP class
require "dbip.class.php";
function get_country_by_ip($ip) {
    try {
        // Connect to the database
        $hostname = 'localhost';
        $username = 'scriptusermin';
        $password = 'aw00gaaw00ga';
        $database = 'geoip';
        $db       = new PDO("mysql:host=$hostname;dbname=$database", $username , $password);

        // Instantiate a new DBIP object with the database connection
        $dbip = new DBIP($db);

        // Lookup an IP address
        $data = $dbip->Lookup($ip);
        
        // Return the associated country
        return $data->country_name;

    } catch (DBIP_Exception $e) {

        return "error: {$e->getMessage()}";

    }
}

function get_country_by_ip_js($ip) {
    $country = get_country_by_ip($ip);
    #echo '<pre>'; var_dump($ip); '</pre>';exit;
    if (strpos($country, 'error') === 0) {
        return "false;\nconsole.log('" . $country . "')";
    }
    return "'$country'";
}

#$country = get_country_by_ip($_SERVER['REMOTE_ADDR']);
#echo '<pre>'; var_dump($country); '</pre>';
?>