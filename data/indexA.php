<?php
#echo "<pre>\n"; var_dump($_GET); echo "</pre>\n";;
#echo "<pre>\n"; var_dump($_SERVER); echo "</pre>\n";
#exit;
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

set_include_path(implode(PATH_SEPARATOR, array(
	'DataService',
	get_include_path(),
	)));

spl_autoload_register(create_function('$class',
	"include str_replace('_', '/', \$class) . '.php';"
	));

$classname = ucfirst(trim($_SERVER['PATH_INFO'], '/'));
$service   = new $classname;

// Initialise:
$order         = false;
$group         = false;
$callback      = false;
$helpers       = false;
$collect       = false;
$collect_order = false;

$dao      = $service->dao;
$main_sql = $service->main_sql;
$sql_map  = $service->sql_map;
$wheres   = array();
$values   = array();

$i = 0;
foreach ($_GET as $key => $value) {
	if ($key == 'order') {
		$order = $value;
		continue;
	}
	if ($key == 'group') {
		$group = $value;
		continue;
	}
	if ($key == 'helpers') {
		$helpers = explode('+', $value);
		continue;
	}
	if ($key == 'callback') {
		$callback = $value;
		continue;
	}
	$key = str_replace('~', '', $key, $count);
	if (array_key_exists($key, $sql_map)) {
		// Ignore and/or on first item:
		if ($i > 0 && isset($wheres[$key])) {
			$and_or = (bool) $count ? ' OR ' : ' AND ';
		}  else {
		    $and_or = '';
		}
		// Prepared statements don't like ! in placeholders, so replace with N:
		$val_key          = ':' . str_replace('!', 'N', $key) . $i;
		$wheres[$key][]   = $and_or . sprintf($sql_map[$key], $val_key);
		$values[$val_key] = $value;
		$i++;
	}
}

$sql = $main_sql . "\nWHERE ";
$c = count($wheres);
$i = 0;
foreach ($wheres as $where) {
	if (count($where) > 1) {
		$sql .= '(' . implode("\n", $where) . ')';
	} else {
	    $sql .= $where[0];
	}
	if ($i < $c) {
		$sql .= " AND \n";
	}
	$i++;
}
echo "<pre>\n"; var_dump($sql); echo "</pre>\n"; exit;
#$sql = $main_sql . "\nWHERE " . implode("\n", $wheres);
#$sql = $main_sql . "\nWHERE " . implode("\n", $wheres);

if ($group) {
	$sql .= "\GROUP BY :group";
	$values[':group'] = $value;
}
/*
if ($order) {
	$sql .= "\nORDER BY :order";
	$values[':order'] = $value;
}
*/
#$sql .= ';';

echo "<pre>\n"; var_dump($wheres); echo "</pre>\n";
echo "<pre>\n"; var_dump($values); echo "</pre>\n";
exit;

#echo "<pre>\n"; var_dump($sql); echo "</pre>\n";# exit;
$sth = $dao->prepare($sql);
#echo "<pre>\n"; var_dump($sth); echo "</pre>\n";

$sth->execute($values);


#echo "<pre>\n"; var_dump($sth); echo "</pre>\n";

$data = $sth->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>\n"; var_dump($sth); echo "</pre>\n";
echo "<pre>\n"; var_dump($data); echo "</pre>\n";

exit;


$json = json_encode($data);
if ($callback) {
	header('Content-type: text/javascipt');
	echo $callback . '(' . $json . ')';
	exit;
}

header('Content-type: application/json');
echo $json;
?>