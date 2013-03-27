Thanks for downloading the Tableau Portal app!

This app has 2 primary functions:

 1. Provide a method for users to login to Tableau Server with their Open LDAP credntials.
 2. Automatically create new valid LDAP users in Tableau Server


Function 1: LDAP Login

	This works by simply placing the app /trunk inside of a virtual host on your apache server running PHP with PECL_HTTP extensions. When browsing to this host your users will be prompted with an LDAP authentication dialogue. Once they have entered valid credentials a trusted ticket will be generated in Tableau and they will be logged in. If they do not have any account they will receive an error page that can be customized with instructions on how to gain access. In addition, if you choose to, you can change the ADD_TABLEAU_USERS config option to true to have the next function executed.
	
Function 2: Automate account creation

	When a valid LDAP user fails to generate a trusted ticket it is assumed this is because they do not have a valid Tableau Server account. This part of the app is then executed to create their account using their LDAP username and password. For this to work you must set the configuration variables for the ADD_TABLEAU_USERS to true, as well as the admin username and password. This function uses a version of Tableau's command line utility that has been ported over to linux which is included in the app called 'tabcmdexe'
	
	
**** Setup ****

 1. Download the app
 2. Create a virtual host on your apache server pointed to the app
 3. Copy config-local.php-dist and save-as config-local.php in the apps root directory.
 4. Edit the config-local.php file with your LDAP server and admin credentials. You may also turn on automatic user creation.
 5. Revel in your accoplishments!

**** Future Enhancements ****

 1. Add a 'login with LDAP' button to the Tableau Server login page: 
 2. Create users with their common name "cn" LDAP Attribute instead of just their username.
 3. Sync LDAP groups when creating new users
 4. Handle changed passwords to keep them in sync w/ Tableau (security concerns)