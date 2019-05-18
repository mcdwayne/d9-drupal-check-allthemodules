Overview:
-------------------------------

If you log out of a Drupal site and then hit the back button, 
you can see pages from the authenticated user's previous session.

This could be a problem on public computers, if the authenticated user
 had permission to see content protected by node access (or similar).

So the logout redirect module is designed to stop this behaviour.

So after user logging off if browser back button is clicked then this module is 
redirecting user to default drupal login page or you can redirect that user to 
custom login page which can be set by administrator from configuration form.



Installation and configuration:
-------------------------------

Installation is as simple as copying the module into your 'modules' directory,
then enabling the module.

Enter url on which you wants to redirect user after logging off and if 
user press browser back button.
 
'Configuration >> System >> Logout Redirect Configuration'
The path for this is /admin/config/logout/redirect/settings

For a full description visit project page:
https://www.drupal.org/project/logout_redirect

Bug reports, feature suggestions and latest developments:
http://drupal.org/project/issues/logout_redirect


---REQUIREMENTS---

*None. (Other than a clean Drupal 8 installation)
 

MAINTAINERS
-----------
Current maintainers:
* Hardik Patel - https://www.drupal.org/user/3316709/


