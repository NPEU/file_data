<?php
$pdo = new PDO('sqlite:geoip.sqlite3');
// Set errormode to exceptions
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS ip_country (
  addr_type` enum('ipv4','ipv6') NOT NULL,
  ip_start` varbinary(16) NOT NULL,
  ip_end` varbinary(16) NOT NULL,
  country` char(2) NOT NULL,
  PRIMARY KEY (`ip_start`)
)");
?>