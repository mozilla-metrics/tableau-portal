<?php

// Returns a trusted URL for a view on a server for the
// given user.  For example, if the URL of the view is:
//    http://tabserver/views/MyWorkbook/MyView
//
// Then:
//   $server = "tabserver";
//   $view_url = "views/MyWorkbook/MyView";
//

function get_default_url($user,$server,$start_page) {

	  $ticket = get_trusted_ticket($server, $user, $_SERVER['REMOTE_ADDR']);
	  if($ticket > 0) {
	    return "https://$server/trusted/$ticket/";
	  }
	  else {
	    return 0;
	}
	
}

function get_trusted_url($user,$server,$view_url,$params) {
  if (!isset($params)) {
	  $params = ':embed=yes&:toolbar=yes';
}


  $ticket = get_trusted_ticket($server, $user, $_SERVER['REMOTE_ADDR']);
  if($ticket > 0) {
    return "https://$server/trusted/$ticket/$view_url";
  }
  else 
    return 0;
}

// Note that this function requires the pecl_http extension. 
// See: http://pecl.php.net/package/pecl_http

// the client_ip parameter isn't necessary to send in the POST unless you have
// wgserver.extended_trusted_ip_checking enabled (it's disabled by default)
Function get_trusted_ticket($wgserver, $user, $remote_addr) {
  $params = array(
    'username' => $user,
    'client_ip' => $remote_addr
  );
	
  $resp =  http_parse_message(http_post_fields("https://$wgserver/trusted", $params))->body;

	//testing
	// print_r ($resp);
	
	//actually return it
	return $resp;

}

?>
