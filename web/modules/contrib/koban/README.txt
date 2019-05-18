
Module: Koban
Author: Vadim - Devdotcom <https://drupal.org/user/3554395>


Description
===========
Adds Koban tracking system to your website.

Requirements
============

* KobanCrm (https://koban.cloud) account with active API key

Installation
============
Copy the 'koban' module directory in to your Drupal
sites/all/modules directory as usual.

Usage
=====
In the settings page enter your Koban API Key.

All pages will now have the required JavaScript added to the
HTML header.

Page specific tracking
======================
The default is set to "Add to every page except the listed pages". By
default the following pages are listed for exclusion:

admin
admin/*
batch
node/add*
node/*/*
user/*/*

These defaults are changeable by the website administrator or any other
user with 'Administer Koban Tracking' permission.

Like the blocks visibility settings in Drupal core, there is a choice for
"Add if the following PHP code returns TRUE." Sample PHP snippets that can be
used in this textarea can be found on the handbook page "Overview-approach to
block visibility" at http://drupal.org/node/64135.