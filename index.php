<?php
// Mysql Configuration
require_once('config.php');

function detect_client_ip()
{
	$ip_address = 'UNKNOWN';
	if ($_SERVER['HTTP_CLIENT_IP']) $ip_address = $_SERVER['HTTP_CLIENT_IP'];
	else if ($_SERVER['HTTP_X_FORWARDED_FOR']) $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if ($_SERVER['HTTP_X_FORWARDED']) $ip_address = $_SERVER['HTTP_X_FORWARDED'];
	else if ($_SERVER['HTTP_FORWARDED_FOR']) $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
	else if ($_SERVER['HTTP_FORWARDED']) $ip_address = $_SERVER['HTTP_FORWARDED'];
	else if ($_SERVER['REMOTE_ADDR']) $ip_address = $_SERVER['REMOTE_ADDR'];
	return $ip_address;
}
function is_site_registered($db, $site)
{
	$site_count = 0;
	$stmt = $db->prepare("SELECT COUNT(`site`) FROM `ip` WHERE `site` = ?");
	$stmt->bind_param("s", $site);
	$stmt->execute() or die($stmt->error);
	$stmt->bind_result($site_count);
	$stmt->fetch();
	$stmt->close();
	return $site_count > 0;
}
function register_site($db, $site, $ip)
{
	$address = ip2long($ip);
	$stmt = $db->prepare("INSERT INTO `ip` (`site`,`ip`) VALUES (?,?) ON DUPLICATE KEY UPDATE `ip` = ?");
	$stmt->bind_param("sii", $site, $address, $address);
	$stmt->execute() or die($stmt->error);
	$stmt->close();
}
function ip_for_site($db, $site)
{
	$address = null;
	$stmt = $db->prepare("SELECT `ip` FROM `ip` WHERE site = ?");
	$stmt->bind_param("s", $site);
	$stmt->execute() or die($stmt->error);
	$stmt->bind_result($address);
	$stmt->fetch();
	$stmt->close();
	return long2ip($address);
}
// End if no site is provided
isset($_REQUEST["site"]) or die('no site provided');

// Fetch parameters to be used
$site = $_REQUEST["site"];
$ip = detect_client_ip();

$db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);
if ($db->connect_errno) die("Failed to connect to MySQL");

if (isset($_REQUEST["set"])) {
	register_site($db, $site, $ip);
	echo $ip;
} else if (is_site_registered($db, $site)) {
	echo ip_for_site($db, $site);
}
