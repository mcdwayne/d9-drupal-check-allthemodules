Webtrees Integration
====================

This module provides limited integration of the Webtrees genealogy website with Drupal.
It is designed to provide single sign on support with Drupal being the front-end.
User accounts can come from the Drupal or Webtrees user list or from both lists. 

The system has been test with a Webtrees site that has only one genealogy tree.
It should work with any number of trees but post a bug if you encounter problems.

The system does check both user lists to see if a user is blocked. 

Password reset is done using Drupal but requires that a user has already logged in once. 
This needs to be address and is on the to do list.


Installation
------------
Start with an installed version of Webtrees (http://www.webtrees.net) and
an installed version of Drupal. The suggested configuration is Drupal in the
root URL and Webtrees in a directory like /webtrees.

Drupal can use any database but Webtrees uses MySQL.
If Drupal is using MySQL then it is possible to use the same user and password.

Copy this module into the Drupal modules directory. 

Add the following lines to the Webtrees webserver configuration. 
This example is for Apache. Adjust if you are using a different webserver.

Redirect "/webtrees/login.php" "/user/login"
Redirect "/webtrees/logout.php" "/user/logout"
Redirect "/webtrees/edituser.php" "/user"

These lines will redirect the Webtrees login/logout and user URLs 
so that the Drupal URLs will be used instead. Note, the lines above
assume that Drupal is installed in the root and Webtrees is in /webtrees.
Modify as necessary for your configuration.

MySQL Changes
-------------
If you plan on using the Drupal user account to access the Webtrees 
then you will need to make sure the user has access to the database. 
This is typically done with these commands. 

mysql> GRANT ALL PRIVILEGES ON webtrees.* TO 'username'@'localhost' IDENTIFIED BY 'password'; 


Enabling Single Sign-on
-----------------------
1. You need to install the Webtrees module from the Extend administration page.
2. Look for the Webtrees Administration section under Configuration.
3. Enable Webtrees support from the Settings form.
   You should also check out all the settings. 
   The Database section defines how to access the Webtrees MySQL database. 

   The Configuration section should have the Login option checked. 
   This section is also where you choose the primary user database. 
   The reverse lookup allows both the Drupal and Webtrees databases to provide users not found in the primary database.

   The Role mapping section is used when a user needs to be created in the other user database.
   There is a mapping for Drupal and for Webtrees.

   The Webtrees Create User Defaults section is used when creating a new Webtrees user. 

   Single sign on will be enabled once the configuration is saved.

4. The Testing form can be used when single sign on is disabled. 
   Click the Test login button once the user name and password (optional) are entered.
   This will attempt to access the Webtrees user information and display it. 
   Check the settings and system configuration if a user cannot be accessed using the Test form.


How it works
------------
The webtrees.module has hooks for the login and logout support. 
It accesses the Webtrees database using Mysqli. 
The src/Helper/WebtreesUser.php file has the bulk of the user management support.


To Do
-----
1. Mass import of users for both directions
2. Password reset for Webtrees user who has not logged it
3. Selective logging
4. Drupal 7 support

