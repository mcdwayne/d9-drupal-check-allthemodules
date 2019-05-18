
Module: DebugMe
Author: Piotr Baj <https://www.drupal.org/u/quex>


Description
===========
Adds the DebugMe to your website.

Requirements
============

* DebugMe project id


Installation
============
Copy the 'debugme' module directory in to your Drupal 'modules'
directory as usual.


Usage
=====
In the settings page enter your DebugMe project ID.

All pages will now have the required JavaScript added to the
HTML footer can confirm this by viewing the page source from
your browser.
DebugMe code is viewable only for allowed roles, by default
only administrators can see DebugMe code.

To allow other users view DebugMe code please set
"Use DebugMe" permission for selected role.

Beside role access there is option for show/hide DebugMe code
on selected pages.

Page specific code
====================================================
The default is set to "Add to every page except the listed pages". By
default the following pages are listed for exclusion:

/admin
/admin/*
/batch
/node/add*
/node/*/*
/user/*/*

These defaults are changeable by the website administrator or any other
user with 'Administer DebugMe' permission.


Text format: Full HTML
