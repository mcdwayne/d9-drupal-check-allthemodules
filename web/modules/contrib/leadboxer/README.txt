
Module: LeadBoxer
Author: Baris Wanschers <https://www.drupal.org/u/barisw>


Description
===========
Adds the LeadBoxer tracking system to your website.

Requirements
============

* LeadBoxer dataset ID

Installation
============
Copy the 'leadboxer' module directory in to your Drupal
sites/all/modules directory as usual.

Usage
=====
In the settings page at admin/config/system/leadboxer you need to enter your
LeadBoxer dataset ID.

All pages will now have the required JavaScript added to the HTML. You can
confirm this by viewing the page source from your browser.

Page specific tracking
======================
The default is set to "Add to every page except the listed pages". By default
the following pages are listed for exclusion:

admin
admin/*
batch
node/add*
node/*/*
user/*/*

Role tracking
=============
You can define user roles for which to include or exclude tracking.
If none of the roles are selected, all users will be tracked.

These defaults are changeable by the website administrator or any other user
with 'Administer LeadBoxer' permission.
