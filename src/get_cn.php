<?php
require_once("init.php");
require_once("config.php");
require_once('templates/header.php');


$auth = new MozillaAuthAdapter();
$search = new MozillaSearchAdapter($ldapconn);

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
	}
}

get_ldap_cn("bsullins@mozilla.com");

require_once('templates/footer.php'); 

?>






