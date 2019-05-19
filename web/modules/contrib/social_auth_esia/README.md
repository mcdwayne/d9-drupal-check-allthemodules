CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The module provides integration for <a href="https://www.drupal.org/project/social_auth">Social Auth</a> module with <a href="https://esia.gosuslugi.ru">ESIA</a> via OAuth 2.0.

ESIA from Russian "ЕСИА", which is "Единая система идентификации и аутентификации". Translated as "Unified identification and authentication system".

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/social_auth_esia
 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/social_auth_esia


REQUIREMENTS
------------

This module requires several Drupal modules and some composer dependencies:

 * [drupal/social_auth]\: ^2.0
 * [ekapusta/oauth2-esia]

INSTALLATION
------------

Module must be installed via composer.

 * Install the Social Auth ESIA module as you would normally install a composer
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to ESIA settings form (/admin/config/social-api/social-auth/esia): Administration > Configuration > Social API > User uthentication, click on ESIA tab.
    3. Fill all required settings as you need and save it.
    4. Visit the external documentation page for more details.


MAINTAINERS
-----------

 * Nikita Malyshev (Niklan) - https://www.drupal.org/u/niklan

[Social Auth]: https://www.drupal.org/project/social_auth
[ESIA]: https://esia.gosuslugi.ru
[ekapusta/oauth2-esia]: https://packagist.org/packages/ekapusta/oauth2-esia
[drupal/social_auth]: https://www.drupal.org/project/social_auth