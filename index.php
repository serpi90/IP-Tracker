<?php
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
function register_site($db, $site, $ip)
{
	$address = inet_pton($ip);
	$stmt = $db->prepare("INSERT INTO `ip` (`site`,`ip`) VALUES (?,?) ON DUPLICATE KEY UPDATE `ip` = ?");
	// I still don't know why this must be sss insteas of sbb, php is weird.
	$stmt->bind_param("sss", $site, $address, $address);
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
	$found = $stmt->fetch();
	$stmt->close();
	if (!$found) return null;
	$bytes = unpack("N4", $address); // Network orders is always Big Endian
	if ($bytes[2] === 0 && $bytes[3] === 0 && $bytes[4] === 0) {
		// Assume IPv4
		return long2ip($bytes[1]);
	} else {
		// Assume IPv6
		return inet_ntop($address);
	}
}
// End if no site is provided
isset($_REQUEST["site"]) or die('no site provided');

// Fetch parameters to be used
$site = $_REQUEST["site"];
$ip = detect_client_ip();

require_once('config.php');
$db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);
if ($db->connect_errno) die("Failed to connect to MySQL");

if (isset($_REQUEST["set"])) {
	register_site($db, $site, $ip);
	echo $ip;
} else {
	$ip = ip_for_site($db, $site);
	if ($ip) echo $ip;
}
