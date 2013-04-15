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



function get_ldap_cn($user, $debug=0) {

	try{

		if (!$ds = get_ldap_connection()) { throw new Exception('Unable to connect to LDAP Server');}
		$dn = "mail=$user, o=com, dc=mozilla"; //the object itself instead of the top search level as in ldap_search
		$filter="(objectclass=inetOrgPerson)"; // this command requires some filter
		$justthese = array("cn"); //the attributes to pull, which is much more efficient than pulling all attributes if you don't do this
		if (!$sr=ldap_read($ds, $dn, $filter, $justthese)) { throw new Exception('Incorrect Username or filter');}
		if (!$entry = ldap_get_entries($ds, $sr)) { throw new Exception('Unable to find LDAP entry for ' . $user);}

		if ($debug!=0) {
			echo $entry[0]["cn"][0] . " is the name in LDAP for " . $user;			
		}

		ldap_close($ds);
		return $entry[0]["cn"][0];
		
	} catch (Exception $e) {
		echo 'Oops! I countered the following error: ',  $e->getMessage(), "\n";
		return $_SERVER["PHP_AUTH_USER"];
	}
}

function add_tableau_user ($username, $pwd, $name, $level, $admin, $publisher, $debug=0) {

	try {

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
		if(!$login = shell_exec('./tabcmdexe login --server ' . TABLEAU_SERVER . ' --username ' . TABLEAU_ADMIN . ' --password ' . TABLEAU_ADMIN_PW)) {
			throw new Exception('Unable to login to Tableau Server: ' . $login);
		}

		if($debug!=0){ echo "<h1>Login to Tableau</h1><pre>" . $login . "</pre>"; }

		//create user
		if(!$createusers = shell_exec('./tabcmdexe createusers "' . $users . '"')) {
			throw new Exception('Unable to create users: ' . $createusers);
		}

		if($debug!=0){ echo "<h1>Creating User</h1><pre>" . $createusers . "</pre>"; }

		//add user to ldap group

		if(!$addusers = shell_exec('./tabcmdexe addusers "ldap" --users "' . $users .'"')) {
			throw new Exception('Unable to add users to "ldap" group: ' . $addusers);
		}

		if($debug!=0){ echo "<h1>Add User to group</h1><pre>" . $createusers . "</pre>"; }

		//delete file
		unlink($users);	
		
		return true;
		
	} catch (Exception $e) {
		echo "Oops! Ran into a speed bump: " . $e;
		//delete file
		unlink($users);	
	} 
}

function login_tableau ($user, $host, $home) {
	
	$host = str_replace("https://", "", $host);
	$host = str_replace("http://", "", $host);
	
	if(!$trusted_url=get_default_url($user, $host, $home)){
		return false;
	} else {
		return $trusted_url;
	}
}

?>

<?php

//Log the user in or add them to the server if the ADD_TABLEAU_USERS bit is set to TRUE in the config-local.php file
if (!$trusted_url=login_tableau($_SERVER["PHP_AUTH_USER"],TABLEAU_SERVER,'projects')) {
	
	//add user to the server if config-local.php has bit flipped
	if (ADD_TABLEAU_USERS) {
					
		if (add_tableau_user($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"], get_ldap_cn($_SERVER["PHP_AUTH_USER"]), 'interactor', 'none', '0')){
			
			//get url
			$trusted_url = login_tableau($_SERVER["PHP_AUTH_USER"],TABLEAU_SERVER,'projects');
			
			//login
			echo '<div id="success-msg" style="width=300px; padding-top:40px; text-align:center; vertical-align:baseline; "><h1>Success! Your account in Tableau has been created using your LDAP credentials. You will be redirected there in ~3 seconds. If nothing happens feel free to visit Tableau directly at: ' . TABLEAU_SERVER . '. Have fun seeing your data like never before! <meta http-equiv="refresh" content="7;url=' . $trusted_url . '"></h1></div>';
			
		} else {
			// mail('bsullins@mozilla.com', 'Error logging in for: ' . $_SERVER["PHP_AUTH_USER"], 'Invalid login;');
			echo "Oops! Something went wrong, the Tableau administrators have been notified. If this continues to happen please submit a bug.";
		}
	} else {

		//send error email. Probably good to log this somewhere instead of just emailing
		// mail('bsullins@mozilla.com', 'Error logging in for: ' . $_SERVER["PHP_AUTH_USER"], 'Invalid login;');
		
		//print error message with links to fix
		echo '<h1>Oops! I couldn\'t log you in. Do you have an account with Tableau already? If so, please submit a bug, otherwise follow these instructions for getting an account setup: <a href="https://mana.mozilla.org/wiki/display/METRICS/Getting+Access+to+Tableau+Server">link</a></h1>';

	}
	
} else {
	echo 'Tableau should load shortly...<meta http-equiv="refresh" content="1;url=' . $trusted_url . '">';
}

?>

<?php require_once('templates/footer.php'); ?>
