
DESCRIPTION
---------------------

Allows the management of one or more "Splash Screens screenshot", which are
modal popups. If they accept or reject the offer, the modal will close.
(If they select Do not Show Again, then a cookie is set so as to avoid futher
display.) You may define path for the splae offer , and you have to create
only one offer for the page. So you con't create two offer for the same page.

The module was born as a way to advertise a website companion app and offer a
button to download, but it's generalized nature has the potential for many
other applications.

REQUIREMENTS
---------------------

Javascript is required.
This project creates a new entity type and thus depends on the Entity API:
http://drupal.org/project/entity

INSTALLATION
------------

		1=> Download and unzip this module into your modules directory.
		2=> Goto Administer > Site Building > Extend and enable this module.
		3=> After enable the module need to clear cache by using 
				url : /admin/config/development/performance

CONFIGURATION
-------------

		1=> To create you first offer visit: admin/content/splash-screen.
		2=> Now click on "Add Splash Screen".
		3=> After creating this you need to go to the block section :
		 		/admin/structure/block and add Splash Screen block into content regions.
		4=> Now you will go the page and see the Splash Screen.
