<?php

require_once('init.php');
$ldap = get_ldap_connection();
include 'tableau_trusted.php';

if(add_tableau_user($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"], get_ldap_cn($_SERVER["PHP_AUTH_USER"]), 'interactor', 'none', '0', 0)) {
	$trusted_url = login_tableau($_SERVER["PHP_AUTH_USER"],TABLEAU_SERVER,'workbooks');
	// echo '<meta http-equiv="refresh" content="2;url=' . $trusted_url . '">';
} else {
	echo "Failed attempt to create user: " . $_SERVER["PHP_AUTH_USER"];
}

?>