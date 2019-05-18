Drush Task
==========

Synopsis
--------

Provides a way to invoke drush commands from Web actions.

Overview
--------

This provides a drush command 'service' that can be invoked from actions within
the Drupal website.

The simplest example is via Rules - when something happens, run this command.



Setup
-----

The user that the webserver runs as must have execute permissions on the server.
PHP security lockdowns may prevent this in certain cases. 
This is a good thing, as some of the actions available to drush are potentially
dangerous.

On install, you should check the status at 

    `/admin/config/services/drush_task`
    
It will be neccessary to enter the *full* path to the drush script.

Be aware that the environment that the webserver actions runs in is probably
very different from your user shell!
The `$PATH`, the PHP version, and access to the drush aliases may be different!

To address that, many requirements can be addressed within a 
[drushrc.php](https://raw.githubusercontent.com/drush-ops/drush/master/examples/example.drushrc.php)
file.
