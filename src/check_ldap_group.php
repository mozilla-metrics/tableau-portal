<?php

require_once('init.php');
$ldap = get_ldap_connection();
include 'tableau_trusted.php';

if($users=check_ldap_group($_SERVER["PHP_AUTH_USER"])) {
	
	echo "Yes, you may proceed!";
	
} else {
	
	echo "<h1>Oops! Can't find that user</h1>";
}

?>