<?php
require_once('init.php');
$ldap = get_ldap_connection();
require_once('templates/header.php');
// Tableau-provided functions for doing trusted authentication
include 'tableau_trusted.php';


?>

<?php


//Log the user in or add them to the server if the ADD_TABLEAU_USERS bit is set to TRUE in the config-local.php file
if (!$trusted_url=login_tableau($_SERVER["PHP_AUTH_USER"],TABLEAU_SERVER,'projects')) {
	
	//add user to the server if config-local.php has bit flipped
	if (ADD_TABLEAU_USERS) {
		
		echo '<div id="msg"><h1>Need a Tableau Account?&nbsp;&nbsp;<button class="moz-tableau-login">Create Account</button></h1></div><h3>What is Tableau?</h3><iframe width="560" height="315" src="http://www.youtube.com/embed/OaQdWeFpov8" frameborder="0" allowfullscreen></iframe>';

	} else {

		//print error message with links to fix
		echo '<h1>Oops! I couldn\'t log you in. Do you have an account with Tableau already? If so, please submit a bug, otherwise follow these instructions for getting an account setup: <a href="https://mana.mozilla.org/wiki/display/METRICS/Getting+Access+to+Tableau+Server">link</a></h1>';

	}
	
} else {
	echo '<h1>Tableau should load shortly...</h1><meta http-equiv="refresh" content="0;url=' . $trusted_url . '">';
}

echo '</div>';

?>

<?php require_once('templates/footer.php'); ?>
