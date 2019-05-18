INTRODUCTION
-------------

The MyEmma for Webform module creates a handler for adding email addresses
submitted through a webform to a MyEmma account.

REQUIREMENTS
------------

Depends on the [Webform](https://www.drupal.org/project/webform) module 
and installing with composer to include required library: 
[markroland/emma:^3.0](https://github.com/markroland/emma)

INSTALLATION
------------

Install with composer, `composer require drupal/webform_myemma`. 

CONFIGURATION
-------------

1. Add your MyEmma credentials to the configuration at 
/admin/config/services/webform_myemma. 
2. Add the MyEmma handler to your webform,
assign the email address and group id.
