Content Locker
==============

Project site: https://www.drupal.org/project/content_locker

Issues: https://drupal.org/project/issues/content_locker

INTRODUCTION
------------

What Is This?

This set of modules is intended to provide ability to lock content of the site.
To unlock the site content user have to make an action either to login/register
or agree with the site requirements provided.

REQUIREMENTS
------------

It will require Shortcode and Field Group modules to be installed first but

INSTALLATION
------------

How To Install The Modules

1. The project installs like any other Drupal module.

There is extensive documentation on how to do this here:
https://drupal.org/documentation/install/modules-themes/modules-8
But essentially: Download the tarball and expand it into the modules/ directory
in your Drupal 8 installation.

2.Within Drupal, enable core Content Locker module
and any Content Locker sub-module you wish to use in Admin menu > Extend.

3.To lock content in body field just simply surround content with 
the shortcode tags:

[content_locker type="locker_name"]
... your content to lock goes here ...
[/content_locker]

The locker_name is the code of the locker.
"log_in" code means that user have to log in or register in order to see 
the content.

"consent" code means that user have to agree with the locker reqiurements which
is usually can be a plain simple question similar to 
"Are you agree with ..... bla bla?"

CONFIGURATION
-------------

Locker general settings.

Each locker have additional settings here 
admin/config/content/content-locker/settings
where you can set up text to show above the locker and text to show 
when user did not meet requirements to unlock the content.
