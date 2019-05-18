ABOUT
=====

A simple alternative to Me Aliases for Drupal 8, this module simply
redirects all /me/* paths to the appropriate /user/%user/* page.

It does this as a 302 redirect in case you ever wish to change how this
works.

DEPENDENCIES
============

None.


INSTALLATION
============

Simply enable the module. There is no configuration needed.

USAGE
=====

Create a link anywhere (a menu, in your content, on another site) to [yoursite.com]/me/[anything] and the module will automatically 
redirect a user to the appropriate user account page if they have permission to go there.

If not, they will be shown an Access Denied message.
