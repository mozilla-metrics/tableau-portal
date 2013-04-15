<!DOCTYPE HTML>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>Tableau Portal</title>
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" />
    <script type="text/javascript" src="js/prototype.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
	<script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
	
	<script> // add user to tableau ajax call
	$(document).ready(function(){
	  $("button").click(function(event){

		// alert("starting ajax call");

		$("#msg").html('<h3>Creating account <img src="img/ajax-loader.gif" style="vertical-align:bottom;"/></h3>');
			
		$.post(
			"add_user.php",
		  function(msg){
			$("#msg").html('<h3>Account created! Refresh this page to login whenever you are ready.</h3>');
			$("#results").html(msg);
		  });

	  });
	});	</script>
  </head>

<body>