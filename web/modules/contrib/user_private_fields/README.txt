CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

 This module helps admin to create user field as private. In case if you don't wants
 to display any user field as public .


REQUIREMENTS
------------

This module requires the following modules:

 * user ( Manages the user registration and login system.)
 * field ( Field API to add fields to entities like nodes and users.)

 INSTALLATION
 ------------

  * Install as you would normally install a contributed Drupal module. See:
    https:https://www.drupal.org/documentation/install/modules-themes/modules-8
    for further information.

CONFIGURATION
-------------

 * Configure user field in Configuration » Account settings » Manage fields:

   - We need to add field on admin/config/people/accounts/fields. while giving settings
     to the field we need to enable option "Allow the user to hide this field's value by making it private."
     So, that only admin and respective user is allow to view the user field

MAINTAINERS
-----------

Current maintainers:
 * Rakesh James (rakesh.gectcr) - https://www.drupal.org/u/rakeshgectcr
 * Aditya Anurag (aditya_anurag) - https://www.drupal.org/u/aditya_anurag
 * Mahaveer Singh Panwar (mahavir003) - https://www.drupal.org/u/mahavir003


This project has been sponsored by:
 * Valuebound
   Valuebound is a Drupal based enterprise Web solutions provider with a focus on
   exclusive deliverables across hi-tech, education, media & publishing industries.
   We at Valuebound make the most of Drupal CMS by leveraging some incredible features
   to create customized offerings. Visit http://valuebound.com for more information.
