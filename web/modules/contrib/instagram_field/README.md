CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------
This module allows to use your recent Instagram post as a field in your
content type (or paragraph type), updates when cache timeout is reached. 
Images and links are cached on your server.


REQUIREMENTS
------------

This module requires an Instagram account 


INSTALLATION
------------

Install the Instagram Field module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the instagram_field module
    2. Navigate to Administration > Configuration > Services > Instagram Field
    3. register new client at 
       https://www.instagram.com/developer/clients/manage/ and add redirect URI 
       from callback URL form field (..._instagram_field_callback)
    4. set client ID and client secret from registered client page
    5. authenticate to get the access token (Server-side (Explicit) Flow is 
       used)
    6. add "Instagram recent" field to your content type (or paragraph type)


TROUBLESHOOTING
---------------

 * make sure that no webserver redirection is set for the callback URL (e.g. 
   http -> https)

 * Note: Cache time out of all pages with Instagram fields depend on cache time
   of the Instagram field settings


MAINTAINERS
-----------

 * sleitner - https://www.drupal.org/u/sleitner
