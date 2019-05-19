CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Vk authorization module allows users to register and login to your 
Drupal 8 site with their Vkontakte account.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/sandbox/evseenko/3005662


REQUIREMENTS
------------

No special requirements.


INSTALLATION
------------

 * Install as you would normally install a Drupal module.
   See: https://www.drupal.org/node/895232 for further information.
  
 * This module defines a path "/user/vk/login" which redirects the user to 
   Vkontakte for authentication. Site builders can place (and theme) a link
   to "/user/vk/login" wherever on the site, for example in a custom block 
   which is shown only to anonymous users.

CONFIGURATION
-------------

 * Go to /admin/config/people/vk_authentication and add your 
   Vk application settings.
   Could be found in https://vk.com/editapp?id=your_app_id&section=options.
   
 * Save your settings.


MAINTAINERS
-----------

Current maintainers:
 * Pavel Evseenko (evseenko) - https://www.drupal.org/u/evseenko
