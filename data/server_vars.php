<?php
#echo "<pre>\n";var_dump($_SERVER);echo "</pre>\n";exit;
// Construct the protocol (http|https):
$s                   = empty($_SERVER['SERVER_PORT']) ? '' : ($_SERVER['SERVER_PORT'] == '443' ? 's' : '');
$_SERVER['PROTOCOL'] = preg_replace('#/.*#',  $s, strtolower($_SERVER['SERVER_PROTOCOL']));
// Construct the domain url:
$_SERVER['DOMAIN'] = $_SERVER['PROTOCOL'] . '://' . $_SERVER['SERVER_NAME'];
#echo "<pre>\n";var_dump($_SERVER);echo "</pre>\n";exit;
?>