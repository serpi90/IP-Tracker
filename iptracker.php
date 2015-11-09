<?php
// Mysql Configuration
$mysql_host = "";
$mysql_database = "";
$mysql_user = "";
$mysql_password = "";

// Function to get the client ip address
function get_client_ip() {
    $ipaddress = '';
    if ($_SERVER['HTTP_CLIENT_IP'])
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if($_SERVER['HTTP_X_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if($_SERVER['HTTP_X_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if($_SERVER['HTTP_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if($_SERVER['HTTP_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if($_SERVER['REMOTE_ADDR'])
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
 
    return $ipaddress;
}
// End if no site is provided
isset( $_REQUEST["site"] ) or die();

// Fetch parameters to be used
$site_name = $_REQUEST["site"];
$address = ip2long(get_client_ip());

$db_conn = new mysqli( $mysql_host, $mysql_user, $mysql_password, $mysql_database );
// Check if the provided site exists
$stmt = $db_conn->prepare("SELECT COUNT(site) FROM ip WHERE site = ?");
$stmt->bind_param("s", $site_name);
$stmt->execute() or die( $stmt->error );
$stmt->bind_result($site_exists);
$stmt->fetch();
$stmt->close();
$site_exists = $site_exists == 0 ? false : true;

// ----
if( isset( $_REQUEST["set"] ) ) {
	if( !$site_exists ) {
		// Handle site IP registration
		$stmt = $db_conn->prepare("INSERT INTO ip (site,ip) VALUES (?,?)");
		$stmt->bind_param("si", $site_name, $address);
		$stmt->execute() or die( $stmt->error );
		$stmt->close();
	} else {
		// Handle site IP update
		$stmt = $db_conn->prepare("UPDATE ip SET ip = ? WHERE site = ?");
		$stmt->bind_param("is", $address, $site_name);
		$stmt->execute() or die( $stmt->error );
		$stmt->close();
	}
} else {	
	// Handle site IP request
	$site_exists or die();
	$stmt = $db_conn->prepare("SELECT ip FROM ip WHERE site = ?");
	$stmt->bind_param("s", $site_name);
	$stmt->execute() or die( $stmt->error );
	$stmt->bind_result($address);
	$stmt->fetch();
	$stmt->close();
	// Display the obtained IPv4 address
	echo long2ip($address);
}
?>
