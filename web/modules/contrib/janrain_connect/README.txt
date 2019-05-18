CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Janrain Sync
 * Janrain Users
 * Forms as block
 * Maintainers


INTRODUCTION
------------

This project allows you to quickly integrate the Janrain Solution into your
Drupal websites, helping you to improve your registration conversion rates by
allowing your customers to sign in, register, and share through their social
network of choice, such as Facebook, Google, Yahoo!, OpenID, LinkedIn, Twitter,
and many others.

Janrain’s Customer Identity Management Platform makes it easy for you to acquire
and recognize customers across all devices and collect the accurate customer
profile data you need to power more personalized marketing.

REQUIREMENTS
------------

No special requirements


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8 for further
information.


CONFIGURATION
-------------

 * Please configure: Administration » Configuration » People » Janrain Settings

  In General, please configure the fields with your Janrain settings:
 
   - Application ID;
   - Client ID;
   - Capture Server URL;
   - Entity Type;
   - Flow Name;
   - Flow Version;
   - English code(s);
   - Save.


JANRAIN SYNC
------------

 * When run the Janrain Sync the flow data will saved in yml file in Drupal. The
   sync significantly improves project performance.

  For execute the sync, please access:
  Administration » Configuration » People » Janrain Sync

  - Set Janrain directory (default is "janrain");
  - Click in "Sync".


JANRAIN USERS
------------

 * You can choose fields to be persisted in the Drupal user. For configure it,
   please access:

   Administration » Configuration » People » Janrain Users

  - Check fields to be persisted;
  - Click in "Save configuration".


FORMS AS BLOCK
------------

 * You can choose forms to render as block. For configure it, please access:

   Administration » Configuration » People » Janrain Forms

  - Check forms to be render as block;
  - Click in "Save configuration".

   After that, you can access Janrain Form blocks in:
   Administration » Structure » Block layout


MAINTAINERS
-----------

Current maintainers:
 * Renato Gonçalves (RenatoG) - https://www.drupal.org/user/3326031
 * Luis Ribeiro (luisr) - https://www.drupal.org/user/1902616
 * Rodrigo Menardi (rmenardi) - https://www.drupal.org/user/3423385
 * Andre Toledo (atoledo@ciandt.com) - https://www.drupal.org/user/1674098
 * Felipe Ribeiro (felribeiro) - https://www.drupal.org/user/1902796
 * Geovanni Conti (geovanni.conti) - https://www.drupal.org/user/3200019
