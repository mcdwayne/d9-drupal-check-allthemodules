CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module is primarily intended for multilingual sites that detect the user
language via the URL and which utilize different top-level domains (TLDs).

You may have both example.com and example.es, for example.

Administration of such sites can often be frustrating, since cookie policies do
not allow example.es to read the session cookies set upon login to example.com.
Users and administrators must therefore log into each site separately, even
though the installation of Drupal is the same.

This module allows to automatically log the user in when getting a 403 error
page if he/she is logged in on the default language domain.

Note on security:

It is recommended to serve i18n_sso/* paths over HTTPS. That will provide extra
security.

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install and enable this module like any other drupal 8 module.


CONFIGURATION
-------------

This module adds a javascript file on 403 pages to log the user in using ajax
requests. During the requests the javascript add some markup on the page to
inform the visitor about the status of the process.

You can control the jQuery selector on which to append this markup by overriding
the selector in the services.yml file of your website. See the parameters
section of the i18n_sso.services.yml file.


HOW IT WORKS
------------

On a website with the domains example.com and example.es. example.com is the
default language domain.

When getting a 403 page on example.es, the module adds a JS file.

The javascript makes a first request on the endpoint
example.com/i18n_sso/get-token:
  * if the user is not logged in on this domain, a message is displayed asking
    the user to log in on the default language domain (example.com).
  * otherwise a token is retrieved or generated for this user and sent back in
    the response. The token lifetime is 10 minutes.

If a token is obtained, the javascript makes a second request on
example.es/i18n_sso/login with the token in parameter:
  * if the token is still valid, the user is logged in and so a session cookie
    can be set on the example.es domain. And the page is reloaded.
  * if the token is not valid anymore a message is displayed to inform the user
    that an error has occurred.


MAINTAINERS
-----------

Current maintainers:
 * Kevin Kaland (wizonesolutions) - https://www.drupal.org/user/739994
 * Bastien Rigon (barig) - https://www.drupal.org/user/2537604
 * Florent Torregrosa (Grimreaper) - https://www.drupal.org/user/2388214

This project has been sponsored by:
 * Smile - http://www.smile.fr
