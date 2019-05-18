Login Disable
=============

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

Prevent users from logging in to your Drupal site unless they know the secret
key to add to the end of the login form page.
( default: http://example.com/?q=user/login&admin )

If your site has clean urls enabled you may use
http://example.com/user/login?admin instead.

If a user does find out about the secret key they will still have their user
account role checked during authentication. If they do not have the 'bypass
disabled login' granted they will be refused access and displayed a
customisable "denied" message.

REQUIREMENTS
------------

This module requires the following modules:

 * User

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

 * Configure user permissions in Configuration » People » Login Disable:

   - Check Prevent user login checkbox to prevent user login for all the roles
   except those having login disable permission.

   - Add the access key. This key is used to by pass the user login.

   - End User Message: Modify the default UI message as required.

 * Try logging in as anonymous user, unless access key is added, login form is
 disabled unless url is baseurl/user/login?{access_key}.

MAINTAINERS
-----------
 * Developed by Budda / Mike Carter @ Ixis
 * Neha(nehajyoti)
