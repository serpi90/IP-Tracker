<?php
header('Content-Type: text/plain');
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

if ($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST') {
	// 405 Method Not Allowed
	http_response_code(405);
	die();
}

if (!isset($_REQUEST["site"])) {
	// 400 Bad Request
	http_response_code(400);
	die('no site provided');
}

$site = $_REQUEST["site"];
require_once('config.php');
$db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);
if ($db->connect_errno) {
	http_response_code(500);
	die("Failed to connect to MySQL");
}

switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		$ip = ip_for_site($db, $site);
		if ($ip) echo $ip;
		else http_response_code(404);
		break;
	case 'POST':
		header('Connection: close');
		$ip = detect_client_ip();
		register_site($db, $site, $ip);
		echo $ip;
		break;
	default:
		// 405 Method Not Allowed
		http_response_code(405);
		die();
}
