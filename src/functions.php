<?php

function ask() {
  header('WWW-Authenticate: Basic realm="Mozilla Corporation - LDAP Login"');
}

function wail_and_bail() {
  header('HTTP/1.0 401 Unauthorized');
  ask();
  print "<h1>401 Unauthorized</h1>";
  die;
}

function get_ldap_connection() {
  $ldapconn = ldap_connect(LDAP_HOST);
  $auth = new MozillaAuthAdapter();

  if (!isset($_SERVER["PHP_AUTH_USER"])) {
    ask();
    wail_and_bail();
  } else {
    // Check for validity of login
    if ($auth->check_valid_user($_SERVER["PHP_AUTH_USER"])) {
      $user_dn = $auth->user_to_dn($_SERVER["PHP_AUTH_USER"]);
      $password = $_SERVER["PHP_AUTH_PW"];
    } else {
      wail_and_bail();
    }
  }

  if (!ldap_bind($ldapconn, $user_dn, $_SERVER['PHP_AUTH_PW'])) {
    wail_and_bail();
    die(ldap_error($ldapconn));
  }

  return $ldapconn;
}

/*
function email_to_dn($ldapconn, $email) {
  $user_s = ldap_search($ldapconn, "dc=mozilla", "mail=" . $email);
  $user_s_r = ldap_get_entries($ldapconn, $user_s);
  if ($user_s_r['count'] != 1) {
    die("Multiple DNs match email.");
  }
  return $user_s_r[0]['dn'];
}
*/


function query_users($ldapconn, $filter, $base='', $attributes, $sort=null) {
  $adapter = new MozillaSearchAdapter();
  $conf = $adapter->conf();
  $search = ldap_search($ldapconn, $base, $filter, $attributes);
  ldap_sort($ldapconn, $search, $sort || $conf["ldap_sort_order"] || "sn");
  return ldap_get_entries($ldapconn, $search);
}

/*
// The logic here is that failure to find out who has permissions to edit
// someone else's entry implies that you aren't one of them.
function is_phonebook_admin($ldapconn, $dn) {
  $search = ldap_list(
    $ldapconn,
    "ou=groups, dc=mozilla", "(&(member=$dn)(cn=phonebook_admin))",
    array("cn")
  );
  $results = ldap_get_entries($ldapconn, $search);
  return $results["count"];
}
*/

/*
// Used to create LDAP data structures
function empty_array($element) {
  if (empty($element[0])) {
    return array();
  }
  return $element;
}
*/

/*
// Facilitates in creating user
function get_status($current_org, $current_emp_type) {
  if ($current_emp_type == 'D' ||
      $current_org == 'D') {
    return "DISABLED";
  } else {
    return $current_org . $current_emp_type;
  }
}
*/

/*
function clean_userdata($user_data) {
  global $editable_fields;
  foreach ($editable_fields as $field) {
    $field = strtolower($field);
    if (!isset($user_data[$field])) {
      $user_data[$field] = array('count' => 0, '');
    }
  }
  return $user_data;
}
*/

/*
function everyone_list($ldapconn) {
  $search = ldap_search($ldapconn, 'o=com,dc=mozilla', 'objectClass=mozComPerson');
  ldap_sort($ldapconn, $search, 'cn');
  return ldap_get_entries($ldapconn, $search);
}
*/

function escape($s) {
  return htmlspecialchars($s, ENT_QUOTES);
}

// Normalizes an LDAP entry data structure to a JSON-friendly structure
function normalize($o) {
  if (!is_array($o)) {
    return $o;
  }
  unset($o["count"]);
  $keys = array_keys($o);
  if (count(array_unique(array_map("is_int", $keys))) != 1) {
    $i = 0;
    while (isset($o[$i])){
      unset($o[$i]);
      $i++;
    }
  }
  foreach ($o as &$e) {
    $e = normalize($e);
    if (is_array($e) && count($e) == 1) {
      $e = $e[0];
    }
  }
  return $o;
}

// LDAP escape functions borrowed from PEAR's Net_LDAP_Utils

/**
* Converts all ASCII chars < 32 to "\HEX"
*
* @param string $string String to convert
*
* @static
* @return string
*/
function asc2hex32($string)
{
    for ($i = 0; $i < strlen($string); $i++) {
        $char = substr($string, $i, 1);
        if (ord($char) < 32) {
            $hex = dechex(ord($char));
            if (strlen($hex) == 1) {
                $hex = '0'.$hex;
            }
            $string = str_replace($char, '\\'.$hex, $string);
        }
    }
    return $string;
}

/**
* Escapes a DN value according to RFC 2253
*
* Escapes the given VALUES according to RFC 2253 so that they can be safely used in LDAP DNs.
* The characters ",", "+", """, "\", "<", ">", ";", "#", "=" with a special meaning in RFC 2252
* are preceeded by ba backslash. Control characters with an ASCII code < 32 are represented as \hexpair.
* Finally all leading and trailing spaces are converted to sequences of \20.
*
* @param array $values An array containing the DN values that should be escaped
*
* @static
* @return array The array $values, but escaped
*/
function escape_ldap_dn_value($values = array())
{
    // Parameter validation
    $unwrap = !is_array($values);
    if ($unwrap) {
        $values = array($values);
    }

    foreach ($values as $key => $val) {
        // Escaping of filter meta characters
        $val = str_replace('\\', '\\\\', $val);
        $val = str_replace(',', '\,', $val);
        $val = str_replace('+', '\+', $val);
        $val = str_replace('"', '\"', $val);
        $val = str_replace('<', '\<', $val);
        $val = str_replace('>', '\>', $val);
        $val = str_replace(';', '\;', $val);
        $val = str_replace('#', '\#', $val);
        $val = str_replace('=', '\=', $val);

        // ASCII < 32 escaping
        $val = asc2hex32($val);

        // Convert all leading and trailing spaces to sequences of \20.
        if (preg_match('/^(\s*)(.+?)(\s*)$/', $val, $matches)) {
            $val = $matches[2];
            for ($i = 0; $i < strlen($matches[1]); $i++) {
                $val = '\20'.$val;
            }
            for ($i = 0; $i < strlen($matches[3]); $i++) {
                $val = $val.'\20';
            }
        }

        if (null === $val) {
            $val = '\0';  // apply escaped "null" if string is empty
        }

        $values[$key] = $val;
    }

    if ($unwrap) return $values[0]; else return $values;
}

/**
* Escapes the given VALUES according to RFC 2254 so that they can be safely used in LDAP filters.
*
* Any control characters with an ACII code < 32 as well as the characters with special meaning in
* LDAP filters "*", "(", ")", and "\" (the backslash) are converted into the representation of a
* backslash followed by two hex digits representing the hexadecimal value of the character.
*
* @param array $values Array of values to escape
*
* @static
* @return array Array $values, but escaped
*/
function escape_ldap_filter_value($values = array())
{
    // Parameter validation
    $unwrap = !is_array($values);
    if ($unwrap) {
        $values = array($values);
    }

    foreach ($values as $key => $val) {
        // Escaping of filter meta characters
        $val = str_replace('\\', '\5c', $val);
        $val = str_replace('*', '\2a', $val);
        $val = str_replace('(', '\28', $val);
        $val = str_replace(')', '\29', $val);

        // ASCII < 32 escaping
        $val = asc2hex32($val);

        if (null === $val) {
            $val = '\0';  // apply escaped "null" if string is empty
        }

        $values[$key] = $val;
    }

    if ($unwrap) return $values[0]; else return $values;
}

// Tableau LDAP Functions
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
		
		//set server
		$server = PROTOCOL . '://'  . TABLEAU_SERVER;

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
		if(!$login = shell_exec('./tabcmdexe login --server ' . $server . ' --username ' . TABLEAU_ADMIN . ' --password ' . TABLEAU_ADMIN_PW . ' --no-certcheck')) {
			throw new Exception('Unable to login to Tableau Server: ' . $login);
		}

		if($debug!=0){ echo "<h1>Login to Tableau</h1><pre>" . $login . "</pre>"; }

		//create user
		if(!$createusers = shell_exec('./tabcmdexe createusers "' . $users . '" --no-certcheck')) {
			throw new Exception('Unable to create users: ' . $createusers);
		}

		if($debug!=0){ echo "<h1>Creating User</h1><pre>" . $createusers . "</pre>"; }

		//create ldap group (could be switched off by config)
		if(!$creategroup = shell_exec('./tabcmdexe creategroup "ldap" --no-certcheck')) {
			throw new Exception('Unable to create group "ldap" because: ' . $creategroup);
		}

		//add user to ldap group
		if(!$addusers = shell_exec('./tabcmdexe addusers "ldap" --users "' . $users .'" --no-certcheck')) {
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

function login_tableau ($user, $server, $home) {
	
	if(!$trusted_url=get_default_url($user, $server, $home)){
		
		// debug
		// echo '<script type="text/javascript">alert("unable to generate ticket for ' . $user . ' at ' . $server .' to page ' . $home .'");</script>';
		
		return false;
	} else {
		return $trusted_url;
	}
}

//check ldap group

function check_ldap_group($user){
	    try{
		
			$server=LDAP_HOST;
			$admin='uid=' . BIND_USER . ',ou=logins,dc=mozilla';
			$passwd=BIND_USER_PW;
			$conn=ldap_connect($server);
			$ds=ldap_bind($conn, $admin, $passwd);
			$access=0; //default level of access		

			if (!$ds) { throw new Exception('Unable to connect to LDAP Server');}

			//1st query, get the users mail address
			$dn = "mail=$user,o=com, dc=mozilla"; //the object itself instead of the top search level as in ldap_search
			$filter="(objectclass=*)"; // this command requires some filter
			$justthese = array("mail", "cn", "uid"); //the attributes to pull, which is much more efficient than pulling all attributes if you don't do this
			$sr=ldap_search($conn, $dn, $filter, $justthese);
			$entry = ldap_get_entries($conn, $sr);
			
			
			$uid = $entry[0]["uid"][0];
			$cn = $entry[0]["cn"][0];
			$mail = $entry[0]["mail"][0];
			
			//testing
			// print_r($entry);
			// echo "<h1>1st Query</h1>";
			// echo "cn: " . $entry[0]["cn"][0] . "<br/>";
			// echo "mail: " . $entry[0]["mail"][0] . "<br/>";
			// echo "uid: " . $entry[0]["uid"][0] . "<br/>";


			//2nd query to get groups
			$dn = "ou=groups,dc=mozilla";
			$filter = "(&(member=mail=$mail,o=com,dc=mozilla)(objectClass=groupOfNames))";
			$sr=ldap_search($conn, $dn, $filter);
			$entries = ldap_get_entries($conn, $sr);
			
			
			// echo "<h1>2nd Query</h1>";
			// echo $entries["count"]." entries returned<br/>";

			//print all the groups
			// foreach($entries as $grp) {
			// 	
			// 	echo $grp["cn"][0] . "<br/>";
			// 
			// }


			//group check
			foreach($entries as $grps) {
			            // yes
			            if ($grps["cn"][0]==LDAP_SEC_GROUP) {$access = 1; break;}
			}
			
			if ($access==1) {
				// echo "Yes! $cn is in the group: " . LDAP_SEC_GROUP;
				return true;
			} else {
				// echo "NO! $cn is in the group: " . LDAP_SEC_GROUP;
				return false;
				
			}

			
			ldap_close($conn);

		} catch (Exception $e) {
			echo 'Oops! I countered the following error: ',  $e->getMessage(), "\n";
			return $_SERVER["PHP_AUTH_USER"];
		}
}


