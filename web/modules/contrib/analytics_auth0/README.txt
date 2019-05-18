CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Analytics With Auth0 module allows developers to declare custom Google
Analytic events tracking while passing a custom dimension of the Auth0's User
ID. 

 * For a full description of the module, visit the project page:
   https://drupal.org/project/analytics_auth0

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/analytics_auth0


REQUIREMENTS
------------

This module requires the following module:

 * Auth0 (https://www.drupal.org/project/auth0
   or https://github.com/auth0/auth0-drupal)


INSTALLATION
------------
 
 * Install the Analytics with Auth0 module as you would normally install
   a contributed Drupal module. Visit: 
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.

    2. Plug your Google Web Property ID into the following code and 
       place in your theme's html.html.twig above the closing body tag:

       <!-- Google Analytics -->
       <!-- Global site tag (gtag.js) - Google Analytics -->
       <script async
       src="https://www.googletagmanager.com/gtag/
       js?id=UA-34092501-10"></script>
       <script>
         window.dataLayer = window.dataLayer || [];
         function gtag(){dataLayer.push(arguments);}
         gtag('js', new Date());
         gtag('config', 'WEB_PROPERTY_ID');
       </script>

    3. Set up a custom dimension for an Auth0 user in your Google
       Analytics account following these instructions:
       https://support.google.com/analytics
       answer/2709829#set_up_custom_dimensions

    4. Add custom event tracking in js/ga_analytics.js using the
       following syntax:
       attach([element], [category], [label], [action (optional)]);


MAINTAINERS
-----------

This module was created by:

 * Beverly Lanning (bemarlan) - https://www.drupal.org/u/bemarlan

as an independent volunteer
