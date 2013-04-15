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
		
		//are we checking for specific LDAP group membership?
		if (LDAP_GROUP_CHECK) {
			
			//Check the ldap group set in the local-config.php file
			if (check_ldap_group($_SERVER["PHP_AUTH_USER"])) {

				echo '<div id="msg"><h1>Need a Tableau Account?&nbsp;&nbsp;<button class="moz-tableau-login">Create Account</button></h1></div><div id="results"></div><h3>What is Tableau Server?</h3><iframe width="853" height="480" src="http://www.youtube.com/embed/uGgkiBhkRHk" frameborder="0" allowfullscreen></iframe>';
				
			} else {
				echo '<div id="msg"><h1>Sorry, it looks like you\'re not in the "' . LDAP_SEC_GROUP . '" LDAP group needed to access Tableau. Please contact your administrator.';

			}
	
		} else {
			
			echo '<div id="msg"><h1>Need a Tableau Account?&nbsp;&nbsp;<button class="moz-tableau-login">Create Account</button></h1></div><div id="results"></div><h3>What is Tableau Server?</h3><iframe width="853" height="480" src="http://www.youtube.com/embed/uGgkiBhkRHk" frameborder="0" allowfullscreen></iframe>';
		}
		
	//if you are not adding them automatically this message will show by default
	} else {

		//print error message with links to fix
		echo '<h1>Oops! I couldn\'t log you in. Do you have an account with Tableau already? If so, please submit a bug, otherwise follow these instructions for getting an account setup: <a href="https://mana.mozilla.org/wiki/display/METRICS/Getting+Access+to+Tableau+Server">link</a></h1>';

	}
	
	
	//we've got a ticket and will log the user in now.
} else {
	echo '<h1>Tableau should load shortly...</h1><meta http-equiv="refresh" content="0;url=' . $trusted_url . '">';
}

echo '</div>';

?>

<?php require_once('templates/footer.php'); ?>
