<?php
require_once('init.php');
$ldap = get_ldap_connection();
require_once('templates/header.php');

// Tableau-provided functions for doing trusted authentication
include 'tableau_trusted.php';

// Check if the user is in a specific LDAP group, not quite working...
// $search = new MozillaSearchAdapter($ldap);
// $sr = $search->query_users("cn=*sullins*");
// print_r($sr);



function add_tableau_user ($username, $pwd, $name, $level, $admin, $publisher) {
	//create file
	$filename = "users";
	$users = getcwd() . '/'.$filename.'.csv';
	//create new file
	$filehandle = fopen($users, 'w') or die("can't open file");
	fclose($filehandle);
	$fp = fopen($users, 'w');
	
	//add data to file	
	$csv_fields = array();
    $csv_fields[0] = array();
    $csv_fields[0][] = $username;
    $csv_fields[0][] = $pwd;
    $csv_fields[0][] = $name;
    $csv_fields[0][] = $level;
    $csv_fields[0][] = $admin;
    $csv_fields[0][] = $publisher;
	
	
	foreach ($csv_fields as $fields) {
	fputcsv($fp, $fields);
	}
	fclose($fp);

	//run tabcmd to create user
	$login = shell_exec('./tabcmdexe login --server https://dataviz.mozilla.org --username ' . TABLEAU_ADMIN . ' --password ' . TABLEAU_ADMIN_PW);
	
	echo "<h1>Login to Tableau</h1><pre>" . $login . "</pre>";
	
	//create user
	$createusers = shell_exec('./tabcmdexe createusers "' . $users . '"');
	
	echo "<h1>Creating User</h1><pre>" . $createusers . "</pre>";
	
	//add user to ldap group
	
	$addusers = shell_exec('./tabcmdexe addusers "ldap" --users "' . $users .'"');
	
	echo "<h1>Add User to group</h1><pre>" . $createusers . "</pre>";
	
	//delete file
	unlink($users);
	
	return true;

}

function login_tableau ($user, $host, $home) {
	
	if(!$trusted_url=get_default_url($user, $host, $home)){
		return false;
	} else {
		return $trusted_url;
	}
}

?>




<h1>Hi <?php echo $_SERVER["PHP_AUTH_USER"] . "!" ?></h1><br/>

<?php

//Log the user in or add them to the server if the ADD_TABLEAU_USERS bit is set to TRUE in the config-local.php file
if (!$trusted_url=login_tableau($_SERVER["PHP_AUTH_USER"],'dataviz.mozilla.org','projects')) {
	
	//add user to the server if config-local.php has bit flipped
	if (ADD_TABLEAU_USERS) {
					
		if (add_tableau_user($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"], $_SERVER["PHP_AUTH_USER"], 'interactor', 'none', '0')){
			
			//get url
			$trusted_url = login_tableau($_SERVER["PHP_AUTH_USER"],'dataviz.mozilla.org','projects');
			
			//login
			echo '<meta http-equiv="refresh" content="0;url=' . $trusted_url . '">';
			
		} else {
			// mail('bsullins@mozilla.com', 'Error logging in for: ' . $_SERVER["PHP_AUTH_USER"], 'Invalid login;');
			echo "Oops! Something went wrong, the Metrics team has been notified. If this continues to happen please submit a bug.";
		}
	} else {

		//send error email. Probably good to log this somewhere instead of just emailing
		// mail('bsullins@mozilla.com', 'Error logging in for: ' . $_SERVER["PHP_AUTH_USER"], 'Invalid login;');
		
		//print error message with links to fix
		echo "<h1>Oops! I couldn't log you in. Do you have an account with Tableau already? If so, please submit a bug, otherwise follow these instructions for getting an account setup: link.</h1>";

	}
	
} else {
	echo 'Tableau should load shortly...<meta http-equiv="refresh" content="0;url=' . $trusted_url . '">';
}

?>

<?php require_once('templates/footer.php'); ?>
